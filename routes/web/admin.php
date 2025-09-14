<?php
/*
 * JobClass - Job Board Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com/jobclass
 * Author: BeDigit | https://bedigit.com
 *
 * LICENSE
 * -------
 * This software is furnished under a license and may be used and copied
 * only in accordance with the terms of such license and with the inclusion
 * of the above copyright notice. If you Purchased from CodeCanyon,
 * Please read the full License from here - https://codecanyon.net/licenses/standard
 */

use App\Http\Controllers\Web\Admin\ActionController;
use App\Http\Controllers\Web\Admin\AdvertisingController;
use App\Http\Controllers\Web\Admin\Auth\ForgotPasswordController;
use App\Http\Controllers\Web\Admin\Auth\LoginController;
use App\Http\Controllers\Web\Admin\BackupController;
use App\Http\Controllers\Web\Admin\BlacklistController;
use App\Http\Controllers\Web\Admin\CategoryController;
use App\Http\Controllers\Web\Admin\CityController;
use App\Http\Controllers\Web\Admin\CompanyController;
use App\Http\Controllers\Web\Admin\CountryController;
use App\Http\Controllers\Web\Admin\CurrencyController;
use App\Http\Controllers\Web\Admin\DashboardController;
use App\Http\Controllers\Web\Admin\FileController;
use App\Http\Controllers\Web\Admin\HomeSectionController;
use App\Http\Controllers\Web\Admin\InlineRequestController;
use App\Http\Controllers\Web\Admin\LanguageController;
use App\Http\Controllers\Web\Admin\MetaTagController;
use App\Http\Controllers\Web\Admin\PackageController;
use App\Http\Controllers\Web\Admin\PageController;
use App\Http\Controllers\Web\Admin\Panel\Library\PanelRoutes;
use App\Http\Controllers\Web\Admin\PaymentController;
use App\Http\Controllers\Web\Admin\PaymentMethodController;
use App\Http\Controllers\Web\Admin\PermissionController;
use App\Http\Controllers\Web\Admin\PictureController;
use App\Http\Controllers\Web\Admin\PluginController;
use App\Http\Controllers\Web\Admin\PostController;
use App\Http\Controllers\Web\Admin\PostTypeController;
use App\Http\Controllers\Web\Admin\ReportTypeController;
use App\Http\Controllers\Web\Admin\RoleController;
use App\Http\Controllers\Web\Admin\SalaryTypeController;
use App\Http\Controllers\Web\Admin\SettingController;
use App\Http\Controllers\Web\Admin\SubAdmin1Controller;
use App\Http\Controllers\Web\Admin\SubAdmin2Controller;
use App\Http\Controllers\Web\Admin\SystemController;
use App\Http\Controllers\Web\Admin\UserController;
use Illuminate\Support\Facades\Route;

// Auth
Route::namespace('Auth')
	->group(function ($router) {
		// Authentication Routes...
		Route::controller(LoginController::class)
			->group(function ($router) {
				Route::get('login', 'showLoginForm')->name('login');
				Route::post('login', 'login');
				Route::get('logout', 'logout')->name('logout');
			});
		
		// Password Reset Routes...
		Route::controller(ForgotPasswordController::class)
			->group(function ($router) {
				Route::get('password/reset', 'showLinkRequestForm')->name('password.request');
				Route::post('password/email', 'sendResetLinkEmail')->name('password.email');
			});
	});

