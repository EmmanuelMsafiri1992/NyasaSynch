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

namespace App\Http\Requests\Install;

use App\Helpers\DBTool;
use App\Http\Requests\Request;
use App\Rules\AlphaPlusRule;

class DatabaseInfoRequest extends Request
{
	private ?string $connexionErrorMessage = null;
	
	/**
	 * Prepare the data for validation.
	 *
	 * @return void
	 */
	protected function prepareForValidation(): void
	{
		$input = $this->all();
		
		// prefix
		$input['prefix'] = getAsString($this->input('prefix'));
		$input['prefix'] = strtolower($input['prefix']);
		
		request()->merge($input); // Required!
		$this->merge($input);
	}
	
	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array
	 */
	public function rules(): array
	{
		$rules = [
			'host'     => ['required'],
			'port'     => ['required', 'integer'],
			'socket'   => ['nullable'],
			'database' => ['required'],
			'prefix'   => ['nullable', new AlphaPlusRule('_')],
			'username' => ['required'],
		];
		
		if ($this->filled('socket')) {
			$rules['host'] = [];
			$rules['port'] = ['nullable', 'integer'];
		}
		
		// Get the required fields
		$requiredFields = collect($rules)->filter(function ($rule) {
			if (is_array($rule)) {
				return in_array('required', $rule);
			} else if (is_string($rule)) {
				return str_contains($rule, 'required');
			}
			
			return false;
		})->keys();
		
		// Check if required fields are not empty in the request
		$emptyRequiredFields = $requiredFields->filter(fn ($item) => empty($this->input($item)));
		
		// Validate the database connexion (with the given parameters)
		if ($emptyRequiredFields->isEmpty()) {
			// Initial request with host & port
			$inputData = $this->all();
			
			// Note: The MySQL Unix socket (shouldn't be used with host or port)
			// More Info: https://www.php.net/manual/en/ref.pdo-mysql.connection.php
			$unsetKeys = ['socket'];
			foreach ($unsetKeys as $key) {
				if (array_key_exists($key, $inputData)) {
					unset($inputData[$key]);
				}
			}
			
			// Try to get PDO connexion
			try {
				$pdo = DBTool::getPdoConnection($inputData);
				$this->connexionErrorMessage = null;
			} catch (\Throwable $e) {
				$this->connexionErrorMessage = $e->getMessage();
			}
			
			// If socket is filled,
			// Retry request with socket if the initial connection failed
			if ($this->filled('socket')) {
				if (!empty($this->connexionErrorMessage)) {
					$inputData = $this->all();
					
					// Note: The MySQL Unix socket (shouldn't be used with host or port)
					// More Info: https://www.php.net/manual/en/ref.pdo-mysql.connection.php
					$keysToUnset = ['host', 'port'];
					foreach ($keysToUnset as $key) {
						if (array_key_exists($key, $inputData)) {
							unset($inputData[$key]);
						}
					}
					
					// Try to get PDO connexion
					try {
						$pdo = DBTool::getPdoConnection($inputData);
						$this->connexionErrorMessage = null;
					} catch (\Throwable $e) {
						$this->connexionErrorMessage = $e->getMessage();
					}
				}
			}
			
			// Set validation rule if there is a connection error
			if (!empty($this->connexionErrorMessage)) {
				$rules = array_merge($rules, ['valid_database_connection' => 'required']);
			} else {
				// Replace the request's inputs related to the successful connection
				// Note: They will be used in the .env file creation or update process
				request()->request->replace($inputData);
				$this->request->replace($inputData);
			}
		}
		
		return $rules;
	}
	
	/**
	 * @return array
	 */
	public function messages(): array
	{
		$messages = [];
		
		if (!empty($this->connexionErrorMessage)) {
			$messages['valid_database_connection.required'] = $this->connexionErrorMessage;
		}
		
		return array_merge(parent::messages(), $messages);
	}
	
	/**
	 * @return array
	 */
	public function attributes(): array
	{
		$attributes = [
			'host'     => mb_strtolower(trans('messages.database_host')),
			'port'     => mb_strtolower(trans('messages.database_port')),
			'socket'   => mb_strtolower(trans('messages.database_socket')),
			'database' => mb_strtolower(trans('messages.database_name')),
			'prefix'   => mb_strtolower(trans('messages.database_tables_prefix')),
			'username' => mb_strtolower(trans('messages.database_username')),
			'password' => mb_strtolower(trans('messages.database_password')),
		];
		
		return array_merge(parent::attributes(), $attributes);
	}
}
