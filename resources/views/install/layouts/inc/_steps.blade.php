@php
	$stepUrl ??= [];
	$step ??= 0;
	$current ??= 1;
	
	$navLinks = [
		'compatibility' => [
			'step' => 1,
			'label' => trans('messages.compatibility'),
			'icon'  => 'bi bi-info-circle',
			'url'   => data_get($stepUrl, 'compatibility') . '/?mode=manual',
		],
		'siteInfo' => [
			'step' => 2,
			'label' => trans('messages.site_info'),
			'icon'  => 'bi bi-gear',
			'url'   => data_get($stepUrl, 'siteInfo'),
		],
		'databaseInfo' => [
			'step' => 3,
			'label' => trans('messages.database_info'),
			'icon'  => 'bi bi-plugin',
			'url'   => data_get($stepUrl, 'databaseInfo'),
		],
		'databaseImport' => [
			'step' => 4,
			'label' => trans('messages.database_import'),
			'icon'  => 'bi bi-database-up',
			'url'   => data_get($stepUrl, 'databaseImport'),
		],
		'cronJobs' => [
			'step' => 5,
			'label' => trans('messages.cron_jobs'),
			'icon'  => 'bi bi-clock',
			'url'   => data_get($stepUrl, 'cronJobs'),
		],
		'finish' => [
			'step' => 6,
			'label' => trans('messages.finish'),
			'icon'  => 'bi bi-check-circle',
			'url'   => data_get($stepUrl, 'finish'),
		],
	];
@endphp
<ul class="nav nav-pills justify-content-center install-steps">
	@foreach($navLinks as $link)
		@php
			$linkStep = (int)data_get($link, 'step');
			$linkPrevStep = $linkStep - 1;
			
			$enabledClass = ($step >= $linkPrevStep) ? ' enabled' : '';
			$activeClass = ($current == $linkStep) ? ' active' : '';
			$disabledClass = ($current < $linkStep) ? ' disabled' : '';
		@endphp
		<li class="nav-item{{ $enabledClass }}">
			<a class="nav-link{{ $disabledClass.$activeClass }}" href="{{ data_get($link, 'url') }}">
				<i class="{{ data_get($link, 'icon') }}"></i> {{ data_get($link, 'label') }}
			</a>
		</li>
	@endforeach
</ul>
