<?php
namespace WPBS\Services;

class FeatureFlags
{
    public static function init(): void
    {
        // Ensure option exists
        $settings = get_option('wpbs_settings');
        if (!$settings || !isset($settings['feature_flags'])) {
            $settings = is_array($settings) ? $settings : [];
            $settings['feature_flags'] = [
                'packages' => true,
                'coupons' => true,
                'income_proofs' => true,
                'ratings' => false,
            ];
            update_option('wpbs_settings', $settings);
        }
    }

    public static function is_enabled(string $flag): bool
    {
        $settings = get_option('wpbs_settings');
        return !empty($settings['feature_flags'][$flag]);
    }
}
