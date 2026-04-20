const landingBootstrapState = {
    promise: null,
    clickTracked: false,
    phoneLeadPromise: null,
    touchPromises: new Map(),
    entryAnalyticsContext: {
        clickId: null,
        revisitId: null,
    },
    touchAnalyticsContext: {
        touchId: null,
        type: null,
    },
    leadAnalyticsContext: {
        leadId: null,
        origin: null,
    },
    analyticsContext: {
        visitorId: null,
        visitId: null,
    },
};

const landingBootstrapReloadStorageKey = 'landing-capture-soft-reload-attempt-at';
const landingBootstrapReloadCooldownMs = 15_000;

function readLandingBootstrapReloadAttemptAt() {
    try {
        const rawValue = window.sessionStorage?.getItem(landingBootstrapReloadStorageKey);

        if (rawValue === null || rawValue === undefined) {
            return null;
        }

        const timestamp = Number(rawValue);

        return Number.isFinite(timestamp) ? timestamp : null;
    } catch {
        return null;
    }
}

function writeLandingBootstrapReloadAttemptAt(timestamp) {
    try {
        window.sessionStorage?.setItem(landingBootstrapReloadStorageKey, String(timestamp));
    } catch {
        //
    }
}

function clearLandingBootstrapReloadAttemptAt() {
    try {
        window.sessionStorage?.removeItem(landingBootstrapReloadStorageKey);
    } catch {
        //
    }
}

function isSuccessfulBootstrapResponse(response) {
    if (typeof response?.ok === 'boolean') {
        return response.ok;
    }

    if (typeof response?.status !== 'number') {
        return true;
    }

    return response.status >= 200 && response.status < 300;
}

function responseStatus(response) {
    return typeof response?.status === 'number' ? response.status : null;
}

function isNonEmptyString(value) {
    return typeof value === 'string' && value !== '';
}

function ensureLandingDataLayer() {
    if (typeof window !== 'object' || window === null) {
        return null;
    }

    if (!Array.isArray(window.dataLayer)) {
        window.dataLayer = [];
    }

    return window.dataLayer;
}

function currentLandingAnalyticsContext() {
    const payload = {};

    if (isNonEmptyString(landingBootstrapState.analyticsContext.visitorId)) {
        payload.visitor_uuid = landingBootstrapState.analyticsContext.visitorId;
    }

    if (isNonEmptyString(landingBootstrapState.analyticsContext.visitId)) {
        payload.visit_uuid = landingBootstrapState.analyticsContext.visitId;
    }

    return payload;
}

function rememberLandingAnalyticsContext(record = {}) {
    if (typeof record !== 'object' || record === null) {
        return;
    }

    if (isNonEmptyString(record.visitorId)) {
        landingBootstrapState.analyticsContext.visitorId = record.visitorId;
    }

    if (isNonEmptyString(record.visitId)) {
        landingBootstrapState.analyticsContext.visitId = record.visitId;
    }
}

function rememberLandingEntryAnalyticsContext(record = {}, resultType = null) {
    if (typeof record !== 'object' || record === null) {
        return;
    }

    const entryId = isNonEmptyString(record.resultId)
        ? record.resultId
        : resultType === 'click' && isNonEmptyString(record.clickId)
            ? record.clickId
            : resultType === 'revisit' && isNonEmptyString(record.revisitId)
                ? record.revisitId
                : null;

    if (resultType === 'revisit') {
        landingBootstrapState.entryAnalyticsContext.clickId = null;

        if (entryId !== null) {
            landingBootstrapState.entryAnalyticsContext.revisitId = entryId;
        }

        return;
    }

    if (resultType === 'click') {
        landingBootstrapState.entryAnalyticsContext.revisitId = null;

        if (entryId !== null) {
            landingBootstrapState.entryAnalyticsContext.clickId = entryId;
        }
    }
}

function rememberLandingTouchAnalyticsContext(record = {}) {
    if (typeof record !== 'object' || record === null) {
        return;
    }

    const touchId = isNonEmptyString(record.resultId)
        ? record.resultId
        : isNonEmptyString(record.touchId)
            ? record.touchId
            : null;

    if (touchId !== null) {
        landingBootstrapState.touchAnalyticsContext.touchId = touchId;
    }

    if (isNonEmptyString(record.type)) {
        landingBootstrapState.touchAnalyticsContext.type = record.type;
    }
}

