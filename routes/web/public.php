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

use App\Http\Controllers\Web\Public\Account\CloseController;
use App\Http\Controllers\Web\Public\Account\CompanyController as AccountCompanyController;
use App\Http\Controllers\Web\Public\Account\EditController as AccountEditController;
use App\Http\Controllers\Web\Public\Account\MessagesController;
use App\Http\Controllers\Web\Public\Account\PostsController;
use App\Http\Controllers\Web\Public\Account\ResumeController as AccountResumeController;
use App\Http\Controllers\Web\Public\Account\SavedSearchesController;
use App\Http\Controllers\Web\Public\Account\SubscriptionController;
use App\Http\Controllers\Web\Public\Account\TransactionsController;
use App\Http\Controllers\Web\Public\Ajax\CategoryController as AjaxCategoryController;
use App\Http\Controllers\Web\Public\Ajax\Location\AutoCompleteController;
use App\Http\Controllers\Web\Public\Ajax\Location\ModalController;
use App\Http\Controllers\Web\Public\Ajax\Location\SelectController;
use App\Http\Controllers\Web\Public\Ajax\PostController as AjaxPostController;
use App\Http\Controllers\Web\Public\Ajax\UserController as AjaxUserController;
use App\Http\Controllers\Web\Public\Auth\ForgotPasswordController;
use App\Http\Controllers\Web\Public\Auth\LoginController;
use App\Http\Controllers\Web\Public\Auth\RegisterController;
use App\Http\Controllers\Web\Public\Auth\ResetPasswordController;
use App\Http\Controllers\Web\Public\Auth\SocialController;
use App\Http\Controllers\Web\Public\CountriesController;
use App\Http\Controllers\Web\Public\FileController;
use App\Http\Controllers\Web\Public\HomeController;
use App\Http\Controllers\Web\Public\Locale\LocaleController;
use App\Http\Controllers\Web\Public\Page\ContactController;
use App\Http\Controllers\Web\Public\Page\PricingController;
use App\Http\Controllers\Web\Public\Page\CmsController;
use App\Http\Controllers\Web\Public\Post\CreateOrEdit\MultiSteps\CreateController;
use App\Http\Controllers\Web\Public\Post\CreateOrEdit\MultiSteps\EditController;
use App\Http\Controllers\Web\Public\Post\CreateOrEdit\MultiSteps\PaymentController;
use App\Http\Controllers\Web\Public\Post\CreateOrEdit\SingleStep\CreateController as SingleCreateController;
use App\Http\Controllers\Web\Public\Post\CreateOrEdit\SingleStep\EditController as SingleEditController;
use App\Http\Controllers\Web\Public\Post\ShowController;
use App\Http\Controllers\Web\Public\Post\ReportController;
use App\Http\Controllers\Web\Public\Search\CategoryController;
use App\Http\Controllers\Web\Public\Search\CityController;
use App\Http\Controllers\Web\Public\Search\CompanyController;
use App\Http\Controllers\Web\Public\Search\SearchController;
use App\Http\Controllers\Web\Public\Search\TagController;
use App\Http\Controllers\Web\Public\Search\UserController;
use App\Http\Controllers\Web\Public\SitemapController;
use App\Http\Controllers\Web\Public\SitemapsController;
use App\Http\Controllers\Web\Public\CareerToolsController;
use Illuminate\Support\Facades\Route;

// Select Language
Route::namespace('Locale')
	->group(function ($router) {
		Route::get('locale/{code}', [LocaleController::class, 'setLocale']);
	});

// Subscription Management (New Enhanced Features)
Route::controller(\App\Http\Controllers\Web\Public\SubscriptionController::class)
	->group(function () {
		Route::get('pricing', 'pricing')->name('subscription.pricing');
		Route::get('subscription/manage', 'index')->name('account.subscription')->middleware('auth');
		Route::get('subscription/upgrade', 'upgrade')->name('subscription.upgrade')->middleware('auth');
		Route::post('subscription/subscribe', 'store')->name('subscription.subscribe')->middleware('auth');
		Route::delete('subscription/cancel', 'cancel')->name('subscription.cancel')->middleware('auth');
		Route::post('subscription/check-feature', 'checkFeature')->name('subscription.checkFeature')->middleware('auth');
	});

