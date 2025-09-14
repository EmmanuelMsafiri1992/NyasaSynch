<?php

namespace extras\plugins\offlinepayment\app\Notifications;

use App\Helpers\UrlGen;
use App\Models\Package;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\VonageMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Post;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;

class PaymentSent extends Notification implements ShouldQueue
{
	use Queueable;
	
	protected $payment;
	protected $post;
	
	protected $package;
	protected $paymentMethod;
	
	public function __construct(Payment $payment, Post $post)
	{
		$this->payment = $payment;
		$this->post = $post;
		
		$this->package = Package::find($payment->package_id);
		$this->paymentMethod = PaymentMethod::find($payment->payment_method_id);
	}
	
	public function via($notifiable)
	{
		if (!empty($this->post->email)) {
			return ['mail'];
		} else {
			if (config('settings.sms.driver') == 'twilio') {
				return [TwilioChannel::class];
			}
			
			return ['vonage'];
		}
	}
	
	public function toMail($notifiable)
	{
		$postUrl = UrlGen::post($this->post);
		
		return (new MailMessage)
			->subject(trans('offlinepayment::mail.payment_sent_title'))
			->greeting(trans('offlinepayment::mail.payment_sent_content_1'))
			->line(trans('offlinepayment::mail.payment_sent_content_2', [
				'postUrl' => $postUrl,
				'title'   => $this->post->title,
			]))
			->line(trans('offlinepayment::mail.payment_sent_content_3'))
			->line(trans('offlinepayment::mail.payment_sent_content_4'))
			->line(trans('offlinepayment::mail.payment_sent_content_5', [
				'adId'                     => $this->post->id,
				'packageName'              => (!empty($this->package->short_name)) ? $this->package->short_name : $this->package->name,
				'amount'                   => $this->package->price,
				'currency'                 => $this->package->currency_code,
				'paymentMethodDescription' => $this->paymentMethod->description,
			]));
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
		return trans('offlinepayment::sms.payment_sent_content', ['appName' => config('app.name'), 'title' => $this->post->title]);
	}
}
