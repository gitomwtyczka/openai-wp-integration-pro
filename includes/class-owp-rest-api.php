<?php
/**
 * REST API endpoints.
 *
 * @package OpenAI_WP_Integration_Pro
 */

class Owp_REST_API {
    /**
     * Register REST routes.
     *
     * @return void
     */
    public function register_routes() {
        register_rest_route(
            'owp/v1',
            '/youtube/fetch',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'handle_youtube_fetch' ),
                'permission_callback' => function() {
                    return current_user_can( 'edit_posts' );
                },
            )
        );

        register_rest_route(
            'owp/v1',
            '/youtube/update-meta',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'handle_youtube_update_meta' ),
                'permission_callback' => function() {
                    return current_user_can( 'edit_posts' );
                },
            )
        );

        register_rest_route(
            'owp/v1',
            '/openai/summarize',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'handle_openai_summarize' ),
                'permission_callback' => function() {
                    return current_user_can( 'edit_posts' );
                },
            )
        );

        register_rest_route(
            'owp/v1',
            '/openai/titles',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'handle_openai_titles' ),
                'permission_callback' => function() {
                    return current_user_can( 'edit_posts' );
                },
            )
        );

        register_rest_route(
            'owp/v1',
            '/openai/description',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'handle_openai_description' ),
                'permission_callback' => function() {
                    return current_user_can( 'edit_posts' );
                },
            )
        );
    }

    /**
     * Handle YouTube fetch requests.
     *
     * @param WP_REST_Request $request REST request instance.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function handle_youtube_fetch( WP_REST_Request $request ) {
        $video_url = $request->get_param( 'video_url' );
        $video_id  = $request->get_param( 'video_id' );
        $target    = ! empty( $video_url ) ? $video_url : $video_id;

        if ( empty( $target ) ) {
            return new WP_Error( 'owp_missing_video', __( 'Provide a YouTube video URL or ID.', 'openai-wp-integration-pro' ), array( 'status' => 400 ) );
        }

        $api_key = get_option( 'owp_youtube_api_key', '' );
        $service = new Owp_Youtube_Service( $api_key );

        $result = $service->fetch_video_data( $target );
        if ( is_wp_error( $result ) ) {
            $status = $result->get_error_data( 'status' );
            $status = ! empty( $status ) ? $status : 500;

            return new WP_Error(
                $result->get_error_code(),
                $result->get_error_message(),
                array( 'status' => $status )
            );
        }

        return rest_ensure_response( $result );
    }

    /**
     * Handle video metadata update requests.
     *
     * @param WP_REST_Request $request REST request instance.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function handle_youtube_update_meta( WP_REST_Request $request ) {
        $video_url    = $request->get_param( 'video_url' );
        $video_id     = $request->get_param( 'video_id' );
        $target       = ! empty( $video_url ) ? $video_url : $video_id;
        $title        = $request->get_param( 'title' );
        $description  = $request->get_param( 'description' );
        $tags         = $request->get_param( 'tags' );
        $category_id  = $request->get_param( 'category_id' );

        if ( empty( $target ) ) {
            return new WP_Error( 'owp_missing_video', __( 'Provide a YouTube video URL or ID.', 'openai-wp-integration-pro' ), array( 'status' => 400 ) );
        }

        $api_key      = get_option( 'owp_youtube_api_key', '' );
        $access_token = get_option( 'owp_youtube_access_token', '' );

        $service = new Owp_Youtube_Service( $api_key );
        $service->set_access_token( $access_token );

        $result = $service->update_video_metadata( $target, $title, $description, $tags, $category_id );
        if ( is_wp_error( $result ) ) {
            $status = $result->get_error_data( 'status' );
            $status = ! empty( $status ) ? $status : 500;

            return new WP_Error(
                $result->get_error_code(),
                $result->get_error_message(),
                array( 'status' => $status )
            );
        }

        return rest_ensure_response( $result );
    }

    /**
     * Handle OpenAI summarization requests.
     *
     * @param WP_REST_Request $request REST request instance.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function handle_openai_summarize( WP_REST_Request $request ) {
        $text     = $request->get_param( 'text' );
        $video_id = $request->get_param( 'video_id' );
        $video_url = $request->get_param( 'video_url' );

        if ( empty( $text ) && ( ! empty( $video_id ) || ! empty( $video_url ) ) ) {
            $youtube_api_key = get_option( 'owp_youtube_api_key', '' );
            $youtube_service = new Owp_Youtube_Service( $youtube_api_key );

            $target = ! empty( $video_url ) ? $video_url : $video_id;
            $video  = $youtube_service->fetch_video_data( $target );

            if ( is_wp_error( $video ) ) {
                $status = $video->get_error_data( 'status' );
                $status = ! empty( $status ) ? $status : 500;

                return new WP_Error(
                    $video->get_error_code(),
                    $video->get_error_message(),
                    array( 'status' => $status )
                );
            }

            $text = trim( $video['description'] );
        }

        if ( empty( $text ) ) {
            return new WP_Error( 'owp_missing_text', __( 'Provide text or a video reference to summarize.', 'openai-wp-integration-pro' ), array( 'status' => 400 ) );
        }

        $api_key = get_option( 'owp_openai_api_key', '' );
        $model   = get_option( 'owp_openai_model', 'gpt-4' );
        $service = new Owp_OpenAI_Service( $api_key, $model );

        $result = $service->generate_summary_from_text( $text );

        if ( is_wp_error( $result ) ) {
            $status = $result->get_error_data( 'status' );
            $status = ! empty( $status ) ? $status : 500;

            return new WP_Error(
                $result->get_error_code(),
                $result->get_error_message(),
                array( 'status' => $status )
            );
        }

        return rest_ensure_response( array( 'summary' => $result ) );
    }

    /**
     * Handle OpenAI title generation requests.
     *
     * @param WP_REST_Request $request REST request instance.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function handle_openai_titles( WP_REST_Request $request ) {
        $text  = $request->get_param( 'text' );
        $count = $request->get_param( 'count' );

        if ( empty( $text ) ) {
            return new WP_Error( 'owp_missing_text', __( 'Text is required to generate titles.', 'openai-wp-integration-pro' ), array( 'status' => 400 ) );
        }

        $api_key = get_option( 'owp_openai_api_key', '' );
        $model   = get_option( 'owp_openai_model', 'gpt-4' );
        $service = new Owp_OpenAI_Service( $api_key, $model );

        $result = $service->generate_titles( $text, $count );

        if ( is_wp_error( $result ) ) {
            $status = $result->get_error_data( 'status' );
            $status = ! empty( $status ) ? $status : 500;

            return new WP_Error(
                $result->get_error_code(),
                $result->get_error_message(),
                array( 'status' => $status )
            );
        }

        return rest_ensure_response( array( 'titles' => $result ) );
    }

    /**
     * Handle OpenAI description generation requests.
     *
     * @param WP_REST_Request $request REST request instance.
     *
     * @return WP_REST_Response|WP_Error
     */
    public function handle_openai_description( WP_REST_Request $request ) {
        $text = $request->get_param( 'text' );

        if ( empty( $text ) ) {
            return new WP_Error( 'owp_missing_text', __( 'Text is required to generate a description.', 'openai-wp-integration-pro' ), array( 'status' => 400 ) );
        }

        $api_key = get_option( 'owp_openai_api_key', '' );
        $model   = get_option( 'owp_openai_model', 'gpt-4' );
        $service = new Owp_OpenAI_Service( $api_key, $model );

        $result = $service->generate_description( $text );

        if ( is_wp_error( $result ) ) {
            $status = $result->get_error_data( 'status' );
            $status = ! empty( $status ) ? $status : 500;

            return new WP_Error(
                $result->get_error_code(),
                $result->get_error_message(),
                array( 'status' => $status )
            );
        }

        return rest_ensure_response( array( 'description' => $result ) );
    }
}
