/** Thin wrapper over the Laravel projects API, using the Sanctum-aware fetch client. */
export function useProjectsApi() {
  const client = useSanctumClient()

  return {
    list: (params: ProjectListParams) =>
      client<Paginated<Project>>('/api/projects', { params }),

    create: (input: ProjectInput) =>
      client<{ data: Project }>('/api/projects', { method: 'POST', body: input }),

    update: (id: number, input: ProjectInput) =>
      client<{ data: Project }>(`/api/projects/${id}`, { method: 'PUT', body: input }),

    remove: (id: number) =>
      client<void>(`/api/projects/${id}`, { method: 'DELETE' }),
  }
}
