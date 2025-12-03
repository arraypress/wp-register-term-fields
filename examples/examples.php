<?php
/**
 * Term Fields Examples
 *
 * Practical examples of using the WP Register Term Fields library.
 * No need to wrap in admin_init - the library handles hook timing automatically.
 *
 * @package ArrayPress\WP\RegisterTermFields
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Example 1: Basic category fields
 *
 * Simple text and checkbox fields for categories.
 */
register_term_fields( 'category', [
	'subtitle'  => [
		'label'       => __( 'Subtitle', 'textdomain' ),
		'type'        => 'text',
		'placeholder' => __( 'A short tagline for this category', 'textdomain' ),
	],
	'featured'  => [
		'label'       => __( 'Featured Category', 'textdomain' ),
		'type'        => 'checkbox',
		'description' => __( 'Show this category prominently on the homepage.', 'textdomain' ),
	],
] );

/**
 * Example 2: Multiple taxonomies
 *
 * Register the same fields across categories and tags.
 */
register_term_fields( [ 'category', 'post_tag' ], [
	'icon_class' => [
		'label'       => __( 'Icon Class', 'textdomain' ),
		'type'        => 'text',
		'placeholder' => 'dashicons-star-filled',
		'description' => __( 'CSS class for the term icon.', 'textdomain' ),
	],
] );

/**
 * Example 3: Select with static options
 *
 * Dropdown field with predefined choices.
 */
register_term_fields( 'category', [
	'layout' => [
		'label'       => __( 'Archive Layout', 'textdomain' ),
		'type'        => 'select',
		'default'     => 'grid',
		'description' => __( 'How posts in this category should be displayed.', 'textdomain' ),
		'options'     => [
			'grid'    => __( 'Grid', 'textdomain' ),
			'list'    => __( 'List', 'textdomain' ),
			'masonry' => __( 'Masonry', 'textdomain' ),
		],
	],
] );

/**
 * Example 4: Select with dynamic options
 *
 * Options fetched from database at render time.
 */
register_term_fields( 'download_category', [
	'tax_class_id' => [
		'label'       => __( 'Tax Class', 'textdomain' ),
		'type'        => 'select',
		'description' => __( 'Products in this category will use this tax class unless overridden.', 'textdomain' ),
		'options'     => function () {
			// This would typically query your custom table
			$classes = [
				(object) [ 'id' => 1, 'name' => 'Standard Rate' ],
				(object) [ 'id' => 2, 'name' => 'Reduced Rate' ],
				(object) [ 'id' => 3, 'name' => 'Zero Rate' ],
			];

			$options = [ '' => __( '— Use Default —', 'textdomain' ) ];

			foreach ( $classes as $class ) {
				$options[ $class->id ] = $class->name;
			}

			return $options;
		},
	],
] );

/**
 * Example 5: Number fields with constraints
 *
 * Integer and decimal number fields.
 */
register_term_fields( 'product_cat', [
	'display_order'  => [
		'label'       => __( 'Display Order', 'textdomain' ),
		'type'        => 'number',
		'min'         => 0,
		'max'         => 999,
		'default'     => 0,
		'description' => __( 'Lower numbers appear first.', 'textdomain' ),
	],
	'discount_rate'  => [
		'label'       => __( 'Category Discount (%)', 'textdomain' ),
		'type'        => 'number',
		'min'         => 0,
		'max'         => 100,
		'step'        => 0.5,
		'default'     => 0,
		'description' => __( 'Default discount for products in this category.', 'textdomain' ),
	],
] );

/**
 * Example 6: URL and Email fields
 *
 * Validated input types.
 */
register_term_fields( 'vendor', [
	'website'       => [
		'label'       => __( 'Website', 'textdomain' ),
		'type'        => 'url',
		'placeholder' => 'https://example.com',
	],
	'contact_email' => [
		'label'       => __( 'Contact Email', 'textdomain' ),
		'type'        => 'email',
		'placeholder' => 'contact@example.com',
	],
] );

/**
 * Example 7: Textarea field
 *
 * Multi-line text input.
 */
register_term_fields( 'category', [
	'extended_description' => [
		'label'       => __( 'Extended Description', 'textdomain' ),
		'type'        => 'textarea',
		'rows'        => 4,
		'placeholder' => __( 'A longer description for this category...', 'textdomain' ),
		'description' => __( 'This appears on the category archive page.', 'textdomain' ),
	],
] );

