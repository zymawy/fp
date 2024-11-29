<?php


/** @var Router $api */

use Dingo\Api\Routing\Router;

$api = app('Dingo\Api\Routing\Router');

$api->version(['v1', 'v2'], function (Router $api) {
    $api->get('routes', fn() => response()->json((new \Tighten\Ziggy\Ziggy)->toArray()));
    $api->resource('users', 'App\Http\Controllers\UserController');
    $api->resource('causes', 'App\Http\Controllers\CauseController');
    $api->resource('donations', 'App\Http\Controllers\DonationController');
    $api->resource('transactions', 'App\Http\Controllers\TransactionController');
    $api->resource('financial-reports', 'App\Http\Controllers\FinancialReportController');
});