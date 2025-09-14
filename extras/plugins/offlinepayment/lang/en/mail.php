<?php

return [
	
	// payment_sent
	'payment_sent_title'             => 'Thanks for choosing offline payment!',
	'payment_sent_content_1'         => 'Hello,',
	'payment_sent_content_2'         => 'We have received your offline payment request for the ad "<a href=":postUrl">:title</a>".',
	'payment_sent_content_3'         => 'We will wait to receive your payment to process your request.',
	'payment_sent_content_4'         => 'Thank you!',
	'payment_sent_content_5'         => 'Follow the information below to make the payment:
<br><strong>Reason for payment:</strong> Ad #:adId - :packageName
<br><strong>Amount:</strong> :amount :currency
<br><br>:paymentMethodDescription',
	
	
	// payment_notification
	'payment_notification_title'     => 'New offline payment request',
	'payment_notification_content_1' => 'Hello Admin,',
	'payment_notification_content_2' => 'The user :advertiserName has just made an offline payment request for her ad "<a href=":postUrl">:title</a>".',
	'payment_notification_content_3' => 'THE PAYMENT DETAILS
<br><strong>Reason of the payment:</strong> Ad #:adId - :packageName
<br><strong>Amount:</strong> :amount :currency
<br><strong>Payment Method:</strong> :paymentMethodName',
	'payment_notification_content_4' => '<strong>NOTE:</strong> After receiving the amount of the offline payment, you must manually approve the payment in the Admin panel -> Payments -> List -> (Search the "Reason of the payment" using the Ad ID and check the "Approved" checkbox).',
	
];
