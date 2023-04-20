<?php
/*
Plugin Name: User Timezones
Plugin URI: https://github.com/YakMade/WordPress-User-Timezones
Description: Add timezone settings to users in Wordpress, allowing users to work in their local timezone on a multi-user install.
Author: Andrew Saturn, Yak
Version: 0.2
Author URI: https://yakmade.com
*/

/* add timezone setting to user panel */
function yak_set_user_meta_subpanel() {
	/* get user */
  global $user_id;

  if (!$user_id) {
    $user_id = get_current_user_id();
  }

	/* handle setting the time zone */
	if(isset($_POST['action']))	{
		switch($_POST['action']) {
			case 'setUserMeta':
				if(!isset($_POST['local_timezone']) || $_POST['local_timezone'] == "") {
					$message = 'Please select a timezone.';
					$class = 'notice-error';
					break;
				}

				$message = 'Local timezone set.';
				$class = 'notice-success';
				/* set it */
				update_user_meta($user_id, 'local_timezone', $_POST['local_timezone']);
				break;

			default:
				$message = 'No action specified.';
				$class = 'notice-warning';
		}
		if(isset($message)) {
		?>
		<div id="message" class="is-dismissible notice <?php echo $class; ?>">
			<p><?php _e($message); ?></p>
		</div>
		<?php
		}
	}
	?>

	<div class="wrap">
		<h2>Set Your Local Timezone</h2>
		<form method="post" action="" enctype="multipart/form-data">

			<p>This will set a user-level timezone to help calculate when a post is published or scheduled in your own local time.</p>

			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="offset">Timezone</label>
						</th>
						<td>
							<input type="hidden" name="action" value="setUserMeta" />

							<script type='text/javascript'>
							<?php /* set current timezone as selected */ ?>
							  jQuery(document).ready(function ($) {
									$("#local_timezone option[value='<?php echo get_user_meta($user_id, 'local_timezone', true); ?>']").prop('selected', true).addClass('selected');
							  });
							</script>
							<style type="text/css">
								#local_timezone option.selected {
									background-color: #edeff0;
								}
							</style>

							<select name="local_timezone" id="local_timezone">
								<option value="">Select a timezone</option>
								<?php
									$tzlist = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
									foreach ($tzlist as $timezone_list_item) {
										if($timezone_list_item != "UTC") { echo '<option value="' . $timezone_list_item . '">' . $timezone_list_item . '</option>'; }
									}
								?>
							</select>
							<?php

							/* show time comparison if set */
							if(get_user_meta($user_id, 'local_timezone', false)) { // false returns true or false based on if it exists
  							$user_tz_setting = get_user_meta($user_id, 'local_timezone', true); // true returns value

                // global $wpdb;
                $wordpress_timezone = new DateTimeZone(wp_timezone_string());
                $user_timezone = new DateTimeZone($user_tz_setting); /* create timezone based on user timezone setting */
                $wordpress_time = new DateTime('now', $wordpress_timezone); /* timestamp based on wordpress timezone */
  							$user_time = new DateTime('now', $user_timezone); /* create "now" time based on user timezone */

                $time1 = new DateTime($wordpress_time->format('Y-m-d G:i:s'));
                $time2 = new DateTime($user_time->format('Y-m-d G:i:s'));
                $diff = date_diff($time1, $time2);

							?>
							<p class="timezone-info">
								<p><span id="local-time">Default local time is <?php echo wp_timezone_string(); ?> <code><?php echo $wordpress_time->format('Y-m-d G:i:s'); ?></code>.</span></p>
								<p><span id="user-time">Your local user time is <?php echo $user_tz_setting; ?> <code><?php echo $user_time->format('Y-m-d G:i:s'); ?></code>.</p>
                <p>Your local timezone has a difference of <code><?php echo $diff->format('%r%H:%I'); ?></code> from the default timezone.</span></p>
							</p>
						<?php } else {
              /* no timezone setting for this user */
              ?><p><em>No local timezone setting found for your user account.</em></p><?php
            } ?>
						</td>
					</tr>

				</tbody>
			</table>

			<p class="submit">
				<input name="submit" id="submit" class="button button-primary" value="Save Changes" type="submit">
			</p>
		</form>
	</div>
	<?php
}

/* create settings panel */
function yak_set_user_meta_panel() {
		if(function_exists('add_submenu_page')) {
			add_submenu_page('profile.php', 'Your Timezone', 'Your Timezone', 'read', 'localtimezone', 'yak_set_user_meta_subpanel');
		}
 }
add_action('admin_menu', 'yak_set_user_meta_panel');

function yak_user_timezone($timezone) {
  global $user_id;

  if (!$user_id) {
    $user_id = get_current_user_id();
  }

  $timezone = get_user_meta($user_id, 'local_timezone', true);
  if($timezone == '') {
    // user local_timezone setting doesn't exist
    return false;
  } else {
    return $timezone;
  }
}
if(is_admin()) {
  global $pagenow;
  /* prevent default timezone from being overridden on the general options page and this user page */
  if(!in_array($pagenow, array('options-general.php', 'users.php'))) {
    /* substitute timezone for user's timezone */
    add_filter('pre_option_timezone_string',  'yak_user_timezone');
  }
}
?>
