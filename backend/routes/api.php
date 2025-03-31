<?php
// Unified API Routes file combining Laravel and Dingo API routes
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CauseController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DonationController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\FinancialReportController;
use App\Http\Controllers\Api\PartnerController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\CentrifugoController;
use App\Http\Controllers\Api\AdminPanelApiController;
use App\Http\Controllers\Api\ReportsController;
use Dingo\Api\Routing\Router;

/** @var Router $api */
$api = app(Router::class);

// Version 1 of our API
$api->version('v1', [
    'namespace' => 'App\Http\Controllers\Api',
    'middleware' => ['api'],
], function (Router $api) {
    // Public routes
    $api->post('auth/register', 'Auth\AuthController@register')->name('auth.register');
    $api->post('auth/login', 'Auth\AuthController@login')->name('auth.login');
    $api->post('auth/forgot-password', 'Auth\AuthController@forgotPassword')->name('auth.forgot-password');
    $api->post('auth/reset-password', 'Auth\AuthController@resetPassword')->name('auth.reset-password');

    // Public resource routes
    $api->get('categories', 'CategoryController@index')->name('categories.index');
    $api->get('categories/{category}', 'CategoryController@show')->name('categories.show');

    $api->get('causes', 'CauseController@index')->name('causes.index');
    $api->get('causes/{cause}', 'CauseController@show')->name('causes.show');

    $api->get('partners', 'PartnerController@index')->name('partners.index');
    $api->get('partners/{partner}', 'PartnerController@show')->name('partners.show');

    $api->get('users', 'UserController@index')->name('users.index');
    $api->get('users/{user}', 'UserController@show')->name('users.show');

    // Payment routes - public
    $api->post('payments/process', 'PaymentController@process')->name('payments.process');
    $api->get('payments/{id}/status', 'PaymentController@verifyStatus')->name('payments.status');
    $api->post('payments/webhook', 'PaymentController@webhook')->name('payments.webhook');
    $api->get('payments/callback', 'PaymentController@callback')->name('payments.callback');
    $api->get('payments/error', 'PaymentController@errorCallback')->name('payments.error');
    $api->post('payments/donation-callback', 'DonationController@paymentCallback')->name('payments.donation-callback');

    // Payment methods routes
    $api->get('payment-methods', 'PaymentMethodController@index')->name('payment-methods.index');
    $api->get('payment-methods/{id}', 'PaymentMethodController@show')->name('payment-methods.show');
    $api->post('payment-methods/initiate', 'PaymentMethodController@initiatePayment')->name('payment-methods.initiate');
    $api->get('payment-methods/refresh', 'PaymentMethodController@refresh')->name('payment-methods.refresh');

    // Temporary public access to donations routes - IMPORTANT: Move these behind authentication later
    $api->get('donations', 'DonationController@index')->name('donations.index');
    $api->post('donations', 'DonationController@store')->name('donations.store');
    $api->get('donations/{donation}', 'DonationController@show')->name('donations.show');

    // Routes for admin panel
    $api->get('admin/dashboard/stats', 'AdminPanelApiController@getDashboardStats')->name('admin.dashboard.stats');
    $api->get('admin/dashboard/trends', 'AdminPanelApiController@getDonationTrends')->name('admin.dashboard.trends');
    $api->get('admin/dashboard/activity', 'AdminPanelApiController@getRecentActivity')->name('admin.dashboard.activity');
    $api->get('admin/dashboard/user-growth', 'AdminPanelApiController@getUserGrowth')->name('admin.dashboard.user-growth');
    
    // New Reports API route
    $api->get('reports', 'ReportsController@getReports')->name('reports');

    // Protected routes with JWT authentication
    $api->group(['middleware' => 'api.auth'], function (Router $api) {
        $api->post('auth/logout', 'Auth\AuthController@logout')->name('auth.logout');
        $api->post('auth/refresh', 'Auth\AuthController@refresh')->name('auth.refresh');
        $api->get('user', 'UserController@me')->name('user.profile');
        $api->put('user', 'UserController@update')->name('user.update');

        // Profile routes
        $api->get('profile', 'UserController@profile')->name('profile.show');
        $api->get('api/profile', 'UserController@profile')->name('profile.show');
        $api->post('profile', 'UserController@updateProfile')->name('profile.update');

        // Upload routes
        $api->post('upload/avatar', 'UploadController@uploadAvatar')->name('upload.avatar');

        // User achievements and statistics
        $api->get('/achievements', 'UserController@achievements')->name('users.achievements');
        $api->get('api/users/{user}/achievements', 'UserController@achievements')->name('users.achievements');
        $api->get('users/{user}/statistics', 'UserController@statistics')->name('users.statistics');
        $api->get('api/users/{user}/statistics', 'UserController@statistics')->name('users.statistics');

        // Donations - authenticated operations
        $api->put('donations/{donation}', 'DonationController@update')->name('donations.update');
        $api->delete('donations/{donation}', 'DonationController@destroy')->name('donations.destroy');

        // Transactions - authenticated operations
        $api->get('transactions', 'TransactionController@index')->name('transactions.index');
        $api->get('transactions/{transaction}', 'TransactionController@show')->name('transactions.show');

        // Centrifugo token endpoints
        $api->get('centrifugo/tokens', 'CentrifugoController@getTokens')->name('centrifugo.tokens');
        $api->get('centrifugo/tokens/cause/{causeId}', 'CentrifugoController@getCauseSubscriptionToken')->name('centrifugo.cause.token');

        // Admin-only routes
        $api->group(['middleware' => 'admin'], function (Router $api) {
            // Dashboard routes
            $api->get('dashboard/stats', 'DashboardController@getStats')->name('dashboard.stats');
            $api->get('dashboard/trends', 'DashboardController@getTrends')->name('dashboard.trends');
            $api->get('dashboard/recent-donations', 'DashboardController@getRecentDonations')->name('dashboard.recent-donations');

            // Admin-only resource routes
            $api->post('categories', 'CategoryController@store')->name('categories.store');
            $api->put('categories/{category}', 'CategoryController@update')->name('categories.update');
            $api->delete('categories/{category}', 'CategoryController@destroy')->name('categories.destroy');

            $api->post('causes', 'CauseController@store')->name('causes.store');
            $api->put('causes/{cause}', 'CauseController@update')->name('causes.update');
            $api->delete('causes/{cause}', 'CauseController@destroy')->name('causes.destroy');

            $api->post('partners', 'PartnerController@store')->name('partners.store');
            $api->put('partners/{partner}', 'PartnerController@update')->name('partners.update');
            $api->delete('partners/{partner}', 'PartnerController@destroy')->name('partners.destroy');

            $api->post('users', 'UserController@store')->name('users.store');
            $api->put('users/{user}', 'UserController@update')->name('users.update');
            $api->delete('users/{user}', 'UserController@destroy')->name('users.destroy');

            // Financial reports routes
            $api->get('financial-reports', 'FinancialReportController@index')->name('financial-reports.index');
            $api->post('financial-reports', 'FinancialReportController@store')->name('financial-reports.store');
            $api->get('financial-reports/{financial_report}', 'FinancialReportController@show')->name('financial-reports.show');
            $api->put('financial-reports/{financial_report}', 'FinancialReportController@update')->name('financial-reports.update');
            $api->delete('financial-reports/{financial_report}', 'FinancialReportController@destroy')->name('financial-reports.destroy');
        });
    });
});

