<?php

declare( strict_types=1 );

use Blockify\Container\Container;
use Fieldify\Fields\ServiceProvider;

if ( ! class_exists( 'Fieldify' ) ) {

	/**
	 * Fieldify registry.
	 *
	 * @since 1.0.0
	 */
	class Fieldify {

		/**
		 * Service provider instances.
		 *
		 * @var array <string, ServiceProvider>
		 */
		private static array $providers = [];

		/**
		 * Registers instance.
		 *
		 * @param string $file Main plugin or theme file.
		 *
		 * @return void
		 */
		public static function register( string $file ): void {
			if ( ! isset( self::$providers[ $file ] ) ) {
				self::$providers[ $file ] = new ServiceProvider( $file );
				self::$providers[ $file ]->register( new Container() );
			}
		}
	}
}
