<?php
/**
 * 2GOOD GitHub Updater
 *
 * Checks GitHub Releases for plugin updates and integrates with WP update system.
 *
 * @package 2GOOD
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('_2GOOD_GitHub_Updater')) {
    class _2GOOD_GitHub_Updater {

        private $plugin_file;   // e.g. 'woo-discount-display/woo-discount-display.php'
        private $plugin_slug;   // e.g. 'woo-discount-display'
        private $version;       // current installed version
        private $github_owner;  // e.g. '2good-tech'
        private $github_repo;   // e.g. 'woo-discount-display'
        private $github_token;  // optional, for private repos
        private $plugin_data;
        private $github_response;

        /**
         * @param string $plugin_file  Full plugin file path relative to plugins dir (e.g. 'woo-discount-display/woo-discount-display.php')
         * @param string $github_owner GitHub organization or username
         * @param string $github_repo  GitHub repository name
         * @param string $github_token Optional access token for private repos
         */
        public function __construct($plugin_file, $github_owner, $github_repo, $github_token = '') {
            $this->plugin_file  = $plugin_file;
            $this->plugin_slug  = dirname($plugin_file);
            $this->github_owner = $github_owner;
            $this->github_repo  = $github_repo;
            $this->github_token = $github_token;

            add_filter('pre_set_site_transient_update_plugins', array($this, 'check_update'));
            add_filter('plugins_api', array($this, 'plugin_info'), 10, 3);
            add_filter('upgrader_pre_install', array($this, 'before_install'), 10, 2);
            add_filter('upgrader_post_install', array($this, 'after_install'), 10, 3);
        }

        /**
         * Get plugin data from the plugin header
         */
        private function get_plugin_data() {
            if (empty($this->plugin_data)) {
                if (!function_exists('get_plugin_data')) {
                    require_once ABSPATH . 'wp-admin/includes/plugin.php';
                }
                $this->plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $this->plugin_file);
                $this->version = $this->plugin_data['Version'];
            }
        }

        /**
         * Fetch the latest release from GitHub API
         */
        private function fetch_github_release() {
            if (!empty($this->github_response)) {
                return $this->github_response;
            }

            $url = sprintf(
                'https://api.github.com/repos/%s/%s/releases/latest',
                $this->github_owner,
                $this->github_repo
            );

            $args = array(
                'headers' => array(
                    'Accept' => 'application/vnd.github.v3+json',
                ),
                'timeout' => 10,
            );

            if (!empty($this->github_token)) {
                $args['headers']['Authorization'] = 'token ' . $this->github_token;
            }

            $response = wp_remote_get($url, $args);

            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
                return false;
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (empty($body) || !isset($body['tag_name'])) {
                return false;
            }

            $this->github_response = $body;
            return $this->github_response;
        }

        /**
         * Get the version string from a GitHub release tag
         */
        private function get_remote_version($release) {
            return ltrim($release['tag_name'], 'vV');
        }

        /**
         * Get the download URL for the release ZIP
         */
        private function get_download_url($release) {
            // Prefer an uploaded asset named {slug}.zip
            if (!empty($release['assets'])) {
                foreach ($release['assets'] as $asset) {
                    if (substr($asset['name'], -4) === '.zip') {
                        return $asset['browser_download_url'];
                    }
                }
            }
            // Fall back to the auto-generated source ZIP
            return $release['zipball_url'];
        }

        /**
         * Check for plugin updates (hooked to pre_set_site_transient_update_plugins)
         */
        public function check_update($transient) {
            if (empty($transient->checked)) {
                return $transient;
            }

            $this->get_plugin_data();
            $release = $this->fetch_github_release();

            if (!$release) {
                return $transient;
            }

            $remote_version = $this->get_remote_version($release);

            if (version_compare($remote_version, $this->version, '>')) {
                $transient->response[$this->plugin_file] = (object) array(
                    'slug'        => $this->plugin_slug,
                    'plugin'      => $this->plugin_file,
                    'new_version' => $remote_version,
                    'url'         => $release['html_url'],
                    'package'     => $this->get_download_url($release),
                );
            } else {
                // Tell WP there is no update so it doesn't flag it
                $transient->no_update[$this->plugin_file] = (object) array(
                    'slug'        => $this->plugin_slug,
                    'plugin'      => $this->plugin_file,
                    'new_version' => $this->version,
                    'url'         => '',
                    'package'     => '',
                );
            }

            return $transient;
        }

        /**
         * Provide plugin info for the "View Details" modal (hooked to plugins_api)
         */
        public function plugin_info($result, $action, $args) {
            if ($action !== 'plugin_information' || !isset($args->slug) || $args->slug !== $this->plugin_slug) {
                return $result;
            }

            $this->get_plugin_data();
            $release = $this->fetch_github_release();

            if (!$release) {
                return $result;
            }

            $remote_version = $this->get_remote_version($release);

            return (object) array(
                'name'          => $this->plugin_data['Name'],
                'slug'          => $this->plugin_slug,
                'version'       => $remote_version,
                'author'        => $this->plugin_data['AuthorName'],
                'homepage'      => $this->plugin_data['PluginURI'],
                'requires'      => $this->plugin_data['RequiresWP'],
                'tested'        => '',
                'download_link' => $this->get_download_url($release),
                'sections'      => array(
                    'description' => $this->plugin_data['Description'],
                    'changelog'   => nl2br(esc_html($release['body'])),
                ),
            );
        }

        /**
         * Store whether the plugin was active before the update begins
         */
        public function before_install($response, $hook_extra) {
            if (isset($hook_extra['plugin']) && $hook_extra['plugin'] === $this->plugin_file) {
                $this->was_active = is_plugin_active($this->plugin_file);
            }
            return $response;
        }

        /**
         * Rename the extracted folder to match the plugin slug (hooked to upgrader_post_install)
         *
         * GitHub ZIPs extract to {repo}-{tag}/ but WP expects {slug}/
         */
        public function after_install($response, $hook_extra, $result) {
            if (!isset($hook_extra['plugin']) || $hook_extra['plugin'] !== $this->plugin_file) {
                return $result;
            }

            global $wp_filesystem;

            $proper_destination = WP_PLUGIN_DIR . '/' . $this->plugin_slug;
            $wp_filesystem->move($result['destination'], $proper_destination);
            $result['destination'] = $proper_destination;

            // Re-activate if it was active before the update
            if (!empty($this->was_active)) {
                activate_plugin($this->plugin_file);
            }

            return $result;
        }
    }
}
