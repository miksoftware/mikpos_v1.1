# Implementation Plan: Weight Quantity Modal

## Overview

This implementation plan breaks down the weight quantity modal feature into incremental coding tasks. Each task builds on previous work, ensuring the feature is developed progressively with proper testing at each stage.

## Tasks

- [x] 1. Database schema and model updates
  - [x] 1.1 Create migration to add `is_weight_unit` column to units table
    - Add boolean column `is_weight_unit` with default false
    - Column should be placed after `abbreviation`
    - _Requirements: 1.1, 1.2_
  
  - [x] 1.2 Update Unit model with new field
    - Add `is_weight_unit` to `$fillable` array
    - Add `is_weight_unit` to `casts()` as boolean
    - Add `isWeightUnit()` helper method
    - _Requirements: 1.1_
  
  - [x] 1.3 Create seeder to mark default weight units
    - Update existing units with abbreviations: KG, KILO, KILOGRAMO, LB, LIBRA, GR, G, GRAMO, OZ, ONZA, MG, MILIGRAMO
    - Set `is_weight_unit = true` for matching abbreviations (case-insensitive)
    - Add seeder to tracked seeders in SeedPending and SeedMarkExecuted commands
    - _Requirements: 1.3_

- [x] 2. Checkpoint - Database changes
  - Run migration and seeder
  - Verify units table has new column
  - Verify weight units are properly marked
  - Ensure all tests pass, ask the user if questions arise.

- [x] 3. PointOfSale component logic
  - [x] 3.1 Add weight modal state properties to PointOfSale component
    - Add `$showWeightModal` boolean property (default false)
    - Add `$weightModalProduct` array property (default null)
    - Add `$weightModalQuantity` string property (default empty)
    - _Requirements: 2.1_
  
  - [x] 3.2 Implement weight unit detection method
    - Create `isWeightBasedProduct($product)` protected method
    - Load unit relationship if not loaded
    - Return true if product has unit with `is_weight_unit = true`
    - Return false otherwise (including products without unit)
    - _Requirements: 2.1, 5.1_
  
  - [x] 3.3 Implement openWeightModal method
    - Accept `$productId` and optional `$childId` parameters
    - Load product with unit, tax, and children relationships
    - Populate `$weightModalProduct` with: product_id, child_id, name, price (with tax), unit abbreviation, stock
    - Set `$showWeightModal = true`
    - Clear `$weightModalQuantity`
    - _Requirements: 2.1, 2.3, 2.4, 2.5, 2.6_
  
  - [x] 3.4 Implement confirmWeightModal method
    - Parse quantity (handle both comma and period as decimal separator)
    - Validate quantity > 0, show error if not
    - Validate quantity <= stock, show error if not
    - Round quantity to 3 decimal places
    - Call internal method to add product to cart with specified quantity
    - Close modal and dispatch focus event
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 4.4_
  
  - [x] 3.5 Implement closeWeightModal method
    - Reset `$showWeightModal = false`
    - Reset `$weightModalProduct = null`
    - Reset `$weightModalQuantity = ''`
    - Dispatch event to focus barcode/product search
    - _Requirements: 4.2, 4.3, 4.5, 6.2_
  
  - [x] 3.6 Modify addToCart method to check for weight units
    - Load unit relationship with product query
    - Check if product is weight-based using `isWeightBasedProduct()`
    - If weight-based, call `openWeightModal()` and return early
    - Otherwise, continue with existing add-to-cart logic
    - _Requirements: 2.1, 5.1_
  
  - [x] 3.7 Create internal method for adding to cart with specific quantity
    - Extract cart addition logic from `addToCart()` into `addProductToCartWithQuantity($productId, $childId, $quantity)`
    - This method handles the actual cart item creation/update
    - Used by both regular add (qty=1) and weight modal (custom qty)
    - _Requirements: 4.4_
  
  - [ ]* 3.8 Write property test for weight unit detection
    - **Property 2: Weight Unit Detection Determines Modal Behavior**
    - Generate random products with various unit configurations
    - Verify `isWeightBasedProduct()` returns correct boolean
    - **Validates: Requirements 2.1, 5.1, 6.1**
  
  - [ ]* 3.9 Write property test for quantity validation
    - **Property 5: Non-Positive Quantity Rejection**
    - **Property 6: Stock Limit Validation**
    - Generate random non-positive quantities, verify rejection
    - Generate random quantities exceeding stock, verify rejection
    - **Validates: Requirements 3.2, 3.3**

- [x] 4. Checkpoint - Component logic
  - Test weight detection with existing products
  - Test modal opens for weight products
  - Test modal doesn't open for non-weight products
  - Ensure all tests pass, ask the user if questions arise.

- [x] 5. Weight modal UI implementation
  - [x] 5.1 Add weight quantity modal to point-of-sale.blade.php
    - Add modal markup after existing modals (variant modal, discount modal)
    - Include backdrop with click-to-close functionality
    - Include keyboard shortcuts (Enter to confirm, Escape to cancel)
    - Auto-focus quantity input on modal open
    - _Requirements: 2.2, 4.1, 4.2, 4.3_
  
  - [x] 5.2 Implement modal content section
    - Display product name prominently
    - Display unit price with unit abbreviation
    - Add large numeric input field with step="0.001"
    - Display unit abbreviation below input
    - Display available stock information
    - _Requirements: 2.3, 2.4, 2.5, 2.6, 3.1_
  
  - [x] 5.3 Implement modal footer with action buttons
    - Add "Cancelar" button calling closeWeightModal
    - Add "Agregar" button calling confirmWeightModal
    - Style buttons according to MikPOS UI standards
    - _Requirements: 4.4, 4.5_

- [x] 6. Barcode scanner integration
  - [x] 6.1 Update searchByBarcode method for weight products
    - When barcode identifies a product, check if weight-based
    - If weight-based, call `openWeightModal()` instead of `addToCart()`
    - Ensure focus returns to barcode input after modal closes
    - _Requirements: 6.1, 6.2_

- [x] 7. Final checkpoint
  - Test complete flow: select weight product → enter quantity → add to cart
  - Test barcode scanning for weight products
  - Test keyboard shortcuts (Enter, Escape)
  - Test validation errors display correctly
  - Test non-weight products still work normally
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- The implementation follows the existing MikPOS patterns for Livewire components and modals
- Property tests validate universal correctness properties
- Unit tests validate specific examples and edge cases
