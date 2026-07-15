<?php

namespace Tests\Unit\Services;

use App\Services\YandexDirectService;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class YandexDirectListLoginsTest extends TestCase
{
    private function configureDirectApi(string $apiUrl = 'https://api.direct.yandex.com/json/v5/'): void
    {
        config([
            'services.yandex_direct.use_sandbox' => false,
            'services.yandex_direct.api_url' => $apiUrl,
        ]);
    }

    #[Test]
    public function test_list_client_logins_parses_agency_clients(): void
    {
        $this->configureDirectApi();

        Http::fake([
            'api.direct.yandex.com/json/v5/agencyclients' => Http::response([
                'result' => [
                    'Clients' => [
                        ['Login' => 'client-a', 'ClientInfo' => 'Клиент А'],
                        ['Login' => 'client-b', 'ClientInfo' => ''],
                    ],
                ],
            ]),
        ]);

        $logins = app(YandexDirectService::class)->listClientLogins('test-token');

        $this->assertCount(2, $logins);
        $byValue = $logins->keyBy('value');
        $this->assertSame([
            'value' => 'client-a',
            'label' => 'Клиент А (client-a)',
        ], $byValue->get('client-a'));
        $this->assertSame([
            'value' => 'client-b',
            'label' => 'client-b',
        ], $byValue->get('client-b'));
    }

    #[Test]
    public function test_list_client_logins_paginates_agency_clients(): void
    {
        $this->configureDirectApi();

        Http::fake([
            'api.direct.yandex.com/json/v5/agencyclients' => Http::sequence()
                ->push([
                    'result' => [
                        'Clients' => [
                            ['Login' => 'client-a', 'ClientInfo' => 'A'],
                        ],
                        'LimitedBy' => 1,
                    ],
                ])
                ->push([
                    'result' => [
                        'Clients' => [
                            ['Login' => 'client-b', 'ClientInfo' => 'B'],
                        ],
                    ],
                ]),
        ]);

        $logins = app(YandexDirectService::class)->listClientLogins('test-token');

        $this->assertCount(2, $logins);
        $this->assertSame(['client-a', 'client-b'], $logins->pluck('value')->all());

        Http::assertSentCount(2);
    }

    #[Test]
    public function test_agency_clients_request_uses_archived_no_filter(): void
    {
        $this->configureDirectApi();

        Http::fake([
            'api.direct.yandex.com/json/v5/agencyclients' => Http::response([
                'result' => [
                    'Clients' => [
                        ['Login' => 'client-a', 'ClientInfo' => 'A'],
                    ],
                ],
            ]),
        ]);

        app(YandexDirectService::class)->listClientLogins('test-token');

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->url() === 'https://api.direct.yandex.com/json/v5/agencyclients'
                && ($body['params']['SelectionCriteria']['Archived'] ?? null) === 'NO'
                && ($body['params']['Page']['Limit'] ?? null) === 10000
                && ($body['params']['Page']['Offset'] ?? null) === 0;
        });
    }

    #[Test]
    public function test_normalizes_legacy_v5_json_api_url(): void
    {
        $this->configureDirectApi('https://api.direct.yandex.com/v5/json/');

        Http::fake([
            'api.direct.yandex.com/json/v5/agencyclients' => Http::response([
                'result' => [
                    'Clients' => [
                        ['Login' => 'client-a', 'ClientInfo' => 'A'],
                    ],
                ],
            ]),
        ]);

        $logins = app(YandexDirectService::class)->listClientLogins('test-token');

        $this->assertCount(1, $logins);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.direct.yandex.com/json/v5/agencyclients');
    }

    #[Test]
    public function test_agency_account_does_not_fallback_to_oauth_login(): void
    {
        $this->configureDirectApi();

        Http::fake([
            'api.direct.yandex.com/json/v5/agencyclients' => Http::response([
                'error' => [
                    'error_code' => 152,
                    'error_string' => 'Insufficient privileges',
                ],
            ], 200),
            'api.direct.yandex.com/json/v5/clients' => Http::response([
                'result' => [
                    'Clients' => [
                        ['Type' => 'AGENCY', 'Login' => 'agency-rep'],
                    ],
                ],
            ]),
            'login.yandex.ru/info*' => Http::response([
                'login' => 'should-not-appear',
            ]),
        ]);

        $resolved = app(YandexDirectService::class)->resolveClientLogins('test-token');

        $this->assertTrue($resolved['logins']->isEmpty());
        $this->assertStringContainsString('клиентов агентства', (string) $resolved['error']);

        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'login.yandex.ru/info'));
    }

    #[Test]
    public function test_direct_advertiser_uses_clients_get_login(): void
    {
        $this->configureDirectApi();

        Http::fake([
            'api.direct.yandex.com/json/v5/agencyclients' => Http::response([
                'error' => [
                    'error_code' => 152,
                    'error_string' => 'Insufficient privileges',
                ],
            ], 200),
            'api.direct.yandex.com/json/v5/clients' => Http::response([
                'result' => [
                    'Clients' => [
                        ['Type' => 'CLIENT', 'Login' => 'direct-advertiser'],
                    ],
                ],
            ]),
        ]);

        $logins = app(YandexDirectService::class)->listClientLogins('test-token');

        $this->assertCount(1, $logins);
        $this->assertSame([
            'value' => 'direct-advertiser',
            'label' => 'direct-advertiser',
        ], $logins->first());
    }

    #[Test]
    public function test_list_client_logins_falls_back_to_oauth_user_login(): void
    {
        $this->configureDirectApi();

        Http::fake([
            'api.direct.yandex.com/json/v5/agencyclients' => Http::response([
                'error' => [
                    'error_code' => 152,
                    'error_string' => 'Insufficient privileges',
                ],
            ], 200),
            'api.direct.yandex.com/json/v5/clients' => Http::response([
                'result' => [
                    'Clients' => [
                        ['Type' => 'CLIENT', 'Login' => ''],
                    ],
                ],
            ]),
            'login.yandex.ru/info*' => Http::response([
                'login' => 'direct-user',
            ]),
        ]);

        $logins = app(YandexDirectService::class)->listClientLogins('test-token');

        $this->assertCount(1, $logins);
        $this->assertSame([
            'value' => 'direct-user',
            'label' => 'direct-user',
        ], $logins->first());
    }

    #[Test]
    public function test_unreachable_api_does_not_fallback_to_oauth_login(): void
    {
        $this->configureDirectApi();

        Http::fake([
            'api.direct.yandex.com/json/v5/agencyclients' => Http::response('Not Found', 404),
            'api.direct.yandex.com/json/v5/clients' => Http::response('Not Found', 404),
            'login.yandex.ru/info*' => Http::response([
                'login' => 'should-not-appear',
            ]),
        ]);

        $resolved = app(YandexDirectService::class)->resolveClientLogins('test-token');

        $this->assertTrue($resolved['logins']->isEmpty());
        $this->assertStringContainsString('YANDEX_DIRECT', (string) $resolved['error']);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'login.yandex.ru/info'));
    }

    #[Test]
    public function test_list_client_logins_returns_empty_when_no_logins_found(): void
    {
        $this->configureDirectApi();

        Http::fake([
            'api.direct.yandex.com/json/v5/agencyclients' => Http::response([
                'error' => ['error_code' => 152],
            ], 200),
            'api.direct.yandex.com/json/v5/clients' => Http::response([
                'error' => ['error_code' => 152],
            ], 200),
            'login.yandex.ru/info*' => Http::response([], 401),
        ]);

        $logins = app(YandexDirectService::class)->listClientLogins('test-token');

        $this->assertTrue($logins->isEmpty());
    }

    #[Test]
    public function test_agency_with_empty_clients_returns_specific_error(): void
    {
        $this->configureDirectApi();

        Http::fake([
            'api.direct.yandex.com/json/v5/agencyclients' => Http::response([
                'result' => [
                    'Clients' => [],
                ],
            ]),
            'api.direct.yandex.com/json/v5/clients' => Http::response([
                'result' => [
                    'Clients' => [
                        ['Type' => 'AGENCY', 'Login' => 'agency-rep'],
                    ],
                ],
            ]),
        ]);

        $resolved = app(YandexDirectService::class)->resolveClientLogins('test-token');

        $this->assertTrue($resolved['logins']->isEmpty());
        $this->assertSame('У агентства нет активных клиентов в Яндекс.Директе', $resolved['error']);
    }
}
