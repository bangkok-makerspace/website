<?php

class WP_Meetup_Admin {

	private $options;
	var $wp_meetup;
	var $sqltable_cron;

	// Start Up
	public function __construct($meetup) {
		
		global $wpdb;
		$this->wp_meetup = $meetup;
		$this->sqltable_cron = $wpdb->prefix . 'nm_cron';
		add_action('admin_menu', array( $this, 'add_plugin_pages' ));
		add_action('admin_enqueue_scripts', array(&$this, 'load_settings_styles'), 100);
	}

	public function add_plugin_pages() {
		// This page will be under "Settings"
		add_menu_page(
			'WP Meetup Settings', 
			'WP Meetup', 
			'administrator', 
			'wp_meetup_settings', 
			array($this, 'create_admin_page')
		);
		add_submenu_page(
			'wp_meetup_settings',
			'Options',
			'Options',
			'administrator',
			'wp_meetup_options',
			array($this, 'create_options_submenu')
		);
		add_submenu_page(
			'wp_meetup_settings',
			'Groups',
			'Groups',
			'administrator',
			'wp_meetup_groups',
			array($this, 'create_groups_submenu')
		);
		$event_page_name = $this->wp_meetup->custom_post_type;
		$event_page_name = ucfirst($event_page_name);
		add_submenu_page(
			'wp_meetup_settings',
			$event_page_name,
			$event_page_name,
			'administrator',
			'wp_meetup_events',
			array($this, 'create_events_submenu')
		);
		add_submenu_page(
			'wp_meetup_settings',
			'Debug Information',
			'Debug Information',
			'administrator',
			'wp_meetup_debug',
			array($this, 'create_debug_submenu')
		);
	}

	// Options page callback
	public function create_admin_page() {
		
		if (!current_user_can('manage_options'))  {
			wp_die( __('You do not have sufficient permissions to access this page.') );
		}
		global $wpdb, $nmcron;
		?>
		<div class="wrap">
			<?php 
			screen_icon(); 

			$this->update_all_options();
			echo '<div class="clear"></div>';
			$wpmOptions = get_option($this->wp_meetup->options_name);
			if ($wpmOptions['apikey'] === NULL) {   
				// if there is no access token set, then this is run, requesting information required to generate accesss token.
				$this->request_apikey();
			}
			if ($wpmOptions['apikey'] !== NULL) {
				$this->display_main_options();
				?>
					
				</div>
				<?php
			}
			?>
		</div>
		<?php
	}

