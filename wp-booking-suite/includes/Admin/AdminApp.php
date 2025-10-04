<?php
namespace WPBS\Admin;

class AdminApp
{
    public function init(): void
    {
        add_action('admin_menu', [$this, 'register_menu']);
    }

    public function register_menu(): void
    {
        add_menu_page(
            __('Booking Suite', 'wp-booking-suite'),
            __('Booking Suite', 'wp-booking-suite'),
            'manage_options',
            'wpbs-dashboard',
            [$this, 'render_dashboard'],
            'dashicons-calendar-alt',
            56
        );

        add_submenu_page(
            'wpbs-dashboard',
            __('Settings', 'wp-booking-suite'),
            __('Settings', 'wp-booking-suite'),
            'manage_options',
            'wpbs-settings',
            [$this, 'render_settings']
        );
    }

    public function render_dashboard(): void
    {
        echo '<div class="wrap"><h1>' . esc_html__('WP Booking Suite', 'wp-booking-suite') . '</h1><p>' . esc_html__('Dashboard coming soon.', 'wp-booking-suite') . '</p></div>';
    }

    public function render_settings(): void
    {
        echo '<div class="wrap"><h1>' . esc_html__('Settings', 'wp-booking-suite') . '</h1><p>' . esc_html__('Feature flags and integrations will appear here.', 'wp-booking-suite') . '</p></div>';
    }
}