// ENTERPRISE FEATURES - WEB ROUTES
// Learning Platform
Route::prefix('learning')
	->controller(\App\Http\Controllers\Web\Public\LearningController::class)
	->middleware(['auth'])
	->group(function () {
		Route::get('/', 'index')->name('learning.index');
		Route::get('courses', 'courses')->name('learning.courses');
		Route::get('courses/{course}', 'showCourse')->name('learning.course.show');
		Route::get('courses/{course}/lessons/{lesson}', 'showLesson')->name('learning.lesson.show');
		Route::get('ide', 'ide')->name('learning.ide');
		Route::get('assessments', 'assessments')->name('learning.assessments');
	});

// Career Tools
Route::prefix('tools')
	->controller(\App\Http\Controllers\Web\Public\CareerToolsController::class)
	->group(function () {
		Route::get('/', 'index')->name('tools.index');
		Route::get('career-quiz', 'careerQuiz')->name('tools.career-quiz');
		Route::post('career-quiz', 'processCareerQuiz')->name('tools.career-quiz.process');
		Route::get('quiz-results/{token}', 'quizResults')->name('tools.quiz-results');
		Route::get('salary-calculator', 'salaryCalculator')->name('tools.salary-calculator');
		Route::get('career-planning', 'careerPlanning')->name('tools.career-planning');
		Route::get('profile-scoring', 'profileScoring')->name('tools.profile-scoring')->middleware(['auth']);
	});

// Business Intelligence Dashboard (Admin/Employers)
Route::prefix('analytics')
	->controller(\App\Http\Controllers\Web\Public\AnalyticsController::class)
	->middleware(['auth', 'verified'])
	->group(function () {
		Route::get('/', 'dashboard')->name('analytics.dashboard');
		Route::get('jobs', 'jobAnalytics')->name('analytics.jobs');
		Route::get('applications', 'applicationAnalytics')->name('analytics.applications');
		Route::get('reports', 'reports')->name('analytics.reports');
	});

// FILES
Route::controller(FileController::class)
	->prefix('common')
	->group(function ($router) {
		Route::get('file', 'watchMediaContent');
		Route::get('js/fileinput/locales/{code}.js', 'bootstrapFileinputLocales');
		Route::get('js/intl-tel-input/countries.js', 'intlTelInputData');
		Route::get('css/style.css', 'cssStyle');
	});

if (!plugin_exists('domainmapping')) {
	// SITEMAPS (XML)
	Route::get('sitemaps.xml', [SitemapsController::class, 'getAllCountriesSitemapIndex']);
}

// Impersonate (As admin user, login as another user)
Route::middleware(['auth'])
	->group(function ($router) {
		Route::impersonate();
	});


// HOMEPAGE
if (!doesCountriesPageCanBeHomepage()) {
	Route::get('/', [HomeController::class, 'index']);
	Route::get(dynamicRoute('routes.countries'), [CountriesController::class, 'index']);
} else {
	Route::get('/', [CountriesController::class, 'index']);
}


