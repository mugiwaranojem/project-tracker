<?php

namespace App\Models;

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'client_name',
    'project_name',
    'description',
    'status',
    'priority',
    'start_date',
    'due_date',
])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    /**
     * Columns the list endpoint may be sorted by.
     *
     * @var list<string>
     */
    public const SORTABLE = [
        'client_name',
        'project_name',
        'status',
        'priority',
        'start_date',
        'due_date',
        'created_at',
    ];

    /**
     * Case-insensitive match on client name, project name or description.
     *
     * @param  Builder<Project>  $query
     */
    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        if ($term === null || trim($term) === '') {
            return;
        }

        // "!" is the explicit ESCAPE character: unlike backslash, it behaves the same on MySQL and SQLite.
        $like ='%'.preg_replace('/([!%_])/', '!$1', trim($term)).'%';

        $query->where(function (Builder $query) use ($like) {
            $query->whereRaw("client_name LIKE ? ESCAPE '!'", [$like])
                ->orWhereRaw("project_name LIKE ? ESCAPE '!'", [$like])
                ->orWhereRaw("description LIKE ? ESCAPE '!'", [$like]);
        });
    }

    /**
     * Sort by a whitelisted column. Status and priority follow their logical order
     * (low -> high, planning -> completed) rather than alphabetical order.
     *
     * @param  Builder<Project>  $query
     */
    #[Scope]
    protected function sortBy(Builder $query, string $column, string $direction = 'asc'): void
    {
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';

        match ($column) {
            'status' => $query->orderByRaw(self::enumOrderSql('status', ProjectStatus::cases()).' '.$direction),
            'priority' => $query->orderByRaw(self::enumOrderSql('priority', ProjectPriority::cases()).' '.$direction),
            default => $query->orderBy(in_array($column, self::SORTABLE, true) ? $column : 'id', $direction),
        };

        // Stable ordering for ties, so pagination never repeats or skips rows.
        $query->orderBy('id', $direction);
    }

    /**
     * @param  array<int, ProjectStatus|ProjectPriority>  $cases
     */
    private static function enumOrderSql(string $column, array $cases): string
    {
        $whens = collect($cases)
            ->values()
            ->map(fn ($case, $position) => "WHEN '{$case->value}' THEN {$position}")
            ->implode(' ');

        return "CASE {$column} {$whens} END";
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'priority' => ProjectPriority::class,
            'start_date' => 'date',
            'due_date' => 'date',
        ];
    }
}
