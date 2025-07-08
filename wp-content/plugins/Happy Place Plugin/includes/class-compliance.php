<?php
/**
 * MLS and Fair Housing Compliance Class
 *
 * @package HappyPlace
 */

namespace HappyPlace;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Compliance handler for MLS and Fair Housing regulations
 */
class Compliance {
    private static ?self $instance = null;

    public static function get_instance(): self {
        return self::$instance ??= new self();
    }

    private function __construct() {
        $this->setup_hooks();
    }

    private function setup_hooks(): void {
        add_filter('the_content', [$this, 'add_fair_housing_notice']);
        add_filter('the_excerpt', [$this, 'add_fair_housing_notice']);
        add_action('init', [$this, 'register_compliance_settings']);
    }

    /**
     * Add Fair Housing notice to property content
     */
    public function add_fair_housing_notice(string $content): string {
        if (is_singular('property')) {
            $notice = get_option('happy_place_fair_housing_notice', '');
            if ($notice) {
                $content .= sprintf(
                    '<div class="fair-housing-notice">%s</div>',
                    wp_kses_post($notice)
                );
            }
        }
        return $content;
    }

    /**
     * Register compliance-related settings
     */
    public function register_compliance_settings(): void {
        register_setting(
            'happy_place_compliance',
            'happy_place_fair_housing_notice',
            [
                'type' => 'string',
                'description' => 'Fair Housing Notice for property listings',
                'sanitize_callback' => 'wp_kses_post',
                'show_in_rest' => true,
                'default' => sprintf(
                    /* translators: %s: Equal Housing Opportunity statement */
                    __('This property is offered in compliance with Federal Fair Housing Law. %s', 'happy-place'),
                    __('Equal Housing Opportunity', 'happy-place')
                ),
            ]
        );
    }
}