// AUTH
Route::namespace('Auth')
	->middleware(['guest', 'no.http.cache'])
	->group(function ($router) {
		// Registration Routes...
		Route::controller(RegisterController::class)
			->group(function ($router) {
				Route::get(dynamicRoute('routes.register'), 'showRegistrationForm');
				Route::post(dynamicRoute('routes.register'), 'register');
				Route::get('register/finish', 'finish');
			});
		
		// Authentication Routes...
		Route::controller(LoginController::class)
			->group(function ($router) {
				Route::get(dynamicRoute('routes.login'), 'showLoginForm');
				Route::post(dynamicRoute('routes.login'), 'login');
			});
		
		// Forgot Password Routes...
		Route::controller(ForgotPasswordController::class)
			->group(function ($router) {
				Route::get('password/reset', 'showLinkRequestForm');
				Route::post('password/email', 'sendResetLink');
				
				// Email Address or Phone Number verification
				$router->pattern('field', 'email|phone');
				$router->pattern('token', '.*');
				Route::get('password/{id}/verify/resend/email', 'reSendEmailVerification');
				Route::get('password/{id}/verify/resend/sms', 'reSendPhoneVerification');
				Route::get('password/verify/{field}/{token?}', 'verification');
				Route::post('password/verify/{field}/{token?}', 'verification');
			});
		
		Route::controller(ResetPasswordController::class)
			->group(function ($router) {
				// Reset Password using Token
				Route::get('password/token', 'showTokenRequestForm');
				Route::post('password/token', 'sendResetToken');
				
				// Reset Password using Link (Core Routes...)
				Route::get('password/reset/{token}', 'showResetForm');
				Route::post('password/reset', 'reset');
			});
		
		// Social Authentication
		Route::controller(SocialController::class)
			->group(function ($router) {
				$router->pattern('provider', 'facebook|linkedin|twitter-oauth-2|twitter|google');
				Route::get('auth/{provider}', 'redirectToProvider');
				Route::get('auth/{provider}/callback', 'handleProviderCallback');
			});
	});

Route::namespace('Auth')
	->group(function ($router) {
		// Email Address or Phone Number verification
		Route::controller(RegisterController::class)
			->group(function ($router) {
				$router->pattern('field', 'email|phone');
				Route::get('users/{id}/verify/resend/email', 'reSendEmailVerification');
				Route::get('users/{id}/verify/resend/sms', 'reSendPhoneVerification');
				Route::get('users/verify/{field}/{token?}', 'verification');
				Route::post('users/verify/{field}/{token?}', 'verification');
			});
		
		// User Logout
		Route::get(dynamicRoute('routes.logout'), [LoginController::class, 'logout']);
	});


