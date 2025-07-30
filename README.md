# B2B Commerce Pro - WordPress Plugin

A comprehensive B2B and wholesale e-commerce solution for WordPress and WooCommerce.

## 🚀 Features

### ✅ **User Management**
- **B2B User Registration**: Custom registration form with business information
- **User Approval System**: Admin approval workflow for new B2B accounts
- **Role-based Access**: Different user roles (Wholesale, Distributor, Retailer)
- **Bulk Actions**: Approve/reject multiple users at once
- **User Export**: Export user data to CSV format

### ✅ **Pricing Management**
- **Wholesale Pricing**: Set different pricing for B2B customers
- **Volume Discounts**: Quantity-based pricing rules
- **Customer Group Pricing**: Role-specific pricing
- **Pricing Rules Management**: Easy-to-use admin interface

### ✅ **Order Management**
- **B2B Order Tracking**: Monitor B2B orders separately
- **Order History**: Complete order history for B2B customers
- **Invoice Generation**: Automatic invoice creation
- **Order Status Tracking**: Real-time order status updates

### ✅ **Dashboard & Analytics**
- **Admin Dashboard**: Comprehensive overview with statistics
- **User Statistics**: Total users, pending approvals, revenue
- **Recent Orders**: Quick view of latest B2B orders
- **Performance Metrics**: Key business metrics at a glance

### ✅ **Email Templates**
- **Customizable Templates**: Approval, rejection, and notification emails
- **Template Management**: Easy template editing in admin
- **Automated Notifications**: Email notifications for status changes

### ✅ **Frontend Features**
- **B2B Registration Form**: `[b2b_registration]` shortcode
- **B2B Dashboard**: `[b2b_dashboard]` shortcode
- **Order History**: `[b2b_order_history]` shortcode
- **Account Management**: `[b2b_account]` shortcode

## 📋 Installation

1. **Upload Plugin**: Upload the `b2b-commerce-pro` folder to `/wp-content/plugins/`
2. **Activate Plugin**: Activate through the 'Plugins' menu in WordPress
3. **Configure Settings**: Go to B2B Commerce Pro → Settings
4. **Add Shortcodes**: Use shortcodes on pages for frontend functionality

## 🎯 Quick Start

### 1. **User Registration**
Add this shortcode to any page:
```
[b2b_registration]
```

### 2. **B2B Dashboard**
Add this shortcode to a protected page:
```
[b2b_dashboard]
```

### 3. **Admin Management**
Navigate to **B2B Commerce Pro** in your WordPress admin menu.

## 🔧 Configuration

### **User Roles**
The plugin creates these user roles:
- `wholesale_customer` - Wholesale customers
- `distributor` - Distributors
- `retailer` - Retailers
- `b2b_customer` - General B2B customers

### **Approval Process**
1. Users register via the frontend form
2. Admin receives notification of pending approval
3. Admin approves/rejects users from B2B Commerce Pro → Users
4. Users receive email notification of approval status

### **Pricing Setup**
1. Go to B2B Commerce Pro → B2B Pricing
2. Add pricing rules for different customer groups
3. Set discount types (percentage or fixed amount)
4. Configure minimum quantities

## 📊 Shortcodes

### **Registration Form**
```
[b2b_registration]
```
Displays the B2B account registration form.

### **B2B Dashboard**
```
[b2b_dashboard]
```
Shows the B2B customer dashboard with order history and account management.

### **Order History**
```
[b2b_order_history]
```
Displays order history for logged-in B2B users.

### **Account Management**
```
[b2b_account]
```
Shows account management form for B2B users.

## 🎨 Customization

### **CSS Customization**
The plugin includes comprehensive CSS files:
- `assets/css/b2b-admin-standalone-demo.css` - Admin styles
- `assets/css/b2b-commerce-pro.css` - Frontend styles

### **JavaScript Functionality**
- `assets/js/b2b-commerce-pro.js` - Admin JavaScript
- AJAX functionality for user approval
- Bulk actions and export features

## 🔒 Security Features

- **Nonce Verification**: All forms include security nonces
- **Role-based Access**: Proper permission checks
- **Data Sanitization**: All user input is sanitized
- **CSRF Protection**: AJAX requests include security tokens

## 📈 Analytics & Reporting

### **Dashboard Statistics**
- Total B2B users
- Pending approvals
- Approved users
- Total revenue (if WooCommerce active)

### **Export Features**
- Export user data to CSV
- Export pricing rules to CSV
- Bulk user management

## 🛠️ Technical Details

### **Database Tables**
- `wp_b2b_pricing_rules` - Pricing rules table
- User meta fields for B2B information

### **Hooks & Filters**
The plugin provides various hooks for customization:
- `b2b_user_approved` - Fired when user is approved
- `b2b_user_rejected` - Fired when user is rejected
- `b2b_pricing_rule_saved` - Fired when pricing rule is saved

### **File Structure**
```
b2b-commerce-pro/
├── includes/
│   ├── Init.php
│   ├── AdminPanel.php
│   ├── UserManager.php
│   ├── PricingManager.php
│   ├── ProductManager.php
│   ├── Frontend.php
│   ├── AdvancedFeatures.php
│   └── Reporting.php
├── assets/
│   ├── css/
│   │   ├── b2b-admin-standalone-demo.css
│   │   └── b2b-commerce-pro.css
│   └── js/
│       └── b2b-commerce-pro.js
└── b2b-commerce-pro.php
```

## 🚀 Advanced Features

### **Bulk Operations**
- Bulk approve/reject users
- Bulk delete users
- Export user data

### **Email Notifications**
- Approval notifications
- Rejection notifications
- Custom email templates

### **Pricing Rules**
- Customer group-specific pricing
- Volume-based discounts
- Minimum quantity requirements

## 🔧 Troubleshooting

### **Common Issues**

1. **Users not appearing in admin**
   - Check if users have B2B roles assigned
   - Verify user meta fields are set

2. **Pricing not applying**
   - Ensure pricing rules are configured
   - Check user role assignments
   - Verify WooCommerce is active

3. **Email notifications not sending**
   - Check WordPress email configuration
   - Verify email templates are set up

### **Debug Mode**
Enable WordPress debug mode to see detailed error messages:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## 📞 Support

For support and feature requests, please contact the plugin developer.

## 🔄 Updates

The plugin includes automatic update checking and will notify you when new versions are available.

## 📄 License

This plugin is licensed under GPL v2 or later.

---

**B2B Commerce Pro** - The complete B2B solution for WordPress and WooCommerce.
