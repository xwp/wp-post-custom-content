<?php
/**
 * Plugin Name: Custom (unfiltered) content for individual posts
 * Author: Dzikri Aziz <dzikri@x-team.com>, Akeda Bagus <akeda@x-team.com>
 */

/**
 * Custom (unfiltered) content for individual posts
 *
 * @author Dzikri Aziz <dzikri@x-team.com>
 * @author Akeda Bagus <akeda@x-team.com>
 */

use XTeam\Custom_Content;

require_once(  plugin_dir_path( __FILE__ ) . 'metabox.class.php' );

$settings = array(
	'post_types' => array(
		'post'               => true,
		'page'               => true,
		'rogers_alert'       => false,
		'rogers_bio'         => true,
		'rogers_contest'     => true,
		'rogers_episode'     => true,
		'rogers_event'       => false,
		'rogers_extra'       => true,
		'rogers_poll'        => false,
		'rogers_social_post' => false,
		'rogers_spotlight'   => false,
		'rogers_video'       => false,
	),
	'capability' => 'edit_others_posts',
);

Post_Custom_Content::setup( $settings );

class Post_Custom_Content {
	const SHORTCODE = 'wpized_custom_content';

	static $options = array();

	static function setup( $options = array() ){
		self::$options = $options;

		if ( empty(self::$options['post_types']) ){
			return;
		}

		add_action( 'admin_init',  array( __CLASS__, '_action_admin_init' ) );
		add_filter( 'the_content', array( __CLASS__, '_append_custom_content' ) );
		add_shortcode( self::SHORTCODE, array( __CLASS__, '_shortcode_handler' ) );
	}

	static function get_post_types() {
		return array_keys( array_filter( self::$options['post_types'] ) );
	}


	static function _action_admin_init() {
		if ( ! current_user_can( self::$options['capability'] ) ) {
			return;
		}

		foreach ( self::get_post_types() as $post_type ) {
			$metabox = new Post_Custom_Content_Metabox( array( 'page' => $post_type ) );
			$metabox->register();
		}
	}


	/**
	 * Append custom content to post content
	 *
	 * @param  string $content Original post content
	 * @return string $content Modified post content
	 *
	 * @filter the_content
	 */
	static function _append_custom_content( $content ) {
		$custom_content        = get_post_meta( get_the_ID(), Post_Custom_Content_Metabox::META_KEY_CONTENT, true );
		$custom_content_render = get_post_meta( get_the_ID(), Post_Custom_Content_Metabox::META_KEY_RENDER,  true );

		if ( ! empty( $custom_content ) && ! is_array( $custom_content ) ) {
			// Previous postmeta only store a single string
			$custom_content        = array( $custom_content );
			$custom_content_render = array( true );
		}

		if ( ! $custom_content )
			return $content;

		foreach ( $custom_content as $index => $row_content ) {
			if ( $custom_content_render[ $index ] ) // custom content may rendered via shortcode
				$content .= $row_content;
		}

		return $content;
	}


	static function get_script_registry( $dep_args = array() ) {
		$dep_args = wp_parse_args( $dep_args );
		return array(
			'ace-editor' => array_merge(
				$dep_args,
				array(
					'src' => plugins_url( 'vendor/ace/ace.js', __FILE__ ),
				)
			)
		);
	}

	/**
	 * Handler for `[wpized_custom_content]` shortcode.
	 *
	 * @param mixed an associative array of attributes, or an empty string if no attributes are given
	 */
	public static function _shortcode_handler( $atts = '' ) {
		global $post;

		if ( ! in_array( $post->post_type, self::get_post_types() ) )
			return null;

		extract(
			shortcode_atts(
				array(
					'id' => '',
				),
				$atts
			)
		);

		$custom_content = get_post_meta( $post->ID, Post_Custom_Content_Metabox::META_KEY_CONTENT, true );

		if ( isset( $custom_content[ $id - 1 ] ) )
			return $custom_content[ $id - 1 ];
		else
			return null;
	}
}


class Post_Custom_Content_Metabox extends Custom_Content\MetaBox {

	const META_KEY_CONTENT = '_wpized_post_custom_content';
	const META_KEY_RENDER  = '_wpized_post_custom_content_render';

	const CSS_HANDLER = 'wpized_post_custom_content_css';
	const JS_HANDLER  = 'wpized_post_custom_content_js';
	const JS_I18N_VAR = 'wpized_post_custom_content_i18n';

	public function __construct( $args = array() ) {
		parent::__construct(
			array_merge(
				array(
					'title'   => __( 'Custom Content', WPIZED_LOCALE ),
					'scripts' => Post_Custom_Content::get_script_registry(),
				),
				$args
			)
		);

		add_action( 'load-post.php',     array( __CLASS__, 'scripts_styles' ) );
		add_action( 'load-post-new.php', array( __CLASS__, 'scripts_styles' ) );
	}

