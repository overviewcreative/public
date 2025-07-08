<?php
/**
 * Assets Manager Class
 *
 * @package HappyPlace
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Happy_Place_Assets_Manager {
    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    public function enqueue_styles() {
        // Core styles
        wp_enqueue_style(
            'happy-place-core',
            HAPPY_PLACE_THEME_URI . '/assets/css/core.css',
            array(),
            filemtime( HAPPY_PLACE_THEME_DIR . '/assets/css/core.css' )
        );

        // Listing styles
        wp_enqueue_style(
            'happy-place-listing',
            HAPPY_PLACE_THEME_URI . '/assets/css/listing.css',
            array(),
            filemtime( HAPPY_PLACE_THEME_DIR . '/assets/css/listing.css' )
        );
    }

    public function enqueue_scripts() {
        // Core scripts
        wp_enqueue_script(
            'happy-place-core',
            HAPPY_PLACE_THEME_URI . '/assets/js/core.js',
            array( 'jquery' ),
            filemtime( HAPPY_PLACE_THEME_DIR . '/assets/js/core.js' ),
            true
        );

        // Listing scripts
        wp_enqueue_script(
            'happy-place-listing',
            HAPPY_PLACE_THEME_URI . '/assets/js/listing.js',
            array( 'jquery' ),
            filemtime( HAPPY_PLACE_THEME_DIR . '/assets/js/listing.js' ),
            true
        );
    }
}
