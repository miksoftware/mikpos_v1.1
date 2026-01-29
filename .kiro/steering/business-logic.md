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

## Purchase Management (Compras)

### Purchases
- Each purchase belongs to a branch (`branch_id`)
- Payment types: `cash` (contado) or `credit` (crédito)
- Payment status: `paid`, `partial`, `pending`
- Fields include: `supplier_id`, `total`, `payment_type`, `payment_status`, `amount_paid`

**Credit Purchase Flow**:
1. Create purchase with `payment_type = 'credit'`
2. Initial `payment_status = 'pending'`, `amount_paid = 0`
3. Register partial payments via payment modal
4. When `amount_paid >= total`, status becomes `paid`
5. Can also mark as fully paid directly

**Filters**:
- By payment type (cash/credit)
- By payment status (paid/partial/pending)
- By date range
- By supplier

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
- Products
- Customers
- Combos
- Cash Registers
- Purchases
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

// Check if enabled
if ($factus->isEnabled()) {
    // Create and validate invoice
    $response = $factus->createInvoice($sale);
    
    // Get PDF
    $pdfBase64 = $factus->getInvoicePdf($sale);
    
    // Check status
    $status = $factus->getInvoiceStatus($sale);
}
```

### DIAN Codes Reference

**Payment Methods (Métodos de Pago):**
- 10: Efectivo
- 47: Transferencia (Nequi, Daviplata, PSE, etc.)
- 48: Tarjeta Crédito
- 49: Tarjeta Débito
- 42: Consignación
- 20: Cheque
- 71: Bonos
- 72: Vales
- ZZ: Otro
- 1: No Definido

**Document Types (Tipos de Documento):**
- 1: Registro Civil (RC)
- 2: Tarjeta de Identidad (TI)
- 3: Cédula de Ciudadanía (CC)
- 4: Tarjeta de Extranjería (TE)
- 5: Cédula de Extranjería (CE)
- 6: NIT
- 7: Pasaporte (PA)
- 8: Documento de Identificación Extranjero (DIE)
- 9: PEP
- 10: NIT de Otro País
- 11: NUIP

**Legal Organization Types:**
- 1: Persona Jurídica
- 2: Persona Natural

**Payment Forms:**
- 1: Pago de contado
- 2: Pago a crédito

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
3. Selects correction concept
4. Enters reason
5. For partial: selects items and quantities
6. System creates CreditNote and CreditNoteItems
7. Sends to DIAN via FactusService::createCreditNote()
8. Updates status based on DIAN response

### FactusService Credit Note Methods
```php
// Create and validate credit note
$response = $factusService->createCreditNote($creditNote);

// Get PDF
$pdf = $factusService->getCreditNotePdf($creditNote);
```

## Refunds (Devoluciones) - POS Sales

### Overview
- Refunds are used for POS sales (non-electronic)
- Internal document, not sent to DIAN
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
6. Opens print window for refund receipt
7. Logs activity

### Refund Receipt
- Printed on 80mm thermal printer
- Shows original sale reference
- Lists refunded items
- Includes signature lines for customer and staff
- Route: `/refund-receipt/{refund}`

### Validation Rules
- Cannot create credit note/refund for more than remaining quantity
- System tracks credited/refunded quantities per item
- Reason is required (min 5-10 characters)
- At least one item must be selected
