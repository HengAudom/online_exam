import katex from 'katex'

/**
 * Render math expressions embedded in text strings.
 * Supports:
 * - $$ block math $$
 * - $ inline math $
 * - Unwrapped LaTeX syntax (e.g. \frac{...}{...}, \sqrt{...}, \int, e^{...}, etc.)
 */
export function renderMath(rawText) {
  if (!rawText || typeof rawText !== 'string') return ''

  let text = rawText

  // 1. If text contains explicit $...$ or $$...$$
  if (text.includes('$')) {
    // Block math $$ ... $$
    text = text.replace(/\$\$([\s\S]+?)\$\$/g, (match, formula) => {
      try {
        return katex.renderToString(formula.trim(), { displayMode: true, throwOnError: false })
      } catch (e) {
        return match
      }
    })

    // Inline math $ ... $
    text = text.replace(/\$([^\$\n]+?)\$/g, (match, formula) => {
      try {
        return katex.renderToString(formula.trim(), { displayMode: false, throwOnError: false })
      } catch (e) {
        return match
      }
    })

    return text
  }

  // 2. If entire string is a standalone LaTeX formula without $ (like in option inputs)
  if (/\\(?:frac|sqrt|int|sum|prod|pi|alpha|beta|gamma|theta|lambda|sigma|partial|infty|approx|times|div|pm|ne|le|ge|in|subset|forall|exists|mathbb|mathbf|mathcal|sin|cos|tan|cot|ln|log)/.test(text) || /\^[0-9a-zA-Z\{\(]|_[0-9a-zA-Z\{\(]/.test(text)) {
    // Check if it's purely a formula or contains Khmer text
    const hasKhmer = /[\u1780-\u17FF]/.test(text)
    if (!hasKhmer) {
      try {
        return katex.renderToString(text.trim(), { displayMode: false, throwOnError: false })
      } catch (e) {
        // fallback to token replacement
      }
    }

    // If mixed with Khmer text, find and replace LaTeX segments
    text = text.replace(/(\\(?:frac\{[^{}]+\}\{[^{}]+\}|sqrt(?:\[[^{}]+\])?\{[^{}]+\}|int|sin|cos|tan|cot|ln|log|pi|in|mathbb\{[A-Z]\}|times|div|ne|forall|exists|approx|pm|[a-zA-Z0-9\(\)\+\-\=\/\^\_\s\.\,\{\}\[\]\\]+)+)/g, (match) => {
      const trimmed = match.trim()
      if (trimmed && /\\|\^|\_/.test(trimmed)) {
        try {
          return katex.renderToString(trimmed, { displayMode: false, throwOnError: false })
        } catch (e) {
          return match
        }
      }
      return match
    })
  }

  return text
}
