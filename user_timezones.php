<?php
/*
Plugin Name: User Timezones
Plugin URI: https://github.com/YakMade/WordPress-User-Timezones
Description: Add timezone settings to users in Wordpress, allowing users to work in their local timezone on a multi-user install.
Author: Andrew Saturn, Yak
Version: 0.1
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
					$message = 'Please specify a timezone.';
					$class = 'notice-error';
					break;
				}

				$message = 'Timezone set.';
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
							/* set current timezone as selected */
							  $(document).ready(function() {
									$("#local_timezone option[value='<?php echo get_user_meta($user_id, 'local_timezone', true); ?>']").prop('selected', true).addClass('selected');
							  });
							</script>
							<style type="text/css">
								#local_timezone option.selected {
									background-color: #edeff0;
								}
							</style>

							<select name="local_timezone" id="local_timezone">
								<option value="" disabled>Select a timezone</option>
								<?php
									$tzlist = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
									foreach ($tzlist as $timezone) {
										if($timezone != "UTC") { echo '<option value="' . $timezone . '">' . $timezone . '</option>'; }
									}
								?>
							</select>
							<?php

							/* show time comparison if set */
							if(get_user_meta($user_id, 'local_timezone', false)) { // false returns true or false based on if it exists
  							$tz = get_user_meta($user_id, 'local_timezone', true); // true returns value

                global $wpdb;
                //$db_timezone = $wpdb->get_var("SELECT option_value FROM {$wpdb->prefix}options WHERE option_name = 'timezone_string'");

                $wordpress_timezone = new DateTimeZone(get_option('timezone_string'));
                //$wordpress_timezone = new DateTimeZone($db_timezone);

                $user_timezone = new DateTimeZone($tz); /* create timezone based on user timezone setting */

                $wordpress_time = new DateTime('now', $wordpress_timezone); /* timestamp based on wordpress timezone */
  							$user_time = new DateTime('now', $user_timezone); /* create "now" time based on user timezone */

                $time1 = new DateTime($wordpress_time->format('Y-m-d G:i:s'));
                $time2 = new DateTime($user_time->format('Y-m-d G:i:s'));
                $diff = date_diff($time1, $time2);

							?>
							<p class="timezone-info">
								<span id="local-time">Default time is <code><?php echo $wordpress_time->format('Y-m-d G:i:s'); ?></code>.</span>
								<span id="user-time">Your local user time is <code><?php echo $user_time->format('Y-m-d G:i:s'); ?></code>.<br>
                Your local timezone has a difference of <code><?php echo $diff->format('%r%H:%I'); ?></code> from the default timezone.</span>
							</p>
						<?php } ?>
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
  if($timezone == "") {
    /* set to default if it doesn't exist */
    global $wpdb;
    $timezone = $wpdb->get_var("SELECT option_value FROM {$wpdb->prefix}options WHERE option_name = 'timezone_string'");
  }
  return $timezone;
}
if(is_admin()) {
  global $pagenow;
  /* prevent default timezone from being overridden on the general options page */
  if(!in_array($pagenow, array('options-general.php'))) {
    /* substitute timezone for user's timezone */
    add_filter('pre_option_timezone_string',  'yak_user_timezone');
  }
}
?>
