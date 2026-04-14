<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Brand;
use App\Models\User;
use App\Models\Product;
use App\Models\Service;
use App\Models\Purchase;
use App\Models\CashMovement;
use App\Models\Expense;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\CashRegister;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ReportExportController extends Controller
{
    public function commissionsPdf(Request $request)
    {
        $mode = $request->get('mode', 'detailed');
        $data = $this->getCommissionsData($request);
        
        if ($mode === 'totalized') {
            $data['totalizedData'] = $this->getTotalizedCommissions($data['rawItems'] ?? collect());
            $view = 'reports.commissions-totalized-pdf';
            $filename = 'comisiones-totalizado-' . now()->format('Y-m-d') . '.pdf';
        } else {
            $view = 'reports.commissions-pdf';
            $filename = 'comisiones-discriminado-' . now()->format('Y-m-d') . '.pdf';
        }
        
        unset($data['rawItems']);
        
        $pdf = Pdf::loadView($view, $data);
        $pdf->setPaper('a4', 'portrait');
        
        return $pdf->download($filename);
    }

    private function getCommissionsData(Request $request): array
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $branchId = $request->get('branch_id');
        $userId = $request->get('user_id');
        $categoryId = $request->get('category_id');
        $brandId = $request->get('brand_id');

        $user = auth()->user();

        $query = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->leftJoin('products', 'sale_items.product_id', '=', 'products.id')
            ->leftJoin('services', 'sale_items.service_id', '=', 'services.id')
            ->leftJoin('categories', function ($join) {
                $join->on('categories.id', '=', DB::raw('COALESCE(products.category_id, services.category_id)'));
            })
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->join('users', 'sales.user_id', '=', 'users.id')
            ->where('sales.status', 'completed')
            ->whereDate('sales.created_at', '>=', $startDate)
            ->whereDate('sales.created_at', '<=', $endDate)
            ->where(function ($q) {
                $q->where(function ($pq) {
                    $pq->where('products.has_commission', true)
                       ->whereNotNull('products.commission_value')
                       ->where('products.commission_value', '>', 0);
                })
                ->orWhere(function ($sq) {
                    $sq->where('services.has_commission', true)
                       ->whereNotNull('services.commission_value')
                       ->where('services.commission_value', '>', 0);
                });
            });

        if ($branchId) {
            $query->where('sales.branch_id', $branchId);
        } elseif (!$user->isSuperAdmin()) {
            $query->where('sales.branch_id', $user->branch_id);
        }

        if ($userId) {
            $query->where('sales.user_id', $userId);
        }

        if ($categoryId) {
            $query->where(function ($q) use ($categoryId) {
                $q->where('products.category_id', $categoryId)
                  ->orWhere('services.category_id', $categoryId);
            });
        }

        if ($brandId) {
            $query->where('products.brand_id', $brandId);
        }

        $items = (clone $query)
            ->select(
                'sale_items.*',
                'sales.invoice_number',
                'sales.created_at as sale_date',
                'users.id as user_id',
                'users.name as user_name',
                DB::raw("COALESCE(categories.name, 'Sin categoría') as category_name"),
                DB::raw("COALESCE(brands.name, 'Sin marca') as brand_name")
            )
            ->with(['product', 'service'])
            ->orderBy('users.name')
            ->orderBy('sales.created_at', 'desc')
            ->get();

        // Group by user
        $userCommissions = [];
        $totalCommissions = 0;
        $totalSales = 0;

        foreach ($items as $item) {
            $userId = $item->user_id;
            if (!isset($userCommissions[$userId])) {
                $userCommissions[$userId] = [
                    'user_name' => $item->user_name,
                    'commission' => 0,
                    'sales' => 0,
                    'items_count' => 0,
                    'items' => [],
                ];
            }

            $isService = $item->service_id !== null;
            $commission = $this->calculateCommission($item);
            $userCommissions[$userId]['commission'] += $commission;
            $userCommissions[$userId]['sales'] += (float) $item->total;
            $userCommissions[$userId]['items_count'] += (float) $item->quantity;
            $userCommissions[$userId]['items'][] = [
                'invoice_number' => $item->invoice_number,
                'date' => Carbon::parse($item->sale_date)->format('d/m/Y'),
                'product_name' => $item->product_name,
                'product_sku' => $item->product_sku,
                'category' => $item->category_name,
                'brand' => $item->brand_name,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total' => (float) $item->total,
                'commission' => $commission,
                'is_service' => $isService,
            ];

            $totalCommissions += $commission;
            $totalSales += (float) $item->total;
        }

        // Sort by commission desc
        uasort($userCommissions, fn($a, $b) => $b['commission'] <=> $a['commission']);

        // Get filter names
        $branchName = 'Todas las sucursales';
        if ($branchId) {
            $branch = Branch::find($branchId);
            $branchName = $branch ? $branch->name : 'Sucursal no encontrada';
        } elseif (!$user->isSuperAdmin() && $user->branch_id) {
            $branchName = $user->branch?->name ?? 'Mi sucursal';
        }

        $userName = 'Todos los vendedores';
        if ($userId) {
            $selectedUser = User::find($userId);
            $userName = $selectedUser ? $selectedUser->name : 'Vendedor no encontrado';
        }

        $categoryName = 'Todas las categorías';
        if ($categoryId) {
            $category = Category::find($categoryId);
            $categoryName = $category ? $category->name : 'Categoría no encontrada';
        }

        $brandName = 'Todas las marcas';
        if ($brandId) {
            $brand = Brand::find($brandId);
            $brandName = $brand ? $brand->name : 'Marca no encontrada';
        }

        return [
            'startDate' => Carbon::parse($startDate)->format('d/m/Y'),
            'endDate' => Carbon::parse($endDate)->format('d/m/Y'),
            'branchName' => $branchName,
            'userName' => $userName,
            'categoryName' => $categoryName,
            'brandName' => $brandName,
            'totalCommissions' => $totalCommissions,
            'totalSales' => $totalSales,
            'userCommissions' => array_values($userCommissions),
            'generatedAt' => now()->format('d/m/Y H:i:s'),
            'rawItems' => $items,
        ];
    }

    private function getTotalizedCommissions($items): array
    {
        $totalized = [];

        foreach ($items as $item) {
            $isService = $item->service_id !== null;
            $key = ($isService ? 'S-' : 'P-') . ($item->product_sku ?? $item->product_name);

            if (!isset($totalized[$key])) {
                $totalized[$key] = [
                    'product_name' => $item->product_name,
                    'product_sku' => $item->product_sku,
                    'category' => $item->category_name,
                    'brand' => $item->brand_name ?? 'Sin marca',
                    'is_service' => $isService,
                    'quantity' => 0,
                    'total_sales' => 0,
                    'total_commission' => 0,
                ];
            }

            $totalized[$key]['quantity'] += (float) $item->quantity;
            $totalized[$key]['total_sales'] += (float) $item->total;
            $totalized[$key]['total_commission'] += $this->calculateCommission($item);
        }

        // Sort by commission desc
        uasort($totalized, fn($a, $b) => $b['total_commission'] <=> $a['total_commission']);

        return array_values($totalized);
    }

    private function calculateCommission($item): float
    {
        $basePrice = (float) $item->unit_price;
        $quantity = (float) $item->quantity;

        // Check if it's a service
        if ($item->service_id ?? null) {
            $service = $item->service ?? null;
            if (!$service || !$service->has_commission) {
                return 0;
            }
            $commissionValue = (float) $service->commission_value;
            $commissionType = $service->commission_type;
        } else {
            $product = $item->product ?? null;
            if (!$product || !$product->has_commission) {
                return 0;
            }
            $commissionValue = (float) $product->commission_value;
            $commissionType = $product->commission_type;
        }

        if ($commissionType === 'percentage') {
            return ($basePrice * ($commissionValue / 100)) * $quantity;
        }

        return $commissionValue * $quantity;
    }

    public function productsSoldPdf(Request $request)
    {
        $data = $this->getReportData($request);
        
        $pdf = Pdf::loadView('reports.products-sold-pdf', $data);
        $pdf->setPaper('a4', 'portrait');
        
        $filename = 'productos-vendidos-' . now()->format('Y-m-d') . '.pdf';
        
        return $pdf->download($filename);
    }

    public function productsSoldExcel(Request $request)
    {
        $data = $this->getReportData($request);
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Productos Vendidos');

        // Styles
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'A855F7']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '9333EA']]],
        ];

        $titleStyle = [
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '1E293B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];

        $subtitleStyle = [
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'A855F7']],
        ];

        $summaryStyle = [
            'font' => ['bold' => true, 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
        ];

        $dataStyle = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];

        $row = 1;

        // Title
        $sheet->setCellValue('A' . $row, 'REPORTE DE PRODUCTOS VENDIDOS');
        $sheet->mergeCells('A' . $row . ':H' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray($titleStyle);
        $sheet->getRowDimension($row)->setRowHeight(30);
        $row += 2;

        // Meta info
        $sheet->setCellValue('A' . $row, 'Período:');
        $sheet->setCellValue('B' . $row, $data['startDate'] . ' - ' . $data['endDate']);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue('A' . $row, 'Sucursal:');
        $sheet->setCellValue('B' . $row, $data['branchName']);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue('A' . $row, 'Categoría:');
        $sheet->setCellValue('B' . $row, $data['categoryName']);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue('A' . $row, 'Generado:');
        $sheet->setCellValue('B' . $row, $data['generatedAt']);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row += 2;

        // Summary section
        $sheet->setCellValue('A' . $row, 'RESUMEN');
        $sheet->getStyle('A' . $row)->applyFromArray($subtitleStyle);
        $row++;

        $sheet->setCellValue('A' . $row, 'Total Unidades Vendidas:');
        $sheet->setCellValue('B' . $row, $data['totalQuantity']);
        $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray($summaryStyle);
        $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0');
        $row++;

        $sheet->setCellValue('A' . $row, 'Total Ingresos:');
        $sheet->setCellValue('B' . $row, $data['totalRevenue']);
        $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray($summaryStyle);
        $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('$#,##0');
        $row += 2;

        // Top Products section
        $sheet->setCellValue('A' . $row, 'TOP PRODUCTOS MÁS VENDIDOS');
        $sheet->getStyle('A' . $row)->applyFromArray($subtitleStyle);
        $row++;

        // Top products header
        $sheet->setCellValue('A' . $row, '#');
        $sheet->setCellValue('B' . $row, 'Producto');
        $sheet->setCellValue('C' . $row, 'SKU');
        $sheet->setCellValue('D' . $row, 'Cantidad');
        $sheet->setCellValue('E' . $row, 'Total');
        $sheet->getStyle('A' . $row . ':E' . $row)->applyFromArray($headerStyle);
        $sheet->getRowDimension($row)->setRowHeight(25);
        $row++;

        // Top products data
        $rank = 1;
        foreach ($data['topProducts'] as $product) {
            $sheet->setCellValue('A' . $row, $rank);
            $sheet->setCellValue('B' . $row, $product->product_name);
            $sheet->setCellValue('C' . $row, $product->product_sku);
            $sheet->setCellValue('D' . $row, $product->total_quantity);
            $sheet->setCellValue('E' . $row, $product->total_revenue);
            
            $sheet->getStyle('A' . $row . ':E' . $row)->applyFromArray($dataStyle);
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('$#,##0');
            
            // Highlight top 3
            if ($rank <= 3) {
                $colors = ['FFD700', 'C0C0C0', 'CD7F32'];
                $sheet->getStyle('A' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB($colors[$rank - 1]);
            }
            
            $rank++;
            $row++;
        }
        $row += 2;

        // Detailed sales section
        $sheet->setCellValue('A' . $row, 'DETALLE DE VENTAS');
        $sheet->getStyle('A' . $row)->applyFromArray($subtitleStyle);
        $row++;

        // Detail header
        $headers = ['Fecha', 'Factura', 'Producto', 'SKU', 'Cliente', 'Cantidad', 'P. Unitario', 'Total'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $col++;
        }
        $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray($headerStyle);
        $sheet->getRowDimension($row)->setRowHeight(25);
        $row++;

        // Detail data
        foreach ($data['items'] as $item) {
            $sheet->setCellValue('A' . $row, $item->sale->created_at->format('d/m/Y H:i'));
            $sheet->setCellValue('B' . $row, $item->sale->invoice_number);
            $sheet->setCellValue('C' . $row, $item->product_name);
            $sheet->setCellValue('D' . $row, $item->product_sku);
            $sheet->setCellValue('E' . $row, $item->sale->customer?->full_name ?? 'Consumidor Final');
            $sheet->setCellValue('F' . $row, $item->quantity);
            $sheet->setCellValue('G' . $row, $item->unit_price);
            $sheet->setCellValue('H' . $row, $item->total);
            
            $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray($dataStyle);
            $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('$#,##0');
            $sheet->getStyle('H' . $row)->getNumberFormat()->setFormatCode('$#,##0');
            
            // Alternate row colors
            if ($row % 2 == 0) {
                $sheet->getStyle('A' . $row . ':H' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
            }
            
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Create file
        $writer = new Xlsx($spreadsheet);
        $filename = 'productos-vendidos-' . now()->format('Y-m-d') . '.xlsx';
        
        $tempFile = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($tempFile);
        
        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function getReportData(Request $request): array
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $branchId = $request->get('branch_id');
        $categoryId = $request->get('category_id');

        $user = auth()->user();

        $query = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.status', 'completed')
            ->whereDate('sales.created_at', '>=', $startDate)
            ->whereDate('sales.created_at', '<=', $endDate);

        if ($branchId) {
            $query->where('sales.branch_id', $branchId);
        } elseif (!$user->isSuperAdmin()) {
            $query->where('sales.branch_id', $user->branch_id);
        }

        if ($categoryId) {
            $query->whereHas('product', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        // Summary
        $totalQuantity = (clone $query)->sum('sale_items.quantity');
        $totalRevenue = (clone $query)->sum('sale_items.total');

        // Detailed items
        $items = (clone $query)
            ->select(
                'sale_items.*',
                'sales.invoice_number',
                'sales.created_at as sale_date'
            )
            ->with(['sale.customer', 'sale.branch', 'product.category'])
            ->orderBy('sales.created_at', 'desc')
            ->get();

        // Top products
        $topProducts = (clone $query)
            ->select(
                'sale_items.product_name',
                'sale_items.product_sku',
                DB::raw('SUM(sale_items.quantity) as total_quantity'),
                DB::raw('SUM(sale_items.total) as total_revenue')
            )
            ->groupBy('sale_items.product_name', 'sale_items.product_sku')
            ->orderByDesc('total_quantity')
            ->limit(20)
            ->get();

        // Get branch name
        $branchName = 'Todas las sucursales';
        if ($branchId) {
            $branch = Branch::find($branchId);
            $branchName = $branch ? $branch->name : 'Sucursal no encontrada';
        } elseif (!$user->isSuperAdmin() && $user->branch_id) {
            $branchName = $user->branch?->name ?? 'Mi sucursal';
        }

        // Get category name
        $categoryName = 'Todas las categorías';
        if ($categoryId) {
            $category = Category::find($categoryId);
            $categoryName = $category ? $category->name : 'Categoría no encontrada';
        }

        return [
            'startDate' => Carbon::parse($startDate)->format('d/m/Y'),
            'endDate' => Carbon::parse($endDate)->format('d/m/Y'),
            'branchName' => $branchName,
            'categoryName' => $categoryName,
            'totalQuantity' => $totalQuantity,
            'totalRevenue' => $totalRevenue,
            'items' => $items,
            'topProducts' => $topProducts,
            'generatedAt' => now()->format('d/m/Y H:i:s'),
        ];
    }

    public function profitLossExcel(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $branchId = $request->get('branch_id');
        $user = auth()->user();

        $branchName = 'Todas';
        if ($branchId) {
            $branchName = Branch::find($branchId)?->name ?? 'Todas';
        } elseif (!$user->isSuperAdmin()) {
            $branchId = $user->branch_id;
            $branchName = Branch::find($branchId)?->name ?? '';
        }

        // Build sales query
        $salesQuery = Sale::where('sales.status', 'completed')
            ->whereDate('sales.created_at', '>=', $startDate)
            ->whereDate('sales.created_at', '<=', $endDate);
        if ($branchId) $salesQuery->where('sales.branch_id', $branchId);

        $salesSummary = (clone $salesQuery)->selectRaw('
            COUNT(*) as transactions,
            COALESCE(SUM(sales.subtotal), 0) as subtotal,
            COALESCE(SUM(sales.tax_total), 0) as tax,
            COALESCE(SUM(sales.discount), 0) as discount,
            COALESCE(SUM(sales.total), 0) as revenue
        ')->first();

        $totalRevenue = (float) ($salesSummary->revenue ?? 0);
        $totalTax = (float) ($salesSummary->tax ?? 0);
        $totalDiscount = (float) ($salesSummary->discount ?? 0);
        $totalTransactions = $salesSummary->transactions ?? 0;

        // Cost
        $totalCost = 0;
        $sales = (clone $salesQuery)->with('items.product')->get();
        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                if ($item->product) {
                    $totalCost += $item->product->purchase_price * (float) $item->quantity;
                }
            }
        }

        // Purchases
        $purchasesQuery = Purchase::whereDate('purchases.created_at', '>=', $startDate)
            ->whereDate('purchases.created_at', '<=', $endDate);
        if ($branchId) $purchasesQuery->where('purchases.branch_id', $branchId);
        $totalPurchases = (float) $purchasesQuery->sum('total');

        // Expenses from cash movements
        $expQuery = CashMovement::where('type', 'expense')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate);
        if ($branchId) {
            $expQuery->whereHas('reconciliation', fn($q) => $q->where('branch_id', $branchId));
        }
        $totalCashExpenses = (float) $expQuery->sum('amount');

        // Cash income (ingresos from cash movements)
        $cashIncomeQuery = CashMovement::where('type', 'income')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate);
        if ($branchId) {
            $cashIncomeQuery->whereHas('reconciliation', fn($q) => $q->where('branch_id', $branchId));
        }
        $totalCashIncome = (float) $cashIncomeQuery->sum('amount');

        // Module expenses
        $moduleExpQuery = Expense::whereDate('expenses.created_at', '>=', $startDate)
            ->whereDate('expenses.created_at', '<=', $endDate);
        if ($branchId) {
            $moduleExpQuery->where('expenses.branch_id', $branchId);
        }
        $totalModuleExpenses = (float) $moduleExpQuery->sum('amount');

        $totalExpenses = $totalCashExpenses + $totalModuleExpenses;

        $grossProfit = $totalRevenue + $totalCashIncome - $totalCost;
        $totalIncome = $totalRevenue + $totalCashIncome;
        $grossMargin = $totalIncome > 0 ? ($grossProfit / $totalIncome) * 100 : 0;
        $netProfit = $grossProfit - $totalExpenses;
        $netMargin = $totalIncome > 0 ? ($netProfit / $totalIncome) * 100 : 0;

        // Category breakdown
        $categoryData = SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->leftJoin('products', 'sale_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('sales.status', 'completed')
            ->whereDate('sales.created_at', '>=', $startDate)
            ->whereDate('sales.created_at', '<=', $endDate);
        if ($branchId) $categoryData->where('sales.branch_id', $branchId);

        $categories = $categoryData->select(
            DB::raw("COALESCE(categories.name, 'Sin categoría') as name"),
            DB::raw('SUM(sale_items.subtotal) as revenue'),
            DB::raw('SUM(sale_items.quantity * products.purchase_price) as cost')
        )->groupBy('categories.name')->orderByDesc('revenue')->get();

        // Product profitability
        $productQuery = SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.status', 'completed')
            ->whereDate('sales.created_at', '>=', $startDate)
            ->whereDate('sales.created_at', '<=', $endDate);
        if ($branchId) $productQuery->where('sales.branch_id', $branchId);

        $products = $productQuery->select(
            'products.name', 'products.sku',
            DB::raw('SUM(sale_items.quantity) as qty'),
            DB::raw('SUM(sale_items.subtotal) as revenue'),
            DB::raw('SUM(sale_items.quantity * products.purchase_price) as cost')
        )->groupBy('products.id', 'products.name', 'products.sku')->orderByDesc(DB::raw('SUM(sale_items.subtotal) - SUM(sale_items.quantity * products.purchase_price)'))->get();

        // Payment methods
        $payments = SalePayment::join('sales', 'sale_payments.sale_id', '=', 'sales.id')
            ->join('payment_methods', 'sale_payments.payment_method_id', '=', 'payment_methods.id')
            ->where('sales.status', 'completed')
            ->whereDate('sales.created_at', '>=', $startDate)
            ->whereDate('sales.created_at', '<=', $endDate);
        if ($branchId) $payments->where('sales.branch_id', $branchId);
        $paymentMethods = $payments->select('payment_methods.name', DB::raw('SUM(sale_payments.amount) as total'), DB::raw('COUNT(DISTINCT sales.id) as count'))
            ->groupBy('payment_methods.id', 'payment_methods.name')->orderByDesc('total')->get();

        // Expenses breakdown (cash + module)
        $expBreakdown = CashMovement::where('type', 'expense')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate);
        if ($branchId) {
            $expBreakdown->whereHas('reconciliation', fn($q) => $q->where('branch_id', $branchId));
        }
        $cashExpenses = $expBreakdown->select('concept', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('concept')->orderByDesc('total')->get()
            ->map(fn($e) => (object) ['concept' => $e->concept . ' (Caja)', 'total' => $e->total, 'count' => $e->count]);

        $modExpBreakdown = Expense::whereDate('expenses.created_at', '>=', $startDate)
            ->whereDate('expenses.created_at', '<=', $endDate);
        if ($branchId) {
            $modExpBreakdown->where('expenses.branch_id', $branchId);
        }
        $modExpenses = $modExpBreakdown->select('description as concept', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('description')->orderByDesc('total')->get()
            ->map(fn($e) => (object) ['concept' => $e->concept, 'total' => $e->total, 'count' => $e->count]);

        $expenses = $cashExpenses->concat($modExpenses)->sortByDesc('total');

        // Build Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('P&G');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'A855F7']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '9333EA']]],
        ];
        $titleStyle = ['font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '1E293B']], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]];
        $subtitleStyle = ['font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'A855F7']]];
        $dataStyle = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]]];

        $row = 1;
        $sheet->setCellValue('A' . $row, 'REPORTE DE PÉRDIDAS Y GANANCIAS');
        $sheet->mergeCells('A' . $row . ':F' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray($titleStyle);
        $sheet->getRowDimension($row)->setRowHeight(30);
        $row += 2;

        $sheet->setCellValue('A' . $row, 'Período:'); $sheet->setCellValue('B' . $row, $startDate . ' - ' . $endDate); $sheet->getStyle('A' . $row)->getFont()->setBold(true); $row++;
        $sheet->setCellValue('A' . $row, 'Sucursal:'); $sheet->setCellValue('B' . $row, $branchName); $sheet->getStyle('A' . $row)->getFont()->setBold(true); $row++;
        $sheet->setCellValue('A' . $row, 'Generado:'); $sheet->setCellValue('B' . $row, now()->format('d/m/Y H:i')); $sheet->getStyle('A' . $row)->getFont()->setBold(true); $row += 2;

        // P&G Statement
        $sheet->setCellValue('A' . $row, 'ESTADO DE PÉRDIDAS Y GANANCIAS'); $sheet->getStyle('A' . $row)->applyFromArray($subtitleStyle); $row++;

        $stmtItems = [
            ['Ingresos por Ventas', $totalRevenue, '4472C4'],
            ['(+) Otros Ingresos (Mov. Caja)', $totalCashIncome, '70AD47'],
            ['(-) Descuentos', $totalDiscount, 'E2E8F0'],
            ['Impuestos Recaudados', $totalTax, 'E2E8F0'],
            ['(-) Costo de Ventas', $totalCost, 'ED7D31'],
            ['= UTILIDAD BRUTA', $grossProfit, $grossProfit >= 0 ? '70AD47' : 'FF0000'],
            ['(-) Gastos Operativos', $totalExpenses, 'FF6B6B'],
            ['    Egresos de Caja', $totalCashExpenses, 'E2E8F0'],
            ['    Gastos Registrados', $totalModuleExpenses, 'E2E8F0'],
            ['= UTILIDAD NETA', $netProfit, $netProfit >= 0 ? '00B050' : 'FF0000'],
        ];

        foreach ($stmtItems as $item) {
            $sheet->setCellValue('A' . $row, $item[0]);
            $sheet->setCellValue('B' . $row, $item[1]);
            $sheet->getStyle('A' . $row)->getFont()->setBold(str_starts_with($item[0], '='));
            $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            if (str_starts_with($item[0], '=')) {
                $sheet->getStyle('A' . $row . ':B' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($item[2]);
                if ($item[2] === '00B050' || $item[2] === 'FF0000') {
                    $sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setColor(new Color('FFFFFF'));
                }
            }
            $row++;
        }

        $sheet->setCellValue('B' . $row, 'Margen Bruto: ' . number_format($grossMargin, 1) . '% | Margen Neto: ' . number_format($netMargin, 1) . '%');
        $sheet->getStyle('B' . $row)->getFont()->setItalic(true);
        $row += 2;

        // Additional info
        $sheet->setCellValue('A' . $row, 'INFORMACIÓN ADICIONAL'); $sheet->getStyle('A' . $row)->applyFromArray($subtitleStyle); $row++;
        $sheet->setCellValue('A' . $row, 'Total Transacciones:'); $sheet->setCellValue('B' . $row, $totalTransactions); $row++;
        $sheet->setCellValue('A' . $row, 'Ticket Promedio:'); $sheet->setCellValue('B' . $row, $totalTransactions > 0 ? $totalRevenue / $totalTransactions : 0); $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('$#,##0.00'); $row++;
        $sheet->setCellValue('A' . $row, 'Compras del Período:'); $sheet->setCellValue('B' . $row, $totalPurchases); $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('$#,##0.00'); $row += 2;

        // Payment methods
        $sheet->setCellValue('A' . $row, 'INGRESOS POR MÉTODO DE PAGO'); $sheet->getStyle('A' . $row)->applyFromArray($subtitleStyle); $row++;
        $sheet->setCellValue('A' . $row, 'Método'); $sheet->setCellValue('B' . $row, 'Transacciones'); $sheet->setCellValue('C' . $row, 'Total');
        $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray($headerStyle); $row++;
        foreach ($paymentMethods as $pm) {
            $sheet->setCellValue('A' . $row, $pm->name); $sheet->setCellValue('B' . $row, $pm->count); $sheet->setCellValue('C' . $row, $pm->total);
            $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray($dataStyle);
            $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('$#,##0.00'); $row++;
        }
        $row++;

        // Categories
        $sheet->setCellValue('A' . $row, 'RENTABILIDAD POR CATEGORÍA'); $sheet->getStyle('A' . $row)->applyFromArray($subtitleStyle); $row++;
        $sheet->setCellValue('A' . $row, 'Categoría'); $sheet->setCellValue('B' . $row, 'Ventas'); $sheet->setCellValue('C' . $row, 'Costo'); $sheet->setCellValue('D' . $row, 'Utilidad');
        $sheet->getStyle('A' . $row . ':D' . $row)->applyFromArray($headerStyle); $row++;
        foreach ($categories as $cat) {
            $profit = $cat->revenue - ($cat->cost ?? 0);
            $sheet->setCellValue('A' . $row, $cat->name); $sheet->setCellValue('B' . $row, $cat->revenue); $sheet->setCellValue('C' . $row, $cat->cost ?? 0); $sheet->setCellValue('D' . $row, $profit);
            $sheet->getStyle('A' . $row . ':D' . $row)->applyFromArray($dataStyle);
            $sheet->getStyle('B' . $row . ':D' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            if ($profit < 0) $sheet->getStyle('D' . $row)->getFont()->setColor(new Color('FF0000'));
            $row++;
        }
        $row++;

        // Products
        $sheet->setCellValue('A' . $row, 'RENTABILIDAD POR PRODUCTO'); $sheet->getStyle('A' . $row)->applyFromArray($subtitleStyle); $row++;
        $sheet->setCellValue('A' . $row, 'Producto'); $sheet->setCellValue('B' . $row, 'SKU'); $sheet->setCellValue('C' . $row, 'Cantidad'); $sheet->setCellValue('D' . $row, 'Ventas'); $sheet->setCellValue('E' . $row, 'Costo'); $sheet->setCellValue('F' . $row, 'Utilidad');
        $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray($headerStyle); $row++;
        foreach ($products as $p) {
            $profit = $p->revenue - ($p->cost ?? 0);
            $sheet->setCellValue('A' . $row, $p->name); $sheet->setCellValue('B' . $row, $p->sku); $sheet->setCellValue('C' . $row, $p->qty);
            $sheet->setCellValue('D' . $row, $p->revenue); $sheet->setCellValue('E' . $row, $p->cost ?? 0); $sheet->setCellValue('F' . $row, $profit);
            $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray($dataStyle);
            $sheet->getStyle('D' . $row . ':F' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            if ($profit < 0) $sheet->getStyle('F' . $row)->getFont()->setColor(new Color('FF0000'));
            $row++;
        }
        $row++;

        // Expenses
        if ($expenses->count() > 0) {
            $sheet->setCellValue('A' . $row, 'DESGLOSE DE GASTOS'); $sheet->getStyle('A' . $row)->applyFromArray($subtitleStyle); $row++;
            $sheet->setCellValue('A' . $row, 'Concepto'); $sheet->setCellValue('B' . $row, 'Cantidad'); $sheet->setCellValue('C' . $row, 'Total');
            $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray($headerStyle); $row++;
            foreach ($expenses as $exp) {
                $sheet->setCellValue('A' . $row, $exp->concept); $sheet->setCellValue('B' . $row, $exp->count); $sheet->setCellValue('C' . $row, $exp->total);
                $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray($dataStyle);
                $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('$#,##0.00'); $row++;
            }
        }

        foreach (range('A', 'F') as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }

        $writer = new Xlsx($spreadsheet);
        $filename = 'pyg-' . $startDate . '-' . $endDate . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function creditsGroupedExcel(Request $request)
    {
        $dateRange = $request->get('date_range', 'all');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $branchId = $request->get('branch_id');
        $paymentStatus = $request->get('payment_status', '');
        $search = $request->get('search', '');
        $user = auth()->user();

        $branchName = 'Todas';
        if ($branchId) {
            $branchName = Branch::find($branchId)?->name ?? 'Todas';
        } elseif (!$user->isSuperAdmin()) {
            $branchId = $user->branch_id;
            $branchName = Branch::find($branchId)?->name ?? '';
        }

        // Build base query for credit sales grouped by customer
        $query = Sale::where('sales.payment_type', 'credit')
            ->where('sales.status', 'completed')
            ->whereNotNull('sales.customer_id')
            ->join('customers', 'sales.customer_id', '=', 'customers.id');

        if ($branchId) {
            $query->where('sales.branch_id', $branchId);
        } elseif (!$user->isSuperAdmin()) {
            $query->where('sales.branch_id', $user->branch_id);
        }

        if ($startDate) {
            $query->whereDate('sales.created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('sales.created_at', '<=', $endDate);
        }
        if ($paymentStatus) {
            $query->where('sales.payment_status', $paymentStatus);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('customers.first_name', 'like', "%{$search}%")
                    ->orWhere('customers.last_name', 'like', "%{$search}%")
                    ->orWhere('customers.business_name', 'like', "%{$search}%")
                    ->orWhere('customers.document_number', 'like', "%{$search}%");
            });
        }

        // Get customer IDs with their summaries
        $customerSummaries = (clone $query)
            ->select(
                'customers.id',
                'customers.document_number',
                'customers.phone',
                DB::raw("CASE WHEN customers.customer_type = 'juridico' THEN customers.business_name ELSE CONCAT(customers.first_name, ' ', customers.last_name) END as customer_name"),
                DB::raw('COUNT(sales.id) as total_invoices'),
                DB::raw('SUM(sales.credit_amount) as total_credit'),
                DB::raw('SUM(sales.paid_amount) as total_paid'),
                DB::raw('SUM(sales.credit_amount - sales.paid_amount) as total_remaining')
            )
            ->groupBy('customers.id', 'customers.customer_type', 'customers.business_name', 'customers.first_name', 'customers.last_name', 'customers.document_number', 'customers.phone')
            ->orderByDesc('total_remaining')
            ->get();

        // Get all invoices grouped by customer
        $allInvoices = Sale::where('sales.payment_type', 'credit')
            ->where('sales.status', 'completed')
            ->whereIn('sales.customer_id', $customerSummaries->pluck('id'));

        if ($branchId) {
            $allInvoices->where('sales.branch_id', $branchId);
        } elseif (!$user->isSuperAdmin()) {
            $allInvoices->where('sales.branch_id', $user->branch_id);
        }
        if ($startDate) {
            $allInvoices->whereDate('sales.created_at', '>=', $startDate);
        }
        if ($endDate) {
            $allInvoices->whereDate('sales.created_at', '<=', $endDate);
        }
        if ($paymentStatus) {
            $allInvoices->where('sales.payment_status', $paymentStatus);
        }

        $invoicesByCustomer = $allInvoices->orderBy('sales.created_at', 'desc')
            ->get()
            ->groupBy('customer_id');

        // Build Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Créditos por Cliente');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'A855F7']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '9333EA']]],
        ];
        $customerHeaderStyle = [
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '475569']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '334155']]],
        ];
        $subtotalStyle = [
            'font' => ['bold' => true, 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ];
        $dataStyle = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
        ];
        $titleStyle = [
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '1E293B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];

        $row = 1;
        $sheet->setCellValue('A' . $row, 'REPORTE DE CRÉDITOS POR CLIENTE');
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray($titleStyle);
        $sheet->getRowDimension($row)->setRowHeight(30);
        $row += 2;

        $periodLabel = $startDate && $endDate ? "$startDate - $endDate" : 'Todo';
        $sheet->setCellValue('A' . $row, 'Período:');
        $sheet->setCellValue('B' . $row, $periodLabel);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        $sheet->setCellValue('A' . $row, 'Sucursal:');
        $sheet->setCellValue('B' . $row, $branchName);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        $sheet->setCellValue('A' . $row, 'Generado:');
        $sheet->setCellValue('B' . $row, now()->format('d/m/Y H:i'));
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        if ($paymentStatus) {
            $statusLabels = ['pending' => 'Pendiente', 'partial' => 'Parcial', 'paid' => 'Pagado'];
            $sheet->setCellValue('A' . $row, 'Estado:');
            $sheet->setCellValue('B' . $row, $statusLabels[$paymentStatus] ?? $paymentStatus);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
        }

        $row++;

        // Grand totals
        $grandTotalCredit = $customerSummaries->sum('total_credit');
        $grandTotalPaid = $customerSummaries->sum('total_paid');
        $grandTotalRemaining = $customerSummaries->sum('total_remaining');
        $grandTotalInvoices = $customerSummaries->sum('total_invoices');

        $sheet->setCellValue('A' . $row, 'RESUMEN GENERAL');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
        $row++;
        $sheet->setCellValue('A' . $row, 'Total Clientes:');
        $sheet->setCellValue('B' . $row, $customerSummaries->count());
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        $sheet->setCellValue('A' . $row, 'Total Facturas:');
        $sheet->setCellValue('B' . $row, $grandTotalInvoices);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        $sheet->setCellValue('A' . $row, 'Total Créditos:');
        $sheet->setCellValue('B' . $row, $grandTotalCredit);
        $sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true);
        $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
        $row++;
        $sheet->setCellValue('A' . $row, 'Total Pagado:');
        $sheet->setCellValue('B' . $row, $grandTotalPaid);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
        $sheet->getStyle('B' . $row)->getFont()->setColor(new Color('16A34A'));
        $row++;
        $sheet->setCellValue('A' . $row, 'Total Pendiente:');
        $sheet->setCellValue('B' . $row, $grandTotalRemaining);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
        $sheet->getStyle('B' . $row)->getFont()->setBold(true)->setColor(new Color('DC2626'));
        $row += 2;

        // Per-customer detail
        foreach ($customerSummaries as $customer) {
            // Customer header row
            $sheet->setCellValue('A' . $row, $customer->customer_name);
            $sheet->setCellValue('C' . $row, 'Doc: ' . $customer->document_number);
            $sheet->setCellValue('E' . $row, 'Tel: ' . ($customer->phone ?? '-'));
            $sheet->mergeCells('A' . $row . ':B' . $row);
            $sheet->mergeCells('C' . $row . ':D' . $row);
            $sheet->mergeCells('E' . $row . ':G' . $row);
            $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($customerHeaderStyle);
            $row++;

            // Invoice headers
            $sheet->setCellValue('A' . $row, 'Factura');
            $sheet->setCellValue('B' . $row, 'Fecha');
            $sheet->setCellValue('C' . $row, 'Total Venta');
            $sheet->setCellValue('D' . $row, 'Total Crédito');
            $sheet->setCellValue('E' . $row, 'Pagado');
            $sheet->setCellValue('F' . $row, 'Pendiente');
            $sheet->setCellValue('G' . $row, 'Estado');
            $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($headerStyle);
            $row++;

            // Invoice rows
            $invoices = $invoicesByCustomer->get($customer->id, collect());
            foreach ($invoices as $invoice) {
                $remaining = (float) $invoice->credit_amount - (float) $invoice->paid_amount;
                $statusLabels = ['pending' => 'Pendiente', 'partial' => 'Parcial', 'paid' => 'Pagado'];

                $sheet->setCellValue('A' . $row, $invoice->invoice_number);
                $sheet->setCellValue('B' . $row, $invoice->created_at->format('d/m/Y'));
                $sheet->setCellValue('C' . $row, (float) $invoice->total);
                $sheet->setCellValue('D' . $row, (float) $invoice->credit_amount);
                $sheet->setCellValue('E' . $row, (float) $invoice->paid_amount);
                $sheet->setCellValue('F' . $row, $remaining);
                $sheet->setCellValue('G' . $row, $statusLabels[$invoice->payment_status] ?? $invoice->payment_status);
                $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($dataStyle);
                $sheet->getStyle('C' . $row . ':F' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');

                if ($remaining > 0) {
                    $sheet->getStyle('F' . $row)->getFont()->setColor(new Color('DC2626'));
                }
                $row++;
            }

            // Customer subtotal
            $sheet->setCellValue('A' . $row, 'Subtotal ' . $customer->customer_name);
            $sheet->mergeCells('A' . $row . ':B' . $row);
            $sheet->setCellValue('C' . $row, '');
            $sheet->setCellValue('D' . $row, (float) $customer->total_credit);
            $sheet->setCellValue('E' . $row, (float) $customer->total_paid);
            $sheet->setCellValue('F' . $row, (float) $customer->total_remaining);
            $sheet->setCellValue('G' . $row, $customer->total_invoices . ' factura(s)');
            $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($subtotalStyle);
            $sheet->getStyle('D' . $row . ':F' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet->getStyle('F' . $row)->getFont()->setBold(true)->setColor(new Color('DC2626'));
            $row += 2;
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'creditos-por-cliente-' . now()->format('Y-m-d') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function paymentMethodsExcel(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $branchId = $request->get('branch_id');
        $cashRegisterId = $request->get('cash_register_id');
        $paymentMethodId = $request->get('payment_method_id');
        $userId = $request->get('user_id');
        $user = auth()->user();

        $branchName = 'Todas';
        if ($branchId) {
            $branchName = Branch::find($branchId)?->name ?? 'Todas';
        } elseif (!$user->isSuperAdmin()) {
            $branchId = $user->branch_id;
            $branchName = Branch::find($branchId)?->name ?? '';
        }

        // Base query
        $baseQuery = SalePayment::join('sales', 'sale_payments.sale_id', '=', 'sales.id')
            ->join('payment_methods', 'sale_payments.payment_method_id', '=', 'payment_methods.id')
            ->where('sales.status', 'completed')
            ->whereDate('sales.created_at', '>=', $startDate)
            ->whereDate('sales.created_at', '<=', $endDate);

        if ($branchId) $baseQuery->where('sales.branch_id', $branchId);
        if ($cashRegisterId) {
            $baseQuery->whereHas('sale.cashReconciliation', fn($q) => $q->where('cash_register_id', $cashRegisterId));
        }
        if ($paymentMethodId) $baseQuery->where('sale_payments.payment_method_id', $paymentMethodId);
        if ($userId) $baseQuery->where('sales.user_id', $userId);

        // Summary by payment method
        $summary = (clone $baseQuery)->select(
            'payment_methods.name',
            DB::raw('SUM(sale_payments.amount) as total'),
            DB::raw('COUNT(DISTINCT sales.id) as transaction_count')
        )->groupBy('payment_methods.id', 'payment_methods.name')->orderByDesc('total')->get();

        $grandTotal = $summary->sum('total');

        // Detail
        $detail = (clone $baseQuery)->join('users', 'sales.user_id', '=', 'users.id')
            ->leftJoin('branches', 'sales.branch_id', '=', 'branches.id')
            ->select(
                'sales.invoice_number',
                'sales.created_at',
                'sales.total as sale_total',
                'sale_payments.amount',
                'payment_methods.name as payment_method_name',
                'users.name as user_name',
                'branches.name as branch_name'
            )->orderByDesc('sales.created_at')->get();

        // By user
        $byUser = (clone $baseQuery)->join('users', 'sales.user_id', '=', 'users.id')
            ->select(
                'users.name as user_name',
                'payment_methods.name as payment_method_name',
                DB::raw('SUM(sale_payments.amount) as total'),
                DB::raw('COUNT(DISTINCT sales.id) as transaction_count')
            )->groupBy('users.id', 'users.name', 'payment_methods.name')
            ->orderBy('users.name')->orderByDesc('total')->get();

        // Build Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Medios de Pago');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'A855F7']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '9333EA']]],
        ];
        $titleStyle = ['font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '1E293B']], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]];
        $subtitleStyle = ['font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'A855F7']]];
        $dataStyle = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]]];

        $row = 1;
        $sheet->setCellValue('A' . $row, 'REPORTE DE MEDIOS DE PAGO');
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray($titleStyle);
        $sheet->getRowDimension($row)->setRowHeight(30);
        $row += 2;

        $sheet->setCellValue('A' . $row, 'Período:'); $sheet->setCellValue('B' . $row, $startDate . ' - ' . $endDate); $sheet->getStyle('A' . $row)->getFont()->setBold(true); $row++;
        $sheet->setCellValue('A' . $row, 'Sucursal:'); $sheet->setCellValue('B' . $row, $branchName); $sheet->getStyle('A' . $row)->getFont()->setBold(true); $row++;
        $sheet->setCellValue('A' . $row, 'Generado:'); $sheet->setCellValue('B' . $row, now()->format('d/m/Y H:i')); $sheet->getStyle('A' . $row)->getFont()->setBold(true); $row += 2;

        // Summary section
        $sheet->setCellValue('A' . $row, 'RESUMEN POR MÉTODO DE PAGO'); $sheet->getStyle('A' . $row)->applyFromArray($subtitleStyle); $row++;
        $sheet->setCellValue('A' . $row, 'Método de Pago'); $sheet->setCellValue('B' . $row, 'Transacciones'); $sheet->setCellValue('C' . $row, 'Total'); $sheet->setCellValue('D' . $row, '% del Total');
        $sheet->getStyle('A' . $row . ':D' . $row)->applyFromArray($headerStyle); $row++;

        foreach ($summary as $item) {
            $pct = $grandTotal > 0 ? ($item->total / $grandTotal) * 100 : 0;
            $sheet->setCellValue('A' . $row, $item->name);
            $sheet->setCellValue('B' . $row, $item->transaction_count);
            $sheet->setCellValue('C' . $row, $item->total);
            $sheet->setCellValue('D' . $row, number_format($pct, 1) . '%');
            $sheet->getStyle('A' . $row . ':D' . $row)->applyFromArray($dataStyle);
            $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            $row++;
        }
        // Total row
        $sheet->setCellValue('A' . $row, 'TOTAL'); $sheet->setCellValue('B' . $row, $summary->sum('transaction_count')); $sheet->setCellValue('C' . $row, $grandTotal); $sheet->setCellValue('D' . $row, '100%');
        $sheet->getStyle('A' . $row . ':D' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':D' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F1F5F9');
        $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
        $row += 2;

        // By user section
        $sheet->setCellValue('A' . $row, 'RESUMEN POR VENDEDOR'); $sheet->getStyle('A' . $row)->applyFromArray($subtitleStyle); $row++;
        $sheet->setCellValue('A' . $row, 'Vendedor'); $sheet->setCellValue('B' . $row, 'Método de Pago'); $sheet->setCellValue('C' . $row, 'Transacciones'); $sheet->setCellValue('D' . $row, 'Total');
        $sheet->getStyle('A' . $row . ':D' . $row)->applyFromArray($headerStyle); $row++;

        foreach ($byUser as $item) {
            $sheet->setCellValue('A' . $row, $item->user_name);
            $sheet->setCellValue('B' . $row, $item->payment_method_name);
            $sheet->setCellValue('C' . $row, $item->transaction_count);
            $sheet->setCellValue('D' . $row, $item->total);
            $sheet->getStyle('A' . $row . ':D' . $row)->applyFromArray($dataStyle);
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            $row++;
        }
        $row += 1;

        // Detail section (new sheet)
        $detailSheet = $spreadsheet->createSheet();
        $detailSheet->setTitle('Detalle');
        $dRow = 1;
        $detailSheet->setCellValue('A' . $dRow, 'DETALLE DE PAGOS');
        $detailSheet->mergeCells('A' . $dRow . ':G' . $dRow);
        $detailSheet->getStyle('A' . $dRow)->applyFromArray($titleStyle);
        $detailSheet->getRowDimension($dRow)->setRowHeight(30);
        $dRow += 2;

        $detailSheet->setCellValue('A' . $dRow, 'Factura'); $detailSheet->setCellValue('B' . $dRow, 'Fecha');
        $detailSheet->setCellValue('C' . $dRow, 'Método'); $detailSheet->setCellValue('D' . $dRow, 'Vendedor');
        $detailSheet->setCellValue('E' . $dRow, 'Sucursal'); $detailSheet->setCellValue('F' . $dRow, 'Total Venta');
        $detailSheet->setCellValue('G' . $dRow, 'Monto Pagado');
        $detailSheet->getStyle('A' . $dRow . ':G' . $dRow)->applyFromArray($headerStyle);
        $dRow++;

        foreach ($detail as $item) {
            $detailSheet->setCellValue('A' . $dRow, $item->invoice_number);
            $detailSheet->setCellValue('B' . $dRow, Carbon::parse($item->created_at)->format('d/m/Y H:i'));
            $detailSheet->setCellValue('C' . $dRow, $item->payment_method_name);
            $detailSheet->setCellValue('D' . $dRow, $item->user_name);
            $detailSheet->setCellValue('E' . $dRow, $item->branch_name ?? '-');
            $detailSheet->setCellValue('F' . $dRow, $item->sale_total);
            $detailSheet->setCellValue('G' . $dRow, $item->amount);
            $detailSheet->getStyle('A' . $dRow . ':G' . $dRow)->applyFromArray($dataStyle);
            $detailSheet->getStyle('F' . $dRow . ':G' . $dRow)->getNumberFormat()->setFormatCode('$#,##0.00');
            $dRow++;
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $detailSheet->getColumnDimension($col)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);
        $writer = new Xlsx($spreadsheet);
        $filename = 'medios-pago-' . $startDate . '-' . $endDate . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function ecommerceOrdersReportPdf(Request $request)
    {
        $data = $this->getEcommerceReportData($request);

        $pdf = Pdf::loadView('reports.ecommerce-orders-report-pdf', $data);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('reporte-pedidos-tienda-' . now()->format('Y-m-d') . '.pdf');
    }

    public function ecommerceOrdersReportExcel(Request $request)
    {
        $data = $this->getEcommerceReportData($request);

        $products = $data['products'];
        $customers = $data['customers'];
        $customerTotals = $data['customerTotals'];
        $grandTotal = $data['grandTotal'];
        $customerKeys = array_keys($customers);
        $totalColumns = count($customerKeys) + 2; // product + customers + total

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Pedidos Tienda');

        $colLetter = fn(int $idx) => Coordinate::stringFromColumnIndex($idx);
        $cell = fn(int $c, int $r) => $colLetter($c) . $r;

        // Styles
        $titleStyle = [
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '1E293B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'A855F7']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '9333EA']]],
        ];
        $totalHeaderStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '7C3AED']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '6D28D9']]],
        ];
        $dataStyle = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];
        $totalRowStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '0F172A']]],
        ];
        $metaLabelStyle = [
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '64748B']],
        ];

        $row = 1;
        $lastColL = $colLetter($totalColumns);
        $totalColL = $colLetter($totalColumns);

        // Title
        $sheet->setCellValue('A' . $row, 'TABLA DE PEDIDOS - TIENDA');
        $sheet->mergeCells('A' . $row . ':' . $lastColL . $row);
        $sheet->getStyle('A' . $row)->applyFromArray($titleStyle);
        $sheet->getRowDimension($row)->setRowHeight(30);
        $row += 2;

        // Meta
        $sheet->setCellValue('A' . $row, 'Período:');
        $sheet->setCellValue('B' . $row, $data['startDate'] . ' - ' . $data['endDate']);
        $sheet->getStyle('A' . $row)->applyFromArray($metaLabelStyle);
        $row++;
        $sheet->setCellValue('A' . $row, 'Estado:');
        $sheet->setCellValue('B' . $row, $data['statusLabel']);
        $sheet->getStyle('A' . $row)->applyFromArray($metaLabelStyle);
        $row++;
        $sheet->setCellValue('A' . $row, 'Generado:');
        $sheet->setCellValue('B' . $row, $data['generatedAt']);
        $sheet->getStyle('A' . $row)->applyFromArray($metaLabelStyle);
        $row += 2;

        // Summary row
        $summaryBase = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];
        $sheet->setCellValue('A' . $row, 'Productos: ' . count($products));
        $sheet->getStyle('A' . $row)->applyFromArray(array_merge($summaryBase, [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FF7261']],
        ]));
        $sheet->setCellValue('B' . $row, 'Clientes: ' . count($customers));
        $sheet->getStyle('B' . $row)->applyFromArray(array_merge($summaryBase, [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '8B5CF6']],
        ]));
        $sheet->setCellValue('C' . $row, 'Total Uds: ' . $grandTotal);
        $sheet->getStyle('C' . $row)->applyFromArray(array_merge($summaryBase, [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '10B981']],
        ]));
        $sheet->getRowDimension($row)->setRowHeight(28);
        $row += 2;

        // Table header
        $headerRow = $row;
        $col = 1;
        $sheet->setCellValue($cell($col, $row), 'PRODUCTO');
        $col++;
        foreach ($customers as $name) {
            $sheet->setCellValue($cell($col, $row), $name);
            $col++;
        }
        $sheet->setCellValue($cell($col, $row), 'TOTAL');

        $headerRange = 'A' . $row . ':' . $colLetter(count($customerKeys) + 1) . $row;
        $sheet->getStyle($headerRange)->applyFromArray($headerStyle);
        $sheet->getStyle($totalColL . $row)->applyFromArray($totalHeaderStyle);
        $sheet->getRowDimension($row)->setRowHeight(25);
        $row++;

        // Data rows
        foreach ($products as $product) {
            $col = 1;
            $sheet->setCellValue($cell($col, $row), $product['name']);
            $col++;
            foreach ($customerKeys as $custKey) {
                $qty = $product['quantities'][$custKey] ?? 0;
                if ($qty > 0) {
                    $sheet->setCellValue($cell($col, $row), $qty);
                }
                $col++;
            }
            $sheet->setCellValue($cell($col, $row), $product['total']);

            $rowRange = 'A' . $row . ':' . $totalColL . $row;
            $sheet->getStyle($rowRange)->applyFromArray($dataStyle);

            // Alternate row color
            if ($row % 2 == 0) {
                $sheet->getStyle('A' . $row . ':' . $colLetter(count($customerKeys) + 1) . $row)
                    ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
            }

            // Total column highlight
            $sheet->getStyle($totalColL . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F3E8FF');
            $sheet->getStyle($totalColL . $row)->getFont()->setBold(true)->getColor()->setRGB('7C3AED');

            // Number format for quantity columns
            for ($c = 2; $c <= $totalColumns; $c++) {
                $sheet->getStyle($colLetter($c) . $row)->getNumberFormat()->setFormatCode('#,##0.###');
            }

            $row++;
        }

        // Totals row
        $col = 1;
        $sheet->setCellValue($cell($col, $row), 'TOTAL');
        $col++;
        foreach ($customerKeys as $custKey) {
            $val = $customerTotals[$custKey] ?? 0;
            if ($val > 0) {
                $sheet->setCellValue($cell($col, $row), $val);
            }
            $col++;
        }
        $sheet->setCellValue($cell($col, $row), $grandTotal);

        $sheet->getStyle('A' . $row . ':' . $totalColL . $row)->applyFromArray($totalRowStyle);
        $sheet->getRowDimension($row)->setRowHeight(25);

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(35);
        for ($c = 2; $c <= $totalColumns; $c++) {
            $sheet->getColumnDimension($colLetter($c))->setAutoSize(true);
        }

        // Freeze panes
        $sheet->freezePane('B' . ($headerRow + 1));

        $writer = new Xlsx($spreadsheet);
        $filename = 'reporte-pedidos-tienda-' . now()->format('Y-m-d') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function getEcommerceReportData(Request $request): array
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));
        $status = $request->get('status', 'all');

        $query = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
            ->where('sales.source', 'ecommerce')
            ->where('sale_items.is_unavailable', false);

        if ($status === 'pending') {
            $query->where('sales.status', 'pending_approval');
        } elseif ($status === 'approved') {
            $query->where('sales.status', 'completed');
        } elseif ($status === 'rejected') {
            $query->where('sales.status', 'rejected');
        } else {
            $query->whereIn('sales.status', ['pending_approval', 'completed', 'rejected']);
        }

        if ($dateFrom) {
            $query->whereDate('sales.created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('sales.created_at', '<=', $dateTo);
        }

        $items = $query->select(
            'sale_items.product_id',
            'sale_items.product_name',
            'sale_items.product_sku',
            'sales.customer_id',
            DB::raw("COALESCE(CONCAT(customers.first_name, ' ', customers.last_name), customers.business_name, 'Sin cliente') as customer_name"),
            DB::raw('SUM(sale_items.quantity) as total_quantity'),
        )
        ->groupBy(
            'sale_items.product_id',
            'sale_items.product_name',
            'sale_items.product_sku',
            'sales.customer_id',
            'customers.first_name',
            'customers.last_name',
            'customers.business_name',
        )
        ->get();

        $products = [];
        $customers = [];

        foreach ($items as $item) {
            $productKey = $item->product_id ?? $item->product_name;
            $customerKey = $item->customer_id ?? 'sin_cliente';
            $customerName = trim($item->customer_name) ?: 'Sin cliente';

            if (!isset($products[$productKey])) {
                $products[$productKey] = [
                    'name' => $item->product_name,
                    'sku' => $item->product_sku,
                    'quantities' => [],
                    'total' => 0,
                ];
            }

            if (!isset($customers[$customerKey])) {
                $customers[$customerKey] = $customerName;
            }

            $qty = (float) $item->total_quantity;
            $products[$productKey]['quantities'][$customerKey] = ($products[$productKey]['quantities'][$customerKey] ?? 0) + $qty;
            $products[$productKey]['total'] += $qty;
        }

        uasort($products, fn($a, $b) => strcmp($a['name'], $b['name']));
        asort($customers);

        $customerTotals = [];
        foreach ($customers as $key => $name) {
            $customerTotals[$key] = 0;
            foreach ($products as $product) {
                $customerTotals[$key] += $product['quantities'][$key] ?? 0;
            }
        }

        $statusLabel = match($status) {
            'pending' => 'Pendientes',
            'approved' => 'Aprobados',
            'rejected' => 'Rechazados',
            default => 'Todos',
        };

        return [
            'products' => $products,
            'customers' => $customers,
            'customerTotals' => $customerTotals,
            'grandTotal' => collect($products)->sum('total'),
            'startDate' => Carbon::parse($dateFrom)->format('d/m/Y'),
            'endDate' => Carbon::parse($dateTo)->format('d/m/Y'),
            'statusLabel' => $statusLabel,
            'generatedAt' => now()->format('d/m/Y H:i:s'),
        ];
    }

    public function salesBookExcel(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $branchId = $request->get('branch_id');
        $userId = $request->get('user_id');
        $paymentMethodId = $request->get('payment_method_id');
        $cashRegisterId = $request->get('cash_register_id');
        $statusFilter = $request->get('status', 'all');
        $search = $request->get('search', '');

        $user = auth()->user();

        // Build query
        $query = Sale::query()
            ->with(['customer', 'user', 'branch', 'payments.paymentMethod', 'cashReconciliation.cashRegister']);

        if ($startDate) {
            $query->whereDate('sales.created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('sales.created_at', '<=', $endDate);
        }

        if ($branchId) {
            $query->where('sales.branch_id', $branchId);
        } elseif (!$user->isSuperAdmin()) {
            $query->where('sales.branch_id', $user->branch_id);
        }

        if ($userId) {
            $query->where('sales.user_id', $userId);
        }

        if ($paymentMethodId) {
            $query->whereHas('payments', function ($q) use ($paymentMethodId) {
                $q->where('payment_method_id', $paymentMethodId);
            });
        }

        if ($cashRegisterId) {
            $query->whereHas('cashReconciliation', function ($q) use ($cashRegisterId) {
                $q->where('cash_register_id', $cashRegisterId);
            });
        }

        if ($statusFilter !== 'all') {
            $query->where('sales.status', $statusFilter);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('sales.invoice_number', 'like', "%{$search}%")
                  ->orWhere('sales.dian_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('business_name', 'like', "%{$search}%")
                        ->orWhere('document_number', 'like', "%{$search}%");
                  });
            });
        }

        $sales = $query->orderByDesc('sales.created_at')->get();

        // Summary (completed only)
        $completedSales = $sales->where('status', 'completed');
        $totalSales = $completedSales->sum('total');
        $totalSubtotal = $completedSales->sum('subtotal');
        $totalTax = $completedSales->sum('tax_total');
        $totalDiscount = $completedSales->sum('discount');
        $totalTransactions = $completedSales->count();
        $averageTicket = $totalTransactions > 0 ? $totalSales / $totalTransactions : 0;

        // Branch name
        $branchName = 'Todas';
        if ($branchId) {
            $branchName = Branch::find($branchId)?->name ?? 'Todas';
        } elseif (!$user->isSuperAdmin()) {
            $branchName = $user->branch?->name ?? '-';
        }

        // Build spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Libro de Ventas');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'A855F7']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '9333EA']]],
        ];

        $titleStyle = [
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '1E293B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];

        $subtitleStyle = [
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'A855F7']],
        ];

        $summaryStyle = [
            'font' => ['bold' => true, 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
        ];

        $dataStyle = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];

        $row = 1;

        // Title
        $sheet->setCellValue('A' . $row, 'LIBRO DE VENTAS');
        $sheet->mergeCells('A' . $row . ':J' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray($titleStyle);
        $sheet->getRowDimension($row)->setRowHeight(30);
        $row += 2;

        // Meta info
        $sheet->setCellValue('A' . $row, 'Período:');
        $sheet->setCellValue('B' . $row, Carbon::parse($startDate)->format('d/m/Y') . ' - ' . Carbon::parse($endDate)->format('d/m/Y'));
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue('A' . $row, 'Sucursal:');
        $sheet->setCellValue('B' . $row, $branchName);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue('A' . $row, 'Generado:');
        $sheet->setCellValue('B' . $row, now()->format('d/m/Y H:i:s'));
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row += 2;

        // Summary
        $sheet->setCellValue('A' . $row, 'RESUMEN');
        $sheet->getStyle('A' . $row)->applyFromArray($subtitleStyle);
        $row++;

        $summaryData = [
            ['Total Ventas (Completadas):', $totalSales, '$#,##0'],
            ['Subtotal:', $totalSubtotal, '$#,##0'],
            ['Impuestos:', $totalTax, '$#,##0'],
            ['Descuentos:', $totalDiscount, '$#,##0'],
            ['Transacciones:', $totalTransactions, '#,##0'],
            ['Ticket Promedio:', $averageTicket, '$#,##0'],
        ];

        foreach ($summaryData as $item) {
            $sheet->setCellValue('A' . $row, $item[0]);
            $sheet->setCellValue('B' . $row, $item[1]);
            $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray($summaryStyle);
            $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode($item[2]);
            $row++;
        }
        $row += 2;

        // Detail table
        $sheet->setCellValue('A' . $row, 'DETALLE DE VENTAS');
        $sheet->getStyle('A' . $row)->applyFromArray($subtitleStyle);
        $row++;

        $headers = ['Factura', 'No. DIAN', 'Fecha', 'Hora', 'Cliente', 'Documento', 'Vendedor', 'Forma de Pago', 'Subtotal', 'Impuestos', 'Descuento', 'Total', 'Estado', 'Tipo Pago', 'Caja'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $col++;
        }
        $sheet->getStyle('A' . $row . ':O' . $row)->applyFromArray($headerStyle);
        $sheet->getRowDimension($row)->setRowHeight(25);
        $row++;

        // Data rows
        foreach ($sales as $sale) {
            $customerName = $sale->customer?->full_name ?? 'Consumidor Final';
            $customerDoc = $sale->customer?->document_number ?? '';

            $paymentMethods = $sale->payments->map(fn($p) => $p->paymentMethod?->name ?? 'N/A')->implode(', ');
            if (empty($paymentMethods) && $sale->payment_type === 'credit') {
                $paymentMethods = 'Crédito';
            }

            $status = $sale->status === 'completed' ? 'Completada' : 'Anulada';
            $paymentType = match($sale->payment_type) {
                'cash' => 'Contado',
                'credit' => 'Crédito',
                default => $sale->payment_type ?? '-',
            };
            $cashRegister = $sale->cashReconciliation?->cashRegister?->name ?? '-';

            $sheet->setCellValue('A' . $row, $sale->invoice_number);
            $sheet->setCellValue('B' . $row, $sale->dian_number ?? '-');
            $sheet->setCellValue('C' . $row, $sale->created_at->format('d/m/Y'));
            $sheet->setCellValue('D' . $row, $sale->created_at->format('H:i'));
            $sheet->setCellValue('E' . $row, $customerName);
            $sheet->setCellValue('F' . $row, $customerDoc);
            $sheet->setCellValue('G' . $row, $sale->user?->name ?? '-');
            $sheet->setCellValue('H' . $row, $paymentMethods);
            $sheet->setCellValue('I' . $row, (float) $sale->subtotal);
            $sheet->setCellValue('J' . $row, (float) $sale->tax_total);
            $sheet->setCellValue('K' . $row, (float) $sale->discount);
            $sheet->setCellValue('L' . $row, (float) $sale->total);
            $sheet->setCellValue('M' . $row, $status);
            $sheet->setCellValue('N' . $row, $paymentType);
            $sheet->setCellValue('O' . $row, $cashRegister);

            $sheet->getStyle('A' . $row . ':O' . $row)->applyFromArray($dataStyle);
            $sheet->getStyle('I' . $row)->getNumberFormat()->setFormatCode('$#,##0');
            $sheet->getStyle('J' . $row)->getNumberFormat()->setFormatCode('$#,##0');
            $sheet->getStyle('K' . $row)->getNumberFormat()->setFormatCode('$#,##0');
            $sheet->getStyle('L' . $row)->getNumberFormat()->setFormatCode('$#,##0');

            if ($row % 2 == 0) {
                $sheet->getStyle('A' . $row . ':O' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
            }

            if ($sale->status !== 'completed') {
                $sheet->getStyle('M' . $row)->getFont()->getColor()->setRGB('DC2626');
            }

            $row++;
        }

        // Totals row
        $sheet->setCellValue('H' . $row, 'TOTALES:');
        $sheet->getStyle('H' . $row)->getFont()->setBold(true);
        $sheet->setCellValue('I' . $row, $completedSales->sum('subtotal'));
        $sheet->setCellValue('J' . $row, $completedSales->sum('tax_total'));
        $sheet->setCellValue('K' . $row, $completedSales->sum('discount'));
        $sheet->setCellValue('L' . $row, $completedSales->sum('total'));
        $sheet->getStyle('H' . $row . ':O' . $row)->applyFromArray($summaryStyle);
        $sheet->getStyle('I' . $row)->getNumberFormat()->setFormatCode('$#,##0');
        $sheet->getStyle('J' . $row)->getNumberFormat()->setFormatCode('$#,##0');
        $sheet->getStyle('K' . $row)->getNumberFormat()->setFormatCode('$#,##0');
        $sheet->getStyle('L' . $row)->getNumberFormat()->setFormatCode('$#,##0');

        // Auto-size columns
        foreach (range('A', 'O') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'libro-ventas-' . now()->format('Y-m-d') . '.xlsx';

        $tempFile = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

}
