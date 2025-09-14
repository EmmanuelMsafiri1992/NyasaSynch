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

use App\Helpers\Date;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\VonageMessage;
use Illuminate\Notifications\Notification;
use App\Models\Post;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;

class PostArchived extends Notification
{
	use Queueable;
	
	protected Post $post;
	protected int $archivedPostsExpiration;
	
	protected string $todayDateFormatted;
	protected string $willBeDeletedAtFormatted;
	
	public function __construct(Post $post, $archivedPostsExpiration)
	{
		$this->post = $post;
		$this->archivedPostsExpiration = (int)$archivedPostsExpiration;
		
		// Get timezone
		$tz = Date::getAppTimeZone();
		
		// Get today date
		$this->todayDateFormatted = Date::format(now($tz));
		
		// Get delete date
		$willBeDeletedAt = $this->post->archived_at->addDays($this->archivedPostsExpiration);
		$this->willBeDeletedAtFormatted = Date::format($willBeDeletedAt);
	}
	
	public function via($notifiable)
	{
		// Is email can be sent?
		$emailNotificationCanBeSent = (
			config('settings.mail.confirmation') == '1'
			&& !empty($this->post->email)
			&& !empty($this->post->archived_at)
		);
		
		// Is SMS can be sent in addition?
		$smsNotificationCanBeSent = (
			config('settings.sms.enable_phone_as_auth_field') == '1'
			&& config('settings.sms.confirmation') == '1'
			&& $this->post->auth_field == 'phone'
			&& !empty($this->post->phone)
			&& !empty($this->post->archived_at)
			&& !isDemoDomain()
		);
		
		if ($emailNotificationCanBeSent) {
			return ['mail'];
		}
		
		if ($smsNotificationCanBeSent) {
			if (config('settings.sms.driver') == 'twilio') {
				return [TwilioChannel::class];
			}
			
			return ['vonage'];
		}
		
		return [];
	}
	
	public function toMail($notifiable)
	{
		// Get Repost URL
		$path = 'account/posts/archived/' . $this->post->id . '/repost';
		$repostUrl = (config('plugins.domainmapping.installed'))
			? dmUrl($this->post->country_code, $path)
			: url($path);
		
		return (new MailMessage)
			->subject(trans('mail.post_archived_title', ['title' => $this->post->title]))
			->greeting(trans('mail.post_archived_content_1'))
			->line(trans('mail.post_archived_content_2', [
				'title'   => $this->post->title,
				'now'     => $this->todayDateFormatted,
				'appName' => config('app.name'),
			]))
			->line(trans('mail.post_archived_content_3', ['repostUrl' => $repostUrl]))
			->line(trans('mail.post_archived_content_4', [
				'willBeDeletedAt' => $this->willBeDeletedAtFormatted,
				'willBeDeletedAt'         => $this->willBeDeletedAtFormatted, // @note: need to be removed
			]))
			->line(trans('mail.post_archived_content_5'))
			->line('<br>')
			->line(trans('mail.post_archived_content_6'))
			->salutation(trans('mail.footer_salutation', ['appName' => config('app.name')]));
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
		return trans('sms.post_archived_content', [
			'appName'         => config('app.name'),
			'title'           => $this->post->title,
			'willBeDeletedAt' => $this->willBeDeletedAtFormatted,
			'willBeDeletedAt'         => $this->willBeDeletedAtFormatted, // @note: need to be removed
		]);
	}
}