// POSTS
Route::namespace('Post')
	->group(function ($router) {
		$router->pattern('id', '[0-9]+');
		
		$hidPrefix = config('larapen.core.hashableIdPrefix');
		if (is_string($hidPrefix) && !empty($hidPrefix)) {
			$router->pattern('hashableId', '([0-9]+)?(' . $hidPrefix . '[a-z0-9A-Z]{11})?');
		} else {
			$router->pattern('hashableId', '([0-9]+)?([a-z0-9A-Z]{11})?');
		}
		
		// $router->pattern('slug', '.*');
		$bannedSlugs = regexSimilarRoutesPrefixes();
		if (!empty($bannedSlugs)) {
			/*
			 * NOTE:
			 * '^(?!companies|users)$' : Don't match 'companies' or 'users'
			 * '^(?=.*)$'              : Match any character
			 * '^((?!\/).)*$'          : Match any character, but don't match string with '/'
			 */
			$router->pattern('slug', '^(?!' . implode('|', $bannedSlugs) . ')(?=.*)((?!\/).)*$');
		} else {
			$router->pattern('slug', '^(?=.*)((?!\/).)*$');
		}
		
		// SingleStep Post creation
		Route::namespace('CreateOrEdit\SingleStep')
			->controller(SingleCreateController::class)
			->group(function ($router) {
				Route::get('create', 'getForm');
				Route::post('create', 'postForm');
				Route::get('create/finish', 'finish');
				
				// Payment Gateway Success & Cancel
				Route::get('create/payment/success', 'paymentConfirmation');
				Route::get('create/payment/cancel', 'paymentCancel');
				Route::post('create/payment/success', 'paymentConfirmation');
				
				// Email Address or Phone Number verification
				$router->pattern('field', 'email|phone');
				Route::get('posts/{id}/verify/resend/email', 'reSendEmailVerification');
				Route::get('posts/{id}/verify/resend/sms', 'reSendPhoneVerification');
				Route::get('posts/verify/{field}/{token?}', 'verification');
				Route::post('posts/verify/{field}/{token?}', 'verification');
			});
		
		// MultiSteps Post creation
		Route::namespace('CreateOrEdit\MultiSteps')
			->controller(CreateController::class)
			->group(function ($router) {
				Route::get('posts/create', 'getPostStep');
				Route::post('posts/create', 'postPostStep');
				Route::get('posts/create/payment', 'getPaymentStep');
				Route::post('posts/create/payment', 'postPaymentStep');
				Route::post('posts/create/finish', 'finish');
				Route::get('posts/create/finish', 'finish');
				
				// Payment Gateway Success & Cancel
				Route::get('posts/create/payment/success', 'paymentConfirmation');
				Route::post('posts/create/payment/success', 'paymentConfirmation');
				Route::get('posts/create/payment/cancel', 'paymentCancel');
				
				// Email Address or Phone Number verification
				$router->pattern('field', 'email|phone');
				Route::get('posts/{id}/verify/resend/email', 'reSendEmailVerification');
				Route::get('posts/{id}/verify/resend/sms', 'reSendPhoneVerification');
				Route::get('posts/verify/{field}/{token?}', 'verification');
				Route::post('posts/verify/{field}/{token?}', 'verification');
			});
		
		Route::middleware(['auth'])
			->group(function ($router) {
				$router->pattern('id', '[0-9]+');
				
				// SingleStep Post edition
				Route::namespace('CreateOrEdit\SingleStep')
					->controller(SingleEditController::class)
					->group(function ($router) {
						Route::get('edit/{id}', 'getForm');
						Route::put('edit/{id}', 'postForm');
						
						// Payment Gateway Success & Cancel
						Route::get('edit/{id}/payment/success', 'paymentConfirmation');
						Route::get('edit/{id}/payment/cancel', 'paymentCancel');
						Route::post('edit/{id}/payment/success', 'paymentConfirmation');
					});
				
				// MultiSteps Post edition
				Route::namespace('CreateOrEdit\MultiSteps')
					->group(function ($router) {
						Route::controller(EditController::class)
							->group(function ($router) {
								Route::get('posts/{id}/edit', 'getForm');
								Route::put('posts/{id}/edit', 'postForm');
							});
						Route::controller(PaymentController::class)
							->group(function ($router) {
								Route::get('posts/{id}/payment', 'getForm');
								Route::post('posts/{id}/payment', 'postForm');
								
								// Payment Gateway Success & Cancel
								Route::get('posts/{id}/payment/success', 'paymentConfirmation');
								Route::post('posts/{id}/payment/success', 'paymentConfirmation');
								Route::get('posts/{id}/payment/cancel', 'paymentCancel');
							});
					});
			});
		
		// Post's Details
		Route::get(dynamicRoute('routes.post'), [ShowController::class, 'index']);
		
		// Send report abuse
		Route::controller(ReportController::class)
			->group(function ($router) {
				Route::get('posts/{hashableId}/report', 'showReportForm');
				Route::post('posts/{hashableId}/report', 'sendReport');
			});
	});


Route::namespace('Search')
	->group(function ($router) {
		Route::post('send-by-email', [SearchController::class, 'sendByEmail']);
	});


