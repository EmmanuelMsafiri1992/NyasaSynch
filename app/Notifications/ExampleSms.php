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

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\VonageMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;

/*
 * Note: Implementing "Illuminate\Contracts\Queue\ShouldQueue"
 * allows Laravel to save mail sending as Queue in the database
 */

class ExampleSms extends Notification
{
	use Queueable;
	
	private ?string $driver;
	
	public function __construct(?string $driver = null)
	{
		$this->driver = !empty($driver) ? $driver : config('settings.sms.driver');
	}
	
	public function via($notifiable)
	{
		if (isDemoDomain()) {
			return [];
		}
		
		if ($this->driver == 'twilio') {
			return [TwilioChannel::class];
		}
		
		return ['vonage'];
	}
	
	public function toVonage($notifiable)
	{
		return (new VonageMessage())->content($this->smsMessage())->unicode();
	}
	
	public function toTwilio($notifiable)
	{
		return (new TwilioSmsMessage())->content($this->smsMessage());
	}
	
	protected function smsMessage()
	{
		return trans('sms.example_content', ['appName' => config('app.name')]);
	}
}
