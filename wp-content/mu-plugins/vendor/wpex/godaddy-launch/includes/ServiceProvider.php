<?php
/**
 * The ServiceProvider class.
 *
 * @package GoDaddy
 */

namespace GoDaddy\WordPress\Plugins\Launch;

/**
 * The ServiceProvider class.
 */
abstract class ServiceProvider {

	/**
	 * The application instance.
	 *
	 * @var \GoDaddy\WordPress\Plugins\Launch\Application;
	 */
	protected $app;

	/**
	 * Create a new service provider instance.
	 *
	 * @param  \GoDaddy\WordPress\Plugins\Launch\Application $app The Application.
	 */
	public function __construct( $app ) {
		$this->app = $app;
	}

	/**
	 * Register any application services.
	 */
	public function register() {}
}
