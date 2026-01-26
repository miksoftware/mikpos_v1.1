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
