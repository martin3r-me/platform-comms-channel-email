<?php

namespace Platform\Comms\ChannelEmail\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyPostmarkBasicAuth
{
    public function handle(Request $request, Closure $next)
    {
        $user = config('comms-email.inbound.username');
        $pass = config('comms-email.inbound.password');

        $expected = 'Basic ' . base64_encode("{$user}:{$pass}");
        $actual = $request->header('Authorization');

        abort_unless(
            hash_equals($expected, (string) $actual),
            401,
            'Invalid Postmark inbound credentials.'
        );

        return $next($request);
    }
}