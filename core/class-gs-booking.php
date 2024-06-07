<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'Gs_Booking' ) ) :

	/**
	 * Main Gs_Booking Class.
	 *
	 * @package		GSBOOKING
	 * @subpackage	Classes/Gs_Booking
	 * @since		1.0.0
	 * @author		Guy Shapira
	 */
	final class Gs_Booking {

		/**
		 * The real instance
		 *
		 * @access	private
		 * @since	1.0.0
		 * @var		object|Gs_Booking
		 */
		private static $instance;

		/**
		 * GSBOOKING helpers object.
		 *
		 * @access	public
		 * @since	1.0.0
		 * @var		object|Gs_Booking_Helpers
		 */
		public $helpers;

		/**
		 * GSBOOKING settings object.
		 *
		 * @access	public
		 * @since	1.0.0
		 * @var		object|Gs_Booking_Settings
		 */
		public $settings;

		/**
		 * Throw error on object clone.
		 *
		 * Cloning instances of the class is forbidden.
		 *
		 * @access	public
		 * @since	1.0.0
		 * @return	void
		 */
		public function __clone() {
			_doing_it_wrong( __FUNCTION__, __( 'You are not allowed to clone this class.', 'gs-booking' ), '1.0.0' );
		}

		/**
		 * Disable unserializing of the class.
		 *
		 * @access	public
		 * @since	1.0.0
		 * @return	void
		 */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, __( 'You are not allowed to unserialize this class.', 'gs-booking' ), '1.0.0' );
		}

		/**
		 * Main Gs_Booking Instance.
		 *
		 * Insures that only one instance of Gs_Booking exists in memory at any one
		 * time. Also prevents needing to define globals all over the place.
		 *
		 * @access		public
		 * @since		1.0.0
		 * @static
		 * @return		object|Gs_Booking	The one true Gs_Booking
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Gs_Booking ) ) {
				self::$instance					= new Gs_Booking;
				self::$instance->base_hooks();
				self::$instance->includes();
				self::$instance->helpers		= new Gs_Booking_Helpers();
				self::$instance->settings		= new Gs_Booking_Settings();
				// self::$instance->backend		= new GS_Booking_Backend();

				//Fire the plugin logic
				new Gs_Booking_Run();

				/**
				 * Fire a custom action to allow dependencies
				 * after the successful plugin setup
				 */
				do_action( 'GSBOOKING/plugin_loaded' );
			}

			return self::$instance;
		}

		/**
		 * Include required files.
		 *
		 * @access  private
		 * @since   1.0.0
		 * @return  void
		 */
		private function includes() {
			require_once GSBOOKING_PLUGIN_DIR . 'core/includes/classes/class-gs-booking-helpers.php';
			require_once GSBOOKING_PLUGIN_DIR . 'core/includes/classes/class-gs-booking-settings.php';

			require_once GSBOOKING_PLUGIN_DIR . 'core/includes/classes/class-gs-booking-run.php';
		}

		/**
		 * Add base hooks for the core functionality
		 *
		 * @access  private
		 * @since   1.0.0
		 * @return  void
		 */
		private function base_hooks() {
			add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );
		}

		/**
		 * Loads the plugin language files.
		 *
		 * @access  public
		 * @since   1.0.0
		 * @return  void
		 */
		public function load_textdomain() {
			load_plugin_textdomain( 'gs-booking', FALSE, dirname( plugin_basename( GSBOOKING_PLUGIN_FILE ) ) . '/languages/' );
		}

	}

endif; // End if class_exists check.