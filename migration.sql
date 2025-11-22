-- Migration Script: Add Buying/Selling Price and Stock Movements
-- Run this ONLY if you already have an existing database

USE hsms;

-- Step 1: Backup your data first!

-- Step 2: Add new columns to products table
ALTER TABLE products 
ADD COLUMN buying_price DECIMAL(10,2) DEFAULT 0 AFTER unit,
ADD COLUMN selling_price DECIMAL(10,2) DEFAULT 0 AFTER buying_price;

-- Step 3: Copy old price to selling_price
UPDATE products SET selling_price = price WHERE selling_price = 0;

-- Step 4: Remove old price column (optional, uncomment if you want to remove it)
-- ALTER TABLE products DROP COLUMN price;

-- Step 5: Create stock_movements table
CREATE TABLE IF NOT EXISTS stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    user_id INT,
    movement_type ENUM('purchase', 'sale', 'adjustment') NOT NULL,
    quantity INT NOT NULL,
    buying_price DECIMAL(10,2),
    reference_number VARCHAR(100),
    notes TEXT,
    movement_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Done! Your database is now updated.