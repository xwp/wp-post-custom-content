<?php
namespace XTeam\Custom_Content;
/**
 * @todo Like WPized_Widget, should we have a singular get/set_field_error() and has_field_error
 * @todo Like WPized_Widget, should we have get_field_bad_value
 * @todo Like WPized_Widget, we should have a fields_schema
 * @todo There could be a 'serializer' in fields_schema that tells out to take a 'validator' and put it into the control?
 */
abstract class MetaBox {
	static $default_args = array(
		'id' => null,
		'page' => null,
		'context' => 'normal',
		'priority' => 'default',
		'callback_args' => array(),
		'save_post_action_priority' => 10,
		'styles' => array(),
		'scripts' => array(),
	);
	public $args = array();
	public $current_post;
	protected $_is_registered = false;

	function __construct( $args = array() ) {
		$this->args = array_merge(
			self::$default_args,
			array( 'id' => get_class( $this ) ),
			$this->args,
			$args
		);
		extract( $this->args );
		$this->args['callback'] = array( &$this, '_callback' );
		$this->_enqueue_files();
		add_action( 'save_post', array( &$this, '_save' ), $this->args['save_post_action_priority'] );
	}

	abstract function render( $post );

	/**
	 * @todo Provide a generic save method that validates and saves the fields based on the fields_schema
	 */
	abstract function save( $post );

	/**
	 *
	 */
	function get_request_param_prefix() {
		return strtolower( get_class( $this ) ) . '_';
	}

	function get_form_field_id( $name ){
		return $this->get_request_param_prefix() . $name;
	}

	/**
	 * Get all of the field errors
	 */
	function get_field_errors(){
		return get_post_meta( $this->current_post->ID, sprintf( '_%s_field_errors', strtolower( get_class( $this ) ) ), true );
	}

	/**
	 * Get all of the field errors
	 * @param {mixed} $errors If empty() then errors will be deleted from postmeta; otherwise must be an array()
	 */
	function set_field_errors( $errors = null ){
		if ( empty( $errors ) ) {
			delete_post_meta( $this->current_post->ID, sprintf( '_%s_field_errors', strtolower( get_class( $this ) ) ) );
		}
		else {
			assert( is_array( $errors ) );
			update_post_meta( $this->current_post->ID, sprintf( '_%s_field_errors', strtolower( get_class( $this ) ) ), $errors );
		}
	}

	static function get_prefixed_key( $key ) {
		$default = '';
		if ( defined( 'WPIZED_NS' ) ) {
			$default = WPIZED_NS;
		}
		return '_' . WPized_Theme_Config::get( 'namespace', $default ) . '_' . $key;
	}

	function get( $key ){ return $this->get_field_value( $key ); }
	function get_field( $key ){ return $this->get_field_value( $key ); }
	function get_field_value( $key ){
		assert( is_object( $this->current_post ) );
		return get_post_meta(
			$this->current_post->ID,
			self::get_prefixed_key( $key ),
			true
		);
	}
	function set( $key, $value ){ return $this->set_field_value( $key, $value ); }
	function set_field( $key, $value ){ return $this->set_field_value( $key, $value ); }
	function set_field_value( $key, $value ){
		assert( is_object( $this->current_post ) );
		// @todo This should check a fields_schema
		return update_post_meta(
			$this->current_post->ID,
			self::get_prefixed_key( $key ),
			$value
		);
	}
	function delete( $key, $value = '' ) { return $this->delete_field( $key, $value ); }
	function delete_field( $key, $value = '' ){
		return delete_post_meta(
			$this->current_post->ID,
			self::get_prefixed_key( $key ),
			$value
		);
	}

	/**
	 * Output the nonce fields for security, and display any field errors that were captured during the last save
	 */
	function _start_render(){
		wp_nonce_field( strtolower( get_class( $this ) ) . '_save', strtolower( get_class( $this ) ) . '_nonce' );

		// Display any field errors
		$field_errors = $this->get_field_errors();
		if ( ! empty( $field_errors ) ) :
			?>
			<div class="error wpized-metabox-field-errors">
				<p>
					<?php printf( __( '<strong>%s</strong> error(s) with your last save: ', WPIZED_LOCALE ), esc_html( $this->args['title'] ) ); ?><br />
					<?php echo join( '<br />', array_map( 'esc_html', array_values( $field_errors ) ) ); // xss ok ?>
				</p>
			</div>
			<?php
		endif;
	}

