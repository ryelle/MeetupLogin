<?php 
/**
 * VsMeetLogin
 * All functionality for allowing logins via meetup oAuth
 */

class VsMeetLogin extends VsMeet {
	private $req_url = 'http://api.meetup.com/oauth/request/';
	private $authurl = 'http://www.meetup.com/authorize/';
	private $acc_url = 'http://api.meetup.com/oauth/access/';
	private $api_url = 'http://api.meetup.com/';
	private $callback_url = '';
		
	private $key = '';
	private $secret = '';	
	protected $api_key = "";
	
	public function __construct() {
		$options = get_option('vs_meet_options');
		$this->key = $options['vs_meetup_key'];
		$this->secret = $options['vs_meetup_secret'];
		$this->api_key = $options['vs_meetup_api_key'];
		$this->callback_url = admin_url( 'admin-ajax.php' ) .'?action=meetup_login';
		
		parent::__construct();
		
		// add login hook -> 'login_init'?
		
		// add 'login via meetup' link to login
		add_action( 'login_head', array($this, 'includes') );
		add_action( 'login_form', array($this,'login') );
		// add 'register via meetup' link to BP register screen
		add_action( 'bp_core_screen_signup', array($this, 'includes') );
		add_action( 'bp_before_account_details_fields', array($this, 'register') );
		
		// add Meetup ID to user meta on account creation - this is a hook so you can disable if desired.
		add_action( 'meetup_user_create', array($this, 'add_user_meetup_id'), 10, 2 );
		
		// add login function to ajax requests
		add_action( 'wp_ajax_nopriv_meetup_login', array(&$this, 'meetup_login_popup') );
		add_action( 'wp_ajax_meetup_login', array(&$this, 'meetup_login_popup') );
	}

	/**
	 * Add javascript and css to header files.
	 */
	public function includes(){
		wp_enqueue_style( 'meetup', plugins_url("meetup.css",__FILE__) );
	    wp_enqueue_script( 'meetup-login', plugins_url("meetup-login.js",__FILE__), array('jquery') );
	    $data = array( 
	    	'url' => admin_url( 'admin-ajax.php' ),
	    	'action' => 'meetup_login'
	    );
	    wp_localize_script( 'meetup-login', 'data', $data );
	}

	/**
	 * Display a link that pops up a "login via meetup" window
	 */
	function login(){
		if (!isset($_SESSION['token']) || !isset($_SESSION['secret']) ){
			echo "<div id='meetup-login'><a href='#' class='meetup'>Log in via Meetup.com</a></div>";
		}
	}
	
	/**
	 * Display a link that pops up a "register via meetup" window
	 */
	function register(){
		if (!isset($_SESSION['token']) || !isset($_SESSION['secret']) ){
			echo "<div id='meetup-login'><a href='#' class='meetup'>Register via your Meetup.com account</a></div>";
		}
	}

	/**
	 * Get the user details from their meetup ID number
	 */
	function get_user_by_meetup($meetup_id) {
		global $wpdb;
		$sql = "SELECT u.ID FROM $wpdb->usermeta AS um	INNER JOIN  $wpdb->users AS u ON (um.user_id=u.ID) WHERE um.meta_key = 'meetup_id' AND um.meta_value = '%s'";
		return $wpdb->get_var( $wpdb->prepare( $sql, $meetup_id ) );
	}
	
	/**
	 * Add the meetup ID to the newly-created user, so we can find them when they log in next.
	 */
	function add_user_meetup_id($user, $meetup) {
		add_user_meta( $user->id, 'meetup_id', $meetup->id, true );
	}
	
