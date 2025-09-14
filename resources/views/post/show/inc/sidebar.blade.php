@php
	$authUser = auth()->check() ? auth()->user() : null;
	$authUserId = !empty($authUser) ? $authUser->getAuthIdentifier() : 0;
	$authUserTypeId = (!empty($authUser) && !empty($authUser->user_type_id)) ? $authUser->user_type_id : 0;
	
	$post ??= [];
	$user ??= [];
	$countPackages ??= 0;
	$countPaymentMethods ??= 0
@endphp
<aside>
	<div class="card sidebar-card card-contact-seller">
		<div class="card-header">{{ t('Company Information') }}</div>
		<div class="card-content user-info">
			<div class="card-body text-center">
				<div class="seller-info">
					<div class="company-logo-thumb mb20">
						@if (!empty(data_get($post, 'company')))
							<a href="{{ \App\Helpers\UrlGen::company(null, data_get($post, 'company.id')) }}">
								<img alt="Logo {{ data_get($post, 'company_name') }}" class="img-fluid" src="{{ data_get($post, 'logo_url.medium') }}">
							</a>
						@else
							<img alt="Logo {{ data_get($post, 'company_name') }}" class="img-fluid" src="{{ data_get($post, 'logo_url.medium') }}">
						@endif
					</div>
					@if (!empty(data_get($post, 'company')))
						<h3 class="no-margin">
							<a href="{{ \App\Helpers\UrlGen::company(null, data_get($post, 'company.id')) }}">
								{{ data_get($post, 'company.name') }}
							</a>
						</h3>
					@else
						<h3 class="no-margin">{{ data_get($post, 'company_name') }}</h3>
					@endif
					<p>
						{{ t('location') }}:&nbsp;
						<strong>
							<a href="{!! \App\Helpers\UrlGen::city(data_get($post, 'city')) !!}">
								{{ data_get($post, 'city.name') }}
							</a>
						</strong>
					</p>
					@if (!config('settings.listing_page.hide_date'))
						@if (!empty($user) && !empty(data_get($user, 'created_at_formatted')))
							<p>{{ t('Joined') }}: <strong>{!! data_get($user, 'created_at_formatted') !!}</strong></p>
						@endif
					@endif
					@if (!empty(data_get($post, 'company')))
						@if (!empty(data_get($post, 'company.website')))
							<p>
								{{ t('Web') }}:
								<strong>
									<a href="{{ data_get($post, 'company.website') }}" target="_blank" rel="nofollow">
										{{ getUrlHost(data_get($post, 'company.website')) }}
									</a>
								</strong>
							</p>
						@endif
					@endif
				</div>
				<div class="user-posts-action">
					@if (!empty($authUser))
						@if ($authUserId == data_get($post, 'user_id'))
							<a href="{{ \App\Helpers\UrlGen::editPost($post) }}" class="btn btn-default btn-block">
								<i class="fa-regular fa-pen-to-square"></i> {{ t('Update the details') }}
							</a>
							@if (config('settings.listing_form.publication_form_type') == '1')
								@if ($countPackages > 0 && $countPaymentMethods > 0)
									<a href="{{ url('posts/' . data_get($post, 'id') . '/payment') }}" class="btn btn-success btn-block">
										<i class="fa-regular fa-circle-check"></i> {{ t('Make It Premium') }}
									</a>
								@endif
							@endif
							@if (empty(data_get($post, 'archived_at')) && isVerifiedPost($post))
								<a href="{{ url('account/posts/list/' . data_get($post, 'id') . '/offline') }}"
								   class="btn btn-warning btn-block confirm-simple-action"
								>
									<i class="fa-solid fa-eye-slash"></i> {{ t('put_it_offline') }}
								</a>
							@endif
							@if (!empty(data_get($post, 'archived_at')))
								<a href="{{ url('account/posts/archived/' . data_get($post, 'id') . '/repost') }}"
								   class="btn btn-info btn-block confirm-simple-action"
								>
									<i class="fa-solid fa-recycle"></i> {{ t('re_post_it') }}
								</a>
							@endif
						@else
							@if ($authUserTypeId == 2)
								{!! genEmailContactBtn($post, true) !!}
							@endif
							{!! genPhoneNumberBtn($post, true) !!}
						@endif
						@php
							try {
								if (doesUserHavePermission($authUser, \App\Models\Permission::getStaffPermissions())) {
									$btnUrl = admin_url('blacklists/add') . '?';
									$btnQs = (!empty(data_get($post, 'email'))) ? 'email=' . data_get($post, 'email') : '';
									$btnQs = (!empty($btnQs)) ? $btnQs . '&' : $btnQs;
									$btnQs = (!empty(data_get($post, 'phone'))) ? $btnQs . 'phone=' . data_get($post, 'phone') : $btnQs;
									$btnUrl = $btnUrl . $btnQs;
									
									if (!isDemoDomain($btnUrl)) {
										$btnText = trans('admin.ban_the_user');
										$btnHint = $btnText;
										if (!empty(data_get($post, 'email')) && !empty(data_get($post, 'phone'))) {
											$btnHint = trans('admin.ban_the_user_email_and_phone', [
												'email' => data_get($post, 'email'),
												'phone' => data_get($post, 'phone'),
											]);
										} else {
											if (!empty(data_get($post, 'email'))) {
												$btnHint = trans('admin.ban_the_user_email', ['email' => data_get($post, 'email')]);
											}
											if (!empty(data_get($post, 'phone'))) {
												$btnHint = trans('admin.ban_the_user_phone', ['phone' => data_get($post, 'phone')]);
											}
										}
										$tooltip = ' data-bs-toggle="tooltip" data-bs-placement="bottom" title="' . $btnHint . '"';
										
										$btnOut = '<a href="'. $btnUrl .'" class="btn btn-outline-danger btn-block confirm-simple-action"'. $tooltip .'>';
										$btnOut .= $btnText;
										$btnOut .= '</a>';
										
										echo $btnOut;
									}
								}
							} catch (\Throwable $e) {}
						@endphp
					@else
						{!! genEmailContactBtn($post, true) !!}
						{!! genPhoneNumberBtn($post, true) !!}
					@endif
				</div>
			</div>
		</div>
	</div>
	
	@if (config('settings.listing_page.show_listing_on_googlemap'))
		@php
			$mapHeight = 250;
			$mapPlace = (!empty(data_get($post, 'city')))
				? data_get($post, 'city.name') . ',' . config('country.name')
				: config('country.name');
			$mapUrl = getGoogleMapsEmbedUrl(config('services.googlemaps.key'), $mapPlace);
		@endphp
		<div class="card sidebar-card">
			<div class="card-header">{{ t('location_map') }}</div>
			<div class="card-content">
				<div class="card-body text-start p-0">
					<div class="posts-googlemaps">
						<iframe id="googleMaps" width="100%" height="{{ $mapHeight }}" src="{{ $mapUrl }}"></iframe>
					</div>
				</div>
			</div>
		</div>
	@endif
	
	@if (isVerifiedPost($post))
		@includeFirst([
			config('larapen.core.customizedViewPath') . 'layouts.inc.social.horizontal',
			'layouts.inc.social.horizontal'
		])
	@endif
	
	<div class="card sidebar-card">
		<div class="card-header">{{ t('Tips for candidates') }}</div>
		<div class="card-content">
			<div class="card-body text-start">
				<ul class="list-check">
					<li>{{ t('Check if the offer matches your profile') }}</li>
					<li>{{ t('Check the start date') }}</li>
					<li>{{ t('Meet the employer in a professional location') }}</li>
				</ul>
				@php
					$tipsLinkAttributes = getUrlPageByType('tips');
				@endphp
				@if (!str_contains($tipsLinkAttributes, 'href="#"') && !str_contains($tipsLinkAttributes, 'href=""'))
					<p>
						<a class="float-end" {!! $tipsLinkAttributes !!}>
							{{ t('Know more') }} <i class="fa-solid fa-angles-right"></i>
						</a>
					</p>
				@endif
			</div>
		</div>
	</div>
</aside>
