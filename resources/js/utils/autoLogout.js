import { logout } from './auth'

/**
 * setupAutoLogout.js
 * Tracks user activity and triggers logout callback when inactive.
 * 
 * @param {object} router - Vue Router instance to check current route name
 */
export function setupAutoLogout(router) {
    let timeoutId = null
    const INACTIVITY_TIMEOUT = 30 * 60 * 1000 // 30 minutes
    const events = ['mousemove', 'mousedown', 'keypress', 'scroll', 'touchstart']

    const resetTimer = () => {
        // Clear any previous timer
        if (timeoutId) clearTimeout(timeoutId)
        
        // Skip if user is not authenticated
        if (!localStorage.getItem('isAuthenticated')) return

        // Skip auto-logout if user is in ExamRoom or viewing Results
        const currentRoute = router.currentRoute.value
        const routeName = currentRoute?.name
        const routePath = currentRoute?.path

        if (
            routeName === 'Exam' || 
            routeName === 'ExamResults' || 
            routePath?.includes('/student/exam/') || 
            routePath?.includes('/student/results/')
        ) {
            // console.log('Auto-logout suppressed (Exam/Results page)')
            return
        }

        // Set a new timer
        timeoutId = setTimeout(() => {
            console.log('Inactivity detected (30m). Automatic logout triggered.')
            logout()
        }, INACTIVITY_TIMEOUT)
    }

    // Set up window listeners
    const init = () => {
        events.forEach(eventName => {
            window.addEventListener(eventName, resetTimer)
        })
        // Immediately start timer if already authenticated (e.g. page reload or navigation)
        resetTimer()
    }

    const cleanup = () => {
        if (timeoutId) clearTimeout(timeoutId)
        events.forEach(eventName => {
            window.removeEventListener(eventName, resetTimer)
        })
    }

    init()

    // Return both cleanup and reset functions
    return { cleanup, resetTimer }
}