	function display_main_options() {
		?>
			<div class="wrap">
				<div class="wp-meetup-options-page">
					<div class="logo-wrap one">
						<a href="http://www.nuancedmedia.com" title="Nuanced Media"><img src="<?php echo plugins_url(); ?>/wp-meetup/images/meetup_logo.png" width="106" height="59"/></a>
						<h2>WP Meetup Options</h2>
					</div>
					<div class="clear"></div>
					<div class="two-thirds">
						<div class="one-half">
							<h3>Instructions</h3>
							<p>Thanks for using the WP Meetup Plugin for WordPress! At this point you can place the shortcode <strong>[meetup-calendar]</strong> on any page to display the current month and its events.</p>
							<p>If you are interested if displaying multiple month, this is completely possible. In order to display one past month, add "past=1" to your shortcode. Also, If you would like to add one future month, add "future=1" to your shortcode. The current month will always display. Past and future can be combined in your shortcode. Example: <strong>[meetup-calendar past=1 future=1]</strong>.</p>
							<!-- <h3>Visual Options</h3>
							<p>We currently don't have Visual Options</p> -->
							<br />
							<div class="credit-permission">
								<?php $permission = get_option('wpm_credit_permission'); ?>
								<?php $permission_val = ''; if ($permission['permission_value'] == 'checked') { $permission_val = 'checked'; } ?>
								<h3>Support our development staff</h3>
								<form method="post" action="" name="credit_permission">
								
								<input type="hidden" name="update_permission" value="permission update" />
								<input type="checkbox" name="credit_permission" value="checked" <?php echo $permission_val; ?> />
								<label>We thank you for choosing to use our plugin! We would also appreciate it if you allowed us to put our name on the plugin we worked so hard to build. If you are okay with us having a credit line on the calendar, then please check this box and change your permission settings.</label>
								<br />
								<input type="submit" value="Change Permission Setting" form_id="credit_permission" />
								</form>
							</div>
						</div>
						<div class="one-half">
							<h3>Need to update the events right now?</h3>
							<p>The below button will force the WP Meetup plugin to update the database of events. This way if you just posted an event on Meetup and want the event on the calendar right now, all thats needed is one click. </p>
							<div class="demand-update-button">
								<form method="POST" action="">
								<input type="hidden" name="demand_update" value="right now" />
								<input type="submit" value="Update Events Now">
								</form>
							</div>
							<?php $this->insert_mailing_list();
							$this->insert_review_us(); ?>
						</div>
						<div class="clear"></div>
					</div>
					<div class="one-third">
						<h3>Built by</h3>
						<p>The <a href="http://nuancedmedia.com/">Nuanced Media</a> team.</p>
						<div>
							<?php
								$url=plugins_url();
								$output='<a href="http://www.nuancedmedia.com" title="Nuanced Media"><img src="'.$url.'/wp-meetup/images/NM_logo_banner.png" width="106" height="59"/></a></div>';
								echo $output;
							?>
							<div id="fb-root"></div>
							<script>(function(d, s, id) {
							  var js, fjs = d.getElementsByTagName(s)[0];
							  if (d.getElementById(id)) {return;}
							  js = d.createElement(s); js.id = id;
							  js.src = "//connect.facebook.net/en_US/all.js#xfbml=1";
							  fjs.parentNode.insertBefore(js, fjs);
							}(document, 'script', 'facebook-jssdk'));</script>
							<div id="wp-meetup-social">
								<div class="fb-like" data-href="https://www.facebook.com/NuancedMedia" data-send="false" data-layout="button_count" data-width="100" data-show-faces="true"></div><br><br>
								<g:plusone annotation="inline" width="216" href="http://nuancedmedia.com/"></g:plusone><br>
								<!-- Place this tag where you want the +1 button to render -->
								<script type="text/javascript" src="https://apis.google.com/js/plusone.js"></script>
								<div class="g-plus" height=69 data-href="https://plus.google.com/105681796007125615548/" rel="author"></div>
								
							</div>
							<!-- Place this render call where appropriate -->
							<script type="text/javascript">
							(function() {
								var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
								po.src = 'https://apis.google.com/js/plusone.js';
								var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
							  })();
							</script>
							
							<h3>WP Meetup Links</h3>
							<ul class="wp-meetup-link-list">
							<li><a href="http://wordpress.org/extend/plugins/wp-meetup/" target="_blank">Wordpress.org Plugin Directory listing</a></li>
							<li><a href="http://nuancedmedia.com/wordpress-meetup-plugin/" target="_blank">WP Meetup Plugin homepage</a></li>
							</ul>
						</div>
					</div>
					<div class="clear"></div>
				</div>
			</div>
		<?php

	}

	function insert_link_color_checkbox() {
		$option = $this->wp_meetup->color_permission;
		$permission = get_option($option);
		$color_permission_val = ''; 
		if ($permission['color_permission'] == 'checked') { 
			$color_permission_val = 'checked'; 
		} 
		?>
		<div>
		<h3>White Link Color</h3>
		<form method="post" action="">
		<input type="hidden" name="update_color_permission" value="permission update" />
		<input type="checkbox" name="color_permission" value="checked" <?php echo $color_permission_val; ?> />
		<label>Checking this box will make all links within the calendar white instead of the color created by your theme default.</label><br />
		<input type="submit" value="Change Link Color" />
		</form>
		</div>
		<?php
	}

	function insert_review_us() {
		?>
		<h3>Review Us</h3>
		<p>Tell us your opinion of the plugin. We are continuously working to improve your experience with the Meetup Plugin and we can do that better if we know what you like and dislike. Let us know on the Wordpress <a href="http://wordpress.org/support/view/plugin-reviews/wp-meetup">Review Page</a>. </p>
		<?php
	}

	function insert_mailing_list() {
		?>
		<h3>Email List</h3>
		<p>Stay updated on new releases and future features for the WP Meetup Plugin by joining the email list below.</p>
		<div class="meetup-mailing-list-form">
			<script>
			jQuery(function(){
			});
			</script>
			<form method="POST" action="http://nuancedmedia.com/wordpress-meetup-plugin/">
				<table>
					<tr>
						<td>
							Email:
						</td>
						<td>
							<input name="input_2" id="input_10_2" type="text" value="" class="medium" tabindex="1">
						</td>
					</tr>
					<tr>
						<td>
							<input type="submit" id="gform_submit_button_10" class="button gform_button" value="Join" tabindex="2" onclick="if(window[&quot;gf_submitting_10&quot;]){return false;}  window[&quot;gf_submitting_10&quot;]=true; ">
							<input type="hidden" class="gform_hidden" name="is_submit_10" value="1">
							<input type="hidden" class="gform_hidden" name="gform_submit" value="10">
							<input type="hidden" class="gform_hidden" name="gform_unique_id" value="">
							<input type="hidden" class="gform_hidden" name="state_10" value="WyJhOjA6e30iLCI3MzgxZDc3NTA3OTk0MDMwMTI4MTM4ZDczZTU1MzNkMSJd">
							<input type="hidden" class="gform_hidden" name="gform_target_page_number_10" id="gform_target_page_number_10" value="0">
							<input type="hidden" class="gform_hidden" name="gform_source_page_number_10" id="gform_source_page_number_10" value="1">
							<input type="hidden" name="gform_field_values" value="">
						</td>
					</tr>
				</table>
			</form>
		</div>
		<?php
	}

