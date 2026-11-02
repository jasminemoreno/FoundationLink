<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted proxies
    |--------------------------------------------------------------------------
    |
    | Only these addresses may tell Laravel the visitor's real address and that
    | the visit is https (the X-Forwarded-* headers). Anyone else could fake them
    | to dodge the login lock. ngrok connects from this computer, so the default
    | covers it. On a host where every visit passes through its front server, and
    | the site can't be reached directly, set TRUSTED_PROXIES=* in .env.
    |
    */

    'proxies' => env('TRUSTED_PROXIES', '127.0.0.1,::1'),

];
