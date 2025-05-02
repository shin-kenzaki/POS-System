# POS Inventory System Database Documentation

## Overview
The POS Inventory System database (`pos_inventory`) is designed to support a comprehensive point-of-sale system with integrated inventory management. The database handles user authentication, product management, inventory tracking, sales transactions, customer management, supplier relationships, and purchase orders.

## Database Schema Diagram
```
┌───────────┐     ┌───────────┐     ┌───────────┐     ┌───────────┐
│   Users   │     │ Categories │     │ Products  │     │ Inventory │
└───────────┘     └───────────┘     └───────────┘     └───────────┘
      ▲                 ▲                ▲ │               │
      │                 │                │ │               │
      │                 │                │ │               │
      │                 │                │ ▼               ▼
┌───────────┐     ┌───────────┐     ┌───────────┐     ┌───────────┐
│   Sales   │     │ Sale Items │     │ Inventory │     │  Purchase │
│           │◄────┤           │◄────┤   Trans   │     │  Orders   │
└───────────┘     └───────────┘     └───────────┘     └───────────┘
      ▲                                                      │
      │                                                      │
      │                                                      ▼
┌───────────┐                                          ┌───────────┐
│ Customers │                                          │   P.O.    │
│           │                                          │   Items   │
└───────────┘                                          └───────────┘
                    ┌───────────┐     ┌───────────┐
                    │ Suppliers │     │ Payments  │
                    └───────────┘     └───────────┘
                         ▲                 │
                         │                 │
                         └─────────────────┘
```

## Detailed Table Descriptions

### 1. Users
Stores authentication information and details about employees who use the system.

**Columns:**
| Column Name | Data Type | Constraints | Description |
|-------------|-----------|------------|-------------|
| user_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier for each user |
| username | VARCHAR(50) | UNIQUE, NOT NULL | Login username |
| password | VARCHAR(255) | NOT NULL | Hashed password for authentication |
| full_name | VARCHAR(100) | NOT NULL | User's complete name |
| email | VARCHAR(100) | UNIQUE | User's email address |
| phone | VARCHAR(20) | | Contact phone number |
| role | ENUM | NOT NULL | One of: 'admin', 'manager', 'cashier', 'inventory' |
| is_active | BOOLEAN | DEFAULT TRUE | Whether user account is active |
| last_login | DATETIME | | Timestamp of last successful login |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation time |
| updated_at | TIMESTAMP | AUTO-UPDATE | Record last modification time |

**Role Descriptions:**
- **Admin**: Complete system access with user management capabilities
- **Manager**: Access to reports, some configuration settings, and operations approval
- **Cashier**: Access to sales processing and customer management
- **Inventory**: Stock management, receiving, and inventory adjustments

**User Security Notes:**
- Passwords are stored using bcrypt hashing algorithm
- Failed login attempts should be tracked in application logic
- Password reset functionality requires email validation

### 2. Categories
Organizes products into logical groupings for easier management and reporting.

**Columns:**
| Column Name | Data Type | Constraints | Description |
|-------------|-----------|------------|-------------|
| category_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier for each category |
| name | VARCHAR(50) | NOT NULL | Category name |
| description | TEXT | | Detailed description of the category |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation time |
| updated_at | TIMESTAMP | AUTO-UPDATE | Record last modification time |

**Business Rules:**
- Categories can be nested hierarchically through application logic
- Categories affect reporting and analytics grouping
- Default categories should include standard retail divisions (Electronics, Clothing, etc.)

### 3. Products
Core product information including pricing, identification and categorization.

