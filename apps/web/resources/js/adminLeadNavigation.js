export const LEAD_DETAILS_BACK_URL_STORAGE_KEY = 'backoffice.leadDetailsBackUrl'

export function sanitizeBackofficeAdminUrl(candidate, origin = globalThis.window?.location?.origin ?? null) {
    if (typeof candidate !== 'string') {
        return null
    }

    const normalizedCandidate = candidate.trim()

    if (normalizedCandidate === '') {
        return null
    }

    try {
        const url = origin ? new URL(normalizedCandidate, origin) : new URL(normalizedCandidate)

        if (origin && url.origin !== origin) {
            return null
        }

        if (!url.pathname.startsWith('/admin')) {
            return null
        }

        return url.toString()
    } catch {
        return null
    }
}

export function rememberLeadDetailsBackUrl(storage = globalThis.window?.sessionStorage, currentHref = globalThis.window?.location?.href, origin = globalThis.window?.location?.origin ?? null) {
    if (!storage?.setItem) {
        return
    }

    const sanitizedUrl = sanitizeBackofficeAdminUrl(currentHref, origin)

    if (sanitizedUrl === null) {
        return
    }

    storage.setItem(LEAD_DETAILS_BACK_URL_STORAGE_KEY, sanitizedUrl)
}

export function resolveLeadDetailsBackUrl(storage = globalThis.window?.sessionStorage, fallback = null, origin = globalThis.window?.location?.origin ?? null) {
    if (!storage?.getItem) {
        return fallback
    }

    const storedUrl = storage.getItem(LEAD_DETAILS_BACK_URL_STORAGE_KEY)

    return sanitizeBackofficeAdminUrl(storedUrl, origin) ?? fallback
}

function isPlainLeftClick(event) {
    return event.button === 0
        && !event.metaKey
        && !event.ctrlKey
        && !event.shiftKey
        && !event.altKey
}

export default function mountAdminLeadNavigation() {
    const doc = globalThis.document
    const win = globalThis.window

    if (!doc || !win) {
        return
    }

    doc.addEventListener('click', (event) => {
        const link = event.target?.closest?.('[data-lead-details-source-link]')

        if (!link) {
            return
        }

        if (!isPlainLeftClick(event)) {
            return
        }

        if (link.target === '_blank' || link.hasAttribute?.('download')) {
            return
        }

        rememberLeadDetailsBackUrl(win.sessionStorage, win.location?.href, win.location?.origin ?? null)
    })

    const backButton = doc.querySelector('[data-lead-details-back-button]')

    if (!backButton?.getAttribute || !backButton?.setAttribute) {
        return
    }

    const fallbackUrl = backButton.getAttribute('href')
    const resolvedBackUrl = resolveLeadDetailsBackUrl(win.sessionStorage, fallbackUrl, win.location?.origin ?? null)

    if (resolvedBackUrl !== null) {
        backButton.setAttribute('href', resolvedBackUrl)
    }
}
