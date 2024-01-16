<?php
/**
 * The LiveSiteControlProvider class.
 *
 * @package GoDaddy
 */

namespace GoDaddy\WordPress\Plugins\Launch\LiveSiteControl;

use GoDaddy\WordPress\Plugins\Launch\Helper;
use GoDaddy\WordPress\Plugins\Launch\PublishGuide\GuideItems;
use GoDaddy\WordPress\Plugins\Launch\ServiceProvider;

/**
 * The LiveSiteControlProvider class.
 */
class LiveSiteControlProvider extends ServiceProvider {
	const ASSET_SLUG = 'live-site-control';

	const APP_CONTAINER_CLASS      = 'gdl-live-site-control';
	const PORTAL_CONTAINER_CLASS   = 'gdl-live-site-control-portal';
	const LIVE_CONTROL_PREVIEW_ARG = 'gdl-live-control-preview';
	const LIVE_CONTROL_EVENT_NAME  = 'gdl-live-control-go-live';

	const SETTINGS = array(
		'publishState'    => 'gdl_site_published',
		'blogPublic'      => 'blog_public',
		'liveSiteDismiss' => 'gdl_live_site_dismiss',
	);

	const SETTINGS_OVERRIDE = array(
		'publishState' => array(
			'default'           => false,
			'true_as_timestamp' => true,
		),
	);

	/**
	 * Take in raw json data from API and format into usable WP response here.
	 *
	 * Example raw response
	 * {{"headers":[],"body":"","response":{"code":true,"message":"sample",},"cookies":[],"http_response":null}}
	 *
	 * @param Array $remote_request The raw remote request data.
	 * @return \WP_Error|\WP_Rest_Response Error if invalid or typical REST response otherwise.
	 */
	protected function format_remote_post_response( $remote_request ) {

		if ( ! is_array( $remote_request ) || is_wp_error( $remote_request ) ) {
			return rest_ensure_response( $remote_request );
		}

		// Retrieve information.
		$response_code    = wp_remote_retrieve_response_code( $remote_request );
		$response_message = wp_remote_retrieve_response_message( $remote_request );
		$response_body    = wp_remote_retrieve_body( $remote_request );

		return new \WP_REST_Response(
			array(
				'status'        => $response_code,
				'response'      => $response_message,
				'body_response' => json_decode( $response_body ),
			),
			$response_code
		);
	}

	/**
	 * Function takes in $url and $body variables and performs wp_remote_post call
	 * to NUX api and return the response.
	 *
	 * @param String $url String representing the external URI of the NUX API.
	 * @param Array  $body Contains the content sent to NUX API.
	 * @return Array Raw response object from NUX API.
	 */
	protected function perform_remote_api_post( $url, $body ) {
		return wp_remote_post(
			$url,
			array(
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( $body ),
			)
		);
	}

	/**
	 * This should only be sent ONCE when the `publishState` option is
	 * saved. Subsequent milestone requests from the same domain will
	 * be ignored by the API so this is our only chance to send the
	 * right data.
	 *
	 * @return \WP_Error|\WP_Rest_Response Processed response object from NUX API.
	 */
	public function milestone_published_nux_api() {
		$domain           = defined( 'GD_TEMP_DOMAIN' ) ? GD_TEMP_DOMAIN : Helper::domain();
		$is_migrated_site = defined( 'GD_MIGRATED_SITE' ) ? GD_MIGRATED_SITE : false;
		$url              = Helper::wpnux_api_base() . '/milestones/site-publish?domain=' . $domain;

		// Remove option filters so we get the raw option value.
		remove_filter( 'option_' . self::SETTINGS['publishState'], array( Helper::class, 'get_option_convert_timestamp_to_true' ) );

		$body = Helper::get_default_nux_api_request_body();

		// This is the timestamp of when the user clicked "launch later".
		// If the user clicked "launch now" this option will not exist.
		$launch_later_at = get_option( self::SETTINGS['liveSiteDismiss'] );

		$body['is_launch_now']   = empty( $launch_later_at );
		$body['launch_later_at'] = $launch_later_at ? (int) $launch_later_at : null;

		list( $enabled, $complete, $complete_timestamps, $incomplete, $skipped, $disabled ) = $this->get_guide_items_state();

		$body['guide_items']                    = $enabled;
		$body['guide_items_count']              = count( $enabled );
		$body['guide_items_complete']           = $complete;
		$body['guide_items_skipped']            = $skipped;
		$body['guide_items_skipped_count']      = count( $skipped );
		$body['guide_items_complete_count']     = count( $complete );
		$body['guide_items_complete_percent']   = $enabled ? (int) round( count( $complete ) / count( $enabled ) * 100 ) : 0;
		$body['guide_items_incomplete']         = $incomplete;
		$body['guide_items_incomplete_count']   = count( $incomplete );
		$body['guide_items_incomplete_percent'] = $enabled ? (int) round( count( $incomplete ) / count( $enabled ) * 100 ) : 0;
		$body['guide_items_disabled']           = $disabled;
		$body['guide_items_disabled_count']     = count( $disabled );
		$body['guide_items_disabled_method']    = $this->get_guide_items_disabled_method( $disabled );
		$body['is_migrated_site']               = $is_migrated_site;

		foreach ( $complete_timestamps as $item => $timestamp ) {
			if ( $timestamp ) {
				$body[ $item . '_completed_at' ] = (int) $timestamp;
			}
		}

		ksort( $body );

		$remote_post_raw = $this->perform_remote_api_post( $url, $body );
		$remote_post     = $this->format_remote_post_response( $remote_post_raw );

		// Restore the option filters.
		add_filter( 'option_' . self::SETTINGS['publishState'], array( Helper::class, 'get_option_convert_timestamp_to_true' ) );

		return $remote_post;
	}