**Columns:**
| Column Name | Data Type | Constraints | Description |
|-------------|-----------|------------|-------------|
| product_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier for each product |
| category_id | INT | FOREIGN KEY | Reference to categories table |
| name | VARCHAR(100) | NOT NULL | Product name |
| description | TEXT | | Detailed product description |
| sku | VARCHAR(50) | UNIQUE | Stock Keeping Unit - internal ID |
| barcode | VARCHAR(50) | UNIQUE | UPC/EAN/ISBN barcode for scanning |
| cost_price | DECIMAL(10,2) | NOT NULL | Wholesale/purchase price |
| selling_price | DECIMAL(10,2) | NOT NULL | Retail price to customers |
| tax_rate | DECIMAL(5,2) | DEFAULT 0 | Product-specific tax rate percentage |
| image_url | VARCHAR(255) | | Path/URL to product image |
| is_active | BOOLEAN | DEFAULT TRUE | Whether product is available for sale |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation time |
| updated_at | TIMESTAMP | AUTO-UPDATE | Record last modification time |

**Foreign Keys:**
- `category_id` references `categories(category_id)` with SET NULL on delete

**Business Rules:**
- Profit margin is calculated as (selling_price - cost_price) / cost_price * 100
- Products can be deactivated rather than deleted to maintain sales history
- SKU format should follow company-specific conventions (e.g., CAT-1234)
- Tax rates may vary by product category or type based on local regulations

### 4. Inventory
Tracks current stock levels and stock management thresholds.

**Columns:**
| Column Name | Data Type | Constraints | Description |
|-------------|-----------|------------|-------------|
| inventory_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier for inventory record |
| product_id | INT | FOREIGN KEY, NOT NULL | Reference to products table |
| quantity | INT | NOT NULL, DEFAULT 0 | Current stock quantity |
| min_stock_level | INT | DEFAULT 10 | Reorder point threshold |
| max_stock_level | INT | DEFAULT 100 | Maximum desired inventory |
| last_restock_date | DATETIME | | When product was last restocked |
| last_stock_count | DATETIME | | When inventory was last physically counted |
| location | VARCHAR(100) | | Storage location identifier |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation time |
| updated_at | TIMESTAMP | AUTO-UPDATE | Record last modification time |

**Foreign Keys:**
- `product_id` references `products(product_id)` with CASCADE on delete

**Business Rules:**
- Stock alerts are triggered when quantity falls below min_stock_level
- Inventory value can be calculated as quantity * cost_price
- FIFO (First-In-First-Out) or LIFO (Last-In-First-Out) accounting methods can be implemented through inventory transactions
- Regular stock counts should reconcile physical inventory with database records

### 5. Suppliers
Contains information about vendors who provide products.

**Columns:**
| Column Name | Data Type | Constraints | Description |
|-------------|-----------|------------|-------------|
| supplier_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier for each supplier |
| name | VARCHAR(100) | NOT NULL | Supplier company name |
| contact_person | VARCHAR(100) | | Primary contact individual |
| email | VARCHAR(100) | | Contact email address |
| phone | VARCHAR(20) | NOT NULL | Contact phone number |
| address | TEXT | | Physical address |
| tax_id | VARCHAR(50) | | Tax/VAT identification number |
| is_active | BOOLEAN | DEFAULT TRUE | Whether supplier relationship is active |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation time |
| updated_at | TIMESTAMP | AUTO-UPDATE | Record last modification time |

**Business Rules:**
- Suppliers can be associated with specific product categories
- Lead times and payment terms should be tracked in application logic
- Supplier performance metrics can include on-time delivery percentage and order accuracy

### 6. Customers
Stores information about shoppers for loyalty programs, credit accounts, and receipting.

**Columns:**
| Column Name | Data Type | Constraints | Description |
|-------------|-----------|------------|-------------|
| customer_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier for each customer |
| name | VARCHAR(100) | NOT NULL | Customer name |
| email | VARCHAR(100) | UNIQUE | Email address |
| phone | VARCHAR(20) | | Contact phone |
| address | TEXT | | Shipping/billing address |
| loyalty_points | INT | DEFAULT 0 | Rewards program points balance |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation time |
| updated_at | TIMESTAMP | AUTO-UPDATE | Record last modification time |

