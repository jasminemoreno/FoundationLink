// Shared donor-side helpers.
// Import these instead of redefining getImage/formatMoney/formatDate in every page:
//   import { getImage, formatMoney, formatDate } from '@/utils/donor'

const STORAGE_BASE = '/storage/'

export function getImage(path) {
  if (!path) return ''
  if (path.startsWith('http://') || path.startsWith('https://')) return path
  const clean = path.startsWith('/') ? path.slice(1) : path
  return STORAGE_BASE + clean
}

export function formatMoney(v) {
  return Number(v || 0).toLocaleString()
}

export function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' })
}

export function formatDateTime(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-PH', {
    year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
  })
}