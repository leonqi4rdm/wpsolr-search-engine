<?php

namespace wpsolr\core\classes\metabox;

use wpsolr\core\classes\services\WPSOLR_Service_Container;
use wpsolr\core\classes\utilities\WPSOLR_Help;
use wpsolr\core\classes\WPSOLR_Events;

/**
 * Class WPSOLR_Metabox
 */
class WPSOLR_Metabox {


	// SQL statements
	const TABLE_POST_METAS = 'postmeta';
	const SQL_STATEMENT_BLACKLISTED_IDS = 'SELECT post_id FROM {{REPLACE_TABLE_NAME}} WHERE meta_key = %s AND meta_value = %s ';

	// Fields stored in metabox
	const METABOX_FIELD_IS_DO_NOT_INDEX = '_wpsolr-meta-is-do-not-index';
	const METABOX_FIELD_IS_DO_INDEX_ACF_FIELD_FILES = '_wpsolr-meta-is-do-index-acf-field-files';
	const METABOX_FIELD_IS_DO_INDEX_EMBED_ANY_DOCUMENT = '_wpsolr-meta-is-do-index-embed-any-document';
	const METABOX_FIELD_IS_DO_INDEX_TOOLSET_FIELD_FILES = '_wpsolr-meta-is-do-index-toolset-field-files';

	// Metabox id
	const METABOX_NONCE_ID = 'wpsolr-metabox-nonce-id';

	// Metabox html data
	const METABOX_CHECKBOX_YES = 'yes';

	static $metabox;

	public static function register() {

		if ( ! isset( self::$metabox ) ) {
			self::$metabox = new self();
		}
	}

	/**
	 * @inheritDoc
	 */
	public function __construct() {

		// Register current metabox callbacks
		add_action( 'add_meta_boxes', [ $this, 'action_add_meta_boxes' ] );
		add_action( 'save_post', [ $this, 'action_save_post_callback' ] );
		add_action( 'add_attachment', [ $this, 'action_save_post_callback' ] );
		add_action( 'edit_attachment', [ $this, 'action_save_post_callback' ] );
	}

	/**
	 * Metabox action
	 *
	 */
	public function action_add_meta_boxes() {

		add_meta_box( 'wpsolr_metabox_id', __( 'wpsolr', 'wpsolr' ),
			[
				$this,
				'action_add_meta_boxes_callback'
			],
			null,
			'side',
			'high'
		);


	}


