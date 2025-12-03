<?php
/**
 * Term Fields Class
 *
 * A lightweight class for registering custom fields on WordPress taxonomy term
 * add/edit screens. Provides a simple API for common field types with automatic
 * saving and sanitization.
 *
 * @package     ArrayPress\WP\RegisterTermFields
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\RegisterTermFields;

use Exception;
use WP_Term;

/**
 * Class TermFields
 *
 * Manages custom field registration for taxonomy terms in WordPress admin.
 *
 * @package ArrayPress\WP\RegisterTermFields
 */
class TermFields {

    /**
     * Array of registered field configurations keyed by taxonomy.
     *
     * @var array
     */
    protected static array $fields = [];

    /**
     * The taxonomy this instance manages.
     *
     * @var string
     */
    protected string $taxonomy;

    /**
     * Supported field types and their default sanitizers.
     *
     * @var array
     */
    protected static array $field_types = [
            'text'     => 'sanitize_text_field',
            'textarea' => 'sanitize_textarea_field',
            'number'   => null, // Handled specially based on step
            'select'   => null, // Validated against options
            'checkbox' => null, // Boolean cast
            'url'      => 'esc_url_raw',
            'email'    => 'sanitize_email',
    ];

    /**
     * TermFields constructor.
     *
     * Initializes the term fields for a specific taxonomy.
     *
     * @param string $taxonomy The taxonomy slug to register fields for.
     * @param array  $fields   Array of field configurations keyed by meta key.
     *
     * @throws Exception If a field key is invalid or taxonomy is empty.
     */
    public function __construct( string $taxonomy, array $fields ) {
        if ( empty( $taxonomy ) ) {
            throw new Exception( 'Taxonomy cannot be empty.' );
        }

        $this->taxonomy = $taxonomy;
        $this->add_fields( $fields );

        // Load hooks immediately if already in admin, otherwise wait for admin_init
        if ( did_action( 'admin_init' ) ) {
            $this->load_hooks();
        } else {
            add_action( 'admin_init', [ $this, 'load_hooks' ] );
        }
    }

    /**
     * Add fields to the configuration for this taxonomy.
     *
     * Parses and validates field configurations, merging with defaults.
     *
     * @param array $fields Array of field configurations keyed by meta key.
     *
     * @return void
     * @throws Exception If a field key is invalid.
     */
    protected function add_fields( array $fields ): void {
        $defaults = [
                'label'             => '',
                'type'              => 'text',
                'description'       => '',
                'default'           => '',
                'placeholder'       => '',
                'options'           => [],
                'min'               => null,
                'max'               => null,
                'step'              => null,
                'rows'              => 5,
                'sanitize_callback' => null,
                'capability'        => 'manage_categories',
        ];

        foreach ( $fields as $key => $field ) {
            if ( ! is_string( $key ) || empty( $key ) ) {
                throw new Exception( 'Invalid field key provided. It must be a non-empty string.' );
            }

            // Validate field type
            $type = $field['type'] ?? 'text';
            if ( ! array_key_exists( $type, self::$field_types ) ) {
                throw new Exception( sprintf( 'Invalid field type "%s" for field "%s".', $type, $key ) );
            }

            self::$fields[ $this->taxonomy ][ $key ] = wp_parse_args( $field, $defaults );
        }
    }

    /**
     * Get all registered fields for a taxonomy.
     *
     * @param string $taxonomy The taxonomy slug.
     *
     * @return array Array of field configurations.
     */
    public static function get_fields( string $taxonomy ): array {
        return self::$fields[ $taxonomy ] ?? [];
    }

    /**
     * Get a specific field configuration by meta key.
     *
     * @param string $taxonomy The taxonomy slug.
     * @param string $meta_key The field's meta key.
     *
     * @return array|null The field configuration or null if not found.
     */
    public static function get_field( string $taxonomy, string $meta_key ): ?array {
        return self::$fields[ $taxonomy ][ $meta_key ] ?? null;
    }

