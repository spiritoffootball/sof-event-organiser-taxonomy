<?php
/**
 * Plugin Name: SOF Event Organiser Taxonomy
 * Description: Provides a Custom Taxonomy for Event Organiser Events for Spirit of Football.
 * Version: 1.0
 * Author: Christian Wach
 * Author URI: https://haystack.co.uk
 * Text Domain: sof-event-organiser-taxonomy
 * Domain Path: /languages
 *
 * @package SOF_Event_Organiser_Taxonomy
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



// Set our version here.
define( 'SOF_EVENT_ORGANISER_TAXONOMY_VERSION', '1.0' );

// Store reference to this file.
if ( ! defined( 'SOF_EVENT_ORGANISER_TAXONOMY_FILE' ) ) {
	define( 'SOF_EVENT_ORGANISER_TAXONOMY_FILE', __FILE__ );
}

// Store URL to this plugin's directory.
if ( ! defined( 'SOF_EVENT_ORGANISER_TAXONOMY_URL' ) ) {
	define( 'SOF_EVENT_ORGANISER_TAXONOMY_URL', plugin_dir_url( SOF_EVENT_ORGANISER_TAXONOMY_FILE ) );
}
// Store PATH to this plugin's directory.
if ( ! defined( 'SOF_EVENT_ORGANISER_TAXONOMY_PATH' ) ) {
	define( 'SOF_EVENT_ORGANISER_TAXONOMY_PATH', plugin_dir_path( SOF_EVENT_ORGANISER_TAXONOMY_FILE ) );
}



/**
 * Main Plugin Class.
 *
 * A class that encapsulates plugin functionality.
 *
 * @since 1.0
 */
class SOF_Event_Organiser_Taxonomy {

	/**
	 * Custom Post Type name.
	 *
	 * @since 1.0
	 * @access public
	 * @var object $cpt The name of the Custom Post Type.
	 */
	public $post_type_name = 'event';

	/**
	 * Taxonomy name.
	 *
	 * @since 1.0
	 * @access public
	 * @var str $taxonomy_name The name of the Custom Taxonomy.
	 */
	public $taxonomy_name = 'event-type';

	/**
	 * Taxonomy REST base.
	 *
	 * @since 1.0
	 * @access public
	 * @var str $taxonomy_rest_base The REST base of the Custom Taxonomy.
	 */
	public $taxonomy_rest_base = 'event-type';

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {

		// Initialise.
		add_action( 'plugins_loaded', [ $this, 'initialise' ] );

		// Load translation.
		add_action( 'plugins_loaded', [ $this, 'translation' ] );

	}

	/**
	 * Actions to perform on plugin activation.
	 *
	 * @since 1.0
	 */
	public function activate() {

		// Pass through.
		$this->taxonomy_create();

		// Go ahead and flush.
		flush_rewrite_rules();

	}

	/**
	 * Actions to perform on plugin deactivation (NOT deletion).
	 *
	 * @since 1.0
	 */
	public function deactivate() {

		// Flush rules to reset.
		flush_rewrite_rules();

	}

	/**
	 * Initialise this plugin.
	 *
	 * @since 1.0
	 */
	public function initialise() {

		// Only do this once.
		static $done;
		if ( isset( $done ) && $done === true ) {
			return;
		}

		// Create taxonomy.
		add_action( 'init', [ $this, 'taxonomy_create' ] );
		add_filter( 'wp_terms_checklist_args', [ $this, 'taxonomy_fix_metabox' ], 10, 2 );
		add_action( 'restrict_manage_posts', [ $this, 'taxonomy_filter_post_type' ] );

		/**
		 * Broadcast that this plugin is now loaded.
		 *
		 * @since 1.0
		 */
		do_action( 'sof_eot/loaded' );

		// We're done.
		$done = true;

	}

	/**
	 * Enable translation.
	 *
	 * @since 1.0
	 */
	public function translation() {

		// Load translations.
		// phpcs:ignore WordPress.WP.DeprecatedParameters.Load_plugin_textdomainParam2Found
		load_plugin_textdomain(
			'sof-event-organiser-taxonomy', // Unique name.
			false, // Deprecated argument.
			dirname( plugin_basename( SOF_EVENT_ORGANISER_TAXONOMY_FILE ) ) . '/languages/' // Relative path to files.
		);

	}

	// -------------------------------------------------------------------------

