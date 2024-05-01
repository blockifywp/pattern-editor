<?php

declare( strict_types=1 );

namespace Fieldify\Fields;

use Blockify\Container\Container;
use Blockify\Container\Interfaces\Registerable;
use Blockify\Hooks\Hook;

/**
 * Service provider.
 *
 * @since 1.0.0
 */
class ServiceProvider implements Registerable {

	/**
	 * Services.
	 *
	 * @var array
	 */
	private array $services = [
		Assets::class,
		Blocks::class,
		MetaBoxes::class,
		PostTypes::class,
		Settings::class,
		Taxonomies::class,
	];

	/**
	 * Main plugin or theme file.
	 *
	 * @var string
	 */
	private string $file;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $file Main plugin or theme file.
	 */
	public function __construct( string $file ) {
		$this->file = $file;
	}

	/**
	 * Registers services.
	 *
	 * @since 1.0.0
	 *
	 * @param Container $container Container instance.
	 *
	 * @return void
	 */
	public function register( Container $container ): void {
		$container->make( Config::class, $this->file );

		foreach ( $this->services as $id ) {
			$service = $container->make( $id );

			if ( is_object( $service ) ) {
				Hook::annotations( $service );
			}
		}
	}

}
