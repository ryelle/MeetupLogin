<?php
/*
 * Plugin Name: Meetup Login
 * Description: Integrate Meetup.com users into your WP site
 * Version: 1.0
 * Author: Kelly Dwan
 * Author URI: http://redradar.net
 * Plugin URI: http://me.redradar.net/category/plugins/meetup-login/
 * License: GPL2
 * Date: 5.29.2012
 */

/** 
 * If the class exists we've declared it in another Meetup integration plugin,
 * and should not do so twice.
 */
if (!class_exists('VsMeet')):

class VsMeet {
	/* global variables */
	private $req_url = 'http://api.meetup.com/oauth/request/';
	private $authurl = 'http://www.meetup.com/authorize/';
	private $acc_url = 'http://api.meetup.com/oauth/access/';
	private $api_url = 'http://api.meetup.com/';
		
	private $key = '';
	private $secret = '';	
	protected $api_key = "";
	
	public function __construct($var = '') {
		$options = get_option('vs_meet_options');
		$this->key = $options['vs_meetup_key'];
		$this->secret = $options['vs_meetup_secret'];
		$this->api_key = $options['vs_meetup_api_key'];
		
		register_activation_hook( __FILE__, array ($this, 'install' ) );
		
		// TODO deal with translations.
		//load_plugin_textdomain('vsmeet_domain');
		
		// add admin page & options
		add_filter( 'admin_init' , array( $this , 'register_fields' ) );
	}
	
	public function install(){
		//nothing here yet, as there's really nothing to 'install' that isn't covered by __construct
	}
	
	function register_fields() {
		register_setting( 'general', 'vs_meet_options', array($this,'validate'));
		
		add_settings_section('vs_meet', 'Meetup API Settings', array($this, 'setting_section_vs_meetup'), 'general');
		
		add_settings_field('vs_meetup_key', '<label for="vs_meetup_key">'.__('OAuth Meetup Key:' , 'vsmeet_domain' ).'</label>' , array(&$this, 'setting_vs_meetup_key') , 'general', 'vs_meet' );
		add_settings_field('vs_meetup_secret', '<label for="vs_meetup_secret">'.__('OAuth Meetup Secret:' , 'vsmeet_domain' ).'</label>' , array(&$this, 'setting_vs_meetup_secret') , 'general', 'vs_meet' );
		
		add_settings_field('vs_meetup_api_key', '<label for="vs_meetup_api_key">'.__('Meetup API Key:' , 'vsmeet_domain' ).'</label>' , array(&$this, 'setting_vs_meetup_api_key') , 'general', 'vs_meet' );
		
		
	}

	function setting_section_vs_meetup(){
		echo "<p>".__("To use this plugin, you need to create an OAuth consumer. You can do that (or reset your information) by going here: <a target='_blank' href='http://www.meetup.com/meetup_api/oauth_consumers/'>Your OAuth Consumers</a>",'vsmeet_domain')."</p>";
	}

	function setting_vs_meetup_key() {
		$options = get_option('vs_meet_options');
		echo "<input id='vs_meetup_key' name='vs_meet_options[vs_meetup_key]' size='40' type='text' value='{$options['vs_meetup_key']}' />";
	}
	function setting_vs_meetup_secret() {
		$options = get_option('vs_meet_options');
		echo "<input id='vs_meetup_secret' name='vs_meet_options[vs_meetup_secret]' size='40' type='text' value='{$options['vs_meetup_secret']}' />";
	}
	function setting_vs_meetup_api_key() {
		$options = get_option('vs_meet_options');
		echo "<input id='vs_meetup_api_key' name='vs_meet_options[vs_meetup_api_key]' size='40' type='text' value='{$options['vs_meetup_api_key']}' />";
	}

	/**
	 * Sanitize and validate input. 
	 * @param array $input an array to sanitize
	 * @return array a valid array.
	 */
	public function validate($input) {
		if ( !preg_match('/^[a-zA-Z0-9]{0,40}$/i', $input['vs_meetup_key']) )
			$input['vs_meetup_key'] = $input['vs_meetup_key'];
		if ( !preg_match('/^[a-zA-Z0-9]{0,40}$/i', $input['vs_meetup_secret']) )
			$input['vs_meetup_secret'] = $input['vs_meetup_secret'];
		if ( !preg_match('/^[a-zA-Z0-9]{0,40}$/i', $input['vs_meetup_api_key']) )
			$input['vs_meetup_api_key'] = $input['vs_meetup_api_key'];
	    return $input;
	}
	
}
endif;

require_once('vs_meetup_login.php');

/**
 * Initialize Meetup Login
 */
function meetup_login_start() {
	if (!class_exists('OAuth')){
		add_action('pre_current_active_plugins','vsml_need_oauth');
	} else {
		$vsml = new VsMeetLogin();
	}

} add_action( 'init', 'meetup_login_start' );

function vsml_need_oauth() {
	echo '<div id="message" class="error"><p>Meetup Login requires <a href="http://php.net/manual/en/book.oauth.php">OAuth</a>, which is not detected on this server. Contact your host for more information on installing OAuth.</p></div>';
}
