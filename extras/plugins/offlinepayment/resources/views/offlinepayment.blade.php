<div class="row payment-plugin" id="offlinePayment" style="display: none;">
	<div class="col-md-10 col-sm-12 box-center center mt-4 mb-0">
		<div class="row">
			
			<div class="col-xl-12 text-center">
				<img class="img-fluid" src="{{ url('images/offlinepayment/payment.png') }}" title="{{ trans('offlinepayment::messages.Offline Payment') }}">
			</div>
			
			<div class="col-xl-12 mt-3">
				<div id="offlinePaymentDescription">
					<div class="card card-default">
						
						<div class="card-header">
							<h3 class="panel-title">
								{{ trans('offlinepayment::messages.Payment Details') }}
							</h3>
						</div>
						
						<div class="card-body">
							<h3><strong>{{ trans('offlinepayment::messages.Follow the information below to make the payment') }}:</strong></h3>
							<ul>
								<li>
									<strong>{{ trans('offlinepayment::messages.Reason for payment') }}: </strong>
									{{ trans('offlinepayment::messages.Listing') }} #{{ (isset($post)) ? $post->id : 'ID' }} - <span class="package-name"></span>
								</li>
								<li>
									<strong>{{ trans('offlinepayment::messages.Amount') }}: </strong>
									<span class="amount-currency currency-in-left" style="display: none;"></span>
									<span class="payable-amount">0</span>
									<span class="amount-currency currency-in-right" style="display: none;"></span>
								</li>
							</ul>
							
							<hr class="border-0 bg-secondary">
							
							{!! (isset($offlinepaymentPaymentMethod) && !empty($offlinepaymentPaymentMethod->description)) ? $offlinepaymentPaymentMethod->description : '...' !!}
						</div>
					</div>
				</div>
			</div>
			
		</div>
    </div>
</div>

@section('after_scripts')
    @parent
    <script>
        $(document).ready(function ()
        {
            var selectedPackage = $('input[name=package_id]:checked').val();
			var packageName = $('input[name=package_id]:checked').data('name');
            var packagePrice = getPackagePrice(selectedPackage);
            var paymentMethod = $('#paymentMethodId').find('option:selected').data('name');
    		
            /* Check Payment Method */
            checkPaymentMethodForOfflinePayment(paymentMethod, packageName, packagePrice);
            
            $('#paymentMethodId').on('change', function () {
                paymentMethod = $(this).find('option:selected').data('name');
                checkPaymentMethodForOfflinePayment(paymentMethod, packageName, packagePrice);
            });
            $('.package-selection').on('click', function () {
                selectedPackage = $(this).val();
				packageName = $(this).data('name');
                packagePrice = getPackagePrice(selectedPackage);
                paymentMethod = $('#paymentMethodId').find('option:selected').data('name');
                checkPaymentMethodForOfflinePayment(paymentMethod, packageName, packagePrice);
            });
    		
            /* Send Payment Request */
            $('#submitPostForm').on('click', function (e)
            {
                e.preventDefault();
        		
                paymentMethod = $('#paymentMethodId').find('option:selected').data('name');
                
                if (paymentMethod !== 'offlinepayment' || packagePrice <= 0) {
                    return false;
                }
    			
                $('#postForm').submit();
        
                /* Prevent form from submitting */
                return false;
            });
        });
		
        function checkPaymentMethodForOfflinePayment(paymentMethod, packageName, packagePrice)
        {
            if (paymentMethod === 'offlinepayment' && packagePrice > 0) {
            	$('#offlinePaymentDescription').find('.package-name').html(packageName);
                $('#offlinePayment').show();
            } else {
                $('#offlinePayment').hide();
            }
        }
    </script>
@endsection