// ACCOUNT
Route::namespace('Account')
	->prefix('account')
	->group(function ($router) {
		// Messenger
		// Contact Job's Author
		Route::post('messages/posts/{id}', [MessagesController::class, 'store']);
		
		Route::middleware(['auth', 'banned.user', 'no.http.cache'])
			->group(function ($router) {
				$router->pattern('id', '[0-9]+');
				
				// Users
				Route::controller(AccountEditController::class)
					->group(function ($router) {
						Route::get('/', 'index');
						Route::middleware(['impersonate.protect'])
							->group(function ($router) {
								Route::put('/', 'updateDetails');
								Route::put('settings', 'updateDetails');
								Route::put('photo', 'updatePhoto');
								Route::put('photo/delete', 'deletePhoto');
							});
					});
				Route::controller(CloseController::class)
					->group(function ($router) {
						Route::get('close', 'index');
						Route::middleware(['impersonate.protect'])
							->group(function ($router) {
								Route::post('close', 'submit');
							});
					});
				
				// Subscription
				Route::controller(SubscriptionController::class)
					->group(function ($router) {
						Route::get('subscription', 'getForm');
						Route::post('subscription', 'postForm');
						
						// Payment Gateway Success & Cancel
						Route::get('{id}/payment/success', 'paymentConfirmation');
						Route::post('{id}/payment/success', 'paymentConfirmation');
						Route::get('{id}/payment/cancel', 'paymentCancel');
					});
				
				// New Subscription Management
				Route::get('subscription-management', [\App\Http\Controllers\Web\Public\SubscriptionController::class, 'index'])
					->name('account.subscription-management');
				
				// Companies
				Route::prefix('companies')
					->controller(AccountCompanyController::class)
					->group(function ($router) {
						Route::get('/', 'index');
						Route::get('create', 'create');
						Route::post('/', 'store');
						Route::get('{id}', 'show');
						Route::get('{id}/edit', 'edit');
						Route::put('{id}', 'update');
						Route::get('{id}/delete', 'destroy');
						Route::post('delete', 'destroy');
					});
				
				// Resumes
				Route::prefix('resumes')
					->controller(AccountResumeController::class)
					->group(function ($router) {
						Route::get('/', 'index');
						Route::get('create', 'create');
						Route::post('/', 'store');
						Route::get('{id}', 'show');
						Route::get('{id}/edit', 'edit');
						Route::put('{id}', 'update');
						Route::get('{id}/delete', 'destroy');
						Route::post('delete', 'destroy');
					});
				
				// Posts
				Route::controller(PostsController::class)
					->prefix('posts')
					->group(function ($router) {
						$router->pattern('pagePath', '(list|archived|pending-approval|favourite)+');
						Route::get('{pagePath}', 'getPage');
						Route::get('{pagePath}/{id}/delete', 'destroy');
						Route::post('{pagePath}/delete', 'destroy');
						
						Route::get('list/{id}/offline', 'index');
						Route::get('archived/{id}/repost', 'archivedPosts');
					});
				
				// Saved Searches
				Route::controller(SavedSearchesController::class)
					->prefix('saved-searches')
					->group(function ($router) {
						$router->pattern('id', '[0-9]+');
						Route::get('/', 'index');
						Route::get('{id}', 'show');
						Route::get('{id}/delete', 'destroy');
						Route::post('delete', 'destroy');
					});
				
				// Messenger
				Route::controller(MessagesController::class)
					->prefix('messages')
					->group(function ($router) {
						$router->pattern('id', '[0-9]+');
						Route::post('check-new', 'checkNew');
						Route::get('/', 'index');
						Route::post('/', 'store');
						Route::get('{id}', 'show');
						Route::put('{id}', 'update');
						Route::get('{id}/actions', 'actions');
						Route::post('actions', 'actions');
						Route::get('{id}/delete', 'destroy');
						Route::post('delete', 'destroy');
					});
				
				// Transactions
				Route::namespace('Transactions')
					->prefix('transactions')
					->group(function ($router) {
						Route::get('promotion', [TransactionsController::class, 'index']);
						Route::get('subscription', [TransactionsController::class, 'index']);
					});
			});
	});


// AJAX
Route::namespace('Ajax')
	->prefix('ajax')
	->group(function ($router) {
		Route::namespace('Location')
			->group(function ($router) {
				$router->pattern('countryCode', getCountryCodeRoutePattern());
				Route::post('countries/{countryCode}/cities/autocomplete', [AutoCompleteController::class, 'index']);
				Route::controller(SelectController::class)
					->group(function ($router) {
						$router->pattern('id', '[0-9]+');
						Route::get('countries/{countryCode}/admins/{adminType}', 'getAdmins');
						Route::get('countries/{countryCode}/admins/{adminType}/{adminCode}/cities', 'getCities');
						Route::get('countries/{countryCode}/cities/{id}', 'getSelectedCity');
					});
				Route::controller(ModalController::class)
					->group(function ($router) {
						Route::post('locations/{countryCode}/admins/{adminType}', 'getAdmins');
						Route::post('locations/{countryCode}/admins/{adminType}/{adminCode}/cities', 'getCities');
						Route::post('locations/{countryCode}/cities', 'getCities');
					});
			});
		Route::controller(AjaxCategoryController::class)
			->group(function ($router) {
				Route::post('categories/select', 'getCategoriesHtml');
			});
		Route::controller(AjaxPostController::class)
			->group(function ($router) {
				Route::post('save/post', 'savePost');
				Route::post('save/search', 'saveSearch');
				Route::post('post/phone', 'getPhone');
			});
		Route::controller(AjaxUserController::class)
			->group(function ($router) {
				Route::post('user/dark-mode', 'setDarkMode');
			});
	});


