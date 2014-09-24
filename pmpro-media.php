<?php
/*
Plugin Name: PMPro Media
Version: .1.1
Plugin URI: http://www.paidmembershipspro.com/wp/pmpro-media/
Description: Protect media files with Paid Memberships Pro.
Author: strangerstudios
*/

/*
	Settings
	Copy these lines into your wp-config.php file and uncomment them.
*/
//define('PMPRORM_S3_BUCKET', '');
//define('PMPRORM_S3_ACCESS_KEY', '');
//define('PMPRORM_S3_SECRET_KEY', '');

/*
	Definitions
*/
define('PMPROM_DIR', dirname(__FILE__));

/*
	Includes
*/
require_once(PMPROM_DIR . "/classes/class.pmpromedia.php");
require_once(PMPROM_DIR . "/classes/class.pmprom_amazon.php");