    /**
     * Load WordPress hooks for the taxonomy.
     *
     * Registers actions for rendering fields on add/edit forms and saving data.
     *
     * @return void
     */
    public function load_hooks(): void {
        // Add form fields
        add_action( "{$this->taxonomy}_add_form_fields", [ $this, 'render_add_form_fields' ] );
        add_action( "{$this->taxonomy}_edit_form_fields", [ $this, 'render_edit_form_fields' ], 10, 2 );

        // Save fields
        add_action( "created_{$this->taxonomy}", [ $this, 'save_fields' ] );
        add_action( "edited_{$this->taxonomy}", [ $this, 'save_fields' ] );
    }

    /**
     * Render fields on the "Add New" term form.
     *
     * Uses div-based markup appropriate for the add form layout.
     *
     * @return void
     */
    public function render_add_form_fields(): void {
        $fields = self::get_fields( $this->taxonomy );

        foreach ( $fields as $meta_key => $field ) {
            if ( ! $this->check_permission( $field ) ) {
                continue;
            }

            $this->render_add_field( $meta_key, $field );
        }
    }

    /**
     * Render fields on the "Edit" term form.
     *
     * Uses table row markup appropriate for the edit form layout.
     *
     * @param WP_Term $term     The term object being edited.
     * @param string  $taxonomy The taxonomy slug.
     *
     * @return void
     */
    public function render_edit_form_fields( WP_Term $term, string $taxonomy ): void {
        $fields = self::get_fields( $this->taxonomy );

        foreach ( $fields as $meta_key => $field ) {
            if ( ! $this->check_permission( $field ) ) {
                continue;
            }

            $value = get_term_meta( $term->term_id, $meta_key, true );

            // Use default if no value exists
            if ( '' === $value && '' !== $field['default'] ) {
                $value = $field['default'];
            }

            $this->render_edit_field( $meta_key, $field, $value );
        }
    }

