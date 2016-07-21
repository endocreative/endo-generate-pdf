<?php
/**
 * Plugin Name: Endo Generate PDF
 * Plugin URI: http://www.endocreative.com
 * Description: A brief description of the plugin.
 * Version: 1.0.0
 * Author: Endo Creative
 * Author URI: http://www.endocreative.com
 * Text Domain: mytextdomain
 * License: GPL2
 */


// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-endo-generate-pdf.php';
require plugin_dir_path( __FILE__ ) . 'includes/lib/mpdf/mpdf.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_endo_generate_pdf() {
	$plugin = new Endo_Generate_PDF();
	$plugin->run();
}
run_endo_generate_pdf();