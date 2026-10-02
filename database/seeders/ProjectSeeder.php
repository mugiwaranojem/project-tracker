<?php

namespace Database\Seeders;

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProjectSeeder extends Seeder
{
    /**
     * Seed the sample projects from data/projects.json.
     *
     * Rows are matched on their id, so re-running updates them instead of duplicating.
     */
    public function run(): void
    {
        $rows = json_decode(
            file_get_contents(database_path('seeders/data/projects.json')),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );

        $now = now();

        $projects = collect($rows)->map(fn (array $row) => [
            'id' => $row['id'],
            'client_name' => $row['clientName'],
            'project_name' => $row['projectName'],
            'description' => $row['description'],
            // "In Progress" -> "in_progress"; from() fails loudly on an unknown label.
            'status' => ProjectStatus::from(Str::snake($row['status']))->value,
            'priority' => ProjectPriority::from(Str::snake($row['priority']))->value,
            'start_date' => $row['startDate'],
            'due_date' => $row['dueDate'],
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        Project::upsert(
            $projects,
            uniqueBy: ['id'],
            update: ['client_name', 'project_name', 'description', 'status', 'priority', 'start_date', 'due_date', 'updated_at'],
        );
    }
}
