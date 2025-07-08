<?php
/**
 * Agent Class
 *
 * @package HappyPlace
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Happy_Place_Agent {
    private $user;

    public function __construct( $user = null ) {
        if ( is_numeric( $user ) ) {
            $this->user = get_user_by( 'id', $user );
        } elseif ( $user instanceof WP_User ) {
            $this->user = $user;
        }
    }

    // Add methods for handling agent data
}
