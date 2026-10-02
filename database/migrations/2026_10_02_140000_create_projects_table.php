<?php

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('client_name');
            $table->string('project_name');
            $table->text('description')->nullable();
            $table->string('status')->default(ProjectStatus::Planning->value);
            $table->string('priority')->default(ProjectPriority::Medium->value);
            $table->date('start_date');
            $table->date('due_date');
            $table->timestamps();

            // Filtered list queries (GET /api/projects?status=&priority=).
            $table->index(['status', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
