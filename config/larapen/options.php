<?php
/*
 * Nyasajob - Job Board Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com/nyasajob
 * Author: BeDigit | https://bedigit.com
 *
 * LICENSE
 * -------
 * This software is furnished under a license and may be used and copied
 * only in accordance with the terms of such license and with the inclusion
 * of the above copyright notice. If you Purchased from CodeCanyon,
 * Please read the full License from here - https://codecanyon.net/licenses/standard
 */

return [
	
	// Cache Drivers
	'cache' => [
		'file'      => 'File (Default)',
		'array'     => 'None',
		'database'  => 'Database',
		'apc'       => 'APC',
		'memcached' => 'Memcached',
		'redis'     => 'Redis',
	],
	
	// Mail Drivers
	'mail'   => [
		'sendmail'   => 'Sendmail',
		'smtp'       => 'SMTP',
		'mailgun'    => 'Mailgun',
		'postmark'   => 'Postmark',
		'ses'        => 'Amazon SES',
		'sparkpost'  => 'Sparkpost',
		'resend'     => 'Resend',
		'mailersend' => 'MailerSend',
		// 'brevo'   => 'Brevo',
	],
	
	// SMS Drivers
	'sms' => [
		'vonage' => 'Vonage',
		'twilio' => 'Twilio',
	],
	
	// GeoIP Drivers
	'geoip' => [
		'ipinfo'           => 'ipinfo.io',
		'dbip'             => 'db-ip.com',
		'ipbase'           => 'ipbase.com',
		'ip2location'      => 'ip2location.com',
		'ipapi'            => 'ip-api.com', // No API Key
		'ipapico'          => 'ipapi.co',   // No API Key
		'ipgeolocation'    => 'ipgeolocation.io',
		'iplocation'       => 'iplocation.net',
		'ipstack'          => 'ipstack.com',
		'maxmind_api'      => 'maxmind.com (Web Services)',
		'maxmind_database' => 'maxmind.com (Database)', // No API Key (But need to download DB)
	],
	
	// WYSIWYG Editor
	'wysiwyg' => [
		'none'       => 'None',
		'tinymce'    => 'TinyMCE',
		'ckeditor'   => 'CKEditor',
		'summernote' => 'Summernote',
		'simditor'   => 'Simditor',
	],
	
	// Permalinks & Extensions
	'permalink' => [
		'post' => [
			'{slug}-{hashableId}',
			'{slug}/{hashableId}',
			'{slug}_{hashableId}',
			'{hashableId}-{slug}',
			'{hashableId}/{slug}',
			'{hashableId}_{slug}',
			'{hashableId}',
		],
	],
	'permalinkExt' => [
		'',
		'.html',
		'.htm',
		'.php',
		'.asp',
		'.aspx',
		'.jsp',
	],

];
