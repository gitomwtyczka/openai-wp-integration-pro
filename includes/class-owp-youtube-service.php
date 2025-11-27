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
     * OAuth 2.0 access token for YouTube Data API requests.
     *
     * @var string
     */
    private $access_token;

    /**
     * Initialize service with API key.
     *
     * @param string $api_key      YouTube Data API key.
     * @param string $access_token Optional OAuth 2.0 access token.
     */
    public function __construct( $api_key, $access_token = '' ) {
        $this->api_key      = $api_key;
        $this->access_token = $access_token;
    }

    /**
     * Set OAuth 2.0 access token used for authenticated YouTube requests.
     *
     * @param string $token OAuth 2.0 access token.
     *
     * @return void
     */
    public function set_access_token( $token ) {
        $this->access_token = $token;
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
            'category_id' => isset( $first['categoryId'] ) ? $first['categoryId'] : '',
            'thumbnail'   => $thumbnail,
        );
    }

    /**
     * Update video metadata such as title, description and tags.
     *
     * @param string       $video_id_or_url Video ID or URL.
     * @param string       $title           New video title.
     * @param string       $description     New video description.
     * @param string|array $tags            Tags as array or comma-separated list.
     * @param string|int   $category_id     Category ID to assign.
     *
     * @return array|WP_Error
     */
    public function update_video_metadata( $video_id_or_url, $title, $description, $tags, $category_id ) {
        if ( empty( $this->access_token ) ) {
            return new WP_Error( 'owp_missing_token', __( 'YouTube OAuth token is not configured.', 'openai-wp-integration-pro' ), array( 'status' => 401 ) );
        }

        $video_id = $this->extract_video_id( $video_id_or_url );

        if ( empty( $video_id ) ) {
            return new WP_Error( 'owp_invalid_video', __( 'Unable to determine the YouTube video ID.', 'openai-wp-integration-pro' ), array( 'status' => 400 ) );
        }

        $normalized_tags = $this->normalize_tags( $tags );

        $resource = array(
            'id'      => $video_id,
            'snippet' => array(
                'title'       => $title,
                'description' => $description,
                'tags'        => $normalized_tags,
                'categoryId'  => (string) $category_id,
            ),
        );

        $request_args = array(
            'method'  => 'PUT',
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->access_token,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ),
            'body'    => wp_json_encode( $resource ),
        );

        $response = wp_remote_request( 'https://www.googleapis.com/youtube/v3/videos?part=snippet', $request_args );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( 200 !== $code ) {
            $error_message = isset( $data['error']['message'] ) ? $data['error']['message'] : __( 'YouTube API request failed.', 'openai-wp-integration-pro' );
            return new WP_Error( 'owp_youtube_request_failed', $error_message, array( 'status' => $code ) );
        }

        $items = isset( $data['items'] ) ? $data['items'] : array();
        $first = isset( $items[0]['snippet'] ) ? $items[0]['snippet'] : null;

        if ( empty( $first ) ) {
            return new WP_Error( 'owp_video_not_found', __( 'Video not found on YouTube.', 'openai-wp-integration-pro' ), array( 'status' => 404 ) );
        }

        return array(
            'id'          => isset( $data['items'][0]['id'] ) ? $data['items'][0]['id'] : $video_id,
            'title'       => isset( $first['title'] ) ? $first['title'] : '',
            'description' => isset( $first['description'] ) ? $first['description'] : '',
            'tags'        => isset( $first['tags'] ) ? $first['tags'] : array(),
            'category_id' => isset( $first['categoryId'] ) ? $first['categoryId'] : '',
        );
    }

    /**
     * Normalize list of tags to an array of strings without empty values.
     *
     * @param string|array $tags Tags passed by the request.
     *
     * @return array
     */
    private function normalize_tags( $tags ) {
        if ( is_string( $tags ) ) {
            $tags = explode( ',', $tags );
        }

        if ( ! is_array( $tags ) ) {
            return array();
        }

        $tags = array_map( 'trim', $tags );
        $tags = array_filter( $tags, 'strlen' );

        return array_values( $tags );
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
