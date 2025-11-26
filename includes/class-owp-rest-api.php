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
}
