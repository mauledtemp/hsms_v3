-- ========================================================================
-- SAMPLE DATA FOR HARDWARE STORE MANAGEMENT SYSTEM
-- Run this in phpMyAdmin to populate your database with test data
-- ========================================================================

USE hsms;

-- ========================================================================
-- 1. INSERT SAMPLE PRODUCTS (Hardware Store Items)
-- ========================================================================

INSERT INTO products (product_code, product_name, category, unit, buying_price, selling_price, stock_quantity, reorder_level, supplier, status) VALUES
-- Nails & Screws
('P001', 'Misumari 2 Inch', 'Nails & Screws', 'pcs', 500.00, 800.00, 300, 50, 'Steel Supplies Ltd', 'active'),
('P002', 'Misumari 3 Inch', 'Nails & Screws', 'pcs', 600.00, 950.00, 250, 50, 'Steel Supplies Ltd', 'active'),
('P003', 'Paku 1.5 Inch', 'Nails & Screws', 'pcs', 300.00, 500.00, 400, 80, 'Steel Supplies Ltd', 'active'),
('P004', 'Wood Screws Set', 'Nails & Screws', 'box', 2500.00, 4000.00, 50, 10, 'Steel Supplies Ltd', 'active'),

-- Cement & Building Materials
('P005', 'Cement 50kg', 'Building Materials', 'bag', 15000.00, 18000.00, 100, 20, 'Tanga Cement', 'active'),
('P006', 'Sand (Fine)', 'Building Materials', 'ton', 25000.00, 32000.00, 15, 5, 'Local Suppliers', 'active'),
('P007', 'Ballast', 'Building Materials', 'ton', 28000.00, 35000.00, 12, 5, 'Local Suppliers', 'active'),
('P008', 'Bricks (Red)', 'Building Materials', 'pcs', 300.00, 450.00, 5000, 1000, 'Mbeya Bricks', 'active'),

-- Paint & Finishing
('P009', 'White Paint 4L', 'Paint', 'tin', 12000.00, 16500.00, 80, 15, 'Crown Paints', 'active'),
('P010', 'Blue Paint 4L', 'Paint', 'tin', 12000.00, 16500.00, 60, 15, 'Crown Paints', 'active'),
('P011', 'Paint Brush Small', 'Paint', 'pcs', 1500.00, 2500.00, 150, 30, 'Various', 'active'),
('P012', 'Paint Brush Large', 'Paint', 'pcs', 3000.00, 4500.00, 100, 20, 'Various', 'active'),
('P013', 'Paint Roller', 'Paint', 'pcs', 2500.00, 3800.00, 75, 15, 'Various', 'active'),

-- Tools
('P014', 'Hammer 500g', 'Tools', 'pcs', 8000.00, 12000.00, 45, 10, 'Stanley Tools', 'active'),
('P015', 'Screwdriver Set', 'Tools', 'set', 15000.00, 22000.00, 30, 8, 'Stanley Tools', 'active'),
('P016', 'Measuring Tape 5m', 'Tools', 'pcs', 3500.00, 5500.00, 60, 15, 'Various', 'active'),
('P017', 'Spirit Level', 'Tools', 'pcs', 6000.00, 9000.00, 25, 8, 'Various', 'active'),
('P018', 'Hand Saw', 'Tools', 'pcs', 10000.00, 15000.00, 20, 5, 'Stanley Tools', 'active'),

-- Electrical
('P019', 'Light Bulb LED 15W', 'Electrical', 'pcs', 3000.00, 4500.00, 200, 40, 'Philips', 'active'),
('P020', 'Extension Cable 5m', 'Electrical', 'pcs', 8000.00, 12000.00, 50, 10, 'Various', 'active'),
('P021', 'Socket 3-Way', 'Electrical', 'pcs', 2000.00, 3200.00, 80, 20, 'Various', 'active'),
('P022', 'Wire 2.5mm (per meter)', 'Electrical', 'meter', 500.00, 800.00, 500, 100, 'Kabelmetal', 'active'),

-- Plumbing
('P023', 'PVC Pipe 1/2 inch', 'Plumbing', 'meter', 1500.00, 2500.00, 150, 30, 'Borealis', 'active'),
('P024', 'PVC Pipe 3/4 inch', 'Plumbing', 'meter', 2000.00, 3200.00, 120, 25, 'Borealis', 'active'),
('P025', 'Tap Faucet', 'Plumbing', 'pcs', 5000.00, 8000.00, 40, 10, 'Various', 'active'),
('P026', 'Pipe Joints Set', 'Plumbing', 'set', 3000.00, 5000.00, 60, 15, 'Various', 'active'),

