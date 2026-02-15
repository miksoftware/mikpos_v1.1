# MikPOS - Business Logic

## Cash Management (Cajas)

### Cash Registers (Cajas)
- Each cash register belongs to a branch (`branch_id`)
- A user can only have ONE cash register assigned (validation in store method)
- Fields: `name`, `branch_id`, `user_id` (nullable), `is_active`
- When user is assigned, they can only operate that specific register

### Cash Reconciliations (Arqueos de Caja)
- Represents opening/closing of a cash register
- Status: `open` or `closed`
- Fields:
  - `cash_register_id` - The register being reconciled
  - `user_id` - User who opened/closed
  - `opening_amount` - Initial cash amount
  - `closing_amount` - Final cash amount (set when closing)
  - `expected_amount` - Calculated: opening + income - expenses
  - `difference` - Calculated: closing - expected
  - `opened_at`, `closed_at` - Timestamps
  - `opening_notes`, `closing_notes` - Optional notes

**User Experience**:
- If user has an assigned cash register, the form shows simplified view (only amount + notes)
- If user is admin/supervisor, they can select any register from their branch
- Only ONE open reconciliation per cash register at a time

### Cash Reconciliation Edit (Audit History)
- Closed reconciliations can be edited with audit trail
- `CashReconciliationEdit` model stores: old/new values, reason, user who edited
- Permission: `cash_reconciliations.edit`
- Edit history viewable in detail modal

### Cash Movements (Movimientos de Caja)
- Income/expense movements during an open reconciliation
- Types: `income` (ingreso) or `expense` (egreso)
- Fields: `cash_reconciliation_id`, `type`, `amount`, `description`, `user_id`
- Visual cards: Green for income, Red for expense
- Movements affect the expected closing amount

**Expected Amount Calculation**:
```php
$expected = $opening_amount 
    + $movements->where('type', 'income')->sum('amount')
    - $movements->where('type', 'expense')->sum('amount');
```

### Cash Reconciliation Totals (Sales Integration)
- Sales totals calculated from `sale_payments` where amount = actual sale total (not cash tendered)
- Refunds and credit notes shown separately in reconciliation detail
- Refund totals: sum of refunds linked to the reconciliation
- Credit note totals: sum of credit notes for sales within the reconciliation period

## Purchase Management (Compras)

### Purchases
- Each purchase belongs to a branch (`branch_id`)
- Payment types: `cash` (contado) or `credit` (crédito)
- Payment status: `paid`, `partial`, `pending`
- Fields include: `supplier_id`, `total`, `payment_type`, `payment_status`, `amount_paid`

### Purchase Item Discounts
- Each purchase item supports inline discounts
- `discount_type`: 'percentage' or 'fixed' (stored in `purchase_items` table)
- `discount`: The discount value
- Subtotal calculation: `(quantity * unit_cost) - discount_amount`

**Credit Purchase Flow**:
1. Create purchase with `payment_type = 'credit'`
2. Initial `payment_status = 'pending'`, `amount_paid = 0`
3. Register partial payments via payment modal
4. When `amount_paid >= total`, status becomes `paid`
5. Can also mark as fully paid directly

### Product Search in Purchases
- Products are filtered by selected branch
- **Super Admin**: Must select branch before searching products
- **Other Users**: Automatically filtered by their assigned branch
- Show warning message if no branch selected (for super_admin)

## Branch-Dependent Operations

### Data Filtering Pattern
```php
// For super_admin - must select branch
if (auth()->user()->isSuperAdmin()) {
    if (!$this->branch_id) {
        // Show warning, don't allow operation
        return;
    }
    $query->where('branch_id', $this->branch_id);
} else {
    // Regular users - use their assigned branch
    $query->where('branch_id', auth()->user()->branch_id);
}
```

### Entities with Branch Filtering
- Products, Services
- Customers
- Combos
- Cash Registers
- Purchases, Sales
- Inventory Movements

