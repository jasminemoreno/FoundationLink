// Google Maps links (free, no API key). On phones they open the Google Maps app.

// Shows one spot on the map
export function mapsPointUrl(lat, lng) {
  return `https://www.google.com/maps/search/?api=1&query=${lat},${lng}`
}

// A readable address for a GPS spot ("Rizal St, Poblacion, Surigao City, Surigao del Norte"),
// from OpenStreetMap's free lookup. Returns '' when it can't tell — the typed address still works.
export async function addressFromPoint(lat, lng) {
  try {
    const res = await fetch(
      `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`,
      { headers: { 'Accept-Language': 'en' } }
    )
    if (!res.ok) return ''
    const a = (await res.json())?.address || {}
    const parts = [
      [a.house_number, a.road].filter(Boolean).join(' '),
      a.quarter || a.neighbourhood || a.suburb || a.village || a.hamlet,
      a.city || a.town || a.municipality || a.county,
      a.state || a.province,
    ].filter(Boolean)
    return [...new Set(parts)].join(', ')
  } catch {
    return ''
  }
}

// Directions from wherever the user is to the exact spot, or else to the typed address
export function mapsDirectionsUrl({ lat, lng, address } = {}) {
  const hasPoint = lat !== null && lat !== undefined && lng !== null && lng !== undefined
  const destination = hasPoint ? `${lat},${lng}` : (address || '').trim()
  return destination
    ? `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(destination)}`
    : ''
}