-- Safety Equipment
('P027', 'Work Gloves', 'Safety', 'pair', 2000.00, 3500.00, 100, 25, 'Safety First', 'active'),
('P028', 'Safety Goggles', 'Safety', 'pcs', 3500.00, 5500.00, 50, 15, 'Safety First', 'active'),
('P029', 'Dust Mask (Pack of 5)', 'Safety', 'pack', 4000.00, 6500.00, 80, 20, 'Safety First', 'active'),
('P030', 'Hard Hat', 'Safety', 'pcs', 8000.00, 12000.00, 30, 8, 'Safety First', 'active');

-- ========================================================================
-- 2. INSERT SAMPLE SALES DATA (Last 30 Days)
-- ========================================================================

-- Note: These sales are backdated to show graph trends
-- Adjust user_id if your admin user has a different ID

-- Sales from 30 days ago
INSERT INTO sales (sale_number, user_id, customer_name, total_amount, total_cost, total_profit, payment_method, sale_date) VALUES
('SALE-20241023-A1B2C3', 1, 'John Mkamba', 54000.00, 42000.00, 12000.00, 'cash', DATE_SUB(CURDATE(), INTERVAL 30 DAY)),
('SALE-20241023-D4E5F6', 1, 'Walk-in Customer', 28500.00, 21000.00, 7500.00, 'cash', DATE_SUB(CURDATE(), INTERVAL 30 DAY));

-- Sales from 25 days ago
INSERT INTO sales (sale_number, user_id, customer_name, total_amount, total_cost, total_profit, payment_method, sale_date) VALUES
('SALE-20241028-G7H8I9', 1, 'Mary Ntiro', 72000.00, 60000.00, 12000.00, 'card', DATE_SUB(CURDATE(), INTERVAL 25 DAY)),
('SALE-20241028-J1K2L3', 1, 'Safari Builders', 145000.00, 110000.00, 35000.00, 'cash', DATE_SUB(CURDATE(), INTERVAL 25 DAY));

-- Sales from 20 days ago
INSERT INTO sales (sale_number, user_id, customer_name, total_amount, total_cost, total_profit, payment_method, sale_date) VALUES
('SALE-20241102-M4N5O6', 1, 'Walk-in Customer', 36000.00, 28000.00, 8000.00, 'cash', DATE_SUB(CURDATE(), INTERVAL 20 DAY)),
('SALE-20241102-P7Q8R9', 1, 'Juma Construction', 98000.00, 75000.00, 23000.00, 'mobile', DATE_SUB(CURDATE(), INTERVAL 20 DAY));

-- Sales from 15 days ago
INSERT INTO sales (sale_number, user_id, customer_name, total_amount, total_cost, total_profit, payment_method, sale_date) VALUES
('SALE-20241107-S1T2U3', 1, 'Hamisi Rajabu', 45000.00, 35000.00, 10000.00, 'cash', DATE_SUB(CURDATE(), INTERVAL 15 DAY)),
('SALE-20241107-V4W5X6', 1, 'Walk-in Customer', 19500.00, 15000.00, 4500.00, 'cash', DATE_SUB(CURDATE(), INTERVAL 15 DAY)),
('SALE-20241107-Y7Z8A9', 1, 'Grace Mwita', 67000.00, 52000.00, 15000.00, 'card', DATE_SUB(CURDATE(), INTERVAL 15 DAY));

-- Sales from 10 days ago
INSERT INTO sales (sale_number, user_id, customer_name, total_amount, total_cost, total_profit, payment_method, sale_date) VALUES
('SALE-20241112-B1C2D3', 1, 'Baraka Hardware', 156000.00, 120000.00, 36000.00, 'cash', DATE_SUB(CURDATE(), INTERVAL 10 DAY)),
('SALE-20241112-E4F5G6', 1, 'Walk-in Customer', 28000.00, 22000.00, 6000.00, 'cash', DATE_SUB(CURDATE(), INTERVAL 10 DAY));

-- Sales from 7 days ago
INSERT INTO sales (sale_number, user_id, customer_name, total_amount, total_cost, total_profit, payment_method, sale_date) VALUES
('SALE-20241115-H7I8J9', 1, 'Abdul Juma', 84000.00, 65000.00, 19000.00, 'mobile', DATE_SUB(CURDATE(), INTERVAL 7 DAY)),
('SALE-20241115-K1L2M3', 1, 'Neema Stores', 52000.00, 40000.00, 12000.00, 'cash', DATE_SUB(CURDATE(), INTERVAL 7 DAY));