## User Management

### Role Assignment
- Roles are assigned via many-to-many relationship (`user_role` pivot table)
- Use `$user->roles()->sync([$roleId])` to assign role
- Get user's role: `$user->roles->first()`
- Role model has `display_name` field for UI display

### Branch Assignment
- Users have direct `branch_id` field
- Super admin has no branch restriction
- Other users can only access data from their branch

## Activity Logging

### Usage Pattern
```php
use App\Services\ActivityLogService;

// Create
ActivityLogService::logCreate($model, "Descripción creada");

// Update
ActivityLogService::logUpdate($model, $oldValues, "Descripción actualizada");

// Delete
ActivityLogService::logDelete($model, "Descripción eliminada");
```

**Important**: Always pass Eloquent Model instance, not stdClass or array.

### Activity Log Viewer
- Module: `ActivityLogs` component at `/activity-logs`
- Filters: period (today/yesterday/week/month/custom), branch, user, module, action, search
- Detail modal shows old_values (red) and new_values (green) comparison
- Permissions: `activity_logs.view`, `activity_logs.export`

## Validation Rules

### Cash Register
- User can only have ONE register assigned
- Name is required and unique per branch

### Cash Reconciliation
- Only one OPEN reconciliation per register
- Closing amount required when closing
- Cannot close with negative expected amount

### Purchases
- Branch required for super_admin
- Supplier required
- At least one item required
- Payment amount cannot exceed remaining balance


## Electronic Invoicing (Facturación Electrónica)

### Overview
- Integration with Factus API for DIAN electronic invoicing in Colombia
- Can be enabled/disabled from BillingSettings module
- When enabled, invoices are automatically sent to DIAN after each sale

### Configuration (BillingSetting model)
- `provider`: 'factus' (default)
- `is_enabled`: Toggle to enable/disable electronic invoicing
- `environment`: 'sandbox' or 'production'
- `api_url`: Auto-configured based on environment
- `client_id`, `client_secret`: OAuth credentials
- `username`, `password`: Factus user credentials
- `access_token`, `refresh_token`: Stored tokens (encrypted)
- `token_expires_at`: Token expiration timestamp

### Sale Electronic Invoice Fields
- `is_electronic`: Boolean indicating if invoice was sent to DIAN
- `cufe`: DIAN unique code (Código Único de Factura Electrónica)
- `qr_code`: QR code URL for verification
- `dian_number`: DIAN invoice number (prefix + number)
- `dian_validated_at`: Timestamp when DIAN validated
- `dian_response`: Full JSON response from Factus
- `reference_code`: Unique reference for Factus (POS-{sale_id}-{timestamp})

### FactusService Methods
```php
use App\Services\FactusService;

$factus = new FactusService();

if ($factus->isEnabled()) {
    $response = $factus->createInvoice($sale);
    $pdfBase64 = $factus->getInvoicePdf($sale);
    $status = $factus->getInvoiceStatus($sale);
}
```

### DIAN Codes Reference

**Payment Methods:** 10: Efectivo, 47: Transferencia, 48: Tarjeta Crédito, 49: Tarjeta Débito, 42: Consignación, 20: Cheque, 71: Bonos, 72: Vales, ZZ: Otro, 1: No Definido

**Document Types:** 1: RC, 2: TI, 3: CC, 4: TE, 5: CE, 6: NIT, 7: PA, 8: DIE, 9: PEP, 10: NIT Otro País, 11: NUIP

**Legal Organization Types:** 1: Persona Jurídica, 2: Persona Natural

**Payment Forms:** 1: Pago de contado, 2: Pago a crédito

### POS Integration
- Electronic invoicing is processed automatically after sale creation
- If enabled and configured, invoice is sent to DIAN
- Success/failure is shown in notification
- Sale continues even if DIAN validation fails (logged for retry)
- Visual indicator "FE" in POS header when enabled


## Credit Notes (Notas Crédito) - Electronic Invoices