function rememberLandingLeadAnalyticsContext(record = {}) {
    if (typeof record !== 'object' || record === null) {
        return;
    }

    const leadId = isNonEmptyString(record.resultId)
        ? record.resultId
        : isNonEmptyString(record.leadId)
            ? record.leadId
            : null;

    if (leadId !== null) {
        landingBootstrapState.leadAnalyticsContext.leadId = leadId;
    }

    if (isNonEmptyString(record.origin)) {
        landingBootstrapState.leadAnalyticsContext.origin = record.origin;
    }
}

function hasLandingAnalyticsRecordIdentifiers(record = {}) {
    return isNonEmptyString(record.visitorId)
        || isNonEmptyString(record.visitId)
        || isNonEmptyString(record.touchId)
        || isNonEmptyString(record.leadId)
        || isNonEmptyString(record.resultId);
}

function buildLandingAnalyticsEventPayload(eventName) {
    const context = currentLandingAnalyticsContext();
    const payload = {
        visitor_uuid: context.visitor_uuid ?? null,
        visit_uuid: context.visit_uuid ?? null,
        click_uuid: null,
        revisit_uuid: null,
        touch_uuid: null,
        touch_type: null,
        lead_uuid: null,
        lead_origin: null,
    };

    if (eventName === 'landing_click_registered') {
        payload.click_uuid = isNonEmptyString(landingBootstrapState.entryAnalyticsContext.clickId)
            ? landingBootstrapState.entryAnalyticsContext.clickId
            : null;
    }

    if (eventName === 'landing_revisit_registered') {
        payload.revisit_uuid = isNonEmptyString(landingBootstrapState.entryAnalyticsContext.revisitId)
            ? landingBootstrapState.entryAnalyticsContext.revisitId
            : null;
    }

    if (eventName === 'landing_touch_registered') {
        payload.touch_uuid = isNonEmptyString(landingBootstrapState.touchAnalyticsContext.touchId)
            ? landingBootstrapState.touchAnalyticsContext.touchId
            : null;
        payload.touch_type = isNonEmptyString(landingBootstrapState.touchAnalyticsContext.type)
            ? landingBootstrapState.touchAnalyticsContext.type
            : null;
    }

    if (eventName === 'lead_created' || eventName === 'lead_form_submit' || eventName === 'lead_phone_click') {
        payload.lead_uuid = isNonEmptyString(landingBootstrapState.leadAnalyticsContext.leadId)
            ? landingBootstrapState.leadAnalyticsContext.leadId
            : null;
        payload.lead_origin = isNonEmptyString(landingBootstrapState.leadAnalyticsContext.origin)
            ? landingBootstrapState.leadAnalyticsContext.origin
            : null;
    }

    return payload;
}

function pushLandingAnalyticsEvent(eventName) {
    if (!isNonEmptyString(eventName)) {
        return;
    }

    const dataLayer = ensureLandingDataLayer();

    if (dataLayer === null) {
        return;
    }

    dataLayer.push({
        event: eventName,
        ...buildLandingAnalyticsEventPayload(eventName),
    });
}

async function readResponseJson(response) {
    if (typeof response?.json !== 'function') {
        return {};
    }

    return await response.json().catch(() => ({}));
}

function responseRecord(responseJson = {}) {
    if (typeof responseJson !== 'object' || responseJson === null) {
        return {};
    }

    if (typeof responseJson.data !== 'object' || responseJson.data === null) {
        return {};
    }

    return responseJson.data;
}

function landingAnalyticsResultType(record = {}, fallbackResultType = null) {
    if (isNonEmptyString(record.resultType)) {
        return record.resultType;
    }

    return fallbackResultType;
}

function landingAnalyticsLeadEventName(record = {}, fallbackEventName = 'lead_created') {
    if (typeof record !== 'object' || record === null) {
        return fallbackEventName;
    }

    if (record.origin === 'form') {
        return 'lead_form_submit';
    }

    if (record.origin === 'phone_click') {
        return 'lead_phone_click';
    }

    return fallbackEventName;
}

function pushLandingAnalyticsEventForResultType(record = {}, resultType = null) {
    if (!hasLandingAnalyticsRecordIdentifiers(record)) {
        return false;
    }

    if (resultType === 'click') {
        rememberLandingAnalyticsContext(record);
        rememberLandingEntryAnalyticsContext(record, resultType);
        pushLandingAnalyticsEvent('landing_click_registered');

        return true;
    }

    if (resultType === 'revisit') {
        rememberLandingAnalyticsContext(record);
        rememberLandingEntryAnalyticsContext(record, resultType);
        pushLandingAnalyticsEvent('landing_revisit_registered');

        return true;
    }

    if (resultType === 'lead') {
        rememberLandingAnalyticsContext(record);
        rememberLandingLeadAnalyticsContext(record);
        pushLandingAnalyticsEvent(landingAnalyticsLeadEventName(record));

        return true;
    }

    if (resultType === 'touch') {
        rememberLandingAnalyticsContext(record);
        rememberLandingTouchAnalyticsContext(record);
        pushLandingAnalyticsEvent('landing_touch_registered');

        return true;
    }

    return false;
}

