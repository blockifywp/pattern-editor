<?php

declare( strict_types=1 );

namespace Fieldify\Fields;

use Blockify\Utilities\Arr;
use Blockify\Utilities\Str;
use InvalidArgumentException;
use WP_Comment;
use WP_Post;
use function add_meta_box;
use function apply_filters;
use function array_key_exists;
use function array_merge;
use function current_user_can;
use function esc_attr;
use function filter_input;
use function in_array;
use function is_a;
use function is_array;
use function is_null;
use function is_string;
use function printf;
use const FILTER_SANITIZE_FULL_SPECIAL_CHARS;
use const INPUT_GET;

/**
 * Meta boxes.
 *
 * @since 0.1.0
 */
class MetaBoxes {

	public const HOOK = 'fieldify_meta_boxes';

	/**
	 * Config.
	 *
	 * @var Config
	 */
	private Config $config;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param Config $config Config.
	 *
	 * @return void
	 */
	public function __construct( Config $config ) {
		$this->config = $config;
	}

	/**
	 * Registers a meta box.
	 *
	 * @param string $id   The meta box ID.
	 * @param array  $args The meta box arguments.
	 *
	 * @return void
	 */
	public static function register_meta_box( string $id, array $args ): void {
		add_filter(
			static::HOOK,
			static fn( array $meta_boxes ): array => array_merge(
				$meta_boxes,
				[ $id => $args ]
			)
		);
	}

	/**
	 * Registers custom post meta.
	 *
	 * @hook after_setup_theme 11
	 *
	 * @return void
	 */
	public function register_custom_post_meta(): void {
		$meta_boxes = $this->get_meta_boxes();

		if ( empty( $meta_boxes ) ) {
			return;
		}

		$defaults = [
			'string'  => '',
			'number'  => 0,
			'array'   => [],
			'object'  => [],
			'boolean' => false,
		];

		foreach ( $meta_boxes as $meta_box ) {
			$post_types = $meta_box['post_types'] ?? [ 'post' ];
			$fields     = $meta_box['fields'] ?? [];

			foreach ( $fields as $id => $field ) {
				$schema = $this->get_item_schema( $field );
				$type   = $schema['type'];

				$args = [
					'type'          => $type,
					'description'   => $field['label'] ?? Str::title_case( $id ),
					'default'       => $field['default'] ?? $defaults[ $type ] ?? null,
					'single'        => true,
					'show_in_rest'  => true,
					'auth_callback' => static fn(): bool => current_user_can( 'manage_options' ),
				];

				if ( $field['sanitizeCallback'] ?? null ) {
					$args['sanitize_callback'] = $field['sanitizeCallback'];
				}

				if ( in_array( $type, [ 'array', 'object' ], true ) ) {
					$args['show_in_rest'] = [
						'schema' => $schema,
					];
				}

				foreach ( $post_types as $post_type ) {
					register_post_meta( $post_type, $id, $args );
				}
			}
		}
	}

