<?php
/**
 * OpenAI service for chat completions.
 *
 * @package OpenAI_WP_Integration_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Provides helper methods for interacting with the OpenAI API.
 */
class Owp_OpenAI_Service {
    /**
     * OpenAI API key.
     *
     * @var string
     */
    private $api_key;

    /**
     * Default OpenAI model.
     *
     * @var string
     */
    private $model;

    /**
     * Initialize service with API key and optional model name.
     *
     * @param string $api_key OpenAI API key.
     * @param string $model   Optional default model name.
     */
    public function __construct( $api_key, $model = 'gpt-4' ) {
        $this->api_key = $api_key;
        $this->model   = ! empty( $model ) ? $model : 'gpt-4';
    }

    /**
     * Perform a chat completion request against the OpenAI API.
     *
     * @param array  $messages Chat messages formatted for the API.
     * @param string $model    Optional model override.
     *
     * @return array|WP_Error
     */
    public function chat( $messages, $model = 'gpt-4' ) {
        if ( empty( $this->api_key ) ) {
            return new WP_Error( 'owp_missing_openai_key', __( 'OpenAI API key is not configured.', 'openai-wp-integration-pro' ), array( 'status' => 400 ) );
        }

        $resolved_model = ! empty( $model ) ? $model : $this->model;

        $request_args = array(
            'method'  => 'POST',
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
            ),
            'body'    => wp_json_encode(
                array(
                    'model'    => $resolved_model,
                    'messages' => $messages,
                )
            ),
        );

        $response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', $request_args );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( 200 !== $code ) {
            $error_message = isset( $data['error']['message'] ) ? $data['error']['message'] : __( 'OpenAI API request failed.', 'openai-wp-integration-pro' );
            return new WP_Error( 'owp_openai_request_failed', $error_message, array( 'status' => $code ) );
        }

        return $data;
    }

    /**
     * Generate a concise summary from provided text.
     *
     * @param string $text Source text to summarize.
     *
     * @return string|WP_Error
     */
    public function generate_summary_from_text( $text ) {
        if ( empty( $text ) ) {
            return new WP_Error( 'owp_missing_text', __( 'Text is required to generate a summary.', 'openai-wp-integration-pro' ), array( 'status' => 400 ) );
        }

        $messages = array(
            array(
                'role'    => 'system',
                'content' => __( 'Summarize the provided content in 2-3 short sentences. Use plain language suitable for WordPress posts.', 'openai-wp-integration-pro' ),
            ),
            array(
                'role'    => 'user',
                'content' => $text,
            ),
        );

        $response = $this->chat( $messages, $this->model );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        return $this->extract_message_content( $response );
    }

    /**
     * Generate title suggestions from text.
     *
     * @param string $text  Source text to derive titles from.
     * @param int    $count Number of titles to return.
     *
     * @return array|WP_Error
     */
    public function generate_titles( $text, $count = 3 ) {
        if ( empty( $text ) ) {
            return new WP_Error( 'owp_missing_text', __( 'Text is required to generate titles.', 'openai-wp-integration-pro' ), array( 'status' => 400 ) );
        }

        $count     = absint( $count );
        $count     = ( $count > 0 ) ? $count : 3;
        $messages  = array(
            array(
                'role'    => 'system',
                'content' => sprintf(
                    /* translators: %d: number of requested titles */
                    __( 'Generate %d concise, engaging titles for the provided content. Return each title on its own line without numbering.', 'openai-wp-integration-pro' ),
                    $count
                ),
            ),
            array(
                'role'    => 'user',
                'content' => $text,
            ),
        );
        $response  = $this->chat( $messages, $this->model );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $content = $this->extract_message_content( $response );
        if ( is_wp_error( $content ) ) {
            return $content;
        }

        $lines  = array_map( 'trim', explode( "\n", $content ) );
        $titles = array();

        foreach ( $lines as $line ) {
            $title = preg_replace( '/^[\-\d\.)\s]+/', '', $line );
            $title = trim( $title );

            if ( ! empty( $title ) ) {
                $titles[] = $title;
            }
        }

        return array_slice( $titles, 0, $count );
    }

    /**
     * Generate a suggested SEO-friendly description from text.
     *
     * @param string $text Source text.
     *
     * @return string|WP_Error
     */
    public function generate_description( $text ) {
        if ( empty( $text ) ) {
            return new WP_Error( 'owp_missing_text', __( 'Text is required to generate a description.', 'openai-wp-integration-pro' ), array( 'status' => 400 ) );
        }

        $messages = array(
            array(
                'role'    => 'system',
                'content' => __( 'Write a compelling SEO meta description in up to 155 characters. Focus on clarity and encourage clicks.', 'openai-wp-integration-pro' ),
            ),
            array(
                'role'    => 'user',
                'content' => $text,
            ),
        );

        $response = $this->chat( $messages, $this->model );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        return $this->extract_message_content( $response );
    }

    /**
     * Extract assistant message content from API response.
     *
     * @param array $response API response payload.
     *
     * @return string|WP_Error
     */
    private function extract_message_content( $response ) {
        if ( empty( $response['choices'][0]['message']['content'] ) ) {
            return new WP_Error( 'owp_openai_empty_response', __( 'OpenAI response did not include any content.', 'openai-wp-integration-pro' ), array( 'status' => 500 ) );
        }

        return trim( $response['choices'][0]['message']['content'] );
    }
}
