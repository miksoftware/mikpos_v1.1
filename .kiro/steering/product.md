# MikPOS - Product Overview

MikPOS is a Point of Sale (POS) system designed for retail/business operations with multi-branch support.

## Core Features
- User authentication with role-based access (admin, manager, supervisor, cashier)
- Multi-branch management with user assignment
- User management with status toggling
- Dashboard for operations overview

## User Roles (via roles table)
- **super_admin**: Full system access across all branches
- **branch_admin**: Administration of assigned branch only
- **supervisor**: Oversight capabilities within branch
- **cashier**: POS operations only

## Permissions System
- Permissions are organized by modules (dashboard, branches, users, roles, pos, reports, activity_logs)
- Each module has granular permissions (view, create, edit, delete, etc.)
- See `docs/PERMISSIONS.md` for creating new permissions

## Language
- UI is in Spanish (es)
- Code comments and variable names in English
