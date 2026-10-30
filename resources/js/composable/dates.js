// Philippine calendar days as "YYYY-MM-DD" (UTC+8 all year, no daylight saving)

export function manilaToday() {
  return new Date(Date.now() + 8 * 3600e3).toISOString().slice(0, 10)
}

export function addDaysTo(date, n) {
  const d = new Date(`${date}T00:00:00Z`)
  d.setUTCDate(d.getUTCDate() + n)
  return d.toISOString().slice(0, 10)
}