	function update_all_options() {

		if (isset($_POST['submitted']) && $_POST['submitted'] == 'apiKeySecrets') {
			$this->update_apikey();
		}
		if (isset($_POST['demand_update']) && $_POST['demand_update'] == 'right now') {
			$this->demand_update_cron();
			echo '<div class="updated">';
			echo "<h3>Event Update Successful!</h3>";
			$event_debug_result = get_option($this->wp_meetup->event_debug_string);
			if ($event_debug_result && isset($event_debug_result['result'])) {
				echo '<p>' . $event_debug_result['result'] . '</p>';
			}
			echo '</div><div class="clear"></div>';
			$_POST['demand_update'] = NULL;
		}
		if (isset($_POST['update_permission']) && $_POST['update_permission'] == 'permission update') {
			$current_permission = get_option($this->wp_meetup->credit_permission);
			//$permission = 'checked';
			if (!isset($_POST['credit_permission'])) {
				$permission = FALSE;
			}
			else {
				$permission = 'checked';
			}
			$update_permission = array(
				'permission_value' => $permission,
				);
			update_option($this->wp_meetup->credit_permission, $update_permission);
			$_POST['update_permission'] = NULL;
		}
		if (isset($_POST['update_color_permission']) && $_POST['update_color_permission'] == 'permission update') {
			$current_color_permission = get_option($this->wp_meetup->color_permission);
			if (!isset($_POST['color_permission'])) {
				$permission = FALSE;
			}
			else {
				$permission = 'checked';
			}
			$update_color_permission = array(
				'color_permission' => $permission,
				);
			update_option($this->wp_meetup->color_permission, $update_color_permission);
			$_POST['update_permission'] = NULL;
		}
		if (isset($_POST['update_redirect_link']) && $_POST['update_redirect_link'] == 'redirect link') {
			$redirect_link_permission = get_option($this->wp_meetup->redirect_link);
			if (!isset($_POST['redirect_link'])) {
				$permission = FALSE;
			}
			else {
				$permission = 'checked';
			}
			$update_redirect_link = array(
				'redirect_link' => $permission,
				);
			update_option($this->wp_meetup->redirect_link, $update_redirect_link);
			$_POST['update_redirect_link'] = NULL;
		}
		if (isset($_POST['widget_options']) && $_POST['widget_options'] == 'Update Widget') {
			$option_name = $this->wp_meetup->widget_options;
			$options = array();
			foreach ($_POST as $key=>$value) {
				if ($key != 'widget_options') {
					$options[$key] = $value;
				}
			}
			update_option($option_name, $options);
			$_POST['widget_options'] = NULL; 
		}
		if (isset($_POST['update_incl_homepage']) && $_POST['update_incl_homepage'] == 'include homepage') {
			$incl_homepage_permission = get_option($this->wp_meetup->include_homepage);
			if (!isset($_POST['include_homepage'])) {
				$permission = FALSE;
			}
			else {
				$permission = 'checked';
			}
			$update_incl_homepage = array(
				'include_homepage' => $permission,
				);
			update_option($this->wp_meetup->include_homepage, $update_incl_homepage);
			$_POST['update_incl_homepage'] = NULL;
		}
		if (isset($_POST['perform_secret']) && $_POST['perform_secret'] == 'update_performance') {
			$newdata = array(
				'past_months_queried' => $_POST['past_months_queried'],
				'future_months_queried' => $_POST['future_months_queried'],
				'max_event' => $_POST['max_event'],
				);
			update_option($this->wp_meetup->performance_option_name, $newdata);

		}
	}


	function request_apikey() {

		?>
		<form method="post" action="">
			<div class="wpm-settings">
				<div class="wpm-settings-header">
					<h2>WP Meetup Settings</h2>
					<p>Thank you for choosing to use the WP Meetup plugin for Wordpress. Please fill out the following before continuing. </p>
					<p>For those who aren't familiar with API keys, <a href="http://www.meetup.com/meetup_api/key/" target="_blank"><strong>This link</strong></a> will take you to where you can find yours.</p>
				</div>
				<input type="hidden" name="submitted" value="apiKeySecrets" />
				<table>
					<tr class="wpm-register-settings-option">
						<td><label><strong>Meetup API Key</strong></label></td>
						<td><input name="apikey" type="text" autocomplete="on" /></td>
					</tr>

					<tr class="wpm-register-settings-option">
						<td><label><strong>Meetup URL Name</strong></label></td>
						<td>www.meetup.com/<input name="urlname" type="text" autocomplete="on" /></td>
					</tr>
					<tr>
						<td></td>
						<td><input type="submit" value="Submit" /></td>
					</tr>
				</table>
			</div>
		</form>
		<?php
	}

