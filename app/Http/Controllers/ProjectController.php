<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListProjectsRequest;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ProjectController extends Controller
{
    public function __construct(private readonly ProjectRepositoryInterface $projects) {}

    public function index(ListProjectsRequest $request): AnonymousResourceCollection
    {
        return ProjectResource::collection($this->projects->paginate($request->validated()));
    }

    public function store(StoreProjectRequest $request): ProjectResource
    {
        return new ProjectResource($this->projects->create($request->validated()));
    }

    public function show(Project $project): ProjectResource
    {
        return new ProjectResource($project);
    }

    public function update(UpdateProjectRequest $request, Project $project): ProjectResource
    {
        return new ProjectResource($this->projects->update($project, $request->validated()));
    }

    public function destroy(Project $project): Response
    {
        $this->projects->delete($project);

        return response()->noContent();
    }
}
