@php
	$keywords = rawurldecode(request()->input('q'));
@endphp
@includeFirst([config('larapen.core.customizedViewPath') . 'home.inc.spacer', 'home.inc.spacer'])
<div class="container">
	<form id="search" name="search" action="{{ \App\Helpers\UrlGen::company() }}" method="GET">
		<div class="row m-0">
			<div class="col-sm-10 col-12 px-0">
				<input name="q" class="form-control keyword" type="text" placeholder="{{ t('company_name') }}" value="{{ $keywords }}">
			</div>
			
			<div class="col-sm-2 col-12 ps-sm-1 px-0 mt-sm-0 mt-1">
				<button class="btn btn-block btn-primary"><i class="fa-solid fa-magnifying-glass"></i></button>
			</div>
			{!! csrf_field() !!}
		</div>
	</form>
</div>
