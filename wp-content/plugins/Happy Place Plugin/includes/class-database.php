<?php
namespace HappyPlace\Core;

/**
 * Database Tables Setup
 *
 * @package HappyPlace
 * @subpackage Core
 */

if (!defined('ABSPATH')) {
    exit;
}

class Database {
    private static ?self $instance = null;

    /**
     * Get singleton instance
     */
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('init', [$this, 'check_version']);
        // Reference global constant with leading backslash
        register_activation_hook(\HPH_PLUGIN_FILE, [$this, 'install']);
    }

    /**
     * Check database version and update if necessary
     */
    public function check_version(): void {
        if (get_option('hph_db_version') !== \HPH_VERSION) {
            $this->install();
        }
    }

    /**
     * Install database tables
     */
    public function install(): void {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = $wpdb->get_charset_collate();

        // Property Views Table
        $table_views = $wpdb->prefix . 'hph_property_views';
        $sql_views = "CREATE TABLE IF NOT EXISTS $table_views (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            property_id bigint(20) NOT NULL,
            user_id bigint(20),
            ip_address varchar(45),
            user_agent varchar(255),
            viewed_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY property_id (property_id),
            KEY user_id (user_id),
            KEY viewed_at (viewed_at)
        ) $charset_collate;";
        dbDelta($sql_views);

        // Inquiries Table
        $table_inquiries = $wpdb->prefix . 'hph_inquiries';
        $sql_inquiries = "CREATE TABLE IF NOT EXISTS $table_inquiries (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            property_id bigint(20) NOT NULL,
            agent_id bigint(20) NOT NULL,
            name varchar(100) NOT NULL,
            email varchar(100) NOT NULL,
            phone varchar(20),
            message text NOT NULL,
            status varchar(20) DEFAULT 'new',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY property_id (property_id),
            KEY agent_id (agent_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        dbDelta($sql_inquiries);

        // Agent Stats Table
        $table_stats = $wpdb->prefix . 'hph_agent_stats';
        $sql_stats = "CREATE TABLE IF NOT EXISTS $table_stats (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            agent_id bigint(20) NOT NULL,
            stat_date date NOT NULL,
            listing_views int(11) DEFAULT 0,
            inquiry_count int(11) DEFAULT 0,
            listing_count int(11) DEFAULT 0,
            open_house_count int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY agent_date (agent_id, stat_date),
            KEY agent_id (agent_id),
            KEY stat_date (stat_date)
        ) $charset_collate;";
        dbDelta($sql_stats);

        // Team Members Table
        $table_teams = $wpdb->prefix . 'hph_team_members';
        $sql_teams = "CREATE TABLE IF NOT EXISTS $table_teams (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            team_id bigint(20) NOT NULL,
            agent_id bigint(20) NOT NULL,
            role varchar(50) DEFAULT 'member',
            joined_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY team_agent (team_id, agent_id),
            KEY team_id (team_id),
            KEY agent_id (agent_id)
        ) $charset_collate;";
        dbDelta($sql_teams);

        // Documents Table
        $table_docs = $wpdb->prefix . 'hph_documents';
        $sql_docs = "CREATE TABLE IF NOT EXISTS $table_docs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            property_id bigint(20) NOT NULL,
            title varchar(255) NOT NULL,
            file_path varchar(255) NOT NULL,
            file_type varchar(50),
            uploaded_by bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY property_id (property_id),
            KEY uploaded_by (uploaded_by)
        ) $charset_collate;";
        dbDelta($sql_docs);

        // Market Reports Table
        $table_reports = $wpdb->prefix . 'hph_market_reports';
        $sql_reports = "CREATE TABLE IF NOT EXISTS $table_reports (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text,
            area_code varchar(20),
            report_date date NOT NULL,
            data_json longtext,
            created_by bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY area_code (area_code),
            KEY report_date (report_date),
            KEY created_by (created_by)
        ) $charset_collate;";
        dbDelta($sql_reports);

        // Save database version
        update_option('hph_db_version', \HPH_VERSION);
    }
}
