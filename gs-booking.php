<?php
/**
 * GS Booking
 *
 * @package       GSBOOKING
 * @author        Guy Shapira
 * @license       gplv2
 * @version       1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:   GS Booking
 * Plugin URI:    https://mydomain.com
 * Description:   Enables booking retreats in your conditions
 * Version:       1.0.0
 * Author:        Guy Shapira
 * Author URI:    https://your-author-domain.com
 * Text Domain:   gs-booking
 * Domain Path:   /languages
 * License:       GPLv2
 * License URI:   https://www.gnu.org/licenses/gpl-2.0.html
 *
 * You should have received a copy of the GNU General Public License
 * along with GS Booking. If not, see <https://www.gnu.org/licenses/gpl-2.0.html/>.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
// Plugin name
define( 'GSBOOKING_NAME',			'GS Booking' );

// Plugin version
define( 'GSBOOKING_VERSION',		'1.0.0' );

// Plugin Root File
define( 'GSBOOKING_PLUGIN_FILE',	__FILE__ );

// Plugin base
define( 'GSBOOKING_PLUGIN_BASE',	plugin_basename( GSBOOKING_PLUGIN_FILE ) );

// Plugin Folder Path
define( 'GSBOOKING_PLUGIN_DIR',	plugin_dir_path( GSBOOKING_PLUGIN_FILE ) );

// Plugin Folder URL
define( 'GSBOOKING_PLUGIN_URL',	plugin_dir_url( GSBOOKING_PLUGIN_FILE ) );

/**
 * Load the main class for the core functionality
 */
require_once GSBOOKING_PLUGIN_DIR . 'core/class-gs-booking.php';

/**
 * The main function to load the only instance
 * of our master class.
 *
 * @author  Guy Shapira
 * @since   1.0.0
 * @return  object|Gs_Booking
 */
function GSBOOKING() {
	return Gs_Booking::instance();
}

GSBOOKING();
