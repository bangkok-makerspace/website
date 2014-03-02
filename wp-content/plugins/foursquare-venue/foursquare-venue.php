<?php
/*
Plugin Name: Foursquare Venue
Plugin URI: http://sutherlandboswell.com/2010/11/foursquare-venue-wordpress-plugin/
Description: Display your venue's foursquare stats in an easy-to-use widget or with the <code>[venue id=3945]</code> shortcode.
Author: Sutherland Boswell
Author URI: http://sutherlandboswell.com
Version: 2.2.2
License: GPL2
*/
/*  Copyright 2010 Sutherland Boswell  (email : sutherland.boswell@gmail.com)

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

// Set Default Options

register_activation_hook(__FILE__,'foursquare_venue_activate');
register_deactivation_hook(__FILE__,'foursquare_venue_deactivate');

function foursquare_venue_activate() {
	add_option('foursquare_venue_client_id','');
	add_option('foursquare_venue_client_secret','');
	add_option('foursquare_venue_show_title','');
	add_option('foursquare_venue_stats_title','Foursquare Stats');
	add_option('foursquare_show_venue_name','');
	add_option('foursquare_show_venue_icon','');
	add_option('foursquare_show_here_now','1');
	add_option('foursquare_show_here_now_text','');
	add_option('foursquare_show_total','1');
	add_option('foursquare_show_total_text','');
	add_option('foursquare_show_mayor','1');
	add_option('foursquare_show_mayor_text','');
	add_option('foursquare_link_mayor','1');
	add_option('foursquare_show_mayor_photo','1');
	add_option('foursquare_mayor_photo_size','32');
	add_option('foursquare_venue_stats_width','');
	add_option('foursquare_venue_stats_align','');
}

function foursquare_venue_deactivate() {
	delete_option('foursquare_venue_client_id');
	delete_option('foursquare_venue_client_secret');
	delete_option('foursquare_venue_show_title');
	delete_option('foursquare_venue_stats_title');
	delete_option('foursquare_show_venue_name');
	delete_option('foursquare_show_venue_icon');
	delete_option('foursquare_show_here_now');
	delete_option('foursquare_show_here_now_text');
	delete_option('foursquare_show_total');
	delete_option('foursquare_show_total_text');
	delete_option('foursquare_show_mayor');
	delete_option('foursquare_show_mayor_text');
	delete_option('foursquare_link_mayor');
	delete_option('foursquare_show_mayor_photo');
	delete_option('foursquare_mayor_photo_size');
	delete_option('foursquare_venue_stats_width');
	delete_option('foursquare_venue_stats_align');
}

function getVenueInfo($id) {
	// Check for cached data
    $transient = "foursquare_venue_$id";
    $data = get_transient( $transient );
	// Fetch and cache data if needed
    if( false === $data )
    {
		$client_id = get_option('foursquare_venue_client_id');
		$client_secret = get_option('foursquare_venue_client_secret');
		$request = "https://api.foursquare.com/v2/venues/$id?client_id=$client_id&client_secret=$client_secret&v=20111113";
		$response = wp_remote_get( $request, array( 'sslverify' => false ) );
		if( is_wp_error( $response ) ) {
			$error_string = $response->get_error_message();
			echo '<div id="message" class="error"><p>' . $error_string . '</p></div>';
		} else {
			$data = json_decode($response['body']);
			set_transient( $transient, $data, 15 * 60 );
		}
	}
	// Return the data
    return $data;
}

function renderVenueInfo($venue) {
	$venueName = $venue->response->venue->name;
	$venueIcon = $venue->response->venue->categories[0]->icon;
	$venueURL = $venue->response->venue->shortUrl;
	
	$stats_title = get_option('foursquare_venue_stats_title');

	if(get_option('foursquare_venue_show_title')==1) {
		$rendered_html .= '<h3>';
		$rendered_html .= $stats_title;
		$rendered_html .= '</h3>';
	}
	if(get_option('foursquare_show_venue_name')==1) {
		$rendered_html .= '<h4>';
		if(get_option('foursquare_show_venue_icon')==1) $rendered_html .= '<img src="' . $venueIcon . '" style="border: 0; margin: 0;" /> ';
		$rendered_html .= '<a href="' . $venueURL . '">';
		$rendered_html .= $venueName;
		$rendered_html .= '</a>';
		$rendered_html .= '</h4>';
	}
	return $rendered_html;
}

function renderVenueStats($venue) {
	$mayor = $venue->response->venue->mayor->user->firstName . ' ' . $venue->response->venue->mayor->user->lastName;
	$mayorURL = 'http://foursquare.com/user/' . $venue->response->venue->mayor->user->id;
	$mayorPic = $venue->response->venue->mayor->user->photo;
	$mayorCount = $venue->response->venue->mayor->count;
	$hereNow = $venue->response->venue->hereNow->count;
	$totalCheckins = $venue->response->venue->stats->checkinsCount;
	
	$here_now_text = get_option('foursquare_show_here_now_text');
	if($here_now_text=='') $here_now_text = 'People here now:';
	$total_text = get_option('foursquare_show_total_text');
	if($total_text=='') $total_text = 'Total check-ins:';
	$mayor_text = get_option('foursquare_show_mayor_text');
	if($mayor_text=='') $mayor_text = 'Mayor:';

	$rendered_html .= '<ul>';
	if(get_option('foursquare_show_here_now')==1) $rendered_html .= '<li>' . $here_now_text . ' ' . $hereNow . '</li>';
	if(get_option('foursquare_show_total')==1) $rendered_html .= '<li>' . $total_text . ' ' . $totalCheckins . '</li>';
	if(get_option('foursquare_show_mayor')==1) {
		$rendered_html .= '<li>' . $mayor_text . ' ';
		if(get_option('foursquare_link_mayor')==1) $rendered_html .= '<a href="' . $mayorURL . '" title="' . $mayorCount . ' check-ins in the past 2 months" >';
		$rendered_html .= $mayor;
		if(get_option('foursquare_link_mayor')==1) $rendered_html .= '</a>';
		if(get_option('foursquare_show_mayor_photo')==1) $rendered_html .=  ' <img src="' . $mayorPic . '" height="' . get_option('foursquare_mayor_photo_size') . '" width="' . get_option('foursquare_mayor_photo_size') . '" style="border:0;margin:0;" />';
		$rendered_html .= '</li>';
	}
	$rendered_html .= '</ul>';
	return $rendered_html;
}

class Foursquare_Venue extends WP_Widget 
{

	function Foursquare_Venue() 
	{
		$widget_ops = array('classname' => 'foursquare_venue_widget', 'description' => 'Foursquare Venue');
		$this->WP_Widget('foursquare_venue', 'Foursquare Venue', $widget_ops);
	}

	function widget($args, $instance) 
	{
		extract($args);
		
		$id=str_replace("https://foursquare.com/venue/", "", $instance['venue_id']);
		$id=str_replace("http://foursquare.com/venue/", "", $id);
		$venue = getVenueInfo($id);

		echo $before_widget;
		$title = strip_tags($instance['title']);
		echo $before_title . $title . $after_title;

		echo renderVenueStats($venue);

		echo $after_widget;
	}

	function update($new_instance, $old_instance) 
	{
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['venue_id'] = trim(strip_tags($new_instance['venue_id']));

		return $instance;
	}

	function form($instance) 
	{
		$instance = wp_parse_args((array)$instance, array('title' => 'Foursquare', 'venue_id' => 3945));
		$title = strip_tags($instance['title']);
		$venue_id = strip_tags($instance['venue_id']);
?>
			<p><label for="<?php echo $this->get_field_id('title'); ?>">Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo attribute_escape($title); ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('venue_id'); ?>">Venue ID: <input class="widefat" id="<?php echo $this->get_field_id('venue_id'); ?>" name="<?php echo $this->get_field_name('venue_id'); ?>" type="text" value="<?php echo attribute_escape($venue_id); ?>" /></label></p>
<?php
	}
}

add_action('widgets_init', 'RegisterFoursquareVenueWidget');

function RegisterFoursquareVenueWidget() {
	register_widget('Foursquare_Venue');
}

// Shortcode

function show_foursquare_venue($atts) {
	extract(shortcode_atts(array(
		'id' => '',
	), $atts));
	
$id=str_replace("https://foursquare.com/venue/", "", $id);
$id=str_replace("http://foursquare.com/venue/", "", $id);

		$venue = getVenueInfo($id);
		
		if ($venue->meta->code==200) {

		$widget_html = '<div class="venue-stats';
		if(get_option('foursquare_venue_stats_align')=='left') $widget_html .= ' alignleft';
		if(get_option('foursquare_venue_stats_align')=='right') $widget_html .= ' alignright';
		$widget_html .= '"';
		if(get_option('foursquare_venue_stats_width')!='') $widget_html .= ' style="width:' . get_option('foursquare_venue_stats_width') . ';"';
		$widget_html .= '>';

		// Display Venue's Info
		$widget_html .= renderVenueInfo($venue);

		// Display Venue's Statistics
		$widget_html .= renderVenueStats($venue);

		$widget_html .= '</div>';
		
		return $widget_html;
		
		} else {
			return '[<strong>Error: '.$venue->meta->errorDetail.'</strong>]';
		}
}

add_shortcode('venue', 'show_foursquare_venue');

// Admin Page

add_action('admin_menu', 'foursquare_venue_menu');

function foursquare_venue_menu() {

  add_options_page('Foursquare Venue Options', 'Foursquare Venue', 'manage_options', 'foursquare-venue-options', 'foursquare_venue_options');

}

function foursquare_venue_options() {

  if (!current_user_can('manage_options'))  {
    wp_die( __('You do not have sufficient permissions to access this page.') );
  }

?>

<div class="wrap">

	<div id="icon-plugins" class="icon32"></div><h2>Foursquare Venue</h2>
	
	<h3>Getting Started</h3>
	
	<p>Before using this plugin, you'll have to set up a free Foursquare API key. Visit <a href="https://foursquare.com/oauth/">foursquare.com/oauth</a>, click "Register a new consumer," then enter the name of your site, your site's address, and for "Callback URL" just enter your site's address again. You'll be given two keys, "Client ID" and "Client Secret," which need to be copied and pasted into the matching fields on this page.</p>
	<p>After saving your API key you'll be able to start using the widget or shortcode. To use the widget simply add it to your sidebar and set the venue's ID. To use the shortcode just insert <code>[venue id=23647]</code> (replacing the '23647' with the desired venue's ID) into any post or page, and the plugin do the rest.</p>
	<p>The venue's ID can be found by taking the number from the end of the venue's page on the Foursquare website. For example, if the venue's URL is <code>https://foursquare.com/venue/<span style="color:green;">1278862</span></code>, the id is <code>1278862</code>.</p>
	
	<div id="icon-options-general" class="icon32"></div><h2>Options</h2>

	<form method="post" action="options.php">
	<?php wp_nonce_field('update-options'); ?>

	<h3>API Key</h3>
	
	<table class="form-table">
	
	<tr valign="top">
	<th scope="row">Client ID</th> 
	<td><fieldset><legend class="screen-reader-text"><span>Client ID</span></legend> 
	<input name="foursquare_venue_client_id" type="text" id="foursquare_venue_client_id" value="<?php echo get_option('foursquare_venue_client_id'); ?>" />
	</fieldset></td> 
	</tr>
	
	<tr valign="top">
	<th scope="row">Client Secret</th> 
	<td><fieldset><legend class="screen-reader-text"><span>Client Secret</span></legend> 
	<input name="foursquare_venue_client_secret" type="text" id="foursquare_venue_client_secret" value="<?php echo get_option('foursquare_venue_client_secret'); ?>" />
	</fieldset></td> 
	</tr>
	
	</table>

	<h3>Check-ins</h3>
	
	<table class="form-table">
	
	<tr valign="top"> 
	<th scope="row">Show Current Check-ins</th> 
	<td><fieldset><legend class="screen-reader-text"><span>Show the number of people currently checked-in</span></legend> 
	<label for="foursquare_show_here_now"><input name="foursquare_show_here_now" type="checkbox" id="foursquare_show_here_now" value="1" <?php if(get_option('foursquare_show_here_now')==1) echo "checked='checked'"; ?>/> Show the number of people currently checked-in</label> 
	</fieldset></td> 
	</tr>
	
	<tr valign="top">
	<th scope="row">Current Check-ins Text</th> 
	<td><fieldset><legend class="screen-reader-text"><span>Customize the text for the current number of check-ins</span></legend> 
	<label for="foursquare_show_here_now_text"><input name="foursquare_show_here_now_text" type="text" id="foursquare_show_here_now_text" value="<?php if(($here_now_text=get_option('foursquare_show_here_now_text'))!='') echo $here_now_text; else echo 'People here now:'; ?>" /> Customize the text for the current number of check-ins</label> 
	</fieldset></td> 
	</tr>

	<tr valign="top"> 
	<th scope="row">Show Total Check-ins</th> 
	<td><fieldset><legend class="screen-reader-text"><span>Show the total number of check-ins</span></legend> 
	<label for="foursquare_show_total"><input name="foursquare_show_total" type="checkbox" id="foursquare_show_total" value="1" <?php if(get_option('foursquare_show_total')==1) echo "checked='checked'"; ?>/> Show the total number of check-ins</label> 
	</fieldset></td> 
	</tr>
	
	<tr valign="top">
	<th scope="row">Total Check-ins Text</th> 
	<td><fieldset><legend class="screen-reader-text"><span>Customize the text for the total number of check-ins</span></legend> 
	<label for="foursquare_show_total_text"><input name="foursquare_show_total_text" type="text" id="foursquare_show_total_text" value="<?php if(($total_text=get_option('foursquare_show_total_text'))!='') echo $total_text; else echo 'Total check-ins:'; ?>" /> Customize the text for the total number of check-ins</label> 
	</fieldset></td> 
	</tr>
	
	</table>
	
	<h3>Mayor</h3>
	
	<table class="form-table">
	
	<tr valign="top"> 
	<th scope="row">Show Mayor</th> 
	<td><fieldset><legend class="screen-reader-text"><span>Show the current mayor</span></legend> 
	<label for="foursquare_show_mayor"><input name="foursquare_show_mayor" type="checkbox" id="foursquare_show_mayor" value="1" <?php if(get_option('foursquare_show_mayor')==1) echo "checked='checked'"; ?>/> Show the current mayor</label> 
	</fieldset></td> 
	</tr>
	
	<tr valign="top">
	<th scope="row">Mayor Text</th> 
	<td><fieldset><legend class="screen-reader-text"><span>Customize the text for the mayor</span></legend> 
	<label for="foursquare_show_mayor_text"><input name="foursquare_show_mayor_text" type="text" id="foursquare_show_mayor_text" value="<?php if(($mayor_text=get_option('foursquare_show_mayor_text'))!='') echo $mayor_text; else echo 'Mayor:'; ?>" /> Customize the text for the mayor</label> 
	</fieldset></td> 
	</tr>
	
	<tr valign="top"> 
	<th scope="row">Link to Mayor</th> 
	<td><fieldset><legend class="screen-reader-text"><span>Link to the current mayor</span></legend> 
	<label for="foursquare_link_mayor"><input name="foursquare_link_mayor" type="checkbox" id="foursquare_link_mayor" value="1" <?php if(get_option('foursquare_link_mayor')==1) echo "checked='checked'"; ?>/> Link to the current mayor</label> 
	</fieldset></td> 
	</tr>
	
	<tr valign="top">
	<th scope="row">Show Mayor's Photo</th> 
	<td><fieldset><legend class="screen-reader-text"><span>Show a photo of the current mayor next to their name</span></legend> 
	<label for="foursquare_show_mayor_photo"><input name="foursquare_show_mayor_photo" type="checkbox" id="foursquare_show_mayor_photo" value="1" <?php if(get_option('foursquare_show_mayor_photo')==1) echo "checked='checked'"; ?>/> Show a photo of the current mayor next to their name</label> 
	</fieldset></td> 
	</tr>

	<tr valign="top">
	<th scope="row">Mayor Photo Size</th> 
	<td><fieldset><legend class="screen-reader-text"><span>Choose what size to display the mayor's photo</span></legend> 
	<label for="foursquare_mayor_photo_size"><input name="foursquare_mayor_photo_size" type="text" id="foursquare_mayor_photo_size" value="<?php echo get_option('foursquare_mayor_photo_size'); ?>" size="3" />px (ex: 32 will create a 32x32 photo)</label> 
	</fieldset></td> 
	</tr>
	
	</table>
	
	<h3>Style</h3>
	
	<p>Note: These settings do not affect the Foursquare Venue widget, only the shortcode. Advanced users can style their Foursquare stats using CSS. Ex: <code>.venue-stats { width: 300px; float: right; }</code></p>
	
	<table class="form-table">
	
	<tr valign="top"> 
	<th scope="row">Show Shortcode Title</th> 
	<td><fieldset><legend class="screen-reader-text"><span>Show the current mayor</span></legend> 
	<label for="foursquare_venue_show_title"><input name="foursquare_venue_show_title" type="checkbox" id="foursquare_venue_show_title" value="1" <?php if(get_option('foursquare_venue_show_title')==1) echo "checked='checked'"; ?>/> Show a title above the Foursquare stats</label> 
	</fieldset></td> 
	</tr>
	
	<tr valign="top">
	<th scope="row">Shortcode Title Text</th> 
	<td><fieldset><legend class="screen-reader-text"><span>Customize the text for the mayor</span></legend> 
	<label for="foursquare_venue_stats_title"><input name="foursquare_venue_stats_title" type="text" id="foursquare_venue_stats_title" value="<?php if(($mayor_text=get_option('foursquare_venue_stats_title'))!='') echo $mayor_text; else echo 'Mayor:'; ?>" /> Customize the title above the stats</label> 
	</fieldset></td> 
	</tr>
	
	<tr valign="top"> 
	<th scope="row">Show Venue Name</th> 
	<td><fieldset><legend class="screen-reader-text"><span>Show the venue name</span></legend> 
	<label for="foursquare_show_venue_name"><input name="foursquare_show_venue_name" type="checkbox" id="foursquare_show_venue_name" value="1" <?php if(get_option('foursquare_show_venue_name')==1) echo "checked='checked'"; ?>/> Show name and link to the venue</label> 
	</fieldset></td> 
	</tr>
	
	<tr valign="top"> 
	<th scope="row">Show Venue Icon</th> 
	<td><fieldset><legend class="screen-reader-text"><span>Show venue icon</span></legend> 
	<label for="foursquare_show_venue_icon"><input name="foursquare_show_venue_icon" type="checkbox" id="foursquare_show_venue_icon" value="1" <?php if(get_option('foursquare_show_venue_icon')==1) echo "checked='checked'"; ?>/> Show an icon for the venue's category</label> 
	</fieldset></td> 
	</tr>
	
	<tr valign="top">
	<th scope="row">Width</th> 
	<td><fieldset>
	<label for="foursquare_venue_stats_width"><input name="foursquare_venue_stats_width" type="text" id="foursquare_venue_stats_width" value="<?php echo get_option('foursquare_venue_stats_width'); ?>" size="3" />px</label> 
	</fieldset></td> 
	</tr>
	
	<tr valign="top">
	<th scope="row">Align</th> 
	<td><fieldset>
	<label for="foursquare_venue_stats_alignleft"><input type="radio" name="foursquare_venue_stats_align" id="foursquare_venue_stats_alignleft" value="left" <?php if(get_option('foursquare_venue_stats_align')=='left') echo 'checked="checked" '; ?>/> Left</label> <label for="foursquare_venue_stats_alignright"><input type="radio" name="foursquare_venue_stats_align" id="foursquare_venue_stats_alignright" value="right" <?php if(get_option('foursquare_venue_stats_align')=='right') echo 'checked="checked" '; ?>/> Right</label> <label for="foursquare_venue_stats_alignnone"><input type="radio" name="foursquare_venue_stats_align" id="foursquare_venue_stats_alignnone" value="" <?php if(get_option('foursquare_venue_stats_align')=='') echo 'checked="checked" '; ?>/> None</label> 
	</fieldset></td> 
	</tr>
	
	</table>
	
	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="page_options" value="foursquare_venue_client_id,foursquare_venue_client_secret,foursquare_venue_show_title,foursquare_venue_stats_title,foursquare_show_venue_name,foursquare_show_venue_icon,foursquare_show_here_now,foursquare_show_here_now_text,foursquare_show_total,foursquare_show_total_text,foursquare_show_mayor,foursquare_show_mayor_text,foursquare_link_mayor,foursquare_show_mayor_photo,foursquare_mayor_photo_size,foursquare_venue_stats_width,foursquare_venue_stats_align" />
	
	<p class="submit">
	<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
	</p>
	
	</form>

</div>

<?php

}

?>