	/**
	 * Metabox callback
	 *
	 * @param $post \WP_Post
	 *
	 * @return string
	 */
	public function action_add_meta_boxes_callback( $post ) {
		global $license_manager;

		if ( empty( $post ) || ! $this->get_is_show_meta_box( $post ) ) {
			return;
		}

		wp_nonce_field( basename( __FILE__ ), self::METABOX_NONCE_ID );
		$post_meta = get_post_meta( $post->ID );
		?>

		<?php
		// Include license activation popup boxes in all admin tabs
		add_thickbox();
		if ( ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			// Do not load in Ajax
			require_once WPSOLR_PLUGIN_ANY_DIR . '/wpsolr/core/classes/extensions/licenses/admin_options.inc.php';
		}
		?>


        <div class="wpsolr-metabox-row-content">
            <label for="<?php echo esc_attr( self::METABOX_FIELD_IS_DO_NOT_INDEX ); ?>">
                <input type="checkbox" name="<?php echo esc_attr( self::METABOX_FIELD_IS_DO_NOT_INDEX ); ?>"
                       id="<?php echo esc_attr( self::METABOX_FIELD_IS_DO_NOT_INDEX ); ?>"
                       value="<?php echo esc_attr( self::METABOX_CHECKBOX_YES ); ?>" <?php if ( isset ( $post_meta[ self::METABOX_FIELD_IS_DO_NOT_INDEX ] ) ) {
					checked( $post_meta[ self::METABOX_FIELD_IS_DO_NOT_INDEX ][0], self::METABOX_CHECKBOX_YES );
				} ?> />
				<?php _e( 'Do not search', 'wpsolr' ) ?>
            </label>
        </div>

		<?php
		if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_ACF_FIELD_FILE ) ) ) {
			require $file_to_include;
		}
		?>

		<?php
		if ( file_exists( $file_to_include = apply_filters( WPSOLR_Events::WPSOLR_FILTER_INCLUDE_FILE, WPSOLR_Help::HELP_TOOLSET_FIELD_FILE ) ) ) {
			require $file_to_include;
		}
		?>

		<?php
		/*
		?>
		<div class="wpsolr-metabox-row-content">
			<label for="<?php echo esc_attr( self::METABOX_FIELD_IS_DO_INDEX_EMBED_ANY_DOCUMENT ); ?>">
				<input type="checkbox"
				       name="<?php echo esc_attr( self::METABOX_FIELD_IS_DO_INDEX_EMBED_ANY_DOCUMENT ); ?>"
				       id="<?php echo esc_attr( self::METABOX_FIELD_IS_DO_INDEX_EMBED_ANY_DOCUMENT ); ?>"
				       value="<?php echo esc_attr( self::METABOX_CHECKBOX_YES ); ?>" <?php if ( isset ( $post_meta[ self::METABOX_FIELD_IS_DO_INDEX_EMBED_ANY_DOCUMENT ] ) ) {
			checked( $post_meta[ self::METABOX_FIELD_IS_DO_INDEX_EMBED_ANY_DOCUMENT ][0], self::METABOX_CHECKBOX_YES );
		} ?>
					<?php echo $license_manager->get_license_enable_html_code( OptionLicenses::LICENSE_PACKAGE_EMBED_ANY_DOCUMENT ); ?>
				/>
				<?php echo $license_manager->show_premium_link( OptionLicenses::LICENSE_PACKAGE_EMBED_ANY_DOCUMENT, _x( 'Search in Embed Any Document', 'wpsolr' ), true, true ); ?>
			</label>
		</div>
		*/
		?>

	<?php }


	/**
	 * Can the post show a metabox ?
	 *
	 * @param $post
	 *
	 * @return bool
	 */
	public function get_is_show_meta_box( $post ) {

		$post_types = WPSOLR_Service_Container::getOption()->get_option_index_post_types();
		if ( ! is_array( $post_types ) ) {
			$post_types = [];
		}

		$attachment_types = WPSOLR_Service_Container::getOption()->get_option_index_attachment_types();
		if ( ! is_array( $attachment_types ) ) {
			$attachment_types = [];
		}

		$types = array_merge( $post_types, $attachment_types );

		switch ( $post->post_type ) {

			case 'attachment':
				$type    = $post->post_mime_type;
				$message = sprintf( '%1s attachments are not indexable.', $type );
				break;

			default:
				$type             = $post->post_type;
				$post_type_object = get_post_type_object( $type )->labels;
				$message          = sprintf( '%1s are not indexable.', esc_attr( $post_type_object->name ) );
				break;

		}

		if ( ! in_array( $type, $types, true ) ) {
			// Show the metabox on post types indexable
			// Show the metabox on atttachment types indexable

			echo $message . ' You can change that in wpsolr settings.';

			return false;
		}

		return true;
	}

	/**
	 * Saves the custom meta input
	 *
	 * @param $post_id
	 */
	public function action_save_post_callback( $post_id ) {

		// Checks save status
		$is_autosave = wp_is_post_autosave( $post_id );
		$is_revision = wp_is_post_revision( $post_id );

		// Using a nonce, the post meta is restored with the post (after a trash followed by a recovery).
		$is_valid_nonce = ( isset( $_POST[ self::METABOX_NONCE_ID ] ) && wp_verify_nonce( $_POST[ self::METABOX_NONCE_ID ], basename( __FILE__ ) ) ) ? true : false;

		// Exits script depending on save status
		if ( $is_autosave || $is_revision || ! $is_valid_nonce ) {
			return;
		}

		// Checks for input and sanitizes/saves if needed
		update_post_meta( $post_id, self::METABOX_FIELD_IS_DO_NOT_INDEX, isset( $_POST[ self::METABOX_FIELD_IS_DO_NOT_INDEX ] ) ? sanitize_text_field( $_POST[ self::METABOX_FIELD_IS_DO_NOT_INDEX ] ) : '' );
		update_post_meta( $post_id, self::METABOX_FIELD_IS_DO_INDEX_ACF_FIELD_FILES, isset( $_POST[ self::METABOX_FIELD_IS_DO_INDEX_ACF_FIELD_FILES ] ) ? sanitize_text_field( $_POST[ self::METABOX_FIELD_IS_DO_INDEX_ACF_FIELD_FILES ] ) : '' );
		update_post_meta( $post_id, self::METABOX_FIELD_IS_DO_INDEX_TOOLSET_FIELD_FILES, isset( $_POST[ self::METABOX_FIELD_IS_DO_INDEX_TOOLSET_FIELD_FILES ] ) ? sanitize_text_field( $_POST[ self::METABOX_FIELD_IS_DO_INDEX_TOOLSET_FIELD_FILES ] ) : '' );

	}

	/**
	 * Return a metabox checkbox field value
	 *
	 * @param $metabox_field_name
	 * @param $post_id
	 *
	 * @return bool
	 */
	public static function get_metabox_checkbox_value( $metabox_field_name, $post_id ) {

		$value = get_post_custom_values( $metabox_field_name, $post_id );

		return ( isset( $value ) && ( ! empty( $value[0] ) ) );
	}


	/**
	 * Is a post not indexable ?
	 *
	 * @param $post_id
	 *
	 * @return bool
	 *
	 */
	public static function get_metabox_is_do_not_index( $post_id ) {

		return self::get_metabox_checkbox_value( self::METABOX_FIELD_IS_DO_NOT_INDEX, $post_id );
	}

	/**
	 * Is a post index it's ACF embedded documents ?
	 *
	 * @param $post_id
	 *
	 * @return bool
	 *
	 */
	public static function get_metabox_is_do_index_acf_field_files( $post_id ) {

		return self::get_metabox_checkbox_value( self::METABOX_FIELD_IS_DO_INDEX_ACF_FIELD_FILES, $post_id );
	}

	/**
	 * Is a post index it's embedd any document shortcodes ?
	 *
	 * @param $post_id
	 *
	 * @return bool
	 *
	 */
	public static function get_metabox_is_do_index_embed_any_document( $post_id ) {

		return self::get_metabox_checkbox_value( self::METABOX_FIELD_IS_DO_INDEX_EMBED_ANY_DOCUMENT, $post_id );
	}


	/**
	 * @return array
	 */
	static public function get_blacklisted_ids() {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				str_replace( '{{REPLACE_TABLE_NAME}}', $wpdb->prefix . self::TABLE_POST_METAS, self::SQL_STATEMENT_BLACKLISTED_IDS ), self::METABOX_FIELD_IS_DO_NOT_INDEX, self::METABOX_CHECKBOX_YES
			),
			ARRAY_N
		);

		if ( empty( $rows ) ) {
			return [];
		}

		$results = [];
		foreach ( $rows as $key => $value ) {
			$results[] = $value[0];
		}

		return $results;
	}

	/**
	 * Is a post index it's Toolset embedded documents ?
	 *
	 * @param $post_id
	 *
	 * @return bool
	 *
	 */
	public static function get_metabox_is_do_index_toolset_field_files( $post_id ) {

		return self::get_metabox_checkbox_value( self::METABOX_FIELD_IS_DO_INDEX_TOOLSET_FIELD_FILES, $post_id );
	}


}