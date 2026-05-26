import { api } from '@cabinet/api/client'

export interface AnalyticsSummary {
  labels: string[]
  values: number[]
}

export interface AnalyticsTableRow {
  label: string
  visits: number
  bounce_rate?: number
  page_depth?: number
  avg_duration_sec?: number
}

export interface AnalyticsTimeseries {
  categories: string[]
  series: Array<{ name: string; data: number[] }>
}

export interface AnalyticsReportData {
  summary: AnalyticsSummary
  table: AnalyticsTableRow[]
  timeseries?: AnalyticsTimeseries
}

export type AnalyticsReportType =
  | 'traffic-sources'
  | 'search-engines'
  | 'search-branded'
  | 'search-non-branded'
  | 'geography'
  | 'devices'

export interface AnalyticsQuery {
  dateFrom: string
  dateTo: string
  groupBy: 'day' | 'month'
}

function today(): string {
  return new Date().toISOString().slice(0, 10)
}

function dateDaysAgo(days: number): string {
  const date = new Date()
  date.setDate(date.getDate() - days)
  return date.toISOString().slice(0, 10)
}

function dateYearsAgo(years: number): string {
  const date = new Date()
  date.setFullYear(date.getFullYear() - years)
  return date.toISOString().slice(0, 10)
}

export function createAnalyticsQuery(options?: Partial<AnalyticsQuery> & { days?: number }): AnalyticsQuery {
  return {
    dateFrom: options?.dateFrom ?? dateDaysAgo(options?.days ?? 30),
    dateTo: options?.dateTo ?? today(),
    groupBy: options?.groupBy ?? 'month',
  }
}

export function createYearAnalyticsQuery(groupBy: 'day' | 'month' = 'month'): AnalyticsQuery {
  return {
    dateFrom: dateYearsAgo(1),
    dateTo: today(),
    groupBy,
  }
}

export function isAnalyticsEmpty(data: AnalyticsReportData | null): boolean {
  if (!data) return true

  const hasSummary = (data.summary?.values?.length ?? 0) > 0
  const hasTable = (data.table?.length ?? 0) > 0
  const hasTimeseries = (data.timeseries?.series?.some((item) => item.data.some((value) => value > 0)) ?? false)

  return !hasSummary && !hasTable && !hasTimeseries
}

export function formatDuration(seconds: number): string {
  const total = Math.max(0, Math.round(seconds))
  const minutes = Math.floor(total / 60)
  const secs = total % 60
  return `${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`
}

export async function fetchAnalyticsReport(
  siteId: string,
  type: AnalyticsReportType,
  query: AnalyticsQuery,
  refresh = false,
): Promise<AnalyticsReportData> {
  const params = new URLSearchParams({
    date_from: query.dateFrom,
    date_to: query.dateTo,
    group_by: query.groupBy,
  })

  if (refresh) {
    params.set('refresh', '1')
  }

  const res = await api<{ data: AnalyticsReportData }>(
    `/projects/${siteId}/analytics/${type}?${params.toString()}`,
  )

  return res.data
}