	function update_apikey() {

		if (isset($_POST['submitted']) && $_POST['submitted'] == 'apiKeySecrets') {
			$_POST['submitted'] = 'not submitted';
			$settings = get_option($this->wp_meetup->options_name);
			$meetup_options = array(
				'apikey' => $_POST['apikey'],
				'urlname' => $_POST['urlname'],
				);
			update_option($this->wp_meetup->options_name, $meetup_options);
			$group_name = $_POST['urlname'];
			$group_name = str_replace('/', '', $group_name);
			$new_group=array(
				'name' => $group_name,
				'group_id' => $this->get_group_id($group_name)
			);
			$groups[] = $new_group;
			update_option('wp_meetup_groups', $groups);
			$this->demand_update_cron();
		}
	}

	function demand_update_cron() {
		// This forces the nmcron to return a 1, which then allows for a query of events. 
		global $wpdb, $nmcron, $wpMeetup;
		$data = array(
			'last_date' => date('Y-m-d H:00:00'),
			'run'       => 1,
			);
		$where = array(
			'id' => 1
			);
		$wpdb->update( $this->sqltable_cron, $data, $where);
		$this->wp_meetup->maybe_update_event_posts(TRUE);
	}

	function create_groups_submenu() {
		global $wpdb, $nmcron;
		if (!current_user_can('manage_options')) {
			wp_die( __('You do not have sufficient permissions to access this page.') );
		}
		if ($this->wp_meetup->is_registered()) {
			?>
				<div class="wrap">
					<h3>Groups</h3>
					<form method="post" action="">
					<table>
					<tbody>
						<?php 
						$this->update_apikey();
						$this->update_group_options();
						$this->display_group_colorpickers();
						?>
						<tr>
							<td><input type="submit" value="Save Colors"></td>
						</tr>
					</tbody>
					</table>
					<hr>
					<table>
					<tbody>
						<?php
						$this->display_meetup_addition();
						?>
						<tr>
							<td><input type="submit" value="Add New Group"></td>
						</tr>
					</tbody>
					</table>	
					</form>
				</div>
			<?php
		}
		else {
			$this->request_apikey();
		}
	}

	function display_group_colorpickers() {

		if (isset($_GET['remove']) && $_GET['remove']!==NULL) {
			$this->remove_group($_GET['remove']);
			$_GET['remove'] = NULL;
		}
		$groups = get_option($this->wp_meetup->group_options_name);
		$wp_meetup_colors = get_option($this->wp_meetup->color_options_name);
		if (isset($groups)) {
			foreach ($groups as $group) {
				$name = $group['name'];
				$color_array = $wp_meetup_colors['colors'];
				$color_name = 'wpm_calendar_' . $name . '_color';
				$admin_url = admin_url( 'admin.php?page=wp_meetup_groups&remove=' . $name);
				if (isset($color_array[$color_name])){
					$output = '<tr><td><label>color for ' . $name . '</label></td><td><input type="color" name="wpm_calendar_' . $name . '_color" value="' . $color_array[$color_name] . '"></td><td>Delete Group?</td><td><a href="' . $admin_url . '">DELETE</a></td></tr>';
				}
				else{
					$output = '<tr><td><label>color for ' . $name . '</label></td><td><input type="color" name="wpm_calendar_' . $name . '_color" value="#555555"></td><td>Delete Group?</td><td><a href="' . $admin_url . '">DELETE</a></td></tr>';
				}
				echo $output;
			}
		}
		?>
		<input type="hidden" name="submitted" value="wpmColorSecrets" />
		<?php
	}


	function display_meetup_addition() {
		?>		
			<tr class="wpm-register-settings-option">
				<td><label><strong>Add Meetup URL Name:</strong></label></td>
				<td>www.meetup.com/<input name="add_wpm_urlname" type="text" autocomplete="off" /></td>
			</tr>
			<input type="hidden" name="submitted" value="wpmMainSecrets" />
		<?php
	}

