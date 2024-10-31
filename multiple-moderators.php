<?php
/*
Plugin Name: Multiple Moderators
Plugin URI: https://github.com/uol-essl/multiple-moderators
Description: Wordpress plugin which allows site administrators to nominate multiple blog users to moderate comments. Adds a page to the Wordpress admin which enables the selection of blog users (either individually or by role) as comment moderators. Achieves moderation by plugging the wp_notify_moderator() function (in wp-includes/pluggable.php) and adding the extra moderators to the admin email.
Version: 1.0
Author: Peter Edwards
Author URI: https://github.com/uol-essl/multiple-moderators
Text Domain: multiple-moderators
License: GPL2
*/

/* include pluggable wp_notify_moderators function */
require_once(dirname(__FILE__) . '/wp_notify_moderator.php');

/**
 * class used to handle the Wordpress admin page, settings, and register 
 * a filter for the pluggable wp_notify_moderator function
 */
class multipleModerators
{
	/* prevent object instantiation */
	private function __construct(){}
	private function multipleModerators(){}

	/* register with Wordpress API */
	public static function register()
	{
		/* adds a filter to add comment moderators (used in wp_notify_moderator) */
		add_filter( 'comment_moderator_emails', array('multipleModerators', 'add_moderators') );
		/* adds moderator sub-page to Comments admin menu */
		add_action( 'admin_menu', array('multipleModerators', 'add_moderator_submenu') );
		/* register settings for the plugin */
		add_action( 'admin_init', array('multipleModerators', 'register_settings') );
		/* load language files */
		add_action( 'init', array('multipleModerators', 'load_multiple_moderators_textdomain' ) );
	}

	/* loads language files for plugin */
	public static function load_multiple_moderators_textdomain()
	{
		load_plugin_textdomain('multiple-moderators', false, dirname(plugin_basename(__FILE__)).'/languages/');
	}

	/* adds moderators to the wp_notify_moderator email array */
	public static function add_moderators($emails)
	{
		$users = get_users();
		$settings = self::get_settings();
		$emails_temp = array();
		if (is_array($users) && count($users)) {
			foreach ($users as $user) {
				$details = get_userdata($user->ID);
				/* add emails based on roles */
				foreach($details->roles as $role) {
					if (in_array($role, $settings["mod_roles"])) {
						$emails_temp[] = $details->data->user_email;
					}
				}
				/* add emails based on IDs */
				if (in_array($user->ID, $settings["mod_users"])) {
					$emails_temp[] = $details->data->user_email;
				}
			}
		}
		/* remove any duplicates from the merged arrays */
		$all_emails = array_unique(array_merge($emails_temp, $emails));
		/* disable the site admin user if this has been selected */
		if (isset($settings["disable_admin"]) && $settings["disable_admin"] == 1) {
			if (in_array(get_option('admin_email'), $all_emails)) {
				array_splice($all_emails, array_search(get_option('admin_email'), $all_emails), 1);
			}
		}
		return $all_emails;
	}

	/* adds the moderators submenu to the comments section of Wordpress admin */
	public static function add_moderator_submenu()
	{
		add_comments_page( __('Comment Moderators', 'multiple-moderators'), __('Comment Moderators', 'multiple-moderators'), 'edit_users', 'mm-comment-moderators', array('multipleModerators', 'moderator_admin_page') );
	}

	/* generate admin page under comments */
	public static function moderator_admin_page()
	{
		printf('<div class="wrap"><h2>%s</h2>', __('Multiple Moderators', 'multiple-moderators'));
		settings_errors('multimod_settings');
		if (isset($_REQUEST['settings-updated']) && $_REQUEST['settings-updated'] == "true")
		{
		    printf('<div id="message" class="updated fadeout"><p><strong>%s</strong></p></div>', __('Settings saved', 'multiple-moderators'));
		}
		printf('<p>%s</p><p>%s</br><code>%s</code></p>', __('This plugin will change the default comment moderation behaviour of Wordpress (which is to send email alerts to the author of the post which receives the comment and the site administrator). Use the fields below to include moderators on the site.', 'multiple-moderators'), __('Your comment moderators are currently:', 'multiple-moderators'), implode('</code>, <code>', self::add_moderators(array(__('([post author] (cannot be removed)', 'multiple-moderators'), get_option('admin_email')))));

		print('<form action="options.php" method="post">');
		settings_fields('multimod_settings');
		do_settings_sections('comment-moderators');
		printf('<p><input name="Submit" type="submit" class="button-primary" value="%s" /></p>', __('Save Changes', 'multiple-moderators'));
		print('</form></div>');
	}

