const relativeTimeFormatter = new Intl.RelativeTimeFormat('fr-FR', { numeric: 'auto' })
const dateTimeFormatter = new Intl.DateTimeFormat('fr-FR', { dateStyle: 'short', timeStyle: 'medium' })

export function formatRelativeTime(isoDate: string, now: Date = new Date()): string {
  const elapsedMinutes = Math.round((new Date(isoDate).getTime() - now.getTime()) / 60_000)

  if (Math.abs(elapsedMinutes) < 60) {
    return relativeTimeFormatter.format(elapsedMinutes, 'minute')
  }

  const elapsedHours = Math.round(elapsedMinutes / 60)
  if (Math.abs(elapsedHours) < 24) {
    return relativeTimeFormatter.format(elapsedHours, 'hour')
  }

  return relativeTimeFormatter.format(Math.round(elapsedHours / 24), 'day')
}

export function formatDateTime(isoDate: string): string {
  return dateTimeFormatter.format(new Date(isoDate))
}

export function initials(name: string): string {
  return name
    .split(/[\s._-]+/)
    .filter((namePart) => namePart !== '')
    .slice(0, 2)
    .map((namePart) => namePart.charAt(0).toUpperCase())
    .join('')
}
