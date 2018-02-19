<?php
/*
 * @package My WP Contributions
 * @version 1.0.0
 *
 * Plugin Name:       My WP Contributions
 * Plugin URI:        https://xkon.gr/my-wp-contributions
 * Description:       Gathers various stats & contributions from WordPress.org and updates a post daily.
 * Version:           1.0.0
 * Author:            Xenos (xkon) Konstantinos
 * Author URI:        https://xkon.gr/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       my-wp-contributions
 * Domain Path:       /languages
 *
*/

/*
 * Define the My WP Stats Post ID
 * Change these settings
*/
define( 'MY_WP_CONTRIBUTIONS_POST_ID', 771 );
define( 'MY_WP_CONTRIBUTIONS_USERNAME', 'xkon' );

function my_wp_contributions_activation() {
	if ( ! wp_next_scheduled( 'my_wp_contributions_event' ) ) {
		wp_schedule_event( time(), 'daily', 'my_wp_contributions_event' );
	}
}
register_activation_hook( __FILE__, 'my_wp_contributions_activation' );

add_action( 'my_wp_contributions_event', 'my_wp_contributions_show_contributions' );

function my_wp_contributions_deactivation() {
	wp_clear_scheduled_hook( 'my_wp_contributions_event' );
}
register_deactivation_hook( __FILE__, 'my_wp_contributions_deactivation' );

function my_wp_contributions_show_contributions() {
	require_once( dirname( __FILE__ ) . '/simple_html_dom.php' );
	$output = 'WordPress.org Username: <a href="https://profiles.wordpress.org/' . MY_WP_CONTRIBUTIONS_USERNAME . '">'. MY_WP_CONTRIBUTIONS_USERNAME . '</a><br/>';

	// General
	$curl = curl_init();
	curl_setopt( $curl, CURLOPT_URL, 'https://wordpress.org/support/users/' . MY_WP_CONTRIBUTIONS_USERNAME );
	curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $curl, CURLOPT_CONNECTTIMEOUT, 10 );
	$str = curl_exec( $curl );
	curl_close( $curl );

	$html = str_get_html( $str );
	$forum_role = $html->find( 'p[class=bbp-user-forum-role]' );
	$forum_replies = $html->find( 'p[class=bbp-user-reply-count]' );

	$output .= $forum_role[0]->innertext . '<br>';
	$output .= 'Forum ' . $forum_replies[0]->innertext . '<br>';

	// core commits
	$curl = curl_init();
	curl_setopt( $curl, CURLOPT_URL, 'https://core.trac.wordpress.org/search?q=props+' . MY_WP_CONTRIBUTIONS_USERNAME . '&noquickjump=1&changeset=on' );
	curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $curl, CURLOPT_CONNECTTIMEOUT, 10 );
	$str = curl_exec( $curl );
	curl_close( $curl );

	$html = str_get_html( $str );

	$mt = $html->find( 'dl[id=results] dt' );

	$output .= 'Core commits: ' . count( $mt );

	// plugins commits
	$curl = curl_init();
	curl_setopt( $curl, CURLOPT_URL, 'https://plugins.trac.wordpress.org/search?q=props+' . MY_WP_CONTRIBUTIONS_USERNAME . '&noquickjump=1&changeset=on' );
	curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $curl, CURLOPT_CONNECTTIMEOUT, 10 );
	$str = curl_exec( $curl );
	curl_close( $curl );

	$html = str_get_html( $str );

	$mt = $html->find( 'dl[id=results] dt' );

	$plugin_commits = count( $mt );

	// meta commits
	$curl = curl_init();
	curl_setopt( $curl, CURLOPT_URL, 'https://meta.trac.wordpress.org/search?q=props+' . MY_WP_CONTRIBUTIONS_USERNAME . '&noquickjump=1&changeset=on' );
	curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $curl, CURLOPT_CONNECTTIMEOUT, 10 );
	$str = curl_exec( $curl );
	curl_close( $curl );

	$html = str_get_html( $str );

	$mt = $html->find( 'dl[id=results] dt' );

	$meta_commits = count( $mt );

	$totalmeta = $plugin_commits + $meta_commits;
	$output .= '</br>';
	$output .= 'Meta commits: ' . $totalmeta;

	// Core Tickets
	$output .= '<h3>Open Core tickets that I\'m participating</h3>';

	$curl = curl_init();
	curl_setopt( $curl, CURLOPT_URL, 'https://core.trac.wordpress.org/my-comments?USER=' . MY_WP_CONTRIBUTIONS_USERNAME );
	curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $curl, CURLOPT_CONNECTTIMEOUT, 10 );
	$str = curl_exec( $curl );
	curl_close( $curl );

	$html = str_get_html( $str );

	$mt = $html->find( 'td[class=ticket]' );
	$mc = $html->find( 'td[class=component]' );
	$ms = $html->find( 'td[class=summary]' );

	$output .= '<table class="core-tickets">';
	foreach ( $mt as $i => $t ) {
		$output .= '<tr><td>' . $mc[ $i ] . '</td><td>' . $t . '</td><td>' . $ms[ $i ] . '</td></tr>';
	}
	$output .= '</table>';

	//	Meta Tickets
	$curl = curl_init();
	curl_setopt( $curl, CURLOPT_URL, 'https://meta.trac.wordpress.org/report/7?USER=' . MY_WP_CONTRIBUTIONS_USERNAME );
	curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt( $curl, CURLOPT_CONNECTTIMEOUT, 10 );
	$str = curl_exec( $curl );
	curl_close( $curl );

	$html = str_get_html( $str );

	$mt = $html->find( 'td[class=ticket]' );
	$mc = $html->find( 'td[class=component]' );
	$ms = $html->find( 'td[class=summary]' );

	$output .= '<h3>Open Meta tickets that I\'m participating</h3>';

	$output .= '<table class="meta-tickets">';
	foreach ( $mt as $i => $t ) {
		$output .= '<tr><td>' . $mc[ $i ] . '</td><td>' . $t . '</td><td>' . $ms[ $i ] . '</td></tr>';
	}
	$output .= '</table>';

	wp_update_post(
		array(
			'ID' => MY_WP_CONTRIBUTIONS_POST_ID,
			'post_content' => $output,
		)
	);

}

function my_wp_contributions_js() {
	if ( is_page( 'wp-contributions' ) ) {
		echo '<script>
				(function($){
					$("td:empty").remove();
					$("a[href*=\'ticket\']").each(function() {
						if ($(this).parent().parent().parent().parent().hasClass(\'core-tickets\')) {
							var newRef = \'https://core.trac.wordpress.org\' + $(this).attr(\'href\');
							$(this).attr(\'href\', newRef);
						} else if ($(this).parent().parent().parent().parent().hasClass(\'meta-tickets\')) {
							var newRef = \'https://meta.trac.wordpress.org\' + $(this).attr(\'href\');
							$(this).attr(\'href\', newRef);
						}
					});
				})(jQuery)
				</script>';
	}
}
add_action( 'wp_footer', 'my_wp_contributions_js', 100 );
