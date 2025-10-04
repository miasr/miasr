<?php
namespace WPBS;

use WPBS\Admin\AdminApp;
use WPBS\API\ApiRouter;
use WPBS\Services\CronManager;
use WPBS\Services\FeatureFlags;

class Plugin
{
    public function init(): void
    {
        // Settings and feature flags
        FeatureFlags::init();

        // Admin
        if (is_admin()) {
            (new AdminApp())->init();
        }

        // REST API
        add_action('rest_api_init', function () {
            (new ApiRouter())->register_routes();
        });

        // Cron
        (new CronManager())->register();
    }
}
