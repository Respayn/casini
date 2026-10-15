<?php

namespace Tests\Feature\Livewire;

use App\Models\User;
use App\Support\SidebarProjectAccess;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportsListPageTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesTableSeeder::class);
        $this->seed(PermissionSeeder::class);

        session()->forget(SidebarProjectAccess::SESSION_KEY);
        Carbon::setTestNow(Carbon::parse('2026-10-15 12:00:00'));

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(Role::findByName('admin'));
        $this->actingAs($user);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_period_defaults_to_current_month(): void
    {
        $component = Livewire::test('pages::reports');

        $this->assertSame('2026-10-01', $component->get('periodFrom')->toDateString());
        $this->assertSame('2026-10-01', $component->get('periodTo')->toDateString());
    }

    public function test_future_month_is_clamped_to_current_month(): void
    {
        $component = Livewire::test('pages::reports')
            ->set('periodTo', Carbon::parse('2026-12-01'));

        $this->assertSame('2026-10-01', $component->get('periodTo')->toDateString());
    }

    public function test_period_end_follows_start_when_start_is_later(): void
    {
        $component = Livewire::test('pages::reports')
            ->set('periodFrom', Carbon::parse('2026-09-01'))
            ->set('periodTo', Carbon::parse('2026-07-01'));

        $this->assertSame('2026-09-01', $component->get('periodFrom')->toDateString());
        $this->assertSame('2026-09-01', $component->get('periodTo')->toDateString());
    }

    public function test_status_columns_are_removed(): void
    {
        $component = Livewire::test('pages::reports');

        $keys = array_column($component->get('columnSettings'), 'key');

        $this->assertNotContains('is_ready', $keys);
        $this->assertNotContains('is_accepted', $keys);
        $this->assertNotContains('is_sent', $keys);
        $component
            ->assertDontSee('Отчет готов')
            ->assertDontSee('Отчет принят менеджером')
            ->assertDontSee('Отчет отправлен клиенту');
    }

    public function test_empty_list_offers_report_creation(): void
    {
        Livewire::test('pages::reports')
            ->assertSee('Нет отчетов для отображения')
            ->assertDontSee('Добавить клиенто-проект')
            ->assertSeeHtml('href="'.route('reports.create').'"');
    }

    public function test_templates_button_is_secondary(): void
    {
        $html = Livewire::test('pages::reports')->html();

        $this->assertStringNotContainsString('bg-primary', $this->linkClasses($html, route('reports.templates')));
        $this->assertStringContainsString('bg-primary', $this->linkClasses($html, route('reports.create')));
    }

    private function linkClasses(string $html, string $href): string
    {
        $pattern = '/<a href="'.preg_quote($href, '/').'"[^>]*class="([^"]*)"/u';

        $this->assertSame(1, preg_match($pattern, $html, $matches), 'Нет ссылки '.$href);

        return $matches[1];
    }
}
