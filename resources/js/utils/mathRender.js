import katex from 'katex'

/**
 * Render math expressions embedded in text strings while strictly
 * sanitizing HTML to prevent Stored Cross-Site Scripting (XSS).
 * Supports:
 * - $$ block math $$
 * - $ inline math $
 * - Unwrapped LaTeX syntax (e.g. \frac{...}{...}, \sqrt{...}, \int, e^{...}, etc.)
 */
const escapeHtml = (str) => {
  if (!str || typeof str !== 'string') return ''
  return str
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;')
}

export function renderMath(rawText) {
  if (!rawText || typeof rawText !== 'string') return ''

  // 1. If text contains explicit $...$ or $$...$$
  if (rawText.includes('$')) {
    const mathTokens = []

    // Extract block math $$ ... $$
    let text = rawText.replace(/\$\$([\s\S]+?)\$\$/g, (match, formula) => {
      try {
        const rendered = katex.renderToString(formula.trim(), { displayMode: true, throwOnError: false })
        const token = `###MATH_BLOCK_${mathTokens.length}###`
        mathTokens.push(rendered)
        return token
      } catch (e) {
        return match
      }
    })

    // Extract inline math $ ... $
    text = text.replace(/\$([^\$\n]+?)\$/g, (match, formula) => {
      try {
        const rendered = katex.renderToString(formula.trim(), { displayMode: false, throwOnError: false })
        const token = `###MATH_INLINE_${mathTokens.length}###`
        mathTokens.push(rendered)
        return token
      } catch (e) {
        return match
      }
    })

    // Sanitize non-formula text to prevent XSS
    let safeText = escapeHtml(text)

    // Restore rendered KaTeX HTML
    mathTokens.forEach((rendered, i) => {
      safeText = safeText.replace(new RegExp(`###MATH_(?:BLOCK|INLINE)_${i}###`, 'g'), rendered)
    })

    return safeText
  }

  // 2. Standalone LaTeX formula without $ (like in option inputs)
  if (/\\(?:frac|sqrt|int|sum|prod|pi|alpha|beta|gamma|theta|lambda|sigma|partial|infty|approx|times|div|pm|ne|le|ge|in|subset|forall|exists|mathbb|mathbf|mathcal|sin|cos|tan|cot|ln|log)/.test(rawText) || /\^[0-9a-zA-Z\{\(]|_[0-9a-zA-Z\{\(]/.test(rawText)) {
    const hasKhmer = /[\u1780-\u17FF]/.test(rawText)
    if (!hasKhmer) {
      try {
        return katex.renderToString(rawText.trim(), { displayMode: false, throwOnError: false })
      } catch (e) {
        // fallback to token replacement
      }
    }

    const mathTokens = []
    let text = rawText.replace(/(\\(?:frac\{[^{}]+\}\{[^{}]+\}|sqrt(?:\[[^{}]+\])?\{[^{}]+\}|int|sin|cos|tan|cot|ln|log|pi|in|mathbb\{[A-Z]\}|times|div|ne|forall|exists|approx|pm|[a-zA-Z0-9\(\)\+\-\=\/\^\_\s\.\,\{\}\[\]\\]+)+)/g, (match) => {
      const trimmed = match.trim()
      if (trimmed && /\\|\^|\_/.test(trimmed)) {
        try {
          const rendered = katex.renderToString(trimmed, { displayMode: false, throwOnError: false })
          const token = `###MATH_AUTO_${mathTokens.length}###`
          mathTokens.push(rendered)
          return token
        } catch (e) {
          return match
        }
      }
      return match
    })

    let safeText = escapeHtml(text)
    mathTokens.forEach((rendered, i) => {
      safeText = safeText.replace(new RegExp(`###MATH_AUTO_${i}###`, 'g'), rendered)
    })
    return safeText
  }

  // Plain text without math: safely HTML-escape
  return escapeHtml(rawText)
}