	function update_group_options() {

		if (isset($_POST['submitted']) && $_POST['submitted'] == 'wpmMainSecrets' && $_POST['add_wpm_urlname'] != NULL) {
			$wpmgroups = get_option('wp_meetup_groups');
			$group_name = $_POST['add_wpm_urlname'];
			$group_name = str_replace('/', '', $group_name);
			$group_name = str_replace('#', '', $group_name);
			$group_name = str_replace(':', '', $group_name);
			$new_group=array(
				'name' => $group_name,
				'group_id' => $this->get_group_id($group_name)
			);
			if (!in_array($new_group, $wpmgroups) && $new_group['name'] != NULL) {
				$wpmgroups[] = $new_group;
			
				update_option($this->wp_meetup->group_options_name, $wpmgroups);
				$this->demand_update_cron();
			}
		}
		if(isset($_POST['submitted']) && $_POST['submitted'] == 'wpmMainSecrets'){
			$_POST['submitted'] = 'not submitted';
			foreach ($_POST as $post_item) {
				if ($post_item !='submitted' and $post_item != 'add_wpm_urlname') {
					$color_option[]= $post_item;
				}
			}
			$wpmsettings = get_option($this->wp_meetup->color_options_name);
			$wp_meetup_colors = array(
				'colors' => $_POST,
				);
			update_option($this->wp_meetup->color_options_name, $wp_meetup_colors);
		}
		$_POST['submitted'] = 'not submitted';
	}

