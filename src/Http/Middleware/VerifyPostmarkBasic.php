<?php
namespace Martin3r\LaravelInboundOutboundMail\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyPostmarkBasic
{
    public function handle(Request $request, Closure $next)
    {
        $expected = base64_encode(
            env('POSTMARK_INBOUND_USER').':'.env('POSTMARK_INBOUND_PASS')
        );

        // Header ist "Basic {base64}"
        $header = $request->header('Authorization');
        abort_unless(
            $header === 'Basic '.$expected,
            401,
            'Invalid Postmark credentials.'
        );

        return $next($request);
    }
}