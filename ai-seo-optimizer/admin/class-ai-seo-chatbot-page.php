<?php
/**
 * AI SEO Optimizer: SEO Chatbot Assistant Page
 *
 * @package AI_SEO_Optimizer
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI_SEO_Optimizer_Chatbot_Page {

    private static $api_client_static; // For AJAX handler

    public function __construct() {
        add_action('wp_ajax_ai_seo_chatbot_message', [__CLASS__, 'ajax_handle_chatbot_message']);
        // Initialize static API client once if needed, or it can be done in the AJAX handler
        if (null === self::$api_client_static) {
            // self::$api_client_static = new AI_SEO_Optimizer_Gemini_API(); // Not strictly needed here, can be in AJAX
        }
    }

    public static function ajax_handle_chatbot_message() {
        check_ajax_referer('ai_seo_chatbot_nonce', 'nonce');

        if ( ! current_user_can('manage_options') ) { // Or a more specific capability
            wp_send_json_error(['reply' => __('You do not have permission to use this chatbot.', 'ai-seo-optimizer')]);
            return;
        }

        $user_message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        $language     = isset($_POST['language']) ? sanitize_text_field($_POST['language']) : 'en';

        if ( empty($user_message) ) {
            wp_send_json_error(['reply' => __('Message cannot be empty.', 'ai-seo-optimizer')]);
            return;
        }

        // Instantiate API client if not already done (or if you prefer it per request)
        if (null === self::$api_client_static) {
            self::$api_client_static = new AI_SEO_Optimizer_Gemini_API();
        }

        $language_name = ($language === 'th') ? 'Thai' : 'English';

        // Simple prompt for now. Can be enhanced with history later.
        $prompt = sprintf(
            "You are a helpful and concise SEO Chatbot Assistant. " .
            "The user is interacting in %s. Please respond in %s. " .
            "User's question: \"%s\"",
            $language_name, $language_name, $user_message
        );

        $api_response = self::$api_client_static->send_prompt($prompt);

        if ( $api_response['success'] && !empty($api_response['data']) ) {
            wp_send_json_success(['reply' => $api_response['data']]);
        } else {
            $error_message = isset($api_response['error']) ? $api_response['error'] : __('An unknown error occurred with the AI.', 'ai-seo-optimizer');
            wp_send_json_error(['reply' => $error_message]);
        }
    }

    /**
     * Renders the SEO Chatbot Assistant page.
     */
    public function render_page() {
        $chat_nonce = wp_create_nonce('ai_seo_chatbot_nonce');
        ?>
        <div class="wrap ai-seo-optimizer-chatbot-page">
            <h1><?php esc_html_e( 'SEO Chatbot Assistant', 'ai-seo-optimizer' ); ?></h1>
            <p><?php esc_html_e( 'Ask SEO-related questions and get insights from our AI assistant. Supports English and Thai.', 'ai-seo-optimizer' ); ?></p>

            <div id="ai-seo-chatbot-container" style="max-width: 700px;">
                <div id="ai-seo-chatbot-history" style="height: 400px; border: 1px solid #ccd0d4; padding: 10px; overflow-y: auto; background-color: #fff; margin-bottom: 15px;">
                    <div class="chat-message bot">
                        <p><?php echo nl2br(esc_html__( "Hello! I'm your AI SEO Assistant.\nHow can I help you with your SEO strategy, keyword research, content optimization, or technical SEO questions today?", "ai-seo-optimizer" )); ?></p>
                    </div>
                </div>

                <form id="ai-seo-chatbot-form">
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th scope="row" style="width: auto; padding-right:5px;">
                                    <label for="ai-seo-chatbot-input" class="screen-reader-text"><?php esc_html_e( 'Your Question', 'ai-seo-optimizer' ); ?></label>
                                     <select id="ai-seo-chatbot-language" name="ai_seo_chatbot_language" title="<?php esc_attr_e('Select chat language', 'ai-seo-optimizer'); ?>" style="vertical-align: middle; margin-right:5px;">
                                        <option value="en" selected><?php esc_html_e( 'EN', 'ai-seo-optimizer' ); ?></option>
                                        <option value="th"><?php esc_html_e( 'TH', 'ai-seo-optimizer' ); ?></option>
                                    </select>
                                </th>
                                <td style="padding-left:0; display:flex;">
                                    <textarea id="ai-seo-chatbot-input" name="ai_seo_chatbot_input" rows="2" class="large-text" placeholder="<?php esc_attr_e( 'Type your SEO question here...', 'ai-seo-optimizer' ); ?>" style="flex-grow:1; margin-right:10px;"></textarea>
                                    <button type="submit" id="ai-seo-chatbot-send-button" class="button button-primary" style="height:auto;">
                                        <?php esc_html_e( 'Send', 'ai-seo-optimizer' ); ?>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </form>
            </div>
        </div>
        <style type="text/css">
            .ai-seo-chatbot-page #ai-seo-chatbot-history { scroll-behavior: smooth; }
            .ai-seo-chatbot-page #ai-seo-chatbot-history .chat-message { display: flex; margin-bottom: 10px; }
            .ai-seo-chatbot-page #ai-seo-chatbot-history .chat-message p {
                padding: 8px 12px; border-radius: 7px; max-width: 85%; word-wrap: break-word; margin:0;
            }
            .ai-seo-chatbot-page #ai-seo-chatbot-history .chat-message.user p {
                background-color: #0073aa; color: #fff; margin-left: auto;
            }
            .ai-seo-chatbot-page #ai-seo-chatbot-history .chat-message.bot p {
                background-color: #e5e5e5; color: #333; margin-right: auto;
            }
             .ai-seo-chatbot-page #ai-seo-chatbot-history .chat-message.typing-indicator p {
                background-color: #f0f0f0; color: #777; font-style: italic;
            }
            .ai-seo-chatbot-page #ai-seo-chatbot-form textarea { resize: vertical; }
        </style>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                var chatHistory = $('#ai-seo-chatbot-history');
                var chatForm = $('#ai-seo-chatbot-form');
                var chatInput = $('#ai-seo-chatbot-input');
                var sendButton = $('#ai-seo-chatbot-send-button');
                var chatNonce = '<?php echo $chat_nonce; ?>';

                function appendMessage(message, sender) {
                    var messageHtml = '<div class="chat-message ' + sender + '"><p>' + message.replace(/\n/g, '<br>') + '</p></div>';
                    chatHistory.append(messageHtml);
                    chatHistory.scrollTop(chatHistory[0].scrollHeight);
                }

                function showTypingIndicator() {
                    chatHistory.append('<div class="chat-message bot typing-indicator"><p><?php echo esc_js(__("AI is typing...", "ai-seo-optimizer")); ?></p></div>');
                    chatHistory.scrollTop(chatHistory[0].scrollHeight);
                }

                function removeTypingIndicator() {
                    chatHistory.find('.typing-indicator').parent().remove();
                }

                chatForm.on('submit', function(e) {
                    e.preventDefault();
                    var userMessage = chatInput.val().trim();
                    var selectedLanguage = $('#ai-seo-chatbot-language').val();

                    if (userMessage === '') {
                        return;
                    }

                    appendMessage(userMessage, 'user');
                    chatInput.val('');
                    sendButton.prop('disabled', true);
                    showTypingIndicator();

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'ai_seo_chatbot_message',
                            nonce: chatNonce,
                            message: userMessage,
                            language: selectedLanguage
                        },
                        success: function(response) {
                            removeTypingIndicator();
                            if (response.success) {
                                appendMessage(response.data.reply, 'bot');
                            } else {
                                var errorMessage = response.data.reply || '<?php echo esc_js(__("An error occurred.", "ai-seo-optimizer")); ?>';
                                appendMessage(errorMessage, 'bot error-message'); // Add error class for styling if needed
                            }
                        },
                        error: function(xhr, status, error) {
                            removeTypingIndicator();
                            var errorMessage = '<?php echo esc_js(__("AJAX request failed:", "ai-seo-optimizer")); ?> ' + error;
                            appendMessage(errorMessage, 'bot error-message');
                        },
                        complete: function() {
                            sendButton.prop('disabled', false);
                            chatInput.focus();
                        }
                    });
                });

                // Allow Enter to send, Shift+Enter for newline
                chatInput.on('keypress', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        chatForm.trigger('submit');
                    }
                });
            });
        </script>
        <?php
    }
}
EOF
