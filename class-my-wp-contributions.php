<?php
/**
 * @package My WP Contributions
 * @version 4.9.4
 *
 * Plugin Name:       My WP Contributions
 * Description:       Gathers various stats & contributions from WordPress.org and updates a post daily.
 * Version:           4.9.4
 * Author:            Xenos (xkon) Konstantinos
 * Author URI:        https://xkon.gr/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       my-wp-contributions
 * Domain Path:       /languages
 *
 */

// Check that the file is not accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

/*
 * Change these settings to define your post ID and WordPress.org username.
*/
define( 'MY_WP_CONTRIBUTIONS_POST_ID', 8 );
define( 'MY_WP_CONTRIBUTIONS_USERNAME', 'xkon' );


/**
 * Do not edit below this line.
 */

class MY_WP_CONTRIBUTIONS {
	/**
	 * MY_WP_CONTRIBUTIONS constructor.
	 *
	 * @uses MY_WP_CONTRIBUTIONS::init()
	 *
	 * @return void
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Plugin initialization.
	 *
	 * @uses register_activation_hook()
	 * @uses register_deactivation_hook()
	 * @uses add_action()
	 *
	 * @return void
	 */
	public function init() {
		register_activation_hook( __FILE__, array( $this, 'my_wp_contributions_activation' ) );
		register_deactivation_hook( __FILE__, array( $this, 'my_wp_contributions_deactivation' ) );
		add_action( 'my_wp_contributions_event', array( $this, 'my_wp_contributions_show_contributions' ) );
		add_action( 'wp_footer', array( $this, 'my_wp_contributions_js' ), 100 );

	}

	/**
	 * Add cronjob.
	 *
	 * @uses wp_next_scheduled()
	 * @uses wp_schedule_event()
	 *
	 * @return void
	 */
	public function my_wp_contributions_activation() {

		if ( ! wp_next_scheduled( 'my_wp_contributions_event' ) ) {
			wp_schedule_event( time(), 'daily', 'my_wp_contributions_event' );
		}

	}

	/**
	 * Remove cronjob.
	 *
	 * @uses wp_clear_scheduled_hook()
	 *
	 * @return void
	 */
	public function my_wp_contributions_deactivation() {

		wp_clear_scheduled_hook( 'my_wp_contributions_event' );

	}

