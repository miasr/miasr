<?php
/**
 * Plugin Name: WP Booking Suite (Doctors)
 * Description: Comprehensive booking, wallet, payouts, notifications, and integrations (Whereby, Stripe) for doctors and clients.
 * Version: 0.1.0
 * Author: Your Company
 * Text Domain: wp-booking-suite
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

// PSR-4 style autoloader for the WPBS namespace
spl_autoload_register(function ($class) {
    $prefix = 'WPBS\\';
    $prefixLength = strlen($prefix);
    if (strncmp($prefix, $class, $prefixLength) !== 0) {
        return;
    }

    $relativeClass = substr($class, $prefixLength);
    $file = __DIR__ . '/includes/' . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_readable($file)) {
        require $file;
    }
});

// Define constants
define('WPBS_VERSION', '0.1.0');
define('WPBS_PLUGIN_FILE', __FILE__);
define('WPBS_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Activation/Deactivation hooks
register_activation_hook(__FILE__, function () {
    (new WPBS\Installer())->install();
    if (class_exists('WPBS\\Services\\RewriteManager')) {
        WPBS\Services\RewriteManager::activate();
    } else {
        flush_rewrite_rules();
    }
});

register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});

// Bootstrap plugin
add_action('plugins_loaded', function () {
    load_plugin_textdomain('wp-booking-suite', false, dirname(plugin_basename(__FILE__)) . '/languages');

    $app = new WPBS\Plugin();
    $app->init();
});
