<?php

namespace extras\plugins\offlinepayment;

use App\Helpers\Number;
use App\Http\Resources\PaymentResource;
use App\Models\Permission;
use App\Models\Post;
use App\Models\PaymentMethod;
use App\Models\User;
use extras\plugins\offlinepayment\app\Notifications\PaymentNotification;
use extras\plugins\offlinepayment\app\Notifications\PaymentSent;
use Illuminate\Http\Request;
use App\Helpers\Payment;
use App\Models\Package;
use App\Models\Payment as PaymentModel;
use Illuminate\Support\Facades\Notification;

class Offlinepayment extends Payment
{
	/**
	 * Send Payment
	 *
	 * @param \Illuminate\Http\Request $request
	 * @param \App\Models\Post|\App\Models\User $payable
	 * @param array $resData
	 * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
	 */
	public static function sendPayment(Request $request, Post|User $payable, array $resData = [])
	{
		// Messages
		self::$msg['checkout']['success'] = trans('offlinepayment::messages.We have received your offline payment request.') . ' ' .
			trans('offlinepayment::messages.We will wait to receive your payment to process your request.');
		
		// Set the right URLs
		parent::setRightUrls($resData);
		
		// Get the Package
		$package = Package::find($request->input('package_id'));
		
		// Don't make a payment if 'price' = 0 or null
		if (empty($package) || $package->price <= 0) {
			$message = 'Package doesn\'t exist or its price is <= 0.';
			
			if (isFromApi()) {
				$resData['extra']['payment']['message'] = $message;
				$resData['extra']['payment']['result'] = null;
				$resData['extra']['previousUrl'] = parent::$uri['previousUrl'];
				$resData['extra']['nextUrl'] = parent::$uri['nextUrl'];
				
				return self::apiResponse($resData);
			} else {
				flash($message)->error();
				
				return redirect(parent::$uri['previousUrl'] . '?error=package')->withInput();
			}
		}
		
		// API Parameters
		$params = [
			'cancelUrl'         => parent::$uri['paymentCancelUrl'],
			'returnUrl'         => parent::$uri['paymentReturnUrl'],
			'payment_method_id' => $request->input('payment_method_id'),
			'post_id'           => $payable->id,
			'package_id'        => $package->id,
			'name'              => $package->name,
			'description'       => trans('offlinepayment::messages.Listing') . ' #' . $payable->id . ' - ' . $package->name,
			'amount'            => Number::toFloat($package->price),
			'currency'          => $package->currency_code,
		];
		
		// Save the Payment in database
		$resData = self::register($payable, $params, $resData);
		
		if (isFromApi()) {
			
			return self::apiResponse($resData);
			
		} else {
			
			if (data_get($resData, 'extra.payment.success')) {
				flash(data_get($resData, 'extra.payment.message'))->success();
			} else {
				flash(data_get($resData, 'extra.payment.message'))->error();
			}
			
			if (data_get($resData, 'success')) {
				session()->flash('message', data_get($resData, 'message'));
				
				return redirect(self::$uri['nextUrl']);
			} else {
				// Maybe never called
				return redirect(self::$uri['nextUrl'])->withErrors(['error' => data_get($resData, 'message')]);
			}
			
		}
	}
	
