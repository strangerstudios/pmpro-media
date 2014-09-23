<?php
/*
	Helper functions for Amazon S3.
	Modified from http://css-tricks.com/snippets/php/generate-expiring-amazon-s3-link/
*/

//get a signed URL for Cloudfront (modified from: http://flowplayer.blacktrash.org/cfxsigned.php)
function pmpropm_get_signed_url($file, $expires = 720)			//720 = 12 hour default now
{
	//set expiration $expires minutes from now
	$expires = $expires*60 + time();
	
	//get private key (defined at top of this file)
	$key = openssl_get_privatekey(pmpropm_s2_key_fix_wrappers(PMPRORM_PRIVATE_KEY));
		
	//sign the policy with the private key
	openssl_sign(
		'{"Statement":[{"Resource":"' . $file . '","Condition":{"DateLessThan":{"AWS:EpochTime":' . $expires . '}}}]}',
		$signed_policy, 
		$key
	);
	openssl_free_key($key);
		
	//create url safe signed policy
	$base64_signed_policy = base64_encode($signed_policy);
	$signature = str_replace(array('+', '=', '/'), array('-', '_', '~'), $base64_signed_policy);
	
	// construct the url
	$url = $file . '?Expires=' . $expires . '&Signature=' . $signature . '&Key-Pair-Id=' . PMPRORM_KEY_PAIR_ID;

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
function pmpropm_crypto_hmacSHA1($key, $data, $blocksize = 64) {
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
function pmpropm_s3_getTemporaryLink($accessKey, $secretKey, $bucket, $path, $expires = 720) {		//720 = 12 hour expiration
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
  $signature = pmpropm_crypto_hmacSHA1($secretKey, $signsz);
  // Glue the URL ...
  $url = sprintf('http://%s.s3.amazonaws.com/%s', $bucket, $path);
  // ... to the query string ...
  $qs = http_build_query($pieces = array(
	'AWSAccessKeyId' => $accessKey,
	'Expires' => $expires,
	'Signature' => $signature,
  ));
  // ... and return the URL!
  return $url.'&'.$qs;
}