@php
	$packages ??= collect();
	$paymentMethods ??= collect();
	
	$selectedPackage ??= null;
	$currentPackagePrice = $selectedPackage->price ?? 0;
@endphp
@if ($paymentMethods->count() > 0)
	@if (!empty($selectedPackage))
		
		<div class="content-subheading">
			<i class="fa-solid fa-wallet"></i>
			<strong>{{ t('Payment') }}</strong>
		</div>
		
		<div class="col-md-12 page-content mb-4">
			<div class="inner-box">
				
				<div class="row">
					<div class="col-sm-12">
						
						<div class="form-group mb-0">
							<fieldset>
								
								@includeFirst([
									config('larapen.core.customizedViewPath') . 'payment.packages.selected',
									'payment.packages.selected'
								])
							
							</fieldset>
						</div>
					
					</div>
				</div>
			</div>
		</div>
	
	@else
		
		@if ($packages->count() > 0)
			<div class="content-subheading">
				<i class="fa-solid fa-tags"></i>
				<strong>{{ t('Packages') }}</strong>
			</div>
			
			<div class="col-md-12 page-content mb-4">
				<div class="inner-box">
					
					<div class="row">
						<div class="col-sm-12">
							<fieldset>
								
								@includeFirst([
									config('larapen.core.customizedViewPath') . 'payment.packages',
									'payment.packages'
								])
							
							</fieldset>
						
						</div>
					</div>
				</div>
			</div>
		@endif
		
	@endif
@endif

@section('after_styles')
	@parent
@endsection

@section('after_scripts')
	@parent
	<script>
		const packageType = 'promotion';
		const formType = 'singleStep';
		const isCreationFormPage = {{ request()->segment(1) == 'create' ? 'true' : 'false' }};
	</script>
	@include('common.js.payment-js')
@endsection
