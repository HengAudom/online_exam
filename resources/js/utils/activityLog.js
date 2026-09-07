/**
 * activityLog.js
 * Stores recent admin activities in localStorage
 * so the Dashboard can display them in "Recent Activity"
 */

const STORAGE_KEY = 'admin_activity_log'
const MAX_ITEMS   = 10

/**
 * Push a new activity entry.
 * @param {string} title       - e.g. "New student added: John Doe"
 * @param {string} description - e.g. "Role: Student · Computer services"
 */
export function logActivity(title, description = '') {
  const existing = getActivities()
  const entry = {
    title,
    description,
    time: new Date().toISOString(),
  }
  // Newest first, keep MAX_ITEMS
  const updated = [entry, ...existing].slice(0, MAX_ITEMS)
  localStorage.setItem(STORAGE_KEY, JSON.stringify(updated))
}

/**
 * Get all stored activities (newest first).
 * Returns array of { title, description, time }
 */
export function getActivities() {
  try {
    return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]')
  } catch {
    return []
  }
}

/**
 * Format ISO time to readable "Apr 03, 2026 · 23:45"
 */
export function formatTime(iso) {
  if (!iso) return ''
  const d = new Date(iso)
  return d.toLocaleString('en-US', {
    month: 'short', day: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}
