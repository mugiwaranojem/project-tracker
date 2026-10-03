// https://nuxt.com/docs/api/configuration/nuxt-config
// Guard against a scheme-less value (e.g. "api.example.com"): the module would treat it as a
// relative path and request /projects/api.example.com/api/user from the SPA's own origin.
const apiBaseUrl = (process.env.NUXT_PUBLIC_API_BASE_URL ?? 'http://localhost:8000').trim().replace(/\/+$/, '')
if (!/^https?:\/\//.test(apiBaseUrl)) {
  throw new Error(`NUXT_PUBLIC_API_BASE_URL must start with http:// or https:// (got "${apiBaseUrl}")`)
}

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
    baseUrl: apiBaseUrl,
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
