Meetup Login
=============

A WordPress plugin to integrate Meetup.com users into your WP site. For use with a [Meetup.com](http://meetup.com) group. Meetup Login allows users to log in to your WordPress site using their meetup.com account. 

Follow the instructions on [Meetup Widgets](http://wordpress.org/extend/plugins/meetup-widgets/installation/) to set this plugin up with your API & OAuth information. [View the FAQ there for info on finding your API & OAuth info](http://wordpress.org/extend/plugins/meetup-widgets/faq/) - both are required for this plugin.

## Documentation

While this plugin will work just fine with minimal configuration, the real power of it will be in making it work with your site. The default behavior is to create a user account with the meetup ID number as user meta, and then to find that meta value when the user logs in again.

There are [hooks](http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters) created so that you can use any method you want to store the connection between meetup user &amp; WP user. There is no way to do this outside of code, and I don't plan on adding or supporting one.

[View a walkthrough on my website](http://redradar.net/2012/07/01/meetup-login-alpha/).

### Definitions

#### Filters

`apply_filters('meetup_wp_user_id', $id, $meetup);`

Used to override the default method of getting the user ID (`$id`) matching the Meetup user. Needs to be a user ID, if `get_user_by('id', $id)` is false, a new user is created.

`apply_filters( 'meetup_login_new_user_redirect', admin_url('profile.php'), $user );`

After the user is created, they're redirected to their profile. This filter allows you to override that.

`apply_filters( 'meetup_login_existing_user_redirect', home_url(), $user );`

After an existing user logs in, they're redirected to the home page.

#### Actions

`do_action('meetup_user_create', $user, $meetup);`

Run when the plugin creates a user. currently used to hook `add_user_meetup_id`, which adds the meetup ID to user meta (which by default is how the matching user is found)

`do_action('meetup_user_update', $user, $meetup);`

### Applying this to an existing site

There currently is no automated way for existing users to hook up their meetup accounts, though this will probably change in an updated version.

## Todo
* Add a message when users cannot be created. Currently redirects silently to home page.
* Add method to attach existing users to a meetup ID (Will probably be a method for site admin to write in the meetup ID per user).
