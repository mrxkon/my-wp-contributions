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

		add_action( 'admin_menu', array( $this, 'create_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'create_options' ) );
		add_action( 'my_wp_contributions_event', array( $this, 'my_wp_contributions_show_contributions' ) );
		add_action( 'wp_footer', array( $this, 'my_wp_contributions_js' ), 100 );
		add_action( 'wp_ajax_my_wp_contributions_regenerate', array( $this, 'regenerate' ) );
		add_action( 'admin_init', array( $this, 'create_wordpress_page' ) );
		register_activation_hook( __FILE__, array( $this, 'my_wp_contributions_activation' ) );
		register_deactivation_hook( __FILE__, array( $this, 'my_wp_contributions_deactivation' ) );

	}

	/**
	 * Regenerate function.
	 *
	 * @return void
	 */
	public function regenerate() {

		MY_WP_CONTRIBUTIONS::my_wp_contributions_show_contributions();
		$response = 'success';
		wp_send_json_success( $response );

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
	 * Create Admin menu.
	 *
	 * @uses add_menu_page()
	 *
	 * @return void
	 */
	public function create_admin_menu() {

		add_options_page(
			'My WP Contributions Settings',
			'My WP Contributions',
			'manage_options',
			'my-wp-contributions',
			array( $this, 'create_my_wp_contributions_page' )
		);

	}

	/**
	 * Create custom page
	 *
	 *
	 * @return void
	 */
	public function create_wordpress_page() {

		$slug = 'my-wp-contributions-page';

		$args = array(
			'post_type'      => 'page',
			'pagename'       => $slug,
			'posts_per_page' => 1,
		);

		$query = new WP_Query( $args );

		if ( ! $query->have_posts() ) {
			wp_insert_post(
				array(
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
					'post_author'    => get_current_user_id(),
					'post_name'      => $slug,
					'post_title'     => 'Use [my-wp-contributions] shortcode - DO NOT DELETE OR EDIT',
					'post_content'   => '',
					'post_status'    => 'private',
					'post_type'      => 'page',
				)
			);
		}

	}

	/**
	 * Create options.
	 *
	 * @uses add_option()
	 * @uses register_setting()
	 *
	 * @return void
	 */
	public function create_options() {

		add_option( 'mywpcontributions_general_title', 'Currently participating in:' );
		add_option( 'mywpcontributions_core_commits_title', 'Core Commits:' );
		add_option( 'mywpcontributions_meta_commits_title', 'Meta Commits:' );
		add_option( 'mywpcontributions_show_core', 'Yes' );
		add_option( 'mywpcontributions_show_meta', 'Yes' );

		register_setting(
			'mywpcontributions_options_group',
			'mywpcontributions_username',
			array( $this, 'text_sanitize' )
		);

		register_setting(
			'mywpcontributions_options_group',
			'mywpcontributions_before',
			array( $this, 'html_sanitize' )
		);

		register_setting(
			'mywpcontributions_options_group',
			'mywpcontributions_after',
			array( $this, 'html_sanitize' )
		);

		register_setting(
			'mywpcontributions_options_group',
			'mywpcontributions_styles',
			array( $this, 'html_sanitize' )
		);

		register_setting(
			'mywpcontributions_options_group',
			'mywpcontributions_general_title',
			array( $this, 'html_sanitize' )
		);

		register_setting(
			'mywpcontributions_options_group',
			'mywpcontributions_core_commits_title',
			array( $this, 'html_sanitize' )
		);

		register_setting(
			'mywpcontributions_options_group',
			'mywpcontributions_meta_commits_title',
			array( $this, 'html_sanitize' )
		);

		register_setting(
			'mywpcontributions_options_group',
			'mywpcontributions_show_core',
			array( $this, 'select_sanitize' )
		);

		register_setting(
			'mywpcontributions_options_group',
			'mywpcontributions_show_meta',
			array( $this, 'select_sanitize' )
		);

	}

	/**
	* Sanitize text setting field as needed.
	*
	* @param array $input Contains the text string.
	*
	* @uses sanitize_text_field()
	*
	* @return string $new_input Sanitized text setting.
	*/
	public function text_sanitize( $input ) {

		if ( isset( $input ) ) {
			$new_input = sanitize_text_field( $input );
		}

		return $new_input;

	}

	/**
	* Sanitize textarea setting field as needed.
	*
	* @param array $input Contains the textarea string.
	*
	* @uses wp_kses_post()
	*
	* @return string $new_input Sanitized textarea string.
	*/
	public function html_sanitize( $input ) {

		if ( isset( $input ) ) {
			$new_input = wp_kses_post( $input );
		}

		return $new_input;

	}

	/**
	* Sanitize select setting field as needed.
	*
	* @param array $input Contains the select string.
	*
	*
	* @return string $new_input Sanitized select string.
	*/
	public function select_sanitize( $input ) {

		if ( 'Yes' === $input || 'No' === $input ) {
			$new_input = $input;
		} else {
			$new_input = 'Yes';
		}

		return $new_input;

	}

	/**
	 * Create Admin Page.
	 *
	 * @uses settings_fields()
	 * @uses get_option()
	 * @uses submit_button()
	 *
	 * @return void
	 */
	public function create_my_wp_contributions_page() {
		?>
		<div class="wrap">
			<h1>My WP Contributions</h1>
			<form method="post" action="options.php">
			<?php settings_fields( 'mywpcontributions_options_group' ); ?>
				<table class="widefat">
					<tr>
						<td>
							<h3>WordPress.org Username</h3>
							<input type="text" id="mywpcontributions_username" name="mywpcontributions_username" value="<?php echo esc_attr( get_option( 'mywpcontributions_username' ) ); ?>" />
							<h3>General Title (html)</h3>
							<input type="text" id="mywpcontributions_general_title" name="mywpcontributions_general_title" value="<?php echo esc_attr( get_option( 'mywpcontributions_general_title' ) ); ?>" />
						</td>
						<td>
							<h3>Core Commits Title (html)</h3>
							<input type="text" id="mywpcontributions_core_commits_title" name="mywpcontributions_core_commits_title" value="<?php echo esc_attr( get_option( 'mywpcontributions_core_commits_title' ) ); ?>" />
							<h3>Meta Commits Title (html)</h3>
							<input type="text" id="mywpcontributions_meta_commits_title" name="mywpcontributions_meta_commits_title" value="<?php echo esc_attr( get_option( 'mywpcontributions_meta_commits_title' ) ); ?>" />
							</td>
						<td>
							<h3>Show Core Commits</h3>
							<select name="mywpcontributions_show_core" id="mywpcontributions_show_core">
								<?php
								if ( 'Yes' === get_option( 'mywpcontributions_show_core' ) ) {
									echo '<option value="Yes" selected="selected">Yes</option><option values="No">No</option>';
								} else {
									echo '<option value="Yes">Yes</option><option values="No" selected="selected">No</option>';
								}
								?>
							</select>
						<h3>Show Meta Commits</h3>
							<select name="mywpcontributions_show_meta" id="mywpcontributions_show_meta">
								<?php
								if ( 'Yes' === get_option( 'mywpcontributions_show_meta' ) ) {
									echo '<option value="Yes" selected="selected">Yes</option><option values="No">No</option>';
								} else {
									echo '<option value="Yes">Yes</option><option values="No" selected="selected">No</option>';
								}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<td colspan="3">
							<h3>Before output (html)</h3>
							<textarea style="width: 100%;height:200px;" id="mywpcontributions_before" name="mywpcontributions_before"><?php echo esc_textarea( get_option( 'mywpcontributions_before' ) ); ?></textarea>
						</td>
					</tr>
					<tr>
						<td colspan="3">
							<h3>After output (html)</h3>
							<textarea style="width: 100%;height:200px;" id="mywpcontributions_after" name="mywpcontributions_after"><?php echo esc_textarea( get_option( 'mywpcontributions_after' ) ); ?></textarea>
						</td>
					</tr>
					<tr>
						<td colspan="3">
							<h3>Extra CSS rules (mark everything with !important)</h3>
							<textarea style="width: 100%;height:200px;" id="mywpcontributions_styles" name="mywpcontributions_styles"><?php echo esc_textarea( get_option( 'mywpcontributions_styles' ) ); ?></textarea>
						</td>
					</tr>
					<tr>
			</table>
				<?php submit_button(); ?>
			</form>
			<p id="wait"></p>
			<input type="button" id="my-wp-contributions-regenerate" class="button button-primary" value="Regenerate Page">
		</div>
		<style>
			table.widefat {
				background: transparent;
				border: 0;
				box-shadow: none;
			}
			table.widefat td input {
				width: 100%;
			}
			@media (max-width:860px){
				table.widefat td {
					display: block;
					clear: both;
				}
			}
		</style>
		<script>
		(function($){
			$( document ).ready( function() {
				var regenerate = $( '#my-wp-contributions-regenerate' );
				var regenerateData = { 'action': 'my_wp_contributions_regenerate' };
				regenerate.on( 'click', function() {
					$( regenerate ).prop( 'disabled', true );
					$('#wait').html('Please wait.');
					$.post( ajaxurl, regenerateData, function( response ) {
						if ( 'success' === response.data ) {
							$( regenerate ).prop( 'disabled', false );
							$('#wait').html('Page refreshed.');
						}
					} );
				} );
			});
		})(jQuery)
		</script>
	<?php
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
		$page               = get_page_by_path( 'my-wp-contributions-page' );
		$page_id            = $page->ID;
		$username           = get_option( 'mywpcontributions_username' );
		$before             = get_option( 'mywpcontributions_before' );
		$after              = get_option( 'mywpcontributions_after' );
		$styles             = get_option( 'mywpcontributions_styles' );
		$general_title      = get_option( 'mywpcontributions_general_title' );
		$core_commits_title = get_option( 'mywpcontributions_core_commits_title' );
		$meta_commits_title = get_option( 'mywpcontributions_meta_commits_title' );
		$show_core          = get_option( 'mywpcontributions_show_core' );
		$show_meta          = get_option( 'mywpcontributions_show_meta' );

		if ( ! empty( $username ) && ! empty( $page_id ) ) {

			require_once( dirname( __FILE__ ) . '/inc/simple_html_dom.php' );

			$output = $before;

			$output .= '<p class="general-info">';

			$output .= 'WordPress.org Username: <a href="https://profiles.wordpress.org/' . $username . '">' . $username . '</a><br/>';

			// General

			// Setup API Call.
			$get_general  = wp_remote_get( 'https://wordpress.org/support/users/' . $username );
			$general_body = wp_remote_retrieve_body( $get_general );

			$html          = str_get_html( $general_body );
			$forum_role    = $html->find( 'p[class=bbp-user-forum-role]' );
			$forum_replies = $html->find( 'p[class=bbp-user-reply-count]' );

			$output .= $forum_role[0]->innertext . '<br>';
			$output .= 'Forum ' . $forum_replies[0]->innertext . '<br>';

			if ( 'Yes' === $show_core ) {
				// core commits
				$get_remote      = wp_remote_get( 'https://core.trac.wordpress.org/search?q=props+' . $username . '&noquickjump=1&changeset=on' );
				$get_remote_body = wp_remote_retrieve_body( $get_remote );

				$html = str_get_html( $get_remote_body );

				$mt = $html->find( 'dl[id=results] dt' );

				$output .= 'Core commits: ' . count( $mt ) . '<br>';
			}

			if ( 'Yes' === $show_meta ) {
				// plugins commits

				$get_remote      = wp_remote_get( 'https://plugins.trac.wordpress.org/search?q=props+' . $username . '&noquickjump=1&changeset=on' );
				$get_remote_body = wp_remote_retrieve_body( $get_remote );

				$html = str_get_html( $get_remote_body );

				$mt = $html->find( 'dl[id=results] dt' );

				$plugin_commits = count( $mt );

				// meta commits

				$get_remote      = wp_remote_get( 'https://meta.trac.wordpress.org/search?q=props+' . $username . '&noquickjump=1&changeset=on' );
				$get_remote_body = wp_remote_retrieve_body( $get_remote );

				$html = str_get_html( $get_remote_body );

				$mt = $html->find( 'dl[id=results] dt' );

				$meta_commits = count( $mt );

				$total_meta_commits = $plugin_commits + $meta_commits;

				$output .= 'Meta commits: ' . $total_meta_commits;
			}

			$output .= '</p>';

			$output .= $general_title;

			if ( 'Yes' === $show_core ) {
				// core Tickets
				$output .= $core_commits_title;

				$get_remote      = wp_remote_get( 'https://core.trac.wordpress.org/my-comments?USER=' . $username );
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
			}

			if ( 'Yes' === $show_meta ) {
				//	meta Tickets
				$output .= $meta_commits_title;

				$get_remote      = wp_remote_get( 'https://meta.trac.wordpress.org/report/7?USER=' . $username );
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
			}

			$output .= $after;

			$output .= '<style>' . $styles . '</style>';

		} else {

			$output = 'Please set your WordPress.org Username in the My WP Contributions settings page.';

		}

		wp_update_post(
			array(
				'ID'           => $page_id,
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
		// TODO: find a way to do this with the shortcode
		$page    = get_page_by_path( 'my-wp-contributions-page' );
		$page_id = $page->ID;
		if ( is_page( $page_id ) ) {
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
