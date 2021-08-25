<?php 
/**
 * Plugin Name:       Fairly Painless Events Plugin
 * Plugin URI:        https://example.com/plugins/the-basics/
 * Description:       An Uncomplicated Event Plugin.
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Jackie Riemersma
 * Author URI:        https://author.example.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://example.com/my-plugin/
 * Text Domain:       fpep
 */

//  Security Check
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Define URL
define( 'FPEP_URL', plugin_dir_url(__FILE__) );
// Define Directory Path (indludes trailing slash)
define( 'FPEP_DIR', plugin_dir_path( __FILE__ ) );
// Define ACF Path and URL
define( 'FPEP_ACF_PATH', plugin_dir_path( __FILE__ ) . '/inc/lib/advanced-custom-fields/');
define( 'FPEP_ACF_URL', plugin_dir_url( __FILE__ ) . '/inc/lib/advanced-custom-fields/');

// Include ACF in our plugin
include_once( FPEP_ACF_PATH . 'acf.php' );
// Check if ACF is already installed on site
$fpep_show_acf_admin = false;
if(class_exists('ACF')) {
    $fpep_show_acf_admin = true;
}
add_filter('acf/settings/url', 'fpep_acf_settings_url');
add_filter('acf/settings/show_admin', 'fpep_acf_show_admin');

function fpep_acf_settings_url( $url ) {
    return FPEP_ACF_URL;
}
// Hide from admin column unless ACF is already installed on site
function fpep_acf_show_admin( $show_admin ) {
    global $fpep_show_acf_admin;
    return $fpep_show_acf_admin;
}

// Warn if ACF is already installed and is outdated compared to the version we included
add_action('views_edit-fpep-event', 'fpep_older_acf_warning');

function fpep_older_acf_warning( $views ) {
    global $acf;
    $acf_ver = (float)$acf->settings['version'];
    $acf_ver_req = 5.8;

    if( $acf_ver < $acf_ver_req ) {
        echo '<div class="update-nag notice notice-warning inline"><p>You are using an older version of Advanced Custom Fields. Fairly Painless Events Plugin plugin requires ' . $acf_ver_req . ' or higher. Some features of this plugin may not work until Advanced Custom Fields is updated.</p></div>';
    }
    
    return $views;

}

// Indlude Our Custom Post Type (File includes custom columns and ACF Fields)
include(  FPEP_DIR . 'inc/cpt/fpep-event.php');

// Create option in settings menu
function fpep_admin_page() {
    global $fpep_settings;
    $fpep_settings = add_options_page( __('Fairy Painless Events', 'fpep'), __('Fairy Painless Events', 'fpep'), 'manage_options', 'fpep', 'fpep_render_admin');
}

add_action( 'admin_menu', 'fpep_admin_page');

// Render HTML for plugin page accessed from settings menu
function fpep_render_admin() { ?>
    <div class="wrap">
        <h2><?php _e('Fairly Painless Events') ?></h2>
        <div id="refresh-button-container">
            <button
                type="button"
                class="button-primary"
                id="refresh-cache">Refresh Cache For Events
            </button>
            <span class="spinner" style="float: none;"></span>
        </div>
    </div>
<?php
}

// Load admin JavaScript
function fpep_load_scripts($hook) {

    global $fpep_settings;

    //only load this script for a certain URL page slug
    if($hook == $fpep_settings) {
        wp_enqueue_script( 'fpep-custom-admin-js', FPEP_URL . 'inc/js/admin/fpep-admin.js', ['jquery']);

        wp_localize_script('fpep-custom-admin-js', 'fpep_ajax_obj', array(
            'ajax_url' => admin_url('admin-ajax.php')
        ));
    } else {
        return;
    }

}

add_action( 'admin_enqueue_scripts', 'fpep_load_scripts' );

/**
 * EVENT CHECK FUNCTION
 */
function fpep_event_check() {
	// GET ALL EVENTS POSTS
	$args = array(
		'post_type' => 'fpep-event'
	);
	$event_posts = get_posts($args);
	// LOOP THROUGH ALL EVENTS POSTS
	foreach($event_posts as $post) {
		// SETUP THE POST DATA
		setup_postdata( $post );
		$event_type = get_field( 'event_type', $post->ID );
		$recur_day = get_field( 'recurring_day', $post->ID );
		$start_date = get_field( 'start_date', $post->ID );
		$updated_recur_date = date('Ymd', strtotime('next ' . $recur_day));
		$today = date('Ymd');
		// CHECK IF POST IS A RECURRING POST IF START DATE IS NOT EQUAL TO TODAY'S DATE AND
		// START DATE IS LESS THAN (IN THE PAST) THE NEXT DATE THIS EVENT IS SET TO RECUR
		if ( $event_type == 'recur' && $start_date != $today && $start_date < $updated_recur_date ) {
			// UPDATE THE START DATE TO THE NEXT DATE THIS EVENT IS SET TO RECUR
			update_field('field_60f8548e23cef', $updated_recur_date, $post->ID );
		}
	}
	// RESET THE POST DATA
	wp_reset_postdata();
	// NEED TO INCLUDE WP_DIE FOR AJAX CALLBACK FUNCTION
	wp_die();
}

/**
 * THESE ACTIONS HOOKS ALLOW US TO RUN EVENT CHECK FUNCTION VIA AJAX
 */
add_action('wp_ajax_fpep_event_check', 'fpep_event_check');
add_action('wp_ajax_nopriv_fpep_event_check', 'fpep_event_check');

/**
 * SETUP CRON JOB TO RUN DAILY AND FIRE EVENT CHECK FUNCTION
 */
add_action('init', 'fpep_event_check_schedule');

function fpep_event_check_schedule() {

    $timestamp = wp_next_scheduled('fpep_event_check_daily');

    if(!$timestamp) {
        wp_schedule_event(time(), 'daily', 'fpep_event_check_daily');
    }

}

add_action( 'fpep_event_check_daily', 'fpep_event_check' );

/**
 * CREATE SHORTCODE THAT RETURNS THE 5 SOONEST UPCOMING EVENTS
 */
function fpep_create_shortcode_events() {
	$today = date('Ymd');
    $args = array(
        'post_type' => 'fpep-event',
        'post_per_page' => 5,
        'orderby' => 'start_date',
        'order' => 'ASC',
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key' => 'start_date',
                'compare' => '>=',
                'value' => $today,
                'type' => 'DATE'
            ),
            array(
                'key' => 'end_date',
                'compare' => '>=',
                'value' => $today,
                'type' => 'DATE'
            )
        )
    );
    $query = new WP_Query($args);
	$result .= '<div class="event-shortcode-container">';
    if($query->have_posts()) {
        while($query->have_posts()) {
			$query->the_post();
			ob_start();
				get_template_part( 'template-parts/content-loop', 'event' );
			$result .= ob_get_clean();
        }
        wp_reset_postdata();
    } else {
		$result .= '<div class="no-events">';
		$result .= '<h3>Sorry, there are no upcoming events at this time.<h3>';
		$result .= '</div>';
	}
	$result .= '</div>';

    return $result;
}

add_shortcode('fpep-events', 'fpep_create_shortcode_events');