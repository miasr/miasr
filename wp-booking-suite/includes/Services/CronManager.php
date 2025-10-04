<?php
namespace WPBS\Services;

class CronManager
{
    public function register(): void
    {
        add_filter('cron_schedules', [$this, 'add_schedules']);
        add_action('wpbs_notifications_tick', [$this, 'process_notifications']);

        if (!wp_next_scheduled('wpbs_notifications_tick')) {
            wp_schedule_event(time() + 60, 'wpbs_minutely', 'wpbs_notifications_tick');
        }
    }

    public function add_schedules(array $schedules): array
    {
        if (!isset($schedules['wpbs_minutely'])) {
            $schedules['wpbs_minutely'] = [
                'interval' => 60,
                'display' => __('Every Minute (WPBS)', 'wp-booking-suite'),
            ];
        }
        return $schedules;
    }

    public function process_notifications(): void
    {
        /**
         * Placeholder: real implementation will dequeue and send notifications
         * and apply retry with backoff on failure.
         */
        do_action('wpbs/notifications/process');
    }
}
