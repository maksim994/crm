const baseURL = import.meta.env.VITE_API_URL ?? ''

export class ApiError extends Error {
  constructor(
    message: string,
    public status: number,
    public errors?: Record<string, string[]>,
  ) {
    super(message)
  }
}

function getXsrfToken(): string | null {
  const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/)
  if (!match) {
    return null
  }
  try {
    return decodeURIComponent(match[1])
  } catch {
    return match[1]
  }
}

let csrfPromise: Promise<void> | null = null

export function ensureCsrf(): Promise<void> {
  if (!csrfPromise) {
    csrfPromise = fetch(`${baseURL}/sanctum/csrf-cookie`, {
      credentials: 'include',
    }).then(() => undefined)
  }
  return csrfPromise
}

function resetCsrf(): void {
  csrfPromise = null
}

function buildHeaders(options: RequestInit): Record<string, string> {
  const headers: Record<string, string> = {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    ...(options.headers as Record<string, string>),
  }

  if (options.body && !headers['Content-Type']) {
    headers['Content-Type'] = 'application/json'
  }

  const xsrf = getXsrfToken()
  if (xsrf) {
    headers['X-XSRF-TOKEN'] = xsrf
  }

  return headers
}

export async function api<T>(path: string, options: RequestInit = {}): Promise<T> {
  const method = (options.method ?? 'GET').toUpperCase()
  const isMutating = method !== 'GET' && method !== 'HEAD'

  if (isMutating) {
    await ensureCsrf()
  }

  const url = `${baseURL}/api/client${path}`

  let response = await fetch(url, {
    ...options,
    credentials: 'include',
    headers: buildHeaders(options),
  })

  if (response.status === 419 && isMutating) {
    resetCsrf()
    await ensureCsrf()
    response = await fetch(url, {
      ...options,
      credentials: 'include',
      headers: buildHeaders(options),
    })
  }

  if (response.status === 204) {
    return undefined as T
  }

  const contentType = response.headers.get('content-type') ?? ''
  if (!contentType.includes('application/json')) {
    if (!response.ok) {
      throw new ApiError('Ошибка запроса', response.status)
    }
    return (await response.text()) as T
  }

  const data = await response.json().catch(() => ({}))

  if (!response.ok) {
    const message = data.message ?? 'Ошибка запроса'
    throw new ApiError(message, response.status, data.errors)
  }

  return data as T
}

export function exportLeadsUrl(filters: Record<string, string>): string {
  const params = new URLSearchParams()
  Object.entries(filters).forEach(([key, value]) => {
    if (value) {
      params.set(key, value)
    }
  })
  const query = params.toString()

  return `${baseURL}/api/client/leads/export${query ? `?${query}` : ''}`
}

export interface Paginated<T> {
  data: T[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
  links: Record<string, string | null>
}
