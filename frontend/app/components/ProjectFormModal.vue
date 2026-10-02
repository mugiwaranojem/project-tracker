<script setup lang="ts">
const props = defineProps<{
  /** The project being edited, or null when creating a new one. */
  project: Project | null
}>()

const emit = defineEmits<{ saved: [] }>()
const open = defineModel<boolean>('open', { default: false })

const api = useProjectsApi()
const toast = useToast()

function blankState(): ProjectInput {
  return {
    client_name: '',
    project_name: '',
    description: '',
    status: 'planning',
    priority: 'medium',
    start_date: '',
    due_date: '',
  }
}

const state = reactive<ProjectInput>(blankState())
const form = useTemplateRef('form')
const saving = ref(false)

// Re-seed the form every time the modal opens, so a cancelled edit never leaks into the next one.
watch(open, (isOpen) => {
  if (!isOpen) return
  Object.assign(state, props.project
    ? {
        client_name: props.project.client_name,
        project_name: props.project.project_name,
        description: props.project.description ?? '',
        status: props.project.status,
        priority: props.project.priority,
        start_date: props.project.start_date,
        due_date: props.project.due_date,
      }
    : blankState())
})

// Mirrors the API rules so most mistakes are caught before a round trip; the API stays the authority.
function validate(values: Partial<ProjectInput>) {
  const errors: { name: string, message: string }[] = []
  if (!values.client_name?.trim()) errors.push({ name: 'client_name', message: 'Client name is required' })
  if (!values.project_name?.trim()) errors.push({ name: 'project_name', message: 'Project name is required' })
  if (!values.start_date) errors.push({ name: 'start_date', message: 'Start date is required' })
  if (!values.due_date) errors.push({ name: 'due_date', message: 'Due date is required' })
  if (values.start_date && values.due_date && values.due_date < values.start_date) {
    errors.push({ name: 'due_date', message: 'Due date cannot be before the start date' })
  }
  return errors
}

async function onSubmit() {
  saving.value = true
  const payload = { ...state, description: state.description?.trim() || null }

  try {
    if (props.project) {
      await api.update(props.project.id, payload)
    } else {
      await api.create(payload)
    }
    toast.add({
      title: props.project ? 'Project updated' : 'Project created',
      icon: 'i-lucide-circle-check',
      color: 'success',
    })
    open.value = false
    emit('saved')
  } catch (error) {
    const fieldErrors = validationErrors(error)
    if (fieldErrors.length) {
      form.value?.setErrors(fieldErrors)
    } else {
      toast.add({ title: 'Could not save project', description: errorMessage(error), icon: 'i-lucide-circle-alert', color: 'error' })
    }
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <UModal
    v-model:open="open"
    :title="project ? 'Edit project' : 'New project'"
    :description="project ? 'Update the details of this client project.' : 'Add a client project to track.'"
  >
    <template #body>
      <UForm
        id="project-form"
        ref="form"
        :state="state"
        :validate="validate"
        class="space-y-4"
        @submit="onSubmit"
      >
        <div class="grid gap-4 sm:grid-cols-2">
          <UFormField label="Client name" name="client_name" required>
            <UInput v-model="state.client_name" :maxlength="255" class="w-full" />
          </UFormField>
          <UFormField label="Project name" name="project_name" required>
            <UInput v-model="state.project_name" :maxlength="255" class="w-full" />
          </UFormField>
        </div>

        <UFormField label="Description" name="description">
          <UTextarea v-model="state.description" :rows="3" :maxlength="5000" class="w-full" />
        </UFormField>

        <div class="grid gap-4 sm:grid-cols-2">
          <UFormField label="Status" name="status" required>
            <USelect v-model="state.status" :items="STATUS_OPTIONS" class="w-full" />
          </UFormField>
          <UFormField label="Priority" name="priority" required>
            <USelect v-model="state.priority" :items="PRIORITY_OPTIONS" class="w-full" />
          </UFormField>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <UFormField label="Start date" name="start_date" required>
            <UInput v-model="state.start_date" type="date" class="w-full" />
          </UFormField>
          <UFormField label="Due date" name="due_date" required>
            <UInput v-model="state.due_date" type="date" class="w-full" />
          </UFormField>
        </div>
      </UForm>
    </template>

    <template #footer="{ close }">
      <div class="flex w-full justify-end gap-2">
        <UButton color="neutral" variant="outline" :disabled="saving" @click="close">Cancel</UButton>
        <UButton type="submit" form="project-form" :loading="saving">
          {{ project ? 'Save changes' : 'Create project' }}
        </UButton>
      </div>
    </template>
  </UModal>
</template>
