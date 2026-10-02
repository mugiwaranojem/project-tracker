<?php

namespace Tests\Feature;

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'client_name' => 'Acme Corp',
            'project_name' => 'Website Redesign',
            'description' => 'New marketing site.',
            'status' => 'planning',
            'priority' => 'high',
            'start_date' => '2026-11-01',
            'due_date' => '2026-12-15',
        ], $overrides);
    }

    public function test_guests_cannot_access_projects(): void
    {
        $this->getJson('/api/projects')->assertUnauthorized();
        $this->postJson('/api/projects', $this->payload())->assertUnauthorized();
    }

    public function test_it_lists_projects_with_pagination(): void
    {
        Project::factory()->count(3)->create();

        $this->actingAs(User::factory()->create())
            ->getJson('/api/projects')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'client_name', 'project_name', 'description', 'status', 'priority', 'start_date', 'due_date']],
                'links',
                'meta',
            ]);
    }

    public function test_it_filters_projects_by_status_and_priority(): void
    {
        Project::factory()->create(['status' => ProjectStatus::OnHold, 'priority' => ProjectPriority::High]);
        Project::factory()->create(['status' => ProjectStatus::OnHold, 'priority' => ProjectPriority::Low]);
        Project::factory()->create(['status' => ProjectStatus::Completed, 'priority' => ProjectPriority::High]);

        $this->actingAs(User::factory()->create())
            ->getJson('/api/projects?status=on_hold&priority=high')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'on_hold')
            ->assertJsonPath('data.0.priority', 'high');
    }

    public function test_it_rejects_invalid_list_filters(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson('/api/projects?status=nope&per_page=1000&sort=password&direction=sideways')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status', 'per_page', 'sort', 'direction']);
    }

    public function test_it_searches_client_name_project_name_and_description(): void
    {
        Project::factory()->create(['client_name' => 'Acme Corp', 'project_name' => 'Alpha', 'description' => null]);
        Project::factory()->create(['client_name' => 'Globex', 'project_name' => 'Acme Portal', 'description' => null]);
        Project::factory()->create(['client_name' => 'Initech', 'project_name' => 'Beta', 'description' => 'for ACME staff']);
        Project::factory()->create(['client_name' => 'Umbrella', 'project_name' => 'Gamma', 'description' => null]);

        $this->actingAs(User::factory()->create())
            ->getJson('/api/projects?search=acme')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_search_treats_wildcards_literally(): void
    {
        Project::factory()->create(['client_name' => '100% Organic', 'description' => null]);
        Project::factory()->create(['client_name' => 'Plain Co', 'description' => null]);

        $this->actingAs(User::factory()->create())
            ->getJson('/api/projects?search=%25')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.client_name', '100% Organic');
    }

    public function test_search_combines_with_filters(): void
    {
        Project::factory()->create(['client_name' => 'Acme', 'status' => ProjectStatus::Completed]);
        Project::factory()->create(['client_name' => 'Acme', 'status' => ProjectStatus::Planning]);
        Project::factory()->create(['client_name' => 'Globex', 'status' => ProjectStatus::Completed]);

        $this->actingAs(User::factory()->create())
            ->getJson('/api/projects?search=acme&status=completed')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_it_sorts_by_a_column(): void
    {
        Project::factory()->create(['project_name' => 'Bravo']);
        Project::factory()->create(['project_name' => 'Charlie']);
        Project::factory()->create(['project_name' => 'Alpha']);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/projects?sort=project_name&direction=asc')
            ->assertJsonPath('data.*.project_name', ['Alpha', 'Bravo', 'Charlie']);

        $this->actingAs($user)
            ->getJson('/api/projects?sort=project_name&direction=desc')
            ->assertJsonPath('data.*.project_name', ['Charlie', 'Bravo', 'Alpha']);
    }

    public function test_it_sorts_priority_logically_not_alphabetically(): void
    {
        Project::factory()->create(['priority' => ProjectPriority::Medium]);
        Project::factory()->create(['priority' => ProjectPriority::High]);
        Project::factory()->create(['priority' => ProjectPriority::Low]);

        $this->actingAs(User::factory()->create())
            ->getJson('/api/projects?sort=priority&direction=desc')
            ->assertJsonPath('data.*.priority', ['high', 'medium', 'low']);
    }

    public function test_it_sorts_status_in_workflow_order(): void
    {
        Project::factory()->create(['status' => ProjectStatus::Completed]);
        Project::factory()->create(['status' => ProjectStatus::Planning]);
        Project::factory()->create(['status' => ProjectStatus::OnHold]);
        Project::factory()->create(['status' => ProjectStatus::InProgress]);

        $this->actingAs(User::factory()->create())
            ->getJson('/api/projects?sort=status&direction=asc')
            ->assertJsonPath('data.*.status', ['planning', 'in_progress', 'on_hold', 'completed']);
    }

    public function test_it_sorts_by_due_date(): void
    {
        Project::factory()->create(['due_date' => '2026-12-01']);
        Project::factory()->create(['due_date' => '2026-11-01']);
        Project::factory()->create(['due_date' => '2027-01-01']);

        $this->actingAs(User::factory()->create())
            ->getJson('/api/projects?sort=due_date&direction=asc')
            ->assertJsonPath('data.*.due_date', ['2026-11-01', '2026-12-01', '2027-01-01']);
    }

    public function test_default_order_is_newest_first(): void
    {
        $first = Project::factory()->create();
        $second = Project::factory()->create();

        $this->actingAs(User::factory()->create())
            ->getJson('/api/projects')
            ->assertJsonPath('data.*.id', [$second->id, $first->id]);
    }

    public function test_it_shows_a_single_project(): void
    {
        $project = Project::factory()->create(['client_name' => 'Globex']);

        $this->actingAs(User::factory()->create())
            ->getJson("/api/projects/{$project->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $project->id)
            ->assertJsonPath('data.client_name', 'Globex');
    }

    public function test_it_returns_404_for_a_missing_project(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson('/api/projects/999')
            ->assertNotFound();
    }

    public function test_it_creates_a_project(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/api/projects', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.project_name', 'Website Redesign')
            ->assertJsonPath('data.status', 'planning')
            ->assertJsonPath('data.start_date', '2026-11-01');

        $this->assertDatabaseHas('projects', [
            'client_name' => 'Acme Corp',
            'status' => 'planning',
            'priority' => 'high',
        ]);
    }

    public function test_it_validates_a_new_project(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/api/projects', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'client_name', 'project_name', 'status', 'priority', 'start_date', 'due_date',
            ]);
    }

    public function test_it_rejects_invalid_enum_values(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/api/projects', $this->payload(['status' => 'done', 'priority' => 'urgent']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status', 'priority']);
    }

    public function test_due_date_cannot_be_before_start_date(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/api/projects', $this->payload(['start_date' => '2026-12-01', 'due_date' => '2026-11-01']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['due_date']);
    }

    public function test_description_is_optional(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/api/projects', $this->payload(['description' => null]))
            ->assertCreated()
            ->assertJsonPath('data.description', null);
    }

    public function test_it_updates_a_project(): void
    {
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->create())
            ->putJson("/api/projects/{$project->id}", $this->payload(['status' => 'completed', 'priority' => 'low']))
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.priority', 'low');

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'status' => 'completed', 'priority' => 'low']);
    }

    public function test_it_validates_an_update(): void
    {
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->create())
            ->putJson("/api/projects/{$project->id}", $this->payload(['client_name' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['client_name']);
    }

    public function test_it_deletes_a_project(): void
    {
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->create())
            ->deleteJson("/api/projects/{$project->id}")
            ->assertNoContent();

        $this->assertModelMissing($project);
    }
}