// Admin Panel Area
Route::middleware(['admin', 'clearance', 'banned.user', 'no.http.cache'])
	->group(function ($router) {
		// Dashboard
		Route::controller(DashboardController::class)
			->group(function ($router) {
				Route::get('dashboard', 'dashboard');
				Route::get('/', 'redirect');
			});
		
		// Extra (must be called before CRUD)
		Route::controller(SettingController::class)
			->group(function ($router) {
				Route::get('settings/find/{key}', 'find')->where('key', '[^/]+');
				Route::get('settings/reset/{key}', 'reset')->where('key', '[^/]+');
			});
		Route::controller(HomeSectionController::class)
			->group(function ($router) {
				Route::get('homepage/find/{method}', 'find')->where('method', '[^/]+');
				Route::get('homepage/reset/all/{action}', 'resetAll')->where('action', 'reorder|options');
			});
		Route::controller(LanguageController::class)
			->group(function ($router) {
				Route::get('languages/sync_files', 'syncFilesLines');
				Route::get('languages/texts/{lang?}/{file?}', 'showTexts')
					->where('lang', '[^/]*')
					->where('file', '[^/]*');
				Route::post('languages/texts/{lang}/{file}', 'updateTexts')
					->where('lang', '[^/]+')
					->where('file', '[^/]+');
			});
		Route::get('permissions/create_default_entries', [PermissionController::class, 'createDefaultEntries']);
		Route::get('blacklists/add', [BlacklistController::class, 'banUser']);
		Route::get('categories/rebuild-nested-set-nodes', [CategoryController::class, 'rebuildNestedSetNodes']);
		
		// Panel's Default Routes
		PanelRoutes::resource('advertisings', AdvertisingController::class);
		PanelRoutes::resource('blacklists', BlacklistController::class);
		PanelRoutes::resource('categories', CategoryController::class);
		PanelRoutes::resource('categories/{catId}/subcategories', CategoryController::class);
		PanelRoutes::resource('cities', CityController::class);
		PanelRoutes::resource('companies', CompanyController::class);
		PanelRoutes::resource('countries', CountryController::class);
		PanelRoutes::resource('countries/{countryCode}/cities', CityController::class);
		PanelRoutes::resource('countries/{countryCode}/admins1', SubAdmin1Controller::class);
		PanelRoutes::resource('currencies', CurrencyController::class);
		PanelRoutes::resource('homepage', HomeSectionController::class);
		PanelRoutes::resource('admins1/{admin1Code}/cities', CityController::class);
		PanelRoutes::resource('admins1/{admin1Code}/admins2', SubAdmin2Controller::class);
		PanelRoutes::resource('admins2/{admin2Code}/cities', CityController::class);
		PanelRoutes::resource('languages', LanguageController::class);
		PanelRoutes::resource('meta_tags', MetaTagController::class);
		PanelRoutes::resource('packages/promotion', PackageController::class);
		PanelRoutes::resource('packages/subscription', PackageController::class);
		PanelRoutes::resource('pages', PageController::class);
		PanelRoutes::resource('payments/promotion', PaymentController::class);
		PanelRoutes::resource('payments/subscription', PaymentController::class);
		PanelRoutes::resource('payment_methods', PaymentMethodController::class);
		PanelRoutes::resource('permissions', PermissionController::class);
		PanelRoutes::resource('pictures', PictureController::class);
		PanelRoutes::resource('post_types', PostTypeController::class);
		PanelRoutes::resource('posts', PostController::class);
		PanelRoutes::resource('report_types', ReportTypeController::class);
		PanelRoutes::resource('roles', RoleController::class);
		PanelRoutes::resource('salary_types', SalaryTypeController::class);
		PanelRoutes::resource('settings', SettingController::class);
		PanelRoutes::resource('users', UserController::class);
		
		// Others
		Route::get('account', [UserController::class, 'account']);
		Route::post('ajax/{table}/{field}', [InlineRequestController::class, 'make'])
			->where('table', '[^/]+')
			->where('field', '[^/]+');
		
		// Backup
		Route::controller(BackupController::class)
			->group(function ($router) {
				Route::get('backups', 'index');
				Route::put('backups/create', 'create');
				Route::get('backups/download', 'download');
				Route::delete('backups/delete', 'delete');
			});
		
		// Actions
		Route::controller(ActionController::class)
			->group(function ($router) {
				Route::get('actions/clear_cache', 'clearCache');
				Route::get('actions/clear_images_thumbnails', 'clearImagesThumbnails');
				Route::get('actions/maintenance/{mode}', 'maintenance')->where('mode', 'down|up');
			});
		
		// Re-send Email or Phone verification message
		Route::controller(UserController::class)
			->group(function ($router) {
				$router->pattern('id', '[0-9]+');
				Route::get('users/{id}/verify/resend/email', 'reSendEmailVerification');
				Route::get('users/{id}/verify/resend/sms', 'reSendPhoneVerification');
			});
		Route::controller(PostController::class)
			->group(function ($router) {
				$router->pattern('id', '[0-9]+');
				Route::get('posts/{id}/verify/resend/email', 'reSendEmailVerification');
				Route::get('posts/{id}/verify/resend/sms', 'reSendPhoneVerification');
			});
		
		// Plugins
		Route::controller(PluginController::class)
			->group(function ($router) {
				$router->pattern('plugin', '.+');
				Route::get('plugins', 'index');
				Route::post('plugins/{plugin}/install/code', 'installWithCode');
				Route::get('plugins/{plugin}/install', 'installWithoutCode');
				Route::get('plugins/{plugin}/uninstall', 'uninstall');
				Route::get('plugins/{plugin}/delete', 'delete');
			});
		
		// ENTERPRISE FEATURES - ADMIN ROUTES
		// Business Intelligence & Analytics
		Route::prefix('analytics')
			->controller(\App\Http\Controllers\Web\Admin\AnalyticsController::class)
			->group(function ($router) {
				Route::get('/', 'dashboard')->name('admin.analytics');
				Route::get('users', 'users')->name('admin.analytics.users');
				Route::get('jobs', 'jobs')->name('admin.analytics.jobs');
				Route::get('revenue', 'revenue')->name('admin.analytics.revenue');
				Route::get('reports', 'reports')->name('admin.analytics.reports');
				Route::post('reports/generate', 'generateReport')->name('admin.analytics.reports.generate');
			});

		// Learning Platform Management
		Route::prefix('learning')
			->controller(\App\Http\Controllers\Web\Admin\LearningController::class)
			->group(function ($router) {
				Route::get('/', 'index')->name('admin.learning');
				Route::get('courses', 'courses')->name('admin.learning.courses');
				Route::get('courses/create', 'createCourse')->name('admin.learning.courses.create');
				Route::post('courses', 'storeCourse')->name('admin.learning.courses.store');
				Route::get('courses/{course}/edit', 'editCourse')->name('admin.learning.courses.edit');
				Route::put('courses/{course}', 'updateCourse')->name('admin.learning.courses.update');
				Route::delete('courses/{course}', 'destroyCourse')->name('admin.learning.courses.destroy');
			});

		// ATS Integration Management
		Route::prefix('ats')
			->controller(\App\Http\Controllers\Web\Admin\ATSController::class)
			->group(function ($router) {
				Route::get('/', 'index')->name('admin.ats');
				Route::get('integrations', 'integrations')->name('admin.ats.integrations');
				Route::post('integrations', 'storeIntegration')->name('admin.ats.integrations.store');
				Route::get('sync-jobs', 'syncJobs')->name('admin.ats.sync-jobs');
			});

		// Messaging System Management
		Route::prefix('messaging')
			->controller(\App\Http\Controllers\Web\Admin\MessagingController::class)
			->group(function ($router) {
				Route::get('/', 'index')->name('admin.messaging');
				Route::get('conversations', 'conversations')->name('admin.messaging.conversations');
				Route::get('statistics', 'statistics')->name('admin.messaging.statistics');
			});

		// White Label Management
		Route::prefix('white-label')
			->controller(\App\Http\Controllers\Web\Admin\WhiteLabelController::class)
			->group(function ($router) {
				Route::get('/', 'index')->name('admin.white-label');
				Route::get('instances', 'instances')->name('admin.white-label.instances');
				Route::get('instances/create', 'createInstance')->name('admin.white-label.instances.create');
				Route::post('instances', 'storeInstance')->name('admin.white-label.instances.store');
			});

		// System Info
		Route::get('system', [SystemController::class, 'systemInfo']);
	});

// Files (JS, CSS, ...)
Route::controller(FileController::class)
	->prefix('common')
	->group(function ($router) {
		Route::get('js/intl-tel-input/countries.js', 'intlTelInputData');
	});
