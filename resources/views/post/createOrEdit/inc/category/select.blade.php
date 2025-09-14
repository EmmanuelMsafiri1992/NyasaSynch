@php
	$catDisplayType ??= 'c_border_list';
	
	$apiResult ??= [];
	$totalCategories = (int)data_get($apiResult, 'meta.total', 0);
	$areCategoriesPaginable = (!empty(data_get($apiResult, 'links.prev')) || !empty(data_get($apiResult, 'links.next')));
	
	$categories ??= [];
	$category ??= null;
	$hasChildren ??= false;
	$catId ??= 0; /* The selected category ID */
@endphp
@if (!$hasChildren)
	
	{{-- To append in the form (will replace the category field) --}}
	
	@if (!empty($category))
		@if (!empty(data_get($category, 'children')))
			<a href="#browseCategories" data-bs-toggle="modal" class="cat-link" data-id="{{ data_get($category, 'id') }}">
				{{ data_get($category, 'name') }}
			</a>
		@else
			{{ data_get($category, 'name') }}&nbsp;
			[ <a href="#browseCategories"
				 data-bs-toggle="modal"
				 class="cat-link"
				 data-id="{{ data_get($category, 'parent.id', 0) }}"
			><i class="fa-regular fa-pen-to-square"></i> {{ t('Edit') }}</a> ]
		@endif
	@else
		<a href="#browseCategories" data-bs-toggle="modal" class="cat-link" data-id="0">
			{{ t('select_a_category') }}
		</a>
	@endif
	
@else
	
	{{-- To append in the modal (will replace the modal content) --}}

	@if (!empty($category))
		<p>
			<a href="#" class="btn btn-sm btn-success cat-link" data-id="{{ data_get($category, 'parent_id') }}">
				<i class="fa-solid fa-reply"></i> {{ t('go_to_parent_categories') }}
			</a>&nbsp;
			<strong>{{ data_get($category, 'name') }}</strong>
		</p>
		<div style="clear:both"></div>
	@endif
	
	@if (!empty($categories))
		<div class="col-xl-12 content-box layout-section">
			<div class="row row-featured row-featured-category">
				@if ($catDisplayType == 'c_picture_list')
					
					@foreach($categories as $key => $cat)
						@php
							$_hasChildren = (!empty(data_get($cat, 'children'))) ? 1 : 0;
							$_parentId = data_get($cat, 'parent.id', 0);
							$_hasLink = (data_get($cat, 'id') != $catId || $_hasChildren == 1);
						@endphp
						<div class="col-lg-2 col-md-3 col-sm-4 col-6 f-category">
							@if ($_hasLink)
								<a href="#" class="cat-link"
								   data-id="{{ data_get($cat, 'id') }}"
								   data-parent-id="{{ $_parentId }}"
								   data-has-children="{{ $_hasChildren }}"
								>
							@endif
								<img src="{{ data_get($cat, 'picture_url') }}" class="lazyload img-fluid" alt="{{ data_get($cat, 'name') }}">
								<h6 class="{{ !$_hasLink ? 'text-secondary' : '' }}">
									{{ data_get($cat, 'name') }}
								</h6>
							@if ($_hasLink)
								</a>
							@endif
						</div>
					@endforeach
				
				@elseif ($catDisplayType == 'c_bigIcon_list')
					
					@foreach($categories as $key => $cat)
						@php
							$_hasChildren = (!empty(data_get($cat, 'children'))) ? 1 : 0;
							$_parentId = data_get($cat, 'parent.id', 0);
							$_hasLink = (data_get($cat, 'id') != $catId || $_hasChildren == 1);
						@endphp
						<div class="col-lg-2 col-md-3 col-sm-4 col-6 f-category">
							@if ($_hasLink)
								<a href="#" class="cat-link"
								   data-id="{{ data_get($cat, 'id') }}"
								   data-parent-id="{{ $_parentId }}"
								   data-has-children="{{ $_hasChildren }}"
								>
							@endif
								@if (in_array(config('settings.listings_list.show_category_icon'), [2, 6, 7, 8]))
									<i class="{{ data_get($cat, 'icon_class') ?? 'fa-regular fa-folder' }}"></i>
								@endif
								<h6 class="{{ !$_hasLink ? 'text-secondary' : '' }}">
									{{ data_get($cat, 'name') }}
								</h6>
							@if ($_hasLink)
								</a>
							@endif
						</div>
					@endforeach
					
				@else
					
					@php
						$listTab = [
							'c_border_list' => 'list-border',
						];
						$catListClass = (isset($listTab[$catDisplayType])) ? 'list ' . $listTab[$catDisplayType] : 'list';
					@endphp
					<div class="col-xl-12">
						<div class="list-categories">
							<div class="row">
								@foreach ($categories as $key => $items)
									<ul class="cat-list {{ $catListClass }} col-md-4 {{ (count($categories) == $key+1) ? 'cat-list-border' : '' }}">
										@foreach ($items as $k => $cat)
											@php
												$_hasChildren = (!empty(data_get($cat, 'children'))) ? 1 : 0;
												$_parentId = data_get($cat, 'parent.id', 0);
												$_hasLink = (data_get($cat, 'id') != $catId || $_hasChildren == 1);
											@endphp
											<li class="{{ !$_hasLink ? 'text-secondary fw-bold' : '' }}">
												@if (in_array(config('settings.listings_list.show_category_icon'), [2, 6, 7, 8]))
													<i class="{{ data_get($cat, 'icon_class') ?? 'fa-solid fa-check' }}"></i>&nbsp;
												@endif
												@if ($_hasLink)
													<a href="#" class="cat-link"
													   data-id="{{ data_get($cat, 'id') }}"
													   data-parent-id="{{ $_parentId }}"
													   data-has-children="{{ $_hasChildren }}"
													>
												@endif
													{{ data_get($cat, 'name') }}
												@if ($_hasLink)
													</a>
												@endif
											</li>
										@endforeach
									</ul>
								@endforeach
							</div>
						</div>
					</div>
				
				@endif
			
			</div>
		</div>
		@if ($totalCategories > 0 && $areCategoriesPaginable)
			<br>
			@include('vendor.pagination.api.bootstrap-4')
		@endif
	@else
		{{ $apiMessage ?? t('no_categories_found') }}
	@endif
@endif

@section('before_scripts')
	@parent
@endsection
