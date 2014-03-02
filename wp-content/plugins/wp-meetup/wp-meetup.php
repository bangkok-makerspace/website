<?php

/*
Plugin Name: WP Meetup
Plugin URI: http://nuancedmedia.com/wordpress-meetup-plugin/
Description: Pulls events from Meetup.com onto your blog
Version: 2.1.11
Author: Nuanced Media
Author URI: http://nuancedmedia.com/

Copyright 2013  Nuanced Media

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/* ----------  Dump function for debug ----------  */
if (!function_exists('dump')) {function dump ($var, $label = 'Dump', $echo = TRUE){ob_start();var_dump($var);$output = ob_get_clean();$output = preg_replace("/\]\=\>\n(\s+)/m", "] => ", $output);$output = '<pre style="background: #FFFEEF; color: #000; border: 1px dotted #000; padding: 10px; margin: 10px 0; text-align: left;">' . $label . ' => ' . $output . '</pre>';if ($echo == TRUE) {echo $output;}else {return $output;}}}if (!function_exists('dump_exit')) {function dump_exit($var, $label = 'Dump', $echo = TRUE) {dump ($var, $label, $echo);exit;}}




/* ----------  WP Meetup ----------  */
include 'nm-cron.php';
include 'wp-meetup-admin.php';
global $WP_Meetup;
$nm_cron = new NM_Cron();
$wp_meetup = new WP_Meetup();
$wp_meetup_admin = new WP_Meetup_Admin($wp_meetup);

class WP_Meetup {

	var $sqltable            = 'meetup_events';
	var $sqltable_cron       = 'nm_cron';
	var $sqltable_posts      = 'posts';
	var $wpm_version_control = 'wp_meetup_version';
	var $options_name        = 'meetup_options';
	var $credit_permission   = 'wpm_credit_permission';
	var $color_permission    = 'wp_color_permission';
	var $redirect_link	     = 'wp_redirect_link';
	var $include_homepage    = 'wp_include_on_homepage';
	var $group_options_name  = 'wp_meetup_groups';
	var $color_options_name  = 'wp_meetup_colors';
	var $custom_post_type    = 'events';
	var $widget_options 	 = 'meetup-widget-options';

	var $event_debug_info    = array('events_grabbed' => 0, 'groups_queried' => 0, 'events_added' => 0, 'events_deleted' => 0);
	var $event_debug_string  = 'wp_meetup_event_debug';
	var $performance_option_name = 'wp_performance';

	function __construct() {
		global $wpdb;

		$this->sqltable      = $wpdb->prefix . $this->sqltable;
		$this->sqltable_cron = $wpdb->prefix . $this->sqltable_cron;
		$this->sqltable_posts = $wpdb->prefix . $this->sqltable_posts;
		$version             = array( 'version' => '2.1.11' );
		$currentVersion = get_option($this->wpm_version_control);
		update_option($this->wpm_version_control, $version);
		add_action('init', array(&$this, 'init'));
		add_filter('plugin_action_links_' . plugin_basename(__FILE__), array(&$this, 'add_plugin_settings_link') );
		$include_events = get_option($this->include_homepage);
		if (isset($include_events['include_homepage']) && $include_events['include_homepage'] === 'checked'){
			add_filter( 'pre_get_posts', array(&$this, 'include_events_in_loop') );
		}
		$currentVersion = $currentVersion['version'];
		if (isset($currentVersion)) {
			$this->version_check($currentVersion);
		}

		
	}

	function version_check($version) {

		require_once( ABSPATH . 'wp-admin/includes/plugin.php');
		$version_array = explode('.', $version);
		$plugin_data    = get_plugin_data( __FILE__ );
		$current_version = $plugin_data['Version'];
		$current_version_array = explode('.', $current_version);
		$version_number = '';
		foreach ($version_array as $element) {
			if (strlen($element) == '1') {
				$version_number .= '0' . $element;
			}
			else {
				$version_number .= $element;
			}
		}
		$current_version_number = '';
		foreach ($current_version_array as $element) {
			if (strlen($element) == '1') {
				$current_version_number .= '0' . $element;
			}
			else {
				$current_version_number .= $element;
			}
		}
		$apikey = get_option($this->options_name);
		if (!isset($apikey['apikey'])) {
			$this->back_capat();
		}
		elseif ($version_number < '020103') {
			$this->maybe_update_event_posts();
		}
		elseif ($version_number < '020106') {
			$newdata = array(
				'past_months_queried' => 1,
				'future_months_queried' => 3,
				'max_event' => 100,
				);
			update_option($this->performance_option_name, $newdata);
		}
	}

	function init() {
		global $wpdb, $nmcron;

		$tableSearch = $wpdb->get_var("SHOW TABLES LIKE '$this->sqltable'");
		wp_register_sidebar_widget( 'wp_meetup_calendar_widget-__i__', 'WP Meetup Calendar Widget', array(&$this, 'wpm_calendar_widget'), array('description' => 'Displays Meetup.com events in the current month on a calendar'));
		wp_register_widget_control(
			'wp_meetup_event_widget-__i__',
			'wp_meetup_event_widget-__i__',	
			array(&$this,'event_list_widget_control')
		);
		wp_register_sidebar_widget( 'wp_meetup_event_widget-__i__', 'WP Meetup Events Widget', array(&$this, 'wpm_events_widget'), array('description' => 'Displays Meetup.com events in a list format'));

		if ($tableSearch != $this->sqltable) {
			$this->update_database();
		}

		

		if (!$this->is_registered()) {
			/* This is executed if the access token isn't already set. */
			add_shortcode('meetup-calendar', array(&$this, 'no_apikey'));
			add_shortcode('wp-meetup-calendar', array(&$this, 'no_apikey'));
			add_filter('widget_text', 'do_shortcode');
		}
		else {
			/* Executed if the access token is set. */
			$this->create_event_post_type();
			add_shortcode('meetup-calendar', array(&$this, 'shortcode_execute'));
			add_shortcode('wp-meetup-calendar', array(&$this, 'shortcode_execute'));
			add_filter('widget_text', 'do_shortcode');
			add_action('wp_enqueue_scripts', array(&$this, 'load_styles'), 100);
			$this->maybe_update_event_posts();
		}
	}