**Business Rules:**
- Customer accounts can be associated with special pricing or discounts
- Loyalty points typically accumulate at 1 point per currency unit spent
- Customer segmentation can be implemented based on purchase history
- Sales analysis should track customer lifetime value and purchase frequency

### 7. Sales
Records of completed transactions with customers.

**Columns:**
| Column Name | Data Type | Constraints | Description |
|-------------|-----------|------------|-------------|
| sale_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier for each sale |
| user_id | INT | FOREIGN KEY | Employee who processed sale |
| customer_id | INT | FOREIGN KEY | Associated customer (can be null for guest) |
| sale_date | DATETIME | DEFAULT CURRENT_TIMESTAMP | When sale occurred |
| subtotal | DECIMAL(10,2) | NOT NULL | Sum of all items before tax and discounts |
| tax_amount | DECIMAL(10,2) | DEFAULT 0 | Total tax collected |
| discount_amount | DECIMAL(10,2) | DEFAULT 0 | Total discounts applied |
| total_amount | DECIMAL(10,2) | NOT NULL | Final amount including tax and discounts |
| payment_method | ENUM | DEFAULT 'cash' | How payment was made |
| payment_status | ENUM | DEFAULT 'completed' | Current payment status |
| notes | TEXT | | Additional transaction information |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation time |
| updated_at | TIMESTAMP | AUTO-UPDATE | Record last modification time |

**Foreign Keys:**
- `user_id` references `users(user_id)` with SET NULL on delete
- `customer_id` references `customers(customer_id)` with SET NULL on delete

**Payment Methods:**
- cash
- credit_card
- debit_card
- mobile_payment
- other

**Payment Statuses:**
- pending
- completed
- failed
- refunded

**Business Rules:**
- Receipts should be generated with sale_id as reference
- Sales tax calculation varies by jurisdiction and product type
- Returns/refunds create negative inventory transactions
- Daily sales reconciliation compares POS totals with actual cash/payments

### 8. Sale Items
Individual products sold within a sales transaction.

**Columns:**
| Column Name | Data Type | Constraints | Description |
|-------------|-----------|------------|-------------|
| sale_item_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier for each line item |
| sale_id | INT | FOREIGN KEY, NOT NULL | Reference to parent sale |
| product_id | INT | FOREIGN KEY, NOT NULL | Product sold |
| quantity | INT | NOT NULL | Number of units sold |
| unit_price | DECIMAL(10,2) | NOT NULL | Price per unit at time of sale |
| discount | DECIMAL(10,2) | DEFAULT 0 | Discount amount per item |
| tax_amount | DECIMAL(10,2) | DEFAULT 0 | Tax collected per item |
| subtotal | DECIMAL(10,2) | NOT NULL | Line total (quantity * unit_price - discount) |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation time |

**Foreign Keys:**
- `sale_id` references `sales(sale_id)` with CASCADE on delete
- `product_id` references `products(product_id)` with RESTRICT on delete

**Business Rules:**
- Unit price may differ from current product selling_price due to promotions or price changes
- Quantity reduce inventory through inventory_transactions
- Line item tax calculation may use product-specific tax rates
- Discounts can be percentage-based or fixed amounts

### 9. Purchase Orders
Records orders placed with suppliers for inventory replenishment.

**Columns:**
| Column Name | Data Type | Constraints | Description |
|-------------|-----------|------------|-------------|
| po_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier for purchase order |
| supplier_id | INT | FOREIGN KEY, NOT NULL | Supplier providing products |
| user_id | INT | FOREIGN KEY | Employee who created order |
| order_date | DATETIME | DEFAULT CURRENT_TIMESTAMP | When order was placed |
| expected_delivery_date | DATE | | Anticipated delivery date |
| status | ENUM | DEFAULT 'pending' | Current order status |
| total_amount | DECIMAL(10,2) | NOT NULL | Total order cost |
| payment_status | ENUM | DEFAULT 'unpaid' | Current payment status |
| notes | TEXT | | Additional order information |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation time |
| updated_at | TIMESTAMP | AUTO-UPDATE | Record last modification time |

