=== PMPro Media ===
Contributors: strangerstudios
Tags: video, audio, amazon s3, s3, jwplayer, media, pmpro, protect, downloads, post
Requires at least: 3.5
Tested up to: 4.0
Stable tag: .1.1

Protect media files with Paid Memberships Pro.

== Description ==

This plugin will swap out links to media files, e.g. an Amazon S3 file URL, with protected URLs that will first check if the user has access to the file before delivering it.

This plugin was developed to work with Amazon S3 and JWPlayer in particular.

PMpro Media uses some thoughts and code borrowed from the TYT Premium Media plugin by tjenkins@nerdery.com that integrated TYTNetworks with s2 Member

PMPro Media uses some GPL code from the s2member plugin. (includes/s2functions.php).

== Changelog ==
= .1.1 =
* Added pmpro_media_getmedia_url and using it in the Amazon class to swap the full video URL for a temporary URL using API keys/etc.

= .1 =
* Initial committ.