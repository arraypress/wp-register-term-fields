<?php
/**
 * Registration Functions
 *
 * Provides convenient helper functions for registering custom term fields in WordPress.
 * These functions are in the global namespace for easy use throughout your codebase.
 *
 * @package     ArrayPress\WP\RegisterTermFields
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

use ArrayPress\RegisterTermFields\TermFields;

if ( ! function_exists( 'register_term_fields' ) ) {
	/**
	 * Register custom fields for taxonomy terms.
	 *
	 * This function provides a simple API for adding custom fields to taxonomy
	 * term add/edit screens. Fields are automatically saved to term meta.
	 *
	 * @param string|array $taxonomies Taxonomy or array of taxonomies to register fields for.
	 * @param array        $fields     Array of field configurations keyed by meta key.
	 *
	 * @return array Array of TermFields instances, or empty array on error.
	 *
	 * @example
	 * // Register a simple text field
	 * register_term_fields( 'category', [
	 *     'subtitle' => [
	 *         'label'       => 'Subtitle',
	 *         'type'        => 'text',
	 *         'description' => 'A subtitle for this category.',
	 *     ],
	 * ] );
	 *
	 * @example
	 * // Register multiple fields across multiple taxonomies
	 * register_term_fields( [ 'category', 'post_tag' ], [
	 *     'color' => [
	 *         'label'       => 'Color',
	 *         'type'        => 'text',
	 *         'placeholder' => '#000000',
	 *     ],
	 *     'featured' => [
	 *         'label' => 'Featured',
	 *         'type'  => 'checkbox',
	 *     ],
	 * ] );
	 *
	 * @example
	 * // Register a select field with dynamic options
	 * register_term_fields( 'product_cat', [
	 *     'tax_class' => [
	 *         'label'   => 'Tax Class',
	 *         'type'    => 'select',
	 *         'options' => function() {
	 *             return [
	 *                 ''         => '— Default —',
	 *                 'reduced'  => 'Reduced Rate',
	 *                 'zero'     => 'Zero Rate',
	 *             ];
	 *         },
	 *     ],
	 * ] );
	 */
	function register_term_fields( $taxonomies, array $fields ): array {
		$instances = [];

		// Convert single taxonomy to array
		if ( is_string( $taxonomies ) ) {
			$taxonomies = [ $taxonomies ];
		}

		foreach ( $taxonomies as $taxonomy ) {
			try {
				$instances[] = new TermFields( $taxonomy, $fields );
			} catch ( Exception $e ) {
				error_log( 'WP Register Term Fields Error: ' . $e->getMessage() );
			}
		}

		return $instances;
	}
}

if ( ! function_exists( 'get_term_field_value' ) ) {
	/**
	 * Get a term field value with fallback to default.
	 *
	 * This is a convenience wrapper around get_term_meta() that also
	 * checks for a registered default value if the meta doesn't exist.
	 *
	 * @param int    $term_id  The term ID.
	 * @param string $meta_key The field's meta key.
	 * @param string $taxonomy Optional. The taxonomy slug. If provided, checks for registered default.
	 *
	 * @return mixed The field value, or default if not set.
	 *
	 * @example
	 * $color = get_term_field_value( $term_id, 'color', 'category' );
	 */
	function get_term_field_value( int $term_id, string $meta_key, string $taxonomy = '' ) {
		$value = get_term_meta( $term_id, $meta_key, true );

		// If value exists, return it
		if ( '' !== $value ) {
			return $value;
		}

		// Check for registered default
		if ( ! empty( $taxonomy ) ) {
			$field = TermFields::get_field( $taxonomy, $meta_key );
			if ( $field && '' !== $field['default'] ) {
				return $field['default'];
			}
		}

		return $value;
	}
}

if ( ! function_exists( 'get_term_fields' ) ) {
	/**
	 * Get all registered fields for a taxonomy.
	 *
	 * @param string $taxonomy The taxonomy slug.
	 *
	 * @return array Array of field configurations keyed by meta key.
	 *
	 * @example
	 * $fields = get_term_fields( 'category' );
	 * foreach ( $fields as $meta_key => $config ) {
	 *     echo $config['label'];
	 * }
	 */
	function get_term_fields( string $taxonomy ): array {
		return TermFields::get_fields( $taxonomy );
	}
}

if ( ! function_exists( 'get_term_field_config' ) ) {
	/**
	 * Get the configuration for a specific term field.
	 *
	 * @param string $taxonomy The taxonomy slug.
	 * @param string $meta_key The field's meta key.
	 *
	 * @return array|null The field configuration, or null if not found.
	 *
	 * @example
	 * $config = get_term_field_config( 'category', 'color' );
	 * if ( $config ) {
	 *     echo $config['label']; // "Color"
	 * }
	 */
	function get_term_field_config( string $taxonomy, string $meta_key ): ?array {
		return TermFields::get_field( $taxonomy, $meta_key );
	}
}