	function maybe_update_event_posts($forceRun = FALSE) {
		global $wpdb;
		/* This uses nmcron.php which will return a 1 if it is time to query or a 0 if it is not time to query.  */
		if (!$forceRun) {
			$run = $wpdb->get_var( "SELECT `run` FROM $this->sqltable_cron WHERE `id`=1" );
		} else {
			$run = $forceRun;
			//$run = TRUE;
		}
		$id_array = array();
		if ($run) {
			$event_array_2 = $this->multigroup_events();
			foreach($event_array_2 as $result_class) {
				if($result_class) {
					$result_array_2 = $result_class->results;
					foreach($result_array_2 as $event) {
						$this->add_event_post($event);
						if ($event->id) {
							$id_array[] = $wpdb->get_var("SELECT `wp_post_id` FROM $this->sqltable WHERE `wpm_event_id` = '$event->id'");
						}
					}
				}
			}
			$this->clean_old_events($id_array);
			// update the debug info for what just happened
			$this->update_event_debug_info();
		}
	}

	function update_event_debug_info() {
		$result_string = '';
		$result_string .= $this->event_debug_info['events_grabbed'] . ' events were found on Meetup.com';
		$result_string .= ' across ' . $this->event_debug_info['groups_queried'] . ' groups. ';
		$result_string .= '<br/>';
		$result_string .= $this->event_debug_info['events_added'] . ' new events were found and added to WordPress.';
		update_option($this->event_debug_string, array('result' => $result_string));
	}

