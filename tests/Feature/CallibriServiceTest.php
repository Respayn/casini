<?php

namespace Tests\Feature;

use App\Data\Callibri\SiteData;
use App\Models\IntegrationProject;
use App\Models\Project;
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

    public function test_get_leads_with_filters()
    {
        $project = Project::factory()->create();

        IntegrationProject::factory()
            ->forCallibri()
            ->withSettings([
                'email' => config('services.callibri.test_email'),
                'site_id' => config('services.callibri.test_site_id'),
                'utm_source' => 'test',
                'appeals_type' => ['consultation'],
            ])
            ->create(['project_id' => $project->id]);

        $leads = $this->service->getLeads(
            $project,
            now()->subWeek(),
            now()
        );

//        $this->assertTrue($leads->isNotEmpty());
    }
}
