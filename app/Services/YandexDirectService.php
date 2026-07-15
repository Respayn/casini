<?php

namespace App\Services;

use App\Clients\YandexDirect\YandexDirectClient;
use App\Data\YandexDirect\CampaignDTO;
use App\Data\YandexDirect\CampaignStatisticsDTO;
use App\Data\YandexDirect\MonthlyExpenseDTO;
use App\Data\YandexDirect\PerformanceReportDTO;
use App\Exceptions\YandexDirectApiException;
use App\Factories\YandexDirectClientFactory;
use App\Parsers\YandexDirectReportParser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YandexDirectService
{
    private readonly YandexDirectClient $client;

    public function __construct(
        private readonly YandexDirectReportParser $parser,
        private readonly YandexDirectClientFactory $clientFactory
    ) {}

    public function setupClient($token, $clientLogin)
    {
        $this->client = $this->clientFactory->create($token, $clientLogin);
    }

    private const AGENCY_CLIENTS_PAGE_LIMIT = 10000;

    private const AGENCY_CLIENTS_ERROR = 'Не удалось получить список клиентов агентства. Авторизуйтесь под представителем агентства с доступом к клиентам в Яндекс.Директе.';

    private const API_UNREACHABLE_ERROR = 'Не удалось связаться с API Яндекс.Директ. Проверьте настройки YANDEX_DIRECT_*_API_URL и режим sandbox.';

    /**
     * Список логинов клиентов Директа для select в модалке интеграции.
     *
     * @return Collection<int, array{value: string, label: string}>
     */
    public function listClientLogins(string $token): Collection
    {
        return $this->resolveClientLogins($token)['logins'];
    }

    /**
     * Логины + опциональная ошибка для UI модалки.
     *
     * 1. AgencyClients.get (активные клиенты, с пагинацией) — при успехе отдаём дочерние логины.
     * 2. При ошибке/пустом ответе: Clients.get — если Type=AGENCY, не подставляем OAuth-логин.
     * 3. Для прямого рекламодателя — Login из Clients.get, иначе login.yandex.ru/info.
     * 4. Если оба API недоступны (напр. неверный URL) — ошибка без OAuth-fallback.
     *
     * @return array{logins: Collection<int, array{value: string, label: string}>, error: string|null}
     */
    public function resolveClientLogins(string $token): array
    {
        $agencyLogins = $this->fetchAgencyClientLogins($token);

        if ($agencyLogins !== null && $agencyLogins->isNotEmpty()) {
            return ['logins' => $agencyLogins, 'error' => null];
        }

        $account = $this->fetchAccountType($token);

        if (($account['type'] ?? null) === 'AGENCY') {
            return [
                'logins' => collect(),
                'error' => $agencyLogins !== null && $agencyLogins->isEmpty()
                    ? 'У агентства нет активных клиентов в Яндекс.Директе'
                    : self::AGENCY_CLIENTS_ERROR,
            ];
        }

        $login = $account['login'] ?? null;

        if (filled($login)) {
            $login = (string) $login;

            return [
                'logins' => collect([
                    [
                        'value' => $login,
                        'label' => $login,
                    ],
                ]),
                'error' => null,
            ];
        }

        // AgencyClients и Clients.get недоступны — не маскируем misconfiguration OAuth-логином.
        if ($agencyLogins === null && ($account['type'] ?? null) === null) {
            return [
                'logins' => collect(),
                'error' => self::API_UNREACHABLE_ERROR,
            ];
        }

        $oauthLogins = $this->fetchOauthUserLogin($token);

        return [
            'logins' => $oauthLogins,
            'error' => $oauthLogins->isEmpty() ? 'Не найдено доступных логинов Яндекс.Директ' : null,
        ];
    }

    /**
     * @return Collection<int, array{value: string, label: string}>|null null — ошибка API / не агентство
     */
    private function fetchAgencyClientLogins(string $token): ?Collection
    {
        $baseUrl = $this->directApiBaseUrl();
        $offset = 0;
        $allClients = [];

        do {
            try {
                $response = Http::withHeaders($this->directApiHeaders($token))
                    ->post($baseUrl.'/agencyclients', [
                        'method' => 'get',
                        'params' => [
                            'SelectionCriteria' => [
                                'Archived' => 'NO',
                            ],
                            'FieldNames' => ['Login', 'ClientInfo'],
                            'Page' => [
                                'Limit' => self::AGENCY_CLIENTS_PAGE_LIMIT,
                                'Offset' => $offset,
                            ],
                        ],
                    ]);
            } catch (\Throwable $e) {
                $this->logError(__METHOD__, $e);

                return null;
            }

            $payload = $response->json() ?? [];

            if (isset($payload['error'])) {
                Log::warning('[fetchAgencyClientLogins] AgencyClients API error', [
                    'error_code' => $payload['error']['error_code'] ?? null,
                    'error_string' => $payload['error']['error_string'] ?? null,
                    'error_detail' => $payload['error']['error_detail'] ?? null,
                ]);

                return null;
            }

            if (! $response->successful()) {
                Log::warning('[fetchAgencyClientLogins] HTTP error', [
                    'status' => $response->status(),
                ]);

                return null;
            }

            $pageClients = $payload['result']['Clients'] ?? [];
            $allClients = array_merge($allClients, $pageClients);
            $limitedBy = $payload['result']['LimitedBy'] ?? null;

            if ($limitedBy === null) {
                break;
            }

            $offset = (int) $limitedBy;
        } while (true);

        return collect($allClients)
            ->filter(fn ($client) => filled($client['Login'] ?? null))
            ->unique(fn (array $client) => (string) $client['Login'])
            ->map(function (array $client): array {
                $login = (string) $client['Login'];
                $info = trim((string) ($client['ClientInfo'] ?? ''));

                return [
                    'value' => $login,
                    'label' => $info !== '' ? "{$info} ({$login})" : $login,
                    'sort_key' => mb_strtolower($info !== '' ? $info : $login),
                ];
            })
            ->sortBy('sort_key')
            ->map(fn (array $item): array => [
                'value' => $item['value'],
                'label' => $item['label'],
            ])
            ->values();
    }

    /**
     * @return array{type: string|null, login: string|null}
     */
    private function fetchAccountType(string $token): array
    {
        $baseUrl = $this->directApiBaseUrl();

        try {
            $response = Http::withHeaders($this->directApiHeaders($token))
                ->post($baseUrl.'/clients', [
                    'method' => 'get',
                    'params' => [
                        'FieldNames' => ['Type', 'Login'],
                    ],
                ]);
        } catch (\Throwable $e) {
            $this->logError(__METHOD__, $e);

            return ['type' => null, 'login' => null];
        }

        $payload = $response->json() ?? [];

        if (isset($payload['error']) || ! $response->successful()) {
            if (isset($payload['error'])) {
                Log::warning('[fetchAccountType] Clients API error', [
                    'error_code' => $payload['error']['error_code'] ?? null,
                    'error_string' => $payload['error']['error_string'] ?? null,
                ]);
            }

            return ['type' => null, 'login' => null];
        }

        $client = $payload['result']['Clients'][0] ?? [];

        return [
            'type' => isset($client['Type']) ? (string) $client['Type'] : null,
            'login' => filled($client['Login'] ?? null) ? (string) $client['Login'] : null,
        ];
    }

    /**
     * @return Collection<int, array{value: string, label: string}>
     */
    private function fetchOauthUserLogin(string $token): Collection
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'OAuth '.$token,
            ])->get('https://login.yandex.ru/info', [
                'format' => 'json',
                'with_openid_identity' => 1,
            ]);
        } catch (\Throwable $e) {
            $this->logError(__METHOD__, $e);

            return collect();
        }

        if ($response->failed()) {
            return collect();
        }

        $login = $response->json('login');

        if (! filled($login)) {
            return collect();
        }

        $login = (string) $login;

        return collect([
            [
                'value' => $login,
                'label' => $login,
            ],
        ]);
    }

    private function directApiBaseUrl(): string
    {
        $configured = (string) (
            config('services.yandex_direct.use_sandbox')
                ? config('services.yandex_direct.sandbox_api_url')
                : config('services.yandex_direct.api_url')
        );

        return rtrim($this->normalizeDirectApiBaseUrl($configured), '/');
    }

    /**
     * Официальный JSON base: …/json/v5/. Исправляет legacy …/v5/json/ и …/v5/.
     */
    private function normalizeDirectApiBaseUrl(string $url): string
    {
        $url = rtrim(trim($url), '/');

        if ($url === '') {
            return 'https://api.direct.yandex.com/json/v5';
        }

        // …/v5/json → …/json/v5
        if (preg_match('#^(https?://[^/]+)/v5/json$#i', $url, $matches) === 1) {
            return $matches[1].'/json/v5';
        }

        // …/v5 → …/json/v5 (без сегмента json)
        if (preg_match('#^(https?://[^/]+)/v5$#i', $url, $matches) === 1) {
            return $matches[1].'/json/v5';
        }

        return $url;
    }

    /**
     * @return array<string, string>
     */
    private function directApiHeaders(string $token): array
    {
        return [
            'Authorization' => 'Bearer '.$token,
            'Accept-Language' => 'ru',
            'Content-Type' => 'application/json; charset=utf-8',
        ];
    }

    private function getClient(): YandexDirectClient
    {
        if ($this->client === null) {
            throw new \Exception('YandexDirectClient is not initialized. Call setupClient() first.');
        }
        return $this->client;
    }

    /**
     * Получить список рекламных кампаний
     *
     * @return Collection<CampaignDTO>
     * @throws YandexDirectApiException
     */
    public function getCampaigns(): Collection
    {
        try {
            $response = $this->getClient()->request('POST', 'campaigns', [
                'method' => 'get',
                'params' => [
                    'SelectionCriteria' => (object)[],
                    'FieldNames' => ['Id', 'Name', 'Status']
                ]
            ]);

            return collect($response['data']['result']['Campaigns'] ?? [])
                ->map(fn($campaign) => new CampaignDTO(
                    $campaign['Id'],
                    $campaign['Name'],
                    $campaign['Status']
                ));

        } catch (\Exception $e) {
            $this->logError(__METHOD__, $e);
            throw new YandexDirectApiException('Failed to get campaigns', 0, $e);
        }
    }

    /**
     * Получить текущий баланс аккаунта
     *
     * @throws YandexDirectApiException
     */
    public function getAccountBalance(): float
    {
        try {
            return $this->getClient()->getAccountBalance();
        } catch (\Exception $e) {
            $this->logError(__METHOD__, $e);
            throw new YandexDirectApiException('Balance check failed', 0, $e);
        }
    }

    /**
     * Сформировать отчет о производительности
     *
     * @return Collection<PerformanceReportDTO>
     * @throws YandexDirectApiException
     */
    public function getPerformanceReport(
        Carbon $startDate,
        Carbon $endDate,
        array $metrics = ['Impressions', 'Clicks', 'Cost']
    ): Collection {
        $this->validateDateRange($startDate, $endDate);

        try {
            $reportData = $this->getClient()->requestReport([
                'params' => $this->buildReportParams(
                    'ACCOUNT_PERFORMANCE_REPORT',
                    $startDate,
                    $endDate,
                    $metrics
                )
            ]);
            return $this->parser->parsePerformanceReport($reportData);

        } catch (\Exception $e) {
            $this->logError(__METHOD__, $e, [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString()
            ]);
            throw new YandexDirectApiException('Report generation failed', 0, $e);
        }
    }

