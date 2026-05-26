import { ref } from 'vue'
import { api } from '@cabinet/api/client'

export interface CabinetSite {
  id: string
  name: string
}

const sites = ref<CabinetSite[]>([])
let loadPromise: Promise<CabinetSite[]> | null = null

export async function loadCabinetSites(force = false): Promise<CabinetSite[]> {
  if (sites.value.length > 0 && !force) {
    return sites.value
  }

  if (!loadPromise || force) {
    loadPromise = api<{ data: CabinetSite[] }>('/sites').then((res) => {
      sites.value = res.data
      return res.data
    })
  }

  return loadPromise
}

export function useCabinetSites() {
  return {
    sites,
    loadSites: loadCabinetSites,
    siteById(id: string): CabinetSite | undefined {
      return sites.value.find((site) => site.id === id)
    },
    hasSiteAccess(id: string): boolean {
      return sites.value.some((site) => site.id === id)
    },
  }
}

export async function ensureSiteAccess(siteId: string): Promise<boolean> {
  const list = await loadCabinetSites()
  return list.some((site) => site.id === siteId)
}
