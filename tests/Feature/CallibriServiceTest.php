<?php

namespace Tests\Feature;

use App\Data\Callibri\SiteData;
use App\Services\CallibriService;
use Tests\TestCase;

class CallibriServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(CallibriService::class);
        $this->service->setupClient(
            config('services.callibri.test_email'),
            config('services.callibri.test_token')
        );
    }

    public function test_get_sites_returns_collection_of_site_objects()
    {
        $result = $this->service->getSites();

        $this->assertInstanceOf(SiteData::class, $result->first());
    }
}
