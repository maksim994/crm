import { createRouter, createWebHistory } from 'vue-router'
import { api } from '@/api/client'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  scrollBehavior(to, from, savedPosition) {
    return savedPosition || { left: 0, top: 0 }
  },
  routes: [
    {
      path: '/login',
      name: 'Login',
      component: () => import('@/views/wbooster/Login.vue'),
      meta: { title: 'Вход', guest: true },
    },
    {
      path: '/',
      name: 'Dashboard',
      component: () => import('@/views/wbooster/Dashboard.vue'),
      meta: { title: 'Инфопанель', requiresAuth: true },
    },
    {
      path: '/clients',
      name: 'Clients',
      component: () => import('@/views/wbooster/clients/Index.vue'),
      meta: { title: 'Заказчики', requiresAuth: true },
    },
    {
      path: '/clients/create',
      name: 'ClientsCreate',
      component: () => import('@/views/wbooster/clients/Form.vue'),
      meta: { title: 'Новый заказчик', requiresAuth: true },
    },
    {
      path: '/clients/:id',
      name: 'ClientsShow',
      component: () => import('@/views/wbooster/clients/Show.vue'),
      meta: { title: 'Заказчик', requiresAuth: true },
    },
    {
      path: '/clients/:id/edit',
      name: 'ClientsEdit',
      component: () => import('@/views/wbooster/clients/Form.vue'),
      meta: { title: 'Редактирование заказчика', requiresAuth: true },
    },
    {
      path: '/clients/:id/cabinet-users/create',
      name: 'CabinetUsersCreate',
      component: () => import('@/views/wbooster/clients/CabinetUserForm.vue'),
      meta: { title: 'Доступ в ЛК', requiresAuth: true },
    },
    {
      path: '/clients/:id/cabinet-users/:userId/edit',
      name: 'CabinetUsersEdit',
      component: () => import('@/views/wbooster/clients/CabinetUserForm.vue'),
      meta: { title: 'Редактирование доступа в ЛК', requiresAuth: true },
    },
    {
      path: '/sites',
      name: 'Sites',
      component: () => import('@/views/wbooster/sites/Index.vue'),
      meta: { title: 'Проекты', requiresAuth: true },
    },
    {
      path: '/sites/create',
      name: 'SitesCreate',
      component: () => import('@/views/wbooster/sites/Form.vue'),
      meta: { title: 'Новый проект', requiresAuth: true },
    },
    {
      path: '/sites/:id',
      name: 'SitesShow',
      component: () => import('@/views/wbooster/sites/Show.vue'),
      meta: { title: 'Проект', requiresAuth: true },
    },
    {
      path: '/sites/:id/edit',
      name: 'SitesEdit',
      component: () => import('@/views/wbooster/sites/Form.vue'),
      meta: { title: 'Редактирование проекта', requiresAuth: true },
    },
    {
      path: '/leads',
      name: 'Leads',
      component: () => import('@/views/wbooster/leads/Index.vue'),
      meta: { title: 'Лиды', requiresAuth: true },
    },
    {
      path: '/leads/:id',
      name: 'LeadsShow',
      component: () => import('@/views/wbooster/leads/Show.vue'),
      meta: { title: 'Лид', requiresAuth: true },
    },
    {
      path: '/leads/:id/edit',
      name: 'LeadsEdit',
      component: () => import('@/views/wbooster/leads/Form.vue'),
      meta: { title: 'Редактирование лида', requiresAuth: true },
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: '/',
    },
  ],
})

router.beforeEach(async (to) => {
  if (to.meta.title) {
    document.title = `${to.meta.title} | WBooster`
  }

  let authenticated = false
  if (to.meta.requiresAuth || to.meta.guest) {
    try {
      await api<{ data: { id: number } }>('/user')
      authenticated = true
    } catch {
      authenticated = false
    }
  }

  if (to.meta.requiresAuth && !authenticated) {
    return { name: 'Login', query: { redirect: to.fullPath } }
  }

  if (to.meta.guest && authenticated) {
    return { name: 'Dashboard' }
  }
})

export default router