	/**
	 * Include extra css/js files for metabox
	 */
	function _enqueue_files() {
		global $wp_styles, $wp_scripts;

		if ( empty( $wp_styles ) || empty( $wp_scripts ) ) { // We are somewhere where we shouldn't include those files (ajax request probably)
			return false;
		}

		$defaults = array(
			'disabled' => false,
			'enqueue' => true,
			'src' => false,
			'deps' => array(),
			'ver' => false,
			'media' => 'all',
			'in_footer' => false,
		);

		// Include stylesheets
		foreach ( $this->args['styles'] as $css => $options ) {
			$args = wp_parse_args( $options, $defaults );
			if ( ! $args['disabled'] && $args['enqueue'] ) {
				if ( $wp_styles->query( $css ) ) {
					if ( ! in_array( $css, $wp_styles->queue ) ) {
						wp_enqueue_style( $css );
					}
				} else {
					wp_enqueue_style( $css, $args['src'], $args['deps'], $args['ver'], $args['media'] );
				}
			}
		}

		// Include javascripts
		foreach ( $this->args['scripts'] as $js => $options ) {
			$args = wp_parse_args( $options, $defaults );
			if ( $wp_scripts->query( $js ) ) {
				if ( ! in_array( $js, $wp_scripts->queue ) ) {
					wp_enqueue_script( $js );
				}
			} else {
				wp_enqueue_script( $js, $args['src'], $args['deps'], $args['ver'], $args['in_footer'] );
			}
		}

		return true;

	}

	/**
	 * @deprecated
	 */
	function start_render() {
		_deprecated_function( 'You no longer need to invoke start_render manually()', '2011-09-02' );
	}

	/**
	 * Callback for rendering the metabox
	 */
	function _callback() {
		$this->current_post = func_get_arg( 0 );
		$args = func_get_args();
		$this->_start_render();
		call_user_func_array( array( &$this, 'render' ), $args );
	}

	/**
	 * Action handler for 'save_post' which calls self::save() if this is the proper post type
	 * and if self::validate() returns true
	 */
	function _save( $post_id ) {
		$this->current_post = get_post( $post_id );
		if ( $this->is_metabox_for_post_type() && $this->validate() ) {
			$this->save( $this->current_post );
		}
	}

	/**
	 * Returns true if the this metabox is registered for this post type
	 * @return {bool}
	 */
	function is_metabox_for_post_type() {
		return $this->current_post->post_type == $this->args['page'];
	}

	/**
	 * Ensure that the post_save action can safely do it's modifcations
	 * @todo This could actually validate the POSTDATA, and then call self::set_field_errors() if we have a field registry; goal is to not even need self::save() in subclass
	 */
	function validate(){

		if ( ! is_admin() ) {
			return;
		}

		// Without this, wp_insert_post() will fail if called inside of a cron action
		if ( defined( 'DOING_CRON' ) || isset( $_GET['doing_wp_cron'] ) ) {
			return;
		}

		// Verify if this is an auto save routine. If it is our form has not been submitted,
		// so we dont want to do anything
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		// This is to get around the problem of what happens when clicking the "Add Post"
		// link on the admin menu when browsing the main site.
		if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
			return false;
		}

		// If the nonce wasn't even sent, don't attempt to handle
		if ( ! isset( $_POST[ strtolower( get_class( $this ) ) . '_nonce' ] ) ) {
			return false;
		}

		// Verify this came from the our screen and with proper authorization
		// @todo This function calls wp_die(), which is not good.
		if ( ! check_admin_referer( strtolower( get_class( $this ) ) . '_save', strtolower( get_class( $this ) ) . '_nonce' ) ) {
			return false;
		}

		// Check permissions
		if ( ! current_user_can( 'edit_post', $this->current_post->ID ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Register the Meta Box in WordPress
	 */
	function register() {
		if ( $this->_is_registered ) {
			return false;
		}

		if ( ! did_action( 'add_meta_boxes' ) ) {
			add_action( 'add_meta_boxes', array( &$this, '_add_meta_box' ) );
		}
		else {
			$this->_add_meta_box();
		}

		$this->_is_registered = true;
	}

	/**
	 * @access private
	 */
	function _add_meta_box() {
		extract( $this->args );
		if ( ! get_post_type_object( $page ) ) {
			trigger_error( sprintf( __( 'No post type "%s" registered to add metabox for', WPIZED_LOCALE ), $page ), E_USER_NOTICE );
		}
		add_meta_box( $id, $title, $callback, $page, $context, $priority, $callback_args );
	}

	function register_at_init() {
		_deprecated_function( __METHOD__, null );
		$this->register();
	}

}

