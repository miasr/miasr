<?php
namespace WPBS;

class Installer
{
    public function install(): void
    {
        $this->maybeCreateTables();
        $this->maybeAddRoles();
        $this->maybeInstallOptions();
    }

    private function maybeCreateTables(): void
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        $tablesSql = [];

        // Doctors
        $tablesSql[] = "CREATE TABLE {$wpdb->prefix}wpbs_doctors (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            specialties TEXT NULL,
            pricing JSON NULL,
            tax_percent DECIMAL(5,2) DEFAULT 0,
            platform_commission_percent DECIMAL(5,2) DEFAULT 0,
            cancellation_policy TEXT NULL,
            kyc_status VARCHAR(20) DEFAULT 'pending',
            income_proof_status VARCHAR(20) DEFAULT 'pending',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;";

        // Clients
        $tablesSql[] = "CREATE TABLE {$wpdb->prefix}wpbs_clients (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            phone VARCHAR(50) NULL,
            email_verified TINYINT(1) DEFAULT 0,
            phone_verified TINYINT(1) DEFAULT 0,
            blocked TINYINT(1) DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;";

        // Schedules
        $tablesSql[] = "CREATE TABLE {$wpdb->prefix}wpbs_schedules (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            doctor_id BIGINT UNSIGNED NOT NULL,
            timezone VARCHAR(64) NOT NULL,
            weekly_template JSON NULL,
            exceptions JSON NULL,
            holidays JSON NULL,
            breaks JSON NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY doctor_id (doctor_id)
        ) $charset_collate;";

        // Bookings
        $tablesSql[] = "CREATE TABLE {$wpdb->prefix}wpbs_bookings (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            booking_uid VARCHAR(50) NOT NULL,
            client_id BIGINT UNSIGNED NOT NULL,
            doctor_id BIGINT UNSIGNED NOT NULL,
            specialty VARCHAR(120) NULL,
            start_at DATETIME NOT NULL,
            end_at DATETIME NOT NULL,
            duration_minutes INT NOT NULL,
            status VARCHAR(20) NOT NULL,
            payment_status VARCHAR(20) NOT NULL,
            price_cents BIGINT NOT NULL,
            currency VARCHAR(10) NOT NULL,
            coupon_code VARCHAR(50) NULL,
            package_id BIGINT UNSIGNED NULL,
            whereby_room VARCHAR(255) NULL,
            metadata JSON NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE KEY booking_uid (booking_uid),
            PRIMARY KEY  (id),
            KEY doctor_time (doctor_id, start_at, end_at)
        ) $charset_collate;";

        // Sessions (runtime)
        $tablesSql[] = "CREATE TABLE {$wpdb->prefix}wpbs_sessions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            booking_id BIGINT UNSIGNED NOT NULL,
            actually_started_at DATETIME NULL,
            actually_ended_at DATETIME NULL,
            actual_duration_minutes INT NULL,
            no_show TINYINT(1) DEFAULT 0,
            overrun_minutes INT DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY booking_id (booking_id)
        ) $charset_collate;";

        // Wallet
        $tablesSql[] = "CREATE TABLE {$wpdb->prefix}wpbs_wallet_transactions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            type VARCHAR(30) NOT NULL,
            amount_cents BIGINT NOT NULL,
            currency VARCHAR(10) NOT NULL,
            related_id BIGINT UNSIGNED NULL,
            related_type VARCHAR(40) NULL,
            status VARCHAR(20) NOT NULL,
            metadata JSON NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;";

        // Payouts
        $tablesSql[] = "CREATE TABLE {$wpdb->prefix}wpbs_payouts (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            doctor_id BIGINT UNSIGNED NOT NULL,
            amount_cents BIGINT NOT NULL,
            currency VARCHAR(10) NOT NULL,
            status VARCHAR(20) NOT NULL,
            attachment_url TEXT NULL,
            log TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY doctor_id (doctor_id)
        ) $charset_collate;";

        // Notifications
        $tablesSql[] = "CREATE TABLE {$wpdb->prefix}wpbs_notifications (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            channel VARCHAR(20) NOT NULL,
            template_key VARCHAR(100) NOT NULL,
            to_address TEXT NOT NULL,
            payload JSON NULL,
            status VARCHAR(20) NOT NULL,
            scheduled_at DATETIME NULL,
            sent_at DATETIME NULL,
            error TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY channel_status (channel, status)
        ) $charset_collate;";

        // Audit Log
        $tablesSql[] = "CREATE TABLE {$wpdb->prefix}wpbs_audit_logs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            actor_id BIGINT UNSIGNED NULL,
            action VARCHAR(100) NOT NULL,
            target_type VARCHAR(50) NOT NULL,
            target_id BIGINT UNSIGNED NULL,
            message TEXT NULL,
            metadata JSON NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY target (target_type, target_id)
        ) $charset_collate;";

        foreach ($tablesSql as $sql) {
            dbDelta($sql);
        }
    }

    private function maybeAddRoles(): void
    {
        add_role('wpbs_doctor', __('Doctor', 'wp-booking-suite'), [
            'read' => true,
        ]);
        add_role('wpbs_client', __('Client', 'wp-booking-suite'), [
            'read' => true,
        ]);
        add_role('wpbs_accountant', __('Accountant', 'wp-booking-suite'), [
            'read' => true,
        ]);
        add_role('wpbs_support', __('Support', 'wp-booking-suite'), [
            'read' => true,
        ]);

        // Capabilities can be refined later
        $admin = get_role('administrator');
        if ($admin) {
            $caps = [
                'wpbs_manage_payouts',
                'wpbs_export_reports',
                'wpbs_approve_income_proofs',
                'wpbs_manage_bookings',
                'wpbs_manage_settings',
            ];
            foreach ($caps as $cap) {
                $admin->add_cap($cap);
            }
        }
    }

    private function maybeInstallOptions(): void
    {
        $defaults = [
            'currency' => 'USD',
            'timezone' => 'UTC',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'feature_flags' => [
                'packages' => true,
                'coupons' => true,
                'income_proofs' => true,
                'ratings' => false,
            ],
            'cancellation_policy' => [
                'client_free_cancel_hours' => 24,
                'doctor_cancel_penalty' => 0,
            ],
            'stripe' => [
                'secret_key' => '',
                'webhook_secret' => '',
                'test_mode' => true,
            ],
            'whereby' => [
                'api_key' => '',
                'end_grace_minutes' => 10,
            ],
            'smtp' => [
                'host' => '',
                'port' => 587,
                'username' => '',
                'password' => '',
                'from_email' => '',
                'from_name' => 'WP Booking Suite',
            ],
        ];

        add_option('wpbs_settings', $defaults, '', false);
    }
}
