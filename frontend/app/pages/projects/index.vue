<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'

definePageMeta({
  middleware: 'sanctum:auth',
})

useHead({ title: 'Projects' })

const api = useProjectsApi()
const toast = useToast()

// The selects can't hold an empty value, so "all" stands in for "no filter".
const ALL = 'all'
const statusItems = [{ label: 'All statuses', value: ALL }, ...STATUS_OPTIONS]
const priorityItems = [{ label: 'All priorities', value: ALL }, ...PRIORITY_OPTIONS]

const searchInput = ref('')
const search = ref('')
const status = ref<string>(ALL)
const priority = ref<string>(ALL)
const sort = ref<ProjectSortField>('created_at')
const direction = ref<'asc' | 'desc'>('desc')
const page = ref(1)
const perPage = 10

// Wait for a pause in typing before querying the API.
let searchTimer: ReturnType<typeof setTimeout>
watch(searchInput, (value) => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => (search.value = value.trim()), 300)
})
onBeforeUnmount(() => clearTimeout(searchTimer))

// Any change to what is being asked for starts again from the first page.
watch([search, status, priority, sort, direction], () => (page.value = 1))

const params = computed<ProjectListParams>(() => ({
  search: search.value || undefined,
  status: status.value === ALL ? undefined : (status.value as ProjectStatus),
  priority: priority.value === ALL ? undefined : (priority.value as ProjectPriority),
  sort: sort.value,
  direction: direction.value,
  page: page.value,
  per_page: perPage,
}))

const { data, status: loadState, error, refresh } = await useAsyncData(
  'projects',
  () => api.list(params.value),
  { watch: [params] },
)

const projects = computed(() => data.value?.data ?? [])
const total = computed(() => data.value?.meta.total ?? 0)
const loading = computed(() => loadState.value === 'pending')
const hasFilters = computed(() => !!search.value || status.value !== ALL || priority.value !== ALL)

function clearFilters() {
  searchInput.value = ''
  search.value = ''
  status.value = ALL
  priority.value = ALL
}

function toggleSort(field: ProjectSortField) {
  if (sort.value === field) {
    direction.value = direction.value === 'asc' ? 'desc' : 'asc'
  } else {
    sort.value = field
    direction.value = 'asc'
  }
}

function sortIcon(field: ProjectSortField) {
  if (sort.value !== field) return 'i-lucide-arrow-up-down'
  return direction.value === 'asc' ? 'i-lucide-arrow-up' : 'i-lucide-arrow-down'
}

const columns: TableColumn<Project>[] = [
  { accessorKey: 'client_name', header: 'Client' },
  { accessorKey: 'project_name', header: 'Project' },
  { accessorKey: 'status', header: 'Status' },
  { accessorKey: 'priority', header: 'Priority' },
  { accessorKey: 'start_date', header: 'Start' },
  { accessorKey: 'due_date', header: 'Due' },
  { id: 'actions', header: '' },
]

const sortableColumns: ProjectSortField[] = ['client_name', 'project_name', 'status', 'priority', 'start_date', 'due_date']

// Create / edit
const formOpen = ref(false)
const editing = ref<Project | null>(null)

function openCreate() {
  editing.value = null
  formOpen.value = true
}

function openEdit(project: Project) {
  editing.value = project
  formOpen.value = true
}

// Delete
const deleteOpen = ref(false)
const deleting = ref<Project | null>(null)
const deleteLoading = ref(false)

function confirmDelete(project: Project) {
  deleting.value = project
  deleteOpen.value = true
}

async function deleteProject() {
  if (!deleting.value) return
  deleteLoading.value = true
  try {
    await api.remove(deleting.value.id)
    toast.add({ title: 'Project deleted', icon: 'i-lucide-circle-check', color: 'success' })
    deleteOpen.value = false
    // Deleting the last row of a page would otherwise leave us on an empty page.
    if (projects.value.length === 1 && page.value > 1) page.value -= 1
    else await refresh()
  } catch (e) {
    toast.add({ title: 'Could not delete project', description: errorMessage(e), icon: 'i-lucide-circle-alert', color: 'error' })
  } finally {
    deleteLoading.value = false
  }
}
</script>

