<script setup lang="ts">
const user = useSanctumUser()

await navigateTo(user.value ? '/dashboard' : '/login')
</script>

<template>
  <div />
</template>