	/**
	 * Hide post meta from default custom fields UI.
	 *
	 * @param bool   $protected True if the meta key is protected.
	 * @param string $meta_key  Meta key.
	 *
	 * @hook is_protected_meta
	 *
	 * @return bool
	 */
	public function hide_from_custom_fields( bool $protected, string $meta_key ): bool {
		$post      = filter_input( INPUT_GET, 'post', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$post_type = filter_input( INPUT_GET, 'post_type', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( ! $post && ! $post_type ) {
			return $protected;
		}

		$meta_boxes = $this->get_meta_boxes();

		if ( empty( $meta_boxes ) ) {
			return $protected;
		}

		foreach ( $meta_boxes as $meta_box ) {
			$fields = $meta_box['fields'] ?? [];

			foreach ( $fields as $field_id => $field ) {
				if ( $field_id === $meta_key ) {
					return true;
				}
			}
		}

		return $protected;
	}

	/**
	 * Register meta boxes.
	 *
	 * @param string             $current_post_type Current post type.
	 * @param WP_Post|WP_Comment $object            Post or Comment object.
	 *
	 * @hook add_meta_boxes
	 *
	 * @return void
	 */
	public function add_custom_meta_boxes( string $current_post_type, $object ): void {

		// TODO: Add support for comments, users and terms.
		if ( ! is_a( $object, WP_Post::class ) ) {
			return;
		}

		$meta_boxes = $this->get_meta_boxes();

		if ( empty( $meta_boxes ) ) {
			return;
		}

		foreach ( $meta_boxes as $id => $meta_box ) {
			$slug       = $this->config->slug;
			$title      = $meta_box['title'] ?? Str::title_case( $id );
			$post_types = $meta_box['post_types'] ?? [ 'post' ];

			foreach ( $post_types as $post_type ) {
				if ( $current_post_type !== $post_type ) {
					continue;
				}

				add_meta_box(
					$id,
					$title,
					static fn() => printf(
						'<div id="%1$s-meta-box-%2$s" class="%1$s-meta-box"></div>',
						$slug,
						esc_attr( $id )
					),
					$post_type,
					$meta_box['context'] ?? 'normal',
					$meta_box['priority'] ?? 'default',
					$meta_box['fields'] ?? []
				);
			}
		}
	}

	/**
	 * Returns filtered array of custom meta boxes.
	 *
	 * @since 0.5.2
	 *
	 * @throws InvalidArgumentException If meta boxes are not an array.
	 *
	 * @return array <string, array> Meta boxes.
	 */
	public function get_meta_boxes(): array {
		$meta_boxes = apply_filters( self::HOOK, [] );

		if ( empty( $meta_boxes ) ) {
			return [];
		}

		if ( ! is_array( $meta_boxes ) ) {
			throw new InvalidArgumentException( 'Meta box config must be array.' );
		}

		$formatted = [];

		foreach ( $meta_boxes as $id => $meta_box ) {
			$id = is_string( $id ) ? $id : ( $field['id'] ?? '' );

			if ( ! $id ) {
				continue;
			}

			if ( array_key_exists( $id, $formatted ) ) {
				continue;
			}

			$fields             = $meta_box['fields'] ?? [];
			$meta_box['fields'] = [];

			// Format field args.
			foreach ( $fields as $field_id => $field ) {
				$field_id = is_string( $field_id ) ? $field_id : ( $field['id'] ?? '' );

				if ( is_null( $field_id ) ) {
					continue;
				}

				if ( array_key_exists( $field_id, $meta_box['fields'] ) ) {
					continue;
				}

				$field = Arr::keys_to_camel_case( $field );
				$field = $this->replace_condition_key( $field );

				$meta_box['fields'][ $field_id ] = $field;
			}

			$formatted[ $id ] = $meta_box;
		}

		return $formatted;
	}

	/**
	 * Get the meta type based on the field type.
	 *
	 * @param array $field Field data.
	 *
	 * @return array
	 */
	public function get_item_schema( array $field ): array {
		$type_map = [
			'text'     => [
				'type' => 'string',
			],
			'url'      => [
				'type' => 'string',
			],
			'email'    => [
				'type' => 'string',
			],
			'phone'    => [
				'type' => 'string',
			],
			'password' => [
				'type' => 'string',
			],
			'date'     => [
				'type' => 'string',
			],
			'textarea' => [
				'type' => 'string',
			],
			'radio'    => [
				'type' => 'string',
			],
			'select'   => [
				'type' => 'string',
			],
			'file'     => [
				'type' => 'string',
			],
			'color'    => [
				'type' => 'string',
			],
			'blocks'   => [
				'type' => 'string',
			],
			'embed'    => [
				'type' => 'string',
			],
			'number'   => [
				'type' => 'number',
			],
			'range'    => [
				'type' => 'number',
			],
			'image'    => [
				'type' => 'number',
			],
			'checkbox' => [
				'type' => 'boolean',
			],
			'toggle'   => [
				'type' => 'boolean',
			],
			'icon'     => [
				'type'       => 'object',
				'properties' => [
					'set'  => [
						'type' => 'string',
					],
					'name' => [
						'type' => 'string',
					],
					'html' => [
						'type' => 'string',
					],
				],
			],
			'gallery'  => [
				'type'  => 'array',
				'items' => [
					'type' => 'number',
				],
			],
			'repeater' => [
				'type'  => 'array',
				'items' => [
					'type' => 'object',
				],
			],
			'license'  => [
				'type' => 'string',
			],
			'code'     => [
				'type' => 'string',
			],
		];

		$field_type = $field['control'] ?? $field['type'] ?? 'text';
		$schema     = $type_map[ $field_type ] ?? [ 'type' => 'string' ];
		$sub_type   = $schema['items']['type'] ?? null;

		if ( $sub_type === 'object' ) {
			$sub_fields = $field['subfields'] ?? [];

			foreach ( $sub_fields as $sub_field_id => $sub_field ) {
				$schema['items']['properties'][ $sub_field_id ?? $sub_field['id'] ?? '' ] = $this->get_item_schema( $sub_field );
			}
		}

		return $schema;
	}

	/**
	 * Replace show if condition.
	 *
	 * @param array  $field Field data.
	 * @param string $key   Field key.
	 *
	 * @return array
	 */
	public function replace_condition_key( array $field, string $key = 'field' ): array {
		$show_if = $field['showIf'] ?? [];

		if ( ! empty( $show_if ) ) {
			foreach ( $show_if as $show_if_index => $show_if_field ) {
				$field['showIf'][ $show_if_index ]['condition'] = $show_if_field[ $key ] ?? '';

				unset( $field['showIf'][ $show_if_index ][ $key ] );
			}
		}

		$subfields = $field['subfields'] ?? [];

		if ( $subfields ) {
			foreach ( $subfields as $sub_id => $subfield ) {
				$field['subfields'][ $sub_id ] = $this->replace_condition_key( $subfield );
			}
		}

		return $field;
	}

	/**
	 * Correct subfield IDs.
	 *
	 * @param array $field Field data.
	 *
	 * @return array
	 */
	private function replace_field_ids( array $field ): array {
		$subfields = $field['subfields'] ?? [];

		if ( ! $subfields ) {
			return $field;
		}

		foreach ( $subfields as $sub_id => $subfield ) {
			$sub_id = is_string( $sub_id ) ? $sub_id : ( $field['id'] ?? '' );

			if ( is_null( $sub_id ) ) {
				continue;
			}

			$field['subfields'][ $sub_id ] = $this->replace_field_ids( $subfield );
		}

		return $field;
	}
}