    /**
     * Render a single field on the add form.
     *
     * @param string $meta_key The field's meta key.
     * @param array  $field    The field configuration.
     *
     * @return void
     */
    protected function render_add_field( string $meta_key, array $field ): void {
        $value = $field['default'];
        $id    = esc_attr( $meta_key );
        ?>
        <div class="form-field term-<?php echo $id; ?>-wrap">
            <label for="<?php echo $id; ?>"><?php echo esc_html( $field['label'] ); ?></label>
            <?php $this->render_field_input( $meta_key, $field, $value ); ?>
            <?php if ( ! empty( $field['description'] ) ) : ?>
                <p><?php echo esc_html( $field['description'] ); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render a single field on the edit form.
     *
     * @param string $meta_key The field's meta key.
     * @param array  $field    The field configuration.
     * @param mixed  $value    The current field value.
     *
     * @return void
     */
    protected function render_edit_field( string $meta_key, array $field, $value ): void {
        $id = esc_attr( $meta_key );
        ?>
        <tr class="form-field term-<?php echo $id; ?>-wrap">
            <th scope="row">
                <label for="<?php echo $id; ?>"><?php echo esc_html( $field['label'] ); ?></label>
            </th>
            <td>
                <?php $this->render_field_input( $meta_key, $field, $value ); ?>
                <?php if ( ! empty( $field['description'] ) ) : ?>
                    <p class="description"><?php echo esc_html( $field['description'] ); ?></p>
                <?php endif; ?>
            </td>
        </tr>
        <?php
    }

    /**
     * Render the appropriate input element based on field type.
     *
     * @param string $meta_key The field's meta key.
     * @param array  $field    The field configuration.
     * @param mixed  $value    The current field value.
     *
     * @return void
     */
    protected function render_field_input( string $meta_key, array $field, $value ): void {
        $id   = esc_attr( $meta_key );
        $name = esc_attr( $meta_key );
        $type = $field['type'];

        switch ( $type ) {
            case 'textarea':
                $this->render_textarea( $id, $name, $field, $value );
                break;

            case 'select':
                $this->render_select( $id, $name, $field, $value );
                break;

            case 'checkbox':
                $this->render_checkbox( $id, $name, $field, $value );
                break;

            case 'number':
                $this->render_number( $id, $name, $field, $value );
                break;

            case 'url':
            case 'email':
            case 'text':
            default:
                $this->render_text( $id, $name, $field, $value, $type );
                break;
        }
    }

    /**
     * Render a text input field.
     *
     * @param string $id    The field ID attribute.
     * @param string $name  The field name attribute.
     * @param array  $field The field configuration.
     * @param mixed  $value The current field value.
     * @param string $type  The input type (text, url, email).
     *
     * @return void
     */
    protected function render_text( string $id, string $name, array $field, $value, string $type = 'text' ): void {
        $input_type  = in_array( $type, [ 'url', 'email' ], true ) ? $type : 'text';
        $placeholder = ! empty( $field['placeholder'] ) ? ' placeholder="' . esc_attr( $field['placeholder'] ) . '"' : '';
        ?>
        <input type="<?php echo esc_attr( $input_type ); ?>"
               id="<?php echo $id; ?>"
               name="<?php echo $name; ?>"
               value="<?php echo esc_attr( $value ); ?>"
               class="regular-text"<?php echo $placeholder; ?> />
        <?php
    }

    /**
     * Render a number input field.
     *
     * @param string $id    The field ID attribute.
     * @param string $name  The field name attribute.
     * @param array  $field The field configuration.
     * @param mixed  $value The current field value.
     *
     * @return void
     */
    protected function render_number( string $id, string $name, array $field, $value ): void {
        $min         = isset( $field['min'] ) ? ' min="' . esc_attr( $field['min'] ) . '"' : '';
        $max         = isset( $field['max'] ) ? ' max="' . esc_attr( $field['max'] ) . '"' : '';
        $step        = isset( $field['step'] ) ? ' step="' . esc_attr( $field['step'] ) . '"' : '';
        $placeholder = ! empty( $field['placeholder'] ) ? ' placeholder="' . esc_attr( $field['placeholder'] ) . '"' : '';
        ?>
        <input type="number"
               id="<?php echo $id; ?>"
               name="<?php echo $name; ?>"
               value="<?php echo esc_attr( $value ); ?>"
               class="small-text"<?php echo $min . $max . $step . $placeholder; ?> />
        <?php
    }

    /**
     * Render a textarea field.
     *
     * @param string $id    The field ID attribute.
     * @param string $name  The field name attribute.
     * @param array  $field The field configuration.
     * @param mixed  $value The current field value.
     *
     * @return void
     */
    protected function render_textarea( string $id, string $name, array $field, $value ): void {
        $rows        = absint( $field['rows'] );
        $placeholder = ! empty( $field['placeholder'] ) ? ' placeholder="' . esc_attr( $field['placeholder'] ) . '"' : '';
        ?>
        <textarea id="<?php echo $id; ?>"
                  name="<?php echo $name; ?>"
                  rows="<?php echo $rows; ?>"
                  class="large-text"<?php echo $placeholder; ?>><?php echo esc_textarea( $value ); ?></textarea>
        <?php
    }

    /**
     * Render a select dropdown field.
     *
     * @param string $id    The field ID attribute.
     * @param string $name  The field name attribute.
     * @param array  $field The field configuration.
     * @param mixed  $value The current field value.
     *
     * @return void
     */
    protected function render_select( string $id, string $name, array $field, $value ): void {
        $options = $this->get_select_options( $field );
        ?>
        <select id="<?php echo $id; ?>" name="<?php echo $name; ?>">
            <?php foreach ( $options as $option_value => $option_label ) : ?>
                <option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( $value, $option_value ); ?>>
                    <?php echo esc_html( $option_label ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Render a checkbox field.
     *
     * Uses WordPress-style checkbox markup.
     *
     * @param string $id    The field ID attribute.
     * @param string $name  The field name attribute.
     * @param array  $field The field configuration.
     * @param mixed  $value The current field value.
     *
     * @return void
     */
    protected function render_checkbox( string $id, string $name, array $field, $value ): void {
        $checked = ! empty( $value );
        ?>
        <label for="<?php echo $id; ?>">
            <input type="checkbox"
                   id="<?php echo $id; ?>"
                   name="<?php echo $name; ?>"
                   value="1"
                    <?php checked( $checked ); ?> />
            <?php echo esc_html( $field['label'] ); ?>
        </label>
        <?php
    }

    /**
     * Get options for a select field.
     *
     * Handles both static arrays and callable options.
     *
     * @param array $field The field configuration.
     *
     * @return array Array of options as value => label pairs.
     */
    protected function get_select_options( array $field ): array {
        $options = $field['options'];

        if ( is_callable( $options ) ) {
            $options = call_user_func( $options );
        }

        return is_array( $options ) ? $options : [];
    }

    /**
     * Save field values when a term is created or updated.
     *
     * @param int $term_id The term ID.
     *
     * @return void
     */
    public function save_fields( int $term_id ): void {
        $fields = self::get_fields( $this->taxonomy );

        foreach ( $fields as $meta_key => $field ) {
            if ( ! $this->check_permission( $field ) ) {
                continue;
            }

            // Handle checkbox separately (unchecked = not in POST)
            if ( 'checkbox' === $field['type'] ) {
                $value = isset( $_POST[ $meta_key ] ) ? 1 : 0;
            } else {
                if ( ! isset( $_POST[ $meta_key ] ) ) {
                    continue;
                }
                $value = $_POST[ $meta_key ];
            }

            // Sanitize the value
            $value = $this->sanitize_value( $value, $field );

            // Update or delete based on value
            if ( '' === $value || null === $value ) {
                delete_term_meta( $term_id, $meta_key );
            } else {
                update_term_meta( $term_id, $meta_key, $value );
            }
        }
    }

    /**
     * Sanitize a field value based on its type and configuration.
     *
     * @param mixed $value The raw value to sanitize.
     * @param array $field The field configuration.
     *
     * @return mixed The sanitized value.
     */
    protected function sanitize_value( $value, array $field ) {
        // Use custom sanitize callback if provided
        if ( is_callable( $field['sanitize_callback'] ) ) {
            return call_user_func( $field['sanitize_callback'], $value );
        }

        $type = $field['type'];

        switch ( $type ) {
            case 'checkbox':
                return $value ? 1 : 0;

            case 'number':
                // Use floatval if step allows decimals, otherwise intval
                $step = $field['step'] ?? 1;
                if ( is_numeric( $step ) && floor( $step ) != $step ) {
                    $value = floatval( $value );
                } else {
                    $value = intval( $value );
                }

                // Apply min/max constraints
                if ( isset( $field['min'] ) && $value < $field['min'] ) {
                    $value = $field['min'];
                }
                if ( isset( $field['max'] ) && $value > $field['max'] ) {
                    $value = $field['max'];
                }

                return $value;

            case 'select':
                // Validate against options
                $options = $this->get_select_options( $field );
                if ( ! array_key_exists( $value, $options ) ) {
                    return $field['default'];
                }

                return $value;

            case 'url':
                return esc_url_raw( $value );

            case 'email':
                return sanitize_email( $value );

            case 'textarea':
                return sanitize_textarea_field( $value );

            case 'text':
            default:
                return sanitize_text_field( $value );
        }
    }

    /**
     * Check if the current user has permission to view/edit the field.
     *
     * @param array $field The field configuration.
     *
     * @return bool True if user has permission, false otherwise.
     */
    protected function check_permission( array $field ): bool {
        return current_user_can( $field['capability'] );
    }

}
