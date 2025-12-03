# WordPress Register Term Fields

A lightweight library for registering custom fields on WordPress taxonomy term add/edit screens. This library provides a clean, simple API for adding common field types to any taxonomy without JavaScript dependencies or complex configuration.

## Features

- **Simple API**: Register custom term fields with minimal code
- **Multiple Field Types**: Text, textarea, number, select, checkbox, URL, and email
- **Automatic Saving**: Fields are automatically saved to term meta
- **Smart Sanitization**: Each field type has appropriate default sanitization
- **Dynamic Options**: Select fields support callable options for dynamic data
- **Multiple Taxonomies**: Register the same fields across multiple taxonomies
- **Permission Control**: Control field visibility based on user capabilities
- **No JavaScript**: Pure PHP implementation with WordPress-native markup
- **Lightweight**: Single class, minimal footprint

## Requirements

- PHP 7.4 or higher
- WordPress 5.0 or higher

## Installation

Install via Composer:

```bash
composer require arraypress/wp-register-term-fields
```

## Basic Usage

### Simple Text Field

```php
register_term_fields( 'category', [
    'subtitle' => [
        'label'       => __( 'Subtitle', 'textdomain' ),
        'type'        => 'text',
        'description' => __( 'A subtitle displayed below the category name.', 'textdomain' ),
    ],
] );
```

### Multiple Fields

```php
register_term_fields( 'category', [
    'subtitle' => [
        'label'       => __( 'Subtitle', 'textdomain' ),
        'type'        => 'text',
        'placeholder' => __( 'Enter a subtitle...', 'textdomain' ),
    ],
    'color' => [
        'label'       => __( 'Color', 'textdomain' ),
        'type'        => 'text',
        'placeholder' => '#000000',
        'description' => __( 'Hex color code for this category.', 'textdomain' ),
    ],
    'featured' => [
        'label' => __( 'Featured Category', 'textdomain' ),
        'type'  => 'checkbox',
    ],
] );
```

### Multiple Taxonomies

Register the same fields across multiple taxonomies:

```php
register_term_fields( [ 'category', 'post_tag' ], [
    'icon' => [
        'label'       => __( 'Icon Class', 'textdomain' ),
        'type'        => 'text',
        'placeholder' => 'dashicons-star-filled',
    ],
] );
```

### Select Field with Dynamic Options

```php
register_term_fields( 'download_category', [
    'tax_class' => [
        'label'       => __( 'Tax Class', 'textdomain' ),
        'type'        => 'select',
        'description' => __( 'Products in this category will use this tax class.', 'textdomain' ),
        'options'     => function() {
            // Fetch options dynamically
            $classes = get_tax_classes();
            $options = [ '' => __( '— Use Default —', 'textdomain' ) ];
            
            foreach ( $classes as $class ) {
                $options[ $class->id ] = $class->name;
            }
            
            return $options;
        },
    ],
] );
```

## Field Types

### Text

Standard single-line text input.

```php
'field_name' => [
    'label'       => __( 'Field Label', 'textdomain' ),
    'type'        => 'text',
    'placeholder' => __( 'Placeholder text...', 'textdomain' ),
    'default'     => '',
]
```

### Textarea

Multi-line text input.

```php
'description' => [
    'label'       => __( 'Description', 'textdomain' ),
    'type'        => 'textarea',
    'rows'        => 5,
    'placeholder' => __( 'Enter a description...', 'textdomain' ),
]
```

### Number

Numeric input with optional constraints.

```php
'display_order' => [
    'label'   => __( 'Display Order', 'textdomain' ),
    'type'    => 'number',
    'min'     => 0,
    'max'     => 100,
    'step'    => 1,
    'default' => 0,
]
```

For decimal values, set a decimal step:

```php
'price_modifier' => [
    'label' => __( 'Price Modifier', 'textdomain' ),
    'type'  => 'number',
    'min'   => 0,
    'max'   => 1,
    'step'  => 0.01,
]
```

### Select

Dropdown selection with static or dynamic options.

```php
// Static options
'layout' => [
    'label'   => __( 'Layout', 'textdomain' ),
    'type'    => 'select',
    'default' => 'grid',
    'options' => [
        'grid' => __( 'Grid', 'textdomain' ),
        'list' => __( 'List', 'textdomain' ),
        'card' => __( 'Cards', 'textdomain' ),
    ],
]

// Dynamic options via callback
'parent_category' => [
    'label'   => __( 'Parent Category', 'textdomain' ),
    'type'    => 'select',
    'options' => function() {
        $categories = get_categories( [ 'hide_empty' => false ] );
        $options    = [ '' => __( '— None —', 'textdomain' ) ];
        
        foreach ( $categories as $cat ) {
            $options[ $cat->term_id ] = $cat->name;
        }
        
        return $options;
    },
]
```

### Checkbox

Boolean toggle field.

```php
'featured' => [
    'label'   => __( 'Featured Category', 'textdomain' ),
    'type'    => 'checkbox',
    'default' => 0,
]
```

### URL

Text input with URL validation.

```php
'website' => [
    'label'       => __( 'Website', 'textdomain' ),
    'type'        => 'url',
    'placeholder' => 'https://example.com',
]
```

### Email

Text input with email validation.

```php
'contact_email' => [
    'label'       => __( 'Contact Email', 'textdomain' ),
    'type'        => 'email',
    'placeholder' => 'contact@example.com',
]
```

## Field Configuration Options

Each field accepts the following configuration options:

