<?php
/**
 * AI SEO Optimizer: SEO Analyzer Page
 *
 * @package AI_SEO_Optimizer
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI_SEO_Optimizer_Analyzer_Page {

    private $api_client;

    public function __construct() {
        // API client will be created on demand.
    }

    /**
     * Formats and displays the analysis results from Gemini.
     *
     * @param string $api_data The raw string data from the Gemini API.
     */
    private function format_and_display_analysis_results( $api_data ) {
        // Normalize line breaks to \n for consistent processing
        $api_data = str_replace( ["\r\n", "\r"], "\n", $api_data );

        // Define known headings from our prompt.
        // The order matters for sequential parsing if we split by them.
        $headings = [
            'Keyword Usage',
            'Meta Title Suggestion',
            'Meta Description Suggestion',
            'Readability Assessment',
            'LSI Keywords/Related Terms',
            'SEO Strengths',
            'SEO Weaknesses/Areas for Improvement'
        ];

        $sections = [];
        $current_content = $api_data;

        // Attempt to split content by headings. This is a simple approach.
        // A more robust parser might use regex with lookaheads.
        $last_pos = 0;
        $found_headings = 0;

        // Split the text by lines to process
        $lines = explode("\n", $api_data);
        $current_section_key = null;
        $section_content_accumulator = [];

        foreach ($lines as $line) {
            $trimmed_line = trim($line);
            $is_heading = false;
            foreach ($headings as $heading_text) {
                // Check if the line starts with "**Heading Text:**" or "Heading Text:"
                if (preg_match('/^(\*\*|)(\s*'.preg_quote($heading_text, '/').'\s*)(\*\*|:)/i', $trimmed_line, $matches)) {
                    if ($current_section_key !== null && !empty(trim(implode("\n", $section_content_accumulator)))) {
                        $sections[$current_section_key] = trim(implode("\n", $section_content_accumulator));
                    }
                    $current_section_key = $heading_text;
                    $section_content_accumulator = [ trim(str_replace($matches[0], '', $trimmed_line)) ]; // Add rest of the heading line
                    $is_heading = true;
                    $found_headings++;
                    break;
                }
            }
            if (!$is_heading && $current_section_key !== null) {
                $section_content_accumulator[] = $line;
            } elseif (!$is_heading && $current_section_key === null) {
                // Content before any recognized heading (should ideally not happen with good API response)
                if (!isset($sections['Preamble'])) {
                    $sections['Preamble'] = '';
                }
                $sections['Preamble'] .= $line . "\n";
            }
        }
        // Add the last accumulated section
        if ($current_section_key !== null && !empty(trim(implode("\n", $section_content_accumulator)))) {
            $sections[$current_section_key] = trim(implode("\n", $section_content_accumulator));
        }

        if (empty($sections) || $found_headings < 2 ) { // If less than 2 headings found, parsing might have failed
            echo '<h3>' . esc_html__( 'Raw Analysis Output', 'ai-seo-optimizer' ) . '</h3>';
            echo '<p>' . esc_html__( 'Could not parse the analysis into sections. Displaying raw output:', 'ai-seo-optimizer' ) . '</p>';
            echo '<pre style="white-space: pre-wrap; word-wrap: break-word; padding: 10px; background-color: #fff; border: 1px solid #ddd;">' . esc_html( $api_data ) . '</pre>';
            return;
        }

        if (isset($sections['Preamble']) && !empty(trim($sections['Preamble']))) {
            echo '<div>' . nl2br( esc_html( trim($sections['Preamble']) ) ) . '</div>';
        }

        foreach ( $headings as $heading_text ) {
            if ( isset( $sections[ $heading_text ] ) && ! empty( trim( $sections[ $heading_text ] ) ) ) {
                echo '<div class="ai-seo-result-section">';
                echo '<h4>' . esc_html( $heading_text ) . '</h4>';

                $content = trim($sections[ $heading_text ]);

                // Specific formatting for certain sections
                if (in_array($heading_text, ['LSI Keywords/Related Terms', 'SEO Strengths', 'SEO Weaknesses/Areas for Improvement'])) {
                    // Try to parse as a list if lines start with common list markers
                    $list_items = preg_split('/\n\s*([*-]|\d+\.\s*)/', $content, -1, PREG_SPLIT_NO_EMPTY);
                     if (count($list_items) > 1 || preg_match('/^\s*([*-]|\d+\.\s*)/', $content)) {
                        echo '<ul>';
                        // Remove the first element if it's empty due to splitting on the first list item marker
                        if (empty(trim(preg_replace('/^\s*([*-]|\d+\.\s*)/', '', $list_items[0])))) {
                             // This check is tricky; let's assume Gemini gives clean lists or just use nl2br for now
                        }

                        // Simpler list approach: explode by newline, check each line
                        $lines_for_list = explode("\n", $content);
                        foreach ($lines_for_list as $list_line) {
                            $list_line_trimmed = trim($list_line);
                            if (!empty($list_line_trimmed)) {
                                // Remove potential leading list markers for cleaner display if Gemini adds them
                                $cleaned_item = preg_replace('/^\s*([*-]|\d+\.?\s*)\s*/', '', $list_line_trimmed);
                                echo '<li>' . nl2br( esc_html( $cleaned_item ) ). '</li>';
                            }
                        }
                        echo '</ul>';
                    } else {
                         echo '<div>' . nl2br( esc_html( $content ) ) . '</div>'; // Fallback if not a clear list
                    }
                } elseif ($heading_text === 'Meta Title Suggestion' || $heading_text === 'Meta Description Suggestion') {
                     echo '<div class="ai-seo-suggestion-box">' . nl2br( esc_html( $content ) ) . '</div>';
                }
                else {
                    echo '<div>' . nl2br( esc_html( $content ) ) . '</div>';
                }
                echo '</div>'; // .ai-seo-result-section
            }
        }
        ?>
        <style>
            .ai-seo-result-section { margin-bottom: 20px; padding: 15px; background-color: #fff; border: 1px solid #e0e0e0; border-radius: 4px; }
            .ai-seo-result-section h4 { margin-top: 0; margin-bottom: 10px; font-size: 1.1em; color: #2c3338; }
            .ai-seo-result-section ul { margin-left: 20px; }
            .ai-seo-result-section ul li { margin-bottom: 5px; }
            .ai-seo-suggestion-box { padding: 10px; background-color: #f9f9f9; border: 1px dashed #ccc; border-radius: 3px; }
        </style>
        <?php
    }

    /**
     * Handles the SEO Analyzer form submission and API call.
     */
    private function handle_analyzer_submission() {
        if ( ! isset( $_POST['ai_seo_submit_analyzer'] ) || ! isset( $_POST['ai_seo_analyzer_nonce_field'] ) ) {
            return null;
        }

        if ( ! wp_verify_nonce( $_POST['ai_seo_analyzer_nonce_field'], 'ai_seo_analyzer_nonce' ) ) {
            return ['error' => __( 'Nonce verification failed. Please try again.', 'ai-seo-optimizer' )];
        }

        $content_source = sanitize_text_field( $_POST['ai_seo_content_source'] );
        $content_url    = isset( $_POST['ai_seo_content_url'] ) ? esc_url_raw( $_POST['ai_seo_content_url'] ) : '';
        $content_text   = isset( $_POST['ai_seo_content_text'] ) ? wp_kses_post( $_POST['ai_seo_content_text'] ) : '';
        $target_language = sanitize_text_field( $_POST['ai_seo_target_language'] );
        $primary_keyword = sanitize_text_field( $_POST['ai_seo_primary_keyword'] );

        $actual_content = '';
        $content_title = '';

        if ( $content_source === 'url' ) {
            if ( empty( $content_url ) ) return ['error' => __( 'Please enter a URL.', 'ai-seo-optimizer' )];
            $post_id = url_to_postid( $content_url );
            if ( $post_id ) {
                $post = get_post( $post_id );
                if ( $post ) {
                    $actual_content = apply_filters('the_content', $post->post_content);
                    $actual_content = wp_strip_all_tags($actual_content, false);
                    $content_title = $post->post_title;
                } else { return ['error' => __( 'Could not get post for URL.', 'ai-seo-optimizer' )]; }
            } else {
                $response = wp_remote_get( $content_url );
                if ( is_wp_error( $response ) ) return ['error' => __( 'Failed to fetch URL: ', 'ai-seo-optimizer' ) . $response->get_error_message()];
                $body = wp_remote_retrieve_body( $response );
                $actual_content = wp_strip_all_tags( $body, false );
                if (preg_match('/<title>(.*?)<\/title>/is', $body, $matches)) $content_title = $matches[1];
            }
        } elseif ( $content_source === 'text' ) {
            if ( empty( $content_text ) ) return ['error' => __( 'Please enter text.', 'ai-seo-optimizer' )];
            $actual_content = $content_text;
        } else { return ['error' => __( 'Invalid source.', 'ai-seo-optimizer' )]; }

        if ( empty( $actual_content ) ) return ['error' => __( 'No content to analyze.', 'ai-seo-optimizer' )];
        if ( empty( $primary_keyword ) ) return ['error' => __( 'Please enter a keyword.', 'ai-seo-optimizer' )];

        if ( ! $this->api_client ) $this->api_client = new AI_SEO_Optimizer_Gemini_API();

        $language_name = ($target_language === 'th') ? 'Thai' : 'English';
        $prompt = sprintf(
            "You are an expert SEO content analyst. Analyze the following text which is in %s. " .
            "The primary target keyword for this content is: \"%s\".

" .
            "Content Title (if available): \"%s\"
" .
            "Content Text to Analyze:
\"%s\"

" .
            "Based on this, provide a comprehensive SEO analysis including the following sections. Ensure each section starts with its title in bold (e.g., **Keyword Usage:** or **Meta Title Suggestion:**):
" .
            "1. Keyword Usage: Analyze how well the primary keyword \"%s\" is used. Discuss its placement (title, headings, body, start/end of content), density, and semantic relevance. Suggest improvements.
" .
            "2. Meta Title Suggestion: Provide an optimized meta title (around 50-60 characters) incorporating the primary keyword \"%s\".
" .
            "3. Meta Description Suggestion: Provide an optimized meta description (around 150-160 characters) incorporating the primary keyword \"%s\" and a compelling call to action if appropriate.
" .
            "4. Readability Assessment: Evaluate the content's readability for the target language (%s). Provide a score or general assessment and suggest 2-3 specific ways to improve it.
" .
            "5. LSI Keywords/Related Terms: Suggest 3-5 LSI keywords or related terms that could enhance the content's SEO value for the primary keyword \"%s\". List them clearly.
" .
            "6. SEO Strengths: List 2-3 key SEO strengths of the current content. List them clearly.
" .
            "7. SEO Weaknesses/Areas for Improvement: List 2-3 key SEO weaknesses and actionable recommendations to improve them, beyond what's already covered. List them clearly.
" .
            "Present your analysis clearly.",
            $language_name, $primary_keyword, $content_title, $actual_content,
            $primary_keyword, $primary_keyword, $primary_keyword, $language_name, $primary_keyword
        );

        $generation_config = [];
        $api_response = $this->api_client->send_prompt( $prompt, $generation_config );
        return $api_response;
    }

    public function render_page() {
        $analysis_results = $this->handle_analyzer_submission();

        $current_source    = isset($_POST['ai_seo_content_source']) ? sanitize_text_field($_POST['ai_seo_content_source']) : 'url';
        $current_url       = isset($_POST['ai_seo_content_url']) ? esc_url($_POST['ai_seo_content_url']) : '';
        $current_text      = isset($_POST['ai_seo_content_text']) ? esc_textarea($_POST['ai_seo_content_text']) : '';
        $current_language  = isset($_POST['ai_seo_target_language']) ? sanitize_text_field($_POST['ai_seo_target_language']) : 'en';
        $current_keyword   = isset($_POST['ai_seo_primary_keyword']) ? esc_attr($_POST['ai_seo_primary_keyword']) : '';
        ?>
        <div class="wrap ai-seo-optimizer-analyzer-page">
            <h1><?php esc_html_e( 'AI SEO Content Analyzer', 'ai-seo-optimizer' ); ?></h1>
            <p><?php esc_html_e( 'Analyze your content with AI to get SEO suggestions. Supports English and Thai content.', 'ai-seo-optimizer' ); ?></p>

            <?php if ( $analysis_results && isset( $analysis_results['error'] ) ) : ?>
                <div class="notice notice-error is-dismissible"><p><?php echo esc_html( $analysis_results['error'] ); ?></p></div>
            <?php elseif ( $analysis_results && isset( $analysis_results['success'] ) && ! $analysis_results['success'] && isset( $analysis_results['error'] ) ) : ?>
                 <div class="notice notice-error is-dismissible"><p><?php echo esc_html( $analysis_results['error'] ); ?></p></div>
            <?php endif; ?>

            <form id="ai-seo-analyzer-form" method="post" action="">
                <?php wp_nonce_field( 'ai_seo_analyzer_nonce', 'ai_seo_analyzer_nonce_field' ); ?>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="ai-seo-content-source"><?php esc_html_e( 'Content Source', 'ai-seo-optimizer' ); ?></label></th>
                            <td>
                                <select id="ai-seo-content-source" name="ai_seo_content_source">
                                    <option value="url" <?php selected( $current_source, 'url' ); ?>><?php esc_html_e( 'WordPress Post/Page URL', 'ai-seo-optimizer' ); ?></option>
                                    <option value="text" <?php selected( $current_source, 'text' ); ?>><?php esc_html_e( 'Direct Text Input', 'ai-seo-optimizer' ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr class="ai-seo-url-input-row">
                            <th scope="row"><label for="ai-seo-content-url"><?php esc_html_e( 'Post/Page URL', 'ai-seo-optimizer' ); ?></label></th>
                            <td>
                                <input type="url" id="ai-seo-content-url" name="ai_seo_content_url" value="<?php echo $current_url; ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Enter URL of a post or page', 'ai-seo-optimizer' ); ?>">
                                <p class="description"><?php esc_html_e( 'Enter the full URL of a published post or page.', 'ai-seo-optimizer' ); ?></p>
                            </td>
                        </tr>
                        <tr class="ai-seo-text-input-row" style="display: none;">
                            <th scope="row"><label for="ai-seo-content-text"><?php esc_html_e( 'Content Text', 'ai-seo-optimizer' ); ?></label></th>
                            <td>
                                <textarea id="ai-seo-content-text" name="ai_seo_content_text" rows="10" class="large-text" placeholder="<?php esc_attr_e( 'Paste your content here for analysis', 'ai-seo-optimizer' ); ?>"><?php echo $current_text; ?></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ai-seo-target-language"><?php esc_html_e( 'Content Language', 'ai-seo-optimizer' ); ?></label></th>
                            <td>
                                <select id="ai-seo-target-language" name="ai_seo_target_language">
                                    <option value="en" <?php selected( $current_language, 'en' ); ?>><?php esc_html_e( 'English', 'ai-seo-optimizer' ); ?></option>
                                    <option value="th" <?php selected( $current_language, 'th' ); ?>><?php esc_html_e( 'Thai', 'ai-seo-optimizer' ); ?></option>
                                </select>
                                <p class="description"><?php esc_html_e( 'Select the primary language of your content.', 'ai-seo-optimizer' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ai-seo-primary-keyword"><?php esc_html_e( 'Primary Target Keyword', 'ai-seo-optimizer' ); ?></label></th>
                            <td>
                                <input type="text" id="ai-seo-primary-keyword" name="ai_seo_primary_keyword" value="<?php echo $current_keyword; ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Enter your main keyword', 'ai-seo-optimizer' ); ?>">
                                <p class="description"><?php esc_html_e( 'The main keyword or phrase you are targeting for this content.', 'ai-seo-optimizer' ); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <?php submit_button( __( 'Analyze Content', 'ai-seo-optimizer' ), 'primary', 'ai_seo_submit_analyzer' ); ?>
            </form>

            <div id="ai-seo-analyzer-results" style="margin-top: 20px; padding: 15px; border: 1px solid #ccd0d4; background-color: #f0f0f1; min-height: 100px;">
                <h2 style="margin-top:0; border-bottom: 1px solid #ccd0d4; padding-bottom: 10px;"><?php esc_html_e( 'Analysis Results', 'ai-seo-optimizer' ); ?></h2>
                <?php if ( $analysis_results && isset( $analysis_results['success'] ) && $analysis_results['success'] && isset( $analysis_results['data'] ) ) : ?>
                    <div class="notice notice-success is-dismissible" style="margin-bottom: 15px;"><p><?php esc_html_e( 'Analysis successful!', 'ai-seo-optimizer' ); ?></p></div>
                    <?php $this->format_and_display_analysis_results( $analysis_results['data'] ); ?>
                <?php elseif ($analysis_results && isset($analysis_results['error'])) : // Already handled by error notices above, but as a fallback
                    // Error already displayed by notice block above
                ?>
                <?php elseif (!$analysis_results) : ?>
                     <p><?php esc_html_e( 'Submit the form above to see analysis results.', 'ai-seo-optimizer' ); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                function toggleContentSourceFields() {
                    var source = $('#ai-seo-content-source').val();
                    if (source === 'url') {
                        $('.ai-seo-url-input-row').show();
                        $('.ai-seo-text-input-row').hide();
                    } else {
                        $('.ai-seo-url-input-row').hide();
                        $('.ai-seo-text-input-row').show();
                    }
                }
                $('#ai-seo-content-source').on('change', toggleContentSourceFields);
                toggleContentSourceFields();
            });
        </script>
        <?php
    }
}
EOF