**Foreign Keys:**
- `supplier_id` references `suppliers(supplier_id)` with RESTRICT on delete
- `user_id` references `users(user_id)` with SET NULL on delete

**Order Statuses:**
- pending
- approved
- ordered
- received
- cancelled

**Payment Statuses:**
- unpaid
- partially_paid
- paid

**Business Rules:**
- Purchase orders require approval workflow before submission to suppliers
- Partially received orders maintain 'ordered' status until fully received
- Receiving process creates inventory transactions to increase stock
- Order-to-delivery time metrics help evaluate supplier performance

### 10. Purchase Order Items
Individual products ordered within a purchase order.

**Columns:**
| Column Name | Data Type | Constraints | Description |
|-------------|-----------|------------|-------------|
| po_item_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier for each line item |
| po_id | INT | FOREIGN KEY, NOT NULL | Reference to parent purchase order |
| product_id | INT | FOREIGN KEY, NOT NULL | Product being ordered |
| quantity | INT | NOT NULL | Quantity ordered |
| unit_cost | DECIMAL(10,2) | NOT NULL | Cost per unit |
| received_quantity | INT | DEFAULT 0 | Quantity received so far |
| subtotal | DECIMAL(10,2) | NOT NULL | Line total (quantity * unit_cost) |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation time |

**Foreign Keys:**
- `po_id` references `purchase_orders(po_id)` with CASCADE on delete
- `product_id` references `products(product_id)` with RESTRICT on delete

**Business Rules:**
- Partially received items update product cost based on weighted average
- Backordered items maintain the difference between quantity and received_quantity
- Unit costs may be used to update product cost_price on receipt
- Variance between ordered and received quantities must be approved

### 11. Inventory Transactions
Detailed audit trail of all stock movements.

**Columns:**
| Column Name | Data Type | Constraints | Description |
|-------------|-----------|------------|-------------|
| transaction_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier for transaction |
| product_id | INT | FOREIGN KEY, NOT NULL | Affected product |
| user_id | INT | FOREIGN KEY | Employee responsible for transaction |
| transaction_type | ENUM | NOT NULL | Type of inventory movement |
| reference_id | INT | | ID from originating transaction |
| quantity_change | INT | NOT NULL | Net quantity change (+ or -) |
| before_quantity | INT | NOT NULL | Stock level before transaction |
| after_quantity | INT | NOT NULL | Stock level after transaction |
| transaction_date | DATETIME | DEFAULT CURRENT_TIMESTAMP | When transaction occurred |
| notes | TEXT | | Additional information |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation time |

**Foreign Keys:**
- `product_id` references `products(product_id)` with CASCADE on delete
- `user_id` references `users(user_id)` with SET NULL on delete

**Transaction Types:**
- purchase
- sale
- return
- adjustment
- transfer
- stock_count

**Business Rules:**
- Every inventory change must be recorded with a transaction
- Reference_id links to source transactions (e.g., sale_id for sales)
- Negative adjustments below zero inventory should trigger warnings
- Regular reconciliation between physical counts and database records

### 12. Payments
Records of financial transactions for sales.

**Columns:**
| Column Name | Data Type | Constraints | Description |
|-------------|-----------|------------|-------------|
| payment_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier for payment |
| sale_id | INT | FOREIGN KEY | Related sale transaction |
| amount | DECIMAL(10,2) | NOT NULL | Payment amount |
| payment_method | ENUM | NOT NULL | Method of payment |
| payment_date | DATETIME | DEFAULT CURRENT_TIMESTAMP | When payment was processed |
| payment_reference | VARCHAR(100) | | Transaction ID, check number, etc. |
| notes | TEXT | | Additional payment details |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation time |
| created_by | INT | FOREIGN KEY | Employee who processed payment |

