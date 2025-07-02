<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| SPA (Single Page Application) routes for Vue.js
|
*/

// SPA用のルート設定（Vue Routerの履歴モード対応）
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');
