function showCopiedState(button) {
    const defaultLabel = button.dataset.copyLabel || 'Скопіювати повний ID ліда'
    const copiedLabel = button.dataset.copiedLabel || 'Скопійовано'

    if (button._copyResetHandle) {
        window.clearTimeout(button._copyResetHandle)
    }

    button.dataset.copyState = 'copied'
    button.title = copiedLabel
    button.setAttribute('aria-label', copiedLabel)
    button.classList.add('border-teal-200', 'bg-teal-50', 'text-teal-700')

    button._copyResetHandle = window.setTimeout(() => {
        button.dataset.copyState = 'idle'
        button.title = defaultLabel
        button.setAttribute('aria-label', defaultLabel)
        button.classList.remove('border-teal-200', 'bg-teal-50', 'text-teal-700')
    }, 1500)
}

export default function mountAdminLeadList() {
    const roots = document.querySelectorAll('[data-admin-leads-list]')

    roots.forEach((root) => {
        if (root.dataset.copyMounted === 'true') {
            return
        }

        root.dataset.copyMounted = 'true'

        root.addEventListener('click', async (event) => {
            const button = event.target.closest('[data-copy-lead-id-button]')

            if (!button || !root.contains(button)) {
                return
            }

            const value = button.dataset.copyValue

            if (!value || !navigator.clipboard?.writeText) {
                return
            }

            event.preventDefault()

            try {
                await navigator.clipboard.writeText(value)
                showCopiedState(button)
            } catch {
                // Ignore clipboard failures and leave the UI unchanged.
            }
        })
    })
}
