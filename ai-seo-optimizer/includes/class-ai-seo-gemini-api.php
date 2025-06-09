<?php
/**
 * AI SEO Optimizer Gemini API Client
 *
 * @package AI_SEO_Optimizer
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI_SEO_Optimizer_Gemini_API {

    private $api_key;

    public function __construct() {
        $this->api_key = $this->get_api_key();
    }

    /**
     * Retrieves the Gemini API key from WordPress options.
     *
     * @return string The API key, or empty string if not set.
     */
    private function get_api_key() {
        $options = get_option( 'ai_seo_optimizer_settings' );
        return isset( $options['gemini_api_key'] ) ? trim( $options['gemini_api_key'] ) : '';
    }

    /**
     * Sends a prompt to the Google Gemini API.
     *
     * @param string $prompt_text The prompt text to send.
     * @param array $generation_config Optional generation config for the API.
     * @return array Associative array with 'success' (boolean) and 'data' (string) or 'error' (string).
     */
    public function send_prompt( $prompt_text, $generation_config = [] ) {
        if ( empty( $this->api_key ) ) {
            return [
                'success' => false,
                'error' => __( 'API Key is not configured.', 'ai-seo-optimizer' ),
            ];
        }

        // Gemini API endpoint for gemini-pro model
        $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $this->api_key;

        $request_body = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $prompt_text,
                        ],
                    ],
                ],
            ],
        ];

        // Add optional generation configuration
        if ( ! empty( $generation_config ) ) {
            $request_body['generationConfig'] = $generation_config;
            // Example: $generation_config = [
            // 'temperature' => 0.7,
            // 'topK' => 40,
            // 'topP' => 0.95,
            // 'maxOutputTokens' => 1024,
            // ];
        }

        // Safety settings can be added here if required by the application
        // 'safetySettings' => [
        //     [
        //         'category' => 'HARM_CATEGORY_HARASSMENT',
        //         'threshold' => 'BLOCK_MEDIUM_AND_ABOVE',
        //     ],
        //     // ... other categories
        // ],

        $args = [
            'method'  => 'POST',
            'headers' => [
                'Content-Type' => 'application/json',
                // User-Agent might be defined globally or via a constant if AI_SEO_OPTIMIZER_VERSION is not available here
                // For now, let's assume AI_SEO_OPTIMIZER_VERSION is accessible or handle it.
                // 'User-Agent'   => 'WordPress/AI_SEO_Optimizer_Plugin/' . (defined('AI_SEO_OPTIMIZER_VERSION') ? AI_SEO_OPTIMIZER_VERSION : '0.1.0'),
            ],
            'body'    => wp_json_encode( $request_body ),
            'timeout' => 60, // Seconds
        ];

        // Ensure AI_SEO_OPTIMIZER_VERSION is available or fallback
        $plugin_version = defined('AI_SEO_OPTIMIZER_VERSION') ? AI_SEO_OPTIMIZER_VERSION : '0.1.0';
        $args['headers']['User-Agent'] = 'WordPress/AI_SEO_Optimizer_Plugin/' . $plugin_version;


        $response = wp_remote_post( $api_url, $args );

        if ( is_wp_error( $response ) ) {
            // error_log( 'AI SEO Optimizer - WP Error: ' . $response->get_error_message() );
            return [
                'success' => false,
                'error'   => $response->get_error_message(),
            ];
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        $decoded_body  = json_decode( $response_body, true );

        if ( $response_code !== 200 ) {
            $error_message = __( 'API request failed.', 'ai-seo-optimizer' );
            if ( isset( $decoded_body['error']['message'] ) ) {
                $error_message .= ' Details: ' . $decoded_body['error']['message'];
            } elseif ( ! empty( $response_body ) ) {
                // Avoid overly verbose errors in UI, but good for logs
                // error_log( 'AI SEO Optimizer - API Error (' . $response_code . '): ' . $response_body );
                $error_message .= ' ' . sprintf( __( 'Server responded with code %d.', 'ai-seo-optimizer' ), $response_code );
            }
            return [
                'success' => false,
                'error'   => $error_message,
            ];
        }

        if ( ! isset( $decoded_body['candidates'][0]['content']['parts'][0]['text'] ) ) {
            // error_log('AI SEO Optimizer - Gemini API unexpected response structure: ' . $response_body);
            // Check for safety ratings and blocked prompts
            if (isset($decoded_body['promptFeedback']['blockReason'])) {
                return [
                    'success' => false,
                    'error'   => __( 'Content blocked due to safety settings. Reason: ', 'ai-seo-optimizer' ) . $decoded_body['promptFeedback']['blockReason'],
                ];
            }
            if (empty($decoded_body['candidates']) || empty($decoded_body['candidates'][0]['content'])) {
                 return [
                    'success' => false,
                    'error'   => __( 'Content generation failed or response was empty. This might be due to safety filters or other restrictions.', 'ai-seo-optimizer' ),
                ];
            }
            return [
                'success' => false,
                'error'   => __( 'Unexpected response structure from API. No content found.', 'ai-seo-optimizer' ),
            ];
        }

        return [
            'success' => true,
            'data'    => $decoded_body['candidates'][0]['content']['parts'][0]['text'],
        ];
    }
}
EOF