export function resetLandingCaptureBootstrapState() {
    landingBootstrapState.promise = null;
    landingBootstrapState.clickTracked = false;
    landingBootstrapState.phoneLeadPromise = null;
    landingBootstrapState.touchPromises = new Map();
    landingBootstrapState.entryAnalyticsContext = {
        clickId: null,
        revisitId: null,
    };
    landingBootstrapState.touchAnalyticsContext = {
        touchId: null,
        type: null,
    };
    landingBootstrapState.leadAnalyticsContext = {
        leadId: null,
        origin: null,
    };
    landingBootstrapState.analyticsContext = {
        visitorId: null,
        visitId: null,
    };
    clearLandingBootstrapReloadAttemptAt();
}

export default function landingCapture() {
    return {
        config: {},
        isBootstrapping: false,
        bootPromise: null,
        isSubmittingLeadForm: false,
        leadFormState: 'idle',
        leadFormMessage: '',
        leadFormFieldErrors: {},

        init() {
            this.config = this.readConfig();
            this.normalizeInitialLeadPhoneField();
            this.isBootstrapping = true;

            if (landingBootstrapState.promise) {
                this.bootPromise = landingBootstrapState.promise;
                this.bootPromise.finally(() => {
                    this.isBootstrapping = false;
                });

                return this.bootPromise;
            }

            landingBootstrapState.promise = (async () => {
                this.stripAttributionQuery();

                try {
                    await this.trackLandingClick();
                } finally {
                }
            })();
            this.bootPromise = landingBootstrapState.promise;

            this.bootPromise.finally(() => {
                this.isBootstrapping = false;
            });

            return this.bootPromise;
        },

        hasRecentSoftReloadAttempt() {
            const attemptedAt = readLandingBootstrapReloadAttemptAt();

            if (attemptedAt === null) {
                return false;
            }

            if (Date.now() - attemptedAt > landingBootstrapReloadCooldownMs) {
                clearLandingBootstrapReloadAttemptAt();

                return false;
            }

            return true;
        },

        markSoftReloadAttempt() {
            writeLandingBootstrapReloadAttemptAt(Date.now());
        },

        clearSoftReloadAttempt() {
            clearLandingBootstrapReloadAttemptAt();
        },

        ready() {
            return landingBootstrapState.promise ?? Promise.resolve();
        },

        async submitLeadForm(event) {
            event.preventDefault();

            await this.ready();

            if (typeof this.config.leadFormUrl !== 'string' || this.config.leadFormUrl === '') {
                return;
            }

            const form = event.currentTarget;

            if (typeof form !== 'object' || form === null) {
                return;
            }

            this.isSubmittingLeadForm = true;
            this.leadFormState = 'loading';
            this.leadFormMessage = '';
            this.leadFormFieldErrors = {};

            const formData = new FormData(form);
            const localPhone = this.normalizeLeadPhoneLocalPart(formData.get('phone'));
            this.syncPhoneField(form, this.formatLeadPhoneLocalPart(localPhone));
            const payload = {
                name: formData.get('name'),
                phone: this.buildLeadPhone(localPhone),
            };

            if (localPhone === '') {
                this.leadFormState = 'validation-error';
                this.leadFormMessage = this.config.formValidationMessage ?? '';
                this.leadFormFieldErrors = {
                    phone: [this.leadPhoneRequiredMessage()],
                };
                this.isSubmittingLeadForm = false;
                return;
            }

            if (!this.isLeadPhoneLocalPartValid(localPhone)) {
                this.leadFormState = 'validation-error';
                this.leadFormMessage = this.config.formValidationMessage ?? '';
                this.leadFormFieldErrors = {
                    phone: [this.leadPhoneFormatMessage()],
                };
                this.isSubmittingLeadForm = false;
                return;
            }

            try {
                const response = await fetch(this.config.leadFormUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload),
                });

                const responseJson = await readResponseJson(response);
                const record = responseRecord(responseJson);
                const resultType = landingAnalyticsResultType(record, 'lead');

                if (response.status === 201 && responseJson?.ok) {
                    pushLandingAnalyticsEventForResultType(record, resultType);

                    this.leadFormState = 'success';
                    this.leadFormMessage = this.config.formSuccessMessage ?? '';
                    form.reset();
                    return;
                }

                if (response.status === 422) {
                    this.leadFormState = 'validation-error';
                    this.leadFormMessage = this.config.formValidationMessage ?? '';
                    this.leadFormFieldErrors = this.translateLeadFormErrors(responseJson?.errors ?? {});
                    return;
                }

                if (response.status === 409 && responseJson?.code === 'current_visit_not_found') {
                    this.leadFormState = 'server-error';
                    this.leadFormMessage = this.config.formConflictMessage ?? 'Не вдалося зберегти заявку без поточного візиту.';
                    return;
                }

                this.leadFormState = 'server-error';
                this.leadFormMessage = this.config.formFailureMessage ?? '';
            } catch {
                this.leadFormState = 'server-error';
                this.leadFormMessage = this.config.formFailureMessage ?? '';
            } finally {
                this.isSubmittingLeadForm = false;
            }
        },

        normalizeLeadPhoneField(event) {
            const field = event.currentTarget;

            if (typeof field !== 'object' || field === null || !('value' in field)) {
                return;
            }

            field.value = this.formatLeadPhoneLocalPart(this.normalizeLeadPhoneLocalPart(field.value));
        },

        normalizeInitialLeadPhoneField() {
            const field = document.getElementById('phone');

            if (typeof field !== 'object' || field === null || !('value' in field)) {
                return;
            }

            field.value = this.formatLeadPhoneLocalPart(this.normalizeLeadPhoneLocalPart(field.value));
        },

        normalizeLeadPhoneLocalPart(value) {
            if (typeof value !== 'string') {
                return '';
            }

            let digits = value.replace(/\D+/g, '');

            if (digits.startsWith('380')) {
                digits = digits.slice(3, 12);
            } else if (digits.startsWith('0')) {
                digits = digits.slice(1, 10);
            }

            return digits;
        },

        formatLeadPhoneLocalPart(value) {
            if (typeof value !== 'string' || value === '') {
                return '';
            }

            const digits = value.replace(/\D+/g, '');
            const parts = [];
            let offset = 0;

            for (const size of [2, 3, 2, 2]) {
                if (digits.length <= offset) {
                    break;
                }

                parts.push(digits.slice(offset, offset + size));
                offset += size;
            }

            if (digits.length > offset) {
                parts.push(digits.slice(offset));
            }

            return parts.filter((part) => part !== '').join(' ');
        },

        buildLeadPhone(localPhone) {
            return `${this.leadPhoneCountryCode()}${localPhone}`;
        },

        leadPhoneCountryCode() {
            return typeof this.config.leadPhoneCountryCode === 'string' && this.config.leadPhoneCountryCode !== ''
                ? this.config.leadPhoneCountryCode
                : '+380';
        },

        leadPhoneRequiredMessage() {
            return this.config.leadPhoneRequiredMessage ?? 'Вкажіть номер телефону.';
        },

        leadPhoneFormatMessage() {
            return this.config.leadPhoneFormatMessage ?? 'Введіть 9 цифр після +380, наприклад 50 111 22 33.';
        },

        isLeadPhoneLocalPartValid(value) {
            return typeof value === 'string' && /^\d{9}$/.test(value);
        },

        syncPhoneField(form, value) {
            if (!('elements' in form) || typeof form.elements !== 'object' || form.elements === null) {
                return;
            }

            const phoneField = form.elements.namedItem?.('phone') ?? form.elements.phone;

            if (typeof phoneField !== 'object' || phoneField === null || !('value' in phoneField)) {
                return;
            }

            phoneField.value = value;
        },

        translateLeadFormErrors(errors) {
            const translated = {};

            for (const [field, messages] of Object.entries(errors)) {
                translated[field] = (Array.isArray(messages) ? messages : [])
                    .map((message) => this.translateValidationMessage(message))
                    .filter((message) => typeof message === 'string' && message !== '');
            }

            return translated;
        },

        translateValidationMessage(message) {
            const translations = {
                'The phone field is required.': this.leadPhoneRequiredMessage(),
                'The phone field format is invalid.': this.leadPhoneFormatMessage(),
                'The name field must be a string.': 'Ім’я має бути рядком.',
                'The name field may not be greater than 255 characters.': 'Ім’я не може перевищувати 255 символів.',
                'The given data was invalid.': 'Перевірте правильність заповнення форми.',
            };

            return translations[message] ?? message;
        },

        async navigateAfterReady(href) {
            await this.ready();

            if (typeof href !== 'string' || href === '') {
                return;
            }

            if (href.startsWith('#')) {
                this.navigateToHash(href);
                return;
            }

            window.location.href = href;
        },

        async trackPhoneLeadAndNavigate(href) {
            await this.ready();

            if (typeof href !== 'string' || href === '') {
                return;
            }

            const tracking = this.trackPhoneLead();

            await tracking.catch(() => {});

            window.location.href = href;
        },

        async trackTouchAndNavigate(href, type) {
            await this.ready();

            if (typeof href !== 'string' || href === '') {
                return;
            }

            const tracking = this.trackTouch(type);

            await tracking.catch(() => {});

            if (href.startsWith('#')) {
                this.navigateToHash(href);
                return;
            }

            window.location.href = href;
        },

        navigateToHash(href) {
            const targetId = href.slice(1);
            const target = document.getElementById(targetId);
            const isSameHash = window.location.hash === href;

            window.location.hash = href;

            if (!isSameHash) {
                return;
            }

            if (typeof target?.scrollIntoView !== 'function') {
                return;
            }

            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start',
            });
        },

        async trackLandingClick() {
            if (typeof this.config.clickUrl !== 'string' || this.config.clickUrl === '') {
                return;
            }

            if (landingBootstrapState.clickTracked) {
                return;
            }

            landingBootstrapState.clickTracked = true;

            try {
                const response = await fetch(this.config.clickUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken(),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({}),
                });

                if (isSuccessfulBootstrapResponse(response)) {
                    const responseJson = await readResponseJson(response);
                    const record = responseRecord(responseJson);
                    const resultType = landingAnalyticsResultType(record);

                    if (responseJson?.ok === true) {
                        pushLandingAnalyticsEventForResultType(record, resultType);
                    }

                    this.clearSoftReloadAttempt();

                    return;
                }

                landingBootstrapState.clickTracked = false;

                if (responseStatus(response) === 419) {
                    if (!this.hasRecentSoftReloadAttempt()) {
                        this.markSoftReloadAttempt();
                        window.location.reload();
                    }

                    return;
                }
            } catch {
                landingBootstrapState.clickTracked = false;
            }
        },

        async trackPhoneLead() {
            if (typeof this.config.leadPhoneClickUrl !== 'string' || this.config.leadPhoneClickUrl === '') {
                return;
            }

            if (landingBootstrapState.phoneLeadPromise) {
                return landingBootstrapState.phoneLeadPromise;
            }

            landingBootstrapState.phoneLeadPromise = (async () => {
                try {
                    const response = await fetch(this.config.leadPhoneClickUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        keepalive: true,
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken(),
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({}),
                    });

                    const responseJson = await readResponseJson(response);
                    const record = responseRecord(responseJson);
                    const resultType = landingAnalyticsResultType(record);

                    if (responseJson?.ok === true && pushLandingAnalyticsEventForResultType(record, resultType)) {
                        return;
                    }

                } finally {
                    landingBootstrapState.phoneLeadPromise = null;
                }
            })();

            return landingBootstrapState.phoneLeadPromise;
        },

        async trackTouch(type) {
            if (typeof this.config.touchUrl !== 'string' || this.config.touchUrl === '') {
                return;
            }

            if (typeof type !== 'string' || type === '') {
                return;
            }

            if (landingBootstrapState.touchPromises.has(type)) {
                return landingBootstrapState.touchPromises.get(type);
            }

            const promise = (async () => {
                try {
                    const response = await fetch(this.config.touchUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        keepalive: true,
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken(),
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({type}),
                    });

                    const responseJson = await readResponseJson(response);
                    const record = responseRecord(responseJson);
                    const resultType = landingAnalyticsResultType(record, 'touch');

                    if (responseJson?.ok !== false && response.ok) {
                        pushLandingAnalyticsEventForResultType(record, resultType);
                    }
                } finally {
                    landingBootstrapState.touchPromises.delete(type);
                }
            })();

            landingBootstrapState.touchPromises.set(type, promise);

            return promise;
        },

        delay(ms) {
            return new Promise((resolve) => {
                window.setTimeout(resolve, ms);
            });
        },

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        },

        readConfig() {
            const script = document.getElementById('landing-capture-config');

            if (!script) {
                return {};
            }

            try {
                return JSON.parse(script.textContent ?? '{}');
            } catch {
                return {};
            }
        },

        stripAttributionQuery() {
            if (!window.location.search) {
                return;
            }

            const cleanUrl = `${window.location.pathname}${window.location.hash}`;

            window.history.replaceState({}, '', cleanUrl);
        },
    };
}
