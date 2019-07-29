<?php

use Illuminate\Support\Facades\Route;


Route::get('/health', [
    'uses' => 'Vijayd28\LaravelSQS\Http\Controllers\QueueController@',
]);
