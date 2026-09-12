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
      name: 'settings',
      meta: { layout: 'main', auth: true },
      component: () => import('@/modules/settings/SettingsPage.vue'),
    },
    {
      path: '/reviews',
      name: 'organization',
      meta: { layout: 'main', auth: true },
      component: () => import('@/modules/reviews/ReviewsPage.vue'),
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'notFound',
      meta: { layout: 'default' },
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
    return { name: 'settings' }
  }
})

export default router
