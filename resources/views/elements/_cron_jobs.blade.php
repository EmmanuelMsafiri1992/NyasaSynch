@php
    $phpBinaryPath ??= null;
    $requiredPhpVersion ??= '8.2';
	
	$basePath = $basePath ?? base_path();
	$basePath = str($basePath)->finish('/')->toString();
	
	$articleUrl = 'https://support.bedigit.com/help-center/articles/19/configuring-the-cron-job';
@endphp
<h3 class="title-3">
    <i class="fa-regular fa-clock"></i> {{ trans('messages.setting_up_cron_jobs') }}
</h3>

<div class="alert {{ isAdminPanel() ? 'bg-light-info' : 'alert-info' }}">
    {!! trans('messages.cron_jobs_guide', ['articleUrl' => $articleUrl]) !!}
</div>

@if (empty($phpBinaryPath))
    <div class="alert alert-warning">
        {!! trans('messages.cron_jobs_warning', ['phpVersion' => $requiredPhpVersion]) !!}
    </div>
    @php
        $phpBinaryPath = '<span class="text-danger">{PHP_BIN_PATH}</span>';
    @endphp
@endif

<div class="alert alert-light">
    <code>* * * * * {!! $phpBinaryPath !!} {{ $basePath }}artisan schedule:run >> /dev/null 2>&amp;1</code>
</div>
