// "From / To" dates for the report pages. Dates are 'YYYY-MM-DD' in the viewer's
// local time; an empty date means open-ended, and both empty means all time.

const pad = (n) => String(n).padStart(2, '0')

export const ymd = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`

export const todayYmd = () => ymd(new Date())

export const RANGE_PRESETS = [
  { key: 'all',    label: 'All time' },
  { key: 'month',  label: 'This month' },
  { key: '30days', label: 'Last 30 days' },
  { key: 'year',   label: 'This year' },
]

export function presetRange(key) {
  const today = new Date()
  const to = ymd(today)
  switch (key) {
    case 'month':
      return { from: ymd(new Date(today.getFullYear(), today.getMonth(), 1)), to }
    case '30days': {
      const start = new Date(today)
      start.setDate(start.getDate() - 29) // today counts as one of the 30 days
      return { from: ymd(start), to }
    }
    case 'year':
      return { from: `${today.getFullYear()}-01-01`, to }
    default:
      return { from: '', to: '' }
  }
}

export const isAllTime = (range) => !range?.from && !range?.to

// e.g. "Nov 1, 2026 – Nov 15, 2026"
export function rangeLabel(range) {
  const day = (s) => new Date(`${s}T00:00:00`).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
  const { from, to } = range || {}
  if (from && to) return from === to ? day(from) : `${day(from)} – ${day(to)}`
  if (from) return `Since ${day(from)}`
  if (to) return `Until ${day(to)}`
  return 'All time'
}

// Query parameters for the report APIs (left out when empty)
export function rangeParams(range) {
  const params = {}
  if (range?.from) params.from = range.from
  if (range?.to) params.to = range.to
  return params
}

// For PDF file names, e.g. "2026-11-01_to_2026-11-15" or "all-time"
export function rangeFileTag(range) {
  if (isAllTime(range)) return 'all-time'
  return [range.from || 'start', range.to || todayYmd()].join('_to_')
}
