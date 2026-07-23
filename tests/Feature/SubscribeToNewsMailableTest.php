<?php

namespace Tests\Feature;

use App\Mail\SubscribeToNews;
use Tests\TestCase;

class SubscribeToNewsMailableTest extends TestCase
{
    public function test_mailable_content(): void
    {
        $mailable = new SubscribeToNews('email');

        $mailable->assertSeeInHtml('email');
    }
}
