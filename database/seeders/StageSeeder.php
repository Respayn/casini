<?php

namespace Database\Seeders;

use App\Enums\Kpi;
use App\Enums\ProjectType;
use App\Enums\Role;
use App\Enums\ServiceType;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Str;

class StageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            RolesTableSeeder::class,
            PermissionSeeder::class,
            TooltipSeeder::class,
            ProductSeeder::class,
            ProductNotificationSeeder::class,
            IntegrationSeeder::class,
            RatesTableSeeder::class,
        ]);

        $this->seedAgency();
        $this->seedAdmin();
        $this->seedManagers();
        $this->seedSpecialists();
        $this->seedClients();
        $this->seedProjects();
    }

    private function seedAgency(): void
    {
        DB::table('agency_settings')->insert([
            'id' => 1,
            'name' => 'СайтАктив',
            'time_zone' => 'Europe/Moscow',
            'url' => 'https://siteactiv.ru',
            'email' => 'info@siteactiv.ru',
            'phone' => '+73433172230',
            'address' => 'г. Екатеринбург, ул. Примерная, 1',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    private function seedAdmin(): void
    {
        $this->insertUser(1, 'Админ', 'Админов', 'admin');
        $user = User::find(1);
        $user->assignRole(Role::ADMIN->value);
    }

    private function seedManagers(): void
    {
        $this->insertUser(2, 'Евгений', 'Эсселевич', 'esselevich');
        $this->insertUser(3, 'Наталия', 'Борисова', 'borisova');
        $this->insertUser(4, 'Екатерина', 'Манагина', 'managina');
        $this->insertUser(5, 'Роман', 'Тернов', 'ternov');
    }

    private function seedSpecialists(): void
    {
        $this->insertUser(6, 'Александр', 'Сырцев', 'syrtsev');
        $this->insertUser(7, 'Мария', 'Бан', 'ban');
        $this->insertUser(8, 'Марина', 'Хмелева', 'hmeleva');
        $this->insertUser(9, 'Савелий', 'Креативов', 'kreativov');
    }

    private function seedClients(): void
    {
        $this->insertClient(1, 'ООО “ТД ПЗЭМ”', '111111111111', 2);
        $this->insertClient(2, 'ООО “ПРАЙМ-1С-ЕКАТЕРИНБУРГ”', '111111111112', 2);
        $this->insertClient(3, 'ИП Пахомчик В.Н.', '111111111113', 3);
        $this->insertClient(4, 'ООО “ММК-МЕТИЗ”', '111111111114', 4);
        $this->insertClient(5, 'ИП Нетесов', '111111111115', 4);
        $this->insertClient(6, 'ООО “МСМ2”', '111111111116', 2);
        $this->insertClient(7, 'ООО “Альфа Потолок”', '111111111117', 5);
    }

    private function seedProjects(): void 
    {
        $this->insertProject(1, 'Пзем', 'https://pzem.ru', 1, 6, ProjectType::SEO_PROMOTION->value, ServiceType::TRAFFIC->value, Kpi::TRAFFIC->value, true);
        $this->insertProject(2, '1C прайм', 'https://1c-prime.ru', 2, 6, ProjectType::SEO_PROMOTION->value, ServiceType::TRAFFIC->value, Kpi::TRAFFIC->value, true);
        $this->insertProject(3, 'Честный путь', 'https://chestnuyput.ru', 3, 7, ProjectType::SEO_PROMOTION->value, ServiceType::TRAFFIC->value, Kpi::TRAFFIC->value, true);
        $this->insertProject(4, 'ММК Метиз', 'https://mmk-metiz.ru', 4, 7, ProjectType::SEO_PROMOTION->value, ServiceType::TOP->value, Kpi::POSITIONS->value, false);
        $this->insertProject(5, 'Экзампл', 'https://example.com', 5, 8, ProjectType::CONTEXT_AD->value, ServiceType::CA->value, Kpi::LEADS->value, true);
        $this->insertProject(6, 'Честный путь Екб', 'https://chestnuyput.ru', 3, 8, ProjectType::CONTEXT_AD->value, ServiceType::CA->value, Kpi::LEADS->value, true);
        $this->insertProject(7, 'zlp-630.com', 'https://zlp-630.com', 6, 8, ProjectType::CONTEXT_AD->value, ServiceType::CA->value, Kpi::LEADS->value, false);
        $this->insertProject(8, 'Альфа Потолок', 'https://alfa-potolok.com', 7, 9, ProjectType::CONTEXT_AD->value, ServiceType::CA->value, Kpi::LEADS->value, true);
    }

    private function insertUser($userId, $firstName, $lastName, $login): void
    {
        DB::table('users')->insert([
            [
                'id' => $userId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'is_active' => true,
                'login' => $login,
                'email' => "{$login}@mail.com",
                'phone' => '+7 (900) 111-22-' . Str::padLeft($userId, 2, '0'),
                'enable_important_notifications' => true,
                'enable_notifications' => true,
                'password' => Hash::make('123123'),
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
        DB::table('agency_admins')->insert([
            [
                'agency_id' => 1,
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    private function insertClient($id, $name, $inn, $managerId): void
    {
        DB::table('clients')->insert([
            [
                'id' => $id,
                'name' => $name,
                'inn' => $inn,
                'initial_balance' => 0,
                'manager_id' => $managerId,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    private function insertProject($id, $name, $domain, $clientId, $specialistId, $projectType, $serviceType, $kpi, $isActive): void
    {
        DB::table('projects')->insert([
            'id' => $id,
            'name' => $name,
            'domain' => $domain,
            'client_id' => $clientId,
            'specialist_id' => $specialistId,
            'project_type' => $projectType,
            'service_type' => $serviceType,
            'kpi' => $kpi,
            'is_internal' => false,
            'is_active' => $isActive,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('project_bonus_conditions')->insert([
            'id' => $id,
            'project_id' => $id,
            'bonuses_enabled' => false,
            'calculate_in_percentage' => 0,
            'start_month' => 1,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('project_bonus_intervals')->insert([
            'id' => $id,
            'project_bonus_condition_id' => $id,
            'from_percentage' => 0,
            'to_percentage' => 0,
            'bonus_amount' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
