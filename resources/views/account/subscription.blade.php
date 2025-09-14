{{--
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
--}}
@extends('layouts.master')

@php
	$packages ??= collect();
	$paymentMethods ??= collect();
	
	$selectedPackage ??= null;
	$currentPackagePrice = $selectedPackage->price ?? 0;
@endphp
@section('content')
	@include('common.spacer')
    <div class="main-container">
        <div class="container">
            <div class="row">
	            
	            <div class="col-md-3 page-sidebar">
		            @include('account.inc.sidebar')
	            </div>
		           
	            <div class="col-md-9 page-content">
		            
		            @include('flash::message')
		            
		            @if (isset($errors) && $errors->any())
			            <div class="alert alert-danger alert-dismissible">
				            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ t('Close') }}"></button>
				            <h5><strong>{{ t('validation_errors_title') }}</strong></h5>
				            <ul class="list list-check">
					            @foreach ($errors->all() as $error)
						            <li>{{ $error }}</li>
					            @endforeach
				            </ul>
			            </div>
		            @endif
		            
                    <div class="inner-box">
						
                        <h2 class="title-2">
							<strong>
								@if (!empty($selectedPackage))
									<i class="fa-solid fa-wallet"></i> {{ t('Payment') }}
								@else
									<i class="fa-solid fa-tags"></i> {{ t('subscription') }}
								@endif
							</strong>
						</h2>
						
                        <div class="row">
                            <div class="col-sm-12">
                                <form class="form" id="payableForm" method="POST" action="{{ url()->current() }}">
                                    {!! csrf_field() !!}
                                    <input type="hidden" name="payable_id" value="{{ $authUser ? $authUser->id : (auth()->id() ?? '') }}">
                                    <fieldset>
										
										@if (!empty($selectedPackage))
											@includeFirst([
												config('larapen.core.customizedViewPath') . 'payment.packages.selected',
												'payment.packages.selected'
											])
										@else
											@includeFirst([
												config('larapen.core.customizedViewPath') . 'payment.packages',
												'payment.packages'
											])
                                        @endif
										
                                        <div class="row">
                                            <div class="col-md-12 text-center mt-4">
												<a id="skipBtn" href="{{ url('account') }}" class="btn btn-default btn-lg">
													{{ t('Skip') }}
												</a>
                                                <button id="payableFormSubmitButton" class="btn btn-success btn-lg payableFormSubmitButton"> {{ t('Pay') }} </button>
                                            </div>
                                        </div>
                                    
                                    </fieldset>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('after_styles')
@endsection

@section('after_scripts')
	<script>
		const packageType = 'subscription';
		const formType = 'multiStep';
		const isCreationFormPage = false;
	</script>
	@include('common.js.payment-scripts')
	@include('common.js.payment-js')
@endsection
