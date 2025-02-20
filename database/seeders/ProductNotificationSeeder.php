<?php

namespace Database\Seeders;

use App\Enums\ProductNotificationCategory;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductNotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::all()->pluck('id', 'code');

        DB::table('product_notifications')->insert([
            [
                'product_id' => $products->get('channels'),
                'category' => ProductNotificationCategory::OTHER,
                'content' => 'Низкий остаток бюджета ({чч:мм}, {дд.мм} / {остаток бюджета} ₽) в {инструмент} в канале {название канала} ({номер_канала})',
                'code' => 'budget_low'
            ],
            [
                'product_id' => $products->get('channels'),
                'category' => ProductNotificationCategory::OTHER,
                'content' => 'Изменился менеджер в канале {название канала ({номер_канала})',
                'code' => 'manager_changed'
            ],
            [
                'product_id' => $products->get('channels'),
                'category' => ProductNotificationCategory::OTHER,
                'content' => 'Изменился специалист в канале {название канала} ({номер_канала})',
                'code' => 'specialist_changed'
            ],
            [
                'product_id' => $products->get('channels'),
                'category' => ProductNotificationCategory::OTHER,
                'content' => 'Изменился помощник в канале {название канала} ({номер_канала})',
                'code' => 'assistant_changed'
            ],
            [
                'product_id' => $products->get('channels'),
                'category' => ProductNotificationCategory::OTHER,
                'content' => 'Изменился чек клиента в канале {название канала} ({номер_канала})',
                'code' => 'check_changed'
            ],
            [
                'product_id' => $products->get('channels'),
                'category' => ProductNotificationCategory::OTHER,
                'content' => 'Изменилась система расчетов бонусов в канале {название канала} ({номер_канала})',
                'code' => 'bonus_system_changed'
            ],
            [
                'product_id' => $products->get('channels'),
                'category' => ProductNotificationCategory::IMPORTANT,
                'content' => 'Перестали поступать данные из {система_аналитики} в канале {название канала} ({номер_канала})',
                'code' => 'analytic_data_stopped'
            ],
            [
                'product_id' => $products->get('channels'),
                'category' => ProductNotificationCategory::IMPORTANT,
                'content' => 'Перестали поступать данные из {инструмент} в канале {название канала} ({номер_канала})',
                'code' => 'instrument_data_stopped'
            ],
            [
                'product_id' => $products->get('channels'),
                'category' => ProductNotificationCategory::OTHER,
                'content' => 'Изменились настройки интеграции {инструмент} в канале {название канала ({номер_канала})',
                'code' => 'instrument_settings_changed'
            ],
            [
                'product_id' => $products->get('channels'),
                'category' => ProductNotificationCategory::OTHER,
                'content' => 'Изменились настройки интеграции {система_аналитики} в канале {название канала ({номер_канала})',
                'code' => 'analytic_settings_changed'
            ],
            [
                'product_id' => $products->get('statistics'),
                'category' => ProductNotificationCategory::OTHER,
                'content' => 'Прогнозируем НЕвыполнение плана в канале {название канала} ({номер_канала})',
                'code' => 'forecast_not_completed'
            ],
            [
                'product_id' => $products->get('statistics'),
                'category' => ProductNotificationCategory::OTHER,
                'content' => 'Были рассчитаны бонусы за {мм.гг} в канале {название канала} ({номер_канала})',
                'code' => 'bonus_calculated'
            ],
            [
                'product_id' => $products->get('clients'),
                'category' => ProductNotificationCategory::OTHER,
                'content' => 'Были внесены изменения в клиенте {название клиента}',
                'code' => 'client_changed'
            ],
            [
                'product_id' => $products->get('ad_movement'),
                'category' => ProductNotificationCategory::OTHER,
                'content' => 'Поступление рекламных средств {сумма} от {название_клиента}',
                'code' => 'ad_funds_income'
            ],
            [
                'product_id' => $products->get('ad_movement'),
                'category' => ProductNotificationCategory::IMPORTANT,
                'content' => 'Необработанное поступление рекламных средств на сумму {сумма}, {дд.мм.гг}, {№} от {название_клиента}',
                'code' => 'ad_funds_income_unprocessed'
            ],
            [
                'product_id' => $products->get('planning'),
                'category' => ProductNotificationCategory::IMPORTANT,
                'content' => 'Нет плана в {название_канала} ({номер_канала})',
                'code' => 'planning_not_found'
            ],
            [
                'product_id' => $products->get('planning'),
                'category' => ProductNotificationCategory::OTHER,
                'content' => 'Нужно согласовать план в {название_канала} ({номер_канала})',
                'code' => 'planning_not_approved'
            ],
            [
                'product_id' => $products->get('reports'),
                'category' => ProductNotificationCategory::OTHER,
                'content' => 'Специалист сменил статус отчета на “Готов” в {название_канала} ({номер_канала})',
                'code' => 'report_status_changed_ready'
            ],
            [
                'product_id' => $products->get('reports'),
                'category' => ProductNotificationCategory::OTHER,
                'content' => 'Менеджер сменил статус отчета на “Отчет принят менеджером” в {название_канала} ({номер_канала})',
                'code' => 'report_status_changed_approved'
            ],
            [
                'product_id' => $products->get('reports'),
                'category' => ProductNotificationCategory::OTHER,
                'content' => 'Менеджер сменил статус отчета на “Отчет отправлен клиенту” в {название_канала} ({номер_канала})',
                'code' => 'report_status_changed_sent'
            ],
            [
                'product_id' => $products->get('report_templates'),
                'category' => ProductNotificationCategory::OTHER,
                'content' => 'Вам доступен шаблон отчета {название_шаблона}',
                'code' => 'report_template_available'
            ],
            [
                'product_id' => $products->get('report_templates'),
                'category' => ProductNotificationCategory::OTHER,
                'content' => 'Шаблон отчета был обновлен {название_шаблона}',
                'code' => 'report_template_updated'
            ],
            [
                'product_id' => null,
                'category' => ProductNotificationCategory::IMPORTANT,
                'content' => 'Выполнение технических работ завершено {название_продукта}',
                'code' => 'technical_work_completed'
            ],
        ]);
    }
}
