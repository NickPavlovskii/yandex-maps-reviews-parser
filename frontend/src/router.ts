import { createRouter, createWebHistory } from 'vue-router'
import { authStore } from '@/store/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'login',
      meta: { layout: 'default', guest: true },
      component: () => import('@/modules/auth/LoginPage.vue'),
    },
    {
      path: '/',
      name: 'home',
      meta: { layout: 'main', auth: true },
      component: () => import('@/modules/settings/SettingsPage.vue'),
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'notFound',
      meta: { layout: 'default', auth: true },
      component: () => import('@/views/NotFoundPage.vue'),
    },
  ],
})

router.beforeEach(async (to) => {
  await authStore.ensureLoaded()

  if (to.meta.auth && !authStore.user) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.guest && authStore.user) {
    return { name: 'home' }
  }
})

export default router
