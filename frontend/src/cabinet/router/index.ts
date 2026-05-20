import { createRouter, createWebHistory, type RouteLocationNormalized } from 'vue-router'
import { api, ensureCsrf } from '@cabinet/api/client'

interface CabinetUser {
  data: {
    id: string
    role: string
  }
}

const router = createRouter({
  history: createWebHistory('/cabinet/'),
  scrollBehavior() {
    return { left: 0, top: 0 }
  },
  routes: [
    {
      path: '/login',
      name: 'Login',
      component: () => import('@cabinet/views/Login.vue'),
      meta: { title: 'Вход', guest: true },
    },
    {
      path: '/',
      name: 'Leads',
      component: () => import('@cabinet/views/leads/Index.vue'),
      meta: { title: 'Лиды', requiresAuth: true },
    },
    {
      path: '/leads/:id',
      name: 'LeadShow',
      component: () => import('@cabinet/views/leads/Show.vue'),
      meta: { title: 'Карточка лида', requiresAuth: true },
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: '/',
    },
  ],
})

async function fetchClientUser(): Promise<{ user: CabinetUser | null; wrongRole: boolean }> {
  try {
    const user = await api<CabinetUser>('/user')
    if (user.data.role !== 'client_user') {
      await api('/logout', { method: 'POST' }).catch(() => undefined)
      return { user: null, wrongRole: true }
    }
    return { user, wrongRole: false }
  } catch {
    return { user: null, wrongRole: false }
  }
}

async function consumeImpersonationToken(to: RouteLocationNormalized): Promise<boolean> {
  const token = to.query.impersonate
  if (typeof token !== 'string' || !token) {
    return false
  }

  await ensureCsrf()
  await api('/impersonate', {
    method: 'POST',
    body: JSON.stringify({ token }),
  })

  return true
}

router.beforeEach(async (to) => {
  if (to.meta.title) {
    document.title = `${to.meta.title} | WBooster`
  }

  if (to.query.impersonate) {
    try {
      await consumeImpersonationToken(to)
      const { impersonate: _removed, ...query } = to.query

      return {
        path: to.path,
        query,
        replace: true,
      }
    } catch {
      return {
        name: 'Login',
        query: { error: 'impersonate_failed' },
      }
    }
  }

  const needsAuthCheck = to.meta.requiresAuth || to.meta.guest
  const { user, wrongRole } = needsAuthCheck ? await fetchClientUser() : { user: null, wrongRole: false }
  const authenticated = user !== null

  if (to.meta.requiresAuth && !authenticated) {
    return {
      name: 'Login',
      query: {
        redirect: to.fullPath,
        ...(wrongRole ? { error: 'wrong_role' } : {}),
      },
    }
  }

  if (to.meta.guest && authenticated) {
    return { name: 'Leads' }
  }
})

export default router
