<?php
/**
 * AI SEO Optimizer: AI Blog Generator Page
 *
 * @package AI_SEO_Optimizer
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI_SEO_Optimizer_Blog_Generator_Page {

    private $api_client;

    public function __construct() {
        add_action('wp_ajax_ai_seo_create_draft_post', [__CLASS__, 'ajax_create_draft_post']);
    }

    public static function ajax_create_draft_post() {
        check_ajax_referer('ai_seo_create_post_nonce', 'nonce');

        if ( ! current_user_can('edit_posts') ) {
            wp_send_json_error(['message' => __('You do not have permission to create posts.', 'ai-seo-optimizer')]);
            return;
        }

        $title    = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $content  = isset($_POST['content']) ? wp_kses_post($_POST['content']) : ''; // Allows some HTML like paragraphs, headings from markdown
        $language = isset($_POST['language']) ? sanitize_text_field($_POST['language']) : 'en';
        $keywords_str = isset($_POST['keywords']) ? sanitize_text_field($_POST['keywords']) : '';

        if ( empty($title) ) {
            wp_send_json_error(['message' => __('Post title cannot be empty.', 'ai-seo-optimizer')]);
            return;
        }
        if ( empty($content) ) {
            wp_send_json_error(['message' => __('Post content cannot be empty.', 'ai-seo-optimizer')]);
            return;
        }

        // Convert basic markdown to HTML for content (## -> h2, ### -> h3, \n\n -> paragraphs)
        // Replace ## headings
        $html_content = preg_replace('/^##\s+(.*)/m', '<h2>$1</h2>', $content);
        // Replace ### headings
        $html_content = preg_replace('/^###\s+(.*)/m', '<h3>$1</h3>', $html_content);
        // Convert double line breaks to paragraphs, then single line breaks to <br> within those paragraphs
        $html_content = wpautop($html_content);


        $post_data = [
            'post_title'   => $title,
            'post_content' => $html_content,
            'post_status'  => 'draft',
            'post_author'  => get_current_user_id(),
            'post_type'    => 'post',
        ];

        $post_id = wp_insert_post($post_data, true); // true for WP_Error object on failure

        if ( is_wp_error($post_id) ) {
            wp_send_json_error(['message' => $post_id->get_error_message()]);
            return;
        }

        // Set language as post meta
        if ($post_id) {
            update_post_meta($post_id, '_ai_seo_content_language', $language);

            // Set keywords as tags
            if (!empty($keywords_str)) {
                $tags = array_map('trim', explode(',', $keywords_str));
                wp_set_post_tags($post_id, $tags, false); // false to replace existing tags
            }
        }

        $edit_link = get_edit_post_link($post_id, 'raw');
        wp_send_json_success([
            'message' => __('Draft post created successfully!', 'ai-seo-optimizer'),
            'post_id' => $post_id,
            'edit_link' => $edit_link
        ]);
    }


    private function handle_blog_generation_submission() {
        if ( ! isset( $_POST['ai_seo_submit_blog_generator'] ) || ! isset( $_POST['ai_seo_blog_generator_nonce_field'] ) ) {
            return null;
        }
        if ( ! wp_verify_nonce( $_POST['ai_seo_blog_generator_nonce_field'], 'ai_seo_blog_generator_nonce' ) ) {
            return ['error' => __( 'Nonce verification failed.', 'ai-seo-optimizer' )];
        }
        $topic    = sanitize_text_field( $_POST['ai_seo_blog_topic'] );
        $keywords = sanitize_text_field( $_POST['ai_seo_blog_keywords'] );
        $tone     = sanitize_text_field( $_POST['ai_seo_blog_tone'] );
        $language = sanitize_text_field( $_POST['ai_seo_blog_language'] );
        $outline  = isset( $_POST['ai_seo_blog_outline'] ) ? sanitize_textarea_field( $_POST['ai_seo_blog_outline'] ) : '';
        if ( empty( $topic ) ) return ['error' => __( 'Blog post topic cannot be empty.', 'ai-seo-optimizer' )];
        if ( ! $this->api_client ) $this->api_client = new AI_SEO_Optimizer_Gemini_API();
        $language_name = ($language === 'th') ? 'Thai' : 'English';
        $prompt_parts = [
            sprintf( "You are an expert blog post writer. Your task is to generate a comprehensive and engaging blog post in %s.", $language_name ),
            sprintf( "The main topic or title idea for the blog post is: \"%s\".", $topic ),
        ];
        if ( ! empty( $keywords ) ) $prompt_parts[] = sprintf( "Please incorporate the following primary keywords naturally: %s.", $keywords );
        $prompt_parts[] = sprintf( "The desired tone of voice is: %s.", $tone );
        if ( ! empty( $outline ) ) $prompt_parts[] = "Please follow this outline or include these key points:\n" . $outline;
        $prompt_parts[] = "Structure: clear introduction, well-structured body paragraphs, and a concluding summary.";
        $prompt_parts[] = "Generate only the blog post content itself, without any surrounding text. Use appropriate paragraph breaks. For subheadings, use markdown '##' for H2 and '###' for H3.";
        $prompt = implode( "\n\n", $prompt_parts );
        return $this->api_client->send_prompt( $prompt, [] );
    }

    public function render_page() {
        $generation_results = $this->handle_blog_generation_submission();
        $current_topic    = isset($_POST['ai_seo_blog_topic']) ? esc_attr($_POST['ai_seo_blog_topic']) : '';
        $current_keywords = isset($_POST['ai_seo_blog_keywords']) ? esc_attr($_POST['ai_seo_blog_keywords']) : '';
        $current_tone     = isset($_POST['ai_seo_blog_tone']) ? sanitize_text_field($_POST['ai_seo_blog_tone']) : 'professional';
        $current_language = isset($_POST['ai_seo_blog_language']) ? sanitize_text_field($_POST['ai_seo_blog_language']) : 'en';
        $current_outline  = isset($_POST['ai_seo_blog_outline']) ? esc_textarea($_POST['ai_seo_blog_outline']) : '';
        $current_image_prompt = isset($_POST['ai_seo_image_prompt']) ? esc_attr($_POST['ai_seo_image_prompt']) : $current_topic;
        $generated_content = '';
        if ( $generation_results && isset( $generation_results['success'] ) && $generation_results['success'] && isset($generation_results['data']) ) {
            $generated_content = $generation_results['data'];
        }
        $tones = [
            'professional' => __( 'Professional', 'ai-seo-optimizer' ), 'casual' => __( 'Casual', 'ai-seo-optimizer' ),
            'formal' => __( 'Formal', 'ai-seo-optimizer' ), 'friendly' => __( 'Friendly', 'ai-seo-optimizer' ),
            'witty' => __( 'Witty', 'ai-seo-optimizer' ), 'informative' => __( 'Informative', 'ai-seo-optimizer' ),
            'persuasive' => __( 'Persuasive', 'ai-seo-optimizer' ),
        ];
        ?>
        <div class="wrap ai-seo-optimizer-blog-generator-page">
            <h1><?php esc_html_e( 'AI Blog Post Generator', 'ai-seo-optimizer' ); ?></h1>
            <p><?php esc_html_e( 'Generate engaging blog posts using AI. Supports English and Thai languages.', 'ai-seo-optimizer' ); ?></p>

            <div id="ai-seo-ajax-messages"></div> <!-- For AJAX success/error messages -->

            <?php if ( $generation_results && isset( $generation_results['error'] ) ) : ?>
                <div class="notice notice-error is-dismissible"><p><?php echo esc_html( $generation_results['error'] ); ?></p></div>
            <?php elseif ( $generation_results && isset( $generation_results['success'] ) && !$generation_results['success'] && isset($generation_results['error']) ) : ?>
                 <div class="notice notice-error is-dismissible"><p><?php echo esc_html( $generation_results['error'] ); ?></p></div>
            <?php endif; ?>
             <?php if ( $generation_results && isset( $generation_results['success'] ) && $generation_results['success'] ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Blog post generated successfully! Review below.', 'ai-seo-optimizer' ); ?></p></div>
            <?php endif; ?>

            <form id="ai-seo-blog-generator-form" method="post" action="">
                <?php wp_nonce_field( 'ai_seo_blog_generator_nonce', 'ai_seo_blog_generator_nonce_field' ); ?>
                <table class="form-table"> <!-- Form fields -->
                    <tbody>
                        <tr>
                            <th scope="row"><label for="ai-seo-blog-topic"><?php esc_html_e( 'Blog Post Topic / Title Idea', 'ai-seo-optimizer' ); ?></label></th>
                            <td><input type="text" id="ai-seo-blog-topic" name="ai_seo_blog_topic" value="<?php echo $current_topic; ?>" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ai-seo-blog-keywords"><?php esc_html_e( 'Primary Keywords', 'ai-seo-optimizer' ); ?></label></th>
                            <td><input type="text" id="ai-seo-blog-keywords" name="ai_seo_blog_keywords" value="<?php echo $current_keywords; ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ai-seo-blog-tone"><?php esc_html_e( 'Tone of Voice', 'ai-seo-optimizer' ); ?></label></th>
                            <td>
                                <select id="ai-seo-blog-tone" name="ai_seo_blog_tone">
                                    <?php foreach ( $tones as $value => $label ) : ?>
                                        <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_tone, $value ); ?>><?php echo esc_html( $label ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ai-seo-blog-language"><?php esc_html_e( 'Target Language', 'ai-seo-optimizer' ); ?></label></th>
                            <td>
                                <select id="ai-seo-blog-language" name="ai_seo_blog_language">
                                    <option value="en" <?php selected( $current_language, 'en' ); ?>><?php esc_html_e( 'English', 'ai-seo-optimizer' ); ?></option>
                                    <option value="th" <?php selected( $current_language, 'th' ); ?>><?php esc_html_e( 'Thai', 'ai-seo-optimizer' ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="ai-seo-blog-outline"><?php esc_html_e( 'Outline / Key Points (Optional)', 'ai-seo-optimizer' ); ?></label></th>
                            <td><textarea id="ai-seo-blog-outline" name="ai_seo_blog_outline" rows="5" class="large-text"><?php echo $current_outline; ?></textarea></td>
                        </tr>
                    </tbody>
                </table>
                <?php submit_button( __( 'Generate Blog Post', 'ai-seo-optimizer' ), 'primary', 'ai_seo_submit_blog_generator' ); ?>
            </form>

            <div id="ai-seo-blog-generator-results" style="margin-top: 30px;">
                <h2 style="border-bottom: 1px solid #ccd0d4; padding-bottom: 10px;"><?php esc_html_e( 'Generated Content & Image', 'ai-seo-optimizer' ); ?></h2>
                <div id="ai-seo-generated-content-section" style="margin-bottom: 20px;">
                    <h3><?php esc_html_e( 'Blog Post Content', 'ai-seo-optimizer' ); ?></h3>
                    <div id="ai-seo-generated-content-area" style="padding: 1px; border: 1px solid #ccd0d4; background-color: #fff; min-height: 150px;">
                        <?php if ( !empty($generated_content) ) : ?>
                            <textarea rows="15" class="large-text" id="ai-seo-generated-blog-content" readonly style="background-color: #f9f9f9;"><?php echo esc_textarea( $generated_content ); ?></textarea>
                        <?php else : ?>
                            <p><?php esc_html_e( 'Generated blog post will appear here...', 'ai-seo-optimizer' ); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div id="ai-seo-image-generator-section" style="padding: 15px; border: 1px solid #e0e0e0; background-color: #f9f9f9; border-radius:4px;">
                    <h3><?php esc_html_e( 'Featured Image Suggestion', 'ai-seo-optimizer' ); ?></h3>
                    <table class="form-table"> <!-- Image prompt fields -->
                         <tbody>
                            <tr>
                                <th scope="row"><label for="ai-seo-image-prompt"><?php esc_html_e( 'Image Prompt', 'ai-seo-optimizer' ); ?></label></th>
                                <td>
                                    <textarea id="ai-seo-image-prompt" name="ai_seo_image_prompt" rows="3" class="large-text"><?php echo esc_textarea($current_image_prompt); ?></textarea>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"></th>
                                <td>
                                    <button type="button" id="ai-seo-suggest-image-prompt-button" class="button"><?php esc_html_e( 'Suggest Prompt with AI', 'ai-seo-optimizer' ); ?></button>
                                    <button type="button" id="ai-seo-generate-image-button" class="button-primary" style="margin-left: 10px;"><?php esc_html_e( 'Generate Image', 'ai-seo-optimizer' ); ?></button>
                                    <p class="description" style="margin-top:5px;"><?php esc_html_e( 'Image features are placeholders.', 'ai-seo-optimizer' ); ?></p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div id="ai-seo-generated-image-display" style="margin-top: 15px; padding:10px; border: 1px dashed #ccc; min-height: 100px; background-color: #fff; text-align: center;">
                        <p><?php esc_html_e( 'Generated image will appear here.', 'ai-seo-optimizer' ); ?></p>
                    </div>
                </div>
                <div id="ai-seo-create-post-section" style="margin-top:20px; padding-top:20px; border-top: 1px solid #ccd0d4;">
                     <button type="button" id="ai-seo-create-wp-post-button" class="button button-hero" <?php disabled(empty($generated_content)); ?>>
                        <?php esc_html_e( 'Create WordPress Post (Draft)', 'ai-seo-optimizer' ); ?>
                    </button>
                    <p class="description" style="margin-top:5px;"><?php esc_html_e( 'This will create a new draft post with the generated content.', 'ai-seo-optimizer' ); ?></p>
                </div>
            </div>
        </div>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                var createPostNonce = '<?php echo wp_create_nonce('ai_seo_create_post_nonce'); ?>';

                $('#ai-seo-suggest-image-prompt-button').on('click', function() {
                    alert('<?php echo esc_js(__( "Feature Coming Soon: AI will suggest an image prompt.", "ai-seo-optimizer" )); ?>');
                });
                $('#ai-seo-generate-image-button').on('click', function() {
                    alert('<?php echo esc_js(__( "Feature Coming Soon: This will generate an image.", "ai-seo-optimizer" )); ?>');
                });

                $('#ai-seo-create-wp-post-button').on('click', function() {
                    var $button = $(this);
                    var $ajaxMessages = $('#ai-seo-ajax-messages');
                    $ajaxMessages.html('<p><?php echo esc_js(__('Processing...', 'ai-seo-optimizer')); ?></p>').removeClass('notice-success notice-error').addClass('notice-info').show();
                    $button.prop('disabled', true);

                    var postTitle = $('#ai-seo-blog-topic').val();
                    var postContent = $('#ai-seo-generated-blog-content').val(); // Raw markdown/text from Gemini
                    var postLanguage = $('#ai-seo-blog-language').val();
                    var postKeywords = $('#ai-seo-blog-keywords').val();

                    if (!postTitle || !postContent) {
                        $ajaxMessages.html('<p><?php echo esc_js(__('Title and content are required to create a post.', 'ai-seo-optimizer')); ?></p>').removeClass('notice-info notice-success').addClass('notice-error').show();
                        $button.prop('disabled', false);
                        return;
                    }

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'ai_seo_create_draft_post',
                            nonce: createPostNonce,
                            title: postTitle,
                            content: postContent,
                            language: postLanguage,
                            keywords: postKeywords
                        },
                        success: function(response) {
                            if (response.success) {
                                var successMsg = '<p>' + response.data.message +
                                    ' <a href="' + response.data.edit_link + '" target="_blank"><?php echo esc_js(__("Edit Post", "ai-seo-optimizer")); ?></a>' +
                                    ' (ID: ' + response.data.post_id + ')</p>';
                                $ajaxMessages.html(successMsg).removeClass('notice-info notice-error').addClass('notice notice-success is-dismissible').show();
                            } else {
                                $ajaxMessages.html('<p>' + response.data.message + '</p>').removeClass('notice-info notice-success').addClass('notice notice-error is-dismissible').show();
                            }
                        },
                        error: function(xhr, status, error) {
                            var errorMsg = '<?php echo esc_js(__('An error occurred:', 'ai-seo-optimizer')); ?> ' + error;
                            $ajaxMessages.html('<p>' + errorMsg + '</p>').removeClass('notice-info notice-success').addClass('notice notice-error is-dismissible').show();
                        },
                        complete: function() {
                            $button.prop('disabled', ($('#ai-seo-generated-blog-content').val() === ''));
                        }
                    });
                });
                // Disable create post button if no content
                 $('#ai-seo-generated-blog-content').on('input change', function() {
                    $('#ai-seo-create-wp-post-button').prop('disabled', $(this).val() === '');
                }).trigger('change');


            });
        </script>
        <?php
    }
}
EOF
