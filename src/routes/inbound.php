<?php
use Illuminate\Support\Facades\Route;
use Martin3r\LaravelInboundOutboundMail\Http\Controllers\InboundController;


Route::post('/postmark/inbound', InboundController::class)
     ->middleware('verify.postmark.basic')
     ->name('postmark.inbound');