	/**
	 * Determine how the guide items were disabled.
	 *
	 * @param array $disabled Disabled guide item slugs.
	 *
	 * @return array key value pair of disabled items and the disabled method.
	 *               If the option is false, we can assume the user disabled it, otherwise Go is not active.
	 */
	public function get_guide_items_disabled_method( $disabled ) {
		if ( empty( $disabled ) ) {
			return array();
		}

		$theme            = wp_get_theme();
		$is_go_active     = 'Go' === $theme->get( 'Name' );
		$disabled_methods = array();

		foreach ( $disabled as $disabled_slug ) {

			$can_be_theme_disabled = 'site_design' === $disabled_slug;
			$option_enabled        = get_option( "coblocks_{$disabled_slug}_enabled" );
			$is_theme_disabled     = ( $can_be_theme_disabled && ! $is_go_active );

			$disabled_methods[ $disabled_slug ] = $is_theme_disabled ? 'theme' : ( ! $option_enabled ? 'user' : 'theme' );

		}

		return $disabled_methods;
	}

	/**
	 * Function boot used register settings, add options, add actions, enqueue scripts, localize scripts.
	 */
	public function boot() {
		// We need the settings registered to use with the REST API.
		foreach ( self::SETTINGS as $key => $settings_key ) {
			register_setting(
				$settings_key,
				$settings_key,
				array(
					'show_in_rest'      => true,
					'default'           => self::SETTINGS_OVERRIDE[ $key ]['default'] ?? true,
					'type'              => 'boolean',
					'sanitize_callback' => ! empty( self::SETTINGS_OVERRIDE[ $key ]['sanitize_callback'] )
						? array( $this, self::SETTINGS_OVERRIDE[ $key ]['sanitize_callback'] )
						: null,
				)
			);

			// Initialize the option.
			add_option( $settings_key );

			if ( ! empty( self::SETTINGS_OVERRIDE[ $key ]['true_as_timestamp'] ) ) {
				// If the value passed is boolean true, change the value to a timestamp before it's saved.
				add_filter( "pre_update_option_{$settings_key}", array( Helper::class, 'update_option_convert_true_to_timestamp' ) );
				// When pulling the value, convert back to boolean true.
				add_filter( "option_{$settings_key}", array( Helper::class, 'get_option_convert_timestamp_to_true' ) );
			}
		}

		$do_action_on_option_update = function( $value ) {
			do_action( 'gdl_splash_page_set' );

			return $value;
		};

		/**
		 * Hook into the pre_update filter for the publish state to trigger a clearing of the cache.
		 *
		 * @param mixed  $value  The new, unserialized option value.
		 */
		add_filter( 'pre_update_option_' . self::SETTINGS['publishState'], $do_action_on_option_update );

		add_action(
			'rest_api_init',
			function () {

				if ( ! Helper::is_rum_enabled() ) {
					return;
				}

				register_rest_route(
					'gdl/v1',
					'/milestone/publish/',
					array(
						'methods'             => \WP_REST_Server::EDITABLE,
						'permission_callback' => function () {
							// See https://wordpress.org/support/article/roles-and-capabilities/#activate_plugins.
							return current_user_can( 'activate_plugins' );
						},
						'show_in_index'       => false,
						'callback'            => array( $this, 'milestone_published_nux_api' ),
					)
				);
			}
		);

		if ( ! $this->is_restricted() ) {
			return;
		}

		// This is to remove the toolbar when previewing the website.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET[ self::LIVE_CONTROL_PREVIEW_ARG ] ) ) {
			add_action(
				'after_setup_theme',
				function() {
					show_admin_bar( false );
				}
			);

			return;
		}