	/**
	 * Create our Custom Taxonomy.
	 *
	 * @since 1.0
	 */
	public function taxonomy_create() {

		// Only register once.
		static $registered;
		if ( $registered ) {
			return;
		}

		// Arguments.
		$args = [

			// Same as "category".
			'hierarchical' => true,

			// Labels.
			'labels' => [
				'name'              => _x( 'Event Types', 'taxonomy general name', 'sof-event-organiser-taxonomy' ),
				'singular_name'     => _x( 'Event Type', 'taxonomy singular name', 'sof-event-organiser-taxonomy' ),
				'search_items'      => __( 'Search Event Types', 'sof-event-organiser-taxonomy' ),
				'all_items'         => __( 'All Event Types', 'sof-event-organiser-taxonomy' ),
				'parent_item'       => __( 'Parent Event Type', 'sof-event-organiser-taxonomy' ),
				'parent_item_colon' => __( 'Parent Event Type:', 'sof-event-organiser-taxonomy' ),
				'edit_item'         => __( 'Edit Event Type', 'sof-event-organiser-taxonomy' ),
				'update_item'       => __( 'Update Event Type', 'sof-event-organiser-taxonomy' ),
				'add_new_item'      => __( 'Add New Event Type', 'sof-event-organiser-taxonomy' ),
				'new_item_name'     => __( 'New Event Type Name', 'sof-event-organiser-taxonomy' ),
				'menu_name'         => __( 'Event Types', 'sof-event-organiser-taxonomy' ),
				'not_found'         => __( 'No Event Types found', 'sof-event-organiser-taxonomy' ),
			],

			// Rewrite rules.
			'rewrite' => [
				'slug' => 'event-types',
			],

			// Show column in wp-admin.
			'show_admin_column' => true,
			'show_ui' => true,

			// REST setup.
			'show_in_rest' => true,
			'rest_base' => $this->taxonomy_rest_base,

		];

		// Register a taxonomy for this CPT.
		register_taxonomy( $this->taxonomy_name, $this->post_type_name, $args );

		// Flag done.
		$registered = true;

	}

	/**
	 * Fix the Custom Taxonomy metabox.
	 *
	 * @see https://core.trac.wordpress.org/ticket/10982
	 *
	 * @since 1.0
	 *
	 * @param array $args The existing arguments.
	 * @param int $post_id The WordPress post ID.
	 */
	public function taxonomy_fix_metabox( $args, $post_id ) {

		// If rendering metabox for our taxonomy.
		if ( isset( $args['taxonomy'] ) && $args['taxonomy'] === $this->taxonomy_name ) {

			// Setting 'checked_ontop' to false seems to fix this.
			$args['checked_ontop'] = false;

		}

		// --<
		return $args;

	}

	/**
	 * Add a filter for this Custom Taxonomy to the Custom Post Type listing.
	 *
	 * @since 1.0
	 */
	public function taxonomy_filter_post_type() {

		// Access current post type.
		global $typenow;

		// Bail if not our post type.
		if ( $typenow != $this->post_type_name ) {
			return;
		}

		// Get tax object.
		$taxonomy = get_taxonomy( $this->taxonomy_name );

		// Show a dropdown.
		wp_dropdown_categories( [
			/* translators: %s: The plural name of the taxonomy terms. */
			'show_option_all' => sprintf( __( 'Show All %s', 'sof-event-organiser-taxonomy' ), $taxonomy->label ),
			'taxonomy' => $this->taxonomy_name,
			'name' => $this->taxonomy_name,
			'orderby' => 'name',
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended
			'selected' => isset( $_GET[ $this->taxonomy_name ] ) ? wp_unslash( $_GET[ $this->taxonomy_name ] ) : '',
			'show_count' => true,
			'hide_empty' => true,
			'value_field' => 'slug',
			'hierarchical' => 1,
		] );

	}

}



/**
 * Utility to get a reference to this plugin.
 *
 * @since 1.0
 *
 * @return SOF_Event_Organiser_Taxonomy $plugin The plugin reference.
 */
function sof_event_organiser_taxonomy() {

	// Store instance in static variable.
	static $plugin = false;

	// Maybe return instance.
	if ( false === $plugin ) {
		$plugin = new SOF_Event_Organiser_Taxonomy();
	}

	// --<
	return $plugin;

}

// Initialise plugin now.
sof_event_organiser_taxonomy();

// Activation.
register_activation_hook( __FILE__, [ sof_event_organiser_taxonomy(), 'activate' ] );

// Deactivation.
register_deactivation_hook( __FILE__, [ sof_event_organiser_taxonomy(), 'deactivate' ] );

/*
 * Uninstall uses the 'uninstall.php' method.
 *
 * @see https://codex.wordpress.org/Function_Reference/register_uninstall_hook
 */
