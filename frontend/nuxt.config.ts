// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
  modules: ['@nuxt/ui', 'nuxt-auth-sanctum'],
  css: ['~/assets/css/main.css'],

  // Static SPA build: every page fetches its data client-side via Sanctum/$fetch, so the
  // output can be served as plain static files with no Node process.
  ssr: false,

  app: {
    head: {
      title: 'Project Tracker',
    },
  },

  // Sanctum module setup for SPA authentication against the Laravel API. The base URL is
  // build-time configurable (NUXT_PUBLIC_API_BASE_URL); it defaults to the local Docker API.
  sanctum: {
    baseUrl: process.env.NUXT_PUBLIC_API_BASE_URL ?? 'http://localhost:8000',
    endpoints: {
      csrf: '/sanctum/csrf-cookie',
      login: '/login',
      logout: '/logout',
      user: '/api/user',
    },
    redirect: {
      onLogin: '/projects',
      onLogout: '/login',
      onAuthOnly: '/login',
      onGuestOnly: '/projects',
    },
  },
})