//    /**
//     * Получить статистику по кампании
//     *
//     * @return Collection<CampaignStatisticsDTO>
//     * @throws YandexDirectApiException
//     */
//    public function getCampaignStatistics(
//        int $campaignId,
//        Carbon $startDate,
//        Carbon $endDate
//    ): Collection {
//        $this->validateDateRange($startDate, $endDate);
//
//        try {
//            $response = $this->getClient()->request('POST', 'reports', [
//                'params' => $this->buildReportParams(
//                    'CAMPAIGN_PERFORMANCE_REPORT',
//                    $startDate,
//                    $endDate,
//                    ['Date', 'Clicks', 'Cost'],
//                    [
//                        'Page' => [
//                            'Limit' => 1000
//                        ]
//                    ]
//                )
//            ]);
//
//            return $response['data'];
//
//        } catch (\Exception $e) {
//            $this->logError(__METHOD__, $e, [
//                'campaignId' => $campaignId,
//                'period' => $this->formatDateRange($startDate, $endDate)
//            ]);
//            throw new YandexDirectApiException('Campaign statistics failed', 0, $e);
//        }
//    }

    /**
     * Сформировать параметры отчета
     */
    private function buildReportParams(
        string $reportType,
        Carbon $startDate,
        Carbon $endDate,
        array $fields,
        array $additionalCriteria = []
    ): array {
        $baseParams = [
            'SelectionCriteria' => (object)[
                'DateFrom' => $startDate->format('Y-m-d'),
                'DateTo' => $endDate->format('Y-m-d')
            ],
            'FieldNames' => $fields,
            'ReportName' => 'Report_'.time(),
            'ReportType' => $reportType,
            'DateRangeType' => 'CUSTOM_DATE',
            'Format' => 'TSV',
            'IncludeVAT' => 'YES',
            'IncludeDiscount' => 'NO'
        ];

        return array_merge($baseParams, $additionalCriteria);
    }

    /**
     * Валидация временного диапазона
     */
    private function validateDateRange(Carbon $start, Carbon $end): void
    {
        if ($start->isAfter($end)) {
            throw new \InvalidArgumentException('Start date must be before end date');
        }

        if ($end->diffInDays($start) > 365) {
            throw new \InvalidArgumentException('Maximum date range exceeded (365 days)');
        }
    }

    /**
     * Получить общие расходы по проекту за период
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     * @throws YandexDirectApiException
     */
    public function getProjectExpenses(Carbon $startDate, Carbon $endDate): float
    {
        $report = $this->getPerformanceReport(
            $startDate,
            $endDate,
            ['Cost', 'Impressions', 'Clicks']
        );

        return $report->sum('cost');
    }

    /**
     * Получить расходы по проекту с группировкой по месяцам
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return Collection<MonthlyExpenseDTO>
     * @throws YandexDirectApiException
     */
    public function getProjectExpensesByMonth(Carbon $startDate, Carbon $endDate): Collection
    {
        $this->validateDateRange($startDate, $endDate);

        try {
            $reportData = $this->getClient()->requestReport([
                'params' => $this->buildReportParams(
                    'ACCOUNT_PERFORMANCE_REPORT',
                    $startDate,
                    $endDate,
                    ['Date', 'Cost']
                )
            ]);

            return $this->groupByMonth($reportData);
        } catch (\Exception $e) {
            $this->logError(__METHOD__, $e, [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString()
            ]);
            throw new YandexDirectApiException('Failed to get monthly expenses', 0, $e);
        }
    }

    /**
     * Группировка данных по месяцам
     */
    private function groupByMonth(array $reportData): Collection
    {
        return collect($reportData)
            ->groupBy(fn($item) => Carbon::parse($item['Date'])->format('Y-m'))
            ->map(function ($items, $month) {
                return new MonthlyExpenseDTO(
                    Carbon::createFromFormat('Y-m', $month),
                    $items->sum(fn($i) => (float)$i['Cost'])
                );
            })
            ->values();
    }

    /**
     * Унифицированное логирование ошибок
     */
    private function logError(string $method, \Throwable $e, array $context = []): void
    {
        Log::channel('yandex_direct')->error("[$method] {$e->getMessage()}", [
            'exception' => get_class($e),
            'trace' => $e->getTraceAsString(),
            'context' => $context
        ]);
    }
}
