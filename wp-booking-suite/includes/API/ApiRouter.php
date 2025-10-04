<?php
namespace WPBS\API;

use WP_REST_Request;
use WP_REST_Response;

class ApiRouter
{
    public function register_routes(): void
    {
        register_rest_route('wpbs/v1', '/bookings', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'list_bookings'],
                'permission_callback' => [$this, 'require_any_logged_in']
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_booking'],
                'permission_callback' => '__return_true'
            ],
        ]);

        register_rest_route('wpbs/v1', '/sessions/(?P<id>\\d+)/end', [
            'methods'  => 'POST',
            'callback' => [$this, 'end_session'],
            'permission_callback' => [$this, 'require_doctor_or_admin'],
            'args' => [
                'id' => [ 'validate_callback' => 'is_numeric' ],
            ],
        ]);

        register_rest_route('wpbs/v1', '/notifications/test', [
            'methods'  => 'POST',
            'callback' => [$this, 'test_notification'],
            'permission_callback' => function () { return current_user_can('manage_options'); },
        ]);

        register_rest_route('wpbs/v1', '/hooks/stripe', [
            'methods'  => 'POST',
            'callback' => [$this, 'handle_stripe'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('wpbs/v1', '/hooks/whereby', [
            'methods'  => 'POST',
            'callback' => [$this, 'handle_whereby'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function list_bookings(WP_REST_Request $request): WP_REST_Response
    {
        return new WP_REST_Response([
            'data' => [],
            'message' => 'Not implemented',
        ], 200);
    }

    public function create_booking(WP_REST_Request $request): WP_REST_Response
    {
        return new WP_REST_Response([
            'ok' => true,
            'message' => 'Stub create_booking',
        ], 202);
    }

    public function end_session(WP_REST_Request $request): WP_REST_Response
    {
        $id = (int) $request['id'];
        return new WP_REST_Response([
            'ok' => true,
            'session_id' => $id,
        ], 200);
    }

    public function test_notification(WP_REST_Request $request): WP_REST_Response
    {
        return new WP_REST_Response([
            'ok' => true,
            'sent' => true,
        ], 200);
    }

    public function handle_stripe(WP_REST_Request $request): WP_REST_Response
    {
        return new WP_REST_Response(['ok' => true], 200);
    }

    public function handle_whereby(WP_REST_Request $request): WP_REST_Response
    {
        return new WP_REST_Response(['ok' => true], 200);
    }

    public function require_any_logged_in(): bool
    {
        return is_user_logged_in();
    }

    public function require_doctor_or_admin(): bool
    {
        if (current_user_can('manage_options') || current_user_can('wpbs_manage_bookings')) {
            return true;
        }
        $user = wp_get_current_user();
        return $user && in_array('wpbs_doctor', (array) $user->roles, true);
    }
}
