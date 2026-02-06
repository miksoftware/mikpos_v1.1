# Requirements Document

## Introduction

This feature enhances the MikPOS Point of Sale system by automatically displaying a quantity input modal when selecting weight-based products (sold by KG, LB, GR, etc.). This improves the user experience for businesses like fruver (fruit/vegetable stores) where products are commonly sold by weight, allowing cashiers to quickly enter exact quantities without navigating to the quantity field manually.

## Glossary

- **POS**: Point of Sale - The interface where cashiers process sales transactions
- **Weight_Unit**: A unit of measurement used for products sold by weight (e.g., KG, LB, GR, OZ)
- **Unit_Unit**: A unit of measurement for products sold by count (e.g., UND, PZA, UNIDAD)
- **Quantity_Modal**: A popup dialog that allows entering the exact quantity for weight-based products
- **Cart**: The collection of items selected for the current sale transaction
- **Sellable_Item**: A product or service that can be added to the cart

## Requirements

### Requirement 1: Identify Weight-Based Units

**User Story:** As a system administrator, I want the system to identify which units are weight-based, so that the POS can determine when to show the quantity modal.

#### Acceptance Criteria

1. THE Unit model SHALL have an `is_weight_unit` boolean field to identify weight-based units
2. WHEN a new unit is created, THE System SHALL default `is_weight_unit` to false
3. THE System SHALL mark the following unit abbreviations as weight units by default: KG, KL, KILO, KILOS, KILOGRAMO, KILOGRAMOS, LB, LBS, LIBRA, LIBRAS, GR, G, GRAMO, GRAMOS, OZ, ONZA, ONZAS, MG, MILIGRAMO, MILIGRAMOS

### Requirement 2: Automatic Quantity Modal for Weight Products

**User Story:** As a POS cashier, I want a modal to appear automatically when I select a weight-based product, so that I can quickly enter the exact weight without extra navigation.

#### Acceptance Criteria

1. WHEN a user selects a product with a weight-based unit, THE POS SHALL display the Quantity_Modal instead of adding directly to cart
2. WHEN the Quantity_Modal opens, THE System SHALL automatically focus the quantity input field
3. THE Quantity_Modal SHALL display the product name for reference
4. THE Quantity_Modal SHALL display the unit of measure (abbreviation)
5. THE Quantity_Modal SHALL display the unit price for reference
6. THE Quantity_Modal SHALL display the available stock for the product

### Requirement 3: Quantity Input Validation

**User Story:** As a POS cashier, I want the quantity input to validate my entries, so that I don't accidentally enter invalid quantities.

#### Acceptance Criteria

1. THE Quantity_Modal input SHALL accept decimal values with up to 3 decimal places (e.g., 1.5, 0.350, 2.125)
2. WHEN a user enters a quantity less than or equal to zero, THE System SHALL prevent adding to cart and display an error
3. WHEN a user enters a quantity exceeding available stock, THE System SHALL prevent adding to cart and display an error
4. THE System SHALL allow numeric input including decimal point/comma

### Requirement 4: Modal Confirmation and Cancellation

**User Story:** As a POS cashier, I want to confirm or cancel the quantity entry using keyboard shortcuts, so that I can work efficiently.

#### Acceptance Criteria

1. WHEN the user presses Enter in the Quantity_Modal, THE System SHALL confirm the quantity and add the product to cart
2. WHEN the user presses Escape in the Quantity_Modal, THE System SHALL close the modal without adding to cart
3. WHEN the user clicks outside the Quantity_Modal, THE System SHALL close the modal without adding to cart
4. WHEN the user clicks the "Agregar" button, THE System SHALL confirm the quantity and add the product to cart
5. WHEN the user clicks the "Cancelar" button, THE System SHALL close the modal without adding to cart
6. WHEN a product is successfully added to cart, THE System SHALL close the Quantity_Modal and return focus to product search

### Requirement 5: Unit-Based Products Behavior

**User Story:** As a POS cashier, I want unit-based products to add directly to cart with quantity 1, so that I don't have unnecessary extra steps for simple products.

#### Acceptance Criteria

1. WHEN a user selects a product with a unit-based measure (not weight-based), THE POS SHALL add it directly to cart with quantity 1
2. THE System SHALL preserve existing quantity adjustment functionality (+/- buttons) for all cart items
3. THE System SHALL preserve existing manual quantity editing functionality for all cart items

### Requirement 6: Barcode Scanner Integration

**User Story:** As a POS cashier, I want the quantity modal to work with barcode scanning, so that I can scan weight products and enter their quantity.

#### Acceptance Criteria

1. WHEN a barcode scan identifies a weight-based product, THE System SHALL display the Quantity_Modal
2. WHEN the Quantity_Modal is closed (confirmed or cancelled), THE System SHALL return focus to the barcode input field