		add_action(
			is_admin() ? 'admin_enqueue_scripts' : 'wp_enqueue_scripts',
			function( $hook_suffix ) {
				$build_file_path = $this->app->basePath( 'build/' . self::ASSET_SLUG . '.asset.php' );

				$wpnux_export_data = json_decode( get_option( 'wpnux_export_data', '{}' ), true );

				$asset_file = file_exists( $build_file_path )
					? include $build_file_path
					: array(
						'dependencies' => array(),
						'version'      => $this->app->version(),
					);

				wp_enqueue_script(
					self::ASSET_SLUG,
					$this->app->baseUrl( 'build/' . self::ASSET_SLUG . '.js' ),
					$asset_file['dependencies'],
					$asset_file['version'],
					true
				);

				wp_localize_script(
					self::ASSET_SLUG,
					'gdlLiveSiteControlData',
					array(
						'page'                   => $hook_suffix,
						'appContainerClass'      => self::APP_CONTAINER_CLASS,
						'portalContainerClass'   => self::PORTAL_CONTAINER_CLASS,
						'settings'               => self::SETTINGS,
						'previewArg'             => self::LIVE_CONTROL_PREVIEW_ARG,
						'eventName'              => self::LIVE_CONTROL_EVENT_NAME,
						'isReseller'             => defined( 'GD_RESELLER' ) ? GD_RESELLER : null,
						'shouldUseReact18Syntax' => is_wp_version_compatible( '6.2' ) ? 'true' : 'false',
						'isCaaSGenerated'        => isset( $wpnux_export_data['_meta']['content_id'] ) ? 'true' : 'false',
						'siteUrl'                => Helper::domain(),
						'isMigratedSite'         => defined( 'GD_MIGRATED_SITE' ) ? constant( 'GD_MIGRATED_SITE' ) : false,
						'siteId'                 => defined( 'GD_ACCOUNT_UID' ) ? GD_ACCOUNT_UID : null,
					)
				);

				wp_set_script_translations(
					self::ASSET_SLUG,
					'godaddy-launch',
					$this->app->basePath( 'languages' )
				);

				wp_enqueue_style(
					self::ASSET_SLUG,
					$this->app->baseUrl( 'build/' . self::ASSET_SLUG . '.css' ),
					array( 'wp-components' ),
					$asset_file['version']
				);

				if ( is_admin() ) {
					add_action(
						'all_admin_notices',
						function() {
							printf( '<div id="%s"></div>', esc_attr( self::PORTAL_CONTAINER_CLASS ) );
						}
					);
				}

				add_action(
					is_admin() ? 'admin_footer' : 'wp_footer',
					function() {
						printf( '<div id="%s"></div>', esc_attr( self::APP_CONTAINER_CLASS ) );
					}
				);
			}
		);

		/**
		 * Show coming soon template if site is restricted.
		 */
		add_action(
			'parse_request',
			function( \WP $wp ) {
				$is_rest_request = array_key_exists( 'rest_route', $wp->query_vars );
				$is_restricted   = $this->is_restricted() && ! $this->user_can_access();

				if (
					$is_rest_request &&
					$this->is_rest_endpoint_restricted( $wp->query_vars['rest_route'] ) &&
					$is_restricted
				) {
					status_header( rest_authorization_required_code() );
					die;
				}

				if ( ! $is_rest_request && $is_restricted ) {
					include __DIR__ . '/template-coming-soon.php';
					status_header( 403 );
					nocache_headers();
					die;
				}
			},
			1
		);

		add_action( 'wp_before_admin_bar_render', array( $this, 'wp_before_admin_bar_render' ) );

