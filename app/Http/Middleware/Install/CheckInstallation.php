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

namespace App\Http\Middleware\Install;

trait CheckInstallation
{
	/**
	 * Check if the website has already been installed
	 *
	 * @return bool
	 * @throws \App\Exceptions\Custom\CustomException
	 */
	protected function isInstalled(): bool
	{
		if ($this->installationIsComplete()) {
			createTheInstalledFile(true);
			$this->clearInstallationSession();
		}
		
		// Check if the app is installed
		return appIsInstalled();
	}
	
	/**
	 * @return bool
	 * @throws \App\Exceptions\Custom\CustomException
	 */
	protected function isNotInstalled(): bool
	{
		return !$this->isInstalled();
	}
	
	/**
	 * Check if installation is processing
	 *
	 * @return bool
	 */
	protected function installationIsInProgress(): bool
	{
		return (
			!empty(session('databaseImported'))
			|| !empty(session('cronJobsInfoSeen'))
			|| !empty(session('installationCompleted'))
		);
	}
	
	/**
	 * @return bool
	 */
	protected function installationIsNotInProgress(): bool
	{
		return !$this->installationIsInProgress();
	}
	
	// PRIVATE
	
	/**
	 * Check if the installation is complete
	 * If the session contains "installationCompleted" which is equal to 1, this means that the website has just been installed.
	 *
	 * @return bool
	 */
	private function installationIsComplete(): bool
	{
		return (session('installationCompleted') == 1);
	}
	
	/**
	 * Clear the installation session
	 * Remove the "installationCompleted" key from the session
	 *
	 * @return void
	 */
	private function clearInstallationSession(): void
	{
		session()->forget('installationCompleted');
		session()->flush();
	}
}