	/**
	 * Gather and display stats.
	 *
	 * @uses wp_remote_get()
	 * @uses wp_remote_retrieve_body()
	 * @uses str_get_html()
	 * @uses find()
	 * @uses wp_update_post()
	 *
	 * @return void
	 */
	public function my_wp_contributions_show_contributions() {

		require_once( dirname( __FILE__ ) . '/inc/simple_html_dom.php' );

		$output = 'WordPress.org Username: <a href="https://profiles.wordpress.org/' . MY_WP_CONTRIBUTIONS_USERNAME . '">' . MY_WP_CONTRIBUTIONS_USERNAME . '</a><br/>';

		// General

		// Setup API Call.
		$get_general  = wp_remote_get( 'https://wordpress.org/support/users/' . MY_WP_CONTRIBUTIONS_USERNAME );
		$general_body = wp_remote_retrieve_body( $get_general );

		$html          = str_get_html( $general_body );
		$forum_role    = $html->find( 'p[class=bbp-user-forum-role]' );
		$forum_replies = $html->find( 'p[class=bbp-user-reply-count]' );

		$output .= $forum_role[0]->innertext . '<br>';
		$output .= 'Forum ' . $forum_replies[0]->innertext . '<br>';

		// core commits

		$get_remote      = wp_remote_get( 'https://core.trac.wordpress.org/search?q=props+' . MY_WP_CONTRIBUTIONS_USERNAME . '&noquickjump=1&changeset=on' );
		$get_remote_body = wp_remote_retrieve_body( $get_remote );

		$html = str_get_html( $get_remote_body );

		$mt = $html->find( 'dl[id=results] dt' );

		$output .= 'Core commits: ' . count( $mt ) . '<br>';

		// plugins commits

		$get_remote      = wp_remote_get( 'https://plugins.trac.wordpress.org/search?q=props+' . MY_WP_CONTRIBUTIONS_USERNAME . '&noquickjump=1&changeset=on' );
		$get_remote_body = wp_remote_retrieve_body( $get_remote );

		$html = str_get_html( $get_remote_body );

		$mt = $html->find( 'dl[id=results] dt' );

		$plugin_commits = count( $mt );

		// meta commits

		$get_remote      = wp_remote_get( 'https://meta.trac.wordpress.org/search?q=props+' . MY_WP_CONTRIBUTIONS_USERNAME . '&noquickjump=1&changeset=on' );
		$get_remote_body = wp_remote_retrieve_body( $get_remote );

		$html = str_get_html( $get_remote_body );

		$mt = $html->find( 'dl[id=results] dt' );

		$meta_commits = count( $mt );

		$total_meta_commits = $plugin_commits + $meta_commits;

		$output .= 'Meta commits: ' . $total_meta_commits;

		// Core Tickets
		$output .= '<p><strong>Currently participating in:</strong></p>';

		$get_remote      = wp_remote_get( 'https://core.trac.wordpress.org/my-comments?USER=' . MY_WP_CONTRIBUTIONS_USERNAME );
		$get_remote_body = wp_remote_retrieve_body( $get_remote );

		$html = str_get_html( $get_remote_body );

		$mt = $html->find( 'td[class=ticket]' );
		$mc = $html->find( 'td[class=component]' );
		$ms = $html->find( 'td[class=summary]' );

		$output .= '<table class="core-tickets">';
		foreach ( $mt as $i => $t ) {
			$output .= '<tr>' . $mc[ $i ] . $t . $ms[ $i ] . '</tr>';
		}
		$output .= '</table>';

		//	Meta Tickets
		$get_remote      = wp_remote_get( 'https://meta.trac.wordpress.org/report/7?USER=' . MY_WP_CONTRIBUTIONS_USERNAME );
		$get_remote_body = wp_remote_retrieve_body( $get_remote );

		$html = str_get_html( $get_remote_body );

		$mt = $html->find( 'td[class=ticket]' );
		$mc = $html->find( 'td[class=component]' );
		$ms = $html->find( 'td[class=summary]' );

		$output .= '<table class="meta-tickets">';

		foreach ( $mt as $i => $t ) {
			$output .= '<tr>' . $mc[ $i ] . $t . $ms[ $i ] . '</tr>';
		}
		$output .= '</table>';

		wp_update_post(
			array(
				'ID'           => MY_WP_CONTRIBUTIONS_POST_ID,
				'post_content' => $output,
			)
		);

	}

	/**
	 * Add custom JS to output page.
	 *
	 * @uses is_page()
	 *
	 * @return void
	 */
	public function my_wp_contributions_js() {

		if ( is_page( MY_WP_CONTRIBUTIONS_POST_ID ) ) {
			?>
			<script>
					(function( $ ) {
						$( document ).ready( function(){

							$( 'a[href*="ticket"]' ).each(function() {

								if ($(this).parent().parent().parent().parent().hasClass('core-tickets')) {

									var newRef = 'https://core.trac.wordpress.org' + $( this ).attr( 'href' );

									$( this ).attr( 'href', newRef );
									$( this ).attr('target','_blank');
									$( this ).attr('rel','noopener');

								} else if ( $( this ).parent().parent().parent().parent().hasClass( 'meta-tickets' ) ) {

									var newRef = 'https://meta.trac.wordpress.org' + $(this).attr( 'href' );

									$( this ).attr( 'href', newRef);
									$( this ).attr('target','_blank');
									$( this ).attr('rel','noopener');

								}

							} );

						} );

					})( jQuery )
			</script>
			<?php
		}

	}

}

// Initialize My WP Contributions.
new MY_WP_CONTRIBUTIONS();
