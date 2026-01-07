<?php
/**
 * Helper functions
 *
 * @package LocalServiceAreaDistancePricing
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper class
 */
class LSADP_Helpers {

	/**
	 * Get plugin settings
	 *
	 * @return array
	 */
	public static function get_settings() {
		return get_option( 'lsadp_settings', array() );
	}

	/**
	 * Update plugin settings
	 *
	 * @param array $settings Settings array.
	 * @return bool
	 */
	public static function update_settings( $settings ) {
		return update_option( 'lsadp_settings', $settings );
	}

	/**
	 * Sanitize text input
	 *
	 * @param string $input Input to sanitize.
	 * @return string
	 */
	public static function sanitize_text( $input ) {
		return sanitize_text_field( $input );
	}

	/**
	 * Sanitize number
	 *
	 * @param mixed $input Input to sanitize.
	 * @return float
	 */
	public static function sanitize_number( $input ) {
		return floatval( $input );
	}

	/**
	 * Verify nonce
	 *
	 * @param string $nonce Nonce value.
	 * @param string $action Nonce action.
	 * @return bool
	 */
	public static function verify_nonce( $nonce, $action ) {
		return wp_verify_nonce( $nonce, $action );
	}

	/**
	 * Create nonce
	 *
	 * @param string $action Nonce action.
	 * @return string
	 */
	public static function create_nonce( $action ) {
		return wp_create_nonce( $action );
	}

	/**
	 * Get pricing ranges from database
	 *
	 * @return array
	 */
	public static function get_pricing_ranges() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'lsadp_pricing_ranges';

		$results = $wpdb->get_results(
			"SELECT * FROM $table_name ORDER BY min_distance ASC",
			ARRAY_A
		);

		return $results ? $results : array();
	}

	/**
	 * Save pricing range
	 *
	 * @param float $min_distance Minimum distance.
	 * @param float $max_distance Maximum distance.
	 * @param float $price Price.
	 * @return int|false
	 */
	public static function save_pricing_range( $min_distance, $max_distance, $price ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'lsadp_pricing_ranges';

		return $wpdb->insert(
			$table_name,
			array(
				'min_distance' => floatval( $min_distance ),
				'max_distance' => floatval( $max_distance ),
				'price' => floatval( $price ),
			),
			array( '%f', '%f', '%f' )
		);
	}

	/**
	 * Delete pricing range
	 *
	 * @param int $id Range ID.
	 * @return bool
	 */
	public static function delete_pricing_range( $id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'lsadp_pricing_ranges';

		return $wpdb->delete(
			$table_name,
			array( 'id' => intval( $id ) ),
			array( '%d' )
		);
	}

	/**
	 * Calculate distance using Google Distance Matrix API
	 *
	 * @param string $origin Origin address.
	 * @param string $destination Destination address.
	 * @param string $api_key Google API key.
	 * @return array|false
	 */
	public static function calculate_distance( $origin, $destination, $api_key ) {
		if ( empty( $api_key ) || empty( $origin ) || empty( $destination ) ) {
			return false;
		}

		$url = add_query_arg(
			array(
				'origins' => urlencode( $origin ),
				'destinations' => urlencode( $destination ),
				'units' => 'metric',
				'key' => $api_key,
			),
			'https://maps.googleapis.com/maps/api/distancematrix/json'
		);

		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( 'OK' !== $data['status'] ) {
			return false;
		}

		$element = $data['rows'][0]['elements'][0];

		if ( 'OK' !== $element['status'] ) {
			return false;
		}

		return array(
			'distance_km' => $element['distance']['value'] / 1000, // Convert meters to km
			'duration' => $element['duration']['text'],
		);
	}

	/**
	 * Get price for distance
	 *
	 * @param float $distance Distance in km.
	 * @return float|false
	 */
	public static function get_price_for_distance( $distance ) {
		$ranges = self::get_pricing_ranges();

		foreach ( $ranges as $range ) {
			if ( $distance >= floatval( $range['min_distance'] ) && $distance <= floatval( $range['max_distance'] ) ) {
				return floatval( $range['price'] );
			}
		}

		return false;
	}
}

