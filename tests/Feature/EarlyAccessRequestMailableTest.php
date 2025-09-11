<?php

namespace Tests\Feature;

use App\Mail\EarlyAccessRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class EarlyAccessRequestMailableTest extends TestCase
{
    public function test_mailable_content(): void
    {
        $mailable = new EarlyAccessRequest('team', 'telegram', 'agencyName', 'sourceBlock');

        $mailable->assertSeeInHtml('team');
        $mailable->assertSeeInHtml('telegram');
        $mailable->assertSeeInHtml('agencyName');
        $mailable->assertSeeInHtml('sourceBlock');
    }
}