	/**
	 * Create the login popup
	 */
	function meetup_login_popup() {
		$header = '<html dir="ltr" lang="en-US">
			<head>
				<meta charset="UTF-8" />
				<meta name="viewport" content="width=device-width" />
				<title>RSVP to a Meetup</title>
				<link rel="stylesheet" type="text/css" media="all" href="'.get_bloginfo( 'stylesheet_url' ).'" />
				<style>
					.button {
						padding:3%;
						color:white;
						background-color:#B03C2D;
						border-radius:3px;
						display:block;
						font-weight:bold;
						width:40%;
						float:left;
						text-align:center;
					}
					.button.no {
						margin-left:8%;
					}
				</style>
			</head>
			<body>
				<div id="page" class="hfeed meetup login" style="padding:15px;">';
		if ( empty($this->key) || empty($this->secret) ) {
			echo $header;
			echo '<p><a href="'.admin_url('options-general.php').'">Please enter your OAuth key & secret.</a></p>';
			exit;
		}
		session_start();
		
		if (!array_key_exists('state',$_SESSION)) $_SESSION['state'] = 0;
		if (!isset($_GET['oauth_token']) && $_SESSION['state']==1) $_SESSION['state'] = 0;
		
		try {
			$oauth = new OAuth($this->key, $this->secret, OAUTH_SIG_METHOD_HMACSHA1, OAUTH_AUTH_TYPE_AUTHORIZATION );
			$oauth->enableDebug();
			if (!isset($_GET['oauth_token']) && !$_SESSION['state']) {
				$request_token_info = $oauth->getRequestToken($this->req_url); //,plugins_url('vs-oauth.php',__FILE__));
				$_SESSION['secret'] = $request_token_info['oauth_token_secret'];
				$_SESSION['state'] = 1;
				header('Location: '.$this->authurl.'?oauth_token='.$request_token_info['oauth_token'].'&oauth_callback='.$this->callback_url);
				exit;
			} else if ($_SESSION['state']==1) {
				$oauth->setToken($_GET['oauth_token'],$_SESSION['secret']);
				$verifier = (array_key_exists('verifier',$_GET)) ? $_GET['verifier'] : null; 
				$access_token_info = $oauth->getAccessToken($this->acc_url,null,$verifier);
				$_SESSION['state'] = 2;
				$_SESSION['token'] = $access_token_info['oauth_token'];
				$_SESSION['secret'] = $access_token_info['oauth_token_secret'];
			}
			$oauth->setToken($_SESSION['token'],$_SESSION['secret']);
			echo $header;
		
			$oauth->fetch($this->api_url."/members?relation=self");	
			$response = json_decode($oauth->getLastResponse());
			
			$meetup = $response->results[0];
			unset($meetup->topics);
			
			$id = $this->get_user_by_meetup($meetup->id);
			$id = apply_filters('meetup_wp_user_id', $id, $meetup);
			
			//if the user wasn't found, $id is null, and get_user_by returns false.
			if ( false === ($user = get_user_by('id', $id)) ){
				//there is no user, so create, and log in.
				$user_info = array(
					'user_login' => sanitize_title($meetup->name),
					'user_nicename' => $meetup->name,
					'display_name' => $meetup->name,
					'nickname' => $meetup->name,
					//'user_pass' => base_convert(mt_rand(0x19A100, 0x39AA3FF), 10, 36),
					'user_url' => $meetup->link,
					'description' => $meetup->bio,
					//'other_services' => maybe_serialize($meetup->other_services),
					//'avatar' => $meetup->photo_url,
				);
				
				$new_user = wp_insert_user( $user_info );
				if (is_wp_error($new_user))
					wp_die( $new_user );
				
				wp_set_auth_cookie($new_user);
				$user = get_user_by('id', $new_user);
				do_action('meetup_user_create', $user, $meetup);
				
				$new_user_redirect = apply_filters( 'meetup_login_new_user_redirect', admin_url('profile.php'), $user );
				echo "<script>window.opener.location.href = '". $new_user_redirect ."'; window.close();</script>";
			} else {
				wp_set_auth_cookie($user->ID);
				wp_set_current_user( $user->ID );
				do_action('meetup_user_update', $user, $meetup);
				
				$existing_user_redirect = apply_filters( 'meetup_login_existing_user_redirect', get_bloginfo('url'), $user );
				echo "<script>window.opener.location.href = '". $existing_user_redirect ."'; window.close();</script>";
			}
		
		
		} catch(OAuthException $E) {
			echo $header;
			echo "<h1 class='entry-title'>There was an error processing your request. Please try again.</h1>";
			if (WP_DEBUG) echo "<pre>".print_r($E,true)."</pre>";
			if (WP_DEBUG) echo "<pre>".print_r($_SESSION,true)."</pre>";
			if (WP_DEBUG) echo "<pre>".print_r($_GET,true)."</pre>";
		}
		unset($_SESSION['state']);
	}
}