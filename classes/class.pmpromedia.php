<?php
class PMProMedia {
	private static $instance = null;
	
	/*
		get instance of class
	*/
	public static function get_instance() {
        if ( null == self::$instance ) {
            self::$instance = new self;
        }
 
        return self::$instance;
	}    
	
	/*
		constructor
	*/
	function __construct() {
		add_action('init', array('PMProMedia', 'init'));
		add_action('wp_ajax_nopriv_getmedia', array('PMProMedia', 'wp_ajax_getmedia'));
		add_action('wp_ajax_getmedia', array('PMProMedia', 'wp_ajax_getmedia'));
		
		register_activation_hook(__FILE__, array('PMProMedia', 'activation'));
		register_deactivation_hook(__FILE__, array('PMProMedia', 'deactivation'));
		add_action('init', array('PMProMedia', 'init_rewrite'));
	}
	
	/*
		init
	*/
	static function init() {	
		//start output buffering to look for URLs
		if(!is_admin())
			ob_start(array('PMProMedia', 'replaceMediaURLs'));
	}
	
	/*
		function to find and swap out media URLs
	*/
	static function replaceMediaURLs($buffer) {		
		//filters are added through the filter classes
		$filters = apply_filters('pmpro_media_filters', array());
				
		if(!empty($filters)) {
			foreach($filters as $filter) {
				$buffer = call_user_func($filter, $buffer);
			}
		}
		
		return $buffer;
	}
	
	/*
		load media by id
	*/
	static function wp_ajax_getmedia()
	{
		$media_id = intval($_REQUEST['media_id']);
		
		//make sure media id is passed
		if(empty($media_id))
			die("No media_id specified.");
			
		//get the attachment post
		global $wpdb;		
		$media = get_post($media_id);
		
		//found?
		if(empty($media))
			die("Could not find media with that id.");
			
		//check for access
		if(function_exists("pmpro_has_membership_access"))
		{
			if(!pmpro_has_membership_access($media->post_parent))
			{
				//nope
				wp_redirect(pmpro_url("levels"));
				exit;
			}
		}
		
		//redirect
		wp_redirect($media->guid);
		exit;			
	}

	/*
		rewrite rules for /media/
	*/
	//Add rule and flush on acitvation.
	static function activation()
	{
		add_rewrite_rule(
		  'media/([0-9]+)\.[a-zA-Z0-9]+$',
		  str_replace(site_url() . "/", "", admin_url('admin-ajax.php?action=getmedia&media_id=$1')),
		  'top'
		);
		flush_rewrite_rules();
	}

	//Add rule on init in case another plugin flushes
	static function init_rewrite()
	{
		add_rewrite_rule(
		  'media/([0-9]+)\.[a-zA-Z0-9]+$',
		   str_replace(site_url() . "/", "", admin_url('admin-ajax.php?action=getmedia&media_id=$1')),
		  'top'
		);
	}
	
	//Fush rewrite rules on deactivation to remove our rule.
	static function deactivation()
	{
		flush_rewrite_rules();
	}	
}

PMProMedia::get_instance();