### Overview
- Credit notes are used to partially or totally cancel electronic invoices
- Sent to DIAN via Factus API for validation
- Only available for validated electronic invoices (with CUFE)
- Returns inventory to stock (creates inventory movements)

### CreditNote Model Fields
- `sale_id`: Reference to original sale
- `number`: Internal number (NC-YYYYMMDD-XXXX)
- `type`: 'total' or 'partial'
- `correction_concept_code`: DIAN correction concept (1-5)
- `reason`: Description of why the credit note is being issued
- `subtotal`, `tax_total`, `total`: Amounts
- `cufe`, `qr_code`, `dian_public_url`, `dian_number`: DIAN response fields
- `status`: 'pending', 'validated', 'rejected'

### DIAN Correction Concepts
- 1: Devolución parcial de bienes y/o no aceptación parcial del servicio
- 2: Anulación de factura electrónica
- 3: Rebaja o descuento parcial o total
- 4: Ajuste de precio
- 5: Otros

### Credit Note Flow
1. User opens credit note modal from validated electronic invoice
2. Selects type (total/partial)
3. Selects correction concept and enters reason
4. For partial: selects items and quantities
5. System creates CreditNote and CreditNoteItems
6. Sends to DIAN via FactusService::createCreditNote()
7. Returns inventory to stock (inventory movements created)
8. Updates status based on DIAN response


## Refunds (Devoluciones) - POS Sales

### Overview
- Refunds are used for POS sales (non-electronic)
- Internal document, not sent to DIAN
- Returns inventory to stock (creates inventory movements)
- Generates printable receipt

### Refund Model Fields
- `sale_id`: Reference to original sale
- `number`: Internal number (DEV-YYYYMMDD-XXXX)
- `type`: 'total' or 'partial'
- `reason`: Description of why the refund is being issued
- `cash_reconciliation_id`: Optional link to current cash register
- `subtotal`, `tax_total`, `total`: Amounts
- `status`: 'completed', 'cancelled'

### Refund Flow
1. User opens refund modal from POS sale
2. Selects type (total/partial)
3. Enters reason
4. For partial: selects items and quantities
5. System creates Refund and RefundItems
6. Returns inventory to stock (inventory movements created)
7. Opens print window for refund receipt
8. Logs activity

### Refund Receipt
- Printed on 80mm thermal printer
- Shows original sale reference
- Lists refunded items
- Route: `/refund-receipt/{refund}`

### Validation Rules
- Cannot create credit note/refund for more than remaining quantity
- System tracks credited/refunded quantities per item
- Reason is required (min 5-10 characters)
- At least one item must be selected


## Sale Invoice Number Generation
- `Sale::generateInvoiceNumber()` searches by invoice_number LIKE pattern (not by date)
- Format: `{document_code}-{YYYYMMDD}-{sequential_number}`
- Prevents duplicates even after timezone corrections


## Deployment & Seeders

### Automated Seeder System
Similar to migrations, seeders are tracked to avoid duplicates during deployment.

**Table**: `seeder_history`
- `seeder`: Seeder class name
- `batch`: Execution batch number (0 = initial/manual mark)
- `executed_at`: Timestamp

### Artisan Commands

**Run pending seeders:**
```bash
php artisan db:seed-pending --force
```

**Mark existing seeders as executed (initial setup):**
```bash
php artisan db:seed-mark-executed --all
```

### Adding New Seeders
1. Create the seeder: `php artisan make:seeder NewFeatureSeeder`
2. Add to `$trackedSeeders` array in both:
   - `app/Console/Commands/SeedPending.php`
   - `app/Console/Commands/SeedMarkExecuted.php`
3. Commit and push - deploy will run it automatically

