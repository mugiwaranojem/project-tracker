<script setup lang="ts">
// Pages set a plain name with useHead({ title: 'Login' }); this turns it into
// "Project Tracker | Login". A page with no title of its own just shows "Project Tracker".
const APP_NAME = 'Project Tracker'

useHead({
  titleTemplate: title => (!title || title === APP_NAME ? APP_NAME : `${APP_NAME} | ${title}`),
})
</script>

<template>
  <UApp>
    <NuxtRouteAnnouncer />
    <NuxtLayout>
      <NuxtPage />
    </NuxtLayout>
  </UApp>
</template>
