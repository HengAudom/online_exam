import axios from 'axios'

/**
 * Handle user logout.
 * Clears local state, calls API, and redirects to login.
 */
export async function logout() {
  try {
    // Attempt to notify server
    await axios.post('/api/logout')
  } catch (e) {
    // If session already expired, just move on
    console.warn('Logout API failed (likely session already expired)')
  } finally {
    // Clear local authentication flag
    localStorage.removeItem('isAuthenticated')
    
    // Replace current state so user cannot go back
    window.history.replaceState(null, '', '/login')
    
    // Full page reload to clear all Vue states and redirect
    window.location.replace('/login')
  }
}
