<script setup lang="ts">
const user = useSanctumUser<{ name?: string }>()
const { logout } = useSanctumAuth()
</script>

<template>
  <div class="min-h-screen">
    <header class="border-b border-default">
      <UContainer class="flex h-14 items-center justify-between">
        <NuxtLink to="/projects" class="flex items-center gap-2 font-semibold">
          <UIcon name="i-lucide-kanban-square" class="size-5 text-primary" />
          Project Tracker
        </NuxtLink>

        <div class="flex items-center gap-3">
          <span v-if="user?.name" class="hidden text-sm text-muted sm:inline">{{ user.name }}</span>
          <UColorModeButton />
          <UButton color="neutral" variant="outline" size="sm" icon="i-lucide-log-out" @click="logout()">
            Sign out
          </UButton>
        </div>
      </UContainer>
    </header>

    <main>
      <slot />
    </main>
  </div>
</template>
