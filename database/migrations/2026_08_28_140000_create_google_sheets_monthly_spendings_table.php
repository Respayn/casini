<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_sheets_monthly_spendings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->date('year_month');
            $table->decimal('programming_hours', 12, 2)->default(0);
            $table->decimal('programming_sum', 12, 2)->default(0);
            $table->decimal('copyrighting_units', 12, 2)->default(0);
            $table->decimal('copyrighting_sum', 12, 2)->default(0);
            $table->decimal('seo_links_sum', 12, 2)->default(0);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'year_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_sheets_monthly_spendings');
    }
};