	/**
	 * Save the payment and Send payment confirmation email
	 *
	 * @param \App\Models\Post|\App\Models\User $payable
	 * @param array $params
	 * @param array $resData
	 * @return array|mixed
	 * @throws \Psr\Container\ContainerExceptionInterface
	 * @throws \Psr\Container\NotFoundExceptionInterface
	 */
	public static function register(Post|User $payable, array $params, array $resData = [])
	{
		$request = request();
		
		// Update payable model fields
		if ($payable instanceof Post) {
			$payable->reviewed_at = (!empty($payable->reviewed_at)) ? now() : null;
			$payable->featured = ($payable->featured == 1) ? 1 : 0;
			$payable->save();
		}
		
		// Save the payment
		$paymentInfo = [
			'payable_id'        => $payable->id,
			'payable_type'      => get_class($payable),
			'package_id'        => $params['package_id'],
			'payment_method_id' => $params['payment_method_id'],
			'transaction_id'    => $params['transaction_id'] ?? null,
			'amount'            => $params['amount'] ?? 0,
			'active'            => 0,
		];
		
		// Backward compatibility for post_id
		if ($payable instanceof Post) {
			$paymentInfo['post_id'] = $payable->id;
		}
		$payment = new PaymentModel($paymentInfo);
		$payment->save();
		
		$resData['extra']['payment']['success'] = true;
		$resData['extra']['payment']['message'] = self::$msg['checkout']['success'];
		$resData['extra']['payment']['result'] = (new PaymentResource($payment))->toArray($request);
		
		// SEND EMAILS
		
		// Send Payment Email Notifications
		if (config('settings.mail.payment_notification') == 1) {
			// Send Confirmation Email
			try {
				if ($payable instanceof Post) {
					$payable->notify(new PaymentSent($payment, $payable));
				}
			} catch (\Throwable $e) {
				// Not Necessary To Notify
			}
			
			// Send to Admin the Payment Notification Email
			try {
				$admins = User::permission(Permission::getStaffPermissions())->get();
				if ($admins->count() > 0) {
					Notification::send($admins, new PaymentNotification($payment, $payable));
				}
			} catch (\Throwable $e) {
				// Not Necessary To Notify
			}
		}
		
		return $resData;
	}
	
	/**
	 * @return array
	 */
	public static function getOptions(): array
	{
		$options = [];
		
		$paymentMethod = PaymentMethod::active()->where('name', 'offlinepayment')->first();
		if (!empty($paymentMethod)) {
			$options[] = (object)[
				'name'     => mb_ucfirst(trans('admin.settings')),
				'url'      => admin_url('payment_methods/' . $paymentMethod->id . '/edit'),
				'btnClass' => 'btn-info',
			];
		}
		
		return $options;
	}
	
	/**
	 * @return bool
	 */
	public static function installed(): bool
	{
		$cacheExpiration = 86400; // Cache for 1 day (60 * 60 * 24)
		
		return cache()->remember('plugins.offlinepayment.installed', $cacheExpiration, function () {
			$paymentMethod = PaymentMethod::active()->where('name', 'offlinepayment')->first();
			if (empty($paymentMethod)) {
				return false;
			}
			
			return true;
		});
	}
	
	/**
	 * @return bool
	 */
	public static function install(): bool
	{
		// Remove the plugin entry
		self::uninstall();
		
		// Plugin data
		$data = [
			'id'                => 5,
			'name'              => 'offlinepayment',
			'display_name'      => 'Offline Payment',
			'description'       => null,
			'has_ccbox'         => 0,
			'is_compatible_api' => 1,
			'lft'               => 5,
			'rgt'               => 5,
			'depth'             => 1,
			'active'            => 1,
		];
		
		try {
			// Create plugin data
			$paymentMethod = PaymentMethod::create($data);
			if (empty($paymentMethod)) {
				return false;
			}
		} catch (\Throwable $e) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * @return bool
	 */
	public static function uninstall(): bool
	{
		try {
			cache()->forget('plugins.offlinepayment.installed');
		} catch (\Throwable $e) {
		}
		
		$uninstalled = false;
		
		$paymentMethod = PaymentMethod::where('name', 'offlinepayment')->first();
		if (!empty($paymentMethod)) {
			$deleted = $paymentMethod->delete();
			if ($deleted > 0) {
				$uninstalled = true;
			}
		}
		
		if ($uninstalled) {
			try {
				$payments = PaymentModel::where('transaction_id', 'featured');
				if ($payments->count() > 0) {
					foreach ($payments->cursor() as $payment) {
						$post = Post::find($payment->post_id);
						if (!empty($post)) {
							$post->featured = 0;
							$post->save();
						}
						
						$payment->delete();
					}
				}
			} catch (\Throwable $e) {
			}
		}
		
		return $uninstalled;
	}
}
