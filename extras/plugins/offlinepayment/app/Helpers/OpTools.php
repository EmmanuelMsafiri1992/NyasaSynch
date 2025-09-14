<?php

namespace extras\plugins\offlinepayment\app\Helpers;

use App\Models\Package;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Post;

class OpTools
{
	/**
	 * Ajax Checkbox Display
	 *
	 * @param $id
	 * @param $table
	 * @param $field
	 * @param null $fieldValue
	 * @return string
	 */
	public static function featuredCheckboxDisplay($id, $table, $field, $fieldValue = null)
	{
		$lineId = $field . $id;
		$lineId = str_replace('.', '', $lineId); // fix JS bug (in admin layout)
		$data = 'data-table="' . $table . '"
			data-field="' . $field . '"
			data-line-id="' . $lineId . '"
			data-id="' . $id . '"
			data-value="' . (isset($fieldValue) ? $fieldValue : 0) . '"';
		
		// Get the listing's latest current valid payment
		$latestValidPayment = self::getLatestCurrentValidPayment($id);
		$validPaymentExists = (!empty($latestValidPayment));
		$isNotBlankPayment = ($validPaymentExists && $latestValidPayment->transaction_id != 'featured');
		
		// Decoration
		if (isset($fieldValue) && $fieldValue == 1 && $validPaymentExists) {
			$html = '<i id="' . $lineId . '" class="admin-single-icon fa fa-toggle-on" aria-hidden="true"></i>';
			if ($isNotBlankPayment) {
				return $html;
			}
		} else {
			$html = '<i id="' . $lineId . '" class="admin-single-icon fa fa-toggle-off" aria-hidden="true"></i>';
		}
		
		$html = '<a href="" class="ajax-request" ' . $data . '>' . $html . '</a>';
		
		return $html;
	}
	
	/**
	 * Get the listing's latest current valid payment
	 *
	 * @param $postId
	 * @return mixed
	 */
	public static function getLatestCurrentValidPayment($postId)
	{
		$latestValidPayment = Payment::where('post_id', $postId)
			->where('active', 1)
			->orderBy('id', 'DESC')
			->first();
		
		return $latestValidPayment;
	}
	
	/**
	 * Feature the Post
	 * This will create a blank payment for the Post
	 *
	 * @param \App\Models\Post $post
	 * @return bool
	 */
	public static function createFeatured(Post $post)
	{
		// Check the Post
		if (empty($post)) {
			return false;
		}
		
		// Get the cheapest package
		$package = Package::orderBy('price', 'ASC')->first();
		
		// Get the OfflinePayment data
		$paymentMethod = PaymentMethod::where('name', 'offlinepayment')->first();
		
		try {
			// Save a blank payment
			$paymentInfo = [
				'post_id'           => $post->id,
				'package_id'        => (!empty($package)) ? $package->id : 0,
				'payment_method_id' => (!empty($paymentMethod)) ? $paymentMethod->id : 0,
				'transaction_id'    => 'featured',
				'amount'            => 0,
				'active'            => 1,
			];
			$payment = new Payment($paymentInfo);
			$payment->save();
			
			// Update listing's 'reviewed' & 'featured' fields
			$post->reviewed_at = now();
			$post->featured = 1;
			$post->save();
		} catch (\Throwable $e) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * Don't feature the Post
	 * This will remove the Post's blank payments
	 *
	 * @param \App\Models\Post $post
	 * @return bool
	 */
	public static function deleteFeatured(Post $post)
	{
		// Check the Post
		if (empty($post)) {
			return false;
		}
		
		try {
			// Get featured payments
			$payments = Payment::where('post_id', $post->id)->where('transaction_id', 'featured')->get();
			if ($payments->count() > 0) {
				foreach ($payments as $payment) {
					$payment->delete();
				}
			}
			
			// Update listing's 'featured' fields
			$post->featured = 0;
			$post->save();
		} catch (\Throwable $e) {
			return false;
		}
		
		return true;
	}
}
