<?php
/**
 * Email Configuration for B2B Commerce Pro
 * 
 * This file contains email configuration options for the B2B Commerce Pro plugin.
 * Uncomment and configure the settings below to enable email functionality.
 */

// Email Configuration Options
// Uncomment the lines below and configure them for your email service

/*
// Option 1: Gmail SMTP Configuration
define('B2B_SMTP_HOST', 'smtp.gmail.com');
define('B2B_SMTP_PORT', 587);
define('B2B_SMTP_SECURE', 'tls');
define('B2B_SMTP_USERNAME', 'your-email@gmail.com');
define('B2B_SMTP_PASSWORD', 'your-app-password'); // Use App Password, not regular password
define('B2B_SMTP_FROM_EMAIL', 'your-email@gmail.com');
define('B2B_SMTP_FROM_NAME', 'Your Company Name');

// Option 2: SendGrid SMTP Configuration
// define('B2B_SMTP_HOST', 'smtp.sendgrid.net');
// define('B2B_SMTP_PORT', 587);
// define('B2B_SMTP_SECURE', 'tls');
// define('B2B_SMTP_USERNAME', 'apikey');
// define('B2B_SMTP_PASSWORD', 'your-sendgrid-api-key');
// define('B2B_SMTP_FROM_EMAIL', 'your-verified-sender@yourdomain.com');
// define('B2B_SMTP_FROM_NAME', 'Your Company Name');

// Option 3: Mailgun SMTP Configuration
// define('B2B_SMTP_HOST', 'smtp.mailgun.org');
// define('B2B_SMTP_PORT', 587);
// define('B2B_SMTP_SECURE', 'tls');
// define('B2B_SMTP_USERNAME', 'your-mailgun-username');
// define('B2B_SMTP_PASSWORD', 'your-mailgun-password');
// define('B2B_SMTP_FROM_EMAIL', 'your-verified-sender@yourdomain.com');
// define('B2B_SMTP_FROM_NAME', 'Your Company Name');

// Enable SMTP
define('B2B_ENABLE_SMTP', false); // Set to true to enable SMTP
*/

/**
 * Instructions for setting up email:
 * 
 * 1. For Gmail:
 *    - Enable 2-factor authentication on your Gmail account
 *    - Generate an App Password (Google Account > Security > App Passwords)
 *    - Use the App Password instead of your regular password
 * 
 * 2. For SendGrid:
 *    - Sign up for a free SendGrid account
 *    - Verify your sender email address
 *    - Use your API key as the password
 * 
 * 3. For Mailgun:
 *    - Sign up for a free Mailgun account
 *    - Verify your domain
 *    - Use your SMTP credentials
 * 
 * 4. After configuring, uncomment the define('B2B_ENABLE_SMTP', true); line
 * 
 * 5. Test the email functionality by responding to an inquiry
 */

// Email Templates
if (!defined('B2B_INQUIRY_RESPONSE_SUBJECT')) {
    define('B2B_INQUIRY_RESPONSE_SUBJECT', 'Response to your inquiry about {product_name}');
}

if (!defined('B2B_INQUIRY_RESPONSE_TEMPLATE')) {
    define('B2B_INQUIRY_RESPONSE_TEMPLATE', 
        "Dear Customer,\n\n" .
        "Thank you for your inquiry about {product_name}.\n\n" .
        "Our response:\n{admin_response}\n\n" .
        "Best regards,\n{site_name}"
    );
}
