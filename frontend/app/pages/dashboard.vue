<script setup lang="ts">
definePageMeta({
  middleware: 'sanctum:auth',
})

useHead({ title: 'Dashboard' })

const user = useSanctumUser<{ name?: string }>()
const { logout } = useSanctumAuth()
</script>

<template>
  <UContainer class="py-8">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-semibold">Dashboard</h1>
      <UButton color="neutral" variant="outline" icon="i-lucide-log-out" @click="logout()">Sign out</UButton>
    </div>
    <p class="mt-4 text-muted">Welcome{{ user?.name ? `, ${user.name}` : '' }}.</p>
  </UContainer>
</template>
