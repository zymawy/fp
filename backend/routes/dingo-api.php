<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CauseController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\DonationController;
use Dingo\Api\Routing\Router;

/** @var Router $api */
$api = app(Router::class);

// Version 1 of our API
$api->version('v1', [
    'namespace' => 'App\Http\Controllers\Api',
    'middleware' => ['api'],
], function (Router $api) {
    
    // Public routes
    $api->group(['prefix' => 'auth'], function (Router $api) {
        // Replace these with your actual auth controllers if you have them
        // $api->post('register', 'AuthController@register');
        // $api->post('login', 'AuthController@login');
    });
    
    // Categories endpoints
    $api->group(['prefix' => 'categories'], function (Router $api) {
        $api->get('/', 'CategoryController@index');
        $api->get('/{id}', 'CategoryController@show');
        
        // Protected routes
        $api->group(['middleware' => ['api.auth']], function (Router $api) {
            $api->post('/', 'CategoryController@store');
            $api->put('/{category}', 'CategoryController@update');
            $api->delete('/{category}', 'CategoryController@destroy');
        });
    });
    
    // Causes endpoints with filtering
    $api->group(['prefix' => 'causes'], function (Router $api) {
        $api->get('/', 'CauseController@index');
        $api->get('/{id}', 'CauseController@show');
        
        // Protected routes
        $api->group(['middleware' => ['api.auth']], function (Router $api) {
            $api->post('/', 'CauseController@store');
            $api->put('/{cause}', 'CauseController@update');
            $api->delete('/{cause}', 'CauseController@destroy');
        });
    });
    
    // Donations endpoints
    $api->group(['prefix' => 'donations'], function (Router $api) {
        $api->get('/', 'DonationController@index');
        $api->get('/{id}', 'DonationController@show');
        
        // Protected routes that require admin access
        $api->group(['middleware' => ['api.auth']], function (Router $api) {
            $api->post('/', 'DonationController@store');
            $api->put('/{id}', 'DonationController@update');
            $api->delete('/{id}', 'DonationController@destroy');
        });
    });
    
    // Users endpoints
    $api->group(['prefix' => 'users'], function (Router $api) {
        // Public routes
        // None typically, unless you have public user profiles
        
        // Protected routes - All users
        $api->group(['middleware' => ['api.auth']], function (Router $api) {
            // Regular user can only access their own info
            $api->get('/me', 'UserController@me');
        });
        
        // Admin-only routes
        $api->group(['middleware' => ['api.auth']], function (Router $api) {
            // These routes will be protected in the controller
            // by checking if the user has admin role
            $api->get('/', 'UserController@index');
            $api->post('/', 'UserController@store');
            $api->get('/{id}', 'UserController@show');
            $api->put('/{id}', 'UserController@update');
            $api->delete('/{id}', 'UserController@destroy');
        });
    });
    
    // Protected routes for all endpoints
    $api->group(['middleware' => ['api.auth']], function (Router $api) {
        $api->get('user', function () {
            return auth()->user();
        });
    });
}); 