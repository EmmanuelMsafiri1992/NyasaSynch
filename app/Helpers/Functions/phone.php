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

use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;

/**
 * Check if a phone number is a valid mobile number for a given country
 *
 * @param string|null $phone
 * @param string|null $countryCode
 * @return bool
 */
function isValidMobileNumber(?string $phone, ?string $countryCode = null): bool
{
	if (empty($phone) || empty($countryCode)) return false;
	$phone = normalizePhoneNumber($phone, $countryCode);
	
	try {
		$phoneUtil = PhoneNumberUtil::getInstance();
		$phoneObj = $phoneUtil->parse($phone, $countryCode);
		
		$isValid = (
			$phoneUtil->isValidNumberForRegion($phoneObj, $countryCode)
			&& (
				$phoneUtil->getNumberType($phoneObj) === PhoneNumberType::MOBILE
				|| $phoneUtil->getNumberType($phoneObj) === PhoneNumberType::FIXED_LINE_OR_MOBILE
			)
		);
	} catch (\Throwable $e) {
		$isValid = false;
	}
	
	return $isValid;
}

/**
 * Check if a phone number is a possible mobile number for a given country
 *
 * @param string|null $phone
 * @param string|null $countryCode
 * @return bool
 */
function isPossibleMobileNumber(?string $phone, ?string $countryCode = null): bool
{
	if (empty($phone) || empty($countryCode)) return false;
	$phone = normalizePhoneNumber($phone, $countryCode);
	
	try {
		$phoneUtil = PhoneNumberUtil::getInstance();
		$phoneObj = $phoneUtil->parse($phone, $countryCode);
		
		$isPossibleNumber = (
			(
				$phoneUtil->isPossibleNumber($phoneObj)
				|| $phoneUtil->isValidNumberForRegion($phoneObj, $countryCode)
			)
			&& (
				$phoneUtil->getNumberType($phoneObj) === PhoneNumberType::MOBILE
				|| $phoneUtil->getNumberType($phoneObj) === PhoneNumberType::FIXED_LINE_OR_MOBILE
			)
		);
	} catch (\Throwable $e) {
		$isPossibleNumber = false;
	}
	
	return $isPossibleNumber;
}

/**
 * Check if a phone number is valid for a given country
 *
 * @param string|null $phone
 * @param string|null $countryCode
 * @return bool
 */
function isValidPhoneNumber(?string $phone, ?string $countryCode = null): bool
{
	if (empty($phone) || empty($countryCode)) return false;
	$phone = normalizePhoneNumber($phone, $countryCode);
	
	try {
		$phoneUtil = PhoneNumberUtil::getInstance();
		$phoneObj = $phoneUtil->parse($phone, $countryCode);
		
		$isValid = (
			$phoneUtil->isValidNumberForRegion($phoneObj, $countryCode)
			&& (
				$phoneUtil->getNumberType($phoneObj) === PhoneNumberType::MOBILE
				|| $phoneUtil->getNumberType($phoneObj) === PhoneNumberType::FIXED_LINE_OR_MOBILE
				|| $phoneUtil->getNumberType($phoneObj) === PhoneNumberType::FIXED_LINE
				|| $phoneUtil->getNumberType($phoneObj) === PhoneNumberType::UNKNOWN
			)
		);
	} catch (\Throwable $e) {
		$isValid = false;
	}
	
	return $isValid;
}

/**
 * Check if a phone number is a possible phone number for a given country
 *
 * @param string|null $phone
 * @param string|null $countryCode
 * @return bool
 */
function isPossiblePhoneNumber(?string $phone, ?string $countryCode = null): bool
{
	if (empty($phone) || empty($countryCode)) return false;
	$phone = normalizePhoneNumber($phone, $countryCode);
	
	try {
		$phoneUtil = PhoneNumberUtil::getInstance();
		$phoneObj = $phoneUtil->parse($phone, $countryCode);
		
		$isPossibleNumber = (
			(
				$phoneUtil->isPossibleNumber($phoneObj)
				|| $phoneUtil->isValidNumberForRegion($phoneObj, $countryCode)
			)
			&& (
				$phoneUtil->getNumberType($phoneObj) === PhoneNumberType::MOBILE
				|| $phoneUtil->getNumberType($phoneObj) === PhoneNumberType::FIXED_LINE_OR_MOBILE
				|| $phoneUtil->getNumberType($phoneObj) === PhoneNumberType::FIXED_LINE
				|| $phoneUtil->getNumberType($phoneObj) === PhoneNumberType::UNKNOWN
			)
		);
	} catch (\Throwable $e) {
		$isPossibleNumber = false;
	}
	
	return $isPossibleNumber;
}

/**
 * Get Phone's National Format
 *
 * Example: BE: 012/34.56.78 => 012 34 56 78
 *
 * @param string|null $phone
 * @param string|null $countryCode
 * @return string|null
 */
