<?php
/**
 * Admin area functionality.
 *
 * @package OpenAI_WP_Integration_Pro
 */

class Owp_Admin {
	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	private $page_slug = 'owp-integration-pro';

	/**
	 * Register admin hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register plugin settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'owp_integration_pro_settings',
			'owp_youtube_api_key',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		add_settings_section(
			'owp_youtube_section',
			__( 'YouTube', 'openai-wp-integration-pro' ),
			array( $this, 'render_youtube_section' ),
			'owp_integration_pro'
		);

		add_settings_field(
			'owp_youtube_api_key',
			__( 'YouTube API Key', 'openai-wp-integration-pro' ),
			array( $this, 'render_youtube_api_key_field' ),
			'owp_integration_pro',
			'owp_youtube_section'
		);
	}

	/**
	 * Register settings page in the admin menu.
	 *
	 * @return void
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'OpenAI WP Integration Pro', 'openai-wp-integration-pro' ),
			__( 'OpenAI Integration', 'openai-wp-integration-pro' ),
			'manage_options',
			$this->page_slug,
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Render description for YouTube settings section.
	 *
	 * @return void
	 */
	public function render_youtube_section() {
		echo '<p>' . esc_html__( 'Configure your YouTube Data API key to fetch video details.', 'openai-wp-integration-pro' ) . '</p>';
	}

	/**
	 * Render API key input field.
	 *
	 * @return void
	 */
	public function render_youtube_api_key_field() {
		$api_key = get_option( 'owp_youtube_api_key', '' );
		?>
		<input
			type="text"
			id="owp_youtube_api_key"
			name="owp_youtube_api_key"
			value="<?php echo esc_attr( $api_key ); ?>"
			class="regular-text"
			placeholder="<?php esc_attr_e( 'Enter your YouTube Data API key', 'openai-wp-integration-pro' ); ?>"
		/>
		<?php
	}

	/**
	 * Render the settings page markup.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'OpenAI WP Integration Pro', 'openai-wp-integration-pro' ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'owp_integration_pro_settings' );
				do_settings_sections( 'owp_integration_pro' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
