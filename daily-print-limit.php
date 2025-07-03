<?php
/**
 * Plugin Name: Daily Print Limit for Users
 * Description: Limit each logged-in user to 25 prints per day.
 * Version: 1.0
 * Author: teshvenk - Venkatesh Ramdass
 */

if (!defined('ABSPATH')) exit;

class Daily_Print_Limit {

    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'user_print_limit';

        register_activation_hook(__FILE__, [$this, 'create_table']);
        add_action('wp_ajax_handle_print', [$this, 'handle_print_request']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function create_table() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT NOT NULL,
            print_date DATE NOT NULL,
            print_count INT DEFAULT 0,
            UNIQUE KEY user_date (user_id, print_date)
        ) $charset;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function enqueue_scripts() {
        if (is_user_logged_in()) {
            wp_enqueue_script('daily-print-limit', plugin_dir_url(__FILE__) . 'print-limit.js', ['jquery'], null, true);
            wp_localize_script('daily-print-limit', 'PrintLimitAjax', [
                'ajax_url' => admin_url('admin-ajax.php')
            ]);
        }
    }

    public function handle_print_request() {
        if (!is_user_logged_in()) {
            wp_send_json_error("Login required");
        }

        global $wpdb;
        $user_id = get_current_user_id();
        $today = date('Y-m-d');

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT print_count FROM {$this->table_name} WHERE user_id = %d AND print_date = %s",
            $user_id, $today
        ));

        if ($count !== null && $count >= 25) {
            wp_send_json_error("Daily print limit reached (25)");
        }

        if ($count === null) {
            $wpdb->insert($this->table_name, [
                'user_id' => $user_id,
                'print_date' => $today,
                'print_count' => 1
            ]);
        } else {
            $wpdb->update($this->table_name,
                ['print_count' => $count + 1],
                ['user_id' => $user_id, 'print_date' => $today]
            );
        }

        wp_send_json_success("Print allowed");
    }
}

new Daily_Print_Limit();