| Option              | Type            | Default             | Description                                    |
|---------------------|-----------------|---------------------|------------------------------------------------|
| `label`             | string          | `''`                | Field label text                               |
| `type`              | string          | `'text'`            | Field type                                     |
| `description`       | string          | `''`                | Help text displayed below the field            |
| `default`           | mixed           | `''`                | Default value for new terms                    |
| `placeholder`       | string          | `''`                | Placeholder text for text inputs               |
| `options`           | array\|callable | `[]`                | Options for select fields                      |
| `min`               | int\|float      | `null`              | Minimum value for number fields                |
| `max`               | int\|float      | `null`              | Maximum value for number fields                |
| `step`              | int\|float      | `null`              | Step increment for number fields               |
| `rows`              | int             | `5`                 | Number of rows for textarea fields             |
| `sanitize_callback` | callable        | `null`              | Custom sanitization function                   |
| `capability`        | string          | `'manage_categories'` | Required capability to view/edit the field   |

## Retrieving Field Values

### Standard Method

Use WordPress's built-in function:

```php
$value = get_term_meta( $term_id, 'color', true );
```

### With Default Fallback

Use the helper function to automatically fall back to registered defaults:

```php
$value = get_term_field_value( $term_id, 'color', 'category' );
```

### Get All Registered Fields

```php
$fields = get_term_fields( 'category' );

foreach ( $fields as $meta_key => $config ) {
    echo $config['label'] . ': ' . get_term_meta( $term_id, $meta_key, true );
}
```

### Get Field Configuration

```php
$config = get_term_field_config( 'category', 'color' );

if ( $config ) {
    echo 'Label: ' . $config['label'];
    echo 'Type: ' . $config['type'];
}
```

## Custom Sanitization

Override the default sanitization for any field:

```php
register_term_fields( 'category', [
    'custom_data' => [
        'label'             => __( 'Custom Data', 'textdomain' ),
        'type'              => 'textarea',
        'sanitize_callback' => function( $value ) {
            // Custom sanitization logic
            $value = strip_tags( $value, '<p><br><strong><em>' );
            return wp_kses_post( $value );
        },
    ],
] );
```

## Permission Control

Control field visibility based on user capabilities:

```php
register_term_fields( 'category', [
    'internal_notes' => [
        'label'       => __( 'Internal Notes', 'textdomain' ),
        'type'        => 'textarea',
        'capability'  => 'manage_options', // Only administrators
        'description' => __( 'Notes visible only to administrators.', 'textdomain' ),
    ],
] );
```

## Complete Example

```php
// Register fields for product categories
register_term_fields( 'product_cat', [
    // Basic information
    'subtitle' => [
        'label'       => __( 'Subtitle', 'textdomain' ),
        'type'        => 'text',
        'placeholder' => __( 'Brief tagline for this category', 'textdomain' ),
    ],
    
    // Display settings
    'display_order' => [
        'label'       => __( 'Display Order', 'textdomain' ),
        'type'        => 'number',
        'min'         => 0,
        'max'         => 999,
        'default'     => 0,
        'description' => __( 'Lower numbers display first.', 'textdomain' ),
    ],
    
    'layout' => [
        'label'   => __( 'Archive Layout', 'textdomain' ),
        'type'    => 'select',
        'default' => 'grid',
        'options' => [
            'grid'    => __( 'Grid', 'textdomain' ),
            'list'    => __( 'List', 'textdomain' ),
            'masonry' => __( 'Masonry', 'textdomain' ),
        ],
    ],
    
    // Flags
    'featured' => [
        'label'       => __( 'Featured Category', 'textdomain' ),
        'type'        => 'checkbox',
        'description' => __( 'Show this category on the homepage.', 'textdomain' ),
    ],
    
    'hide_empty' => [
        'label'       => __( 'Hide When Empty', 'textdomain' ),
        'type'        => 'checkbox',
        'default'     => 1,
        'description' => __( 'Hide this category if it has no products.', 'textdomain' ),
    ],
    
    // External link
    'external_url' => [
        'label'       => __( 'External URL', 'textdomain' ),
        'type'        => 'url',
        'placeholder' => 'https://example.com',
        'description' => __( 'Link to external category page instead of archive.', 'textdomain' ),
    ],
] );

// Use in a template
$term_id       = get_queried_object_id();
$subtitle      = get_term_meta( $term_id, 'subtitle', true );
$display_order = get_term_field_value( $term_id, 'display_order', 'product_cat' );
$layout        = get_term_field_value( $term_id, 'layout', 'product_cat' );
$is_featured   = get_term_meta( $term_id, 'featured', true );
```

## Integration with Register Columns

This library pairs well with [wp-register-columns](https://github.com/arraypress/wp-register-columns) to display term field values in admin list tables:

```php
// Register the field
register_term_fields( 'category', [
    'color' => [
        'label' => __( 'Color', 'textdomain' ),
        'type'  => 'text',
    ],
] );

// Display in list table
register_taxonomy_columns( 'category', [
    'color' => [
        'label'            => __( 'Color', 'textdomain' ),
        'meta_key'         => 'color',
        'display_callback' => function( $value, $term_id ) {
            if ( empty( $value ) ) {
                return '—';
            }
            return sprintf(
                '<span style="display:inline-block;width:20px;height:20px;background:%s;border-radius:50%%;"></span>',
                esc_attr( $value )
            );
        },
    ],
] );
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

GPL-2.0-or-later

## Author

David Sherlock - [ArrayPress](https://arraypress.com/)

## Support

- [Documentation](https://github.com/arraypress/wp-register-term-fields)
- [Issue Tracker](https://github.com/arraypress/wp-register-term-fields/issues)