	function update_database() {
		/*  Build the Meetup database if and only if it does not already exist */
		global $wpdb;
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		$sql = "CREATE TABLE $this->sqltable (
				 id int(11) NOT NULL AUTO_INCREMENT,
				 wp_post_id INT(11) NOT NULL,
				 wpm_event_id varchar(200) NOT NULL,
				 event_time text NOT NULL,
				 event_url text NOT NULL,
				 group_id text NOT NULL,
				PRIMARY KEY (id)
				)
				CHARACTER SET utf8
				COLLATE utf8_general_ci;";
		dbDelta($sql);
	}

	function is_registered() {
		/* Checks to see is an access token exists in the options database. */
		$options = get_option($this->options_name);
		return ($options['apikey'] != NULL);
	}

	function add_plugin_settings_link($links) {
		$settings_link = '<a href="admin.php?page=wp_meetup_settings">Settings</a>';
		array_unshift($links, $settings_link);
		return $links;
	}

	function load_styles() {
		$pluginDirectory = trailingslashit(plugins_url(basename(dirname(__FILE__))));
		wp_register_style('wpm-styles', $pluginDirectory . 'css/wp-meetup.css');
		wp_enqueue_style('wpm-styles');
	}

	function wpm_deactivate() { 
		flush_rewrite_rules();
	}

	function get_today() {
		$localtime = current_time('mysql');
		$strtotime = strtotime($localtime);
		$today = getdate($strtotime);
		return $today;
	}

	function print_credit() {
		$permission = get_option($this->credit_permission);
		$output = "";
		if ($permission['permission_value']==='checked') {
			$output = '<div class="credit-line">Supported By: <a href="http://www.nuancedmedia.com">';
			$output .= '<img alt="Nuanced Media" src="' . plugins_url() . '/wp-meetup/images/nuanced_media.png">';
			$output .= '</a></div>' . PHP_EOL;
		}
		return $output;
	}

	function build_meetup_backlink($event) {
		$event_link = $event->event_url;
		$event_date = date('l, d M Y g:i',$event->time);
		$event_suffix = date('H',$event->time);
		if ($event_suffix >= 12) {
			$event_suffix = ' PM';
		}
		else {
			$event_suffix = ' AM';
		}
		$output = '';
		$output .= '<div class="meetup-backlink">' . PHP_EOL;
		$output .= '<div class="button-wrapper">' . PHP_EOL;
		$output .= '<a href="' . $event_link  . '" class="button">View event on Meetup.com</a>' . PHP_EOL;
		$output .= '</div>' . PHP_EOL;
		$output .= '<div class="date-wrapper">' . PHP_EOL;
		$output .= '<h3>Date</h3>' . PHP_EOL;
		$output .= '<p>' . $event_date . $event_suffix . '</p>' . PHP_EOL;
		$output .= '</div>' . PHP_EOL;
		if (isset($event->venue)) {
			$event_venue = $event->venue;
			$event_location_name = '<strong>' . $event->venue->name . '</strong><br />';
			$event_location = $event_location_name;
			if (isset($event->venue->address_1) && isset($event->venue->city) && isset($event->venue->state) && isset($event->venue->zip)) {
				$event_location_address = $event->venue->address_1 . '<br />' . $event->venue->city . ', ' . $event->venue->state . ' ' . $event->venue->zip;
				$event_location .= $event_location_address;
			}
			$event_map = '<a href="https://maps.google.com/?q=' . $event->venue->lat . ',' . $event->venue->lon . '">View Map</a>';
			//$event_location = $event_location_name . $event_location_address;
			$output .= '<div class="meetup-backlink-venue">' . PHP_EOL;
			$output .= '<h3>Venue</h3>' . PHP_EOL;
			$output .= '<p>' . $event_location . '</p>' . PHP_EOL;
			$output .= '<div class="meetup-backlink-map">' . $event_map . '</div>' . PHP_EOL;
			$output .= '</div>' . PHP_EOL;
			
		}
		$output .= '</div>' . PHP_EOL;
		return $output;
	}

	function include_events_in_loop($query) {
		if ( is_home() && $query->is_main_query() ) {
			$query->set( 'post_type', array( 'post', 'events') );
		}
	return $query;
	}

	/* ----------  Shortcodes ---------- */

	function no_apikey() {
		/* Echo message iif apikey does not exist. Called by init if is_registered returns false. */
		if(current_user_can("administrator")) {
			echo '<div class="no-api-message">Please register your settings on the WP Meetup Settings page.</div>';
		}
	}

	function shortcode_execute($atts) {
		global $wpdb, $post;

		$output = '';
		extract(shortcode_atts(array(
			'past'             => '0',
			'future'           => '0',
			'number_of_months' => '1',
			'start_month'      => '0',
			'end_month'        => '0',
		), $atts));
		$past = $past - $start_month;
		$future = $future + $number_of_months + $end_month -1;
		$today = $this->get_today();
		$display_legend = TRUE;
		if ($past >= '0') {
			$past = intval($past);
			while ($past > 0) {
				if ($today['mon']-$past < 1) {
					$display_month = array(
						'mday'  => NULL,
						'mon'   => $today['mon'] - $past + 12,
						'year'  => $today['year']-1,
						'month' => date('F', mktime(0, 0, 0, $today['mon']-$past +13, 0, $today['year']-1)),
					);
				}
				else{
					$display_month = array(
						'mday'  => NULL,
						'mon'   => $today['mon'] - $past,
						'year'  => $today['year'],
						'month' => date('F', mktime(0, 0, 0, $today['mon']-$past+1, 0, $today['year'])),
					);
				}
				$past = $past - 1;
				$output .= $this->shortcode_calendar($display_month, $display_legend);
				$display_legend = FALSE;
			}
		}
		$output .= $this->shortcode_calendar($today, $display_legend);
		$display_legend = FALSE;
		if ($future >= '0') {
			$future = intval($future);
			$mon = 1;
			while ($mon <= $future){
				if ($today['mon']+$mon > 12) {
					$display_month = array(
						'mday'  => NULL,
						'mon'   => $today['mon'] + $mon - 12,
						'year'  => $today['year']+1,
						'month' => date('F', mktime(0, 0, 0, $today['mon']+$mon-11, 0, $today['year']+1)),
					);
				}
				else {
					$display_month = array(
							'mday'  => NULL,
							'mon'   => $today['mon'] + $mon,
							'year'  => $today['year'],
							'month' => date('F', mktime(0, 0, 0, $today['mon']+$mon+1, 0, $today['year'])),
						);
				}
				$mon = $mon + 1;
				$output .= $this->shortcode_calendar($display_month, $display_legend);
				$display_legend = FALSE;
			}
		}
		$output .= $this->print_credit();
		return $output;
	}

	/* ----------  Calendar Functions ---------- */

	function shortcode_calendar($today, $display_legend = FALSE) {
		/* Builds Calendar --- Links events to dates */ 

		// Calendar Legend
		$output = "";
		$grouplist = get_option($this->group_options_name);
		$colorlist = get_option($this->color_options_name);
		$style = get_option($this->options_name);
		if ($display_legend && isset($grouplist) and $grouplist != NULL && count($grouplist) > 1) {
			$legend_count = 0;
			$output .= '<div class="wpm-calendar-legend">' . PHP_EOL;
			$output .= '<div class="wpm-legend-item">Groups:</div><div class="clear"></div>' . PHP_EOL;
			foreach ($grouplist as $group) {
				$color_input = 'wpm_calendar_' . $group['name'] . '_color';
				if (isset($colorlist[$color_input])) {
					$output .= '<div class="wpm-legend-item group' . $group['group_id'] . '">' . $group['name'] .'</div>' . PHP_EOL;

				}
				else {
					$colorlist[$color_input] = '#CCCCCC';
					$output .= '<div class="wpm-legend-item group' . $group['group_id'] . '">' . $group['name'] .'</div>' . PHP_EOL;

				}
				$legend_count++;
				if ($legend_count == 3) {
					$output .= '<div class="clear"></div>';
					$legend_count = 0;
				}
			}
			$output .= '</div><div class="clear"></div>' . PHP_EOL;
		}

		// Calendar display
		$output .= '<div class="wp-meetup-calendar">';
		$month = array();
		$week = array();
		$firstWeekdayOfMonth = date('w', mktime(0, 0, 0, $today['mon'], 0, $today['year']));
		$daysInMonth = date('d', mktime(0, 0, 0, $today['mon']+1, 0, $today['year']));
		if ($firstWeekdayOfMonth != 6) {
			for ($i=0; $i<$firstWeekdayOfMonth+1; $i++) {
				$day = array(
					'content'   => '<div class="no-date"></div>',
					'has_event' => FALSE,
					'today'     => FALSE,
				);
				$week[] = $day;
			}
		}
		for ($i=1; $i<=$daysInMonth; $i++) {
			$day = $i;
			$day = $this->dateMatching($day, $today);
			$week[] = $day;
			if (count($week)>=7) {
				$month[] =$week;
				$week    = array();
			}
		}
		if ($week != array()) {
			$month[] = $week;
		}
		$output .= '<h4 class="wpm-current-date-display">' . $today['month'] . ' ' . $today['year'] . '</h4>';
		$output .= '<table class="table calendar-month heading-date">';
		$output .= '<thead>';
		$output .= '<tr>';
		$output .= '<th class="calendar-headings wpm-header-label">Sun</th>';
		$output .= '<th class="calendar-headings wpm-header-label">Mon</th>';
		$output .= '<th class="calendar-headings wpm-header-label">Tue</th>';
		$output .= '<th class="calendar-headings wpm-header-label">Wed</th>';
		$output .= '<th class="calendar-headings wpm-header-label">Thu</th>';
		$output .= '<th class="calendar-headings wpm-header-label">Fri</th>';
		$output .= '<th class="calendar-headings wpm-header-label">Sat</th>';
		$output .= '</tr>';
		$output .= '</thead>';
		foreach ($month as $week) {
			$output .= '<tr class="calendar-week">';
			foreach ($week as $day) {
				if (isset($day['has_event']) and isset($day['content']) and isset($day['today'])) {
					if (($day['has_event'])==TRUE ) {
						if ($day['today'] === TRUE) {
							$output .= '<td class="wpm-table-data wpm-day wpm-has-event wpm-current-day-date ">';
							$output .= '<div class="wpm-calendar-entry wpm-date">' . ($day['content']) . '</div>';
							$output .= '</td>';
						}
						else {
							$output .= '<td class="wpm-table-data wpm-day wpm-has-event">';
							$output .= '<div class="wpm-calendar-entry wpm-date">' . ($day['content']) . '</div>';
							$output .= '</td>';
						}
					}
					else {
						if ($day['today'] === TRUE) {
							$output .= '<td class="wpm-table-data wpm-day wpm-no-event wp2-current-day-date ">';
							$output .= '<div class="wpm-calendar-entry">' . ($day['content']) . '</div>';
							$output .= '</td>';
						}
						else {
							$output .= '<td class="wpm-table-data wpm-day wpm-no-event">';
							$output .= '<div class="wpm-calendar-entry">' . ($day['content']) . '</div>';
							$output .= '</td>';
						}
					}
				}
			}
			$output .= '</tr>';
		}
		$output .= '</table></div>';
		$grouplist = get_option($this->group_options_name);
		$colorlist = get_option($this->color_options_name);
		$style = get_option($this->options_name);
		$output .= '<style>';
		$color_permission = get_option($this->color_permission);
		if ($color_permission['color_permission'] === 'checked') {
			$output .= '.wp-meetup-calendar .calendar-month .calendar-week .wpm-has-event a{color:#ffffff;}.wpm-calendar-legend .wpm-legend-item {color:#ffffff;}' . PHP_EOL;
		}
		/*
		 * Possibly going to implement this in a future version
		 * $output .= '.wp-meetup-calendar .wpm-current-date-display { color: ' . $style['wpm_calendar_font_color'] . '; }' . PHP_EOL;
		 * $output .= '.wp-meetup-calendar .wpm-day { color: ' . $style['wpm_calendar_font_color'] . ';' . 'background-color: ' . $style['wpm_calendar_background_color'] . '; border-bottom:  *solid 1px ' . $style['wpm_calendar_border_color'] . '; }' . PHP_EOL;
		 * $output .= '.wp-meetup-calendar th { color: ' . $style['wpm_calendar_font_color'] . '; background-color: ' . $style['wpm_calendar_background_color'] . '; border-bottom: solid 1px '  *. $style['wpm_calendar_border_color'] . '; }' . PHP_EOL;
		 * $output .= '.wp-meetup-calendar .calendar-month .calendar-week .wpm-has-event { background-color: ' . $style['wpm_calendar_has_event_background_color'] . ';}' . PHP_EOL;
		 * $output .= '.wpm-header-label{ color:' . $style['wpm_calendar_font_color'] . ';}' . PHP_EOL;
		 */
		$output .= '.wp-meetup-calendar { width:100%;}' . PHP_EOL;
		$output .= '.credit-line p{ font-size:12px; }' . PHP_EOL;
		$output .= '.wp-day-event-list{disply:none;}' . PHP_EOL;
		$colorlist = $colorlist['colors'];
		if (isset($grouplist) and $grouplist!=NULL) {
			foreach ($grouplist as $group) {
				$color_input = 'wpm_calendar_' . $group['name'] . '_color';
				if (isset($colorlist[$color_input])) {
					$output .= '.group' . $group['group_id'] . '{ background-color:' . $colorlist[$color_input] . ';}' . PHP_EOL;
				}
				else {
					$colorlist[$color_input] = '#CCCCCC';
					$output .= '.group' . $group['group_id'] . '{ background-color:' . $colorlist[$color_input] . ';}' . PHP_EOL;

				}
			}
		}
		$output .= '</style>' . PHP_EOL;
		return $output;
	}

	function dateMatching($day, $today) {
		/* Matches the dates on the calendar with the links to the event pages for that date. This is done by checks if event start time is within the timeframe of today. */ 
		
		global $wpdb;

		if ($today['mon'] < 10) {
			$thisday  = $today['year'] . '-0' . $today['mon'] . '-' . $day . ' 00:00:00';
			$tomorrow = $today['year'] . '-0' . $today['mon'] . '-' . $day . ' 23:59:59';
		} else {
			$thisday  = $today['year'] . '-' . $today['mon'] . '-' . $day . ' 00:00:00';
			$tomorrow = $today['year'] . '-' . $today['mon'] . '-' . $day . ' 23:59:59';
		}

		$date = $day;
		if ($today['mday'] == $date) {
			$day = array(
				'content'   => '<div class="wpm-number-display">' . $date . '</div>'. '<div class="wpm-event-list">',
				'has_event' => FALSE,
				'today'     => TRUE,
			);
		} else {
			$day = array(
				'content'   => '<div class="wpm-number-display">' . $date . '</div>'. '<div class="wpm-event-list">',
				'has_event' => FALSE,
				'today'     => FALSE,
			);
		}
		$thisday = strtotime($thisday);
		$tomorrow = strtotime($tomorrow);
		$wp_post_id_array = $wpdb->get_results("SELECT `wp_post_id` FROM $this->sqltable WHERE `event_time`>'$thisday' AND `event_time`<'$tomorrow'");
		if ($wp_post_id_array != NULL) {
			foreach($wp_post_id_array as $wp_post_id) {
				$wp_post_id = $wp_post_id->wp_post_id;

				$content = $this->day_build($date, $day, $today, $wp_post_id);
				$day = array(
					'content'   => $day['content'] . $content,
					'has_event' => TRUE,
					'today'     => $day['today'],
				);
			}
		}
		$day = array(
			'content'   => $day['content'] . '</div>',
			'has_event' => $day['has_event'],
			'today'     => $day['today'],
		);
		return $day;
	}

	function day_build($date, $day, $today, $wp_post_id) {
		global $wpdb;
		$group_id = $wpdb->get_var("SELECT `group_id` FROM $this->sqltable WHERE `wp_post_id`= $wp_post_id");
		$title = $wpdb->get_var("SELECT `post_title` FROM $this->sqltable_posts WHERE `id`='$wp_post_id'");
		$redirect_link = get_option($this->redirect_link);
		if (isset($redirect_link) && $redirect_link['redirect_link']) {
			$link = $wpdb->get_var("SELECT `event_url` FROM $this->sqltable WHERE `wp_post_id`='$wp_post_id'");
		}
		else {
			$link = $wpdb->get_var("SELECT `guid` FROM $this->sqltable_posts WHERE `id`='$wp_post_id'");
		}
		$content = '<a href="' .$link. '"><div class="wpm-single group' . $group_id . '">' . $title . '</div></a>';
		return $content;
	}

	/* ----------  Widget Functions ---------- */

	function wpm_events_widget($args) {
    	extract($args);
    	$option_name = $this->widget_options;
    	$widget_options = get_option($option_name);
		echo $before_widget; 
		if ($widget_options && isset($widget_options['list_title'])) {
			echo $before_title;
			echo $widget_options['list_title'];
			echo $after_title;
		}
		echo $this->events_widget_evaluated();
		echo $after_widget;
	}

	function events_widget_evaluated() {
		global $wpdb, $post;
		$today = $this->get_today();
		$output = $this->events_widget_execute($today);
		return $output;
	}
	
	function event_list_widget_control($args=array(), $params=array()) {
		$option_name = $this->widget_options;
		if (isset($_POST['widget_options']) && $_POST['widget_options'] == 'Update Widget') {
			$options = array();
			$options['list_length'] = $_POST['list_length'];
			$options['list_title'] = $_POST['list_title'];
			update_option($option_name, $options);
			$_POST['widget_options'] = NULL; 
			
		}
		$set_list_length = get_option($option_name);
		if (!isset($set_list_length['list_length'])) {
			$set_list_length['list_length'] = '3';
		}
		if (!isset($set_list_length['list_title'])) {
			$set_list_length['list_title'] = 'Event List';
		}
		
		?>
		
		<label>Title:</label><br />
		<input type="text" name="list_title" value="<?php echo $set_list_length['list_title'] ?>"><br />
		<label>The Event List widget should display how many events?</label><br />
		<input type="number" name="list_length" value="<?php echo $set_list_length['list_length'] ?>">
		<input type="hidden" name="widget_options" value="Update Widget">
		<!-- <select name="list_length" value="<?php //echo $options['list_length'] ?>">
			<option value="3">3</option>
			<option value="4">4</option>
			<option value="5">5</option>
			<option value="6">6</option>
			<option value="7">7</option>
			<option value="8">8</option>
			<option value="9">9</option>
			<option value="10">10</option> 
		</select> -->

		

		<?php

	}

	function events_widget_execute($today) {
		$output = '<div class="meetup-widget-event-list">';
		$i = $today['mday'];
		$end = $today['mday'] + 7;
		$list_length = 10;
		$days_in_month = cal_days_in_month(CAL_GREGORIAN, $today['mon'], $today['year']);
		//$output .= '<div class="meetup-event-list-month"><h3>' . $today['month'] . '</h3></div>' ; 
		$event_list_array = array();
		$widget_options = get_option($this->widget_options);
		if ($widget_options && isset($widget_options['list_length'])) {
			$end = 100;
			$list_length = intval($widget_options['list_length']);
			if ($list_length == 0) {
				$list_length = 3;
			} else if (!$list_length || $list_length < 1) {
				$list_length = 1;
			}
			//$list_length = 500;
		}
		
		while ($i<=$end && count($event_list_array) < $list_length) {
			// Put day of month as 0'th element of day array

			$day = $i;
			if ($i > $days_in_month) {
				$i = $i - $days_in_month;
				$end = $end - $days_in_month;
				$today['mon'] =  $today['mon'] + 1;
				$end = $end - $days_in_month;
				$day = $day - $days_in_month;
				if ($today['mon'] > 12) {
					$today['mon'] = $today['mon'] - 12;
					$today['year'] = $today['year'] + 1;
				}
				$mktime = mktime(0, 0, 0, $today['mon'], $i, $today['year']);
				$next_month = date('F', $mktime);
				$days_in_month = cal_days_in_month(CAL_GREGORIAN, $today['mon'], $today['year']);
				$today['month'] = $next_month;
			}
			$day_array = $this->eventListDateMatching($day, $today);
			foreach ($day_array as $day) {
				if (!$day['has_event'] == false && count($event_list_array) < $list_length) {
					$event_list_array[] = array(
						'day' => $day['day'],
						'content' => $day['content'],
						);
					
				}
			}	
			// check and see if there's an event on this day.
			// add it to the day's array.
			// add day to week
			$week[] = $day;
			$i = $i+1;
		}
		foreach ($event_list_array as $element) {
			$output .= $element['day'];
			$output .= '<div class="widget-meetup-event-list-day">' . $element['content'] . '</div><div class="clear"></div>';
		}
		// Credit permission and styles
		$output .= $this->print_credit();
		$grouplist = get_option($this->group_options_name);
		$colorlist = get_option($this->color_options_name);
		$style = get_option($this->options_name);
		$output .= '<style>';
		$color_permission = get_option($this->color_permission);
		if ($color_permission['color_permission'] === 'checked') {
			$output .= '.meetup-widget-event-list .wpm-date-display {color:#ffffff;}' . PHP_EOL;
		}
		$output .= '.wp-meetup-widget-calendar { width:300px;}' . PHP_EOL;
		$colorlist = $colorlist['colors'];
		if (isset($grouplist) and $grouplist!=NULL) {
			foreach ($grouplist as $group) {
				$color_input = 'wpm_calendar_' . $group['name'] . '_color';
				if (isset($colorlist[$color_input])) {
					$output .= '.group' . $group['group_id'] . '{ background-color:' . $colorlist[$color_input] . ';}' . PHP_EOL;

				}
				else {
					$colorlist[$color_input] = '#CCCCCC';
					$output .= '.group' . $group['group_id'] . '{ background-color:' . $colorlist[$color_input] . ';}' . PHP_EOL;
				}
			}
		}
		$output .= '</style>' . PHP_EOL;

		$output .= '</div>';
		return $output;

	}

	function eventListDateMatching($day, $today) {
		/* Matches the dates on the calendar with the links to the event pages for that date. This is done by checks if event start time is within the timeframe of today. */ 
		
		global $wpdb;

		if ($today['mon'] < 10) {
			$thisday  = $today['year'] . '-0' . $today['mon'] . '-' . $day . ' 00:00:00';
			$tomorrow = $today['year'] . '-0' . $today['mon'] . '-' . $day . ' 23:59:59';
		} else {
			$thisday  = $today['year'] . '-' . $today['mon'] . '-' . $day . ' 00:00:00';
			$tomorrow = $today['year'] . '-' . $today['mon'] . '-' . $day . ' 23:59:59';
		}

		$date = $day;
		if ($today['mday'] == $date) {
			$day = array(
				'day' => '<div class="wpm-date-display">' . $today['month'] . '-' . $date . '</div>',
				'content'   => '<div class="wpm-event-list">',
				'has_event' => FALSE,
				'today'     => TRUE,
			);
		} else {
			$day = array(
				'day' => '<div class="wpm-date-display">' . $today['month'] . '-' . $date . '</div>',
				'content'   => '<div class="wpm-event-list">',
				'has_event' => FALSE,
				'today'     => FALSE,
			);
		}
		$thisday = strtotime($thisday);
		$tomorrow = strtotime($tomorrow);
		$wp_post_id_array = $wpdb->get_results("SELECT `wp_post_id` FROM $this->sqltable WHERE `event_time`>'$thisday' AND `event_time`<'$tomorrow'");
		$day_array = array();
		if ($wp_post_id_array != NULL) {
			foreach($wp_post_id_array as $wp_post_id) {
				$wp_post_id = $wp_post_id->wp_post_id;

				$content_array = $this->event_list_day_build($date, $day, $today, $wp_post_id);
				$month = substr($today['month'],0,3);
				$day = array(
					'day' => '<div class="wpm-date-display group' . $content_array['group_id'] . '">' . $month . '<br />' . $date . '</div>',
					'content'   => $content_array['content'],
					'has_event' => TRUE,
					'today'     => $day['today'],
				);
				$day_array[] = $day;
			}
		}
		$day = array(
			'day' => $day['day'],
			'content'   => $day['content'] . '</div>',
			'has_event' => $day['has_event'],
			'today'     => $day['today'],
		);
		return $day_array;
	}

	function event_list_day_build($date, $day, $today, $wp_post_id) {
		global $wpdb;
		$group_id = $wpdb->get_var("SELECT `group_id` FROM $this->sqltable WHERE `wp_post_id`= $wp_post_id");
		$title = $wpdb->get_var("SELECT `post_title` FROM $this->sqltable_posts WHERE `id`='$wp_post_id'");
		$redirect_link = get_option($this->redirect_link);
		if (isset($redirect_link) && $redirect_link['redirect_link']) {
			$link = $wpdb->get_var("SELECT `event_url` FROM $this->sqltable WHERE `wp_post_id`='$wp_post_id'");
		}
		else {
			$link = $wpdb->get_var("SELECT `guid` FROM $this->sqltable_posts WHERE `id`='$wp_post_id'");
		}
		$content = '<a href="' .$link. '"><div class="wpm-single">' . $title . '</div></a>';
		$content_array = array(
			'content' =>$content, 
			'group_id' => $group_id,
			);
		return $content_array;
	}

	function wpm_calendar_widget($args) {
    	extract($args);
		echo $before_widget; 
		echo $this->calendar_widget_evaluated();
		echo $after_widget;
	}

	function calendar_widget_evaluated() {
		global $wpdb, $post;
		$today= $this->get_today();
		$output = $this->widget_calendar($today);
		return $output;
	}

	function widget_calendar($today) {
		/*  Builds Calendar --- Links events to dates */ 
		$output = '<div class="wp-meetup widget-calendar">';
		$month = array();
		$week = array();
		$firstWeekdayOfMonth = date('w', mktime(0, 0, 0, $today['mon'], 0, $today['year']));
		$daysInMonth = date('d', mktime(0, 0, 0, $today['mon']+1, 0, $today['year']));
		if ($firstWeekdayOfMonth != 6) {
			for ($i=0; $i<$firstWeekdayOfMonth+1; $i++) {
				// empty days have no string as their day of month
				$day = array(
				'content' => '<div class="no-date"></div>',
				'has_event' => FALSE,
				'today'=> FALSE,
				);
				$week[] = $day;
			}
		}
		for ($i=1; $i<=$daysInMonth; $i++) {
			// Put day of month as 0'th element of day array
			$day = $i;
			$day = $this->widgetDateMatching($day, $today);
			// check and see if there's an event on this day.
			// add it to the day's array.
			// add day to week
			$week[] = $day;
			// If we have added 7 days to the week . . .
			if (count($week)>=7) {
			// . . . then add the week to the month, and reset.
				$month[]=$week;
				$week = array();
			}
		}
		// If we aren't quite spent, then add the week
		// to the month.
		if ($week != array()) {
			$month[] = $week;
		}
		// Now that we've built the month, let's print it out
		$output .= '<h4 class="wpm-current-date-display">' . $today['month'] . ' ' . $today['year'] . '</h4>';
		$output .= '<table class="table calendar-month heading-date">';
		$output .= '<thead>';
		$output .= '<tr>';
		$output .= '<th class="widget-calendar-headings wpm-header-label">Sun</th>';
		$output .= '<th class="widget-calendar-headings wpm-header-label">Mon</th>';
		$output .= '<th class="widget-calendar-headings wpm-header-label">Tue</th>';
		$output .= '<th class="widget-calendar-headings wpm-header-label">Wed</th>';
		$output .= '<th class="widget-calendar-headings wpm-header-label">Thu</th>';
		$output .= '<th class="widget-calendar-headings wpm-header-label">Fri</th>';
		$output .= '<th class="widget-calendar-headings wpm-header-label">Sat</th>';
		$output .= '</tr>';
		$output .= '</thead>';
		foreach ($month as $week) {
			$output .= '<tr class="calendar-week">';
			foreach ($week as $day) {
				if (isset($day['has_event']) and isset($day['content']) and isset($day['today'])) {
					if (($day['has_event'])==TRUE ) {
						if ($day['today'] === TRUE) {
							$output .= '<td class="wpm-table-data wpm-day wpm-has-event wpm-current-day-date ">';
							$output .= '<div class="wpm-calendar-entry wpm-date">' . ($day['content']) . '</div>';
							$output .= '</td>';
						}
						else {
							$output .= '<td class="wpm-table-data wpm-day wpm-has-event">';
							$output .= '<div class="wpm-calendar-entry wpm-date">' . ($day['content']) . '</div>';
							$output .= '</td>';
						}
					}
					else {
						if ($day['today'] === TRUE) {
							$output .= '<td class="wpm-table-data wpm-day wpm-no-event wpm-current-day-date ">';
							$output .= '<div class="wpm-calendar-entry">' . ($day['content']) . '</div>';
							$output .= '</td>';
						}
						else {
							$output .= '<td class="wpm-table-data wpm-day wpm-no-event">';
							$output .= '<div class="wpm-calendar-entry">' . ($day['content']) . '</div>';
							$output .= '</td>';
						}
					}
				}
			}
			$output .= '</tr>';
		}
		$output .= '</table>';
		$output .= $this->print_credit();
		$grouplist = get_option($this->group_options_name);
		$colorlist = get_option($this->color_options_name);
		$style = get_option($this->options_name);
		$output .= '<style>';
		$output .= '.wp-meetup-wdiget-calendar { width:300px;}' . PHP_EOL;
		$output .= '.credit-line p{ font-size:12px; }' . PHP_EOL;
		$colorlist = $colorlist['colors'];
		if (isset($grouplist) and $grouplist!=NULL) {
			foreach ($grouplist as $group) {
				$color_input = 'wpm_calendar_' . $group['name'] . '_color';
				if (isset($colorlist[$color_input])) {
					$output .= '.group' . $group['group_id'] . '{ background-color:' . $colorlist[$color_input] . ';}' . PHP_EOL;

				}
				else {
					$colorlist[$color_input] = '#CCCCCC';
					$output .= '.group' . $group['group_id'] . '{ background-color:' . $colorlist[$color_input] . ';}' . PHP_EOL;
				}
			}
		}
		$color_permission = get_option($this->color_permission);
		if ($color_permission['color_permission'] === 'checked') {
			$output .= '.WP_Meetup_wpm_calendar_widget .wpm-has-event a .wpm-single{color: #ffffff;}' . PHP_EOL;
		}
		$output .= '</style>' . PHP_EOL;
		$output .= '</div>'; //  end wp-meetup widget-calendar
		return $output;
	}

	function widgetDateMatching($day, $today) {
		/* Matches the dates on the calendar with the links to the event pages for that date. This is done by checks if event start time is within the timeframe of today. */ 
		global $wpdb;

		if ($today['mon'] < 10) {
			$thisday= $today['year'] . '-0' . $today['mon'] . '-' . $day . ' 00:00:00';
			$tomorrow= $today['year'] . '-0' . $today['mon'] . '-' . $day . ' 23:59:59';
		} else {
			$thisday= $today['year'] . '-' . $today['mon'] . '-' . $day . ' 00:00:00';
			$tomorrow= $today['year'] . '-' . $today['mon'] . '-' . $day . ' 23:59:59';
		}

		$date = $day;
		if ($today['mday'] == $date) {
			$day = array(
				'content'   => '',
				'has_event' => FALSE,
				'today'     => TRUE,
			);
		} else {
			$day = array(
				'content'   => '',
				'has_event' => FALSE,
				'today'     => FALSE,
			);
		}
		$thisday = strtotime($thisday);
		$tomorrow = strtotime($tomorrow);
		$wp_post_id = $wpdb->get_var("SELECT `wp_post_id` FROM $this->sqltable WHERE `event_time`>'$thisday' AND `event_time`<'$tomorrow'");
		if (isset($wp_post_id) && $wp_post_id != NULL) {
			$content = $this->widget_day_build($date, $day, $today, $wp_post_id);
			$day = array(
				'content'   => $content,
				'has_event' => TRUE,
				'today'     => $day['today'],
			);
			$day = array(
				'content'   => $content,
				'has_event' => $day['has_event'],
				'today'     => $day['today'],
			);
		} else {
			$day = array(
				'content'   => $date,
				'has_event' => $day['has_event'],
				'today'     => $day['today'],
			);
		}

		return $day;
	}

	function widget_day_build($date, $day, $today, $wp_post_id) {
		global $wpdb;
		//$link = post_permalink($wp_post_id);
		$group_id = $wpdb->get_var("SELECT `group_id` FROM $this->sqltable WHERE `wp_post_id`= $wp_post_id");
		$title = $wpdb->get_var("SELECT `post_title` FROM $this->sqltable_posts WHERE `id`='$wp_post_id'");
		$redirect_link = get_option($this->redirect_link);
		if (isset($redirect_link) && $redirect_link['redirect_link']) {
			$link = $wpdb->get_var("SELECT `event_url` FROM $this->sqltable WHERE `wp_post_id`='$wp_post_id'");
		}
		else {
			$link = $wpdb->get_var("SELECT `guid` FROM $this->sqltable_posts WHERE `id`='$wp_post_id'");
		}
		$content = '<a href="' .$link. '"><div class="wpm-single group' . $group_id . '">' . $date. '</div></a>';
		return $content;
	}