function phoneNational(?string $phone, ?string $countryCode = null): ?string
{
	$phone = normalizePhoneNumber($phone, $countryCode);
	
	try {
		$phoneUtil = PhoneNumberUtil::getInstance();
		$phoneObj = $phoneUtil->parse($phone, $countryCode);
		$phone = $phoneUtil->format($phoneObj, PhoneNumberFormat::NATIONAL);
	} catch (\Throwable $e) {
		// Keep the default value
	}
	
	return $phone;
}

/**
 * Get Phone's E164 Format
 *
 * https://en.wikipedia.org/wiki/E.164
 * https://www.twilio.com/docs/glossary/what-e164
 *
 * Example: BE: 012 34 56 78 => +3212345678
 *
 * @param string|null $phone
 * @param string|null $countryCode
 * @return string|null
 */
function phoneE164(?string $phone, ?string $countryCode = null): ?string
{
	$phone = normalizePhoneNumber($phone, $countryCode);
	
	try {
		$phoneUtil = PhoneNumberUtil::getInstance();
		$phoneObj = $phoneUtil->parse($phone, $countryCode);
		$phone = $phoneUtil->format($phoneObj, PhoneNumberFormat::E164);
	} catch (\Throwable $e) {
		// Keep the default value
	}
	
	return $phone;
}

/**
 * Get Phone's International Format
 * Don't need to be saved in database
 *
 * Example: BE: 012 34 56 78 => +32 12 34 56 78
 *
 * @param string|null $phone
 * @param string|null $countryCode
 * @return string|null
 */
function phoneIntl(?string $phone, ?string $countryCode = null): ?string
{
	$phone = normalizePhoneNumber($phone, $countryCode);
	
	try {
		$phoneUtil = PhoneNumberUtil::getInstance();
		$phoneObj = $phoneUtil->parse($phone, $countryCode);
		$phone = $phoneUtil->format($phoneObj, PhoneNumberFormat::INTERNATIONAL);
	} catch (\Throwable $e) {
		// Keep the default value
	}
	
	return $phone;
}

/**
 * Get an example phone number related to a country
 *
 * @param string|null $countryCode
 * @param string|null $type
 * @return string|null
 */
function getExamplePhoneNumber(?string $countryCode = null, ?string $type = 'MOBILE'): ?string
{
	$phone = null;
	
	try {
		$phoneUtil = PhoneNumberUtil::getInstance();
		
		$phoneObj = null;
		if (!empty($type)) {
			$constantName = '\libphonenumber\PhoneNumberType::' . $type;
			if (defined($constantName)) {
				$phoneNumberType = constant($constantName);
				$phoneObj = $phoneUtil->getExampleNumberForType($countryCode, $phoneNumberType);
			}
		} else {
			$phoneObj = $phoneUtil->getExampleNumber($countryCode);
		}
		
		if (!is_null($phoneObj)) {
			$phone = $phoneUtil->format($phoneObj, PhoneNumberFormat::NATIONAL);
		}
	} catch (\Throwable $e) {
	}
	
	return $phone;
}

/**
 * Get phone's normal format (i.e. With numbers only)
 *
 * Example:
 * - BE: 012/34.56.78 => 012345678
 * - DE: +49 15510 686794 => 004915510686794
 *
 * @param string|null $phone
 * @param string|null $countryCode
 * @return string
 */
function normalizePhoneNumber(?string $phone, ?string $countryCode = null): string
{
	$phone = trim(getAsString($phone));
	
	if (str_starts_with($phone, '00')) {
		$phone = str($phone)->replaceStart('00', '+')->toString();
	}
	/*
	if (str_starts_with($phone, '+')) {
		$phoneUtil = PhoneNumberUtil::getInstance();
		$callingCode = $phoneUtil->getCountryCodeForRegion($countryCode);
		if (!empty($callingCode)) {
			// $phone = str($phone)->replaceStart('+' . $callingCode, '')->trim()->toString();
		}
	}
	*/
	
	$phone = preg_replace('/\D+/', '', $phone);
	
	return getAsString($phone);
}

/**
 * @param string|null $phone
 * @param string|null $provider
 * @return string|null
 */
function setPhoneSign(?string $phone, ?string $provider = null): ?string
{
	$phone = getAsString($phone);
	
	if ($provider == 'vonage') {
		// Vonage doesn't support the sign '+'
		if (str_starts_with($phone, '+')) {
			$phone = str($phone)->replaceStart('+', '')->toString();
		}
	}
	
	if ($provider == 'twilio') {
		// Twilio requires the sign '+'
		if (!str_starts_with($phone, '+')) {
			$phone = '+' . $phone;
		}
	}
	
	if (!in_array($provider, ['vonage', 'twilio'])) {
		if (!str_starts_with($phone, '+')) {
			$phone = '+' . $phone;
		}
	}
	
	return ($phone == '+') ? '' : $phone;
}