<template>
  <UContainer class="py-8">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-2xl font-semibold">Projects</h1>
        <p class="text-sm text-muted">Track client projects, progress and priorities.</p>
      </div>
      <UButton icon="i-lucide-plus" @click="openCreate">New project</UButton>
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-2">
      <UInput
        v-model="searchInput"
        icon="i-lucide-search"
        placeholder="Search client, project or description"
        class="w-full sm:w-80"
      />
      <USelect v-model="status" :items="statusItems" class="w-44" aria-label="Filter by status" />
      <USelect v-model="priority" :items="priorityItems" class="w-44" aria-label="Filter by priority" />
      <UButton
        v-if="hasFilters"
        color="neutral"
        variant="ghost"
        icon="i-lucide-x"
        @click="clearFilters"
      >
        Clear
      </UButton>
    </div>

    <UAlert
      v-if="error"
      color="error"
      variant="subtle"
      icon="i-lucide-circle-alert"
      title="Could not load projects"
      :description="errorMessage(error)"
      class="mb-4"
    >
      <template #actions>
        <UButton color="error" variant="outline" size="xs" @click="() => refresh()">Retry</UButton>
      </template>
    </UAlert>

    <UTable :data="projects" :columns="columns" :loading="loading" class="rounded-lg border border-default">
      <template v-for="field in sortableColumns" :key="field" #[`${field}-header`]="{ column }">
        <UButton
          color="neutral"
          variant="ghost"
          size="sm"
          class="-mx-2.5"
          :label="String(column.columnDef.header)"
          :trailing-icon="sortIcon(field)"
          @click="toggleSort(field)"
        />
      </template>

      <template #project_name-cell="{ row }">
        <div class="max-w-xs">
          <p class="truncate font-medium text-highlighted">{{ row.original.project_name }}</p>
          <p v-if="row.original.description" class="truncate text-xs text-muted">{{ row.original.description }}</p>
        </div>
      </template>

      <template #status-cell="{ row }">
        <UBadge :color="STATUS_COLORS[row.original.status]" variant="subtle">
          {{ statusLabel(row.original.status) }}
        </UBadge>
      </template>

      <template #priority-cell="{ row }">
        <UBadge :color="PRIORITY_COLORS[row.original.priority]" variant="subtle">
          {{ priorityLabel(row.original.priority) }}
        </UBadge>
      </template>

      <template #start_date-cell="{ row }">{{ formatDate(row.original.start_date) }}</template>

      <template #due_date-cell="{ row }">
        <span :class="isOverdue(row.original) ? 'font-medium text-error' : ''">
          {{ formatDate(row.original.due_date) }}
        </span>
        <UBadge v-if="isOverdue(row.original)" color="error" variant="subtle" size="sm" class="ml-2">Overdue</UBadge>
      </template>

      <template #actions-cell="{ row }">
        <div class="flex justify-end gap-1">
          <UButton
            color="neutral"
            variant="ghost"
            icon="i-lucide-pencil"
            :aria-label="`Edit ${row.original.project_name}`"
            @click="openEdit(row.original)"
          />
          <UButton
            color="error"
            variant="ghost"
            icon="i-lucide-trash-2"
            :aria-label="`Delete ${row.original.project_name}`"
            @click="confirmDelete(row.original)"
          />
        </div>
      </template>

      <template #empty>
        <div class="py-10 text-center">
          <UIcon name="i-lucide-folder-open" class="mx-auto size-8 text-dimmed" />
          <p class="mt-2 font-medium">{{ hasFilters ? 'No projects match your filters' : 'No projects yet' }}</p>
          <p class="text-sm text-muted">
            {{ hasFilters ? 'Try a different search or clear the filters.' : 'Create your first project to get started.' }}
          </p>
          <UButton v-if="hasFilters" class="mt-3" color="neutral" variant="outline" @click="clearFilters">Clear filters</UButton>
          <UButton v-else class="mt-3" icon="i-lucide-plus" @click="openCreate">New project</UButton>
        </div>
      </template>
    </UTable>

    <div v-if="total > perPage" class="mt-4 flex items-center justify-between">
      <p class="text-sm text-muted">{{ total }} projects</p>
      <UPagination v-model:page="page" :items-per-page="perPage" :total="total" />
    </div>

    <ProjectFormModal v-model:open="formOpen" :project="editing" @saved="refresh()" />

    <UModal
      v-model:open="deleteOpen"
      title="Delete project"
      :description="`Delete &quot;${deleting?.project_name ?? ''}&quot; for ${deleting?.client_name ?? ''}? This cannot be undone.`"
    >
      <template #footer="{ close }">
        <div class="flex w-full justify-end gap-2">
          <UButton color="neutral" variant="outline" :disabled="deleteLoading" @click="close">Cancel</UButton>
          <UButton color="error" :loading="deleteLoading" @click="deleteProject">Delete</UButton>
        </div>
      </template>
    </UModal>
  </UContainer>
</template>
