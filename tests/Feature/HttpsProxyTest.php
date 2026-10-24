<?php

namespace Tests\Feature;

use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Online, visitors reach Laravel through an HTTPS proxy (ngrok, or the hosting
 * company's front server) that talks plain HTTP to Laravel and says so in the
 * X-Forwarded-* headers. Laravel must trust them, or it builds http:// links
 * on an https:// page and the browser blocks the site's scripts and styles.
 */
class HttpsProxyTest extends TestCase
{
    public function test_pages_behind_an_https_proxy_link_their_files_over_https(): void
    {
        $html = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->withHeaders([
                'X-Forwarded-Proto' => 'https',
                'X-Forwarded-Host' => 'foundationlink.ngrok-free.app',
                'X-Forwarded-Port' => '443',
            ])
            ->get('/login')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('https://foundationlink.ngrok-free.app/build/', $html);
        $this->assertStringNotContainsString('http://foundationlink.ngrok-free.app', $html);
    }

    public function test_the_visitors_real_address_is_used_not_the_proxys(): void
    {
        // The login limiter counts tries per email + visitor address; behind a
        // proxy every visitor would otherwise share the proxy's address.
        $seen = null;
        Event::listen(RouteMatched::class, function (RouteMatched $e) use (&$seen) {
            $seen = $e->request->ip();
        });

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->withHeaders(['X-Forwarded-For' => '203.0.113.7'])
            ->get('/login')
            ->assertOk();

        $this->assertSame('203.0.113.7', $seen);
    }
}
