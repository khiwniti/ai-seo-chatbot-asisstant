<?php
/**
 * AI SEO Optimizer Settings Page and Admin Menu Handler
 *
 * @package AI_SEO_Optimizer
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once AI_SEO_OPTIMIZER_PLUGIN_DIR . 'admin/class-ai-seo-analyzer-page.php';
require_once AI_SEO_OPTIMIZER_PLUGIN_DIR . 'admin/class-ai-seo-blog-generator-page.php';
require_once AI_SEO_OPTIMIZER_PLUGIN_DIR . 'admin/class-ai-seo-chatbot-page.php'; // Added this line

class AI_SEO_Optimizer_Settings {

    private $analyzer_page;
    private $blog_generator_page;
    private $chatbot_page; // Added this line

    public function __construct() {
        $this->analyzer_page = new AI_SEO_Optimizer_Analyzer_Page();
        $this->blog_generator_page = new AI_SEO_Optimizer_Blog_Generator_Page();
        $this->chatbot_page = new AI_SEO_Optimizer_Chatbot_Page(); // Added this line

        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    public function add_admin_menu() {
        add_menu_page(
            __( 'AI SEO Optimizer', 'ai-seo-optimizer' ),
            __( 'AI SEO Optimizer', 'ai-seo-optimizer' ),
            'manage_options',
            'ai-seo-optimizer-main',
            [ $this->analyzer_page, 'render_page' ],
            'dashicons-superhero',
            65
        );

        add_submenu_page(
            'ai-seo-optimizer-main',
            __( 'SEO Content Analyzer', 'ai-seo-optimizer' ),
            __( 'Content Analyzer', 'ai-seo-optimizer' ),
            'manage_options',
            'ai-seo-optimizer-main',
            [ $this->analyzer_page, 'render_page' ]
        );

        add_submenu_page(
            'ai-seo-optimizer-main',
            __( 'AI Blog Generator', 'ai-seo-optimizer' ),
            __( 'Blog Generator', 'ai-seo-optimizer' ),
            'manage_options',
            'ai-seo-optimizer-blog-generator',
            [ $this->blog_generator_page, 'render_page' ]
        );

        add_submenu_page(
            'ai-seo-optimizer-main',
            __( 'SEO Chatbot Assistant', 'ai-seo-optimizer' ),
            __( 'SEO Chatbot', 'ai-seo-optimizer' ),
            'manage_options',
            'ai-seo-optimizer-chatbot',
            [ $this->chatbot_page, 'render_page' ] // Updated callback
        );

        add_submenu_page(
            'ai-seo-optimizer-main',
            __( 'Settings', 'ai-seo-optimizer' ),
            __( 'Settings', 'ai-seo-optimizer' ),
            'manage_options',
            'ai-seo-optimizer-settings',
            [ $this, 'render_settings_page_content' ]
        );
    }

    public function register_settings() {
        register_setting(
            'ai_seo_optimizer_settings_group',
            'ai_seo_optimizer_settings',
            [ $this, 'sanitize_settings' ]
        );
        add_settings_section(
            'ai_seo_optimizer_gemini_api_section',
            __( 'Google Gemini API Settings', 'ai-seo-optimizer' ),
            null,
            'ai-seo-optimizer-settings'
        );
        add_settings_field(
            'gemini_api_key',
            __( 'Gemini API Key', 'ai-seo-optimizer' ),
            [ $this, 'render_api_key_field' ],
            'ai-seo-optimizer-settings',
            'ai_seo_optimizer_gemini_api_section'
        );
    }

    public function render_api_key_field() {
        $options = get_option( 'ai_seo_optimizer_settings' );
        $api_key = isset( $options['gemini_api_key'] ) ? $options['gemini_api_key'] : '';
        ?>
        <input type='text' name='ai_seo_optimizer_settings[gemini_api_key]' value='<?php echo esc_attr( $api_key ); ?>' class='regular-text'>
        <p class="description"><?php esc_html_e( 'Enter your Google Gemini API Key.', 'ai-seo-optimizer' ); ?></p>
        <?php
    }

    public function render_settings_page_content() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'AI SEO Optimizer Settings', 'ai-seo-optimizer' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'ai_seo_optimizer_settings_group' );
                do_settings_sections( 'ai-seo-optimizer-settings' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    // Removed render_chatbot_page_placeholder() as it's no longer needed

    public function sanitize_settings( $input ) {
        $sanitized_input = [];
        if ( isset( $input['gemini_api_key'] ) ) {
            $sanitized_input['gemini_api_key'] = sanitize_text_field( $input['gemini_api_key'] );
        }
        return $sanitized_input;
    }
}
EOF
