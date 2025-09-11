<?php

namespace Tests\Feature;

use App\Mail\SubscribeToNews;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SubscribeToNewsMailableTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_mailable_content(): void
    {
        $mailable = new SubscribeToNews('email');

        $mailable->assertSeeInHtml('email');
    }
}
