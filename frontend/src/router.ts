import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      meta: { layout: 'main' },
      component: () => import('@/modules/home/HomePage.vue'),
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'notFound',
      meta: { layout: 'default' },
      component: () => import('@/views/NotFoundPage.vue'),
    },
  ],
})

export default router
