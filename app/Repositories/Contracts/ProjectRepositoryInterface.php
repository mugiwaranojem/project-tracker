<?php

namespace App\Repositories\Contracts;

use App\Models\Project;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProjectRepositoryInterface
{
    /**
     * @param  array{search?: ?string, status?: string, priority?: string, sort?: string, direction?: string, per_page?: int}  $filters
     * @return LengthAwarePaginator<int, Project>
     */
    public function paginate(array $filters): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Project;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Project $project, array $data): Project;

    public function delete(Project $project): void;
}
