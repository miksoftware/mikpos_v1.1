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
use App\Models\CreditPayment;
use App\Models\Payroll;
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
            ->join('users', 'sales.seller_id', '=', 'users.id')
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
            $query->where('sales.seller_id', $userId);
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

        $sheet->setCellValue('A' . $row, 'Vendedor:');
        $sheet->setCellValue('B' . $row, $data['sellerName']);
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
        $headers = ['Fecha', 'Factura', 'Producto', 'SKU', 'Cliente', 'Vendedor', 'Cantidad', 'P. Unitario', 'Total'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $col++;
        }
        $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray($headerStyle);
        $sheet->getRowDimension($row)->setRowHeight(25);
        $row++;

        // Detail data
        foreach ($data['items'] as $item) {
            $sheet->setCellValue('A' . $row, $item->sale->created_at->format('d/m/Y H:i'));
            $sheet->setCellValue('B' . $row, $item->sale->invoice_number);
            $sheet->setCellValue('C' . $row, $item->product_name);
            $sheet->setCellValue('D' . $row, $item->product_sku);
            $sheet->setCellValue('E' . $row, $item->sale->customer?->full_name ?? 'Consumidor Final');
            $sheet->setCellValue('F' . $row, $item->sale->user?->name ?? '-');
            $sheet->setCellValue('G' . $row, $item->quantity);
            $sheet->setCellValue('H' . $row, $item->unit_price);
            $sheet->setCellValue('I' . $row, $item->total);
            
            $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray($dataStyle);
            $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('H' . $row)->getNumberFormat()->setFormatCode('$#,##0');
            $sheet->getStyle('I' . $row)->getNumberFormat()->setFormatCode('$#,##0');
            
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
        $userId = $request->get('user_id');

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

        if ($userId) {
            $query->where('sales.user_id', $userId);
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

        // Get seller name
        $sellerName = 'Todos los vendedores';
        if ($userId) {
            $selectedUser = User::find($userId);
            $sellerName = $selectedUser ? $selectedUser->name : 'Vendedor no encontrado';
        }

        return [
            'startDate' => Carbon::parse($startDate)->format('d/m/Y'),
            'endDate' => Carbon::parse($endDate)->format('d/m/Y'),
            'branchName' => $branchName,
            'categoryName' => $categoryName,
            'sellerName' => $sellerName,
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
                    $totalCost += $item->unit_cost * (float) $item->quantity;
                }
            }
        }

        // Returns (refunds + credit notes) — captures partial returns too
        $refundsQuery = \App\Models\Refund::query()
            ->where('refunds.status', 'completed')
            ->whereDate('refunds.created_at', '>=', $startDate)
            ->whereDate('refunds.created_at', '<=', $endDate)
            ->whereHas('sale', fn($q) => $q->where('sales.status', 'completed'));
        if ($branchId) $refundsQuery->where('refunds.branch_id', $branchId);
        $totalRefundAmount = (float) (clone $refundsQuery)->sum('refunds.total');
        $totalRefundCount = (int) (clone $refundsQuery)->count();
        $refundIds = (clone $refundsQuery)->pluck('refunds.id');
        $refundCost = 0;
        if ($refundIds->isNotEmpty()) {
            $refundCost = (float) \App\Models\RefundItem::join('sale_items', 'refund_items.sale_item_id', '=', 'sale_items.id')
                ->whereIn('refund_items.refund_id', $refundIds)
                ->sum(DB::raw('refund_items.quantity * sale_items.unit_cost'));
        }

        $creditNotesQuery = \App\Models\CreditNote::query()
            ->whereIn('credit_notes.status', ['pending', 'validated'])
            ->whereDate('credit_notes.created_at', '>=', $startDate)
            ->whereDate('credit_notes.created_at', '<=', $endDate)
            ->whereHas('sale', fn($q) => $q->where('sales.status', 'completed'));
        if ($branchId) $creditNotesQuery->where('credit_notes.branch_id', $branchId);
        $totalCreditNoteAmount = (float) (clone $creditNotesQuery)->sum('credit_notes.total');
        $totalCreditNoteCount = (int) (clone $creditNotesQuery)->count();
        $creditNoteIds = (clone $creditNotesQuery)->pluck('credit_notes.id');
        $creditNoteCost = 0;
        if ($creditNoteIds->isNotEmpty()) {
            $creditNoteCost = (float) \App\Models\CreditNoteItem::join('sale_items', 'credit_note_items.sale_item_id', '=', 'sale_items.id')
                ->whereIn('credit_note_items.credit_note_id', $creditNoteIds)
                ->sum(DB::raw('credit_note_items.quantity * sale_items.unit_cost'));
        }

        $totalRefunds = round($totalRefundAmount + $totalCreditNoteAmount, 2);
        $totalRefundsCost = round($refundCost + $creditNoteCost, 2);

        $totalRevenue = max(0, $totalRevenue - $totalRefunds);
        $totalCost = max(0, $totalCost - $totalRefundsCost);

        // Purchases
        $purchasesQuery = Purchase::whereDate('purchases.created_at', '>=', $startDate)
            ->whereDate('purchases.created_at', '<=', $endDate);
        if ($branchId) $purchasesQuery->where('purchases.branch_id', $branchId);
        $totalPurchases = (float) $purchasesQuery->sum('total');

        // Expenses from cash movements
        // Exclude movements auto-created by refunds/credit notes to avoid double-counting
        // (those amounts are already subtracted from revenue via $totalRefunds).
        $expQuery = CashMovement::where('type', 'expense')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->where(function ($q) {
                $q->where('concept', 'not like', 'Devolución %')
                  ->where('concept', 'not like', 'Nota Crédito %');
            });
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
        $moduleExpQuery = Expense::whereDate('expenses.expense_date', '>=', $startDate)
            ->whereDate('expenses.expense_date', '<=', $endDate);
        if ($branchId) {
            $moduleExpQuery->where('expenses.branch_id', $branchId);
        }
        $totalModuleExpenses = (float) $moduleExpQuery->sum('amount');

        // Payroll expenses (paid payrolls in period)
        $payrollExpQuery = \App\Models\PayrollDetail::join('payrolls', 'payroll_details.payroll_id', '=', 'payrolls.id')
            ->where('payrolls.status', 'pagada')
            ->whereDate('payrolls.payment_date', '>=', $startDate)
            ->whereDate('payrolls.payment_date', '<=', $endDate);
        if ($branchId) {
            $payrollExpQuery->where('payrolls.branch_id', $branchId);
        }
        $totalPayrollExpenses = (float) $payrollExpQuery->sum('payroll_details.net_pay');

        // Operating expenses do NOT include payroll. Payroll is shown separately.
        $totalExpenses = $totalCashExpenses + $totalModuleExpenses;

        $grossProfit = $totalRevenue + $totalCashIncome - $totalCost;
        $totalIncome = $totalRevenue + $totalCashIncome;
        $grossMargin = $totalIncome > 0 ? ($grossProfit / $totalIncome) * 100 : 0;
        $netProfit = $grossProfit - $totalExpenses - $totalPayrollExpenses;
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
            DB::raw('SUM(sale_items.quantity * sale_items.unit_cost) as cost')
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
            DB::raw('SUM(sale_items.quantity * sale_items.unit_cost) as cost')
        )->groupBy('products.id', 'products.name', 'products.sku')->orderByDesc(DB::raw('SUM(sale_items.subtotal) - SUM(sale_items.quantity * sale_items.unit_cost)'))->get();

        // Payment methods
        $payments = SalePayment::join('sales', 'sale_payments.sale_id', '=', 'sales.id')
            ->join('payment_methods', 'sale_payments.payment_method_id', '=', 'payment_methods.id')
            ->where('sales.status', 'completed')
            ->whereDate('sales.created_at', '>=', $startDate)
            ->whereDate('sales.created_at', '<=', $endDate);
        if ($branchId) $payments->where('sales.branch_id', $branchId);
        $paymentMethods = $payments->select('payment_methods.name', DB::raw('SUM(sale_payments.amount) as total'), DB::raw('COUNT(DISTINCT sales.id) as count'))
            ->groupBy('payment_methods.id', 'payment_methods.name')->orderByDesc('total')->get();

        // Expenses breakdown (cash + module) — exclude refund/credit-note auto-movements
        $expBreakdown = CashMovement::where('type', 'expense')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->where(function ($q) {
                $q->where('concept', 'not like', 'Devolución %')
                  ->where('concept', 'not like', 'Nota Crédito %');
            });
        if ($branchId) {
            $expBreakdown->whereHas('reconciliation', fn($q) => $q->where('branch_id', $branchId));
        }
        $cashExpenses = $expBreakdown->select('concept', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('concept')->orderByDesc('total')->get()
            ->map(fn($e) => (object) ['concept' => $e->concept . ' (Caja)', 'total' => $e->total, 'count' => $e->count]);

        $modExpBreakdown = Expense::whereDate('expenses.expense_date', '>=', $startDate)
            ->whereDate('expenses.expense_date', '<=', $endDate);
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
            ['(-) Devoluciones y Notas Crédito', $totalRefunds, 'F59E0B'],
            ['(-) Descuentos', $totalDiscount, 'E2E8F0'],
            ['Impuestos Recaudados', $totalTax, 'E2E8F0'],
            ['(-) Costo de Ventas', $totalCost, 'ED7D31'],
            ['= UTILIDAD BRUTA', $grossProfit, $grossProfit >= 0 ? '70AD47' : 'FF0000'],
            ['(-) Gastos Operativos', $totalExpenses, 'FF6B6B'],
            ['    Egresos de Caja', $totalCashExpenses, 'E2E8F0'],
            ['    Gastos Registrados', $totalModuleExpenses, 'E2E8F0'],
            ['(-) Nómina', $totalPayrollExpenses, 'A855F7'],
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

    /**
     * Helper to compute full P&L metrics for a given date range and branch.
     */
    private function calculateProfitLossMetrics(string $startDate, string $endDate, ?int $branchId, $user = null): array
    {
        if (!$user) {
            $user = auth()->user();
        }

        // Sales query
        $salesQuery = Sale::where('sales.status', 'completed')
            ->whereDate('sales.created_at', '>=', $startDate)
            ->whereDate('sales.created_at', '<=', $endDate);

        if ($branchId) {
            $salesQuery->where('sales.branch_id', $branchId);
        } elseif ($user && !$user->isSuperAdmin()) {
            $salesQuery->where('sales.branch_id', $user->branch_id);
        }

        if ($user && $user->isSupervisor()) {
            $supervisorRegisterIds = $user->getSupervisorCashRegisterIds();
            if (empty($supervisorRegisterIds)) {
                $salesQuery->whereRaw('0 = 1');
            } else {
                $reconciliationIds = \App\Models\CashReconciliation::whereIn('cash_register_id', $supervisorRegisterIds)->pluck('id');
                $salesQuery->whereIn('sales.cash_reconciliation_id', $reconciliationIds);
            }
        }

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
        $totalTransactions = (int) ($salesSummary->transactions ?? 0);

        // COGS
        $totalCost = 0;
        $salesWithItems = (clone $salesQuery)->with('items.product')->get();
        $dailySales = [];
        $dailyCost = [];

        foreach ($salesWithItems as $sale) {
            $dayNum = (int) $sale->created_at->format('j');
            $saleCost = 0;
            foreach ($sale->items as $item) {
                if ($item->product) {
                    $itemCost = $item->unit_cost * (float) $item->quantity;
                    $totalCost += $itemCost;
                    $saleCost += $itemCost;
                }
            }
            $dailySales[$dayNum] = ($dailySales[$dayNum] ?? 0) + (float) $sale->total;
            $dailyCost[$dayNum] = ($dailyCost[$dayNum] ?? 0) + $saleCost;
        }

        // Returns
        $refundsQuery = \App\Models\Refund::query()
            ->where('refunds.status', 'completed')
            ->whereDate('refunds.created_at', '>=', $startDate)
            ->whereDate('refunds.created_at', '<=', $endDate)
            ->whereHas('sale', fn($q) => $q->where('sales.status', 'completed'));

        if ($branchId) {
            $refundsQuery->where('refunds.branch_id', $branchId);
        } elseif ($user && !$user->isSuperAdmin()) {
            $refundsQuery->where('refunds.branch_id', $user->branch_id);
        }
        if ($user && $user->isSupervisor()) {
            $supervisorRegisterIds = $user->getSupervisorCashRegisterIds();
            if (empty($supervisorRegisterIds)) {
                $refundsQuery->whereRaw('0 = 1');
            } else {
                $reconciliationIds = \App\Models\CashReconciliation::whereIn('cash_register_id', $supervisorRegisterIds)->pluck('id');
                $refundsQuery->whereHas('sale', fn($q) => $q->whereIn('cash_reconciliation_id', $reconciliationIds));
            }
        }

        $refundsAggregate = (clone $refundsQuery)->selectRaw('
            COUNT(*) as count,
            COALESCE(SUM(refunds.total), 0) as total
        ')->first();
        $totalRefundAmount = (float) ($refundsAggregate->total ?? 0);
        $totalRefundCount = (int) ($refundsAggregate->count ?? 0);

        $refundCost = 0;
        $refundIds = (clone $refundsQuery)->pluck('refunds.id');
        if ($refundIds->isNotEmpty()) {
            $refundCost = (float) \App\Models\RefundItem::join('sale_items', 'refund_items.sale_item_id', '=', 'sale_items.id')
                ->whereIn('refund_items.refund_id', $refundIds)
                ->sum(DB::raw('refund_items.quantity * sale_items.unit_cost'));
        }

        // Credit notes
        $creditNotesQuery = \App\Models\CreditNote::query()
            ->whereIn('credit_notes.status', ['pending', 'validated'])
            ->whereDate('credit_notes.created_at', '>=', $startDate)
            ->whereDate('credit_notes.created_at', '<=', $endDate)
            ->whereHas('sale', fn($q) => $q->where('sales.status', 'completed'));

        if ($branchId) {
            $creditNotesQuery->where('credit_notes.branch_id', $branchId);
        } elseif ($user && !$user->isSuperAdmin()) {
            $creditNotesQuery->where('credit_notes.branch_id', $user->branch_id);
        }
        if ($user && $user->isSupervisor()) {
            $supervisorRegisterIds = $user->getSupervisorCashRegisterIds();
            if (empty($supervisorRegisterIds)) {
                $creditNotesQuery->whereRaw('0 = 1');
            } else {
                $reconciliationIds = \App\Models\CashReconciliation::whereIn('cash_register_id', $supervisorRegisterIds)->pluck('id');
                $creditNotesQuery->whereHas('sale', fn($q) => $q->whereIn('cash_reconciliation_id', $reconciliationIds));
            }
        }

        $creditNotesAggregate = (clone $creditNotesQuery)->selectRaw('
            COUNT(*) as count,
            COALESCE(SUM(credit_notes.total), 0) as total
        ')->first();
        $totalCreditNoteAmount = (float) ($creditNotesAggregate->total ?? 0);
        $totalCreditNoteCount = (int) ($creditNotesAggregate->count ?? 0);

        $creditNoteCost = 0;
        $creditNoteIds = (clone $creditNotesQuery)->pluck('credit_notes.id');
        if ($creditNoteIds->isNotEmpty()) {
            $creditNoteCost = (float) \App\Models\CreditNoteItem::join('sale_items', 'credit_note_items.sale_item_id', '=', 'sale_items.id')
                ->whereIn('credit_note_items.credit_note_id', $creditNoteIds)
                ->sum(DB::raw('credit_note_items.quantity * sale_items.unit_cost'));
        }

        $totalRefunds = round($totalRefundAmount + $totalCreditNoteAmount, 2);
        $totalRefundsCost = round($refundCost + $creditNoteCost, 2);
        $totalRefundsCount = $totalRefundCount + $totalCreditNoteCount;
        $rawRevenue = $totalRevenue;

        $realRevenue = max(0, $totalRevenue - $totalRefunds);
        $realCost = max(0, $totalCost - $totalRefundsCost);

        // Cash income
        $cashIncomeQuery = CashMovement::where('cash_movements.type', 'income')
            ->whereDate('cash_movements.created_at', '>=', $startDate)
            ->whereDate('cash_movements.created_at', '<=', $endDate);
        if ($branchId) {
            $cashIncomeQuery->whereHas('reconciliation', fn($q) => $q->where('branch_id', $branchId));
        } elseif ($user && !$user->isSuperAdmin()) {
            $cashIncomeQuery->whereHas('reconciliation', fn($q) => $q->where('branch_id', $user->branch_id));
        }
        $totalCashIncome = (float) $cashIncomeQuery->sum('amount');

        // Cash expenses
        $cashExpQuery = CashMovement::where('cash_movements.type', 'expense')
            ->whereDate('cash_movements.created_at', '>=', $startDate)
            ->whereDate('cash_movements.created_at', '<=', $endDate)
            ->where(function ($q) {
                $q->where('cash_movements.concept', 'not like', 'Devolución %')
                  ->where('cash_movements.concept', 'not like', 'Nota Crédito %');
            });
        if ($branchId) {
            $cashExpQuery->whereHas('reconciliation', fn($q) => $q->where('branch_id', $branchId));
        } elseif ($user && !$user->isSuperAdmin()) {
            $cashExpQuery->whereHas('reconciliation', fn($q) => $q->where('branch_id', $user->branch_id));
        }
        $totalCashExpenses = (float) $cashExpQuery->sum('amount');

        // Module expenses
        $moduleExpQuery = Expense::whereDate('expenses.expense_date', '>=', $startDate)
            ->whereDate('expenses.expense_date', '<=', $endDate);
        if ($branchId) {
            $moduleExpQuery->where('expenses.branch_id', $branchId);
        } elseif ($user && !$user->isSuperAdmin()) {
            $moduleExpQuery->where('expenses.branch_id', $user->branch_id);
        }
        $totalModuleExpenses = (float) $moduleExpQuery->sum('amount');

        // Payroll
        $payrollExpQuery = \App\Models\PayrollDetail::join('payrolls', 'payroll_details.payroll_id', '=', 'payrolls.id')
            ->where('payrolls.status', 'pagada')
            ->whereDate('payrolls.payment_date', '>=', $startDate)
            ->whereDate('payrolls.payment_date', '<=', $endDate);
        if ($branchId) {
            $payrollExpQuery->where('payrolls.branch_id', $branchId);
        } elseif ($user && !$user->isSuperAdmin()) {
            $payrollExpQuery->where('payrolls.branch_id', $user->branch_id);
        }
        $totalPayrollExpenses = (float) $payrollExpQuery->sum('payroll_details.net_pay');

        $totalExpenses = $totalCashExpenses + $totalModuleExpenses;

        $grossProfit = $realRevenue + $totalCashIncome - $realCost;
        $totalIncome = $realRevenue + $totalCashIncome;
        $grossMargin = $totalIncome > 0 ? ($grossProfit / $totalIncome) * 100 : 0;
        $netProfit = $grossProfit - $totalExpenses - $totalPayrollExpenses;
        $netMargin = $totalIncome > 0 ? ($netProfit / $totalIncome) * 100 : 0;

        // Purchases
        $purchasesQuery = Purchase::whereDate('purchases.created_at', '>=', $startDate)
            ->whereDate('purchases.created_at', '<=', $endDate);
        if ($branchId) {
            $purchasesQuery->where('purchases.branch_id', $branchId);
        } elseif ($user && !$user->isSuperAdmin()) {
            $purchasesQuery->where('purchases.branch_id', $user->branch_id);
        }
        $totalPurchases = (float) $purchasesQuery->sum('total');

        // Categories
        $catQuery = SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->leftJoin('products', 'sale_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('sales.status', 'completed')
            ->whereDate('sales.created_at', '>=', $startDate)
            ->whereDate('sales.created_at', '<=', $endDate);
        if ($branchId) $catQuery->where('sales.branch_id', $branchId);
        elseif ($user && !$user->isSuperAdmin()) $catQuery->where('sales.branch_id', $user->branch_id);

        $categories = $catQuery->select(
            DB::raw("COALESCE(categories.name, 'Sin categoría') as name"),
            DB::raw('SUM(sale_items.subtotal) as revenue'),
            DB::raw('SUM(sale_items.quantity * sale_items.unit_cost) as cost')
        )->groupBy('categories.name')->orderByDesc('revenue')->get()->keyBy('name')->toArray();

        // Payment methods
        $pmQuery = SalePayment::join('sales', 'sale_payments.sale_id', '=', 'sales.id')
            ->join('payment_methods', 'sale_payments.payment_method_id', '=', 'payment_methods.id')
            ->where('sales.status', 'completed')
            ->whereDate('sales.created_at', '>=', $startDate)
            ->whereDate('sales.created_at', '<=', $endDate);
        if ($branchId) $pmQuery->where('sales.branch_id', $branchId);
        elseif ($user && !$user->isSuperAdmin()) $pmQuery->where('sales.branch_id', $user->branch_id);

        $paymentMethods = $pmQuery->select('payment_methods.name', DB::raw('SUM(sale_payments.amount) as total'), DB::raw('COUNT(DISTINCT sales.id) as count'))
            ->groupBy('payment_methods.id', 'payment_methods.name')->orderByDesc('total')->get()->keyBy('name')->toArray();

        return [
            'rawRevenue' => $rawRevenue,
            'totalRevenue' => $realRevenue,
            'totalCost' => $realCost,
            'totalTax' => $totalTax,
            'totalDiscount' => $totalDiscount,
            'totalTransactions' => $totalTransactions,
            'totalRefunds' => $totalRefunds,
            'totalRefundsCost' => $totalRefundsCost,
            'totalRefundsCount' => $totalRefundsCount,
            'totalCashIncome' => $totalCashIncome,
            'totalCashExpenses' => $totalCashExpenses,
            'totalModuleExpenses' => $totalModuleExpenses,
            'totalPayrollExpenses' => $totalPayrollExpenses,
            'totalExpenses' => $totalExpenses,
            'grossProfit' => $grossProfit,
            'grossMargin' => $grossMargin,
            'netProfit' => $netProfit,
            'netMargin' => $netMargin,
            'totalPurchases' => $totalPurchases,
            'dailySales' => $dailySales,
            'dailyCost' => $dailyCost,
            'categories' => $categories,
            'paymentMethods' => $paymentMethods,
        ];
    }

    /**
     * Export Profit & Loss Versus (Period A vs Period B) comparison as Excel.
     */
    public function profitLossVersusExcel(Request $request)
    {
        $startDateA = $request->get('start_date_a', now()->startOfMonth()->format('Y-m-d'));
        $endDateA = $request->get('end_date_a', now()->format('Y-m-d'));
        $startDateB = $request->get('start_date_b', now()->subMonth()->startOfMonth()->format('Y-m-d'));
        $endDateB = $request->get('end_date_b', now()->subMonth()->endOfMonth()->format('Y-m-d'));
        $labelA = $request->get('label_a', Carbon::parse($startDateA)->translatedFormat('F Y'));
        $labelB = $request->get('label_b', Carbon::parse($startDateB)->translatedFormat('F Y'));
        $branchId = $request->get('branch_id');

        $user = auth()->user();
        $branchName = 'Todas las Sucursales';
        if ($branchId) {
            $branchName = Branch::find($branchId)?->name ?? 'Todas';
        } elseif (!$user->isSuperAdmin()) {
            $branchId = $user->branch_id;
            $branchName = Branch::find($branchId)?->name ?? '';
        }

        $dataA = $this->calculateProfitLossMetrics($startDateA, $endDateA, $branchId, $user);
        $dataB = $this->calculateProfitLossMetrics($startDateB, $endDateB, $branchId, $user);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('PyG Versus');

        // Styles
        $mainHeaderStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 14],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A1225']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ];
        $sectionHeaderStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'A855F7']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '9333EA']]],
        ];
        $headerAStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FF7261']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ];
        $headerBStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '7C3AED']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ];
        $subHeaderStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => '1E293B'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ];
        $dataStyle = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];
        $totalHighlight = [
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ];

        $row = 1;

        // Title
        $sheet->setCellValue('A' . $row, 'MIKPOS - REPORTE COMPARATIVO DE PÉRDIDAS Y GANANCIAS (VERSUS)');
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($mainHeaderStyle);
        $sheet->getRowDimension($row)->setRowHeight(36);
        $row += 2;

        // Metadata
        $sheet->setCellValue('A' . $row, 'Período A (Base):');
        $sheet->setCellValue('B' . $row, ucfirst($labelA) . " ({$startDateA} al {$endDateA})");
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $sheet->setCellValue('D' . $row, 'Período B (Comparado):');
        $sheet->setCellValue('E' . $row, ucfirst($labelB) . " ({$startDateB} al {$endDateB})");
        $sheet->getStyle('D' . $row)->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue('A' . $row, 'Sucursal:');
        $sheet->setCellValue('B' . $row, $branchName);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $sheet->setCellValue('D' . $row, 'Fecha Generación:');
        $sheet->setCellValue('E' . $row, now()->format('d/m/Y H:i:s'));
        $sheet->getStyle('D' . $row)->getFont()->setBold(true);
        $row += 2;

        // SECTION 1: EXECUTIVE BATTLE CARDS / RESUMEN EJECUTIVO
        $sheet->setCellValue('A' . $row, '1. RESUMEN EJECUTIVO COMPARATIVO');
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($sectionHeaderStyle);
        $sheet->getRowDimension($row)->setRowHeight(24);
        $row++;

        $sheet->setCellValue('A' . $row, 'MÉTRICA / INDICADOR CLAVE');
        $sheet->setCellValue('B' . $row, 'PERÍODO A (' . strtoupper($labelA) . ')');
        $sheet->setCellValue('C' . $row, 'PERÍODO B (' . strtoupper($labelB) . ')');
        $sheet->setCellValue('D' . $row, 'DIFERENCIA (B - A)');
        $sheet->setCellValue('E' . $row, '% CRECIMIENTO');
        $sheet->setCellValue('F' . $row, 'PERÍODO GANADOR');
        $sheet->setCellValue('G' . $row, 'TENDENCIA');
        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($subHeaderStyle);
        $sheet->getStyle('B' . $row)->applyFromArray($headerAStyle);
        $sheet->getStyle('C' . $row)->applyFromArray($headerBStyle);
        $sheet->getRowDimension($row)->setRowHeight(22);
        $row++;

        $kpis = [
            ['Ingresos Reales (Ventas Netas)', $dataA['totalRevenue'] + $dataA['totalCashIncome'], $dataB['totalRevenue'] + $dataB['totalCashIncome'], true, true],
            ['Devoluciones y Notas Crédito', $dataA['totalRefunds'], $dataB['totalRefunds'], true, false],
            ['Costo de Ventas (COGS)', $dataA['totalCost'], $dataB['totalCost'], true, false],
            ['Utilidad Bruta', $dataA['grossProfit'], $dataB['grossProfit'], true, true],
            ['Margen Bruto (%)', $dataA['grossMargin'], $dataB['grossMargin'], false, true],
            ['Gastos Operativos (Caja + Registrados)', $dataA['totalExpenses'], $dataB['totalExpenses'], true, false],
            ['Gastos de Nómina', $dataA['totalPayrollExpenses'], $dataB['totalPayrollExpenses'], true, false],
            ['UTILIDAD NETA', $dataA['netProfit'], $dataB['netProfit'], true, true],
            ['Margen Neto (%)', $dataA['netMargin'], $dataB['netMargin'], false, true],
            ['Total Transacciones', $dataA['totalTransactions'], $dataB['totalTransactions'], 'int', true],
            ['Ticket Promedio', $dataA['totalTransactions'] > 0 ? $dataA['totalRevenue'] / $dataA['totalTransactions'] : 0, $dataB['totalTransactions'] > 0 ? $dataB['totalRevenue'] / $dataB['totalTransactions'] : 0, true, true],
            ['Compras del Período', $dataA['totalPurchases'], $dataB['totalPurchases'], true, false],
        ];

        foreach ($kpis as $kpi) {
            $name = $kpi[0];
            $valA = $kpi[1];
            $valB = $kpi[2];
            $isMoney = $kpi[3] === true;
            $isInt = $kpi[3] === 'int';
            $isPercent = $kpi[3] === false;
            $higherIsBetter = $kpi[4];

            $diff = $valB - $valA;
            $growth = $valA != 0 ? (($valB - $valA) / abs($valA)) * 100 : ($valB > 0 ? 100 : 0);

            $winner = 'Empate';
            if ($higherIsBetter) {
                if ($valB > $valA) $winner = ucfirst($labelB) . ' 🏆';
                elseif ($valA > $valB) $winner = ucfirst($labelA) . ' 🏆';
            } else {
                if ($valB < $valA) $winner = ucfirst($labelB) . ' 🏆';
                elseif ($valA < $valB) $winner = ucfirst($labelA) . ' 🏆';
            }

            $trend = $growth > 0 ? '▲ Creció' : ($growth < 0 ? '▼ Cayó' : '— Igual');

            $sheet->setCellValue('A' . $row, $name);
            $sheet->setCellValue('B' . $row, $valA);
            $sheet->setCellValue('C' . $row, $valB);
            $sheet->setCellValue('D' . $row, "=C{$row}-B{$row}");
            $sheet->setCellValue('E' . $row, "=IF(B{$row}<>0, (C{$row}-B{$row})/ABS(B{$row}), 0)");
            $sheet->setCellValue('F' . $row, $winner);
            $sheet->setCellValue('G' . $row, $trend);

            $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($dataStyle);

            if ($isMoney) {
                $sheet->getStyle('B' . $row . ':D' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            } elseif ($isPercent) {
                $sheet->getStyle('B' . $row . ':D' . $row)->getNumberFormat()->setFormatCode('0.0"%"');
            } elseif ($isInt) {
                $sheet->getStyle('B' . $row . ':D' . $row)->getNumberFormat()->setFormatCode('#,##0');
            }
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('+0.0%;-0.0%;0.0%');

            if ($name === 'UTILIDAD NETA' || $name === 'Utilidad Bruta' || $name === 'Ingresos Reales (Ventas Netas)') {
                $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($totalHighlight);
                if ($growth >= 0) {
                    $sheet->getStyle('E' . $row)->getFont()->setColor(new Color('059669'))->setBold(true);
                } else {
                    $sheet->getStyle('E' . $row)->getFont()->setColor(new Color('DC2626'))->setBold(true);
                }
            }

            $row++;
        }
        $row += 2;

        // SECTION 2: COMPARATIVE P&L STATEMENT (ESTADO DE RESULTADOS LADO A LADO)
        $sheet->setCellValue('A' . $row, '2. ESTADO FINANCIERO COMPARATIVO DE PÉRDIDAS Y GANANCIAS');
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($sectionHeaderStyle);
        $sheet->getRowDimension($row)->setRowHeight(24);
        $row++;

        $sheet->setCellValue('A' . $row, 'RUBRO / CONCEPTO');
        $sheet->setCellValue('B' . $row, 'PERÍODO A ($)');
        $sheet->setCellValue('C' . $row, '% ING. A');
        $sheet->setCellValue('D' . $row, 'PERÍODO B ($)');
        $sheet->setCellValue('E' . $row, '% ING. B');
        $sheet->setCellValue('F' . $row, 'VARIACIÓN ($)');
        $sheet->setCellValue('G' . $row, 'VARIACIÓN (%)');
        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($subHeaderStyle);
        $sheet->getRowDimension($row)->setRowHeight(22);
        $row++;

        $incA = ($dataA['totalRevenue'] + $dataA['totalCashIncome']) ?: 1;
        $incB = ($dataB['totalRevenue'] + $dataB['totalCashIncome']) ?: 1;

        $stmtRows = [
            ['Ingresos por Ventas (Brutos)', $dataA['rawRevenue'], $dataB['rawRevenue'], false],
            ['(+) Otros Ingresos (Mov. Caja)', $dataA['totalCashIncome'], $dataB['totalCashIncome'], false],
            ['(-) Devoluciones y Notas Crédito', $dataA['totalRefunds'], $dataB['totalRefunds'], false],
            ['(=) INGRESOS REALES', $dataA['totalRevenue'] + $dataA['totalCashIncome'], $dataB['totalRevenue'] + $dataB['totalCashIncome'], true],
            ['(-) Descuentos Otorgados', $dataA['totalDiscount'], $dataB['totalDiscount'], false],
            ['    Impuestos Recaudados (IVA/ICO)', $dataA['totalTax'], $dataB['totalTax'], false],
            ['(-) Costo de Ventas (COGS)', $dataA['totalCost'], $dataB['totalCost'], false],
            ['(=) UTILIDAD BRUTA', $dataA['grossProfit'], $dataB['grossProfit'], true],
            ['(-) Gastos Operativos (Caja + Registrados)', $dataA['totalExpenses'], $dataB['totalExpenses'], false],
            ['    Egresos de Caja', $dataA['totalCashExpenses'], $dataB['totalCashExpenses'], false],
            ['    Gastos Registrados en Módulo', $dataA['totalModuleExpenses'], $dataB['totalModuleExpenses'], false],
            ['(-) Gastos de Nómina Pagada', $dataA['totalPayrollExpenses'], $dataB['totalPayrollExpenses'], false],
            ['(=) UTILIDAD NETA DEL EJERCICIO', $dataA['netProfit'], $dataB['netProfit'], true],
        ];

        foreach ($stmtRows as $sRow) {
            $cName = $sRow[0];
            $cA = $sRow[1];
            $cB = $sRow[2];
            $isMain = $sRow[3];

            $sheet->setCellValue('A' . $row, $cName);
            $sheet->setCellValue('B' . $row, $cA);
            $sheet->setCellValue('C' . $row, "=IF({$incA}>0, B{$row}/{$incA}, 0)");
            $sheet->setCellValue('D' . $row, $cB);
            $sheet->setCellValue('E' . $row, "=IF({$incB}>0, D{$row}/{$incB}, 0)");
            $sheet->setCellValue('F' . $row, "=D{$row}-B{$row}");
            $sheet->setCellValue('G' . $row, "=IF(B{$row}<>0, (D{$row}-B{$row})/ABS(B{$row}), 0)");

            $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($dataStyle);
            $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('0.0%');
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('0.0%');
            $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('+0.0%;-0.0%;0.0%');

            if ($isMain) {
                $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($totalHighlight);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $sheet->getStyle('B' . $row)->getFont()->setBold(true);
                $sheet->getStyle('D' . $row)->getFont()->setBold(true);
                $sheet->getStyle('F' . $row)->getFont()->setBold(true);
                $sheet->getStyle('G' . $row)->getFont()->setBold(true);
            }
            $row++;
        }
        $row += 2;

        // SECTION 3: CATEGORIES COMPARISON
        $sheet->setCellValue('A' . $row, '3. COMPARATIVA DE RENTABILIDAD POR CATEGORÍA');
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($sectionHeaderStyle);
        $sheet->getRowDimension($row)->setRowHeight(24);
        $row++;

        $sheet->setCellValue('A' . $row, 'CATEGORÍA');
        $sheet->setCellValue('B' . $row, 'VENTAS ' . strtoupper($labelA));
        $sheet->setCellValue('C' . $row, 'UTILIDAD ' . strtoupper($labelA));
        $sheet->setCellValue('D' . $row, 'VENTAS ' . strtoupper($labelB));
        $sheet->setCellValue('E' . $row, 'UTILIDAD ' . strtoupper($labelB));
        $sheet->setCellValue('F' . $row, 'DIF. VENTAS ($)');
        $sheet->setCellValue('G' . $row, 'DIF. UTILIDAD ($)');
        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($subHeaderStyle);
        $sheet->getRowDimension($row)->setRowHeight(22);
        $row++;

        $allCatNames = array_unique(array_merge(array_keys($dataA['categories']), array_keys($dataB['categories'])));
        sort($allCatNames);

        foreach ($allCatNames as $cName) {
            $revA = $dataA['categories'][$cName]['revenue'] ?? 0;
            $costA = $dataA['categories'][$cName]['cost'] ?? 0;
            $profA = $revA - $costA;

            $revB = $dataB['categories'][$cName]['revenue'] ?? 0;
            $costB = $dataB['categories'][$cName]['cost'] ?? 0;
            $profB = $revB - $costB;

            $sheet->setCellValue('A' . $row, $cName);
            $sheet->setCellValue('B' . $row, $revA);
            $sheet->setCellValue('C' . $row, $profA);
            $sheet->setCellValue('D' . $row, $revB);
            $sheet->setCellValue('E' . $row, $profB);
            $sheet->setCellValue('F' . $row, "=D{$row}-B{$row}");
            $sheet->setCellValue('G' . $row, "=E{$row}-C{$row}");

            $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($dataStyle);
            $sheet->getStyle('B' . $row . ':G' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            $row++;
        }
        $row += 2;

        // SECTION 4: DAILY COMPARISON (DÍA 1 AL 31)
        $sheet->setCellValue('A' . $row, '4. COMPARATIVA DE INGRESOS Y UTILIDAD DÍA A DÍA (DÍA 1 AL 31)');
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($sectionHeaderStyle);
        $sheet->getRowDimension($row)->setRowHeight(24);
        $row++;

        $sheet->setCellValue('A' . $row, 'DÍA');
        $sheet->setCellValue('B' . $row, 'VENTAS ' . strtoupper($labelA));
        $sheet->setCellValue('C' . $row, 'UTILIDAD ' . strtoupper($labelA));
        $sheet->setCellValue('D' . $row, 'VENTAS ' . strtoupper($labelB));
        $sheet->setCellValue('E' . $row, 'UTILIDAD ' . strtoupper($labelB));
        $sheet->setCellValue('F' . $row, 'DIFERENCIA VENTAS');
        $sheet->setCellValue('G' . $row, 'DIFERENCIA UTILIDAD');
        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($subHeaderStyle);
        $sheet->getRowDimension($row)->setRowHeight(22);
        $row++;

        for ($d = 1; $d <= 31; $d++) {
            $dRevA = $dataA['dailySales'][$d] ?? 0;
            $dCostA = $dataA['dailyCost'][$d] ?? 0;
            $dProfA = $dRevA - $dCostA;

            $dRevB = $dataB['dailySales'][$d] ?? 0;
            $dCostB = $dataB['dailyCost'][$d] ?? 0;
            $dProfB = $dRevB - $dCostB;

            $sheet->setCellValue('A' . $row, "Día {$d}");
            $sheet->setCellValue('B' . $row, $dRevA);
            $sheet->setCellValue('C' . $row, $dProfA);
            $sheet->setCellValue('D' . $row, $dRevB);
            $sheet->setCellValue('E' . $row, $dProfB);
            $sheet->setCellValue('F' . $row, "=D{$row}-B{$row}");
            $sheet->setCellValue('G' . $row, "=E{$row}-C{$row}");

            $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($dataStyle);
            $sheet->getStyle('B' . $row . ':G' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            $row++;
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'pyg-versus-' . $startDateA . '-vs-' . $startDateB . '.xlsx';
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
        $sellerId = $request->get('seller_id');
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

        if ($sellerId) {
            $query->where('sales.seller_id', $sellerId);
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
        $allInvoices = Sale::with('seller')
            ->where('sales.payment_type', 'credit')
            ->where('sales.status', 'completed')
            ->whereIn('sales.customer_id', $customerSummaries->pluck('id'));

        if ($branchId) {
            $allInvoices->where('sales.branch_id', $branchId);
        } elseif (!$user->isSuperAdmin()) {
            $allInvoices->where('sales.branch_id', $user->branch_id);
        }
        if ($sellerId) {
            $allInvoices->where('sales.seller_id', $sellerId);
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
        $sheet->mergeCells('A' . $row . ':J' . $row);
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

        if ($sellerId) {
            $sUser = User::find($sellerId);
            $sheet->setCellValue('A' . $row, 'Vendedor:');
            $sheet->setCellValue('B' . $row, $sUser?->name ?? '-');
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
        }

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
            $sheet->setCellValue('D' . $row, 'Doc: ' . $customer->document_number);
            $sheet->setCellValue('G' . $row, 'Tel: ' . ($customer->phone ?? '-'));
            $sheet->mergeCells('A' . $row . ':C' . $row);
            $sheet->mergeCells('D' . $row . ':F' . $row);
            $sheet->mergeCells('G' . $row . ':J' . $row);
            $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray($customerHeaderStyle);
            $row++;

            // Invoice headers
            $sheet->setCellValue('A' . $row, 'Factura');
            $sheet->setCellValue('B' . $row, 'Fecha');
            $sheet->setCellValue('C' . $row, 'Vencimiento');
            $sheet->setCellValue('D' . $row, 'Días en Mora');
            $sheet->setCellValue('E' . $row, 'Vendedor');
            $sheet->setCellValue('F' . $row, 'Total Venta');
            $sheet->setCellValue('G' . $row, 'Total Crédito');
            $sheet->setCellValue('H' . $row, 'Pagado');
            $sheet->setCellValue('I' . $row, 'Pendiente');
            $sheet->setCellValue('J' . $row, 'Estado');
            $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray($headerStyle);
            $row++;

            // Invoice rows
            $invoices = $invoicesByCustomer->get($customer->id, collect());
            foreach ($invoices as $invoice) {
                $remaining = (float) $invoice->credit_amount - (float) $invoice->paid_amount;
                $statusLabels = ['pending' => 'Pendiente', 'partial' => 'Parcial', 'paid' => 'Pagado'];
                $dueDateStr = $invoice->payment_due_date ? $invoice->payment_due_date->format('d/m/Y') : ($invoice->created_at ? $invoice->created_at->copy()->addDays(30)->format('d/m/Y') : '-');
                $daysOverdue = $invoice->days_overdue;
                $moraStr = $remaining <= 0 || $invoice->payment_status === 'paid' ? 'Saldado' : ($daysOverdue === 0 ? 'Al día' : "{$daysOverdue} días");

                $sheet->setCellValue('A' . $row, $invoice->invoice_number);
                $sheet->setCellValue('B' . $row, $invoice->created_at->format('d/m/Y'));
                $sheet->setCellValue('C' . $row, $dueDateStr);
                $sheet->setCellValue('D' . $row, $moraStr);
                $sheet->setCellValue('E' . $row, $invoice->seller?->name ?? '-');
                $sheet->setCellValue('F' . $row, (float) $invoice->total);
                $sheet->setCellValue('G' . $row, (float) $invoice->credit_amount);
                $sheet->setCellValue('H' . $row, (float) $invoice->paid_amount);
                $sheet->setCellValue('I' . $row, $remaining);
                $sheet->setCellValue('J' . $row, $statusLabels[$invoice->payment_status] ?? $invoice->payment_status);
                $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray($dataStyle);
                $sheet->getStyle('F' . $row . ':I' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');

                if ($daysOverdue > 0 && $remaining > 0) {
                    $sheet->getStyle('D' . $row)->getFont()->setColor(new Color('DC2626'))->setBold(true);
                }

                if ($remaining > 0) {
                    $sheet->getStyle('I' . $row)->getFont()->setColor(new Color('DC2626'));
                }
                $row++;
            }

            // Customer subtotal
            $sheet->setCellValue('A' . $row, 'Subtotal ' . $customer->customer_name);
            $sheet->mergeCells('A' . $row . ':E' . $row);
            $sheet->setCellValue('F' . $row, '');
            $sheet->setCellValue('G' . $row, (float) $customer->total_credit);
            $sheet->setCellValue('H' . $row, (float) $customer->total_paid);
            $sheet->setCellValue('I' . $row, (float) $customer->total_remaining);
            $sheet->setCellValue('J' . $row, $customer->total_invoices . ' factura(s)');
            $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray($subtotalStyle);
            $sheet->getStyle('G' . $row . ':I' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet->getStyle('I' . $row)->getFont()->setBold(true)->setColor(new Color('DC2626'));
            $row += 2;
        }

        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'reporte-creditos-cliente-' . now()->format('Y-m-d') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function creditsPdf(Request $request)
    {
        $dateRange = $request->get('date_range', 'all');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $branchId = $request->get('branch_id');
        $sellerId = $request->get('seller_id');
        $paymentStatus = $request->get('payment_status', '');
        $search = $request->get('search', '');
        $user = auth()->user();

        $branch = null;
        $branchName = 'Todas las sucursales';
        if ($branchId) {
            $branch = Branch::find($branchId);
            $branchName = $branch?->name ?? 'Todas';
        } elseif (!$user->isSuperAdmin()) {
            $branchId = $user->branch_id;
            $branch = Branch::find($branchId);
            $branchName = $branch?->name ?? '';
        }

        $seller = $sellerId ? User::find($sellerId) : null;

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

        if ($sellerId) {
            $query->where('sales.seller_id', $sellerId);
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

        // Get customer summaries
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

        // Invoices by customer
        $allInvoices = Sale::with(['seller', 'customer'])
            ->where('sales.payment_type', 'credit')
            ->where('sales.status', 'completed')
            ->whereIn('sales.customer_id', $customerSummaries->pluck('id'));

        if ($branchId) {
            $allInvoices->where('sales.branch_id', $branchId);
        } elseif (!$user->isSuperAdmin()) {
            $allInvoices->where('sales.branch_id', $user->branch_id);
        }
        if ($sellerId) {
            $allInvoices->where('sales.seller_id', $sellerId);
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

        // Totals & Overdue analytics
        $grandTotalCredit = (float) $customerSummaries->sum('total_credit');
        $grandTotalPaid = (float) $customerSummaries->sum('total_paid');
        $grandTotalRemaining = (float) $customerSummaries->sum('total_remaining');
        $grandTotalInvoices = (int) $customerSummaries->sum('total_invoices');

        $totalOverdueAmount = 0;
        $totalCurrentAmount = 0;
        $overdueInvoicesCount = 0;
        $currentInvoicesCount = 0;

        foreach ($invoicesByCustomer as $custId => $invoices) {
            foreach ($invoices as $inv) {
                $rem = (float) $inv->credit_amount - (float) $inv->paid_amount;
                if ($rem > 0) {
                    if ($inv->days_overdue > 0) {
                        $totalOverdueAmount += $rem;
                        $overdueInvoicesCount++;
                    } else {
                        $totalCurrentAmount += $rem;
                        $currentInvoicesCount++;
                    }
                }
            }
        }

        $pdf = Pdf::loadView('reports.credits-pdf', [
            'customerSummaries' => $customerSummaries,
            'invoicesByCustomer' => $invoicesByCustomer,
            'branch' => $branch,
            'branchName' => $branchName,
            'seller' => $seller,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'dateRange' => $dateRange,
            'paymentStatus' => $paymentStatus,
            'search' => $search,
            'generatedAt' => now()->format('d/m/Y h:i A'),
            'generatedBy' => $user->name,
            'grandTotalCredit' => $grandTotalCredit,
            'grandTotalPaid' => $grandTotalPaid,
            'grandTotalRemaining' => $grandTotalRemaining,
            'grandTotalInvoices' => $grandTotalInvoices,
            'totalOverdueAmount' => $totalOverdueAmount,
            'totalCurrentAmount' => $totalCurrentAmount,
            'overdueInvoicesCount' => $overdueInvoicesCount,
            'currentInvoicesCount' => $currentInvoicesCount,
        ]);

        $pdf->setPaper('a4', 'landscape');
        $filename = 'reporte-creditos-cartera-' . now()->format('Y-m-d') . '.pdf';

        if ($request->has('download')) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }

    public function paymentMethodsExcel(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $branchId = $request->get('branch_id');
        $cashRegisterId = $request->get('cash_register_id');
        $paymentMethodId = $request->get('payment_method_id');
        $userId = $request->get('user_id');
        $flowType = $request->get('flow_type', 'all');
        $conceptType = $request->get('concept_type', 'all');
        $cashAffectation = $request->get('cash_affectation', 'all');
        $user = auth()->user();

        $branchName = 'Todas';
        if ($branchId) {
            $branchName = Branch::find($branchId)?->name ?? 'Todas';
        } elseif (!$user->isSuperAdmin()) {
            $branchId = $user->branch_id;
            $branchName = Branch::find($branchId)?->name ?? '';
        }

        $shouldInclude = function(string $flow, string $concept) use ($flowType, $conceptType): bool {
            if ($flowType !== 'all' && $flowType !== $flow) return false;
            if ($conceptType !== 'all' && $conceptType !== $concept) return false;
            return true;
        };

        // Queries
        $customerSql = "COALESCE(CASE WHEN customers.customer_type = 'juridico' AND customers.business_name IS NOT NULL AND customers.business_name != '' THEN customers.business_name ELSE TRIM(CONCAT(COALESCE(customers.first_name, ''), ' ', COALESCE(customers.last_name, ''))) END, 'Cliente General')";

        $allMethods = PaymentMethod::where('is_active', true)->orderBy('name')->get()->keyBy('id');
        $methodsMap = [];
        foreach ($allMethods as $id => $pm) {
            $methodsMap[$id] = [
                'id' => $id,
                'name' => $pm->name,
                'sales_total' => 0.0,
                'receivables_total' => 0.0,
                'expenses_total' => 0.0,
                'payrolls_total' => 0.0,
                'payables_total' => 0.0,
                'purchases_total' => 0.0,
                'total_income' => 0.0,
                'total_expense' => 0.0,
                'net_total' => 0.0,
                'transaction_count' => 0,
            ];
        }

        $detailQueries = [];

        // 1. Sales
        if ($shouldInclude('income', 'sales')) {
            $salesQuery = SalePayment::join('sales', 'sale_payments.sale_id', '=', 'sales.id')
                ->join('payment_methods', 'sale_payments.payment_method_id', '=', 'payment_methods.id')
                ->join('users', 'sales.user_id', '=', 'users.id')
                ->leftJoin('branches', 'sales.branch_id', '=', 'branches.id')
                ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
                ->where('sales.status', 'completed')
                ->whereDate('sales.created_at', '>=', $startDate)
                ->whereDate('sales.created_at', '<=', $endDate);

            if ($branchId) $salesQuery->where('sales.branch_id', $branchId);
            if ($cashRegisterId) $salesQuery->whereHas('sale.cashReconciliation', fn($q) => $q->where('cash_register_id', $cashRegisterId));
            if ($paymentMethodId) $salesQuery->where('sale_payments.payment_method_id', $paymentMethodId);
            if ($userId) $salesQuery->where('sales.user_id', $userId);
            if ($cashAffectation === 'with_cash') $salesQuery->whereNotNull('sales.cash_reconciliation_id');
            elseif ($cashAffectation === 'without_cash') $salesQuery->whereNull('sales.cash_reconciliation_id');

            $salesAgg = (clone $salesQuery)->select('sale_payments.payment_method_id', DB::raw('SUM(sale_payments.amount) as total'), DB::raw('COUNT(DISTINCT sales.id) as cnt'))->groupBy('sale_payments.payment_method_id')->get();
            foreach ($salesAgg as $sa) {
                if (isset($methodsMap[$sa->payment_method_id])) {
                    $methodsMap[$sa->payment_method_id]['sales_total'] += (float) $sa->total;
                    $methodsMap[$sa->payment_method_id]['transaction_count'] += (int) $sa->cnt;
                }
            }

            $detailQueries[] = (clone $salesQuery)->select([
                DB::raw("'Venta POS' as operation_label"),
                'sales.invoice_number as document_number',
                DB::raw("{$customerSql} as third_party_name"),
                'sales.created_at as payment_date',
                'payment_methods.name as payment_method_name',
                'users.name as user_name',
                'branches.name as branch_name',
                DB::raw("(CASE WHEN sales.cash_reconciliation_id IS NOT NULL THEN 'Sí' ELSE 'No' END) as affects_cash"),
                'sale_payments.amount as amount',
                DB::raw("'+' as flow_sign")
            ]);
        }

        // 2. Receivables
        if ($shouldInclude('income', 'receivables')) {
            $recQuery = CreditPayment::join('payment_methods', 'credit_payments.payment_method_id', '=', 'payment_methods.id')
                ->join('users', 'credit_payments.user_id', '=', 'users.id')
                ->leftJoin('branches', 'credit_payments.branch_id', '=', 'branches.id')
                ->leftJoin('sales', 'credit_payments.sale_id', '=', 'sales.id')
                ->leftJoin('customers', function ($join) {
                    $join->on('credit_payments.customer_id', '=', 'customers.id')
                        ->orWhere(function ($q) {
                            $q->whereNull('credit_payments.customer_id')->whereColumn('sales.customer_id', 'customers.id');
                        });
                })
                ->where('credit_payments.credit_type', 'receivable')
                ->whereDate('credit_payments.created_at', '>=', $startDate)
                ->whereDate('credit_payments.created_at', '<=', $endDate);

            if ($branchId) $recQuery->where('credit_payments.branch_id', $branchId);
            if ($cashRegisterId) $recQuery->whereHas('cashReconciliation', fn($q) => $q->where('cash_register_id', $cashRegisterId));
            if ($paymentMethodId) $recQuery->where('credit_payments.payment_method_id', $paymentMethodId);
            if ($userId) $recQuery->where('credit_payments.user_id', $userId);
            if ($cashAffectation === 'with_cash') $recQuery->where('credit_payments.affects_cash', true);
            elseif ($cashAffectation === 'without_cash') $recQuery->where('credit_payments.affects_cash', false);

            $recAgg = (clone $recQuery)->select('credit_payments.payment_method_id', DB::raw('SUM(credit_payments.amount) as total'), DB::raw('COUNT(DISTINCT credit_payments.id) as cnt'))->groupBy('credit_payments.payment_method_id')->get();
            foreach ($recAgg as $ra) {
                if (isset($methodsMap[$ra->payment_method_id])) {
                    $methodsMap[$ra->payment_method_id]['receivables_total'] += (float) $ra->total;
                    $methodsMap[$ra->payment_method_id]['transaction_count'] += (int) $ra->cnt;
                }
            }

            $detailQueries[] = (clone $recQuery)->select([
                DB::raw("'Cobro Cartera' as operation_label"),
                'credit_payments.payment_number as document_number',
                DB::raw("{$customerSql} as third_party_name"),
                'credit_payments.created_at as payment_date',
                'payment_methods.name as payment_method_name',
                'users.name as user_name',
                'branches.name as branch_name',
                DB::raw("(CASE WHEN credit_payments.affects_cash = 1 THEN 'Sí' ELSE 'No' END) as affects_cash"),
                'credit_payments.amount as amount',
                DB::raw("'+' as flow_sign")
            ]);
        }

        // 3. Expenses
        if ($shouldInclude('expense', 'expenses')) {
            $expQuery = Expense::join('payment_methods', 'expenses.payment_method_id', '=', 'payment_methods.id')
                ->join('users', 'expenses.user_id', '=', 'users.id')
                ->leftJoin('branches', 'expenses.branch_id', '=', 'branches.id')
                ->leftJoin('customers', function ($join) {
                    $join->on('expenses.contact_id', '=', 'customers.id')->where('expenses.contact_type', '=', 'customer');
                })
                ->leftJoin('suppliers', function ($join) {
                    $join->on('expenses.contact_id', '=', 'suppliers.id')->where('expenses.contact_type', '=', 'supplier');
                })
                ->whereDate('expenses.expense_date', '>=', $startDate)
                ->whereDate('expenses.expense_date', '<=', $endDate);

            if ($branchId) $expQuery->where('expenses.branch_id', $branchId);
            if ($paymentMethodId) $expQuery->where('expenses.payment_method_id', $paymentMethodId);
            if ($userId) $expQuery->where('expenses.user_id', $userId);

            $expAgg = (clone $expQuery)->select('expenses.payment_method_id', DB::raw('SUM(expenses.amount) as total'), DB::raw('COUNT(DISTINCT expenses.id) as cnt'))->groupBy('expenses.payment_method_id')->get();
            foreach ($expAgg as $ea) {
                if (isset($methodsMap[$ea->payment_method_id])) {
                    $methodsMap[$ea->payment_method_id]['expenses_total'] += (float) $ea->total;
                    $methodsMap[$ea->payment_method_id]['transaction_count'] += (int) $ea->cnt;
                }
            }

            $detailQueries[] = (clone $expQuery)->select([
                DB::raw("'Gasto' as operation_label"),
                DB::raw("CONCAT('GST-', LPAD(expenses.id, 5, '0')) as document_number"),
                DB::raw("COALESCE(suppliers.name, {$customerSql}, expenses.description) as third_party_name"),
                'expenses.expense_date as payment_date',
                'payment_methods.name as payment_method_name',
                'users.name as user_name',
                'branches.name as branch_name',
                DB::raw("'No' as affects_cash"),
                'expenses.amount as amount',
                DB::raw("'-' as flow_sign")
            ]);
        }

        // 4. Payables (Credit Payments to Suppliers)
        if ($shouldInclude('expense', 'payables')) {
            $payQuery = CreditPayment::join('payment_methods', 'credit_payments.payment_method_id', '=', 'payment_methods.id')
                ->join('users', 'credit_payments.user_id', '=', 'users.id')
                ->leftJoin('branches', 'credit_payments.branch_id', '=', 'branches.id')
                ->leftJoin('purchases', 'credit_payments.purchase_id', '=', 'purchases.id')
                ->leftJoin('suppliers', function ($join) {
                    $join->on('credit_payments.supplier_id', '=', 'suppliers.id')
                        ->orWhere(function ($q) {
                            $q->whereNull('credit_payments.supplier_id')->whereColumn('purchases.supplier_id', 'suppliers.id');
                        });
                })
                ->where('credit_payments.credit_type', 'payable')
                ->whereDate('credit_payments.created_at', '>=', $startDate)
                ->whereDate('credit_payments.created_at', '<=', $endDate);

            if ($branchId) $payQuery->where('credit_payments.branch_id', $branchId);
            if ($cashRegisterId) $payQuery->whereHas('cashReconciliation', fn($q) => $q->where('cash_register_id', $cashRegisterId));
            if ($paymentMethodId) $payQuery->where('credit_payments.payment_method_id', $paymentMethodId);
            if ($userId) $payQuery->where('credit_payments.user_id', $userId);
            if ($cashAffectation === 'with_cash') $payQuery->where('credit_payments.affects_cash', true);
            elseif ($cashAffectation === 'without_cash') $payQuery->where('credit_payments.affects_cash', false);

            $payAgg = (clone $payQuery)->select('credit_payments.payment_method_id', DB::raw('SUM(credit_payments.amount) as total'), DB::raw('COUNT(DISTINCT credit_payments.id) as cnt'))->groupBy('credit_payments.payment_method_id')->get();
            foreach ($payAgg as $pa) {
                if (isset($methodsMap[$pa->payment_method_id])) {
                    $methodsMap[$pa->payment_method_id]['payables_total'] += (float) $pa->total;
                    $methodsMap[$pa->payment_method_id]['transaction_count'] += (int) $pa->cnt;
                }
            }

            $detailQueries[] = (clone $payQuery)->select([
                DB::raw("'Pago Proveedor' as operation_label"),
                'credit_payments.payment_number as document_number',
                DB::raw("COALESCE(suppliers.name, 'Proveedor') as third_party_name"),
                'credit_payments.created_at as payment_date',
                'payment_methods.name as payment_method_name',
                'users.name as user_name',
                'branches.name as branch_name',
                DB::raw("(CASE WHEN credit_payments.affects_cash = 1 THEN 'Sí' ELSE 'No' END) as affects_cash"),
                'credit_payments.amount as amount',
                DB::raw("'-' as flow_sign")
            ]);
        }

        // 5. Purchases (Cash)
        if ($shouldInclude('expense', 'purchases')) {
            $purQuery = Purchase::join('payment_methods', 'purchases.payment_method_id', '=', 'payment_methods.id')
                ->join('users', 'purchases.user_id', '=', 'users.id')
                ->leftJoin('branches', 'purchases.branch_id', '=', 'branches.id')
                ->leftJoin('suppliers', 'purchases.supplier_id', '=', 'suppliers.id')
                ->where('purchases.payment_type', 'cash')
                ->whereDate('purchases.purchase_date', '>=', $startDate)
                ->whereDate('purchases.purchase_date', '<=', $endDate);

            if ($branchId) $purQuery->where('purchases.branch_id', $branchId);
            if ($paymentMethodId) $purQuery->where('purchases.payment_method_id', $paymentMethodId);
            if ($userId) $purQuery->where('purchases.user_id', $userId);

            $purAgg = (clone $purQuery)->select('purchases.payment_method_id', DB::raw('SUM(COALESCE(purchases.paid_amount, purchases.total)) as total'), DB::raw('COUNT(DISTINCT purchases.id) as cnt'))->groupBy('purchases.payment_method_id')->get();
            foreach ($purAgg as $pua) {
                if (isset($methodsMap[$pua->payment_method_id])) {
                    $methodsMap[$pua->payment_method_id]['purchases_total'] += (float) $pua->total;
                    $methodsMap[$pua->payment_method_id]['transaction_count'] += (int) $pua->cnt;
                }
            }

            $detailQueries[] = (clone $purQuery)->select([
                DB::raw("'Compra Contado' as operation_label"),
                'purchases.purchase_number as document_number',
                DB::raw("COALESCE(suppliers.name, 'Proveedor') as third_party_name"),
                'purchases.purchase_date as payment_date',
                'payment_methods.name as payment_method_name',
                'users.name as user_name',
                'branches.name as branch_name',
                DB::raw("'No' as affects_cash"),
                DB::raw("COALESCE(purchases.paid_amount, purchases.total) as amount"),
                DB::raw("'-' as flow_sign")
            ]);
        }

        // Summary items
        $summary = collect();
        foreach ($methodsMap as $m) {
            $inc = $m['sales_total'] + $m['receivables_total'];
            $exp = $m['expenses_total'] + $m['payrolls_total'] + $m['payables_total'] + $m['purchases_total'];
            if ($m['transaction_count'] === 0 && empty($paymentMethodId)) continue;

            $m['total_income'] = $inc;
            $m['total_expense'] = $exp;
            $m['net_total'] = $inc - $exp;
            $summary->push((object) $m);
        }

        $summary = $summary->sortByDesc('total_income')->values();
        $grandTotalIncome = (float) $summary->sum('total_income');
        $grandTotalExpense = (float) $summary->sum('total_expense');
        $grandNetTotal = $grandTotalIncome - $grandTotalExpense;

        // Detail list
        $detail = collect();
        if (count($detailQueries) > 0) {
            $mainQ = array_shift($detailQueries);
            foreach ($detailQueries as $dq) {
                $mainQ = $mainQ->unionAll($dq);
            }
            $detail = DB::query()->fromSub($mainQ, 'combined')->orderByDesc('payment_date')->get();
        }

        // Build Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Resumen');

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
        $sheet->setCellValue('A' . $row, 'REPORTE CONSOLIDADO DE MEDIOS DE PAGO');
        $sheet->mergeCells('A' . $row . ':J' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray($titleStyle);
        $sheet->getRowDimension($row)->setRowHeight(30);
        $row += 2;

        $sheet->setCellValue('A' . $row, 'Período:'); $sheet->setCellValue('B' . $row, $startDate . ' - ' . $endDate); $sheet->getStyle('A' . $row)->getFont()->setBold(true); $row++;
        $sheet->setCellValue('A' . $row, 'Sucursal:'); $sheet->setCellValue('B' . $row, $branchName); $sheet->getStyle('A' . $row)->getFont()->setBold(true); $row++;
        $sheet->setCellValue('A' . $row, 'Generado:'); $sheet->setCellValue('B' . $row, now()->format('d/m/Y H:i')); $sheet->getStyle('A' . $row)->getFont()->setBold(true); $row += 2;

        // Summary section
        $sheet->setCellValue('A' . $row, 'RESUMEN DE INGRESOS Y EGRESOS POR MÉTODO DE PAGO'); $sheet->getStyle('A' . $row)->applyFromArray($subtitleStyle); $row++;
        $sheet->setCellValue('A' . $row, 'Método de Pago'); 
        $sheet->setCellValue('B' . $row, 'Ventas Directas (+)');
        $sheet->setCellValue('C' . $row, 'Abonos Cartera (+)');
        $sheet->setCellValue('D' . $row, 'Total Ingresos (+)');
        $sheet->setCellValue('E' . $row, 'Gastos (-)');
        $sheet->setCellValue('F' . $row, 'Nómina (-)');
        $sheet->setCellValue('G' . $row, 'Proveedores / Compras (-)');
        $sheet->setCellValue('H' . $row, 'Total Egresos (-)');
        $sheet->setCellValue('I' . $row, 'Balance Neto');
        $sheet->setCellValue('J' . $row, 'Transacciones');
        $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray($headerStyle); $row++;

        foreach ($summary as $item) {
            $sheet->setCellValue('A' . $row, $item->name);
            $sheet->setCellValue('B' . $row, $item->sales_total);
            $sheet->setCellValue('C' . $row, $item->receivables_total);
            $sheet->setCellValue('D' . $row, $item->total_income);
            $sheet->setCellValue('E' . $row, $item->expenses_total);
            $sheet->setCellValue('F' . $row, $item->payrolls_total);
            $sheet->setCellValue('G' . $row, $item->payables_total + $item->purchases_total);
            $sheet->setCellValue('H' . $row, $item->total_expense);
            $sheet->setCellValue('I' . $row, $item->net_total);
            $sheet->setCellValue('J' . $row, $item->transaction_count);
            $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray($dataStyle);
            foreach (['B','C','D','E','F','G','H','I'] as $col) {
                $sheet->getStyle($col . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            }
            $row++;
        }
        // Total row
        $sheet->setCellValue('A' . $row, 'TOTAL GENERAL');
        $sheet->setCellValue('B' . $row, $summary->sum('sales_total'));
        $sheet->setCellValue('C' . $row, $summary->sum('receivables_total'));
        $sheet->setCellValue('D' . $row, $grandTotalIncome);
        $sheet->setCellValue('E' . $row, $summary->sum('expenses_total'));
        $sheet->setCellValue('F' . $row, $summary->sum('payrolls_total'));
        $sheet->setCellValue('G' . $row, $summary->sum('payables_total') + $summary->sum('purchases_total'));
        $sheet->setCellValue('H' . $row, $grandTotalExpense);
        $sheet->setCellValue('I' . $row, $grandNetTotal);
        $sheet->setCellValue('J' . $row, $summary->sum('transaction_count'));
        $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray($headerStyle);
        foreach (['B','C','D','E','F','G','H','I'] as $col) {
            $sheet->getStyle($col . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
        }
        $row += 2;

        // Detail section (new sheet)
        $detailSheet = $spreadsheet->createSheet();
        $detailSheet->setTitle('Detalle de Movimientos');
        $dRow = 1;
        $detailSheet->setCellValue('A' . $dRow, 'DETALLE DE MOVIMIENTOS');
        $detailSheet->mergeCells('A' . $dRow . ':I' . $dRow);
        $detailSheet->getStyle('A' . $dRow)->applyFromArray($titleStyle);
        $detailSheet->getRowDimension($dRow)->setRowHeight(30);
        $dRow += 2;

        $detailSheet->setCellValue('A' . $dRow, 'Concepto');
        $detailSheet->setCellValue('B' . $dRow, 'Documento');
        $detailSheet->setCellValue('C' . $dRow, 'Tercero / Cliente');
        $detailSheet->setCellValue('D' . $dRow, 'Fecha');
        $detailSheet->setCellValue('E' . $dRow, 'Método');
        $detailSheet->setCellValue('F' . $dRow, 'Registrado por');
        $detailSheet->setCellValue('G' . $dRow, 'Sucursal');
        $detailSheet->setCellValue('H' . $dRow, 'Afectó Caja');
        $detailSheet->setCellValue('I' . $dRow, 'Monto');
        $detailSheet->getStyle('A' . $dRow . ':I' . $dRow)->applyFromArray($headerStyle);
        $dRow++;

        foreach ($detail as $item) {
            $detailSheet->setCellValue('A' . $dRow, $item->operation_label);
            $detailSheet->setCellValue('B' . $dRow, $item->document_number);
            $detailSheet->setCellValue('C' . $dRow, $item->third_party_name ?? '-');
            $detailSheet->setCellValue('D' . $dRow, Carbon::parse($item->payment_date)->format('d/m/Y H:i'));
            $detailSheet->setCellValue('E' . $dRow, $item->payment_method_name);
            $detailSheet->setCellValue('F' . $dRow, $item->user_name);
            $detailSheet->setCellValue('G' . $dRow, $item->branch_name ?? '-');
            $detailSheet->setCellValue('H' . $dRow, $item->affects_cash);
            $val = ($item->flow_sign === '-' ? -1 : 1) * (float) $item->amount;
            $detailSheet->setCellValue('I' . $dRow, $val);
            $detailSheet->getStyle('A' . $dRow . ':I' . $dRow)->applyFromArray($dataStyle);
            $detailSheet->getStyle('I' . $dRow)->getNumberFormat()->setFormatCode('$#,##0.00');
            $dRow++;
        }

        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        foreach (range('A', 'I') as $col) {
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
        $cashierId = $request->get('cashier_id');
        $paymentMethodId = $request->get('payment_method_id');
        $cashRegisterId = $request->get('cash_register_id');
        $statusFilter = $request->get('status', 'all');
        $search = $request->get('search', '');

        $user = auth()->user();

        // Build query
        $query = Sale::query()
            ->with(['customer', 'user', 'seller', 'branch', 'payments.paymentMethod', 'cashReconciliation.cashRegister']);

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
            $query->where('sales.seller_id', $userId);
        }

        if ($cashierId) {
            $query->where('sales.user_id', $cashierId);
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

        $sellerName = 'Todos';
        if ($userId) {
            $sellerName = User::find($userId)?->name ?? 'Todos';
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

        $sheet->setCellValue('A' . $row, 'Vendedor:');
        $sheet->setCellValue('B' . $row, $sellerName);
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

        $headers = ['Factura', 'No. DIAN', 'Fecha', 'Hora', 'Cliente', 'Documento', 'Vendedor', 'Cajero / Usuario', 'Forma de Pago', 'Subtotal', 'Impuestos', 'Descuento', 'Total', 'Estado', 'Tipo Pago', 'Caja'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $col++;
        }
        $sheet->getStyle('A' . $row . ':P' . $row)->applyFromArray($headerStyle);
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
            $sheet->setCellValue('G' . $row, $sale->seller?->name ?? '-');
            $sheet->setCellValue('H' . $row, $sale->user?->name ?? '-');
            $sheet->setCellValue('I' . $row, $paymentMethods);
            $sheet->setCellValue('J' . $row, (float) $sale->subtotal);
            $sheet->setCellValue('K' . $row, (float) $sale->tax_total);
            $sheet->setCellValue('L' . $row, (float) $sale->discount);
            $sheet->setCellValue('M' . $row, (float) $sale->total);
            $sheet->setCellValue('N' . $row, $status);
            $sheet->setCellValue('O' . $row, $paymentType);
            $sheet->setCellValue('P' . $row, $cashRegister);

            $sheet->getStyle('A' . $row . ':P' . $row)->applyFromArray($dataStyle);
            $sheet->getStyle('J' . $row)->getNumberFormat()->setFormatCode('$#,##0');
            $sheet->getStyle('K' . $row)->getNumberFormat()->setFormatCode('$#,##0');
            $sheet->getStyle('L' . $row)->getNumberFormat()->setFormatCode('$#,##0');
            $sheet->getStyle('M' . $row)->getNumberFormat()->setFormatCode('$#,##0');

            if ($row % 2 == 0) {
                $sheet->getStyle('A' . $row . ':P' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
            }

            if ($sale->status !== 'completed') {
                $sheet->getStyle('N' . $row)->getFont()->getColor()->setRGB('DC2626');
            }

            $row++;
        }

        // Totals row
        $sheet->setCellValue('I' . $row, 'TOTALES:');
        $sheet->getStyle('I' . $row)->getFont()->setBold(true);
        $sheet->setCellValue('J' . $row, $completedSales->sum('subtotal'));
        $sheet->setCellValue('K' . $row, $completedSales->sum('tax_total'));
        $sheet->setCellValue('L' . $row, $completedSales->sum('discount'));
        $sheet->setCellValue('M' . $row, $completedSales->sum('total'));
        $sheet->getStyle('I' . $row . ':P' . $row)->applyFromArray($summaryStyle);
        $sheet->getStyle('J' . $row)->getNumberFormat()->setFormatCode('$#,##0');
        $sheet->getStyle('K' . $row)->getNumberFormat()->setFormatCode('$#,##0');
        $sheet->getStyle('L' . $row)->getNumberFormat()->setFormatCode('$#,##0');
        $sheet->getStyle('M' . $row)->getNumberFormat()->setFormatCode('$#,##0');

        // Auto-size columns
        foreach (range('A', 'P') as $col) {
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

    /**
     * Helper to compute full Sales Book metrics for a given period and filters.
     */
    private function calculateSalesBookMetrics(
        string $startDate,
        string $endDate,
        ?int $branchId = null,
        ?int $userId = null,
        ?int $cashierId = null,
        ?int $paymentMethodId = null,
        ?int $cashRegisterId = null,
        string $statusFilter = 'completed',
        string $search = '',
        $user = null
    ): array {
        if (!$user) {
            $user = auth()->user();
        }

        $query = Sale::query()->with(['items.product', 'seller', 'user', 'payments.paymentMethod', 'cashReconciliation']);

        if ($startDate) $query->whereDate('sales.created_at', '>=', $startDate);
        if ($endDate) $query->whereDate('sales.created_at', '<=', $endDate);

        if ($branchId) {
            $query->where('sales.branch_id', $branchId);
        } elseif ($user && !$user->isSuperAdmin()) {
            $query->where('sales.branch_id', $user->branch_id);
        }

        if ($userId) $query->where('sales.seller_id', $userId);
        if ($cashierId) $query->where('sales.user_id', $cashierId);

        if ($paymentMethodId) {
            $query->whereHas('payments', fn($q) => $q->where('payment_method_id', $paymentMethodId));
        }

        if ($user && $user->isSupervisor()) {
            $supervisorRegisterIds = $user->getSupervisorCashRegisterIds();
            if (empty($supervisorRegisterIds)) {
                $query->whereRaw('0 = 1');
            } else {
                $filterIds = ($cashRegisterId && in_array((int) $cashRegisterId, $supervisorRegisterIds))
                    ? [(int) $cashRegisterId]
                    : $supervisorRegisterIds;
                $query->whereHas('cashReconciliation', fn($q) => $q->whereIn('cash_register_id', $filterIds));
            }
        } elseif ($cashRegisterId) {
            $query->whereHas('cashReconciliation', fn($q) => $q->where('cash_register_id', $cashRegisterId));
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

        $sales = $query->orderBy('sales.created_at')->get();

        $completed = $sales->where('status', 'completed');
        $totalSales = (float) $completed->sum('total');
        $totalSubtotal = (float) $completed->sum('subtotal');
        $totalTax = (float) $completed->sum('tax_total');
        $totalDiscount = (float) $completed->sum('discount');
        $totalTransactions = (int) $completed->count();
        $averageTicket = $totalTransactions > 0 ? $totalSales / $totalTransactions : 0;

        $totalProfit = 0;
        $dailySales = [];
        $hourlySales = [];
        $sellerSales = [];
        $pmSales = [];

        foreach ($completed as $sale) {
            $saleProfit = 0;
            foreach ($sale->items as $item) {
                if ($item->product) {
                    $saleProfit += ((float) $item->subtotal - ((float) $item->unit_cost * (float) $item->quantity));
                }
            }
            $totalProfit += $saleProfit;

            $day = (int) $sale->created_at->format('j');
            if (!isset($dailySales[$day])) {
                $dailySales[$day] = ['count' => 0, 'total' => 0, 'profit' => 0];
            }
            $dailySales[$day]['count']++;
            $dailySales[$day]['total'] += (float) $sale->total;
            $dailySales[$day]['profit'] += $saleProfit;

            $hour = (int) $sale->created_at->format('G');
            if (!isset($hourlySales[$hour])) {
                $hourlySales[$hour] = ['count' => 0, 'total' => 0];
            }
            $hourlySales[$hour]['count']++;
            $hourlySales[$hour]['total'] += (float) $sale->total;

            $sellerName = $sale->seller?->name ?? 'Sin asignar';
            if (!isset($sellerSales[$sellerName])) {
                $sellerSales[$sellerName] = ['count' => 0, 'total' => 0];
            }
            $sellerSales[$sellerName]['count']++;
            $sellerSales[$sellerName]['total'] += (float) $sale->total;

            foreach ($sale->payments as $payment) {
                $pmName = $payment->paymentMethod?->name ?? 'Otro';
                if (!isset($pmSales[$pmName])) {
                    $pmSales[$pmName] = ['count' => 0, 'total' => 0];
                }
                $pmSales[$pmName]['count']++;
                $pmSales[$pmName]['total'] += (float) $payment->amount;
            }
        }

        return [
            'totalSales' => $totalSales,
            'totalSubtotal' => $totalSubtotal,
            'totalTax' => $totalTax,
            'totalDiscount' => $totalDiscount,
            'totalTransactions' => $totalTransactions,
            'averageTicket' => $averageTicket,
            'totalProfit' => $totalProfit,
            'dailySales' => $dailySales,
            'hourlySales' => $hourlySales,
            'sellerSales' => $sellerSales,
            'pmSales' => $pmSales,
        ];
    }

    /**
     * Export Sales Book Versus (Period A vs Period B) comparison as Excel.
     */
    public function salesBookVersusExcel(Request $request)
    {
        $startDateA = $request->get('start_date_a', now()->startOfMonth()->format('Y-m-d'));
        $endDateA = $request->get('end_date_a', now()->format('Y-m-d'));
        $startDateB = $request->get('start_date_b', now()->subMonth()->startOfMonth()->format('Y-m-d'));
        $endDateB = $request->get('end_date_b', now()->subMonth()->endOfMonth()->format('Y-m-d'));
        $labelA = $request->get('label_a', Carbon::parse($startDateA)->translatedFormat('F Y'));
        $labelB = $request->get('label_b', Carbon::parse($startDateB)->translatedFormat('F Y'));
        $branchId = $request->get('branch_id');
        $userId = $request->get('user_id');
        $cashierId = $request->get('cashier_id');
        $paymentMethodId = $request->get('payment_method_id');
        $cashRegisterId = $request->get('cash_register_id');
        $statusFilter = $request->get('status', 'completed');
        $search = $request->get('search', '');

        $user = auth()->user();
        $branchName = 'Todas las Sucursales';
        if ($branchId) {
            $branchName = Branch::find($branchId)?->name ?? 'Todas';
        } elseif (!$user->isSuperAdmin()) {
            $branchId = $user->branch_id;
            $branchName = Branch::find($branchId)?->name ?? '';
        }

        $dataA = $this->calculateSalesBookMetrics($startDateA, $endDateA, $branchId, $userId, $cashierId, $paymentMethodId, $cashRegisterId, $statusFilter, $search, $user);
        $dataB = $this->calculateSalesBookMetrics($startDateB, $endDateB, $branchId, $userId, $cashierId, $paymentMethodId, $cashRegisterId, $statusFilter, $search, $user);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ventas Versus');

        $mainHeaderStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 14],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A1225']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ];
        $sectionHeaderStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'A855F7']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '9333EA']]],
        ];
        $headerAStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FF7261']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ];
        $headerBStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '7C3AED']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ];
        $subHeaderStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => '1E293B'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ];
        $dataStyle = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];
        $totalHighlight = [
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ];

        $row = 1;

        // Title
        $sheet->setCellValue('A' . $row, 'MIKPOS - REPORTE COMPARATIVO DE LIBRO DE VENTAS (VERSUS)');
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($mainHeaderStyle);
        $sheet->getRowDimension($row)->setRowHeight(36);
        $row += 2;

        // Metadata
        $sheet->setCellValue('A' . $row, 'Período A (Base):');
        $sheet->setCellValue('B' . $row, ucfirst($labelA) . " ({$startDateA} al {$endDateA})");
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $sheet->setCellValue('D' . $row, 'Período B (Comparado):');
        $sheet->setCellValue('E' . $row, ucfirst($labelB) . " ({$startDateB} al {$endDateB})");
        $sheet->getStyle('D' . $row)->getFont()->setBold(true);
        $row++;

        $sheet->setCellValue('A' . $row, 'Sucursal:');
        $sheet->setCellValue('B' . $row, $branchName);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $sheet->setCellValue('D' . $row, 'Fecha Generación:');
        $sheet->setCellValue('E' . $row, now()->format('d/m/Y H:i:s'));
        $sheet->getStyle('D' . $row)->getFont()->setBold(true);
        $row += 2;

        // SECTION 1: SALES SUMMARY COMPARISON
        $sheet->setCellValue('A' . $row, '1. RESUMEN EJECUTIVO DE VENTAS');
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($sectionHeaderStyle);
        $sheet->getRowDimension($row)->setRowHeight(24);
        $row++;

        $sheet->setCellValue('A' . $row, 'MÉTRICA / INDICADOR');
        $sheet->setCellValue('B' . $row, 'PERÍODO A (' . strtoupper($labelA) . ')');
        $sheet->setCellValue('C' . $row, 'PERÍODO B (' . strtoupper($labelB) . ')');
        $sheet->setCellValue('D' . $row, 'DIFERENCIA (B - A)');
        $sheet->setCellValue('E' . $row, '% CRECIMIENTO');
        $sheet->setCellValue('F' . $row, 'PERÍODO LÍDER');
        $sheet->setCellValue('G' . $row, 'TENDENCIA');
        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($subHeaderStyle);
        $sheet->getStyle('B' . $row)->applyFromArray($headerAStyle);
        $sheet->getStyle('C' . $row)->applyFromArray($headerBStyle);
        $sheet->getRowDimension($row)->setRowHeight(22);
        $row++;

        $metrics = [
            ['Total Ventas', $dataA['totalSales'], $dataB['totalSales'], true, true],
            ['Subtotal sin Impuestos', $dataA['totalSubtotal'], $dataB['totalSubtotal'], true, true],
            ['Total Impuestos Recaudados', $dataA['totalTax'], $dataB['totalTax'], true, true],
            ['Total Descuentos Otorgados', $dataA['totalDiscount'], $dataB['totalDiscount'], true, false],
            ['Transacciones / Facturas', $dataA['totalTransactions'], $dataB['totalTransactions'], 'int', true],
            ['Ticket Promedio', $dataA['averageTicket'], $dataB['averageTicket'], true, true],
            ['Ganancia Estimada de Ventas', $dataA['totalProfit'], $dataB['totalProfit'], true, true],
        ];

        foreach ($metrics as $m) {
            $name = $m[0];
            $valA = $m[1];
            $valB = $m[2];
            $isMoney = $m[3] === true;
            $isInt = $m[3] === 'int';
            $higherIsBetter = $m[4];

            $growth = $valA != 0 ? (($valB - $valA) / abs($valA)) * 100 : ($valB > 0 ? 100 : 0);

            $winner = 'Empate';
            if ($higherIsBetter) {
                if ($valB > $valA) $winner = ucfirst($labelB) . ' 🏆';
                elseif ($valA > $valB) $winner = ucfirst($labelA) . ' 🏆';
            } else {
                if ($valB < $valA) $winner = ucfirst($labelB) . ' 🏆';
                elseif ($valA < $valB) $winner = ucfirst($labelA) . ' 🏆';
            }

            $trend = $growth > 0 ? '▲ Creció' : ($growth < 0 ? '▼ Cayó' : '— Igual');

            $sheet->setCellValue('A' . $row, $name);
            $sheet->setCellValue('B' . $row, $valA);
            $sheet->setCellValue('C' . $row, $valB);
            $sheet->setCellValue('D' . $row, "=C{$row}-B{$row}");
            $sheet->setCellValue('E' . $row, "=IF(B{$row}<>0, (C{$row}-B{$row})/ABS(B{$row}), 0)");
            $sheet->setCellValue('F' . $row, $winner);
            $sheet->setCellValue('G' . $row, $trend);

            $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($dataStyle);

            if ($isMoney) {
                $sheet->getStyle('B' . $row . ':D' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            } elseif ($isInt) {
                $sheet->getStyle('B' . $row . ':D' . $row)->getNumberFormat()->setFormatCode('#,##0');
            }
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('+0.0%;-0.0%;0.0%');

            if ($name === 'Total Ventas' || $name === 'Ganancia Estimada de Ventas') {
                $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($totalHighlight);
            }

            $row++;
        }
        $row += 2;

        // SECTION 2: DAILY SALES COMPARISON (1 AL 31)
        $sheet->setCellValue('A' . $row, '2. COMPARATIVA DE VENTAS DÍA A DÍA (DÍA 1 AL 31)');
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($sectionHeaderStyle);
        $sheet->getRowDimension($row)->setRowHeight(24);
        $row++;

        $sheet->setCellValue('A' . $row, 'DÍA');
        $sheet->setCellValue('B' . $row, 'TRANS. ' . strtoupper($labelA));
        $sheet->setCellValue('C' . $row, 'VENTAS ' . strtoupper($labelA));
        $sheet->setCellValue('D' . $row, 'TRANS. ' . strtoupper($labelB));
        $sheet->setCellValue('E' . $row, 'VENTAS ' . strtoupper($labelB));
        $sheet->setCellValue('F' . $row, 'DIF. VENTAS ($)');
        $sheet->setCellValue('G' . $row, '% CRECIMIENTO');
        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($subHeaderStyle);
        $sheet->getRowDimension($row)->setRowHeight(22);
        $row++;

        for ($d = 1; $d <= 31; $d++) {
            $tA = $dataA['dailySales'][$d]['count'] ?? 0;
            $vA = $dataA['dailySales'][$d]['total'] ?? 0;
            $tB = $dataB['dailySales'][$d]['count'] ?? 0;
            $vB = $dataB['dailySales'][$d]['total'] ?? 0;

            $sheet->setCellValue('A' . $row, "Día {$d}");
            $sheet->setCellValue('B' . $row, $tA);
            $sheet->setCellValue('C' . $row, $vA);
            $sheet->setCellValue('D' . $row, $tB);
            $sheet->setCellValue('E' . $row, $vB);
            $sheet->setCellValue('F' . $row, "=E{$row}-C{$row}");
            $sheet->setCellValue('G' . $row, "=IF(C{$row}<>0, (E{$row}-C{$row})/ABS(C{$row}), 0)");

            $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($dataStyle);
            $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('+0.0%;-0.0%;0.0%');
            $row++;
        }
        $row += 2;

        // SECTION 3: PAYMENT METHODS COMPARISON
        $sheet->setCellValue('A' . $row, '3. COMPARATIVA POR MÉTODO DE PAGO');
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($sectionHeaderStyle);
        $sheet->getRowDimension($row)->setRowHeight(24);
        $row++;

        $sheet->setCellValue('A' . $row, 'MÉTODO DE PAGO');
        $sheet->setCellValue('B' . $row, 'TRANS. ' . strtoupper($labelA));
        $sheet->setCellValue('C' . $row, 'TOTAL ' . strtoupper($labelA));
        $sheet->setCellValue('D' . $row, 'TRANS. ' . strtoupper($labelB));
        $sheet->setCellValue('E' . $row, 'TOTAL ' . strtoupper($labelB));
        $sheet->setCellValue('F' . $row, 'VARIACIÓN ($)');
        $sheet->setCellValue('G' . $row, 'VARIACIÓN (%)');
        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($subHeaderStyle);
        $sheet->getRowDimension($row)->setRowHeight(22);
        $row++;

        $allPmNames = array_unique(array_merge(array_keys($dataA['pmSales']), array_keys($dataB['pmSales'])));
        sort($allPmNames);

        foreach ($allPmNames as $pmName) {
            $tA = $dataA['pmSales'][$pmName]['count'] ?? 0;
            $vA = $dataA['pmSales'][$pmName]['total'] ?? 0;
            $tB = $dataB['pmSales'][$pmName]['count'] ?? 0;
            $vB = $dataB['pmSales'][$pmName]['total'] ?? 0;

            $sheet->setCellValue('A' . $row, $pmName);
            $sheet->setCellValue('B' . $row, $tA);
            $sheet->setCellValue('C' . $row, $vA);
            $sheet->setCellValue('D' . $row, $tB);
            $sheet->setCellValue('E' . $row, $vB);
            $sheet->setCellValue('F' . $row, "=E{$row}-C{$row}");
            $sheet->setCellValue('G' . $row, "=IF(C{$row}<>0, (E{$row}-C{$row})/ABS(C{$row}), 0)");

            $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($dataStyle);
            $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('+0.0%;-0.0%;0.0%');
            $row++;
        }
        $row += 2;

        // SECTION 4: SELLERS COMPARISON
        $sheet->setCellValue('A' . $row, '4. COMPARATIVA POR VENDEDOR');
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($sectionHeaderStyle);
        $sheet->getRowDimension($row)->setRowHeight(24);
        $row++;

        $sheet->setCellValue('A' . $row, 'VENDEDOR');
        $sheet->setCellValue('B' . $row, 'TRANS. ' . strtoupper($labelA));
        $sheet->setCellValue('C' . $row, 'TOTAL ' . strtoupper($labelA));
        $sheet->setCellValue('D' . $row, 'TRANS. ' . strtoupper($labelB));
        $sheet->setCellValue('E' . $row, 'TOTAL ' . strtoupper($labelB));
        $sheet->setCellValue('F' . $row, 'VARIACIÓN ($)');
        $sheet->setCellValue('G' . $row, 'VARIACIÓN (%)');
        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($subHeaderStyle);
        $sheet->getRowDimension($row)->setRowHeight(22);
        $row++;

        $allSellerNames = array_unique(array_merge(array_keys($dataA['sellerSales']), array_keys($dataB['sellerSales'])));
        sort($allSellerNames);

        foreach ($allSellerNames as $sName) {
            $tA = $dataA['sellerSales'][$sName]['count'] ?? 0;
            $vA = $dataA['sellerSales'][$sName]['total'] ?? 0;
            $tB = $dataB['sellerSales'][$sName]['count'] ?? 0;
            $vB = $dataB['sellerSales'][$sName]['total'] ?? 0;

            $sheet->setCellValue('A' . $row, $sName);
            $sheet->setCellValue('B' . $row, $tA);
            $sheet->setCellValue('C' . $row, $vA);
            $sheet->setCellValue('D' . $row, $tB);
            $sheet->setCellValue('E' . $row, $vB);
            $sheet->setCellValue('F' . $row, "=E{$row}-C{$row}");
            $sheet->setCellValue('G' . $row, "=IF(C{$row}<>0, (E{$row}-C{$row})/ABS(C{$row}), 0)");

            $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($dataStyle);
            $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('+0.0%;-0.0%;0.0%');
            $row++;
        }
        $row += 2;

        // SECTION 5: HOURLY SALES COMPARISON (00:00 A 23:00)
        $sheet->setCellValue('A' . $row, '5. COMPARATIVA POR FRANJA HORARIA (HORAS PICO)');
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($sectionHeaderStyle);
        $sheet->getRowDimension($row)->setRowHeight(24);
        $row++;

        $sheet->setCellValue('A' . $row, 'HORA');
        $sheet->setCellValue('B' . $row, 'TRANS. ' . strtoupper($labelA));
        $sheet->setCellValue('C' . $row, 'VENTAS ' . strtoupper($labelA));
        $sheet->setCellValue('D' . $row, 'TRANS. ' . strtoupper($labelB));
        $sheet->setCellValue('E' . $row, 'VENTAS ' . strtoupper($labelB));
        $sheet->setCellValue('F' . $row, 'DIFERENCIA ($)');
        $sheet->setCellValue('G' . $row, '% CRECIMIENTO');
        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($subHeaderStyle);
        $sheet->getRowDimension($row)->setRowHeight(22);
        $row++;

        for ($h = 0; $h <= 23; $h++) {
            $hLabel = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00 - ' . str_pad($h, 2, '0', STR_PAD_LEFT) . ':59';
            $tA = $dataA['hourlySales'][$h]['count'] ?? 0;
            $vA = $dataA['hourlySales'][$h]['total'] ?? 0;
            $tB = $dataB['hourlySales'][$h]['count'] ?? 0;
            $vB = $dataB['hourlySales'][$h]['total'] ?? 0;

            if ($tA == 0 && $tB == 0) continue; // skip completely empty hours

            $sheet->setCellValue('A' . $row, $hLabel);
            $sheet->setCellValue('B' . $row, $tA);
            $sheet->setCellValue('C' . $row, $vA);
            $sheet->setCellValue('D' . $row, $tB);
            $sheet->setCellValue('E' . $row, $vB);
            $sheet->setCellValue('F' . $row, "=E{$row}-C{$row}");
            $sheet->setCellValue('G' . $row, "=IF(C{$row}<>0, (E{$row}-C{$row})/ABS(C{$row}), 0)");

            $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($dataStyle);
            $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('+0.0%;-0.0%;0.0%');
            $row++;
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'ventas-versus-' . $startDateA . '-vs-' . $startDateB . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Export Refunds & Credit Notes report as Excel.
     * Two sheets:
     *   1. Resumen      — KPIs and totals by reason/branch
     *   2. Detalle      — All refunds + credit notes merged in chronological order
     */
    public function refundsExcel(Request $request)
    {
        $user = auth()->user();
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $branchId = $request->get('branch_id');
        $filterType = $request->get('filter_type', 'all');
        $search = $request->get('search', '');

        if (!$user->isSuperAdmin()) {
            $branchId = $user->branch_id;
        }

        $branchName = 'Todas las sucursales';
        if ($branchId) {
            $branchName = Branch::find($branchId)?->name ?? '—';
        }
        $typeLabel = match ($filterType) {
            'refund' => 'Solo Devoluciones POS',
            'credit_note' => 'Solo Notas Crédito',
            default => 'Devoluciones + Notas Crédito',
        };

        // Build merged collection
        $rows = collect();

        // Refunds
        if ($filterType !== 'credit_note') {
            $refundsQuery = \App\Models\Refund::with(['sale.customer', 'user', 'branch'])
                ->where('refunds.status', 'completed')
                ->whereDate('refunds.created_at', '>=', $startDate)
                ->whereDate('refunds.created_at', '<=', $endDate);
            if ($branchId) $refundsQuery->where('refunds.branch_id', $branchId);

            if ($search) {
                $refundsQuery->where(function ($q) use ($search) {
                    $q->where('refunds.number', 'like', "%{$search}%")
                      ->orWhereHas('sale', fn($sq) => $sq->where('invoice_number', 'like', "%{$search}%"));
                });
            }

            foreach ($refundsQuery->get() as $r) {
                $rows->push((object) [
                    'kind' => 'refund',
                    'kind_label' => 'Devolución',
                    'number' => $r->number,
                    'date' => $r->created_at,
                    'sale_invoice' => $r->sale?->invoice_number,
                    'customer_name' => $r->sale?->customer?->full_name,
                    'customer_doc' => $r->sale?->customer?->document_number,
                    'type' => $r->type,
                    'reason' => $r->reason,
                    'subtotal' => (float) $r->subtotal,
                    'tax_total' => (float) $r->tax_total,
                    'total' => (float) $r->total,
                    'user' => $r->user?->name,
                    'branch_name' => $r->branch?->name,
                    'status' => $r->status,
                ]);
            }
        }

        // Credit Notes
        if ($filterType !== 'refund') {
            $cnQuery = \App\Models\CreditNote::with(['sale.customer', 'user', 'branch'])
                ->whereIn('credit_notes.status', ['pending', 'validated'])
                ->whereDate('credit_notes.created_at', '>=', $startDate)
                ->whereDate('credit_notes.created_at', '<=', $endDate);
            if ($branchId) $cnQuery->where('credit_notes.branch_id', $branchId);

            if ($search) {
                $cnQuery->where(function ($q) use ($search) {
                    $q->where('credit_notes.number', 'like', "%{$search}%")
                      ->orWhere('credit_notes.dian_number', 'like', "%{$search}%")
                      ->orWhereHas('sale', fn($sq) => $sq->where('invoice_number', 'like', "%{$search}%"));
                });
            }

            foreach ($cnQuery->get() as $c) {
                $rows->push((object) [
                    'kind' => 'credit_note',
                    'kind_label' => 'Nota Crédito',
                    'number' => $c->dian_number ?? $c->number,
                    'date' => $c->created_at,
                    'sale_invoice' => $c->sale?->invoice_number,
                    'customer_name' => $c->sale?->customer?->full_name,
                    'customer_doc' => $c->sale?->customer?->document_number,
                    'type' => $c->type,
                    'reason' => $c->reason,
                    'subtotal' => (float) $c->subtotal,
                    'tax_total' => (float) $c->tax_total,
                    'total' => (float) $c->total,
                    'user' => $c->user?->name,
                    'branch_name' => $c->branch?->name,
                    'status' => $c->status,
                ]);
            }
        }

        $rows = $rows->sortByDesc('date')->values();

        // Aggregates
        $refunds = $rows->where('kind', 'refund');
        $creditNotes = $rows->where('kind', 'credit_note');
        $totalRefundsAmount = (float) $refunds->sum('total');
        $totalCreditNotesAmount = (float) $creditNotes->sum('total');
        $grandTotal = $totalRefundsAmount + $totalCreditNotesAmount;
        $partials = $rows->where('type', 'partial');
        $totals = $rows->where('type', 'total');

        // By reason
        $byReason = [];
        foreach ($rows as $row) {
            $reason = trim((string) ($row->reason ?? '')) ?: 'Sin razón';
            if (!isset($byReason[$reason])) $byReason[$reason] = ['amount' => 0, 'count' => 0];
            $byReason[$reason]['amount'] += $row->total;
            $byReason[$reason]['count']++;
        }
        uasort($byReason, fn($a, $b) => $b['amount'] <=> $a['amount']);

        // By branch
        $byBranch = [];
        foreach ($rows as $row) {
            $b = $row->branch_name ?: '—';
            if (!isset($byBranch[$b])) $byBranch[$b] = ['amount' => 0, 'count' => 0];
            $byBranch[$b]['amount'] += $row->total;
            $byBranch[$b]['count']++;
        }
        uasort($byBranch, fn($a, $b) => $b['amount'] <=> $a['amount']);

        // ============ Build spreadsheet ============
        $spreadsheet = new Spreadsheet();

        $titleStyle = [
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '1E293B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        $subtitleStyle = ['font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'EA580C']]];
        $tableHeaderStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EA580C']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'C2410C']]],
        ];
        $cellBorderStyle = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
        ];

        // ---- Sheet 1: Resumen ----
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Resumen');

        $row = 1;
        $sheet->setCellValue('A' . $row, 'REPORTE DE DEVOLUCIONES Y NOTAS CRÉDITO');
        $sheet->mergeCells('A' . $row . ':E' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray($titleStyle);
        $sheet->getRowDimension($row)->setRowHeight(28);
        $row += 2;

        $info = [
            ['Período:', $startDate . ' a ' . $endDate],
            ['Sucursal:', $branchName],
            ['Tipo:', $typeLabel],
            ['Búsqueda:', $search ?: '—'],
            ['Generado:', now()->format('d/m/Y H:i')],
        ];
        foreach ($info as $i) {
            $sheet->setCellValue('A' . $row, $i[0]);
            $sheet->setCellValue('B' . $row, $i[1]);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
        }
        $row++;

        $sheet->setCellValue('A' . $row, 'TOTALES');
        $sheet->getStyle('A' . $row)->applyFromArray($subtitleStyle);
        $row++;

        $kpis = [
            ['Total devuelto', $grandTotal, 'EA580C'],
            ['Devoluciones POS (cantidad)', $refunds->count(), null],
            ['Devoluciones POS (monto)', $totalRefundsAmount, '2563EB'],
            ['Notas Crédito (cantidad)', $creditNotes->count(), null],
            ['Notas Crédito (monto)', $totalCreditNotesAmount, '7C3AED'],
            ['Devoluciones totales (cantidad)', $totals->count(), null],
            ['Devoluciones totales (monto)', (float) $totals->sum('total'), 'DC2626'],
            ['Devoluciones parciales (cantidad)', $partials->count(), null],
            ['Devoluciones parciales (monto)', (float) $partials->sum('total'), 'F59E0B'],
        ];
        foreach ($kpis as $kpi) {
            $sheet->setCellValue('A' . $row, $kpi[0]);
            $sheet->setCellValue('B' . $row, $kpi[1]);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            if (str_contains($kpi[0], 'monto') || $kpi[0] === 'Total devuelto') {
                $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
                if ($kpi[2]) {
                    $sheet->getStyle('B' . $row)->getFont()->setColor(new Color($kpi[2]))->setBold(true);
                }
            } else {
                $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0');
            }
            $row++;
        }
        $row += 2;

        // By reason
        if (!empty($byReason)) {
            $sheet->setCellValue('A' . $row, 'POR RAZÓN');
            $sheet->getStyle('A' . $row)->applyFromArray($subtitleStyle);
            $row++;

            $headers = ['Razón', 'Cantidad', 'Monto'];
            $col = 'A';
            foreach ($headers as $h) {
                $sheet->setCellValue($col . $row, $h);
                $col++;
            }
            $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray($tableHeaderStyle);
            $row++;
            foreach ($byReason as $reason => $data) {
                $sheet->setCellValue('A' . $row, $reason);
                $sheet->setCellValue('B' . $row, $data['count']);
                $sheet->setCellValue('C' . $row, $data['amount']);
                $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
                $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray($cellBorderStyle);
                $row++;
            }
            $row += 2;
        }

        // By branch (only if super admin or multi-branch data)
        if (!empty($byBranch) && count($byBranch) > 1) {
            $sheet->setCellValue('A' . $row, 'POR SUCURSAL');
            $sheet->getStyle('A' . $row)->applyFromArray($subtitleStyle);
            $row++;

            $headers = ['Sucursal', 'Cantidad', 'Monto'];
            $col = 'A';
            foreach ($headers as $h) {
                $sheet->setCellValue($col . $row, $h);
                $col++;
            }
            $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray($tableHeaderStyle);
            $row++;
            foreach ($byBranch as $branch => $data) {
                $sheet->setCellValue('A' . $row, $branch);
                $sheet->setCellValue('B' . $row, $data['count']);
                $sheet->setCellValue('C' . $row, $data['amount']);
                $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
                $sheet->getStyle('A' . $row . ':C' . $row)->applyFromArray($cellBorderStyle);
                $row++;
            }
        }

        foreach (['A', 'B', 'C', 'D', 'E'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ---- Sheet 2: Detalle ----
        $detSheet = $spreadsheet->createSheet();
        $detSheet->setTitle('Detalle');

        $row = 1;
        $detSheet->setCellValue('A' . $row, 'DETALLE DE DEVOLUCIONES Y NOTAS CRÉDITO');
        $detSheet->mergeCells('A' . $row . ':L' . $row);
        $detSheet->getStyle('A' . $row)->applyFromArray($titleStyle);
        $detSheet->getRowDimension($row)->setRowHeight(28);
        $row += 2;

        $headers = [
            'Fecha', 'Tipo', 'N° Doc.', 'Factura origen', 'Cliente', 'Documento',
            'Alcance', 'Razón', 'Sucursal', 'Usuario', 'Subtotal', 'IVA', 'Total',
        ];
        $col = 'A';
        foreach ($headers as $h) {
            $detSheet->setCellValue($col . $row, $h);
            $col++;
        }
        $detSheet->getStyle('A' . $row . ':M' . $row)->applyFromArray($tableHeaderStyle);
        $detSheet->getRowDimension($row)->setRowHeight(22);
        $row++;

        if ($rows->isEmpty()) {
            $detSheet->setCellValue('A' . $row, 'Sin devoluciones en el período seleccionado.');
            $detSheet->mergeCells('A' . $row . ':M' . $row);
            $detSheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $detSheet->getStyle('A' . $row)->getFont()->setItalic(true);
        } else {
            foreach ($rows as $r) {
                $detSheet->setCellValue('A' . $row, $r->date->format('d/m/Y H:i'));
                $detSheet->setCellValue('B' . $row, $r->kind_label);
                $detSheet->setCellValue('C' . $row, $r->number);
                $detSheet->setCellValue('D' . $row, $r->sale_invoice ?? '—');
                $detSheet->setCellValue('E' . $row, $r->customer_name ?? '—');
                $detSheet->setCellValue('F' . $row, $r->customer_doc ?? '—');
                $detSheet->setCellValue('G' . $row, $r->type === 'total' ? 'Total' : 'Parcial');
                $detSheet->setCellValue('H' . $row, $r->reason ?? '—');
                $detSheet->setCellValue('I' . $row, $r->branch_name ?? '—');
                $detSheet->setCellValue('J' . $row, $r->user ?? '—');
                $detSheet->setCellValue('K' . $row, (float) $r->subtotal);
                $detSheet->setCellValue('L' . $row, (float) $r->tax_total);
                $detSheet->setCellValue('M' . $row, (float) $r->total);

                $detSheet->getStyle('K' . $row . ':M' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');

                if ($r->kind === 'refund') {
                    $detSheet->getStyle('B' . $row)->getFont()->setColor(new Color('2563EB'))->setBold(true);
                } else {
                    $detSheet->getStyle('B' . $row)->getFont()->setColor(new Color('7C3AED'))->setBold(true);
                }
                if ($r->type === 'total') {
                    $detSheet->getStyle('G' . $row)->getFont()->setColor(new Color('DC2626'))->setBold(true);
                } else {
                    $detSheet->getStyle('G' . $row)->getFont()->setColor(new Color('F59E0B'))->setBold(true);
                }

                $detSheet->getStyle('A' . $row . ':M' . $row)->applyFromArray($cellBorderStyle);
                $row++;
            }

            // Totals row
            $detSheet->setCellValue('A' . $row, 'TOTAL');
            $detSheet->mergeCells('A' . $row . ':L' . $row);
            $detSheet->setCellValue('M' . $row, $grandTotal);
            $detSheet->getStyle('A' . $row . ':M' . $row)->getFont()->setBold(true);
            $detSheet->getStyle('A' . $row . ':M' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FED7AA');
            $detSheet->getStyle('M' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            $detSheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M'] as $col) {
            $detSheet->getColumnDimension($col)->setAutoSize(true);
        }
        $detSheet->freezePane('A4');

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);
        $filename = 'devoluciones-' . now()->format('Y-m-d-His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'refunds');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Export Kardex (inventory) report as a multi-sheet Excel workbook.
     *
     * Sheets:
     *  1. Resumen        — KPIs, distribución por estado de stock, agrupado por categoría
     *  2. Inventario     — Lista completa de productos con todas las columnas
     *  3. Mov. Inventario — Movimientos de inventario en el período (entrada/salida)
     */
    public function kardexExcel(Request $request)
    {
        $user = auth()->user();
        $branchId = $request->get('branch_id');
        $categoryId = $request->get('category_id');
        $brandId = $request->get('brand_id');
        $stockFilter = $request->get('stock_filter', 'all');
        $search = $request->get('search', '');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        // Force branch for non-super admin
        if (!$user->isSuperAdmin()) {
            $branchId = $user->branch_id;
        }

        // Resolve display labels
        $branchName = 'Todas las sucursales';
        if ($branchId) {
            $branchName = Branch::find($branchId)?->name ?? '—';
        }
        $categoryName = $categoryId ? (Category::find($categoryId)?->name ?? '—') : 'Todas las categorías';
        $brandName = $brandId ? (Brand::find($brandId)?->name ?? '—') : 'Todas las marcas';
        $stockLabel = match ($stockFilter) {
            'positive' => 'Solo con stock',
            'zero' => 'Solo sin stock',
            'negative' => 'Solo stock negativo',
            default => 'Todos',
        };

        // ============= Build product query =============
        $productsQuery = Product::query()
            ->with(['category', 'brand', 'unit', 'branch', 'locations'])
            ->where('is_active', true);

        if ($branchId) {
            $productsQuery->where('branch_id', $branchId);
        }
        if ($categoryId) {
            $productsQuery->where('category_id', $categoryId);
        }
        if ($brandId) {
            $productsQuery->where('brand_id', $brandId);
        }
        if ($search) {
            $productsQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }
        switch ($stockFilter) {
            case 'positive':
                $productsQuery->where('current_stock', '>', 0);
                break;
            case 'zero':
                $productsQuery->where('current_stock', 0);
                break;
            case 'negative':
                $productsQuery->where('current_stock', '<', 0);
                break;
        }

        $products = $productsQuery->orderBy('name')->get();

        // ============= Aggregate stats =============
        $totalProducts = $products->count();
        $productsWithStock = $products->where('current_stock', '>', 0)->count();
        $productsZeroStock = $products->where('current_stock', 0)->count();
        $productsNegativeStock = $products->where('current_stock', '<', 0)->count();

        $totalInventoryValue = (float) $products->where('current_stock', '>', 0)
            ->sum(fn($p) => (float) $p->current_stock * (float) $p->sale_price);
        $totalInventoryCost = (float) $products->where('current_stock', '>', 0)
            ->sum(fn($p) => (float) $p->current_stock * (float) ($p->average_cost > 0 ? $p->average_cost : $p->purchase_price));
        $totalPotentialProfit = $totalInventoryValue - $totalInventoryCost;

        // Group by category
        $byCategory = $products->groupBy(fn($p) => $p->category?->name ?? 'Sin categoría')
            ->map(function ($group) {
                $totalStock = (float) $group->sum('current_stock');
                $totalValue = (float) $group->sum(fn($p) => (float) $p->current_stock * (float) $p->sale_price);
                $totalCost = (float) $group->sum(fn($p) => (float) $p->current_stock * (float) ($p->average_cost > 0 ? $p->average_cost : $p->purchase_price));
                return [
                    'count' => $group->count(),
                    'stock' => $totalStock,
                    'value' => $totalValue,
                    'cost' => $totalCost,
                    'profit' => $totalValue - $totalCost,
                ];
            });

        // ============= Inventory movements =============
        $productIds = $products->pluck('id');

        $movementsQuery = \App\Models\InventoryMovement::query()
            ->whereIn('product_id', $productIds)
            ->with(['product', 'systemDocument', 'user', 'branch']);

        if ($dateFrom) {
            $movementsQuery->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $movementsQuery->whereDate('created_at', '<=', $dateTo);
        }

        $movements = $movementsQuery->orderBy('created_at')->get();

        // ============= Build spreadsheet =============
        $spreadsheet = new Spreadsheet();

        // ---- Common styles ----
        $titleStyle = [
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => '1E293B']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        $subtitleStyle = ['font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'A855F7']]];
        $tableHeaderStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'A855F7']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '9333EA']]],
        ];
        $cellBorderStyle = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
        ];

        // ============================================================
        // SHEET 1 — Resumen
        // ============================================================
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Resumen');

        $row = 1;
        $sheet->setCellValue('A' . $row, 'REPORTE KARDEX — RESUMEN');
        $sheet->mergeCells('A' . $row . ':F' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray($titleStyle);
        $sheet->getRowDimension($row)->setRowHeight(28);
        $row += 2;

        // Filters info
        $filterInfo = [
            ['Sucursal:', $branchName],
            ['Categoría:', $categoryName],
            ['Marca:', $brandName],
            ['Filtro stock:', $stockLabel],
            ['Búsqueda:', $search ?: '—'],
            ['Período movimientos:', ($dateFrom ?: '—') . ' a ' . ($dateTo ?: '—')],
            ['Generado:', now()->format('d/m/Y H:i')],
        ];
        foreach ($filterInfo as $info) {
            $sheet->setCellValue('A' . $row, $info[0]);
            $sheet->setCellValue('B' . $row, $info[1]);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
        }
        $row++;

        // KPIs
        $sheet->setCellValue('A' . $row, 'INDICADORES');
        $sheet->getStyle('A' . $row)->applyFromArray($subtitleStyle);
        $row++;

        $kpis = [
            ['Total productos', $totalProducts, '0', '4472C4'],
            ['Con existencias', $productsWithStock, '0', '22C55E'],
            ['Sin existencias', $productsZeroStock, '0', 'F59E0B'],
            ['Stock negativo', $productsNegativeStock, '0', 'EF4444'],
            ['Valor inventario (precio venta)', $totalInventoryValue, '$#,##0.00', '70AD47'],
            ['Costo inventario (precio compra)', $totalInventoryCost, '$#,##0.00', 'ED7D31'],
            ['Utilidad potencial', $totalPotentialProfit, '$#,##0.00', $totalPotentialProfit >= 0 ? '22C55E' : 'EF4444'],
        ];
        foreach ($kpis as $kpi) {
            $sheet->setCellValue('A' . $row, $kpi[0]);
            $sheet->setCellValue('B' . $row, $kpi[1]);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode($kpi[2]);
            $sheet->getStyle('B' . $row)->getFont()->setColor(new Color($kpi[3]))->setBold(true);
            $row++;
        }
        $row += 2;

        // By category
        if ($byCategory->isNotEmpty()) {
            $sheet->setCellValue('A' . $row, 'INVENTARIO POR CATEGORÍA');
            $sheet->getStyle('A' . $row)->applyFromArray($subtitleStyle);
            $row++;

            $catHeaders = ['Categoría', 'Productos', 'Stock total', 'Valor (venta)', 'Costo (compra)', 'Utilidad pot.'];
            $col = 'A';
            foreach ($catHeaders as $h) {
                $sheet->setCellValue($col . $row, $h);
                $col++;
            }
            $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray($tableHeaderStyle);
            $row++;

            foreach ($byCategory as $catName => $data) {
                $sheet->setCellValue('A' . $row, $catName);
                $sheet->setCellValue('B' . $row, $data['count']);
                $sheet->setCellValue('C' . $row, $data['stock']);
                $sheet->setCellValue('D' . $row, $data['value']);
                $sheet->setCellValue('E' . $row, $data['cost']);
                $sheet->setCellValue('F' . $row, $data['profit']);
                $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0.000');
                $sheet->getStyle('D' . $row . ':F' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
                $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray($cellBorderStyle);
                $row++;
            }
            $row += 2;
        }

        // Set column widths for sheet 1
        foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $colLetter) {
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // ============================================================
        // SHEET 2 — Inventario (lista de productos)
        // ============================================================
        $invSheet = $spreadsheet->createSheet();
        $invSheet->setTitle('Inventario');

        $row = 1;
        $invSheet->setCellValue('A' . $row, 'INVENTARIO DETALLADO');
        $invSheet->mergeCells('A' . $row . ':M' . $row);
        $invSheet->getStyle('A' . $row)->applyFromArray($titleStyle);
        $invSheet->getRowDimension($row)->setRowHeight(28);
        $row += 2;

        $invHeaders = [
            'SKU', 'Producto', 'Categoría', 'Marca', 'Sucursal', 'Unidad',
            'Stock actual', 'Ubicaciones', 'Stock mín.', 'Precio compra', 'Precio venta',
            'Valor inv.', 'Costo inv.',
        ];
        $col = 'A';
        foreach ($invHeaders as $h) {
            $invSheet->setCellValue($col . $row, $h);
            $col++;
        }
        $invSheet->getStyle('A' . $row . ':M' . $row)->applyFromArray($tableHeaderStyle);
        $invSheet->getRowDimension($row)->setRowHeight(22);
        $row++;

        foreach ($products as $p) {
            $stock = (float) $p->current_stock;
            $value = $stock > 0 ? $stock * (float) $p->sale_price : 0;
            $cost = $stock > 0 ? $stock * (float) ($p->average_cost > 0 ? $p->average_cost : $p->purchase_price) : 0;

            $invSheet->setCellValue('A' . $row, $p->sku);
            $invSheet->setCellValue('B' . $row, $p->name);
            $invSheet->setCellValue('C' . $row, $p->category?->name ?? '—');
            $invSheet->setCellValue('D' . $row, $p->brand?->name ?? '—');
            $invSheet->setCellValue('E' . $row, $p->branch?->name ?? '—');
            $invSheet->setCellValue('F' . $row, $p->unit?->abbreviation ?? '—');
            $locationsText = collect($p->locations ?? [])->map(fn($l) => $l->name . ': ' . $l->pivot->quantity)->implode(', ');

            $invSheet->setCellValue('G' . $row, $stock);
            $invSheet->setCellValue('H' . $row, $locationsText);
            $invSheet->setCellValue('I' . $row, (float) $p->min_stock);
            $invSheet->setCellValue('J' . $row, (float) $p->average_cost);
            $invSheet->setCellValue('K' . $row, (float) $p->sale_price);
            $invSheet->setCellValue('L' . $row, $value);
            $invSheet->setCellValue('M' . $row, $cost);

            // Number formats
            $invSheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0.000');
            $invSheet->getStyle('I' . $row)->getNumberFormat()->setFormatCode('#,##0.000');
            $invSheet->getStyle('J' . $row . ':M' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');

            // Highlight rows with stock issues
            if ($stock < 0) {
                $invSheet->getStyle('A' . $row . ':M' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FEE2E2');
            } elseif ($stock == 0) {
                $invSheet->getStyle('A' . $row . ':M' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FEF3C7');
            } elseif ($p->min_stock > 0 && $stock <= $p->min_stock) {
                $invSheet->getStyle('A' . $row . ':M' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFEDD5');
            }

            $invSheet->getStyle('A' . $row . ':M' . $row)->applyFromArray($cellBorderStyle);
            $row++;
        }

        // Totals row
        if ($products->count() > 0) {
            $invSheet->setCellValue('A' . $row, 'TOTALES');
            $invSheet->mergeCells('A' . $row . ':K' . $row);
            $invSheet->setCellValue('L' . $row, $totalInventoryValue);
            $invSheet->setCellValue('M' . $row, $totalInventoryCost);
            $invSheet->getStyle('A' . $row . ':M' . $row)->getFont()->setBold(true);
            $invSheet->getStyle('A' . $row . ':M' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F1F5F9');
            $invSheet->getStyle('L' . $row . ':M' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            $invSheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M'] as $colLetter) {
            $invSheet->getColumnDimension($colLetter)->setAutoSize(true);
        }
        $invSheet->freezePane('A4');

        // ============================================================
        // SHEET 3 — Movimientos
        // ============================================================
        $movSheet = $spreadsheet->createSheet();
        $movSheet->setTitle('Movimientos');

        $row = 1;
        $movSheet->setCellValue('A' . $row, 'MOVIMIENTOS DE INVENTARIO');
        $movSheet->mergeCells('A' . $row . ':L' . $row);
        $movSheet->getStyle('A' . $row)->applyFromArray($titleStyle);
        $movSheet->getRowDimension($row)->setRowHeight(28);
        $row++;
        if ($dateFrom || $dateTo) {
            $movSheet->setCellValue('A' . $row, 'Período: ' . ($dateFrom ?: '—') . ' a ' . ($dateTo ?: '—'));
            $movSheet->mergeCells('A' . $row . ':L' . $row);
            $movSheet->getStyle('A' . $row)->getFont()->setItalic(true)->setSize(10);
            $movSheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        }
        $row++;

        $movHeaders = [
            'Fecha', 'SKU', 'Producto', 'Sucursal', 'Documento', 'N° doc.',
            'Tipo', 'Cantidad', 'Stock antes', 'Stock después',
            'Costo unit.', 'Costo total',
        ];
        $col = 'A';
        foreach ($movHeaders as $h) {
            $movSheet->setCellValue($col . $row, $h);
            $col++;
        }
        $movSheet->getStyle('A' . $row . ':L' . $row)->applyFromArray($tableHeaderStyle);
        $movSheet->getRowDimension($row)->setRowHeight(22);
        $row++;

        if ($movements->isEmpty()) {
            $movSheet->setCellValue('A' . $row, 'No hay movimientos en el período seleccionado.');
            $movSheet->mergeCells('A' . $row . ':L' . $row);
            $movSheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $movSheet->getStyle('A' . $row)->getFont()->setItalic(true);
        } else {
            foreach ($movements as $m) {
                $movSheet->setCellValue('A' . $row, $m->created_at->format('d/m/Y H:i'));
                $movSheet->setCellValue('B' . $row, $m->product?->sku ?? '—');
                $movSheet->setCellValue('C' . $row, $m->product?->name ?? '—');
                $movSheet->setCellValue('D' . $row, $m->branch?->name ?? '—');
                $movSheet->setCellValue('E' . $row, $m->systemDocument?->name ?? 'N/A');
                $movSheet->setCellValue('F' . $row, $m->document_number ?? '—');
                $movSheet->setCellValue('G' . $row, $m->movement_type === 'in' ? 'Entrada' : 'Salida');
                $movSheet->setCellValue('H' . $row, (float) $m->quantity);
                $movSheet->setCellValue('I' . $row, (float) $m->stock_before);
                $movSheet->setCellValue('J' . $row, (float) $m->stock_after);
                $movSheet->setCellValue('K' . $row, (float) $m->unit_cost);
                $movSheet->setCellValue('L' . $row, (float) $m->total_cost);

                $movSheet->getStyle('H' . $row . ':J' . $row)->getNumberFormat()->setFormatCode('#,##0.000');
                $movSheet->getStyle('K' . $row . ':L' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');

                if ($m->movement_type === 'in') {
                    $movSheet->getStyle('G' . $row)->getFont()->setColor(new Color('22C55E'))->setBold(true);
                } else {
                    $movSheet->getStyle('G' . $row)->getFont()->setColor(new Color('EF4444'))->setBold(true);
                }

                $movSheet->getStyle('A' . $row . ':L' . $row)->applyFromArray($cellBorderStyle);
                $row++;
            }
        }

        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L'] as $colLetter) {
            $movSheet->getColumnDimension($colLetter)->setAutoSize(true);
        }
        $movSheet->freezePane('A4');

        // Reset active sheet to first one
        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);
        $filename = 'kardex-' . now()->format('Y-m-d-His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'kardex');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function purchasesExcel(Request $request)
    {
        $user = auth()->user();
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $branchId = $request->get('branch_id');
        $supplierId = $request->get('supplier_id');
        $paymentType = $request->get('payment_type');
        $paymentStatus = $request->get('payment_status');
        $search = $request->get('search', '');

        if (!$user->isSuperAdmin()) {
            $branchId = $user->branch_id;
        }

        $query = \App\Models\Purchase::with([
            'branch',
            'supplier',
            'user',
            'paymentMethod',
            'partialPaymentMethod',
            'items.product'
        ])->where('status', 'completed');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }
        if ($paymentType) {
            $query->where('payment_type', $paymentType);
        }
        if ($paymentStatus) {
            $query->where('payment_status', $paymentStatus);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('purchase_number', 'like', "%{$search}%")
                  ->orWhereHas('supplier', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $purchases = $query->orderBy('created_at', 'desc')->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Compras');

        $headers = [
            'Fecha',
            'Hora',
            'Número de Compra',
            'Factura Proveedor',
            'Sucursal',
            'Proveedor',
            'Usuario',
            'Estado Pago',
            'Tipo Compra',
            'Medio de Pago',
            'Subtotal',
            'Impuestos',
            'Descuento',
            'Total',
            'Pagado',
            'Pendiente'
        ];

        $sheet->fromArray([$headers], NULL, 'A1');
        
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
        ];
        $sheet->getStyle('A1:P1')->applyFromArray($headerStyle);

        $row = 2;
        foreach ($purchases as $purchase) {
            $paymentMethodName = $purchase->paymentMethod ? $purchase->paymentMethod->name : 'N/A';
            if ($purchase->payment_type === 'credit' && $purchase->paid_amount > 0 && $purchase->partialPaymentMethod) {
                 $paymentMethodName = 'Crédito (' . $purchase->partialPaymentMethod->name . ' abono)';
            }
            $sheet->setCellValue('A' . $row, $purchase->created_at->format('Y-m-d'));
            $sheet->setCellValue('B' . $row, $purchase->created_at->format('H:i:s'));
            $sheet->setCellValue('C' . $row, $purchase->purchase_number);
            $sheet->setCellValue('D' . $row, $purchase->supplier_invoice ?? 'N/A');
            $sheet->setCellValue('E' . $row, $purchase->branch->name ?? 'N/A');
            $sheet->setCellValue('F' . $row, $purchase->supplier->name ?? 'N/A');
            $sheet->setCellValue('G' . $row, $purchase->user->name ?? 'N/A');
            $sheet->setCellValue('H' . $row, $purchase->getPaymentStatusLabel());
            $sheet->setCellValue('I' . $row, $purchase->getPaymentTypeLabel());
            $sheet->setCellValue('J' . $row, $paymentMethodName);
            $sheet->setCellValue('K' . $row, $purchase->subtotal);
            $sheet->setCellValue('L' . $row, $purchase->tax_amount);
            $sheet->setCellValue('M' . $row, $purchase->discount_amount);
            $sheet->setCellValue('N' . $row, $purchase->total);
            $sheet->setCellValue('O' . $row, $purchase->paid_amount);
            $sheet->setCellValue('P' . $row, $purchase->credit_amount - $purchase->paid_amount);

            $sheet->getStyle("K$row:P$row")->getNumberFormat()->setFormatCode('#,##0.00');
            $row++;
        }
        
        foreach (range('A', 'P') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Sheet 2: Detalles de Compras (Items)
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Detalles de Artículos');
        $headers2 = [
            'Fecha',
            'Número Compra',
            'Proveedor',
            'Producto',
            'SKU',
            'Cantidad',
            'Costo Unitario',
            'Impuesto',
            'Subtotal'
        ];
        $sheet2->fromArray([$headers2], NULL, 'A1');
        $sheet2->getStyle('A1:I1')->applyFromArray($headerStyle);
        $row2 = 2;
        foreach ($purchases as $purchase) {
            foreach ($purchase->items as $item) {
                $sheet2->setCellValue('A' . $row2, $purchase->created_at->format('Y-m-d'));
                $sheet2->setCellValue('B' . $row2, $purchase->purchase_number);
                $sheet2->setCellValue('C' . $row2, $purchase->supplier->name ?? 'N/A');
                $sheet2->setCellValue('D' . $row2, $item->product->name ?? 'N/A');
                $sheet2->setCellValue('E' . $row2, $item->product->sku ?? 'N/A');
                $sheet2->setCellValue('F' . $row2, $item->quantity);
                $sheet2->setCellValue('G' . $row2, $item->unit_cost);
                $sheet2->setCellValue('H' . $row2, $item->tax_amount);
                $sheet2->setCellValue('I' . $row2, $item->subtotal);
                
                $sheet2->getStyle("F$row2:I$row2")->getNumberFormat()->setFormatCode('#,##0.00');
                $row2++;
            }
        }
        foreach (range('A', 'I') as $col) {
            $sheet2->getColumnDimension($col)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'Reporte_Compras_' . now()->format('Ymd_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Generate and download the PDF Catalog of store products.
     */
    public function ecommerceCatalogPdf(Request $request)
    {
        @ini_set('memory_limit', '512M');
        @ini_set('max_execution_time', '300');
        @set_time_limit(300);

        try {
            // 0. Ensure fonts cache directory exists
            $fontDir = storage_path('fonts');
            if (!is_dir($fontDir)) {
                @mkdir($fontDir, 0775, true);
            }

            // 1. Resolve Ecommerce Branch
            $branchId = $request->get('branch_id') ?: Branch::getEcommerceBranchId();
            $branch = null;
            if ($branchId) {
                $branch = Branch::with(['department', 'municipality'])->find($branchId);
            }
            if (!$branch) {
                $branch = Branch::getEcommerceBranch();
                if ($branch) {
                    $branch->load(['department', 'municipality']);
                }
            }

            // 2. Base64 encode branch logo if available
            $branchLogoBase64 = null;
            if ($branch && $branch->logo) {
                $branchLogoBase64 = $this->safeImageToBase64($branch->logo);
            }

            // 3. Query active products for shop
            $query = Product::query()
                ->where('is_active', true)
                ->where('show_in_shop', true)
                ->where(function ($q) {
                    $q->where('manages_inventory', false)
                      ->orWhere('current_stock', '>', 0);
                });

            if ($branch) {
                $query->where('branch_id', $branch->id);
            }

            // Filters if provided
            if ($request->filled('category_id')) {
                $query->where('category_id', $request->get('category_id'));
            }
            if ($request->filled('brand_id')) {
                $query->where('brand_id', $request->get('brand_id'));
            }
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('sku', 'like', '%' . $search . '%')
                      ->orWhere('description', 'like', '%' . $search . '%');
                });
            }

            $rawProducts = $query->with([
                'category',
                'brand',
                'unit',
                'tax',
                'activeChildren' => function ($q) {
                    $q->where('show_in_shop', true);
                },
                'activeChildren.presentation',
                'activeChildren.color',
                'activeChildren.productModel',
            ])
            ->orderBy('category_id')
            ->orderBy('name')
            ->get();

            // 4. Structure products grouped by category with safe base64 images & calculated prices
            $categorizedProducts = [];
            $totalProducts = 0;

            foreach ($rawProducts as $product) {
                $catName = $product->category ? $product->category->name : 'General';
                if (!isset($categorizedProducts[$catName])) {
                    $categorizedProducts[$catName] = [];
                }

                // Convert product image to base64 safely
                $imageBase64 = null;
                if (!empty($product->image)) {
                    $imageBase64 = $this->safeImageToBase64($product->image);
                }

                // Tax label
                $taxRate = $product->tax ? (float) $product->tax->value : 0;
                $taxLabel = $taxRate > 0 ? 'IVA ' . rtrim(rtrim(number_format($taxRate, 2), '0'), '.') . '%' : 'Exento';

                // Variants list
                $variants = [];
                if ($product->activeChildren && $product->activeChildren->count() > 0) {
                    foreach ($product->activeChildren as $child) {
                        try {
                            $child->setRelation('product', $product);
                            $variants[] = [
                                'name' => $child->full_name ?: $child->name,
                                'price' => (float) $child->getSalePriceWithTax(),
                                'sku' => $child->sku,
                            ];
                        } catch (\Throwable $e) {
                            // ignore individual variant calculation errors
                        }
                    }
                }

                try {
                    $priceWithTax = (float) $product->getSalePriceWithTax();
                } catch (\Throwable $e) {
                    $priceWithTax = (float) ($product->sale_price ?? 0);
                }

                try {
                    $suggestedPrice = (float) $product->getSuggestedPriceWithTax();
                } catch (\Throwable $e) {
                    $suggestedPrice = (float) ($product->suggested_price ?? 0);
                }

                $categorizedProducts[$catName][] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'description' => $product->description,
                    'brand_name' => $product->brand?->name,
                    'unit_name' => $product->unit?->name,
                    'price_with_tax' => $priceWithTax,
                    'suggested_price' => $suggestedPrice,
                    'tax_label' => $taxLabel,
                    'manages_inventory' => (bool) $product->manages_inventory,
                    'current_stock' => (float) $product->current_stock,
                    'image_base64' => $imageBase64,
                    'variants' => $variants,
                ];

                $totalProducts++;
            }

            $data = [
                'branch' => $branch,
                'branchLogoBase64' => $branchLogoBase64,
                'categorizedProducts' => $categorizedProducts,
                'totalProducts' => $totalProducts,
                'totalCategories' => count($categorizedProducts),
                'currencySymbol' => '$',
                'showStockInShop' => $branch ? (bool) $branch->show_stock_in_shop : false,
                'generatedDate' => now()->translatedFormat('d \d\e F \d\e Y, h:i A'),
            ];

            $pdf = Pdf::loadView('reports.ecommerce-catalog-pdf', $data);
            $pdf->setPaper('letter', 'portrait');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'isPhpEnabled' => true,
                'defaultFont' => 'Helvetica',
                'dpi' => 96,
                'tempDir' => storage_path('framework/cache'),
                'fontDir' => storage_path('fonts'),
                'fontCache' => storage_path('fonts'),
                'chroot' => [public_path(), storage_path('app/public'), storage_path('app'), base_path()],
            ]);

            $branchSlug = \Illuminate\Support\Str::slug($branch?->name ?? 'tienda');
            $filename = 'catalogo-productos-' . $branchSlug . '-' . now()->format('Y-m-d') . '.pdf';

            return $pdf->download($filename);

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error generando catálogo PDF: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            $isAjax = $request->expectsJson() 
                || $request->ajax() 
                || $request->header('X-Requested-With') === 'XMLHttpRequest'
                || $request->get('format') === 'json';

            $diagnostics = [
                'php_version' => PHP_VERSION,
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'storage_fonts_writable' => is_writable(storage_path('fonts')),
                'storage_public_exists' => is_dir(storage_path('app/public')),
            ];

            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al generar el catálogo PDF: ' . $e->getMessage(),
                    'error' => [
                        'class' => get_class($e),
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                    ],
                    'diagnostics' => $diagnostics,
                ], 500);
            }

            // If normal browser navigation and user is authenticated
            if (auth()->check()) {
                return response()->view('errors.catalog-pdf-error', [
                    'exception' => $e,
                    'diagnostics' => $diagnostics,
                    'branch' => $branch ?? null,
                ], 500);
            }

            // Fallback for unauthenticated
            return response()->view('errors.catalog-pdf-error', [
                'exception' => $e,
                'diagnostics' => $diagnostics,
                'branch' => $branch ?? null,
            ], 500);
        }
    }

    /**
     * Safely convert and downscale an image to a compact JPEG base64 thumbnail (max 600x600px)
     * to prevent DomPDF memory explosion and WebP incompatibility on servers.
     */
    protected function safeImageToBase64(?string $imagePath, int $maxDimension = 600): ?string
    {
        if (empty($imagePath)) {
            return null;
        }

        try {
            if (str_starts_with($imagePath, 'data:image')) {
                // If it's already a webp data URI and imagecreatefromwebp doesn't exist, ignore it
                if (str_starts_with($imagePath, 'data:image/webp') && !function_exists('imagecreatefromwebp')) {
                    return null;
                }
                return $imagePath;
            }

            $rawContent = null;

            if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
                $ctx = stream_context_create(['http' => ['timeout' => 2]]);
                $rawContent = @file_get_contents($imagePath, false, $ctx);
            } else {
                $cleanPath = ltrim($imagePath, '/\\');
                if (str_starts_with($cleanPath, 'storage/')) {
                    $cleanPath = substr($cleanPath, 8);
                }
                if (str_starts_with($cleanPath, 'public/')) {
                    $cleanPath = substr($cleanPath, 7);
                }

                $storageDisk = \Illuminate\Support\Facades\Storage::disk('public');
                if ($storageDisk->exists($cleanPath)) {
                    $fullPath = $storageDisk->path($cleanPath);
                    if (file_exists($fullPath) && is_readable($fullPath)) {
                        $rawContent = @file_get_contents($fullPath);
                    }
                }

                if (!$rawContent) {
                    $publicFile = public_path($imagePath);
                    if (file_exists($publicFile) && is_readable($publicFile)) {
                        $rawContent = @file_get_contents($publicFile);
                    }
                }

                if (!$rawContent) {
                    $publicStorageFile = public_path('storage/' . $cleanPath);
                    if (file_exists($publicStorageFile) && is_readable($publicStorageFile)) {
                        $rawContent = @file_get_contents($publicStorageFile);
                    }
                }
            }

            if (!$rawContent || strlen($rawContent) === 0) {
                return null;
            }

            // Check if image is WebP format
            $isWebp = (strlen($rawContent) >= 12 && substr($rawContent, 0, 4) === 'RIFF' && substr($rawContent, 8, 4) === 'WEBP')
                || str_ends_with(strtolower($imagePath), '.webp');

            // 1. Try Imagick first (converts WebP to JPEG seamlessly if installed)
            if (extension_loaded('imagick') && class_exists('\Imagick')) {
                try {
                    $imagick = new \Imagick();
                    $imagick->readImageBlob($rawContent);
                    $imagick->setImageFormat('jpeg');
                    $imagick->thumbnailImage($maxDimension, $maxDimension, true);
                    $thumbData = $imagick->getImageBlob();
                    $imagick->clear();
                    $imagick->destroy();
                    if ($thumbData) {
                        return 'data:image/jpeg;base64,' . base64_encode($thumbData);
                    }
                } catch (\Throwable $e) {
                    // fall through to GD
                }
            }

            // 2. Try GD (if GD can decode the image)
            if (extension_loaded('gd') && function_exists('imagecreatefromstring')) {
                // If webp and imagecreatefromwebp is not available in GD, skip GD
                if (!($isWebp && !function_exists('imagecreatefromwebp'))) {
                    $src = @imagecreatefromstring($rawContent);
                    if ($src !== false) {
                        $w = imagesx($src);
                        $h = imagesy($src);

                        if ($w > 0 && $h > 0) {
                            $ratio = min($maxDimension / $w, $maxDimension / $h, 1.0);
                            $newW = max(1, (int) round($w * $ratio));
                            $newH = max(1, (int) round($h * $ratio));

                            $thumb = imagecreatetruecolor($newW, $newH);
                            $white = imagecolorallocate($thumb, 255, 255, 255);
                            imagefill($thumb, 0, 0, $white);

                            imagecopyresampled($thumb, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);

                            ob_start();
                            imagejpeg($thumb, null, 75);
                            $thumbData = ob_get_clean();

                            imagedestroy($thumb);
                            imagedestroy($src);

                            if ($thumbData) {
                                return 'data:image/jpeg;base64,' . base64_encode($thumbData);
                            }
                        }
                        imagedestroy($src);
                    }
                }
            }

            // 3. Fallback for raw JPEG/PNG (never WebP, never unsupported formats)
            if (!$isWebp && strlen($rawContent) <= 200 * 1024) {
                if (str_starts_with($rawContent, "\xFF\xD8\xFF")) {
                    return 'data:image/jpeg;base64,' . base64_encode($rawContent);
                }
                if (str_starts_with($rawContent, "\x89PNG\r\n\x1a\n")) {
                    return 'data:image/png;base64,' . base64_encode($rawContent);
                }
            }

            // If it's WebP or unrecognized and couldn't be converted to JPEG:
            return null;

        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Helper to compute commissions metrics for an arbitrary period.
     */
    private function calculateCommissionsMetrics(Request $request, string $startDate, string $endDate): array
    {
        $branchId = $request->get('branch_id');
        $userId = $request->get('user_id');
        $categoryId = $request->get('category_id');
        $brandId = $request->get('brand_id');
        $cashRegisterId = $request->get('cash_register_id');
        $user = auth()->user();

        $query = SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->leftJoin('products', 'sale_items.product_id', '=', 'products.id')
            ->leftJoin('services', 'sale_items.service_id', '=', 'services.id')
            ->leftJoin('categories', function ($join) {
                $join->on('categories.id', '=', DB::raw('COALESCE(products.category_id, services.category_id)'));
            })
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->join('users', 'sales.seller_id', '=', 'users.id')
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
            $query->where('sales.seller_id', $userId);
        }

        if ($user->isSupervisor()) {
            $supervisorRegisterIds = $user->getSupervisorCashRegisterIds();
            if (empty($supervisorRegisterIds)) {
                $query->whereRaw('0 = 1');
            } else {
                $filterIds = ($cashRegisterId && in_array((int) $cashRegisterId, $supervisorRegisterIds))
                    ? [(int) $cashRegisterId]
                    : $supervisorRegisterIds;
                $reconciliationIds = \App\Models\CashReconciliation::whereIn('cash_register_id', $filterIds)->pluck('id');
                $query->whereIn('sales.cash_reconciliation_id', $reconciliationIds);
            }
        } elseif ($cashRegisterId) {
            $reconciliationIds = \App\Models\CashReconciliation::where('cash_register_id', $cashRegisterId)->pluck('id');
            $query->whereIn('sales.cash_reconciliation_id', $reconciliationIds);
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

        $items = $query->select(
            'sale_items.*',
            'sales.invoice_number',
            'sales.created_at as sale_date',
            'users.id as seller_id',
            'users.name as seller_name',
            DB::raw("COALESCE(categories.name, 'Sin categoría') as cat_name"),
            'products.has_commission as p_has_comm',
            'products.commission_type as p_comm_type',
            'products.commission_value as p_comm_val',
            'services.has_commission as s_has_comm',
            'services.commission_type as s_comm_type',
            'services.commission_value as s_comm_val'
        )->get();

        $totalCommissions = 0;
        $totalSales = 0;
        $totalItems = 0;
        $uniqueSaleIds = [];
        $sellerData = [];
        $dailyData = [];
        $categoryData = [];
        $productData = [];

        foreach ($items as $item) {
            $basePrice = (float) $item->unit_price;
            $quantity = (float) $item->quantity;
            $itemTotal = (float) $item->total;

            $isService = $item->service_id !== null;
            $hasComm = $isService ? $item->s_has_comm : $item->p_has_comm;
            $commType = $isService ? $item->s_comm_type : $item->p_comm_type;
            $commVal = (float) ($isService ? $item->s_comm_val : $item->p_comm_val);

            $comm = 0;
            if ($hasComm && $commVal > 0) {
                if ($commType === 'percentage') {
                    $comm = ($basePrice * ($commVal / 100)) * $quantity;
                } else {
                    $comm = $commVal * $quantity;
                }
            }

            $totalCommissions += $comm;
            $totalSales += $itemTotal;
            $totalItems += $quantity;
            $uniqueSaleIds[$item->sale_id] = true;

            // Seller breakdown
            $sName = $item->seller_name ?? 'Sin asignar';
            if (!isset($sellerData[$sName])) {
                $sellerData[$sName] = ['name' => $sName, 'commission' => 0, 'sales' => 0, 'items' => 0, 'count' => 0];
            }
            $sellerData[$sName]['commission'] += $comm;
            $sellerData[$sName]['sales'] += $itemTotal;
            $sellerData[$sName]['items'] += $quantity;
            $sellerData[$sName]['count']++;

            // Daily breakdown (1..31)
            $day = (int) Carbon::parse($item->sale_date)->format('j');
            if (!isset($dailyData[$day])) {
                $dailyData[$day] = ['day' => $day, 'commission' => 0, 'sales' => 0, 'items' => 0, 'count' => 0];
            }
            $dailyData[$day]['commission'] += $comm;
            $dailyData[$day]['sales'] += $itemTotal;
            $dailyData[$day]['items'] += $quantity;
            $dailyData[$day]['count']++;

            // Category breakdown
            $cName = $item->cat_name ?? 'Sin categoría';
            if (!isset($categoryData[$cName])) {
                $categoryData[$cName] = ['name' => $cName, 'commission' => 0, 'sales' => 0, 'items' => 0];
            }
            $categoryData[$cName]['commission'] += $comm;
            $categoryData[$cName]['sales'] += $itemTotal;
            $categoryData[$cName]['items'] += $quantity;

            // Product breakdown
            $pKey = $item->product_sku ? $item->product_sku : $item->product_name;
            if (!isset($productData[$pKey])) {
                $productData[$pKey] = ['name' => $item->product_name, 'sku' => $item->product_sku, 'commission' => 0, 'sales' => 0, 'quantity' => 0];
            }
            $productData[$pKey]['commission'] += $comm;
            $productData[$pKey]['sales'] += $itemTotal;
            $productData[$pKey]['quantity'] += $quantity;
        }

        uasort($sellerData, fn($a, $b) => $b['commission'] <=> $a['commission']);
        uasort($categoryData, fn($a, $b) => $b['commission'] <=> $a['commission']);
        uasort($productData, fn($a, $b) => $b['commission'] <=> $a['commission']);

        $topSeller = !empty($sellerData) ? reset($sellerData) : ['name' => 'Ninguno', 'commission' => 0, 'sales' => 0];

        return [
            'totalCommissions' => $totalCommissions,
            'totalSales' => $totalSales,
            'totalItems' => $totalItems,
            'totalTransactions' => count($uniqueSaleIds),
            'avgCommissionRate' => $totalSales > 0 ? ($totalCommissions / $totalSales) * 100 : 0,
            'sellerData' => $sellerData,
            'dailyData' => $dailyData,
            'categoryData' => $categoryData,
            'productData' => $productData,
            'topSeller' => $topSeller,
        ];
    }

    /**
     * Export standard commissions report to Excel.
     */
    public function commissionsExcel(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $metrics = $this->calculateCommissionsMetrics($request, $startDate, $endDate);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Comisiones');

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A1225']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ];

        $subHeaderStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '7C3AED']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ];

        $dataStyle = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];

        $sheet->setCellValue('A1', 'MIKPOS - REPORTE DE COMISIONES POR VENDEDOR');
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(32);

        $sheet->setCellValue('A2', "Período: {$startDate} al {$endDate}");
        $sheet->mergeCells('A2:F2');
        $sheet->getStyle('A2:F2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row = 4;
        $sheet->setCellValue('A' . $row, 'RESUMEN GENERAL');
        $sheet->mergeCells('A' . $row . ':B' . $row);
        $sheet->getStyle('A' . $row . ':B' . $row)->applyFromArray($subHeaderStyle);
        $row++;

        $sheet->setCellValue('A' . $row, 'Total Comisiones');
        $sheet->setCellValue('B' . $row, $metrics['totalCommissions']);
        $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
        $row++;

        $sheet->setCellValue('A' . $row, 'Total Ventas Comisionables');
        $sheet->setCellValue('B' . $row, $metrics['totalSales']);
        $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
        $row++;

        $sheet->setCellValue('A' . $row, 'Tasa Promedio de Comisión');
        $sheet->setCellValue('B' . $row, ($metrics['avgCommissionRate'] / 100));
        $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('0.0%');
        $row++;

        $sheet->setCellValue('A' . $row, 'Total Items Vendidos');
        $sheet->setCellValue('B' . $row, $metrics['totalItems']);
        $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0');
        $row += 2;

        // Sellers Table
        $sheet->setCellValue('A' . $row, 'DETALLE POR VENDEDOR');
        $sheet->mergeCells('A' . $row . ':E' . $row);
        $sheet->getStyle('A' . $row . ':E' . $row)->applyFromArray($subHeaderStyle);
        $row++;

        $sheet->setCellValue('A' . $row, 'VENDEDOR');
        $sheet->setCellValue('B' . $row, 'VENTAS TOTALES ($)');
        $sheet->setCellValue('C' . $row, 'COMISIÓN GENERADA ($)');
        $sheet->setCellValue('D' . $row, '% EFECTIVO');
        $sheet->setCellValue('E' . $row, 'ITEMS VENDIDOS');
        $sheet->getStyle('A' . $row . ':E' . $row)->applyFromArray($subHeaderStyle);
        $row++;

        foreach ($metrics['sellerData'] as $s) {
            $sheet->setCellValue('A' . $row, $s['name']);
            $sheet->setCellValue('B' . $row, $s['sales']);
            $sheet->setCellValue('C' . $row, $s['commission']);
            $sheet->setCellValue('D' . $row, $s['sales'] > 0 ? ($s['commission'] / $s['sales']) : 0);
            $sheet->setCellValue('E' . $row, $s['items']);

            $sheet->getStyle('A' . $row . ':E' . $row)->applyFromArray($dataStyle);
            $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('0.0%');
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $row++;
        }

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'comisiones-' . $startDate . '-al-' . $endDate . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Export comparative versus commissions report to Excel.
     */
    public function commissionsVersusExcel(Request $request)
    {
        $startDateA = $request->get('start_date_a', now()->startOfMonth()->format('Y-m-d'));
        $endDateA = $request->get('end_date_a', now()->format('Y-m-d'));
        $startDateB = $request->get('start_date_b', now()->subMonth()->startOfMonth()->format('Y-m-d'));
        $endDateB = $request->get('end_date_b', now()->subMonth()->endOfMonth()->format('Y-m-d'));
        $labelA = $request->get('label_a', Carbon::parse($startDateA)->translatedFormat('F Y'));
        $labelB = $request->get('label_b', Carbon::parse($startDateB)->translatedFormat('F Y'));

        $dataA = $this->calculateCommissionsMetrics($request, $startDateA, $endDateA);
        $dataB = $this->calculateCommissionsMetrics($request, $startDateB, $endDateB);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Comisiones Versus');

        $mainHeaderStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 14],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A1225']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ];

        $sectionHeaderStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '7C3AED']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ];

        $subHeaderStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => '334155'], 'size' => 9],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ];

        $dataStyle = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];

        // 1. TITLE
        $sheet->setCellValue('A1', 'MIKPOS - COMPARATIVA DE COMISIONES (MODO VERSUS)');
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1:G1')->applyFromArray($mainHeaderStyle);
        $sheet->getRowDimension(1)->setRowHeight(32);

        $sheet->setCellValue('A2', "Período A (Base): " . strtoupper($labelA) . " ({$startDateA} a {$endDateA})  VS  Período B (Comparado): " . strtoupper($labelB) . " ({$startDateB} a {$endDateB})");
        $sheet->mergeCells('A2:G2');
        $sheet->getStyle('A2:G2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(2)->setRowHeight(20);

        // 2. EXECUTIVE BATTLE CARDS SUMMARY
        $row = 4;
        $sheet->setCellValue('A' . $row, '1. RESUMEN EJECUTIVO (BATTLE CARDS)');
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($sectionHeaderStyle);
        $sheet->getRowDimension($row)->setRowHeight(24);
        $row++;

        $sheet->setCellValue('A' . $row, 'INDICADOR KPI');
        $sheet->setCellValue('B' . $row, strtoupper($labelA) . ' (A)');
        $sheet->setCellValue('C' . $row, strtoupper($labelB) . ' (B)');
        $sheet->setCellValue('D' . $row, 'DIFERENCIA ($)');
        $sheet->setCellValue('E' . $row, '% CRECIMIENTO');
        $sheet->setCellValue('F' . $row, 'PROPORCIÓN (A vs B)');
        $sheet->setCellValue('G' . $row, 'GANADOR');
        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($subHeaderStyle);
        $sheet->getRowDimension($row)->setRowHeight(22);
        $row++;

        $kpis = [
            ['Total Comisiones', $dataA['totalCommissions'], $dataB['totalCommissions'], 'currency'],
            ['Total Ventas Comisionables', $dataA['totalSales'], $dataB['totalSales'], 'currency'],
            ['Total Items Vendidos', $dataA['totalItems'], $dataB['totalItems'], 'number'],
            ['Transacciones con Comisión', $dataA['totalTransactions'], $dataB['totalTransactions'], 'number'],
            ['Tasa Promedio de Comisión', ($dataA['avgCommissionRate'] / 100), ($dataB['avgCommissionRate'] / 100), 'percent'],
        ];

        foreach ($kpis as $kpi) {
            $sheet->setCellValue('A' . $row, $kpi[0]);
            $sheet->setCellValue('B' . $row, $kpi[1]);
            $sheet->setCellValue('C' . $row, $kpi[2]);
            $sheet->setCellValue('D' . $row, "=C{$row}-B{$row}");
            $sheet->setCellValue('E' . $row, "=IF(B{$row}<>0, (C{$row}-B{$row})/ABS(B{$row}), 0)");
            $sheet->setCellValue('F' . $row, "=IF(B{$row}+C{$row}>0, C{$row}/(B{$row}+C{$row}), 0.5)");
            $sheet->setCellValue('G' . $row, "=IF(C{$row}>=B{$row}, \"" . strtoupper($labelB) . " 🏆\", \"" . strtoupper($labelA) . " 🏆\")");

            $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($dataStyle);

            if ($kpi[3] === 'currency') {
                $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
                $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
                $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            } elseif ($kpi[3] === 'percent') {
                $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('0.0%');
                $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('0.0%');
                $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('+0.0%;-0.0%;0.0%');
            } else {
                $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('+#,##0;-#,##0;0');
            }

            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('+0.0%;-0.0%;0.0%');
            $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('0.0% B');
            $row++;
        }
        $row += 2;

        // 3. SIDE-BY-SIDE SELLERS COMPARISON
        $sheet->setCellValue('A' . $row, '2. COMPARATIVA DE RENDIMIENTO POR VENDEDOR');
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($sectionHeaderStyle);
        $sheet->getRowDimension($row)->setRowHeight(24);
        $row++;

        $sheet->setCellValue('A' . $row, 'VENDEDOR');
        $sheet->setCellValue('B' . $row, 'VENTAS ' . strtoupper($labelA));
        $sheet->setCellValue('C' . $row, 'COMISIÓN ' . strtoupper($labelA));
        $sheet->setCellValue('D' . $row, 'VENTAS ' . strtoupper($labelB));
        $sheet->setCellValue('E' . $row, 'COMISIÓN ' . strtoupper($labelB));
        $sheet->setCellValue('F' . $row, 'VARIACIÓN COMISIÓN ($)');
        $sheet->setCellValue('G' . $row, '% CRECIMIENTO');
        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($subHeaderStyle);
        $sheet->getRowDimension($row)->setRowHeight(22);
        $row++;

        $allSellers = array_unique(array_merge(array_keys($dataA['sellerData']), array_keys($dataB['sellerData'])));
        sort($allSellers);

        foreach ($allSellers as $sName) {
            $sA = $dataA['sellerData'][$sName]['sales'] ?? 0;
            $cA = $dataA['sellerData'][$sName]['commission'] ?? 0;
            $sB = $dataB['sellerData'][$sName]['sales'] ?? 0;
            $cB = $dataB['sellerData'][$sName]['commission'] ?? 0;

            $sheet->setCellValue('A' . $row, $sName);
            $sheet->setCellValue('B' . $row, $sA);
            $sheet->setCellValue('C' . $row, $cA);
            $sheet->setCellValue('D' . $row, $sB);
            $sheet->setCellValue('E' . $row, $cB);
            $sheet->setCellValue('F' . $row, "=E{$row}-C{$row}");
            $sheet->setCellValue('G' . $row, "=IF(C{$row}<>0, (E{$row}-C{$row})/ABS(C{$row}), 0)");

            $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($dataStyle);
            $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('+0.0%;-0.0%;0.0%');
            $row++;
        }
        $row += 2;

        // 4. DAILY COMMISSIONS BREAKDOWN (DAY 1..31)
        $sheet->setCellValue('A' . $row, '3. COMPARATIVA DÍA POR DÍA (DÍA 1 AL 31)');
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($sectionHeaderStyle);
        $sheet->getRowDimension($row)->setRowHeight(24);
        $row++;

        $sheet->setCellValue('A' . $row, 'DÍA');
        $sheet->setCellValue('B' . $row, 'VENTAS ' . strtoupper($labelA));
        $sheet->setCellValue('C' . $row, 'COMISIÓN ' . strtoupper($labelA));
        $sheet->setCellValue('D' . $row, 'VENTAS ' . strtoupper($labelB));
        $sheet->setCellValue('E' . $row, 'COMISIÓN ' . strtoupper($labelB));
        $sheet->setCellValue('F' . $row, 'VARIACIÓN COMISIÓN ($)');
        $sheet->setCellValue('G' . $row, '% CRECIMIENTO');
        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($subHeaderStyle);
        $sheet->getRowDimension($row)->setRowHeight(22);
        $row++;

        for ($d = 1; $d <= 31; $d++) {
            $sA = $dataA['dailyData'][$d]['sales'] ?? 0;
            $cA = $dataA['dailyData'][$d]['commission'] ?? 0;
            $sB = $dataB['dailyData'][$d]['sales'] ?? 0;
            $cB = $dataB['dailyData'][$d]['commission'] ?? 0;

            if ($sA == 0 && $sB == 0 && $cA == 0 && $cB == 0) continue;

            $sheet->setCellValue('A' . $row, "Día {$d}");
            $sheet->setCellValue('B' . $row, $sA);
            $sheet->setCellValue('C' . $row, $cA);
            $sheet->setCellValue('D' . $row, $sB);
            $sheet->setCellValue('E' . $row, $cB);
            $sheet->setCellValue('F' . $row, "=E{$row}-C{$row}");
            $sheet->setCellValue('G' . $row, "=IF(C{$row}<>0, (E{$row}-C{$row})/ABS(C{$row}), 0)");

            $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($dataStyle);
            $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('+0.0%;-0.0%;0.0%');
            $row++;
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'comisiones-versus-' . $startDateA . '-vs-' . $startDateB . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($tempFile);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}


