<?php
/**
 * Plugin Name:       AI SEO Optimizer
 * Plugin URI:        https://example.com/plugins/ai-seo-optimizer/
 * Description:       A plugin to optimize SEO using AI, generate blog content, and provide an SEO chatbot assistant, with Google Gemini API integration.
 * Version:           0.1.0
 * Author:            Your Name or Company
 * Author URI:        https://example.com/
 * License:           MIT
 * License URI:       https://opensource.org/licenses/MIT
 * Text Domain:       ai-seo-optimizer
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Define plugin constants
if ( ! defined( 'AI_SEO_OPTIMIZER_VERSION' ) ) {
    define( 'AI_SEO_OPTIMIZER_VERSION', '0.1.0' );
}
if ( ! defined( 'AI_SEO_OPTIMIZER_PLUGIN_DIR' ) ) {
    define( 'AI_SEO_OPTIMIZER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'AI_SEO_OPTIMIZER_PLUGIN_URL' ) ) {
    define( 'AI_SEO_OPTIMIZER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'AI_SEO_OPTIMIZER_BASENAME' ) ) {
    define( 'AI_SEO_OPTIMIZER_BASENAME', plugin_basename( __FILE__ ) );
}

// Include core files
require_once AI_SEO_OPTIMIZER_PLUGIN_DIR . 'includes/class-ai-seo-gemini-api.php';
require_once AI_SEO_OPTIMIZER_PLUGIN_DIR . 'admin/class-ai-seo-settings.php';

/**
 * Load plugin textdomain.
 */
function ai_seo_optimizer_load_textdomain() {
    load_plugin_textdomain(
        'ai-seo-optimizer',
        false,
        dirname( AI_SEO_OPTIMIZER_BASENAME ) . '/languages/'
    );
}
add_action( 'plugins_loaded', 'ai_seo_optimizer_load_textdomain' );

/**
 * Initialize plugin components.
 */
function ai_seo_optimizer_init() {
    // Initialize admin settings
    if ( is_admin() ) {
        new AI_SEO_Optimizer_Settings();
        // new AI_SEO_Optimizer_Gemini_API(); // API class is usually instantiated when needed, not globally.
    }
    // Other initializations can go here
}
add_action( 'plugins_loaded', 'ai_seo_optimizer_init' );


// Activation hook
function ai_seo_optimizer_activate() {
    // Actions to run on plugin activation, e.g., set default options
    $default_options = [
        'gemini_api_key' => ''
        // Add other defaults here
    ];
    if ( get_option( 'ai_seo_optimizer_settings' ) === false ) {
        update_option( 'ai_seo_optimizer_settings', $default_options );
    }
    // Flush rewrite rules if custom post types or taxonomies are registered (not yet, but good practice)
    // flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'ai_seo_optimizer_activate' );

// Deactivation hook
function ai_seo_optimizer_deactivate() {
    // Actions to run on plugin deactivation
    // For example, clean up scheduled tasks if any
    // flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'ai_seo_optimizer_deactivate' );

// Uninstall hook (optional, for cleaning up options, tables, etc.)
// function ai_seo_optimizer_uninstall() {
// delete_option( 'ai_seo_optimizer_settings' );
// }
// register_uninstall_hook( __FILE__, 'ai_seo_optimizer_uninstall' );

?>
EOF
