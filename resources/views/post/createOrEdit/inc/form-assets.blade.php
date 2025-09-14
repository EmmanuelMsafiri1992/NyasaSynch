@php
	$postInput ??= [];
	$post ??= [];
	$admin ??= [];
	
	$isMultiStepsForm = (config('settings.listing_form.publication_form_type') == '1');
	$isSingleStepForm = (config('settings.listing_form.publication_form_type') == '2');
	
	$isSingleStepCreateForm = ($isSingleStepForm && request()->segment(1) == 'create');
	$isSingleStepEditForm = ($isSingleStepForm && request()->segment(1) == 'edit');
	
	$postId = data_get($post, 'id') ?? '';
	$postTypeId = data_get($post, 'post_type_id') ?? data_get($postInput, 'post_type_id', 0);
	$countryCode = data_get($post, 'country_code') ?? data_get($postInput, 'country_code', config('country.code', 0));
	
	$adminType = config('country.admin_type', 0);
	$selectedAdminCode = data_get($admin, 'code') ?? data_get($postInput, 'admin_code', 0);
	$cityId = (int)(data_get($post, 'city_id') ?? data_get($postInput, 'city_id', 0));
	
	$postCreatedAt = data_get($post, 'created_at');
	$postCreatedAt = (!empty($postCreatedAt) && isValidDate($postCreatedAt)) ? $postCreatedAt : date('Y-m-d');
	
	$fiTheme = config('larapen.core.fileinput.theme', 'bs5');
@endphp
@section('modal_location')
	@includeFirst([config('larapen.core.customizedViewPath') . 'layouts.inc.modal.location', 'layouts.inc.modal.location'])
@endsection

@push('after_styles_stack')
	@include('layouts.inc.tools.wysiwyg.css')
	
	<link href="{{ url('assets/plugins/bootstrap-fileinput/css/fileinput.min.css') }}" rel="stylesheet">
	@if (config('lang.direction') == 'rtl')
		<link href="{{ url('assets/plugins/bootstrap-fileinput/css/fileinput-rtl.min.css') }}" rel="stylesheet">
	@endif
	@if (str_starts_with($fiTheme, 'explorer'))
		<link href="{{ url('assets/plugins/bootstrap-fileinput/themes/' . $fiTheme . '/theme.min.css') }}" rel="stylesheet">
	@endif
	
	{{-- Multi Steps Form --}}
	@if ($isMultiStepsForm)
		<style>
			.krajee-default.file-preview-frame:hover:not(.file-preview-error) {
				box-shadow: 0 0 5px 0 #666666;
			}
		</style>
	@endif
	
	{{-- Single Step Form --}}
	@if ($isSingleStepForm)
		<style>
			.krajee-default.file-preview-frame:hover:not(.file-preview-error) {
				box-shadow: 0 0 5px 0 #666666;
			}
			.file-loading:before {
				content: " {{ t('loading_wd') }}";
			}
			/* Preview Frame Size */
			.krajee-default.file-preview-frame .kv-file-content {
				height: auto;
			}
			.krajee-default.file-preview-frame .file-thumbnail-footer {
				height: 30px;
			}
		</style>
	@endif
	
	<link href="{{ url('assets/plugins/bootstrap-daterangepicker/3.1/daterangepicker.css') }}" rel="stylesheet">
@endpush

