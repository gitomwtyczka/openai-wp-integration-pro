<?php
/**
 * YouTube service for fetching video data.
 *
 * @package OpenAI_WP_Integration_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides helper methods to communicate with the YouTube Data API.
 */
class Owp_Youtube_Service {
	/**
	 * YouTube API key.
	 *
	 * @var string
	 */
	private $api_key;

	/**
	 * Initialize service with API key.
	 *
	 * @param string $api_key YouTube Data API key.
	 */
	public function __construct( $api_key ) {
		$this->api_key = $api_key;
	}

	/**
	 * Fetch video data from YouTube by ID or URL.
	 *
	 * @param string $video_id_or_url Video ID or full YouTube URL.
	 *
	 * @return array|WP_Error
	 */
	public function fetch_video_data( $video_id_or_url ) {
		if ( empty( $this->api_key ) ) {
			return new WP_Error( 'owp_missing_api_key', __( 'YouTube API key is not configured.', 'openai-wp-integration-pro' ), array( 'status' => 400 ) );
		}

		$video_id = $this->extract_video_id( $video_id_or_url );

		if ( empty( $video_id ) ) {
			return new WP_Error( 'owp_invalid_video', __( 'Unable to determine the YouTube video ID.', 'openai-wp-integration-pro' ), array( 'status' => 400 ) );
		}

		$request_url = add_query_arg(
			array(
				'part' => 'snippet',
				'id'   => $video_id,
				'key'  => $this->api_key,
			),
			'https://www.googleapis.com/youtube/v3/videos'
		);

		$response = wp_remote_get( $request_url );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return new WP_Error( 'owp_youtube_request_failed', __( 'YouTube API request failed.', 'openai-wp-integration-pro' ), array( 'status' => $code ) );
		}

		$body   = wp_remote_retrieve_body( $response );
		$data   = json_decode( $body, true );
		$items  = isset( $data['items'] ) ? $data['items'] : array();
		$first  = isset( $items[0]['snippet'] ) ? $items[0]['snippet'] : null;

		if ( empty( $first ) ) {
			return new WP_Error( 'owp_video_not_found', __( 'Video not found on YouTube.', 'openai-wp-integration-pro' ), array( 'status' => 404 ) );
		}

		$thumbnails = isset( $first['thumbnails'] ) ? $first['thumbnails'] : array();
		$thumbnail  = $this->select_thumbnail( $thumbnails );

		return array(
			'id'          => $video_id,
			'title'       => isset( $first['title'] ) ? $first['title'] : '',
			'description' => isset( $first['description'] ) ? $first['description'] : '',
			'channel_id'  => isset( $first['channelId'] ) ? $first['channelId'] : '',
			'thumbnail'   => $thumbnail,
		);
	}

	/**
	 * Extract YouTube video ID from an ID or URL.
	 *
	 * @param string $video_id_or_url Video identifier or URL.
	 *
	 * @return string|null
	 */
	private function extract_video_id( $video_id_or_url ) {
		if ( empty( $video_id_or_url ) ) {
			return null;
		}

		// If the provided value already looks like a YouTube video ID, return it.
		if ( preg_match( '/^[A-Za-z0-9_-]{11}$/', $video_id_or_url ) ) {
			return $video_id_or_url;
		}

		$patterns = array(
			'/youtu\.be\/([A-Za-z0-9_-]{11})/i',
			'/youtube\.com\/(?:embed\/|shorts\/|watch\?v=)([A-Za-z0-9_-]{11})/i',
			'/youtube\.com\/.+&v=([A-Za-z0-9_-]{11})/i',
		);

		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $video_id_or_url, $matches ) && isset( $matches[1] ) ) {
				return $matches[1];
			}
		}

		return null;
	}

	/**
	 * Pick an appropriate thumbnail from the API response.
	 *
	 * @param array $thumbnails Thumbnails data returned by YouTube.
	 *
	 * @return string
	 */
	private function select_thumbnail( $thumbnails ) {
		$preferred = array( 'maxres', 'standard', 'high', 'medium', 'default' );

		foreach ( $preferred as $key ) {
			if ( isset( $thumbnails[ $key ]['url'] ) ) {
				return $thumbnails[ $key ]['url'];
			}
		}

		return '';
	}
}
