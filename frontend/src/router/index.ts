import { createRouter, createWebHistory } from 'vue-router'
import Integrations from '../views/Integrations.vue'
import Bots from '../views/Bots.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'dashboard',
      component: () => import('../views/Dashboard.vue')
    },
    {
      path: '/editor/:id',
      name: 'editor',
      component: () => import('../views/Editor.vue')
    },
    {
      path: '/integrations',
      name: 'Integrations',
      component: Integrations
    },
    {
      path: '/bots',
      name: 'Bots',
      component: Bots
    }
  ]
})

export default router