<?php
/*
	Amazon S3 Media Filter for PMPro Media
*/
class PMProMediaAmazon {
	private static $instance = null;
	
	//get instance of class
	public static function get_instance() {
        if ( null == self::$instance ) {
            self::$instance = new self;
        }
 
        return self::$instance;
	}
	
	//constructor
	function __construct() {
		add_action('init', array('PMProMediaAmazon', 'init'));
	}
	
	//init
	static function init() {		
		//hooks/filters
		add_filter('pmpro_media_filters', array('PMProMediaAmazon', 'pmpro_media_filters'));
		add_filter('pmpro_media_getmedia_url', array('PMProMediaAmazon', 'pmpro_media_getmedia_url'));
	}
	
	//add our filter to the list
	static function pmpro_media_filters($filters) {		
		$filters[] = array("PMProMediaAmazon", "filter");
		return $filters;
	}
	
	//the actual filter function
	static function filter($buffer) {		
		//check for settings
		if(!defined('PMPRORM_S3_BUCKET') || !defined('PMPRORM_S3_ACCESS_KEY') || !defined('PMPRORM_S3_SECRET_KEY'))
			return $buffer;
		
		//find URLs
		$url_pattern = "/(https?\:\/\/s3\.amazonaws\.com\/" . PMPRORM_S3_BUCKET . "\/[^\'\"]*)/";
				
		//look for media
		$matched = preg_match($url_pattern, $buffer, $matches);								
		
		if(!empty($matched))
		{		
			global $wpdb;
			
			$replacements = array();
			foreach($matches as $match)
			{				
				//already have a replacement for this?
				if(array_key_exists($match, $replacements))
					continue;
					
				//look for media
				$sqlQuery = "SELECT ID FROM $wpdb->posts WHERE post_type='attachment' AND guid = '" . esc_sql($match) . "' LIMIT 1";				
				$media_id = $wpdb->get_var($sqlQuery);
								
				//get the extension used and figure out the filename to use
				$parts = explode(".", basename($match));
				if(is_array($parts) && !empty($parts[1]))
					$ext = $parts[1];
				else
					$ext = "";				
				
				if($ext)
					$newfilename = "/media/" . $media_id . "." . $ext;
				else
					$newfilename = "/media/" . $media_id;
					
				//filter
				$newfilename = apply_filters("pmprom_replacement_filename", $newfilename, $media_id, $match);
				
				//swap
				if(!empty($media_id))
					$replacements[$match] = $newfilename;					
				else
					$replacements[$match] = false;
			}
		}
				
		//replace URLs
		if(!empty($replacements))
		{			
			$buffer = str_replace(array_keys($replacements), array_values($replacements), $buffer);			
		}
		
		return $buffer;
	}
	
	//swap amazon URLs for protected URLs when getting media
	static function pmpro_media_getmedia_url($url)
	{		
		if(strpos($url, "s3.amazonaws.com") !== false)
		{			
			$url = PMProMediaAmazon::getTemporaryLink(PMPRORM_S3_ACCESS_KEY, PMPRORM_S3_SECRET_KEY, PMPRORM_S3_BUCKET, basename($url));		
		}
			
		return $url;
	}
	
	/**
	* Calculate the HMAC SHA1 hash of a string.
	*
	* @param string $key The key to hash against
	* @param string $data The data to hash
	* @param int $blocksize Optional blocksize
	* @return string HMAC SHA1
	*/
	static function crypto_hmacSHA1($key, $data, $blocksize = 64) {
		if (strlen($key) > $blocksize) $key = pack('H*', sha1($key));
		$key = str_pad($key, $blocksize, chr(0x00));
		$ipad = str_repeat(chr(0x36), $blocksize);
		$opad = str_repeat(chr(0x5c), $blocksize);
		$hmac = pack( 'H*', sha1(
		($key ^ $opad) . pack( 'H*', sha1(
		  ($key ^ $ipad) . $data
		))
	  ));
		return base64_encode($hmac);
	}

	/**
	* Create temporary URLs to your protected Amazon S3 files.
	*
	* @param string $accessKey Your Amazon S3 access key
	* @param string $secretKey Your Amazon S3 secret key
	* @param string $bucket The bucket (bucket.s3.amazonaws.com)
	* @param string $path The target file path
	* @param int $expires In minutes
	* @return string Temporary Amazon S3 URL
	* @see http://awsdocs.s3.amazonaws.com/S3/20060301/s3-dg-20060301.pdf
	*/
	static function getTemporaryLink($accessKey, $secretKey, $bucket, $path, $expires = 720) {		//720 = 12 hour expiration
	  // Calculate expiry time
	  $expires = time() + intval(floatval($expires) * 60);
	  // Fix the path; encode and sanitize
	  $path = str_replace('%2F', '/', rawurlencode($path = ltrim($path, '/')));
	  //set content disposition
	  $path .= "?response-content-disposition=attachment";
	  // Path for signature starts with the bucket
	  $signpath = '/'. $bucket .'/'. $path;
	  // S3 friendly string to sign
	  $signsz = implode("\n", $pieces = array('GET', null, null, $expires, $signpath));
	  // Calculate the hash
	  $signature = PMProMediaAmazon::crypto_hmacSHA1($secretKey, $signsz);
	  // Glue the URL ...
	  $url = sprintf('http://s3.amazonaws.com/%s/%s', $bucket, $path);
	  // ... to the query string ...
	  $qs = http_build_query($pieces = array(
		'AWSAccessKeyId' => $accessKey,
		'Expires' => $expires,
		'Signature' => $signature,
	  ));
	  // ... and return the URL!
	  return $url.'&'.$qs;
	}
}

PMProMediaAmazon::get_instance();