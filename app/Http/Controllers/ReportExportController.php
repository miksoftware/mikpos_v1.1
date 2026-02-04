<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Brand;
use App\Models\User;
use App\Models\Product;
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
        $data = $this->getCommissionsData($request);
        
        $pdf = Pdf::loadView('reports.commissions-pdf', $data);
        $pdf->setPaper('a4', 'portrait');
        
        $filename = 'comisiones-' . now()->format('Y-m-d') . '.pdf';
        
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
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->join('users', 'sales.user_id', '=', 'users.id')
            ->where('sales.status', 'completed')
            ->whereDate('sales.created_at', '>=', $startDate)
            ->whereDate('sales.created_at', '<=', $endDate)
            ->where(function ($q) {
                $q->where('products.has_commission', true)
                  ->whereNotNull('products.commission_value')
                  ->where('products.commission_value', '>', 0);
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
            $query->where('products.category_id', $categoryId);
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
                'products.has_commission',
                'products.commission_type',
                'products.commission_value',
                DB::raw("COALESCE(categories.name, 'Sin categoría') as category_name"),
                DB::raw("COALESCE(brands.name, 'Sin marca') as brand_name")
            )
            ->with('product')
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

            $commission = $this->calculateCommission($item);
            $userCommissions[$userId]['commission'] += $commission;
            $userCommissions[$userId]['sales'] += (float) $item->total;
            $userCommissions[$userId]['items_count'] += (int) $item->quantity;
            $userCommissions[$userId]['items'][] = [
                'invoice_number' => $item->invoice_number,
                'date' => Carbon::parse($item->sale_date)->format('d/m/Y'),
                'product_name' => $item->product_name,
                'category' => $item->category_name,
                'brand' => $item->brand_name,
                'quantity' => (int) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total' => (float) $item->total,
                'commission' => $commission,
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
        ];
    }

    private function calculateCommission($item): float
    {
        if (!$item->has_commission || !$item->commission_value) {
            return 0;
        }

        $basePrice = (float) $item->unit_price;
        $quantity = (int) $item->quantity;
        $commissionValue = (float) $item->commission_value;
        $commissionType = $item->commission_type;

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
}