-- Sales from 5 days ago
INSERT INTO sales (sale_number, user_id, customer_name, total_amount, total_cost, total_profit, payment_method, sale_date) VALUES
('SALE-20241117-N4O5P6', 1, 'Walk-in Customer', 31500.00, 24000.00, 7500.00, 'cash', DATE_SUB(CURDATE(), INTERVAL 5 DAY)),
('SALE-20241117-Q7R8S9', 1, 'Francis Mdoe', 76000.00, 58000.00, 18000.00, 'card', DATE_SUB(CURDATE(), INTERVAL 5 DAY));

-- Sales from 3 days ago
INSERT INTO sales (sale_number, user_id, customer_name, total_amount, total_cost, total_profit, payment_method, sale_date) VALUES
('SALE-20241119-T1U2V3', 1, 'Building Experts Co', 189000.00, 145000.00, 44000.00, 'cash', DATE_SUB(CURDATE(), INTERVAL 3 DAY)),
('SALE-20241119-W4X5Y6', 1, 'Walk-in Customer', 42000.00, 33000.00, 9000.00, 'mobile', DATE_SUB(CURDATE(), INTERVAL 3 DAY));

-- Sales from yesterday
INSERT INTO sales (sale_number, user_id, customer_name, total_amount, total_cost, total_profit, payment_method, sale_date) VALUES
('SALE-20241121-Z7A8B9', 1, 'Anna Komba', 58000.00, 45000.00, 13000.00, 'cash', DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
('SALE-20241121-C1D2E3', 1, 'Walk-in Customer', 25500.00, 20000.00, 5500.00, 'cash', DATE_SUB(CURDATE(), INTERVAL 1 DAY));

-- Sales from today
INSERT INTO sales (sale_number, user_id, customer_name, total_amount, total_cost, total_profit, payment_method, sale_date) VALUES
('SALE-20241122-F4G5H6', 1, 'Daudi Mpanda', 95000.00, 72000.00, 23000.00, 'card', CURDATE()),
('SALE-20241122-I7J8K9', 1, 'Walk-in Customer', 38500.00, 30000.00, 8500.00, 'cash', CURDATE());

-- ========================================================================
-- 3. INSERT SAMPLE SALE ITEMS (for the sales above)
-- ========================================================================

-- Sale 1 items (30 days ago)
INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, buying_price, subtotal, profit) VALUES
(1, 5, 3, 18000.00, 15000.00, 54000.00, 9000.00);

-- Sale 2 items (30 days ago)
INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, buying_price, subtotal, profit) VALUES
(2, 9, 1, 16500.00, 12000.00, 16500.00, 4500.00),
(2, 14, 1, 12000.00, 8000.00, 12000.00, 4000.00);

-- Sale 3 items (25 days ago)
INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, buying_price, subtotal, profit) VALUES
(3, 5, 4, 18000.00, 15000.00, 72000.00, 12000.00);

-- Sale 4 items (25 days ago)
INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, buying_price, subtotal, profit) VALUES
(4, 6, 2, 32000.00, 25000.00, 64000.00, 14000.00),
(4, 7, 1, 35000.00, 28000.00, 35000.00, 7000.00),
(4, 8, 100, 450.00, 300.00, 45000.00, 15000.00);

-- Continue pattern for remaining sales...
-- Sale 5-20 (simplified to keep file manageable)

-- Recent sale (today)
INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, buying_price, subtotal, profit) VALUES
(19, 5, 5, 18000.00, 15000.00, 90000.00, 15000.00),
(19, 11, 2, 2500.00, 1500.00, 5000.00, 2000.00);

INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, buying_price, subtotal, profit) VALUES
(20, 14, 2, 12000.00, 8000.00, 24000.00, 8000.00),
(20, 16, 1, 5500.00, 3500.00, 5500.00, 2000.00),
(20, 19, 2, 4500.00, 3000.00, 9000.00, 3000.00);

-- ========================================================================
-- SUMMARY
-- ========================================================================
-- Products: 30 hardware items with realistic pricing
-- Sales: 20 sales spread over last 30 days
-- Total Revenue Generated: ~1,500,000 TZS
-- Total Profit Generated: ~350,000 TZS
-- 
-- After running this:
-- 1. Go to Dashboard - you'll see sales and profit data
-- 2. Click different period buttons to see graph trends
-- 3. POS will have 30 products ready to sell
-- 4. Inventory will show stock levels
-- ========================================================================