**Foreign Keys:**
- `sale_id` references `sales(sale_id)` with CASCADE on delete
- `created_by` references `users(user_id)` with SET NULL on delete

**Payment Methods:**
- cash
- credit_card
- debit_card
- mobile_payment
- other

**Business Rules:**
- Multiple payments may be applied to a single sale
- Payment total must equal or exceed sale total
- Change is calculated as payment amount - sale total (if payment > total)
- Credit card payments should store last 4 digits and authorization codes

### 13. Settings
System-wide configuration options.

**Columns:**
| Column Name | Data Type | Constraints | Description |
|-------------|-----------|------------|-------------|
| setting_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier for setting |
| setting_key | VARCHAR(50) | UNIQUE, NOT NULL | Setting identifier |
| setting_value | TEXT | | Setting value |
| description | TEXT | | Explanation of setting purpose |
| updated_at | TIMESTAMP | AUTO-UPDATE | Record last modification time |

**Default Settings:**
- store_name: "My POS Store"
- store_address: "123 Main St, City, Country" 
- store_phone: "555-123-4567"
- tax_rate: "7.5"
- currency: "USD" 
- receipt_footer: "Thank you for shopping with us!"

**Business Rules:**
- Changes to critical settings should be logged in audit trail
- Settings provide centralized configuration without code changes
- UI should provide administrative interface for settings management

## Database Relationships Explained

### One-to-Many Relationships
1. **Categories to Products**: 
   - One category can contain multiple products
   - Products table has foreign key `category_id` referencing categories

2. **Users to Sales**:
   - One user (employee) can process multiple sales
   - Sales table has foreign key `user_id` referencing users

3. **Customers to Sales**:
   - One customer can have multiple sales transactions
   - Sales table has foreign key `customer_id` referencing customers

4. **Suppliers to Purchase Orders**:
   - One supplier can receive multiple purchase orders
   - Purchase_orders table has foreign key `supplier_id` referencing suppliers

### One-to-One Relationships
1. **Products to Inventory**:
   - Each product has one inventory record
   - Inventory table has foreign key `product_id` referencing products
   - Note: This is technically implemented as one-to-many but business logic enforces one-to-one

### Many-to-Many Relationships (implemented with junction tables)
1. **Products to Sales** (via sale_items):
   - Products can appear in multiple sales
   - Sales can contain multiple products
   - sale_items junction table connects them with quantities and prices

2. **Products to Purchase Orders** (via purchase_order_items):
   - Products can be ordered in multiple purchase orders
   - Purchase orders can contain multiple products
   - purchase_order_items junction table connects them with quantities and costs

## Database Triggers and Procedures

### Potential Triggers:
1. **After Insert on sale_items**:
   - Automatically reduce inventory quantity
   - Create inventory transaction record
   - Update product statistics

2. **After Insert on purchase_order_items** (when received):
   - Automatically increase inventory quantity
   - Create inventory transaction record
   - Update product cost averaging if needed

3. **After Update on products**:
   - Log price changes for audit purposes

### Suggested Stored Procedures:
1. **GenerateDailySalesReport**:
   - Create summary of daily transactions
   - Calculate total sales, tax, discounts

2. **IdentifyLowStockItems**:
   - Find all products below reorder threshold
   - Group by supplier for easy reordering

3. **CalculateInventoryValue**:
   - Sum current inventory * cost price
   - Group by category for financial reporting

## Data Integrity Rules

1. **Cascade Delete**:
   - When a sale is deleted, all related sale_items are deleted
   - When a purchase order is deleted, all related purchase_order_items are deleted
   - When a product is deleted, all related inventory records are deleted

2. **Restrict Delete**:
   - Products cannot be deleted if they appear in sale_items or purchase_order_items
   - Suppliers cannot be deleted if they have associated purchase_orders

