<script setup lang="ts">
const user = useSanctumUser()

await navigateTo(user.value ? '/projects' : '/login')
</script>

<template>
  <div />
</template>