	function create_debug_submenu() {
		global $wpdb, $meetup, $nmcron;

		$this->emergency_update_apikey();
		$groups = get_option($this->wp_meetup->group_options_name);
		$colors = get_option($this->wp_meetup->color_options_name);
		$options = get_option($this->wp_meetup->options_name);
		$colors = $colors['colors'];
		$apikey = $options['apikey'];
		$debug_heading = 'WP Meetup Debug';
		$output = '';
		?>
		<div class="debug-container-wrap">
			<div class="debug-heading">
				<h1><?php echo $debug_heading ?></h1>
				<p>Your stored API key is: <?php echo $apikey ?>. </p>
				<?php
				$this->replace_apikey();
				?>
			</div>
			<br>
			<div class="debug-body">
				<div class="cron-update">
					<?php
					$lastran = $wpdb->get_results("SELECT `last_date` FROM $this->sqltable_cron");
					$lastran = $lastran[0];
					$lastran = $lastran->last_date;
					$time = getdate();
					$currenttime = $time['year'] . '-' . $time['mon'] . '-' . $time['mday'] . ' ' . $time['hours'] . ':' . $time['minutes'] .':' . $time['seconds'];
					$currenttime = strtotime($currenttime);
					$currenttime = date('Y-m-d H:i:s',$currenttime);
					$future = $time['mday']+1;
					$future = $future . ' 00:00:00';
					$nexttime = $time['year'] . '-' . $time['mon'] . '-' . $future;
					$nexttime = strtotime($nexttime);
					$nexttime = date('Y-m-d H:i:s',$nexttime);
					$redirect_link = get_option($this->wp_meetup->redirect_link);
					if (isset($redirect_link) && $redirect_link['redirect_link']) {
						$redirect_link_location = 'Meetup.com.';
					}
					else {
						$redirect_link_location = 'Wordpress Posts.';
					}
					$color_permission = get_option($this->wp_meetup->color_permission);
					if ($color_permission['color_permission'] === 'checked') {
						$link_color = 'White';
					}
					else {
						$link_color = 'Set by theme';
					}
					$include_events = get_option($this->wp_meetup->include_homepage);
					if (isset($include_events['include_homepage']) && $include_events['include_homepage'] === 'checked'){
						$include_events = "Yes";
					}
					else {
						$include_events = 'No';
					}
					$widget_options_name = $this->wp_meetup->widget_options;
					$widget_event_options = get_option($widget_options_name);
					$event_debug_string = get_option($this->wp_meetup->event_debug_string);
					$performance_options = get_option($this->wp_meetup->performance_option_name);
					if (!isset($performance_options['max_event']) && !isset($performance_options['past_months_queried']) && !isset($performance_options['future_months_queried'])) {
						$performance_options = array(
							'past_months_queried' => 1,
							'future_months_queried' => 3,
							'max_event' => 100,
							);
					}
					?>
					<div class="one-half">
					<h3>Option Settings</h3>
					<table>
						<tr>
							<td>Your link color is:</td>
							<td><?php echo $link_color ?></td>
						</tr>
						<tr>
							<td>Calendar Links go to:</td>
							<td><?php echo $redirect_link_location ?></td>
						</tr>
						<tr>
							<td>Include Events on homepage:</td>
							<td><?php echo $include_events ?></td>
						</tr>
						<tr>
							<td>Event List Widget:</td>
							<td><?php echo $widget_event_options['list_length'] ?> events shown</td>
						</tr>
					</table>
					<h3>Event Updating Information</h3>
					<table>
					<tr>
						<td>Events last updated at: </td>
						<td><?php echo $lastran; ?></td>
					</tr>
					<tr>
						<td>Current time is: </td>
						<td><?php echo $currenttime; ?></td>
					</tr>
					<tr>
						<td>Next update not until: </td>
						<td><?php echo $nexttime; ?></td>
					</tr>
					</table>
					<h3>Meetup Query Options</h3>
					<p><?php echo $event_debug_string['result'] ?></p>
					<table>
						<tr>
							<td>Past months queried: </td>
							<td><?php echo $performance_options['past_months_queried'] ?></td>
						</tr>
						<tr>
							<td>Future months queried: </td>
							<td><?php echo $performance_options['future_months_queried'] ?></td>
						</tr>
						<tr>
							<td>Max Events per group:</td>
							<td><?php echo $performance_options['max_event'] ?></td>
						</tr>
					</table>
				</div>
				</div>
				<div class="one-half">
				<?php
				foreach ($groups as $group) {
					$name = $group['name'];
					$id = $group['group_id'];
					$color = $colors['wpm_calendar_' . $name . '_color'];
					$output .= '<h3>' . $name . '</h3>';
					$output .= '<table>';

					if ($id !== NULL) {
						$output .= '<tr><td>Group ID</td><td>' . $id . '</td></tr>';
						$eventTable = $this->wp_meetup->sqltable;
						$eventCount = $wpdb->get_var("SELECT COUNT(*) FROM $eventTable WHERE `group_id` = $id");
						$output .= '<tr><td>Number of Events</td><td>' . $eventCount . '</td></tr>';
					}
					else {
						$output .= '<tr><td>Group ID</td><td>NULL</td></tr>';
						$output .= '<tr><td>Number of Events</td><td>Group is PRIVATE</td</tr>';
					}
					$output .= '<tr><td>Color</td><td style="background-color:' . $color . ';">' . $color . '</td></tr>';
					$output .= '</table>';
				}
				echo $output;
				?>
				</div>
			</div>
		</div>
		<style>td{border-bottom:1px solid #000;}</style>
		<?php

	}

	function replace_apikey() {

		?>
		<form method="post" action="">
			<div class="wpm-settings">
				<input type="hidden" name="submitted" value="apiKeySecrets" />
				<table>
					<tr class="wpm-register-settings-option">
						<td><label>Manual API key update: </label></td>
						<td><input name="apikey" type="text" autocomplete="on" /></td>
						<td><input type="submit" value="Update" /></td>
					</tr>
				</table>
			</div>
		</form>
		<?php
	}

	function emergency_update_apikey() {

		if (isset($_POST['submitted']) && $_POST['submitted'] == 'apiKeySecrets') {
			$_POST['submitted'] = 'not submitted';
			$settings = get_option($this->wp_meetup->options_name);
			$meetup_options = array(
				'apikey' => $_POST['apikey'],
				);
			update_option($this->wp_meetup->options_name, $meetup_options);
			$this->demand_update_cron();
		}
	}

	function get_group_id($urlname) {
		$wpmOptions = get_option($this->wp_meetup->options_name);
		$apikey = $wpmOptions['apikey'];
		$url = 'https://api.meetup.com/2/events.json?key=' . $apikey . '&page=1&group_urlname=' . $urlname . '&sign=true';
		$remote_get = wp_remote_get($url);
        $result = wp_remote_retrieve_body($remote_get);
        $result_array = json_decode($result);
	    $manyResult = $result_array->results;
	    if (isset($manyResult['0'])) {
	        $singleResult = $manyResult['0'];
	        $group = $singleResult->group;
	        $group_id = $group->id;
	        return $group_id;
	    }
    }

    function remove_group($removeName) {
    	global $wpdb;

    	$groups = get_option($this->wp_meetup->group_options_name);
		$colors = get_option($this->wp_meetup->color_options_name);
		$colors = $colors['colors'];
		
		$wpmgroups = array();
		foreach($groups as $group) {
			if ($removeName === $group['name']) {
				$id = $group['group_id'];
				$colorname = 'wpm_calendar_' . $removeName . '_color';
				$color = $colors['wpm_calendar_' . $removeName . '_color'];
				$eventTable = $this->wp_meetup->sqltable;
				$postTable = $wpdb->prefix .'posts';
				//This is where things begin getting removed.
				if ($id != null){
					$events = $wpdb->get_results("SELECT `id` FROM $eventTable WHERE `group_id` = $id");
				}
				if (isset($events) and $events !== NULL) {

					foreach($events as $event_class) {
						$event_id = $event_class->id;
						$wp_post_id = $wpdb->get_results("SELECT `wp_post_id` FROM $eventTable WHERE `id` = $event_id");
						$wp_post_id = $wp_post_id[0]->wp_post_id;
						if (isset($event_id)) {
							$wpdb->delete( $eventTable, array( 'id' => $event_id ));
						}
						if (isset($wp_post_id)) {
							wp_delete_post($wp_post_id, TRUE);
						}
					}
				}
			}
			else {
				$wpmgroups[] = $group;
			}
		}
		$colors = array(
			'colors' => $colors,
			);
		update_option($this->wp_meetup->color_options_name, $colors);
		update_option($this->wp_meetup->group_options_name, $wpmgroups);
    }

    function load_settings_styles() {
		$pluginDirectory = trailingslashit(plugins_url(basename(dirname(__FILE__))));
		wp_register_style('wpm-settings-styles', $pluginDirectory . 'css/wp-meetup.css');
		wp_enqueue_style('wpm-settings-styles');
	}

	function create_events_submenu() {
		$event_list = $this->get_all_events();
		$total_list = array();
		foreach ($event_list as $event) {
			$event_data = $this->get_event_time_and_ids($event->ID);
			//dump($event_data);
			$event_data = $event_data['0'];
			$event_time = date('Y-m-d H:i:s',$event_data->event_time);
			$total_list[] = array(
				'wp_id' => $event->ID,
				'post_title' => $event->post_title,
				'guid' => $event->guid,
				'event_time' => $event_time,
				'group_id' => $event_data->group_id,
				'wp_meetup_id' => $event_data->id,
				);
		}
		$output = $this->display_event_table($total_list);
		echo $output;
		
	}

	function display_event_table($event_list) {

		$group_list = get_option('wp_meetup_groups');
		$colorlist = get_option($this->wp_meetup->color_options_name);
		$colorlist = $colorlist['colors'];
		?>
			<table>
				<tr>
					<th></th>
					<th>Event</th>
					<th class="padding">Event Time</th>
					<th class="padding">Group</th>
					<th class="padding">WP Post ID</th>
					<th class="padding">WP-Meetup ID</th>
				</tr>
				<?php
					foreach ($event_list as $event) {
						
						$output = '<tr>' . PHP_EOL;
						$output .= '<td><div class="group' . $event['group_id'] . '">  </div></td>';
						$output .= '<td class="title"><a href="' . $event['guid'] . '">' . $event['post_title'] . '</a></td>' . PHP_EOL;
						$output .= '<td class="padding">' . $event['event_time'] . '</td>' . PHP_EOL;
						foreach ($group_list as $group) {
							if ($group['group_id'] == $event['group_id']) {
								$output .= '<td class="padding">' . $group['name'] . '</td>' . PHP_EOL;
							}
						}
						$output .= '<td class="padding">' . $event['wp_id'] . '</td>' . PHP_EOL;
						$output .= '<td class="padding">' . $event['wp_meetup_id'] . '</td>' . PHP_EOL;
						$output .= '</tr>' . PHP_EOL;
						echo $output;
					}
				?>
			</table>
			<style>
				td{padding:5px;} th{padding:5px;text-align:left;}.id{text-align: center;}.title{width:180px;}.title a{color:#114477;}.title a:hover{color:#4477BB;}.padding{padding:5px 25px;}
				<?php 
				if (isset($group_list) and $group_list!=NULL) {
					foreach ($group_list as $single_group) {
						$color_input = 'wpm_calendar_' . $single_group['name'] . '_color';
						if (isset($colorlist[$color_input])) {
							$rubik = '.group' . $single_group['group_id'] . '{ background-color:' . $colorlist[$color_input] . ';width:5px;height:5px;}' . PHP_EOL;

						}
						echo $rubik;
					}
				}
				?>
			</style>
		<?php
	}

	function get_event_time_and_ids($event_id) {
		global $wpdb;

		$sqltable = $this->wp_meetup->sqltable;
		$event_data = $wpdb->get_results("SELECT `id`,`event_time`,`group_id` FROM $sqltable WHERE `wp_post_id` = '$event_id'");
		return $event_data;
	}

	function get_all_events() {
		global $wpdb;

		$sqltable_posts = $this->wp_meetup->sqltable_posts;
		$post_type = $this->wp_meetup->custom_post_type;
		$event_array = $wpdb->get_results("SELECT `ID`, `post_title`,`guid` FROM $sqltable_posts WHERE `post_type` = '$post_type'");
		return $event_array;
	}

	function create_options_submenu() {
		global $wpdb, $nmcron;
		if (!current_user_can('manage_options')) {
			wp_die( __('You do not have sufficient permissions to access this page.') );
		}
		$this->update_all_options();
		if ($this->wp_meetup->is_registered()) {
			?>
				<div class="wp-meetup-options-page">
					<h1>Options</h1>
					<div class="one-third meetup-options">
						<h2>General</h2>
						<?php 
						$this->insert_link_color_checkbox(); 
						$this->insert_link_redirect_option();
						$this->insert_include_homepage_option();
						?>
					</div>
					<div class="one-third meetup-options">
						<h2>Widget Options</h2>
						<?php 
						$this->insert_widget_options();
						?>
					</div>
					<div class="one-third meetup-options developer-options">
						<h2>Meetup.com Query Options </h2>
						<?php
						$this->insert_performance_options();
						?>
					</div>
				</div>
			<?php
		}
		else {
			$this->request_apikey();
		}
	}

	function insert_performance_options() {
		$this->update_all_options();
		$performance_options = get_option($this->wp_meetup->performance_option_name);
		if (!isset($performance_options['max_event']) && !isset($performance_options['past_months_queried']) && !isset($performance_options['future_months_queried'])) {
			$performance_options = array(
				'past_months_queried' => 1,
				'future_months_queried' => 3,
				'max_event' => 100,
				);
		}
		?>
		<form method="post" action="">
			<h3>Query Months</h3>
			<table>
				<tbody>
					<tr>
						<td>
							<label>Number of past months queried: </label>
						</td>
						<td>
							<input type="number" name="past_months_queried" value="<?php echo $performance_options['past_months_queried'] ?>" class="limited-width">
						</td>
					</tr>
					<tr>
						<td>
							<label>Number of future months queried: </label>
						</td>
						<td>
							<input type="number" name="future_months_queried" value="<?php echo $performance_options['future_months_queried'] ?>" class="limited-width">
						</td>
					</tr>
					<tr>
						<td>
							<label>Max number of Events pulled per group:</label>
						</td>
						<td>
							<input type="number" name="max_event" value="<?php echo $performance_options['max_event'] ?>" class="limited-width">
						</td>
					</tr>
					<tr>
						<td>
							<input type="hidden" name="perform_secret" value="update_performance">
						</td>
						<td>
							<input type="submit" value="Update Options">
						</td>
					</tr>
				</tbody>
			</table>
		</form>
		<?php
	}

	function insert_link_redirect_option() {
		$option = $this->wp_meetup->redirect_link;
		$permission = get_option($option);
		$redirect_link_val = ''; 
		if ($permission['redirect_link'] == 'checked') { 
			$redirect_link_val = 'checked'; 
		} 
		?>
		<div>
			<h3>Link Redirect</h3>
				<form method="post" action="">
				<input type="hidden" name="update_redirect_link" value="redirect link" />
				<input type="checkbox" name="redirect_link" value="checked" <?php echo $redirect_link_val; ?> />
				<label>Checking this box will make all links within the calendar and widgets direct users to the Meetup.com event page.</label><br />
				<input type="submit" value="Update Redirect Link Option" />
			</form>
		</div>
		<?php
	}

	function insert_include_homepage_option() {
		$option = $this->wp_meetup->include_homepage;
		$permission = get_option($option);
		$incl_homepage_val = ''; 
		if ($permission['include_homepage'] == 'checked') { 
			$incl_homepage_val = 'checked'; 
		} 
		?>
		<div>
			<h3>Include on Homepage</h3>
				<form method="post" action="">
				<input type="hidden" name="update_incl_homepage" value="include homepage" />
				<input type="checkbox" name="include_homepage" value="checked" <?php echo $incl_homepage_val; ?> />
				<label>Would you like Events to appear on your homepage?</label><br />
				<input type="submit" value="Update Homepage Option" />
			</form>
		</div>
		<?php
	}

	function insert_widget_options() {

		$options_name = $this->wp_meetup->widget_options;
		$options = get_option($options_name);
		if (!isset($options['list_length'])) {
			$options['list_length'] = '3';
		}
		if (!isset($options['list_title'])) {
			$options['list_title'] = 'Event List';
		}
		?>
		<div>
			<form method="post" action="">
			<h3>Event List</h3>
				<table>
					<tbody>
						<tr>
							<td>
								<label>Event list title:</label>
							</td>
							<td>
								<input type="text" name="list_title" value="<?php echo $options['list_title'] ?>">
							</td>
						</tr>
						<tr>
							<td>
								<label>The Event List widget should display how many events?</label>
							</td>
							<td>
								<input type="number" name="list_length" value="<?php echo $options['list_length'] ?>" class="limited-width">
							</td>
						</tr>
					</tbody>
				</table>
				<input type="hidden" value="widget_options">
				<input type="submit" name="widget_options" value="Update Widget">
			</form>
		</div>
		<?php
	}
}