@extends('install.layouts.master')
@section('title', trans('messages.compatibility_title'))

@php
	$checkComponents ??= false;
	$components ??= [];
	
	$checkPermissions ??= false;
	$permissions ??= [];
@endphp
@section('content')
	
	@if (!$checkComponents)
		<h3 class="title-3">
			<i class="fa-solid fa-list"></i> {{ trans('messages.requirements') }}
		</h3>
		<div class="row">
			<div class="col-md-12">
				<ul class="installation">
					@foreach ($components as $key => $item)
						@continue($item['isOk'])
						<li>
							@if ($item['isOk'])
								<i class="bi bi-check text-success"></i>
							@else
								<i class="bi bi-x text-danger"></i>
							@endif
							<h5 class="title-5 fw-bold">
								{{ $item['name'] }}
							</h5>
							<p>
								{!! ($item['isOk']) ? $item['success'] : $item['warning'] !!}
							</p>
						</li>
					@endforeach
				</ul>
			</div>
		</div>
	@endif
	
	<h3 class="title-3">
		<i class="fa-regular fa-folder"></i> {{ trans('messages.permissions') }}
	</h3>
	<div class="row">
		<div class="col-md-12">
			<ul class="installation">
				@foreach ($permissions as $key => $item)
					<li>
						@if ($item['isOk'])
							<i class="bi bi-check text-success"></i>
						@else
							<i class="bi bi-x text-danger"></i>
						@endif
						<h5 class="title-5 fw-bold">
							{{ $item['name'] }}
						</h5>
						<p>
							{!! ($item['isOk']) ? $item['success'] : $item['warning'] !!}
						</p>
					</li>
				@endforeach
			</ul>
		</div>
	</div>
	
	<div class="text-end">
		@if ($checkComponents && $checkPermissions)
			<a href="{{ data_get($stepUrl, 'siteInfo') }}" class="btn btn-primary">
				{!! trans('messages.next') !!} <i class="fa-solid fa-chevron-right position-right"></i>
			</a>
		@else
			<a href="{{ data_get($stepUrl, 'compatibility') }}" class="btn btn-primary">
				<i class="fa-solid fa-rotate-right position-right"></i> {!! trans('messages.try_again') !!}
			</a>
		@endif
	</div>

@endsection

@section('after_scripts')
@endsection
