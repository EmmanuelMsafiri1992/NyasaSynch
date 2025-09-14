@php
	/* Get form origin */
	$originForm ??= null;
	
	/* From Company's Form */
	$classLeftCol = 'col-md-3';
	$classRightCol = 'col-md-9';
	
	$classRightCol = ($originForm == 'user') ? 'col-md-7' : $classRightCol; /* From User's Form */
	$classRightCol = ($originForm == 'post') ? 'col-md-8' : $classRightCol; /* From Post's Form */
	
	$resume ??= [];
	
	$fiTheme = config('larapen.core.fileinput.theme', 'bs5');
@endphp
<div id="resumeFields">
	
	@if ($originForm != 'message')
		@if (!empty($resume))
			{{-- name --}}
			@php
				$resumeNameError = (isset($errors) && $errors->has('resume.name')) ? ' is-invalid' : '';
			@endphp
			<div class="row mb-3">
				<label class="{{ $classLeftCol }} col-form-label" for="resume.name">{{ t('Name') }}</label>
				<div class="{{ $classRightCol }}">
					<input name="resume[name]"
						   placeholder="{{ t('Name') }}"
						   class="form-control input-md{{ $resumeNameError }}"
						   type="text"
						   value="{{ old('resume.name', data_get($resume, 'name')) }}"
					>
				</div>
			</div>
		@endif
		
		{{-- filename --}}
		@php
			$resumeFilenameError = (isset($errors) && $errors->has('resume.filename')) ? ' is-invalid' : '';
		@endphp
		<div class="row mb-3">
			<label class="{{ $classLeftCol }} col-form-label{{ $resumeFilenameError }}" for="resume.filename"> {{ t('your_resume') }} </label>
			<div class="{{ $classRightCol }}">
				<div class="mb10">
					<input id="resumeFilename" name="resume[filename]" type="file" class="file{{ $resumeFilenameError }}">
				</div>
				<div class="form-text text-muted">{{ t('file_types', ['file_types' => showValidFileTypes('file')]) }}</div>
				@if (!empty($resume))
					@if (!empty(data_get($resume, 'filename')) && $pDisk->exists(data_get($resume, 'filename')))
					<div class="mt20">
						<a class="btn btn-default" href="{{ privateFileUrl(data_get($resume, 'filename')) }}" target="_blank">
							<i class="fa-solid fa-paperclip"></i> {{ t('Download') }}
						</a>
					</div>
					@endif
				@endif
			</div>
		</div>
	@else
		{{-- filename --}}
		@php
			$resumeFilenameError = (isset($errors) && $errors->has('resume.filename')) ? ' is-invalid' : '';
		@endphp
		<div class="form-group required" {!! (config('lang.direction')=='rtl') ? 'dir="rtl"' : '' !!}>
			<label for="resume.filename" class="col-form-label{{ $resumeFilenameError }}">{{ t('Resume File') }} </label>
			<input id="resumeFilename" name="resume[filename]" type="file" class="file{{ $resumeFilenameError }}">
			<div class="form-text text-muted">{{ t('file_types', ['file_types' => showValidFileTypes('file')]) }}</div>
			@if (!empty($resume))
				@if (!empty(data_get($resume, 'filename')) && $pDisk->exists(data_get($resume, 'filename')))
					<div class="mt20">
						<a class="btn btn-default" href="{{ privateFileUrl(data_get($resume, 'filename')) }}" target="_blank">
							<i class="fa-solid fa-paperclip"></i> {{ t('Download the resume') }}
						</a>
					</div>
				@endif
			@endif
		</div>
	@endif

</div>

@section('after_styles')
	@parent
	<style>
		#resumeFields .select2-container {
			width: 100% !important;
		}
	</style>
@endsection

@section('after_scripts')
	@parent
	<script>
		let cvOptions = {};
		cvOptions.theme = '{{ $fiTheme }}';
		cvOptions.language = '{{ config('app.locale') }}';
		cvOptions.rtl = {{ (config('lang.direction') == 'rtl') ? 'true' : 'false' }};
		cvOptions.allowedFileExtensions = {!! getUploadFileTypes('file', true) !!};
		cvOptions.minFileSize = {{ (int)config('settings.upload.min_file_size', 0) }};
		cvOptions.maxFileSize = {{ (int)config('settings.upload.max_file_size', 1000) }};
		cvOptions.showPreview = false;
		cvOptions.showUpload = false;
		cvOptions.showRemove = false;
		
		onDocumentReady((event) => {
			{{-- fileinput (resume) --}}
			$('#resumeFilename').fileinput(cvOptions);
		});
	</script>
@endsection