		add_action( 'admin_notices', array( $this, 'gdl_launch_site_notice' ) );
	}

	/**
	 * Function producing the admin screen notice that site is not live.
	 */
	public function gdl_launch_site_notice() {
		$notice_class         = 'notice notice-warning gdl-notice';
		$button_class         = 'components-button components-notice__action is-link is-secondary gdl-notice-button';
		$message              = __(
			'Your site is not launched yet. Visitors will see a temporary Coming Soon page until you launch your site.
		',
			'godaddy-launch'
		);
		$button_text          = __( 'Launch Now', 'godaddy-launch' );
		$publish_state_string = self::LIVE_CONTROL_EVENT_NAME;

		// Hide notice when site is launched.
		wp_add_inline_script(
			self::ASSET_SLUG,
			"jQuery( document ).ready( () => {
			const notice = jQuery( \".gdl-notice .gdl-notice-button\" );
			notice.on( 'click', () => {
				window.dispatchEvent( new Event( \"$publish_state_string\" ) );
				const launchButton = jQuery( \".live-site-confirm-modal-success\" );
				launchButton.on( 'click', () => {
					notice.fadeTo( 100, 0, function() {
						notice.slideUp( 100, function() {
							notice.remove();
						} );
					} );
				} );
			} );
		} );"
		);

		printf( '<div class="%1$s"><p>%2$s</p><button data-eid="wp.wpadmin.notice.launch.click" class="%3$s">%4$s</button></div>', esc_attr( $notice_class ), esc_html( $message ), esc_html( $button_class ), esc_html( $button_text ) );
	}

	/**
	 * Determine if site should be restricted
	 *
	 * @return bool
	 */
	public function is_restricted() {
		$is_published = get_option( self::SETTINGS['publishState'], false );

		$export_uid       = get_option( 'wpnux_export_uid', false );
		$export_uid_param = \sanitize_key( $_GET['wpnux_export_uid'] ?? null ); // phpcs:ignore WordPress.Security.NonceVerification

		return ! ( $is_published || $export_uid_param === $export_uid );
	}

	/**
	 * Determine if the current user has access.
	 *
	 * @return bool
	 */
	public function user_can_access() {
		return is_user_logged_in() || is_admin();
	}

	/**
	 * Determine if the REST API endpoint is restricted.
	 *
	 * @param string $path The endpoint path.
	 *
	 * @return bool
	 */
	public function is_rest_endpoint_restricted( $path ) {
		$unrestricted_defaults = array(
			'/wpaas/v1',
		);

		/**
		 * Filters the unrestricted REST API endpoint paths.
		 *
		 * @param array $unrestricted_defaults An array of default unrestricted REST API endpoints.
		 */
		$unrestricted = apply_filters( 'gdl_unrestricted_rest_endpoints', $unrestricted_defaults );

		$filtered = array_filter(
			$unrestricted,
			function( $allowed_path ) use ( $path ) {
				return str_starts_with( $path, $allowed_path );
			}
		);

		return empty( $filtered );
	}

	/**
	 * Render a simple notice in the admin bar when viewing the site as an admin when the site is restricted.
	 */
	public function wp_before_admin_bar_render() {
		global $wp_admin_bar;

		// Only show notice when viewing the website normally.
		if ( is_admin() ) {
			return;
		}

		$wp_admin_bar->add_menu(
			array(
				'parent' => 'top-secondary',
				'id'     => 'gdl-live-site',
				'title'  => __( 'Your website is in Coming Soon mode ', 'godaddy-launch' ),
			)
		);
	}

	/**
	 * Get the current state of guide items.
	 *
	 * @return array
	 */
	public function get_guide_items_state() {
		$items = array(
			GuideItems\AddDomain::class,
			GuideItems\SiteContent::class,
			GuideItems\SiteDesign::class,
			GuideItems\SiteInfo::class,
			GuideItems\SiteMedia::class,
		);

		$enabled             = array();
		$complete            = array();
		$complete_timestamps = array();
		$incomplete          = array();
		$skipped             = array();
		$disabled            = array();

		foreach ( $items as $item ) {
			$item_object = $this->app->make( $item );
			$item_slug   = str_replace( 'gdl_pgi_', '', $item_object->option_name() );

			if ( $item_object->is_enabled() ) {
				$enabled[] = $item_slug;

				if ( $item_object->is_skipped() ) {
					$skipped[] = $item_slug;
					continue;
				}

				if ( $item_object->is_complete() ) {
					$complete[] = $item_slug;

					remove_filter(
						"option_{$item_object->option_name()}",
						array( Helper::class, 'get_skipped_or_boolean_as_string' )
					);

					$complete_timestamps[ $item_slug ] = get_option( $item_object->option_name() );

					add_filter(
						"option_{$item_object->option_name()}",
						array( Helper::class, 'get_skipped_or_boolean_as_string' )
					);
				} else {
					$incomplete[] = $item_slug;
				}
			} else {
				$disabled[] = $item_slug;
			}
		}

		return array(
			$enabled,
			$complete,
			$complete_timestamps,
			$incomplete,
			$skipped,
			$disabled,
		);
	}
}
