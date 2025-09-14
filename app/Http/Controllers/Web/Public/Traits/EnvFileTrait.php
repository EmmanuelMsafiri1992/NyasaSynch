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

namespace App\Http\Controllers\Web\Public\Traits;

use App\Helpers\DotenvEditor;

trait EnvFileTrait
{
	/**
	 * Check & Add the missing entries in the /.env file
	 *
	 * @return void
	 * @throws \App\Exceptions\Custom\CustomException
	 */
	public function checkDotEnvEntries(): void
	{
		if (!appInstallFilesExist()) {
			return;
		}
		
		$isChanged = false;
		
		// Check if HTTPS protocol is configured
		if (config('larapen.core.forceHttps')) {
			if (DotenvEditor::keyExists('APP_URL')) {
				$appUrl = DotenvEditor::getValue('APP_URL');
				if (!empty($appUrl) && is_string($appUrl)) {
					$httpProtocol = 'http://';
					$httpsProtocol = 'https://';
					if (!str_starts_with($appUrl, $httpsProtocol)) {
						$appUrl = str($appUrl)->lower()->remove($httpProtocol)->start($httpsProtocol)->toString();
						DotenvEditor::setKey('APP_URL', $appUrl);
						$isChanged = true;
					}
				}
			}
		}
		
		// Check the App Config Locale
		if (!DotenvEditor::keyExists('APP_LOCALE')) {
			DotenvEditor::setKey('APP_LOCALE', config('appLang.code'));
			$isChanged = true;
		}
		
		// Check Purchase Code
		if (!DotenvEditor::keyExists('PURCHASE_CODE')) {
			if (!empty(config('settings.app.purchase_code'))) {
				DotenvEditor::setKey('PURCHASE_CODE', config('settings.app.purchase_code'));
				$isChanged = true;
			}
		}
		
		// MySQL Dump Binary Path
		if (!DotenvEditor::keyExists('DB_DUMP_BINARY_PATH')) {
			if (DotenvEditor::keyExists('DB_DUMP_COMMAND_PATH')) {
				$dbDumpCommandPath = DotenvEditor::getValue('DB_DUMP_COMMAND_PATH');
				DotenvEditor::setKey('DB_DUMP_BINARY_PATH', $dbDumpCommandPath);
				DotenvEditor::deleteKey('DB_DUMP_COMMAND_PATH');
			} else {
				DotenvEditor::setKey('DB_DUMP_BINARY_PATH', '');
			}
			$isChanged = true;
		}
		
		// API Options
		if (!DotenvEditor::keyExists('APP_API_TOKEN')) {
			DotenvEditor::addEmpty();
			DotenvEditor::setKey('APP_API_TOKEN', generateApiToken());
			DotenvEditor::setKey('APP_HTTP_CLIENT', 'none');
			$isChanged = true;
		}
		
		if ($isChanged) {
			DotenvEditor::save();
		}
	}
}
