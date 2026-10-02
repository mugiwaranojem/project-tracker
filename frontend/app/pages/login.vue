<script setup lang="ts">
definePageMeta({
  middleware: 'sanctum:guest', // Prevent logged-in users from seeing this page
})

useHead({ title: 'Login' })

const { login } = useSanctumAuth()
const toast = useToast()

const credentials = reactive({ email: '', password: '' })
const loading = ref(false)

async function handleLogin() {
  loading.value = true
  try {
    await login(credentials)
  } catch {
    credentials.password = ''
    toast.add({
      title: 'Sign-in failed',
      description: 'Check your email and password and try again.',
      icon: 'i-lucide-circle-alert',
      color: 'error',
    })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center p-4">
    <UCard class="w-full max-w-sm">
      <template #header>
        <h1 class="text-lg font-semibold">Project Tracker</h1>
      </template>

      <form class="space-y-4" @submit.prevent="handleLogin">
        <UFormField label="Email">
          <UInput v-model="credentials.email" type="email" autocomplete="email" required class="w-full" />
        </UFormField>
        <UFormField label="Password">
          <UInput v-model="credentials.password" type="password" autocomplete="current-password" required class="w-full" />
        </UFormField>
        <UButton type="submit" block :loading="loading">Sign in</UButton>
      </form>
    </UCard>
  </div>
</template>