/**
 * Example 8: Custom sanitization
 *
 * Override default sanitization for special cases.
 */
register_term_fields( 'category', [
	'allowed_html' => [
		'label'             => __( 'Formatted Content', 'textdomain' ),
		'type'              => 'textarea',
		'rows'              => 6,
		'description'       => __( 'Allows basic HTML formatting.', 'textdomain' ),
		'sanitize_callback' => function ( $value ) {
			return wp_kses( $value, [
				'p'      => [],
				'br'     => [],
				'strong' => [],
				'em'     => [],
				'a'      => [ 'href' => [], 'title' => [] ],
			] );
		},
	],
] );

/**
 * Example 9: Permission-restricted field
 *
 * Only visible to users with specific capability.
 */
register_term_fields( 'category', [
	'internal_notes' => [
		'label'       => __( 'Internal Notes', 'textdomain' ),
		'type'        => 'textarea',
		'rows'        => 3,
		'capability'  => 'manage_options',
		'description' => __( 'Private notes only visible to administrators.', 'textdomain' ),
	],
] );

/**
 * Example 10: Complete product category setup
 *
 * A comprehensive example showing multiple field types together.
 */
register_term_fields( 'product_cat', [
	// Display settings
	'subtitle'       => [
		'label'       => __( 'Subtitle', 'textdomain' ),
		'type'        => 'text',
		'placeholder' => __( 'Brief category tagline', 'textdomain' ),
	],
	'display_order'  => [
		'label'       => __( 'Display Order', 'textdomain' ),
		'type'        => 'number',
		'min'         => 0,
		'default'     => 0,
	],
	'archive_layout' => [
		'label'   => __( 'Archive Layout', 'textdomain' ),
		'type'    => 'select',
		'default' => 'default',
		'options' => [
			'default'  => __( 'Default', 'textdomain' ),
			'grid-3'   => __( '3 Column Grid', 'textdomain' ),
			'grid-4'   => __( '4 Column Grid', 'textdomain' ),
			'list'     => __( 'List View', 'textdomain' ),
		],
	],

	// Flags
	'featured'       => [
		'label'       => __( 'Featured Category', 'textdomain' ),
		'type'        => 'checkbox',
		'description' => __( 'Display on homepage featured section.', 'textdomain' ),
	],
	'hide_empty'     => [
		'label'   => __( 'Hide When Empty', 'textdomain' ),
		'type'    => 'checkbox',
		'default' => 1,
	],

	// External
	'external_url'   => [
		'label'       => __( 'External URL', 'textdomain' ),
		'type'        => 'url',
		'description' => __( 'Redirect to external page instead of archive.', 'textdomain' ),
	],

	// Admin only
	'admin_notes'    => [
		'label'      => __( 'Admin Notes', 'textdomain' ),
		'type'       => 'textarea',
		'rows'       => 2,
		'capability' => 'manage_options',
	],
] );

/**
 * Example: Retrieving field values in a template
 */
function example_display_category_fields( $term_id ) {
	// Standard WordPress function
	$subtitle = get_term_meta( $term_id, 'subtitle', true );

	// Helper with default fallback
	$layout     = get_term_field_value( $term_id, 'archive_layout', 'product_cat' );
	$is_featured = get_term_meta( $term_id, 'featured', true );

	// Check if featured
	if ( $is_featured ) {
		echo '<span class="badge">Featured</span>';
	}

	// Display subtitle
	if ( ! empty( $subtitle ) ) {
		echo '<p class="category-subtitle">' . esc_html( $subtitle ) . '</p>';
	}

	// Apply layout class
	echo '<div class="products layout-' . esc_attr( $layout ) . '">';
	// ... products ...
	echo '</div>';
}

/**
 * Example: Getting all registered fields
 */
function example_list_registered_fields() {
	$fields = get_term_fields( 'product_cat' );

	echo '<ul>';
	foreach ( $fields as $meta_key => $config ) {
		printf(
			'<li><strong>%s</strong> (%s): %s</li>',
			esc_html( $config['label'] ),
			esc_html( $config['type'] ),
			esc_html( $meta_key )
		);
	}
	echo '</ul>';
}
