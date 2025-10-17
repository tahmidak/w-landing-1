<?php
/**
 * Form Handler for Landing Page
 * Add this to your theme's functions.php or create a new file in theme directory
 */

// Security: Register AJAX handlers
add_action('wp_ajax_submit_contact_form', 'handle_contact_form');
add_action('wp_ajax_nopriv_submit_contact_form', 'handle_contact_form');
add_action('wp_ajax_submit_quote_form', 'handle_quote_form');
add_action('wp_ajax_nopriv_submit_quote_form', 'handle_quote_form');

/**
 * Handle Contact Form (First Form - Contact Section)
 */
function handle_contact_form() {
    // Verify nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'contact_form_nonce')) {
        wp_send_json_error('Security verification failed');
    }

    // Sanitize and validate inputs
    $first_name = sanitize_text_field($_POST['first_name'] ?? '');
    $last_name = sanitize_text_field($_POST['last_name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    $company = sanitize_text_field($_POST['company'] ?? '');
    $message = sanitize_textarea_field($_POST['message'] ?? '');

    // Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($company) || empty($message)) {
        wp_send_json_error('All fields are required');
    }

    if (!is_email($email)) {
        wp_send_json_error('Invalid email address');
    }

    // Save to database
    global $wpdb;
    $table_name = $wpdb->prefix . 'contact_inquiries';
    
    $inserted = $wpdb->insert(
        $table_name,
        array(
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'phone' => $phone,
            'company' => $company,
            'message' => $message,
            'form_type' => 'contact',
            'created_at' => current_time('mysql'),
            'ip_address' => sanitize_text_field($_SERVER['REMOTE_ADDR'])
        ),
        array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
    );

    if (!$inserted) {
        wp_send_json_error('Failed to save inquiry');
    }

    // Send email notification to admin
    send_admin_notification('Contact Form Submission', $first_name, $last_name, $email, $phone, $company, $message);

    // Send confirmation email to user
    send_user_confirmation_email($email, $first_name, 'contact');

    wp_send_json_success('Thank you! We will contact you soon.');
}

/**
 * Handle Quote Form (Second Form - Instant Training Section)
 */
function handle_quote_form() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'quote_form_nonce')) {
        wp_send_json_error('Security verification failed');
    }

    $first_name = sanitize_text_field($_POST['first_name'] ?? '');
    $last_name = sanitize_text_field($_POST['last_name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    $company = sanitize_text_field($_POST['company'] ?? '');
    $message = sanitize_textarea_field($_POST['message'] ?? '');

    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($company) || empty($message)) {
        wp_send_json_error('All fields are required');
    }

    if (!is_email($email)) {
        wp_send_json_error('Invalid email address');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'contact_inquiries';
    
    $inserted = $wpdb->insert(
        $table_name,
        array(
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'phone' => $phone,
            'company' => $company,
            'message' => $message,
            'form_type' => 'quote',
            'created_at' => current_time('mysql'),
            'ip_address' => sanitize_text_field($_SERVER['REMOTE_ADDR'])
        ),
        array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
    );

    if (!$inserted) {
        wp_send_json_error('Failed to save quote request');
    }

    send_admin_notification('Quote Request', $first_name, $last_name, $email, $phone, $company, $message);
    send_user_confirmation_email($email, $first_name, 'quote');

    wp_send_json_success('Quote request received! We will get back to you shortly.');
}

/**
 * Send notification email to admin
 */
function send_admin_notification($form_type, $first_name, $last_name, $email, $phone, $company, $message) {
    $admin_email = get_option('admin_email');
    $subject = "New $form_type - " . $company;
    
    $email_body = "
        <h2>New $form_type Submission</h2>
        <p><strong>Name:</strong> $first_name $last_name</p>
        <p><strong>Email:</strong> $email</p>
        <p><strong>Phone:</strong> $phone</p>
        <p><strong>Company:</strong> $company</p>
        <p><strong>Message:</strong></p>
        <p>" . nl2br(esc_html($message)) . "</p>
    ";

    $headers = array('Content-Type: text/html; charset=UTF-8');
    wp_mail($admin_email, $subject, $email_body, $headers);
}

/**
 * Send confirmation email to user
 */
function send_user_confirmation_email($email, $name, $type) {
    $subject = $type === 'contact' ? 'We Received Your Inquiry' : 'Quote Request Received';
    
    $email_body = "
        <p>Hi $name,</p>
        <p>Thank you for reaching out! We have received your " . ($type === 'contact' ? 'inquiry' : 'quote request') . " and will get back to you shortly.</p>
        <p>Our team will review your request and contact you within 24-48 hours.</p>
        <p>Best regards,<br>The Training Team</p>
    ";

    $headers = array('Content-Type: text/html; charset=UTF-8');
    wp_mail($email, $subject, $email_body, $headers);
}

/**
 * Create database table on theme activation
 */
function create_contact_inquiries_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'contact_inquiries';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        first_name varchar(100) NOT NULL,
        last_name varchar(100) NOT NULL,
        email varchar(100) NOT NULL,
        phone varchar(20) NOT NULL,
        company varchar(200) NOT NULL,
        message longtext NOT NULL,
        form_type varchar(20) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        ip_address varchar(45),
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Create table on theme activation
add_action('after_switch_theme', 'create_contact_inquiries_table');

// Enqueue jQuery and localize AJAX URL
add_action('wp_enqueue_scripts', 'enqueue_form_scripts');
function enqueue_form_scripts() {
    wp_enqueue_script('jquery');
    wp_localize_script('jquery', 'ajax_object', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'contact_nonce' => wp_create_nonce('contact_form_nonce'),
        'quote_nonce' => wp_create_nonce('quote_form_nonce')
    ));
}
?>