# MikPOS - Product Overview

MikPOS is a Point of Sale (POS) system designed for retail/business operations with multi-branch support.

## Core Features
- User authentication with role-based access (super_admin, branch_admin, supervisor, cashier)
- Multi-branch management with user assignment
- User management with status toggling
- Dashboard for operations overview
- Complete product catalog management
- Geographic location management (departments/municipalities)
- Tax and fiscal document configuration
- Currency and payment method management
- Cash register management with reconciliations and edit history
- Purchase management with credit/cash payment tracking and inline discounts
- Inventory management (adjustments, transfers)
- Combo products management
- Services management (no inventory)
- Discounts management (percentage/fixed, global/specific products)
- Expense tracking with payment method and contact association
- Electronic invoicing (DIAN/Factus)
- Credit notes and refunds with inventory return
- Cancel & Replicate sales (refund/credit note + create modified replacement)
- Print format configuration (thermal/letter per document type)
- Payroll management (employees, periods, calculations, payslips)
- Legacy data migration from SQL files
- Activity log viewer
- Reports: Sales Book, Products Sold, Commissions, Profit/Loss, Credits, Purchases, Cash, Payment Methods, Kardex

## User Roles (via roles table - many-to-many relationship)
- **super_admin**: Full system access across all branches
- **branch_admin**: Administration of assigned branch only
- **supervisor**: Oversight capabilities within branch
- **cashier**: POS operations only

**Important**: User roles are stored via many-to-many relationship (`user_role` pivot table), not a direct `role` field on users table. Use `$user->roles()->first()` to get user's role.

## Permissions System
- Permissions are organized by modules with granular permissions (view, create, edit, delete, etc.)
- **Current Modules:**
  - dashboard - Dashboard access
  - branches - Multi-branch management
  - users - User management
  - departments - Geographic departments
  - municipalities - Geographic municipalities
  - roles - Roles and permissions management
  - pos - Point of sale operations
  - reports - Reporting system
  - activity_logs - Activity log viewer (view, export)
  - billing_settings - Electronic invoicing configuration
  - tax_documents - Tax document types
  - currencies - Currency management
  - payment_methods - Payment method configuration
  - taxes - Tax rates management
  - system_documents - System document types
  - product_field_config - Product field configuration
  - **Product Catalog Modules:**
    - categories - Product categories
    - subcategories - Product subcategories
    - brands - Product brands
    - units - Units of measurement
    - product_models - Product models (optional)
    - presentations - Product presentations (optional)
    - colors - Product colors (optional)
    - imeis - IMEI management (optional)
  - **Cash Management:**
    - cash_registers - Cash register creation/management
    - cash_reconciliations - Cash reconciliations (arqueos) + edit permission
  - **Inventory:**
    - products - Product management
    - services - Service management
    - combos - Combo products
    - discounts - Discount management
    - customers - Customer management
    - suppliers - Supplier management
    - purchases - Purchase orders
    - credits - Credit/payment management
    - expenses - Expense tracking
    - inventory_adjustments - Inventory adjustments
    - inventory_transfers - Inventory transfers between branches
  - **Payroll (Nómina):**
    - employees - Employee management
    - payrolls - Payroll period management
  - **Configuration (additional):**
    - print_formats - Print format configuration
    - migration - Legacy data migration
  - **Report Permissions:**
    - reports.view - Base access to reports section
    - reports.products_sold - Products sold report
    - reports.commissions - Commissions report
    - reports.kardex - Kardex inventory report
    - reports.sales_book - Sales book report
    - reports.profit_loss - Profit and loss report
    - reports.credits - Credits report
    - reports.purchases - Purchases report
    - reports.cash - Cash report
    - reports.payment_methods - Payment methods report
    - reports.export - Export reports to PDF/Excel

## Branch-Dependent Data
The following entities are filtered by branch:
- Products (`branch_id`)
- Services (`branch_id`)
- Customers (`branch_id`)
- Combos (`branch_id`)
- Cash Registers (`branch_id`)
- Cash Reconciliations (via cash register)
- Purchases (`branch_id`)
- Sales (`branch_id`)
- Expenses (`branch_id`)
- Employees (`branch_id`)

**Super Admin Behavior**: Must select a branch before performing operations that require branch context (e.g., searching products in purchases).

## Language
- UI is in Spanish (es)
- Code comments and variable names in English

## Default Test Users
- **Super Admin**: admin@mikpos.com / password
- **Branch Admin**: branch@mikpos.com / password
- **Cashier**: cajero@mikpos.com / password
