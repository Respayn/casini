<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReportTestDataSeeder extends Seeder
{
    private const PROJECT_ID = 1;

    private const MONTHS_COUNT = 3;

    private array $months = [];

    public function run(): void
    {
        // Проверяем, есть ли уже данные для этого проекта
        $existingKeywords = DB::table('serp_keywords')
            ->where('project_id', self::PROJECT_ID)
            ->exists();

        if ($existingKeywords) {
            $this->command?->info('ReportTestDataSeeder: данные уже существуют, пропуск.');
            return;
        }

        $this->generateMonths();
        
        $this->seedSerpRegions();
        $this->seedSerpKeywords();
        $this->seedSerpTasks();
        $this->seedSerpPositions();
        $this->seedCallibriLeads();
        $this->seedYandexDirectCampaignStats();
        $this->seedYandexMetrikaSearchEnginesStats();
        $this->seedYandexMetrikaGoalUtms();
        $this->seedYandexMetrikaGoalConversions();
        $this->seedYandexMetrikaVisitsGeo();
        $this->seedYandexMetrikaVisitsSearchQueries();
        $this->seedProjectPlanValues();
        $this->seedCompletedWorks();
    }

    private function generateMonths(): void
    {
        $current = now()->startOfMonth()->subMonths(self::MONTHS_COUNT - 1);
        
        for ($i = 0; $i < self::MONTHS_COUNT; $i++) {
            $this->months[] = $current->copy();
            $current = $current->addMonth();
        }
    }

    private function seedSerpRegions(): void
    {
        $existingCodes = DB::table('serp_regions')->pluck('code')->toArray();

        // Получаем ID поисковых систем из базы
        $googleId = DB::table('search_engines')->where('code', 'google')->value('id');
        $yandexId = DB::table('search_engines')->where('code', 'yandex')->value('id');

        if (!$googleId || !$yandexId) {
            $this->command?->error('Search engines not found. Run SearchEnginesSeeder first.');
            return;
        }

        $regions = [
            ['search_engine_id' => $googleId, 'name' => 'Москва', 'code' => 'google_moscow', 'language' => 'ru', 'country_code' => 'RU', 'geo_id' => '1011969'],
            ['search_engine_id' => $googleId, 'name' => 'Санкт-Петербург', 'code' => 'google_spb', 'language' => 'ru', 'country_code' => 'RU', 'geo_id' => '1011747'],
            ['search_engine_id' => $yandexId, 'name' => 'Москва', 'code' => 'yandex_moscow', 'language' => 'ru', 'country_code' => 'RU', 'geo_id' => '213'],
            ['search_engine_id' => $yandexId, 'name' => 'Санкт-Петербург', 'code' => 'yandex_spb', 'language' => 'ru', 'country_code' => 'RU', 'geo_id' => '2'],
        ];

        foreach ($regions as $region) {
            if (!in_array($region['code'], $existingCodes)) {
                DB::table('serp_regions')->insert(array_merge($region, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    private function seedSerpKeywords(): void
    {
        $keywords = [
            'парогенераторы промышленные',
            'парогенератор купить',
            'промышленный парогенератор цена',
            'парогенератор для производства',
            'электрический парогенератор',
            'парогенератор газовый',
            'паровой котел промышленный',
            'котельное оборудование',
            'парогенератор для бани',
            'промпарогенератор',
            'парогенератор производитель',
            'парогенератор низкого давления',
            'парогенератор высокого давления',
            'индустриальный парогенератор',
            'парогенератор энергонезависимый',
        ];

        foreach ($keywords as $keyword) {
            DB::table('serp_keywords')->insert([
                'project_id' => self::PROJECT_ID,
                'phrase' => $keyword,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedSerpTasks(): void
    {
        $keywords = DB::table('serp_keywords')
            ->where('project_id', self::PROJECT_ID)
            ->pluck('id');

        $regions = DB::table('serp_regions')
            ->select('id as region_id', 'search_engine_id')
            ->get();

        foreach ($keywords as $keywordId) {
            foreach ($regions as $region) {
                DB::table('serp_tasks')->insert([
                    'project_id' => self::PROJECT_ID,
                    'serp_keyword_id' => $keywordId,
                    'search_engine_id' => $region->search_engine_id,
                    'serp_region_id' => $region->region_id,
                    'is_active' => true,
                    'check_frequency' => 'weekly',
                    'last_check_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function seedSerpPositions(): void
    {
        $tasks = DB::table('serp_tasks')
            ->where('project_id', self::PROJECT_ID)
            ->get(['id', 'serp_keyword_id']);

        $keywordPositions = [];
        foreach ($tasks as $task) {
            $keywordPositions[$task->id] = rand(15, 50);
        }

        foreach ($this->months as $monthIndex => $month) {
            $checkDates = [];
            $weeksInMonth = 4;
            for ($w = 0; $w < $weeksInMonth; $w++) {
                $checkDates[] = $month->copy()->addWeeks($w)->format('Y-m-d');
            }

            foreach ($tasks as $task) {
                $basePosition = $keywordPositions[$task->id];
                
                foreach ($checkDates as $checkDate) {
                    $improvement = ($monthIndex * 2) + rand(-3, 5);
                    $position = max(1, $basePosition - $improvement);
                    
                    DB::table('serp_positions')->insert([
                        'serp_task_id' => $task->id,
                        'check_date' => $checkDate,
                        'position' => rand(0, 10) < 8 ? $position : null,
                        'url' => rand(0, 10) < 8 ? 'https://pzem.ru/catalog/parogeneratory' : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    private function seedCallibriLeads(): void
    {
        $utmSources = ['yandex', 'google', 'direct', 'organic', null];
        $utmMediums = ['cpc', 'organic', 'referral', null];
        $utmCampaigns = ['parogeneratory', 'kotelnoe_oborudovanie', 'brand', '12345678', null];
        $utmContents = ['text_ad_1', 'banner_top', null];
        $utmTerms = ['парогенератор', 'промышленный парогенератор', null];

        foreach ($this->months as $month) {
            $leadsCount = rand(8, 12);
            
            for ($i = 0; $i < $leadsCount; $i++) {
                $day = rand(1, 28);
                $date = $month->copy()->day($day);
                
                DB::table('callibri_leads')->insert([
                    'project_id' => self::PROJECT_ID,
                    'external_id' => 'CLB-' . $date->format('Ymd') . '-' . rand(1000, 9999),
                    'date' => $date,
                    'utm_source' => $utmSources[array_rand($utmSources)],
                    'utm_medium' => $utmMediums[array_rand($utmMediums)],
                    'utm_campaign' => $utmCampaigns[array_rand($utmCampaigns)],
                    'utm_content' => $utmContents[array_rand($utmContents)],
                    'utm_term' => $utmTerms[array_rand($utmTerms)],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function seedYandexDirectCampaignStats(): void
    {
        $campaigns = [
            ['name' => 'Парогенераторы - Поиск', 'id' => 100001],
            ['name' => 'Парогенераторы - РСЯ', 'id' => 100002],
            ['name' => 'Котельное оборудование - Поиск', 'id' => 100003],
            ['name' => 'Брендовая кампания', 'id' => 100004],
        ];

        $goalNames = ['Заказ звонка', 'Отправка формы', 'Просмотр каталога', null];

        foreach ($this->months as $month) {
            $daysInMonth = $month->daysInMonth;
            
            foreach ($campaigns as $campaign) {
                $dailyImpressions = rand(50, 200);
                $dailyClicks = rand(5, 20);
                $dailyCost = rand(500, 3000);

                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $impressions = $dailyImpressions + rand(-20, 20);
                    $clicks = $dailyClicks + rand(-3, 3);
                    $costWithVat = $dailyCost + rand(-200, 200);
                    
                    DB::table('yandex_direct_campaign_stats')->insert([
                        'project_id' => self::PROJECT_ID,
                        'campaign_name' => $campaign['name'],
                        'campaign_id' => $campaign['id'],
                        'impressions' => max(0, $impressions),
                        'clicks' => max(0, $clicks),
                        'cost_with_vat' => max(0, $costWithVat),
                        'cost_without_vat' => max(0, $costWithVat * 0.83),
                        'conversions' => rand(0, 3),
                        'goal_name' => $goalNames[array_rand($goalNames)],
                        'date' => $month->copy()->day($day),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    private function seedYandexMetrikaSearchEnginesStats(): void
    {
        $engines = ['yandex', 'google', 'other'];

        foreach ($this->months as $month) {
            foreach ($engines as $engine) {
                $visits = $engine === 'yandex' 
                    ? rand(800, 1200) 
                    : ($engine === 'google' ? rand(400, 700) : rand(50, 150));
                
                $conversions = (int) ($visits * rand(2, 5) / 100);

                DB::table('yandex_metrika_search_engines_stats')->insert([
                    'project_id' => self::PROJECT_ID,
                    'search_engine' => $engine,
                    'month' => $month->format('Y-m-01'),
                    'visits' => $visits,
                    'conversions' => $conversions,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function seedYandexMetrikaGoalUtms(): void
    {
        $goalNames = ['Заказ звонка', 'Отправка формы', 'Скачивание каталога', 'Регистрация'];
        $utmSources = ['yandex', 'google', 'direct', null];
        $utmMediums = ['cpc', 'organic', 'referral', null];
        $utmCampaigns = ['parogeneratory', '100001', '100002', '100003', null];
        $utmContents = ['text_ad_1', 'banner_top', 'product_card', null];
        $utmTerms = ['парогенератор', 'промышленный парогенератор', null];

        foreach ($this->months as $month) {
            $conversionsCount = rand(15, 25);
            
            for ($i = 0; $i < $conversionsCount; $i++) {
                $day = rand(1, 28);
                $date = $month->copy()->day($day);

                DB::table('yandex_metrika_goal_utms')->insert([
                    'project_id' => self::PROJECT_ID,
                    'goal_name' => $goalNames[array_rand($goalNames)],
                    'achieved_date' => $date,
                    'utm_source' => $utmSources[array_rand($utmSources)],
                    'utm_medium' => $utmMediums[array_rand($utmMediums)],
                    'utm_campaign' => $utmCampaigns[array_rand($utmCampaigns)],
                    'utm_content' => $utmContents[array_rand($utmContents)],
                    'utm_term' => $utmTerms[array_rand($utmTerms)],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function seedYandexMetrikaGoalConversions(): void
    {
        $goals = [
            ['name' => 'Заказ звонка', 'baseConversions' => 45],
            ['name' => 'Отправка формы', 'baseConversions' => 30],
            ['name' => 'Скачивание каталога', 'baseConversions' => 60],
            ['name' => 'Просмотр контактов', 'baseConversions' => 25],
        ];

        foreach ($this->months as $month) {
            foreach ($goals as $goal) {
                $conversions = $goal['baseConversions'] + rand(-10, 15);

                DB::table('yandex_metrika_goal_conversions')->insert([
                    'project_id' => self::PROJECT_ID,
                    'goal_name' => $goal['name'],
                    'month' => $month->format('Y-m-01'),
                    'conversions' => max(0, $conversions),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function seedYandexMetrikaVisitsGeo(): void
    {
        $cities = [
            ['name' => 'Москва', 'visitsBase' => 350, 'visitorsBase' => 280, 'goalsBase' => 15],
            ['name' => 'Санкт-Петербург', 'visitsBase' => 200, 'visitorsBase' => 160, 'goalsBase' => 10],
            ['name' => 'Екатеринбург', 'visitsBase' => 150, 'visitorsBase' => 120, 'goalsBase' => 8],
            ['name' => 'Новосибирск', 'visitsBase' => 100, 'visitorsBase' => 80, 'goalsBase' => 5],
            ['name' => 'Казань', 'visitsBase' => 80, 'visitorsBase' => 65, 'goalsBase' => 4],
            ['name' => 'Нижний Новгород', 'visitsBase' => 70, 'visitorsBase' => 55, 'goalsBase' => 3],
            ['name' => 'Челябинск', 'visitsBase' => 60, 'visitorsBase' => 48, 'goalsBase' => 3],
            ['name' => 'Самара', 'visitsBase' => 50, 'visitorsBase' => 40, 'goalsBase' => 2],
        ];

        foreach ($this->months as $month) {
            foreach ($cities as $city) {
                DB::table('yandex_metrika_visits_geo')->insert([
                    'project_id' => self::PROJECT_ID,
                    'month' => $month->format('Y-m-01'),
                    'city' => $city['name'],
                    'visits' => $city['visitsBase'] + rand(-20, 30),
                    'visitors' => $city['visitorsBase'] + rand(-15, 20),
                    'goal_reaches' => $city['goalsBase'] + rand(-2, 5),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function seedYandexMetrikaVisitsSearchQueries(): void
    {
        $queries = [
            ['phrase' => 'парогенератор промышленный', 'visitsBase' => 120, 'visitorsBase' => 95],
            ['phrase' => 'парогенератор купить', 'visitsBase' => 100, 'visitorsBase' => 80],
            ['phrase' => 'промышленный парогенератор цена', 'visitsBase' => 85, 'visitorsBase' => 70],
            ['phrase' => 'паровой котел', 'visitsBase' => 70, 'visitorsBase' => 55],
            ['phrase' => 'котельное оборудование', 'visitsBase' => 60, 'visitorsBase' => 48],
            ['phrase' => 'парогенератор для производства', 'visitsBase' => 55, 'visitorsBase' => 45],
            ['phrase' => 'электрический парогенератор', 'visitsBase' => 50, 'visitorsBase' => 40],
            ['phrase' => 'парогенератор газовый', 'visitsBase' => 45, 'visitorsBase' => 36],
            ['phrase' => 'парогенератор производитель', 'visitsBase' => 40, 'visitorsBase' => 32],
            ['phrase' => 'промпарогенератор', 'visitsBase' => 35, 'visitorsBase' => 28],
            ['phrase' => 'парогенератор низкого давления', 'visitsBase' => 30, 'visitorsBase' => 24],
            ['phrase' => 'парогенератор высокого давления', 'visitsBase' => 28, 'visitorsBase' => 22],
        ];

        foreach ($this->months as $month) {
            foreach ($queries as $query) {
                DB::table('yandex_metrika_visits_search_queries')->insert([
                    'project_id' => self::PROJECT_ID,
                    'month' => $month->format('Y-m-01'),
                    'phrase' => $query['phrase'],
                    'visits' => $query['visitsBase'] + rand(-10, 15),
                    'visitors' => $query['visitorsBase'] + rand(-8, 12),
                    'goal_reaches' => rand(2, 8),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function seedProjectPlanValues(): void
    {
        foreach ($this->months as $month) {
            DB::table('project_plan_values')->insert([
                'project_id' => self::PROJECT_ID,
                'parameter_code' => 'visits',
                'value' => 2500 + rand(-200, 200),
                'year_month_date' => $month->format('Y-m-01'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedCompletedWorks(): void
    {
        $works = [
            'Аудит сайта и анализ конкурентов',
            'Составление семантического ядра',
            'Оптимизация мета-тегов страниц',
            'Написание и публикация статей в блог',
            'Работа с внешними ссылками',
            'Анализ позиций и корректировка стратегии',
            'Техническая оптимизация сайта',
            'Улучшение скорости загрузки страниц',
            'Настройка аналитики и отслеживания целей',
            'Ежемесячный отчёт для клиента',
        ];

        foreach ($this->months as $month) {
            $worksCount = rand(2, 4);
            $usedWorks = array_rand($works, $worksCount);
            
            if (!is_array($usedWorks)) {
                $usedWorks = [$usedWorks];
            }

            foreach ($usedWorks as $workIndex) {
                $day = rand(5, 25);
                
                DB::table('completed_works')->insert([
                    'project_id' => self::PROJECT_ID,
                    'title' => $works[$workIndex],
                    'completed_at' => $month->copy()->day($day),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