// FEEDS
Route::feeds();


if (!plugin_exists('domainmapping')) {
	// SITEMAPS (XML)
	Route::controller(SitemapsController::class)
		->group(function ($router) {
			$router->pattern('countryCode', getCountryCodeRoutePattern());
			Route::get('{countryCode}/sitemaps.xml', 'getSitemapIndexByCountry');
			Route::get('{countryCode}/sitemaps/pages.xml', 'getPagesSitemapByCountry');
			Route::get('{countryCode}/sitemaps/categories.xml', 'getCategoriesSitemapByCountry');
			Route::get('{countryCode}/sitemaps/cities.xml', 'getCitiesSitemapByCountry');
			Route::get('{countryCode}/sitemaps/posts.xml', 'getListingsSitemapByCountry');
		});
}


// PAGES
Route::namespace('Page')
	->group(function ($router) {
		Route::get(dynamicRoute('routes.pricing'), [PricingController::class, 'index']);
		Route::get(dynamicRoute('routes.pageBySlug'), [CmsController::class, 'index']);
		Route::controller(ContactController::class)
			->group(function ($router) {
				Route::get(dynamicRoute('routes.contact'), 'getForm');
				Route::post(dynamicRoute('routes.contact'), 'postForm');
			});
	});


// SITEMAP (HTML)
Route::get(dynamicRoute('routes.sitemap'), [SitemapController::class, 'index']);


// SEARCH
Route::group(['namespace' => 'Search'], function ($router) {
	$router->pattern('id', '[0-9]+');
	$router->pattern('username', '[a-zA-Z0-9]+');
	Route::get(dynamicRoute('routes.companies'), [CompanyController::class, 'index']);
	Route::get(dynamicRoute('routes.search'), [SearchController::class, 'index']);
	Route::get(dynamicRoute('routes.searchPostsByUserId'), [UserController::class, 'index']);
	Route::get(dynamicRoute('routes.searchPostsByUsername'), [UserController::class, 'profile']);
	Route::get(dynamicRoute('routes.searchPostsByCompanyId'), [CompanyController::class, 'profile']);
	Route::get(dynamicRoute('routes.searchPostsByTag'), [TagController::class, 'index']);
	Route::get(dynamicRoute('routes.searchPostsByCity'), [CityController::class, 'index']);
	Route::get(dynamicRoute('routes.searchPostsBySubCat'), [CategoryController::class, 'index']);
	Route::get(dynamicRoute('routes.searchPostsByCat'), [CategoryController::class, 'index']);
});


// CAREER TOOLS
Route::controller(CareerToolsController::class)
	->group(function () {
		// Salary Calculator
		Route::get('salary-calculator', 'salaryCalculator')->name('salary-calculator');
		
		// Career Assessment/Quiz
		Route::get('career-quiz', 'careerQuiz')->name('career-quiz');
		Route::get('career-quiz/{type}', 'careerQuiz')->name('career-quiz.type');
		Route::get('career-quiz/results/{resultId}', 'quizResults')->name('career-quiz.results');
		
		// Career Guides
		Route::get('career-guides', 'careerGuides')->name('career-guides');
		Route::get('career-guides/{guide:slug}', 'showCareerGuide')->name('career-guide.show');
		
		// Career Planning
		Route::get('career-planning', 'careerPlanning')->name('career-planning')->middleware('auth');
		
		// Profile Scoring
		Route::get('profile-scoring', 'profileScoring')->name('profile-scoring')->middleware('auth');
	});
