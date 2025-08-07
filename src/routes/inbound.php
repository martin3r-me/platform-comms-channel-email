<?php

use Illuminate\Support\Facades\Route;
use Platform\Comms\ChannelEmail\Http\Controllers\InboundPostmarkController;

Route::post('/postmark/inbound', InboundPostmarkController::class)
    ->middleware('verify.postmark.basic')
    ->name('comms.channel.email.postmark.inbound');