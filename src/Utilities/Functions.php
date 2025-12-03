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