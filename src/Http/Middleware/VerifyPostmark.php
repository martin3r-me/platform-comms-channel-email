<?php


namespace Martin3r\LaravelInboundOutboundMail\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyPostmark
{
    public function handle(Request $request, Closure $next)
    {
        $raw      = $request->getContent();
        $header   = $request->header('X-Postmark-Signature');
        $secret   = env('POSTMARK_INBOUND_SECRET');

        $expected = base64_encode(hash_hmac('sha256', $raw, $secret, true));

        abort_unless(hash_equals($expected, $header), 401, 'Invalid signature.');

        return $next($request);
    }
}