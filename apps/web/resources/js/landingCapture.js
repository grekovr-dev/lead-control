const landingBootstrapState = {
    promise: null,
    clickTracked: false,
    phoneLeadPromise: null,
    touchPromises: new Map(),
};

export function resetLandingCaptureBootstrapState() {
    landingBootstrapState.promise = null;
    landingBootstrapState.clickTracked = false;
    landingBootstrapState.phoneLeadPromise = null;
    landingBootstrapState.touchPromises = new Map();
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

                const data = await response.json().catch(() => ({}));

                if (response.status === 201 && data?.ok) {
                    this.leadFormState = 'success';
                    this.leadFormMessage = this.config.formSuccessMessage ?? '';
                    form.reset();
                    return;
                }

                if (response.status === 422) {
                    this.leadFormState = 'validation-error';
                    this.leadFormMessage = this.config.formValidationMessage ?? '';
                    this.leadFormFieldErrors = this.translateLeadFormErrors(data?.errors ?? {});
                    return;
                }

                if (response.status === 409 && data?.code === 'active_visit_not_found') {
                    this.leadFormState = 'server-error';
                    this.leadFormMessage = this.config.formConflictMessage ?? 'Не вдалося зберегти заявку без активного візиту.';
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

            await Promise.race([
                tracking,
                this.delay(400),
            ]);

            window.location.href = href;
        },

        async trackTouchAndNavigate(href, type) {
            await this.ready();

            if (typeof href !== 'string' || href === '') {
                return;
            }

            const tracking = this.trackTouch(type);

            await Promise.race([
                tracking,
                this.delay(400),
            ]);

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
                await fetch(this.config.clickUrl, {
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
                    await fetch(this.config.leadPhoneClickUrl, {
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
                    await fetch(this.config.touchUrl, {
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