### Current Tracked Seeders
```php
$trackedSeeders = [
    'RolesAndPermissionsSeeder',
    'DepartmentSeeder',
    'MunicipalitySeeder',
    'TaxDocumentsSeeder',
    'PaymentMethodsSeeder',
    'SystemDocumentsSeeder',
    'ProductCatalogPermissionsSeeder',
    'CustomerModuleSeeder',
    'SupplierModuleSeeder',
    'ProductsModuleSeeder',
    'CombosModuleSeeder',
    'PurchasesModuleSeeder',
    'CashRegistersModuleSeeder',
    'CashReconciliationsModuleSeeder',
    'InventoryAdjustmentsModuleSeeder',
    'InventoryTransfersModuleSeeder',
    'BillingSettingsModuleSeeder',
    'SalesModuleSeeder',
    'ServicesModuleSeeder',
    'ReportsModuleSeeder',
    'CommissionsReportPermissionSeeder',
    'KardexReportPermissionSeeder',
    'SalesBookReportPermissionSeeder',
    'WeightUnitsSeeder',
    'ProfitLossReportPermissionSeeder',
    'CreditsModuleSeeder',
    'CreditsReportPermissionSeeder',
    'CashReconciliationEditPermissionSeeder',
    'RefundSystemDocumentSeeder',
    'PurchasesReportPermissionSeeder',
    'CashReportPermissionSeeder',
];
```

### Deploy Script
Located at `deploy.sh` in project root. Key steps:
1. Maintenance mode on
2. Git pull from origin/main
3. Composer install (no-dev)
4. NPM install + build
5. Run migrations
6. Run pending seeders (`db:seed-pending --force`)
7. Optimize (cache config, routes, views)
8. Maintenance mode off

### Production Setup (First Time)
After deploying the seeder system to production:
```bash
docker compose exec -T php php artisan migrate --force
docker compose exec -T php php artisan db:seed-mark-executed --all
```


## POS Features

### Customer Creation from POS
- Accessible via F7 modal or search button (lupa icon)
- Toggle between search and create views
- Fields: type (natural/juridico), document type, document number, name/business name, phone, email
- New customer automatically selected after creation

### Barcode Auto-Search
- Barcode input auto-triggers search after 300ms of no typing
- Works for barcodes 8+ digits
- Enter key still works for manual search
- Searches both ProductChild and Product tables

### Branch Name Display
- Sidebar logo shows branch name instead of "MikPOS"
- POS header shows branch name
- Falls back to "MikPOS" if no branch assigned

### Product Card Layout (POS Grid)
- Price badge: top-right corner (gradient)
- Service badge: top-left "Serv." (indigo)
- Variant badges: bottom-left "Var." (blue/purple)
- Stock info: bottom of card text area, next to brand name (green/red text)
- Services do NOT show stock


## Model Column Reference (IMPORTANT)

### CRITICAL: Always prefix columns in JOINs
When doing JOINs between tables, ALWAYS prefix column names with the table name to avoid ambiguity errors:
```php
// BAD - will cause "Column 'status' is ambiguous" error
$query->where('status', 'completed');

// GOOD - always prefix with table name
$query->where('sales.status', 'completed');
```

Tables that share common column names:
- `status`: sales, cash_reconciliations, credit_notes, refunds
- `created_at`: ALL tables
- `name`: users, branches, categories, brands, etc.
- `is_active`: most configuration tables

### Module Model
- Uses `name` (NOT `code`) as identifier
- Fields: `name`, `display_name`, `icon`, `order`, `is_active`

### Permission Model
- Uses `name` (NOT `code`) as identifier
- Fields: `name`, `display_name`, `description`, `module_id`

### Role Model
- Uses `name` (NOT `code`) as identifier
- Fields: `name`, `display_name`, `description`, `is_active`

### Customer Model
- Does NOT have a `name` column
- Uses `first_name`, `last_name` for natural persons
- Uses `business_name` for juridical persons
- Has computed `full_name` and `display_name` attributes
- Order by: `orderBy('first_name')` NOT `orderBy('name')`