	public function render( $post ) {
		$custom_content        = get_post_meta( $post->ID, self::META_KEY_CONTENT, true ) ?: array( '' );
		$custom_content_render = get_post_meta( $post->ID, self::META_KEY_RENDER, true  ) ?: array( false );
		if ( ! empty( $custom_content ) && ! is_array( $custom_content ) ) {
			// Previous postmeta only store a single string
			$custom_content        = array( $custom_content );
			$custom_content_render = array( true );
		}
		?>
		<ul id="custom-content-list">
			<?php foreach ( $custom_content as $index => $row ) : ?>
			<li data-index="<?php echo esc_attr( $index ); ?>" class="postbox">
				<div class="handlediv" title="Click to toggle"><br></div>
				<h3 class="hndle">
					<span><?php _e( 'Custom content #id =', WPIZED_LOCALE ) ?></span>
					<span class="row-index"><?php echo esc_html( $index + 1 ) ?></span>
				</h3>

				<div class="inside">
					<div class="custom-content-options">
						<span class="drag-handle"></span>
						<label>
							<input
								type="radio"
								id="<?php echo esc_attr( sprintf( '%s_%d_1', self::META_KEY_RENDER, $index ) ) ?>"
								name="<?php echo esc_attr( sprintf( '%s[%d]', self::META_KEY_RENDER, $index ) ) ?>"
								value="1"
								<?php checked( isset( $custom_content_render[$index] ) ? intval( $custom_content_render[ $index ] ) : 0, 1 ) ?>>
							<?php _e( 'Render automatically below the content', WPIZED_LOCALE ); ?>
						</label>
						<br>
						<label>
							<input
								type="radio"
								id="<?php echo esc_attr( sprintf( '%s_%d_0', self::META_KEY_RENDER, $index ) ) ?>"
								name="<?php echo esc_attr( sprintf( '%s[%d]', self::META_KEY_RENDER, $index ) ) ?>"
								value="0"
								<?php checked( isset( $custom_content_render[$index] ) ? intval( $custom_content_render[ $index ] ) : 0, 0 ) ?>>
							<?php _e( 'Use shortcode to render', WPIZED_LOCALE ); ?>
							<br>
							<code class="shortcode"><?php printf( '[%s id=%d]', Post_Custom_Content::SHORTCODE, $index + 1 ); ?></code>
						</label>

						<a href="#" class="remove-custom-content"><?php _e( 'Remove', WPIZED_LOCALE ) ?></a>
					</div>
					<!-- / custom-content-options -->
					<div class="ace-editor-wrapper">
						<label class="screen-reader-text" for="post-custom-content"><?php _e( 'Custom Content', WPIZED_LOCALE ) ?></label>
						<textarea name="<?php echo esc_attr( sprintf( '%s[%d]', self::META_KEY_CONTENT, $index ) ) ?>" class="widefat" rows="10"><?php echo esc_textarea( $row ) ?></textarea>
					</div>
					<!-- / ace-editor-wrapper -->
				</div>
				<!-- / inside -->
			</li>
			<?php endforeach ?>
		</ul>
		<p>
			<button class="button button-primary" id="add-custom-content"><?php _e( '+ Add custom content', WPIZED_LOCALE ) ?></button>
		</p>
		<?php
	}

	public function save( $post ) {
		foreach ( array( self::META_KEY_CONTENT, self::META_KEY_RENDER ) as $key ) {
			if ( isset( $_POST[ $key ] ) && ! empty( $_POST[ $key ] ) ) {
				update_post_meta( $post->ID, $key, $_POST[ $key ] );
			}
			else {
				delete_post_meta( $post->ID, $key );
			}
		}
		return true;
	}

	public static function scripts_styles() {
		$current_screen = get_current_screen();
		if ( in_array( $current_screen->post_type, Post_Custom_Content::get_post_types() ) ) {
			$asset_name = 'post_custom_content';

			// Enqueue stylesheet
			wp_enqueue_style(
				self::CSS_HANDLER,
				plugins_url( 'post-custom-content.css' , __FILE__ )
			);

			// Enqueue JS to set featured event
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_script(
				self::JS_HANDLER,
				plugins_url( 'post-custom-content.js' , __FILE__ )
			);

			// Enqueue variables
			wp_localize_script(
				self::JS_HANDLER,
				self::JS_I18N_VAR,
				array(
					'field_render'  => self::META_KEY_RENDER,
					'field_content' => self::META_KEY_CONTENT,
					'shortcode_tag' => Post_Custom_Content::SHORTCODE,
					'help_message'  => __( '⤹ Drag and drop rows below to reorder', WPIZED_LOCALE ),
				)
			);
		}
	}
}
