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
            $sheet->setCellValue('E' . $row, $item->sale->customer?->name ?? 'Consumidor Final');
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

        // Expenses
        $expQuery = CashMovement::where('type', 'expense')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate);
        if ($branchId) {
            $expQuery->whereHas('reconciliation', fn($q) => $q->where('branch_id', $branchId));
        }
        $totalExpenses = (float) $expQuery->sum('amount');

        $grossProfit = $totalRevenue - $totalCost;
        $grossMargin = $totalRevenue > 0 ? ($grossProfit / $totalRevenue) * 100 : 0;
        $netProfit = $grossProfit - $totalExpenses;
        $netMargin = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0;

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

        // Expenses breakdown
        $expBreakdown = CashMovement::where('type', 'expense')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate);
        if ($branchId) {
            $expBreakdown->whereHas('reconciliation', fn($q) => $q->where('branch_id', $branchId));
        }
        $expenses = $expBreakdown->select('concept', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('concept')->orderByDesc('total')->get();

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
            ['(-) Descuentos', $totalDiscount, 'E2E8F0'],
            ['Impuestos Recaudados', $totalTax, 'E2E8F0'],
            ['(-) Costo de Ventas', $totalCost, 'ED7D31'],
            ['= UTILIDAD BRUTA', $grossProfit, $grossProfit >= 0 ? '70AD47' : 'FF0000'],
            ['(-) Gastos Operativos', $totalExpenses, 'FF6B6B'],
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
}
