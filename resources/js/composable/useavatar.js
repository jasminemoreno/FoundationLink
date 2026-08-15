// src/composable/useAvatar.js
//
// Pulls out the getInitials()/avatarColor() logic that was copy-pasted
// across the Foundations and Users pages (and re-implemented slightly
// differently in Dashboard/Reports) into one shared source of truth.

const PALETTE = ['#3b82f6', '#059669', '#ca8a04', '#7c3aed', '#dc2626', '#0891b2', '#db2777']

export function getInitials(name, max = 2) {
  if (!name) return '?'
  return name
    .split(' ')
    .filter(Boolean)
    .map(w => w[0])
    .join('')
    .toUpperCase()
    .slice(0, max)
}

export function avatarColor(seed) {
  if (seed === null || seed === undefined || seed === '') return PALETTE[0]
  const num = typeof seed === 'number'
    ? seed
    : String(seed).split('').reduce((sum, char) => sum + char.charCodeAt(0), 0)
  return PALETTE[num % PALETTE.length]
}

export function useAvatar() {
  return { getInitials, avatarColor }
}