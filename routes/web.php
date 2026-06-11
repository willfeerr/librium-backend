<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response('', 302)->header('Location', '/api/docs');
});
