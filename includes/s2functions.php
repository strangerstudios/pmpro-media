<?php
/*
	Functions borrowed from s2members
*/

/**
* Fixes incomplete private key wrappers for RSA-SHA1 signing.
*
* Used by {@link s2Member\Utilities\c_ws_plugin__s2member_utils_strings::rsa_sha1_sign()}.
*
* @package s2Member\Utilities
* @since 111017
*
* @param str $key The secret key to be used in an RSA-SHA1 signature.
* @return str Key with incomplete wrappers corrected, when/if possible.
*
* @see http://www.faqs.org/qa/qa-14736.html
*/
//public static function _rsa_sha1_key_fix_wrappers($key = FALSE)
function pmpropm_s2_key_fix_wrappers($key = FALSE)
{
	if(($key = trim((string)$key)) && (strpos($key, "-----BEGIN RSA PRIVATE KEY-----") === false || strpos($key, "-----END RSA PRIVATE KEY-----") === false))
		{
			foreach(($lines = pmpropm_s2_trim_deep(preg_split("/[\r\n]+/", $key))) as $line => $value)
				if(strpos($value, "-") === 0) // Begins with a boundary identifying character ( a hyphen `-` )?
					{
						$boundaries = (empty($boundaries)) ? 1 : $boundaries + 1; // Counter.
						unset($lines[$line]); // Remove this boundary line. We'll fix these below.
					}
			if(empty($boundaries) || $boundaries <= 2) // Do NOT modify keys with more than 2 boundaries.
				$key = "-----BEGIN RSA PRIVATE KEY-----\n".implode("\n", $lines)."\n-----END RSA PRIVATE KEY-----";
		}
	return $key; // Always a trimmed string here.
}

/**
* Trims deeply; or use {@link s2Member\Utilities\c_ws_plugin__s2member_utils_strings::trim()}.
*
* @package s2Member\Utilities
* @since 3.5
*
* @see s2Member\Utilities\c_ws_plugin__s2member_utils_strings::trim()
* @see http://php.net/manual/en/function.trim.php
*
* @param str|array $value Either a string, an array, or a multi-dimensional array, filled with integer and/or string values.
* @param str|bool $chars Optional. Defaults to false, indicating the default trim chars ` \t\n\r\0\x0B`. Or, set to a specific string of chars.
* @param str|bool $extra_chars Optional. This is NOT possible with PHP alone, but here you can specify extra chars; in addition to ``$chars``.
* @return str|array Either the input string, or the input array; after all data is trimmed up according to arguments passed in.
*/
function pmpropm_s2_trim_deep($value = FALSE, $chars = FALSE, $extra_chars = FALSE)
{
	$chars = /* List of chars to be trimmed by this routine. */ (is_string($chars)) ? $chars : " \t\n\r\0\x0B";
	$chars = (is_string($extra_chars) /* Adding additional chars? */) ? $chars.$extra_chars : $chars;

	if(is_array($value)) /* Handles all types of arrays.
Note, we do NOT use ``array_map()`` here, because multiple args to ``array_map()`` causes a loss of string keys.
For further details, see: <http://php.net/manual/en/function.array-map.php>. */
		{
			foreach($value as &$r) // Reference.
				$r = pmpropm_s2_trim_deep($r, $chars);
			return $value; // Return modified array.
		}
	return trim((string)$value, $chars);
}