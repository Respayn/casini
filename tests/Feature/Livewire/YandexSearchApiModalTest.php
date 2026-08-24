<?php

namespace Tests\Feature\Livewire;

use App\Models\Integration;
use App\Models\User;
use Database\Seeders\IntegrationSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesUserWithClientProjectEdit;
use Tests\TestCase;

class YandexSearchApiModalTest extends TestCase
{
    use CreatesUserWithClientProjectEdit;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IntegrationSeeder::class);
    }

    private function createUserWithAgency(): User
    {
        return $this->createUserWithClientProjectEdit();
    }

    #[Test]
    public function test_select_integration_opens_yandex_search_api_modal_state(): void
    {
        $user = $this->createUserWithAgency();

        Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->call('selectIntegration', 'yandex_search_api')
            ->assertSet('selectedIntegration.integration.code', 'yandex_search_api')
            ->assertDispatched('modal-show');
    }

    #[Test]
    public function test_parse_phrases_from_docx_returns_phrases(): void
    {
        $user = $this->createUserWithAgency();
        $file = $this->makeUploadedDocx(['фраза 1', 'фраза 2']);

        Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->set('phraseDocxFile', $file)
            ->call('parsePhrasesFromDocx')
            ->assertReturned(['phrases' => ['фраза 1', 'фраза 2']]);
    }

    #[Test]
    public function test_set_integration_settings_stores_regions(): void
    {
        $user = $this->createUserWithAgency();
        $integration = Integration::query()->where('code', 'yandex_search_api')->firstOrFail();

        Livewire::actingAs($user)
            ->test('pages::system-settings.client-project-form')
            ->call('setIntegrationSettings', $integration->id, [
                'is_enabled' => true,
                'sync_enabled_at' => '2026-06-17',
                'regions' => [
                    ['code' => 213, 'phrases' => ['болт']],
                ],
            ])
            ->assertSet("integrationSettings.{$integration->id}.isEnabled", true)
            ->assertSet("integrationSettings.{$integration->id}.settings.regions.0.code", 213);
    }

    /**
     * @param  string[]  $lines
     */
    private function makeUploadedDocx(array $lines): UploadedFile
    {
        $phpWord = new PhpWord;
        $section = $phpWord->addSection();
        $section->addText(implode("\n", $lines));

        $path = sys_get_temp_dir().'/yandex-search-api-upload-'.uniqid().'.docx';
        IOFactory::createWriter($phpWord, 'Word2007')->save($path);

        return new UploadedFile($path, 'phrases.docx', null, null, true);
    }
}
