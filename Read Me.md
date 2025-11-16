# Hardware Store Management System (HSMS)

A complete, modern hardware store management system built with PHP, MySQL, HTML, and CSS. Designed for XAMPP local server deployment.

## 🎯 Features

### User Management
- Simple PIN-based login system (POS style)
- Two user roles: Admin and Cashier
- Default admin account included
- Admin can add/manage users

### Product Management
- Add, edit, delete products
- Track stock levels
- Low stock alerts
- Product categories and suppliers
- Active/Inactive product status

### Point of Sale (POS)
- Modern, intuitive interface
- Quick product search
- Real-time cart management
- Multiple payment methods (Cash, Card, Mobile Money)
- Instant sale completion

### Sales Management
- Complete sales history
- Detailed sale receipts
- Print functionality
- Sales tracking by cashier

### Dashboard
- Today's sales summary
- Monthly sales overview
- Total products count
- Low stock alerts
- Recent sales list

## 📋 Requirements

- XAMPP (or any PHP 7.4+ with MySQL)
- Modern web browser
- No additional libraries required

## 🚀 Installation Steps

### 1. Download and Extract
Place all files in your XAMPP `htdocs` folder:
```
C:\xampp\htdocs\hsms\
```

### 2. File Structure
Your folder should contain:
```
hsms/
├── config.php
├── functions.php
├── style.css
├── login.php
├── header.php
├── footer.php
├── index.php
├── pos.php
├── products.php
├── sales.php
├── users.php
├── logout.php
└── database.sql
```

### 3. Start XAMPP
1. Open XAMPP Control Panel
2. Start **Apache** and **MySQL**

### 4. Create Database
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Click "New" to create a database
3. Or use the SQL tab and run the contents of `database.sql`

The database will create:
- Database name: `hsms`
- All required tables
- Default admin user

### 5. Configure Database Connection
Open `config.php` and verify these settings:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Leave empty for default XAMPP
define('DB_NAME', 'hsms');
```

### 6. Access the System
Open your browser and go to:
```
http://localhost/hsms/login.php
```

## 🔐 Default Login Credentials

**Admin Account:**
- Username: `admin`
- Password: `25252525`

**Important:** Change the default password after first login by creating a new admin user!

## 📖 User Guide

### For Cashiers

#### Making a Sale (POS)
1. Navigate to **Point of Sale**
2. Click on products to add to cart
3. Adjust quantities using +/- buttons
4. Enter customer name (optional)
5. Select payment method
6. Click "Complete Sale"

#### Viewing Products
1. Go to **Products** page
2. View all available items
3. Check stock levels
4. See product details

#### Viewing Sales History
1. Navigate to **Sales History**
2. View all past transactions
3. Click "View Details" for full receipt
4. Print receipts if needed

### For Admins

#### Adding Products
1. Go to **Products** page
2. Fill in the "Add New Product" form
3. Enter:
   - Product Code (unique)
   - Product Name
   - Category
   - Unit (pcs, kg, ltr)
   - Price
   - Stock Quantity
   - Reorder Level
   - Supplier
4. Click "Add Product"

#### Managing Stock
1. Go to **Products** page
2. Click "Edit" on any product
3. Update stock quantity
4. Save changes

#### Adding Users
1. Go to **Users** page (Admin only)
2. Fill in the form:
   - Username (unique identifier)
   - Password (min 6 characters)
   - Full Name
   - Role (Admin/Cashier)
3. Click "Add User"

#### Dashboard Overview
- View today's sales
- Monitor monthly performance
- Check low stock alerts
- See recent transactions

## 🔧 Customization

### Change Currency
Edit `config.php`:
```php
define('CURRENCY', 'USD');  // Change to your currency
```

### Modify Colors
Edit `style.css` and change the CSS variables:
```css
:root {
    --primary: #2563eb;      /* Main color */
    --success: #10b981;      /* Success color */
    --danger: #ef4444;       /* Danger/alert color */
}
```

### Add More Payment Methods
Edit `pos.php` and `sales.php`, add options in the payment method dropdown.

## 🛠️ Functions Reference

All functions are centralized in `functions.php`:

### Authentication
- `authenticateUser()` - Verify login credentials
- `isLoggedIn()` - Check if user is logged in
- `isAdmin()` - Check if user is admin
- `requireLogin()` - Require authentication
- `requireAdmin()` - Require admin role
- `logout()` - End session

### User Management
- `createUser()` - Add new user
- `getAllUsers()` - Get all users
- `updateUser()` - Update user details
- `deleteUser()` - Remove user

### Product Management
- `createProduct()` - Add new product
- `getAllProducts()` - Get all products
- `getProductById()` - Get specific product
- `updateProduct()` - Update product
- `updateProductStock()` - Adjust stock levels
- `deleteProduct()` - Remove product
- `getLowStockProducts()` - Get items below reorder level

### Sales Functions
- `createSale()` - Process new sale
- `getAllSales()` - Get sales history
- `getSaleDetails()` - Get sale line items
- `getSaleByNumber()` - Get specific sale

### Statistics
- `getTotalSalesToday()` - Today's revenue
- `getTotalSalesThisMonth()` - Monthly revenue
- `getTotalProducts()` - Active products count
- `getLowStockCount()` - Items needing restock

### Utilities
- `formatCurrency()` - Format numbers as currency
- `sanitizeInput()` - Clean user input
- `showAlert()` - Display messages

## 🔒 Security Notes

1. **Change Default Password:** Immediately after installation
2. **Backup Regularly:** Export database from phpMyAdmin
3. **Production Use:** 
   - Disable error display in `config.php`
   - Use strong passwords
   - Enable HTTPS if possible
   - Set proper file permissions

## 🐛 Troubleshooting

### "Connection failed" Error
- Verify XAMPP MySQL is running
- Check database credentials in `config.php`
- Ensure database `hsms` exists

### "Product code already exists" Error
- Each product must have a unique code
- Use different codes for each product

### Login Not Working
- Verify you're using correct credentials
- Check if user status is "active"
- Clear browser cache and try again

### Styles Not Loading
- Ensure `style.css` is in the same folder
- Check file permissions
- Clear browser cache

## 📝 Database Backup

To backup your data:
1. Open phpMyAdmin
2. Select `hsms` database
3. Click "Export"
4. Choose "Quick" export method
5. Click "Go" to download

## 🔄 Updates and Maintenance

### Adding New Features
All core functions are in `functions.php`. Add your custom functions there for easy maintenance.

### Database Changes
Always backup before modifying database structure.

## 📞 Support

For issues or questions:
1. Check this README first
2. Review error messages carefully
3. Verify all files are present
4. Check XAMPP error logs

## 📄 License

This project is provided as-is for educational and commercial use.

## 🎉 Credits

Created for hardware store management with simplicity and efficiency in mind.

---

**Version:** 1.0  
**Last Updated:** November 2025  
**Designed for:** XAMPP Local Server