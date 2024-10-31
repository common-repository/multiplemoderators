=== Multiple Moderators ===
Contributors: bjorsq
Donate Link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=QAQ2WC8UHFMEQ
Tags: comments, moderators
Requires at least: 3.0.1
Tested up to: 3.5
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A Wordpress plugin which allows site administrators to nominate multiple blog users to moderate comments.

== Description ==

Adds a page to the Wordpress admin area which enables the selection of blog users (either individually or by role) as comment moderators. Achieves moderation by plugging the wp_notify_moderator() function (in wp-includes/pluggable.php) and adding the extra moderators to the admin email.

== Installation ==

1. Upload the unzipped archive to the `/wp-content/plugins/` directory, or install directly through the dashboard
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure moderators for comments on the blog through the Comments submenu "Comment Moderators"

== Frequently Asked Questions ==

= Does this plugin work on a multisite installation? =

Yes, but moderators will have to be set up for comments on each site

= Can this plugin stop me (the site administrator) getting moderation requests? =

Yes, but the post author will always get comment notifications


== Changelog ==

= 1.0 =
* Initial release of the plugin