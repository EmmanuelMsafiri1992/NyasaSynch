<?php

return [
	
	// payment_sent
	'payment_sent_title'             => 'Merci d\'avoir choisi le paiement hors ligne!',
	'payment_sent_content_1'         => 'Bonjour,',
	'payment_sent_content_2'         => 'Nous avons bien reçu votre demande pour le payment hors ligne concernant l\'annonce "<a href=":postUrl">:title</a>".',
	'payment_sent_content_3'         => 'Nous allons attendre de recevoir votre paiement pour traiter votre demande.',
	'payment_sent_content_4'         => 'Merci !',
	'payment_sent_content_5'         => 'Suivez les informations ci-dessous pour effectuer le paiement:
<br><strong>Motif de paiement:</strong> Annonce #:adId - :packageName
<br><strong>Montant:</strong> :amount :currency
<br><br>:paymentMethodDescription',
	
	
	// payment_notification
	'payment_notification_title'     => 'Nouvelle demande de paiement hors ligne',
	'payment_notification_content_1' => 'Bonjour Admin,',
	'payment_notification_content_2' => 'L\'utilisateur :advertiserName vient de faire une demande de paiement hors ligne pour son annonce "<a href=":postUrl">:title</a>".',
	'payment_notification_content_3' => 'DETAILS DU PAIEMENT
<br><strong>Motif du paiement:</strong> Annonce #:adId - :packageName
<br><strong>Montant:</strong> :amount :currency
<br><strong>Moyen de paiement:</strong> :paymentMethodName',
	'payment_notification_content_4' => '<strong>NOTE:</strong> Après avoir encaissé le montant du paiement hors ligne, vous devez approuver manuellement le paiement dans l\'Admin panel -> Payments -> List -> (Recherchez le "Motif du paiement" en utilisant l\'ID de l\'annonce et cocher la case "Approuvé").',
	
];
