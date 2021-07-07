<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return env('APP_NAME') . ' v' . env('APP_VERSION') . ' (' . env('APP_ENV') . ')';
});
$router->group(['prefix' => 'v1'], function () use ($router) {
    $router->group(['prefix' => 'auth'], function () use ($router) {
        $router->post('login', ['as' => 'authLogin', 'uses' => 'AuthController@login']);
        $router->post('login/sso', ['as' => 'authLoginSso', 'uses' => 'AuthController@loginSso']);
    });
});
