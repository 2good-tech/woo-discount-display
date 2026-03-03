<?php
/**
 * 2GOOD Admin Menu
 *
 * @package 2GOOD
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 2GOOD Admin Menu class
 */
if (!class_exists('_2GOOD_Admin_Menu')) {
    class _2GOOD_Admin_Menu {

        /**
         * Initialize the admin menu
         */
        public static function init() {
            add_action('admin_menu', array(__CLASS__, 'register_parent_menu'), 5);
            add_action('admin_init', array(__CLASS__, 'handle_plugin_toggle'));
        }

        /**
         * Handle plugin activate/deactivate actions from the dashboard
         */
        public static function handle_plugin_toggle() {
            if (!isset($_GET['2good_action']) || !isset($_GET['2good_plugin'])) {
                return;
            }

            if (!current_user_can('activate_plugins')) {
                return;
            }

            $action = sanitize_text_field($_GET['2good_action']);
            $plugin = sanitize_text_field($_GET['2good_plugin']);

            check_admin_referer('2good_toggle_' . $plugin);

            if (!function_exists('get_plugins')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }

            // Verify the plugin exists and is a 2GOOD plugin
            $all_plugins = get_plugins();
            if (!isset($all_plugins[$plugin])) {
                return;
            }
            $author = isset($all_plugins[$plugin]['AuthorName']) ? $all_plugins[$plugin]['AuthorName'] : $all_plugins[$plugin]['Author'];
            if (stripos($author, '2GOOD') === false) {
                return;
            }

            if ($action === 'activate') {
                activate_plugin($plugin);
            } elseif ($action === 'deactivate') {
                deactivate_plugins($plugin);
            }

            wp_safe_redirect(admin_url('admin.php?page=2good-settings&toggled=1'));
            exit;
        }

        /**
         * Get the 2GOOD menu SVG icon
         */
        public static function get_menu_icon() {
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                <rect fill="currentColor" width="20" height="20" rx="4" ry="4"/>
                <path  d="M2.23986,15.08533a.671.671,0,0,1-.48-.15723.62385.62385,0,0,1-.165-.47266A3.9723,3.9723,0,0,1,2.09,12.393a4.15931,4.15931,0,0,1,1.09473-1.26807,18.936,18.936,0,0,1,1.6499-1.03516q1.095-.6145,1.5376-.90723a2.78325,2.78325,0,0,0,.70508-.645A1.38032,1.38032,0,0,0,7.34,7.68983a2.47848,2.47848,0,0,0-.13525-.92236.82256.82256,0,0,0-.45752-.45752,2.46841,2.46841,0,0,0-.92236-.13525h-1.605A2.14871,2.14871,0,0,0,3.16955,6.37a.94388.94388,0,0,0-.41992.68994.87431.87431,0,0,1-.21.46484.61061.61061,0,0,1-.44971.1499.63232.63232,0,0,1-.48-.17236.589.589,0,0,1-.13525-.4873A2.34819,2.34819,0,0,1,2.31457,5.4325a3.07177,3.07177,0,0,1,1.90527-.51758h1.605a2.84821,2.84821,0,0,1,2.09229.68262,2.84821,2.84821,0,0,1,.68262,2.09229,2.5166,2.5166,0,0,1-.4126,1.44775,3.63718,3.63718,0,0,1-.99756.99707q-.58447.39038-1.73975,1.0498-.87012.49585-1.29736.78857a3.56239,3.56239,0,0,0-.7876.75A2.35771,2.35771,0,0,0,2.9,13.82557H7.84973a.631.631,0,1,1,0,1.25977Z" transform="translate(0 0)"/>
                <path  d="M11.12756,14.268a3.46657,3.46657,0,0,1-.81738-2.543V8.27479a3.49291,3.49291,0,0,1,.81738-2.5498,3.38269,3.38269,0,0,1,2.52734-.81006h1.81543a3.4176,3.4176,0,0,1,2.15234.585A2.514,2.514,0,0,1,18.485,7.33a.53377.53377,0,0,1-.12012.48.64858.64858,0,0,1-.47949.165.6215.6215,0,0,1-.458-.15771.816.816,0,0,1-.20215-.47266,1.17229,1.17229,0,0,0-.46484-.92236,2.515,2.515,0,0,0-1.29-.24756H13.6549a3.11549,3.11549,0,0,0-1.25977.18018,1.17066,1.17066,0,0,0-.6377.645,3.428,3.428,0,0,0-.1875,1.2749V11.725a3.2484,3.2484,0,0,0,.19531,1.26758,1.2053,1.2053,0,0,0,.6377.6377,3.2484,3.2484,0,0,0,1.26758.19531h1.7998a3.00142,3.00142,0,0,0,1.10254-.1582.95523.95523,0,0,0,.54-.53906,3.02912,3.02912,0,0,0,.15723-1.10254V11.3202l-1.16992-.01562a.55674.55674,0,0,1-.62988-.62988.57388.57388,0,0,1,.62988-.62988l1.7998.01514a.57381.57381,0,0,1,.62988.62939v1.33643A3.15532,3.15532,0,0,1,17.78771,14.35a3.18222,3.18222,0,0,1-2.31738.73535h-1.7998A3.46865,3.46865,0,0,1,11.12756,14.268Z" transform="translate(0 0)"/>
                </svg>';
            return 'data:image/svg+xml;base64,' . base64_encode($svg);
        }

        /**
         * Register the shared 2GOOD parent menu
         */
        public static function register_parent_menu() {
            global $menu;
            if (is_array($menu)) {
                foreach ($menu as $item) {
                    if (isset($item[2]) && $item[2] === '2good-settings') {
                        return;
                    }
                }
            }
            add_menu_page(
                '2GOOD Technologies', '2GOOD', 'manage_options',
                '2good-settings', array(__CLASS__, 'display_dashboard_page'),
                self::get_menu_icon(), 2
            );
            add_submenu_page(
                '2good-settings', '2GOOD Dashboard', 'Dashboard',
                'manage_options', '2good-settings', array(__CLASS__, 'display_dashboard_page')
            );
        }

        /**
         * Get available update info from WP's update transient
         */
        private static function get_update_info() {
            $updates = get_site_transient('update_plugins');
            if (isset($updates->response) && is_array($updates->response)) {
                return $updates->response;
            }
            return array();
        }

        /**
         * Display the 2GOOD Dashboard page
         */
        public static function display_dashboard_page() {
            if (!current_user_can('manage_options')) { return; }
            if (!function_exists('get_plugins')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            $all_plugins    = get_plugins();
            $active_plugins = get_option('active_plugins', array());
            $update_info    = self::get_update_info();
            $_2GOOD_plugins = array();
            foreach ($all_plugins as $file => $data) {
                $author = isset($data['AuthorName']) ? $data['AuthorName'] : $data['Author'];
                if (stripos($author, '2GOOD') !== false) {
                    $_2GOOD_plugins[] = array(
                        'file'        => $file,
                        'name'        => $data['Name'],
                        'version'     => $data['Version'],
                        'active'      => in_array($file, $active_plugins),
                        'description' => $data['Description'],
                        'update'      => isset($update_info[$file]) ? $update_info[$file] : null,
                    );
                }
            }

            if (isset($_GET['toggled'])) {
                echo '<div class="notice notice-success is-dismissible"><p>Plugin status updated.</p></div>';
            }

            echo '<div class="wrap"><h1>2GOOD Technologies Ltd.</h1>';
            echo '<p>Manage all 2GOOD Technologies plugins from one place.</p><hr>';
            echo '<h2>' . esc_html__('Installed Plugins') . '</h2>';
            echo '<table class="widefat fixed striped" style="max-width:980px;">';
            echo '<thead><tr>';
            echo '<th style="width:35%;">' . esc_html__('Plugin') . '</th>';
            echo '<th style="width:10%;">' . esc_html__('Version') . '</th>';
            echo '<th style="width:10%;">' . esc_html__('Status') . '</th>';
            echo '<th>' . esc_html__('Description') . '</th>';
            echo '</tr></thead><tbody>';

            foreach ($_2GOOD_plugins as $p) {
                // Build row actions like WP plugins page
                $actions = array();

                if ($p['active']) {
                    $deactivate_url = wp_nonce_url(
                        admin_url('admin.php?page=2good-settings&2good_action=deactivate&2good_plugin=' . urlencode($p['file'])),
                        '2good_toggle_' . $p['file']
                    );
                    $actions[] = '<a href="' . esc_url($deactivate_url) . '" style="color:#dc3232;">' . esc_html__('Deactivate') . '</a>';
                } else {
                    $activate_url = wp_nonce_url(
                        admin_url('admin.php?page=2good-settings&2good_action=activate&2good_plugin=' . urlencode($p['file'])),
                        '2good_toggle_' . $p['file']
                    );
                    $actions[] = '<a href="' . esc_url($activate_url) . '">' . esc_html__('Activate') . '</a>';
                }

                if ($p['update']) {
                    $update_url = wp_nonce_url(
                        admin_url('update.php?action=upgrade-plugin&plugin=' . urlencode($p['file'])),
                        'upgrade-plugin_' . $p['file']
                    );
                    $actions[] = '<a href="' . esc_url($update_url) . '" style="color:#d63638;font-weight:600;">' . esc_html__('Update') . '</a>';
                }

                $row_actions = '<div class="row-actions visible" style="font-size:12px;">' . implode(' | ', $actions) . '</div>';

                // Status indicator
                $status = $p['active']
                    ? '<span style="color:#46b450;">&#9679; Active</span>'
                    : '<span style="color:#dc3232;">&#9679; Inactive</span>';

                // Version column
                $version_html = esc_html($p['version']);
                if ($p['update']) {
                    $version_html .= '<br><span style="color:#d63638;font-size:12px;">&#8594; ' . esc_html($p['update']->new_version) . '</span>';
                }

                echo '<tr>';
                echo '<td><strong>' . esc_html($p['name']) . '</strong>' . $row_actions . '</td>';
                echo '<td>' . $version_html . '</td>';
                echo '<td>' . $status . '</td>';
                echo '<td>' . wp_kses_post($p['description']) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table></div>';
        }
    }
}
