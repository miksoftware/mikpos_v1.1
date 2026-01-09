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

## User Roles (via roles table)
- **super_admin**: Full system access across all branches
- **branch_admin**: Administration of assigned branch only
- **supervisor**: Oversight capabilities within branch
- **cashier**: POS operations only

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
  - activity_logs - Activity logging
  - tax_documents - Tax document types
  - currencies - Currency management
  - payment_methods - Payment method configuration
  - taxes - Tax rates management
  - **Product Catalog Modules:**
    - categories - Product categories
    - subcategories - Product subcategories
    - brands - Product brands
    - units - Units of measurement
    - product_models - Product models (optional)
    - presentations - Product presentations (optional)
    - colors - Product colors (optional)
    - imeis - IMEI management (optional)

## Product Catalog System
- **Categories**: Main product groupings (Electronics, Clothing, Food, etc.)
- **Subcategories**: Category subdivisions with parent category relationship
- **Brands**: Product manufacturers/brands with model relationships
- **Units**: Measurement units (UND, KG, LT, CJ) with abbreviations
- **Models**: Product models linked to brands (optional)
- **Presentations**: Product presentation types (Box x12, Blister x10) (optional)
- **Colors**: Product colors with HEX color codes (optional)
- **IMEIs**: Device IMEI management with status tracking (available/sold/reserved) (optional)

## Language
- UI is in Spanish (es)
- Code comments and variable names in English

## Default Test Users
- **Super Admin**: admin@mikpos.com / password
- **Branch Admin**: branch@mikpos.com / password  
- **Cashier**: cajero@mikpos.com / password
