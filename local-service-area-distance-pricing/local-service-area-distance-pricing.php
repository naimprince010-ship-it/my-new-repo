<?php
/**
 * Plugin Name: Local Service Area & Distance Pricing
 * Plugin URI: https://github.com/naimprince010-ship-it/my-new-repo
 * Description: Allows business owners to set service radius and distance-based pricing. Customers can check service availability and pricing using a shortcode.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://github.com/naimprince010-ship-it
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: local-service-area-distance-pricing
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Domain Path: /languages
 *
 * @package LocalServiceAreaDistancePricing
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'LSADP_VERSION', '1.0.0' );
define( 'LSADP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LSADP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LSADP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin class
 */
class Local_Service_Area_Distance_Pricing {

	/**
	 * Single instance of the class
	 *
	 * @var Local_Service_Area_Distance_Pricing
	 */
	private static $instance = null;

	/**
	 * Get single instance
	 *
	 * @return Local_Service_Area_Distance_Pricing
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Initialize plugin
	 */
	private function init() {
		// Load plugin textdomain
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

		// Include required files
		$this->includes();

		// Initialize admin
		if ( is_admin() ) {
			require_once LSADP_PLUGIN_DIR . 'includes/admin/class-admin.php';
			new LSADP_Admin();
		}

		// Initialize frontend
		require_once LSADP_PLUGIN_DIR . 'includes/frontend/class-frontend.php';
		new LSADP_Frontend();

		// Register activation and deactivation hooks
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
	}

	/**
	 * Load plugin textdomain
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'local-service-area-distance-pricing',
			false,
			dirname( LSADP_PLUGIN_BASENAME ) . '/languages'
		);
	}

	/**
	 * Include required files
	 */
	private function includes() {
		require_once LSADP_PLUGIN_DIR . 'includes/class-helpers.php';
	}

	/**
	 * Plugin activation
	 */
	public function activate() {
		// Create database table for pricing ranges if needed
		$this->create_tables();

		// Set default options
		$default_options = array(
			'store_address' => '',
			'max_service_radius' => 50,
			'google_api_key' => '',
			'pricing_ranges' => array(),
		);

		if ( ! get_option( 'lsadp_settings' ) ) {
			add_option( 'lsadp_settings', $default_options );
		}

		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation
	 */
	public function deactivate() {
		// Clean up if needed
		flush_rewrite_rules();
	}

	/**
	 * Create database tables
	 */
	private function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name = $wpdb->prefix . 'lsadp_pricing_ranges';

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			min_distance decimal(10,2) NOT NULL,
			max_distance decimal(10,2) NOT NULL,
			price decimal(10,2) NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}

/**
 * Initialize the plugin
 */
function lsadp_init() {
	return Local_Service_Area_Distance_Pricing::get_instance();
}

// Start the plugin
lsadp_init();