3. **Set NULL on Delete**:
   - When a user is deleted, related sales and purchase_orders preserve data but lose user association
   - When a category is deleted, products remain but become uncategorized

## Performance Optimization

### Recommended Indexes:
1. Products table:
   - barcode
   - sku
   - category_id

2. Inventory table:
   - product_id
   - quantity (for low stock queries)

3. Sales table:
   - sale_date
   - customer_id
   - payment_status

4. Purchase_orders table:
   - order_date
   - supplier_id
   - status

### Query Optimization:
1. Reporting queries should use date ranges and appropriate indexing
2. Product lookups should utilize barcode/SKU indexes
3. Consider partitioning large tables like inventory_transactions by date ranges

## Backup and Maintenance

### Backup Strategy:
1. Daily full database backup
2. Transaction log backups every hour during business hours
3. Monthly archived backups for long-term storage

### Maintenance Tasks:
1. Regular indexing optimization
2. Archiving of old transaction data
3. Periodic integrity checks for data validation

## Security Considerations

1. **Access Control**:
   - Implement role-based permissions using user.role
   - PII (Personally Identifiable Information) in customers table should be encrypted
   - Payment information should never be stored in plaintext

2. **Audit Trail**:
   - All inventory adjustments require user_id for accountability
   - Price changes and discounts should be traceable to specific users

3. **Authentication**:
   - Password hashing uses secure algorithm
   - Failed login attempts should be tracked and limited

## Sample Queries

### Product Inventory Status
```sql
SELECT 
    p.product_id, 
    p.name, 
    p.sku, 
    i.quantity,
    i.min_stock_level,
    CASE 
        WHEN i.quantity <= i.min_stock_level THEN 'Reorder'
        WHEN i.quantity <= (i.min_stock_level * 1.5) THEN 'Low Stock'
        ELSE 'In Stock'
    END AS stock_status
FROM 
    products p
JOIN 
    inventory i ON p.product_id = i.product_id
WHERE 
    p.is_active = TRUE
ORDER BY 
    stock_status ASC, p.name ASC;
```

### Sales Summary by Date Range
```sql
SELECT 
    DATE(s.sale_date) AS sale_day,
    COUNT(DISTINCT s.sale_id) AS transaction_count,
    SUM(s.total_amount) AS daily_total,
    SUM(s.tax_amount) AS tax_collected,
    AVG(s.total_amount) AS average_sale
FROM 
    sales s
WHERE 
    s.sale_date BETWEEN '2023-01-01' AND '2023-01-31'
GROUP BY 
    DATE(s.sale_date)
ORDER BY 
    sale_day;
```

### Top Selling Products
```sql
SELECT 
    p.product_id,
    p.name,
    p.category_id,
    c.name AS category,
    SUM(si.quantity) AS units_sold,
    SUM(si.subtotal) AS revenue,
    SUM(si.subtotal - (si.quantity * p.cost_price)) AS gross_profit
FROM 
    products p
JOIN 
    sale_items si ON p.product_id = si.product_id
JOIN 
    sales s ON si.sale_id = s.sale_id
JOIN 
    categories c ON p.category_id = c.category_id
WHERE 
    s.sale_date BETWEEN '2023-01-01' AND '2023-12-31'
GROUP BY 
    p.product_id, p.name, p.category_id, c.name
ORDER BY 
    units_sold DESC
LIMIT 20;
```

## Conclusion

The POS Inventory database is designed as a complete solution for retail operations, supporting all critical business functions from inventory management to customer engagement. The relational design ensures data integrity while maintaining flexibility for business growth and changing requirements.

Key highlights:
- Complete separation of concerns between sales, inventory, and purchasing
- Comprehensive audit trail for all inventory movements
- Flexible product categorization and supplier management
- Support for customer loyalty programs and detailed sales analysis
- Configuration through settings table rather than hard-coded values