	/* register settings for plugin using Settings API */
	public static function register_settings()
	{
		register_setting( 'multimod_settings', 'multimod_settings', array('multipleModerators', 'validate_settings') );
		add_settings_section( 'disable_admin', __('Disable Site admin moderation', 'multiple-moderators'), array('multipleModerators', 'setting_section_text'), 'comment-moderators');
		add_settings_field( 'mod_disable', '', array('multipleModerators', 'setting_disable_admin'), 'comment-moderators', 'disable_admin');
		add_settings_section( 'by_role', __('Select Moderators by Role', 'multiple-moderators'), array('multipleModerators', 'setting_section_text'), 'comment-moderators');
		add_settings_field( 'mod_roles', __('Roles', 'multiple-moderators'), array('multipleModerators', 'setting_user_role'), 'comment-moderators', 'by_role');
		add_settings_section( 'by_user', __('Select Moderators by User', 'multiple-moderators'), array('multipleModerators', 'setting_section_text'), 'comment-moderators');
		add_settings_field( 'mod_users', __('Users', 'multiple-moderators'), array('multipleModerators', 'setting_user'), 'comment-moderators', 'by_user');
	}

	/* gets settings */
	public static function get_settings()
	{
		$options = wp_cache_get('multimod_settings');
		if (!$options) {
			/* get options from wp options */
			$options = get_option('multimod_settings');
			if (!isset($options["mod_roles"])) {
				$options["mod_roles"] = array();
			}
			if (!isset($options["mod_users"])) {
				$options["mod_users"] = array();
			}
			wp_cache_set('multimod_settings', $options);
		}
		return $options;
	}

	/* section text (not used) */
	public static function setting_section_text(){}

	/* checkbox to disable comment moderation by site administrator */
	public static function setting_disable_admin()
	{
		$settings = self::get_settings();
		$chckd = (isset($settings["disable_admin"]) && $settings["disable_admin"] == 1)? ' checked="checked"': '';
		printf('<p><input type="checkbox" name="multimod_settings[disable_admin]" value="1"%s /> %s (<code>%s</code>).</p>', $chckd, __('Check this box to diasble comment moderation for the site administrator', 'multiple-moderators'), get_option('admin_email'));
	}

	/* render form for user role setting */
	public static function setting_user_role()
	{
		$roles = get_editable_roles();
		$settings = self::get_settings();
		if (is_array($roles) && count($roles)) {
			foreach ($roles as $role => $details) {
				if (isset($details["capabilities"]) && isset($details["capabilities"]["moderate_comments"]) && $details["capabilities"]["moderate_comments"] == 1) {
					$chckd = in_array($role, $settings["mod_roles"])? ' checked="checked"': '';
					$label = sprintf(_x('Check this box to nominate all <em>%ss</em> as comment moderators.', 'User Roles', 'multiple-moderators'), $details["name"]);
					printf('<p><input type="checkbox" name="multimod_settings[mod_roles][]" value="%s"%s /> %s</p>', $role, $chckd, $label);
				}
			}
		}
	}

	/* render form for individual user setting */
	public static function setting_user()
	{
		$users = get_users();
		$settings = self::get_settings();
		if (is_array($users) && count($users)) {
			foreach ($users as $user) {
				$details = get_userdata($user->ID);
				if (isset($details->allcaps["moderate_comments"]) && $details->allcaps["moderate_comments"] == 1) {
					$chckd = in_array($user->ID, $settings["mod_users"])? ' checked="checked"': '';
					$label = sprintf(_x('Check this box to nominate <em>%s</em> as a comment moderator.', 'Users (display name)', 'multiple-moderators'), $details->data->display_name);
					printf('<p><input type="checkbox" name="multimod_settings[mod_users][]" value="%s"%s /> %s</p>', $user->ID, $chckd, $label);
				}
			}
		}
	}

	/* validate settings */
	public static function validate_settings($settings)
	{
		return $settings;
	}

}
multipleModerators::register();
