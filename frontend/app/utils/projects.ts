export type ProjectStatus = 'planning' | 'in_progress' | 'on_hold' | 'completed'
export type ProjectPriority = 'low' | 'medium' | 'high'

export type ProjectSortField =
  | 'client_name'
  | 'project_name'
  | 'status'
  | 'priority'
  | 'start_date'
  | 'due_date'
  | 'created_at'

export interface Project {
  id: number
  client_name: string
  project_name: string
  description: string | null
  status: ProjectStatus
  priority: ProjectPriority
  start_date: string
  due_date: string
  created_at: string
  updated_at: string
}

export type ProjectInput = Omit<Project, 'id' | 'created_at' | 'updated_at'>

export interface ProjectListParams {
  search?: string
  status?: ProjectStatus
  priority?: ProjectPriority
  sort?: ProjectSortField
  direction?: 'asc' | 'desc'
  page?: number
  per_page?: number
}

export interface Paginated<T> {
  data: T[]
  meta: { current_page: number, last_page: number, per_page: number, total: number }
}

export const STATUS_OPTIONS: { label: string, value: ProjectStatus }[] = [
  { label: 'Planning', value: 'planning' },
  { label: 'In Progress', value: 'in_progress' },
  { label: 'On Hold', value: 'on_hold' },
  { label: 'Completed', value: 'completed' },
]

export const PRIORITY_OPTIONS: { label: string, value: ProjectPriority }[] = [
  { label: 'Low', value: 'low' },
  { label: 'Medium', value: 'medium' },
  { label: 'High', value: 'high' },
]

export const STATUS_COLORS: Record<ProjectStatus, 'neutral' | 'info' | 'warning' | 'success'> = {
  planning: 'neutral',
  in_progress: 'info',
  on_hold: 'warning',
  completed: 'success',
}

export const PRIORITY_COLORS: Record<ProjectPriority, 'neutral' | 'warning' | 'error'> = {
  low: 'neutral',
  medium: 'warning',
  high: 'error',
}

export function statusLabel(status: ProjectStatus): string {
  return STATUS_OPTIONS.find(option => option.value === status)?.label ?? status
}

export function priorityLabel(priority: ProjectPriority): string {
  return PRIORITY_OPTIONS.find(option => option.value === priority)?.label ?? priority
}

/** Formats an API date ("2026-11-01") without shifting it by the viewer's timezone. */
export function formatDate(date: string): string {
  return new Date(`${date}T00:00:00`).toLocaleDateString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

/** A project is overdue once its due date has passed and it isn't completed. */
export function isOverdue(project: Pick<Project, 'due_date' | 'status'>): boolean {
  if (project.status === 'completed') return false
  const today = new Date()
  const todayKey = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`
  return project.due_date < todayKey
}

/** Pulls the first message per field out of a Laravel 422 response. */
export function validationErrors(error: unknown): { name: string, message: string }[] {
  const errors = (error as { data?: { errors?: Record<string, string[]> } })?.data?.errors
  if (!errors) return []
  return Object.entries(errors).map(([name, messages]) => ({ name, message: messages[0] ?? 'Invalid value' }))
}

/** A message safe to show a user: friendly text for framework-level failures, the API's own text otherwise. */
export function errorMessage(error: unknown, fallback = 'Something went wrong. Please try again.'): string {
  const e = error as { statusCode?: number, status?: number, data?: { message?: string } }
  const status = e?.statusCode ?? e?.status

  if (status === 404) return 'This project no longer exists. It may have been deleted already.'
  if (status === 419) return 'Your session expired. Please reload the page and try again.'
  if (status && status >= 500) return fallback

  return e?.data?.message ?? fallback
}
