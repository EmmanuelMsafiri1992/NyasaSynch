@php
	// Clear Filter Button
	$clearFilterBtn = \App\Helpers\UrlGen::getTypeFilterClearLink($cat ?? null, $city ?? null);
	
	$inputPostType = [];
	if (request()->filled('type')) {
		$types = request()->query('type');
		if (is_array($types)) {
			foreach ($types as $type) {
				$inputPostType[] = $type;
			}
		} else {
			$inputPostType[] = $types;
		}
	}
@endphp
{{-- PostType --}}
<div class="list-filter">
	<h5 class="list-title">
		<span class="fw-bold">
			{{ t('Job Type') }}
		</span> {!! $clearFilterBtn !!}
	</h5>
	<div class="filter-content filter-employment-type">
		<ul id="blocPostType" class="browse-list list-unstyled">
			@if (isset($postTypes) && !empty($postTypes))
				@foreach($postTypes as $key => $postType)
					<li class="form-check form-switch">
						<input type="checkbox"
							name="type[{{ $key }}]"
							id="employment_{{ data_get($postType, 'id') }}"
							value="{{ data_get($postType, 'id') }}"
							class="form-check-input emp emp-type"{{ (in_array(data_get($postType, 'id'),  $inputPostType)) ? ' checked="checked"' : '' }}
						>
						<label class="form-check-label" for="employment_{{ data_get($postType, 'id') }}">{{ data_get($postType, 'name') }}</label>
					</li>
				@endforeach
			@endif
			<input type="hidden" id="postTypeQueryString" value="{{ \App\Helpers\Arr::query(request()->except(['page', 'type'])) }}">
		</ul>
	</div>
</div>
<div style="clear:both"></div>

@section('after_scripts')
	@parent
	<script>
		onDocumentReady((event) => {
			const postTypeEls = document.querySelectorAll('#blocPostType input[type=checkbox]');
			if (postTypeEls.length > 0) {
				postTypeEls.forEach((element) => {
					element.addEventListener('change', (e) => {
						e.preventDefault();
						
						const queryStringEl = document.getElementById('postTypeQueryString');
						if (!queryStringEl) {
							return false;
						}
						
						let queryString = queryStringEl.value;
						
						if (queryString !== '') {
							queryString += '&';
						}
						
						let tmpQString = '';
						const checkedPostTypeEls = document.querySelectorAll('#blocPostType input[type=checkbox]:checked');
						if (checkedPostTypeEls.length > 0) {
							checkedPostTypeEls.forEach((checkedElement) => {
								if (tmpQString !== '') {
									tmpQString += '&';
								}
								tmpQString += 'type[]=' + checkedElement.value;
							});
						}
						
						queryString += tmpQString;
						
						let searchUrl = baseUrl + '?' + queryString;
						redirect(searchUrl);
					});
				});
			}
			
		});
	</script>
@endsection