### User Model
- Has `name` column (full name)
- Check super admin: `$user->isSuperAdmin()` (NOT `hasRole()`)

### SystemDocument Model
- Has `code` field that is auto-generated on creation only
- Never overwrite existing `code` on edit

### Seeder Best Practices
- Do NOT use `$this->command->info()` in seeders - causes null error when run from custom commands
- Use `firstOrCreate()` with correct column names for the model
- Always check model fillable/columns before writing seeders


## Reports Module

### Reports Structure
Located in `app/Livewire/Reports/` with views in `resources/views/livewire/reports/`

Current reports:
- **SalesBook** - Complete sales book report
- **ProductsSold** - Products sold report with filters and export
- **Commissions** - Sales commissions report with export
- **ProfitLoss** - Profit and loss report with export
- **CreditsReport** - Credits/receivables report
- **PurchasesReport** - Purchases report (7 tabs, 3 Chart.js charts, filters by period/branch/supplier/payment)
- **CashReport** - Cash report (3 tabs: Arqueos, Movimientos, Informe consolidado)
- **Kardex** - Inventory kardex report

### Report Permissions
- `reports.view` - Base permission to access reports section
- `reports.products_sold` - Products sold report
- `reports.commissions` - Commissions report
- `reports.kardex` - Kardex inventory report
- `reports.sales_book` - Sales book report
- `reports.profit_loss` - Profit and loss report
- `reports.credits` - Credits report
- `reports.purchases` - Purchases report
- `reports.cash` - Cash report
- `reports.export` - Export reports to PDF/Excel

### Adding New Reports
1. Create Livewire component in `app/Livewire/Reports/`
2. Create view in `resources/views/livewire/reports/`
3. Create permission seeder (use correct model column names!)
4. Add seeder to tracked seeders in both commands
5. Add route in `routes/web.php` under reports group
6. Add menu item in `resources/views/components/sidebar-menu.blade.php`


## Decimal Quantities (Products by Weight)

### Overview
The system supports decimal quantities for products sold by weight (kg, lb, etc.).

### Database Columns (DECIMAL 12,3)
- `sale_items.quantity` - Supports up to 3 decimal places
- `inventory_movements.quantity`, `stock_before`, `stock_after` - Decimal stock tracking
- `products.current_stock`, `min_stock`, `max_stock` - Decimal stock levels

### Model Casts
```php
// SaleItem
'quantity' => 'decimal:3'

// InventoryMovement
'quantity' => 'decimal:3'
'stock_before' => 'decimal:3'
'stock_after' => 'decimal:3'

// Product
'current_stock' => 'decimal:3'
'min_stock' => 'decimal:3'
'max_stock' => 'decimal:3'
```

### Display Formatting
For clean display of quantities (removes trailing zeros):
```blade
{{ rtrim(rtrim(number_format($quantity, 3), '0'), '.') }}
```
Examples: 1.5 → "1.5", 2.000 → "2", 1.125 → "1.125"

### IMPORTANT: Never cast quantity to int
When working with quantities, always use `(float)` cast, never `(int)`:
```php
// BAD - loses decimal precision
$quantity = (int) $item->quantity;

// GOOD - preserves decimals
$quantity = (float) $item->quantity;
```


## Production Database

### CRITICAL: MySQL in Production
- Production uses MySQL, NOT SQLite
- Use MySQL-compatible SQL functions:
  - `DATE_FORMAT()` NOT `strftime()`
  - `CONCAT()` NOT `||`
  - `IFNULL()` works in both
- Always test queries for MySQL compatibility

### Timezone
- App timezone: `America/Bogota` (configured in `.env` and `config/app.php`)
- `fix:utc-dates` command available to convert old UTC dates

### PHP Gotchas
- PHP string interpolation: cannot use `??` inside `"{$var->prop ?? 'default'}"` — extract to variable first
- `auth()->id()` is null in artisan commands — use `Auth::loginUsingId()` when needed