@push('after_scripts_stack')
	@include('layouts.inc.tools.wysiwyg.js')
	@include('common.js.payment-scripts')
	
	<script src="{{ url('assets/plugins/bootstrap-fileinput/js/plugins/sortable.min.js') }}" type="text/javascript"></script>
	<script src="{{ url('assets/plugins/bootstrap-fileinput/js/fileinput.min.js') }}" type="text/javascript"></script>
	<script src="{{ url('assets/plugins/bootstrap-fileinput/themes/' . $fiTheme . '/theme.js') }}" type="text/javascript"></script>
	<script src="{{ url('common/js/fileinput/locales/' . config('app.locale') . '.js') }}" type="text/javascript"></script>
	
	<script src="{{ url('assets/plugins/momentjs/2.30.1/moment.min.js') }}" type="text/javascript"></script>
	<script src="{{ url('assets/plugins/bootstrap-daterangepicker/3.1/daterangepicker.js') }}" type="text/javascript"></script>
	
	<script>
		/* Translation */
		var lang = {
			'select': {
				'country': "{{ t('select_a_country') }}",
				'admin': "{{ t('select_a_location') }}",
				'city': "{{ t('select_a_city') }}"
			},
			'price': "{{ t('Price') }}",
			'salary': "{{ t('Salary') }}",
			'nextStepBtnLabel': {
				'next': "{{ t('Next') }}",
				'submit': "{{ t('Update') }}"
			}
		};
		
		var stepParam = 0;
		
		/* Company */
		var postCompanyId = '{{ old('company_id', ($postCompanyId ?? 0)) }}';
		
		/* Locations */
		var countryCode = '{{ old('country_code', $countryCode) }}';
		var adminType = '{{ $adminType }}';
		var selectedAdminCode = '{{ old('admin_code', $selectedAdminCode) }}';
		var cityId = '{{ old('city_id', data_get($postInput, 'city_id', $cityId)) }}';
		
		/* Packages */
		var packageIsEnabled = false;
		@if (isset($packages, $paymentMethods) && $packages->count() > 0 && $paymentMethods->count() > 0)
			packageIsEnabled = true;
		@endif
	</script>
	<script>
		onDocumentReady((event) => {
			/* Company */
			getCompany(postCompanyId);
			const companyIdEl = document.getElementById('companyId');
			if (companyIdEl) {
				$(companyIdEl).on('click', (e) => getCompany(e.target.value));
				$(companyIdEl).on('change', (e) => getCompany(e.target.value));
			}
			
			/* Company logo's button */
			const companyFormLinkEl = document.getElementById('companyFormLink');
			if (companyFormLinkEl) {
				companyFormLinkEl.addEventListener('click', (e) => {
					let companyLink = e.target.getAttribute('href');
					if (companyLink.indexOf('/new/') !== -1) {
						e.preventDefault();
						getCompany(0);
						
						return false;
					}
				});
			}
			
			{{-- select2: If error occured, apply Bootstrap's error class --}}
			@if (config('settings.listing_form.city_selection') == 'select')
				@if ($errors->has('admin_code'))
					$('select[name="admin_code"]').closest('div').addClass('is-invalid');
				@endif
			@endif
			@if ($errors->has('city_id'))
				$('select[name="city_id"]').closest('div').addClass('is-invalid');
			@endif
			
			{{-- Tagging with multi-value Select Boxes --}}
			@php
				$tagsLimit = (int)config('settings.listing_form.tags_limit', 15);
				$tagsMinLength = (int)config('settings.listing_form.tags_min_length', 2);
				$tagsMaxLength = (int)config('settings.listing_form.tags_max_length', 30);
			@endphp
			let selectTagging = $('.tags-selecter').select2({
				language: langLayout.select2,
				width: '100%',
				tags: true,
				maximumSelectionLength: {{ $tagsLimit }},
				tokenSeparators: [',', ';', ':', '/', '\\', '#'],
				createTag: function (params) {
					var term = $.trim(params.term);
					
					{{-- Don't offset to create a tag if there is some symbols/characters --}}
					let invalidCharsArray = [',', ';', '_', '/', '\\', '#'];
					let arrayLength = invalidCharsArray.length;
					for (let i = 0; i < arrayLength; i++) {
						let invalidChar = invalidCharsArray[i];
						if (term.indexOf(invalidChar) !== -1) {
							return null;
						}
					}
					
					{{-- Don't offset to create empty tag --}}
					{{-- Return null to disable tag creation --}}
					if (term === '') {
						return null;
					}
					
					{{-- Don't allow tags which are less than 2 characters or more than 50 characters --}}
					if (term.length < {{ $tagsMinLength }} || term.length > {{ $tagsMaxLength }}) {
						return null;
					}
					
					return {
						id: term,
						text: term
					}
				}
			});
			
			{{-- Apply tags limit --}}
			selectTagging.on('change', function(e) {
				if ($(this).val().length > {{ $tagsLimit }}) {
					$(this).val($(this).val().slice(0, {{ $tagsLimit }}));
				}
			});
			
			{{-- select2: If error occured, apply Bootstrap's error class --}}
			@if ($errors->has('tags.*'))
				$('select[name^="tags"]').next('.select2.select2-container').addClass('is-invalid');
			@endif
			
			/*
			 * start_date field
			 * https://www.daterangepicker.com/#options
			 */
			let postCreatedAt = '{{ $postCreatedAt }}';
			let referenceDate = moment(postCreatedAt);
			
			let dateEl = $('#payableForm .cf-date');
			dateEl.daterangepicker({
				autoUpdateInput: false,
				autoApply: true,
				showDropdowns: true,
				minYear: parseInt(moment().format('YYYY')),
				maxYear: parseInt(moment().format('YYYY')) + 10,
				locale: {
					format: '{{ t('datepicker_format') }}',
					applyLabel: "{{ t('datepicker_applyLabel') }}",
					cancelLabel: "{{ t('datepicker_cancelLabel') }}",
					fromLabel: "{{ t('datepicker_fromLabel') }}",
					toLabel: "{{ t('datepicker_toLabel') }}",
					customRangeLabel: "{{ t('datepicker_customRangeLabel') }}",
					weekLabel: "{{ t('datepicker_weekLabel') }}",
					daysOfWeek: [
						"{{ t('datepicker_sunday') }}",
						"{{ t('datepicker_monday') }}",
						"{{ t('datepicker_tuesday') }}",
						"{{ t('datepicker_wednesday') }}",
						"{{ t('datepicker_thursday') }}",
						"{{ t('datepicker_friday') }}",
						"{{ t('datepicker_saturday') }}"
					],
					monthNames: [
						"{{ t('January') }}",
						"{{ t('February') }}",
						"{{ t('March') }}",
						"{{ t('April') }}",
						"{{ t('May') }}",
						"{{ t('June') }}",
						"{{ t('July') }}",
						"{{ t('August') }}",
						"{{ t('September') }}",
						"{{ t('October') }}",
						"{{ t('November') }}",
						"{{ t('December') }}"
					],
					firstDay: 1
				},
				singleDatePicker: true,
				startDate: moment().format('{{ t('datepicker_format') }}')
			});
			dateEl.on('apply.daterangepicker', function(ev, picker) {
				if (picker.startDate.format('YYYYMMDD') >= parseInt(referenceDate.format('YYYYMMDD'))) {
					$(this).val(picker.startDate.format('{{ t('datepicker_format') }}'));
				} else {
					let dateInPastText = '{{ t('date_cannot_be_in_the_past') }}';
					Swal.fire({
						position: 'center',
						icon: 'error',
						text: dateInPastText
					});
					
					$(this).val('');
				}
			});
		});
	</script>
	
	<script src="{{ url('assets/js/app/d.modal.category.js') . vTime() }}"></script>
	@if (config('settings.listing_form.city_selection') == 'select')
		<script src="{{ url('assets/js/app/d.select.location.js') . vTime() }}"></script>
	@else
		<script src="{{ url('assets/js/app/browse.locations.js') . vTime() }}"></script>
		<script src="{{ url('assets/js/app/d.modal.location.js') . vTime() }}"></script>
	@endif
@endpush