/* ---------- All Post Related Functions ---------- */
	function create_event_post_type() {
		/*  registers the event type post with WordPress  */
		register_post_type($this->custom_post_type,
			array(
			'labels' => array(
				'name' => __( 'WP Meetup Events' ),
				'singular_name' => __( 'WP Meetup Event' )
			),
			'public' => true,
			'has_archive' => true,
			'show_ui'   => false,
			//'capability_type'    => 'post',
            //'supports' => array('title', 'editor', 'thumbnail')
			)
		);
	}

	function add_event_post($event) {
		/*  This function creates events for all new posts and adds them to the WPM sql database. Also it updates all previously existing posts  */
		global $wpdb;
		$wpm_event_id_count = $wpdb->get_var("SELECT COUNT(*) FROM $this->sqltable WHERE `wpm_event_id`=\"$event->id\"");
		/* If the event does not already exist, add the new post -- else update existing post and existing database entries.*/
		$event->time = $event->time / 1000;
		if (gettype($event->time) == 'double') {
			$event->time = intval($event->time);
		}
		$event->time = $event->time + intval($event->utc_offset / 1000);
		$event->time = substr($event->time, 0, 10);
		$event->description = $this->build_meetup_backlink($event) . $event->description . $this->print_credit();
		if ($wpm_event_id_count != 1) {
			// we are adding a new event
			$this->event_debug_info['events_added'] += 1;
			$post_id = $this->create_event_post($event);
			$group = $event->group;
			$newdata = array(
				'wpm_event_id' => $event->id,
				'wp_post_id'   => $post_id,
				'event_time'   => $event->time,
				'event_url'    => $event->event_url,
				'group_id'     => $group->id
			);
			$wpdb->insert($this->sqltable, $newdata);
		} else {
			$wpm_database_id = $wpdb->get_var("SELECT `id` FROM $this->sqltable WHERE `wpm_event_id`=\"$event->id\"");
			$group = $event->group;
			$replace_data = array(
				'wpm_event_id' => $event->id,
				'event_time'   => $event->time,
				'event_url'    => $event->event_url,
				'group_id'     => $group->id
			);
			$wpdb->update($this->sqltable, $replace_data, array(
				'id' => $wpm_database_id
			));
			$this->update_existing_post($event);
		};
	}

	function create_event_post($event) {
		/* Creates new event posts and returns the WordPress post ID */
		global $wpdb;
		$post = array( 
			'post_type'    => $this->custom_post_type,
			'post_status'  => 'publish',
			'post_content' => $event->description,
			'post_title'   => $event->name,
			'start_time'   => $event->time,
		);
		$post_id = wp_insert_post($post, true);
		return $post_id;

	}

	function update_existing_post($event) {
		/*  Updates all existing posts */
		global $wpdb;
		$wp_post_id = $wpdb->get_var( "SELECT `wp_post_id` FROM $this->sqltable WHERE `wpm_event_id`='$event->id'" );
		$post_update = array(
			'ID'           => $wp_post_id,
			'post_type'    => $this->custom_post_type,
			'post_content' => $event->description,
			'post_title'   => $event->name,
			'start_time'   => $event->time,
		);
		$value = wp_update_post($post_update);
	}

	/* ----------  Get Events ---------- */


	function multigroup_events() {
		global $wpdb;
		$wpmOptions = get_option($this->options_name);
		if (!isset($wpmOptions['apikey'])) {
			return array();
		}
		$apikey = $wpmOptions['apikey'];
		$wpm_groups = get_option($this->group_options_name);
		$event_array = array();

		// reset debug info
		$this->event_debug_info = array('events_grabbed' => 0, 'groups_queried' => 0, 'events_added' => 0, 'events_deleted' => 0);

		$performance_options = get_option($this->performance_option_name);
		if (!isset($performance_options['max_event']) && !isset($performance_options['past_months_queried']) && !isset($performance_options['future_months_queried'])) {
			$performance_options = array(
				'past_months_queried' => 1,
				'future_months_queried' => 3,
				'max_event' => 100,
				);
		}

		foreach ($wpm_groups as $group){
			if (isset($group['name']) && isset($apikey)){
				$urlname = $group['name'];
				$url = 'https://api.meetup.com/2/events.json?key=' . $apikey . '&status=past,upcoming&time=-' . $performance_options['past_months_queried'] . 'm,' . $performance_options['future_months_queried'] . 'm&page=' . $performance_options['max_event'] . '&group_urlname=' . $urlname . '&sign=true';
				$remote_get = wp_remote_get($url);
		        $result = wp_remote_retrieve_body($remote_get);
		        $result_array = json_decode($result);
		        $event_array[] = $result_array;

		        $this->event_debug_info['events_grabbed'] += count($result_array->results);
		        $this->event_debug_info['groups_queried'] += 1;
		    }
	    }
		return $event_array;
	}
	function clean_old_events($id_array = NULL) {
		global $wpdb;
		$get_posts = $wpdb->get_results( "SELECT `ID` FROM $this->sqltable_posts WHERE `post_type` = '$this->custom_post_type'" );
		foreach ($get_posts as $id_class) {
			$id = $id_class->ID;
			if (!in_array($id, $id_array)) {
				wp_delete_post($id, TRUE);
				$wpdb->delete($this->sqltable, array('wp_post_id' => $id));
			}
		}
	}


	/* ----------  Backwards Capatability ----------  */

	function back_capat() {
		/* Check for existing API key */
		$prev_check = get_option('wp_meetup_options');
		if ($prev_check && isset($prev_check['api_key'])) {
			$oldAPIKEY = trim($prev_check['api_key']);
			$apikey = array(
				'apikey' => $oldAPIKEY,
			);
			update_option($this->options_name, $apikey);
			$permission = $prev_check['show_nm_link'];
			if ($permission == TRUE) {
				$permission = array(
					'permission_value' => 'checked',
				);
			} else {
				$permission = array(
					'permission_value' => $permission,
				);
			}
			update_option($this->credit_permission, $permission);
			$this->get_prev();
			//$this->maybe_update_event_posts(TRUE);

		}

	}

	function get_prev() {
		global $wpdb;
		/*  Pull groups and colors from previous version */
		$prev_groups_table = $wpdb->prefix . 'wpmeetup_groups';
		$prev_group_ids = $wpdb->get_results("SELECT `id` FROM $prev_groups_table");
		$wpmcolors = get_option($this->color_options_name);
		$colors_array = $wpmcolors['colors'];

		foreach ($prev_group_ids as $group_class){
			$group_id = $group_class->id;
			$group_name = $wpdb->get_var("SELECT `url_name` FROM $prev_groups_table WHERE `id` = $group_id");
			$wpmgroups = get_option($this->group_options_name);
			$new_group=array(
				'name' => $group_name,
				'group_id' => $group_id,
			);
			if (!in_array($new_group, $wpmgroups) && $new_group['name'] != NULL) {
				$wpmgroups[] = $new_group;
			}
			update_option($this->group_options_name, $wpmgroups);
			$group_color = $wpdb->get_var("SELECT `color` FROM $prev_groups_table WHERE `id` = $group_id");
			$name = 'wpm_calendar_' . $group_name . '_color';
			$colors_array[$name] = $group_color;

		}
		$wp_meetup_colors = array(
			'colors' => $colors_array,
		);
		update_option( $this->color_options_name, $wp_meetup_colors);
	}

}


