<?php
// Original Dingo API routes
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CauseController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\FinancialReportController;
use App\Http\Controllers\Api\PartnerController;
use Dingo\Api\Routing\Router;

$api = app(Router::class);

$api->version('v1', function (Router $api) {
    // Public routes
    $api->post('register', [AuthController::class, 'register'])->name('auth.register');
    $api->post('login', [AuthController::class, 'login'])->name('auth.login');
    $api->post('forgot-password', [AuthController::class, 'forgotPassword'])->name('auth.forgot-password');
    $api->post('reset-password', [AuthController::class, 'resetPassword'])->name('auth.reset-password');

    // Public resource routes
    $api->get('categories', [CategoryController::class, 'index'])->name('categories.index');
    $api->get('categories/{category}', [CategoryController::class, 'show'])->name('categories.show');
    $api->get('causes', [CauseController::class, 'index'])->name('causes.index');
    $api->get('causes/{cause}', [CauseController::class, 'show'])->name('causes.show');
    $api->get('partners', [PartnerController::class, 'index'])->name('partners.index');
    $api->get('partners/{partner}', [PartnerController::class, 'show'])->name('partners.show');
    $api->get('users', [UserController::class, 'index'])->name('users.index');
    $api->get('users/{user}', [UserController::class, 'show'])->name('users.show');
    
    // Temporary public access to donations routes
    // Donations routes
    $api->get('donations', [DonationController::class, 'index'])->name('donations.index');
    $api->post('donations', [DonationController::class, 'store'])->name('donations.store');
    $api->get('donations/{donation}', [DonationController::class, 'show'])->name('donations.show');
    $api->put('donations/{donation}', [DonationController::class, 'update'])->name('donations.update');
    $api->delete('donations/{donation}', [DonationController::class, 'destroy'])->name('donations.destroy');

    // Protected routes
    $api->group(['middleware' => 'api.auth'], function (Router $api) {
        $api->post('logout', [AuthController::class, 'logout'])->name('auth.logout');
        $api->post('refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
        $api->get('user', [UserController::class, 'me'])->name('user.profile');
        $api->put('user', [UserController::class, 'update'])->name('user.update');

        // Admin routes
        $api->group(['middleware' => 'admin'], function (Router $api) {
            // Admin-only resource routes
            $api->post('categories', [CategoryController::class, 'store'])->name('categories.store');
            $api->put('categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
            $api->delete('categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

            $api->post('causes', [CauseController::class, 'store'])->name('causes.store');
            $api->put('causes/{cause}', [CauseController::class, 'update'])->name('causes.update');
            $api->delete('causes/{cause}', [CauseController::class, 'destroy'])->name('causes.destroy');

            $api->post('partners', [PartnerController::class, 'store'])->name('partners.store');
            $api->put('partners/{partner}', [PartnerController::class, 'update'])->name('partners.update');
            $api->delete('partners/{partner}', [PartnerController::class, 'destroy'])->name('partners.destroy');

            $api->post('users', [UserController::class, 'store'])->name('users.store');
            $api->put('users/{user}', [UserController::class, 'update'])->name('users.update');
            $api->delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

            // Financial reports routes
            $api->get('financial-reports', [FinancialReportController::class, 'index'])->name('financial-reports.index');
            $api->post('financial-reports', [FinancialReportController::class, 'store'])->name('financial-reports.store');
            $api->get('financial-reports/{financial_report}', [FinancialReportController::class, 'show'])->name('financial-reports.show');
            $api->put('financial-reports/{financial_report}', [FinancialReportController::class, 'update'])->name('financial-reports.update');
            $api->delete('financial-reports/{financial_report}', [FinancialReportController::class, 'destroy'])->name('financial-reports.destroy');
        });

        // Transactions routes
        $api->get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
        $api->get('transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
    });
});

