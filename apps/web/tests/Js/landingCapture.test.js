import assert from 'node:assert/strict';
import test from 'node:test';
import landingCapture, { resetLandingCaptureBootstrapState } from '../../resources/js/landingCapture.js';

function makeComponent(overrides = {}) {
    resetLandingCaptureBootstrapState();

    const replaceState = overrides.replaceState ?? (() => {});
    const fetch = overrides.fetch ?? (async () => {});
    const phoneField = overrides.phoneField ?? {value: overrides.initialPhoneValue ?? ''};
    const targets = overrides.targets ?? {};
    const config = overrides.config ?? {
        clickUrl: 'https://localhost:8443/capture/click',
        touchUrl: 'https://localhost:8443/capture/touch',
        leadFormUrl: 'https://localhost:8443/capture/leads/form',
        leadPhoneCountryCode: '+380',
        formSuccessMessage: 'Дякуємо! Заявку отримано, ми зв\'яжемося з вами найближчим часом.',
        formValidationMessage: 'Перевірте правильність заповнення форми та надішліть заявку ще раз.',
        formConflictMessage: 'Не вдалося зберегти заявку без поточного візиту. Оновіть сторінку та спробуйте ще раз.',
        formFailureMessage: 'Не вдалося надіслати заявку. Спробуйте ще раз або зателефонуйте нам.',
        leadPhoneRequiredMessage: 'Вкажіть номер телефону.',
        leadPhoneFormatMessage: 'Введіть 9 цифр після +380, наприклад 50 111 22 33.',
    };
    const sessionStorageData = overrides.sessionStorageData ?? new Map();
    const sessionStorage = overrides.sessionStorage ?? {
        getItem(key) {
            return sessionStorageData.has(key) ? sessionStorageData.get(key) : null;
        },
        setItem(key, value) {
            sessionStorageData.set(key, String(value));
        },
        removeItem(key) {
            sessionStorageData.delete(key);
        },
        clear() {
            sessionStorageData.clear();
        },
    };
    const location = overrides.location ?? {
        pathname: '/?utm_source=google',
        search: '?utm_source=google&utm_medium=cpc',
        hash: '#lead-form',
        reload() {},
    };

    globalThis.window = {
        location,
        history: {
            replaceState,
        },
        sessionStorage,
        setTimeout: globalThis.setTimeout.bind(globalThis),
    };

    globalThis.document = {
        getElementById: (id) => {
            if (id === 'landing-capture-config') {
                return {
                    textContent: JSON.stringify(config),
                };
            }

            if (id === 'phone') {
                return phoneField;
            }

            return targets[id] ?? null;
        },
        querySelector: () => ({
            getAttribute: () => 'csrf-token-123',
        }),
    };

    globalThis.fetch = fetch;

    return landingCapture();
}

function deferred() {
    let resolve;
    let reject;

    const promise = new Promise((promiseResolve, promiseReject) => {
        resolve = promiseResolve;
        reject = promiseReject;
    });

    return {promise, resolve, reject};
}

test('landingCapture strips attribution query before tracking click', async () => {
    const replaceStateCalls = [];
    const fetchCalls = [];

    const component = makeComponent({
        replaceState: (...args) => {
            replaceStateCalls.push(args);
        },
        fetch: async (...args) => {
            fetchCalls.push(args);
            return {};
        },
        location: {
            pathname: '/',
            search: '?utm_source=google&utm_medium=cpc',
            hash: '#lead-form',
        },
    });

    await component.init();

    assert.equal(replaceStateCalls.length, 1);
    assert.deepEqual(replaceStateCalls[0], [{}, '', '/#lead-form']);
    assert.equal(fetchCalls.length, 1);
    assert.equal(fetchCalls[0][0], 'https://localhost:8443/capture/click');
});

test('landingCapture skips URL cleanup when search is empty', async () => {
    const replaceStateCalls = [];

    const component = makeComponent({
        replaceState: (...args) => {
            replaceStateCalls.push(args);
        },
        location: {
            pathname: '/',
            search: '',
            hash: '',
        },
    });

    await component.init();

    assert.equal(replaceStateCalls.length, 0);
});

test('landingCapture normalizes the initial phone field value on init', async () => {
    const phoneField = {
        value: '+380 (50) 111-22-33-99',
    };

    const component = makeComponent({
        config: {
            clickUrl: '',
            leadFormUrl: 'https://localhost:8443/capture/leads/form',
            leadPhoneCountryCode: '+380',
            formSuccessMessage: 'Дякуємо! Заявку отримано, ми зв\'яжемося з вами найближчим часом.',
            formValidationMessage: 'Перевірте правильність заповнення форми та надішліть заявку ще раз.',
            formConflictMessage: 'Не вдалося зберегти заявку без активного візиту. Оновіть сторінку та спробуйте ще раз.',
            formFailureMessage: 'Не вдалося надіслати заявку. Спробуйте ще раз або зателефонуйте нам.',
            leadPhoneRequiredMessage: 'Вкажіть номер телефону.',
            leadPhoneFormatMessage: 'Введіть 9 цифр після +380, наприклад 50 111 22 33.',
        },
        location: {
            pathname: '/',
            search: '',
            hash: '',
        },
        phoneField,
    });

    await component.init();

    assert.equal(phoneField.value, '50 111 22 33');
});

test('landingCapture keeps the interface bootstrapping until the click request settles', async () => {
    const pendingFetch = deferred();
    const fetchCalls = [];

    const component = makeComponent({
        fetch: async (...args) => {
            fetchCalls.push(args);
            return await pendingFetch.promise;
        },
        location: {
            pathname: '/',
            search: '',
            hash: '',
        },
    });

    const initPromise = component.init();

    assert.equal(component.isBootstrapping, true);
    assert.equal(fetchCalls.length, 1);

    pendingFetch.resolve({});
    await initPromise;

    assert.equal(component.isBootstrapping, false);
});

test('landingCapture soft reloads once when the landing click returns 419', async () => {
    let reloadCalls = 0;

    const component = makeComponent({
        fetch: async () => ({
            ok: false,
            status: 419,
            json: async () => ({}),
        }),
        location: {
            pathname: '/',
            search: '',
            hash: '',
            reload() {
                reloadCalls += 1;
            },
        },
    });

    await component.init();

    assert.equal(reloadCalls, 1);
    assert.equal(component.hasRecentSoftReloadAttempt(), true);
});

test('landingCapture does not soft reload twice during the cooldown window', async () => {
    let reloadCalls = 0;

    const component = makeComponent({
        fetch: async () => ({
            ok: false,
            status: 419,
            json: async () => ({}),
        }),
        location: {
            pathname: '/',
            search: '',
            hash: '',
            reload() {
                reloadCalls += 1;
            },
        },
    });

    component.markSoftReloadAttempt();
    await component.init();

    assert.equal(reloadCalls, 0);
    assert.equal(component.hasRecentSoftReloadAttempt(), true);
});

test('landingCapture waits for bootstrapping before navigating', async () => {
    const pendingFetch = deferred();

    const component = makeComponent({
        fetch: async (...args) => {
            return await pendingFetch.promise;
        },
        location: {
            pathname: '/',
            search: '',
            hash: '',
        },
    });

    const initPromise = component.init();
    const navigatePromise = component.navigateAfterReady('#works');

    assert.equal(globalThis.window.location.hash, '');

    pendingFetch.resolve({});
    await initPromise;
    await navigatePromise;

    assert.equal(globalThis.window.location.hash, '#works');
});

test('landingCapture scrolls to the same hash target when the hash is already active', async () => {
    const scrollIntoViewCalls = [];

    const component = makeComponent({
        config: {
            clickUrl: '',
            touchUrl: 'https://localhost:8443/capture/touch',
            leadFormUrl: 'https://localhost:8443/capture/leads/form',
            leadPhoneCountryCode: '+380',
            formSuccessMessage: 'Дякуємо! Заявку отримано, ми зв\'яжемося з вами найближчим часом.',
            formValidationMessage: 'Перевірте правильність заповнення форми та надішліть заявку ще раз.',
            formConflictMessage: 'Не вдалося зберегти заявку без активного візиту. Оновіть сторінку та спробуйте ще раз.',
            formFailureMessage: 'Не вдалося надіслати заявку. Спробуйте ще раз або зателефонуйте нам.',
            leadPhoneRequiredMessage: 'Вкажіть номер телефону.',
            leadPhoneFormatMessage: 'Введіть 9 цифр після +380, наприклад 50 111 22 33.',
        },
        location: {
            pathname: '/',
            search: '',
            hash: '#lead-form',
        },
        targets: {
            'lead-form': {
                scrollIntoView(options) {
                    scrollIntoViewCalls.push(options);
                },
            },
        },
    });

    await component.init();
    await component.navigateAfterReady('#lead-form');

    assert.equal(scrollIntoViewCalls.length, 1);
    assert.deepEqual(scrollIntoViewCalls[0], {
        behavior: 'smooth',
        block: 'start',
    });
});

test('landingCapture tracks works click before navigating to the works hash', async () => {
    const fetchCalls = [];

    const component = makeComponent({
        config: {
            clickUrl: '',
            touchUrl: 'https://localhost:8443/capture/touch',
            leadFormUrl: 'https://localhost:8443/capture/leads/form',
            leadPhoneCountryCode: '+380',
            formSuccessMessage: 'Дякуємо! Заявку отримано, ми зв\'яжемося з вами найближчим часом.',
            formValidationMessage: 'Перевірте правильність заповнення форми та надішліть заявку ще раз.',
            formConflictMessage: 'Не вдалося зберегти заявку без активного візиту. Оновіть сторінку та спробуйте ще раз.',
            formFailureMessage: 'Не вдалося надіслати заявку. Спробуйте ще раз або зателефонуйте нам.',
            leadPhoneRequiredMessage: 'Вкажіть номер телефону.',
            leadPhoneFormatMessage: 'Введіть 9 цифр після +380, наприклад 50 111 22 33.',
        },
        fetch: async (...args) => {
            fetchCalls.push(args);

            return {
                ok: true,
                status: 200,
                json: async () => ({ok: true}),
            };
        },
        location: {
            pathname: '/',
            search: '',
            hash: '',
        },
    });

    await component.init();
    await component.trackTouchAndNavigate('#works', 'works_click');

    assert.equal(fetchCalls.length, 1);
    assert.equal(fetchCalls[0][0], 'https://localhost:8443/capture/touch');
    assert.equal(fetchCalls[0][1].body, JSON.stringify({type: 'works_click'}));
    assert.equal(globalThis.window.location.hash, '#works');
});

test('landingCapture tracks lead form click before navigating to the form hash', async () => {
    const fetchCalls = [];

    const component = makeComponent({
        config: {
            clickUrl: '',
            touchUrl: 'https://localhost:8443/capture/touch',
            leadFormUrl: 'https://localhost:8443/capture/leads/form',
            leadPhoneCountryCode: '+380',
            formSuccessMessage: 'Дякуємо! Заявку отримано, ми зв\'яжемося з вами найближчим часом.',
            formValidationMessage: 'Перевірте правильність заповнення форми та надішліть заявку ще раз.',
            formConflictMessage: 'Не вдалося зберегти заявку без активного візиту. Оновіть сторінку та спробуйте ще раз.',
            formFailureMessage: 'Не вдалося надіслати заявку. Спробуйте ще раз або зателефонуйте нам.',
            leadPhoneRequiredMessage: 'Вкажіть номер телефону.',
            leadPhoneFormatMessage: 'Введіть 9 цифр після +380, наприклад 50 111 22 33.',
        },
        fetch: async (...args) => {
            fetchCalls.push(args);

            return {
                ok: true,
                status: 200,
                json: async () => ({ok: true}),
            };
        },
        location: {
            pathname: '/',
            search: '',
            hash: '',
        },
    });

    await component.init();
    await component.trackTouchAndNavigate('#lead-form', 'lead_form_click');

    assert.equal(fetchCalls.length, 1);
    assert.equal(fetchCalls[0][0], 'https://localhost:8443/capture/touch');
    assert.equal(fetchCalls[0][1].body, JSON.stringify({type: 'lead_form_click'}));
    assert.equal(globalThis.window.location.hash, '#lead-form');
});

test('landingCapture sends phone-click tracking before navigating to tel', async () => {
    const fetchCalls = [];

    const component = makeComponent({
        config: {
            clickUrl: '',
            touchUrl: 'https://localhost:8443/capture/touch',
            leadFormUrl: 'https://localhost:8443/capture/leads/form',
            leadPhoneClickUrl: 'https://localhost:8443/capture/leads/phone-click',
            leadPhoneCountryCode: '+380',
            formSuccessMessage: 'Дякуємо! Заявку отримано, ми зв\'яжемося з вами найближчим часом.',
            formValidationMessage: 'Перевірте правильність заповнення форми та надішліть заявку ще раз.',
            formConflictMessage: 'Не вдалося зберегти заявку без активного візиту. Оновіть сторінку та спробуйте ще раз.',
            formFailureMessage: 'Не вдалося надіслати заявку. Спробуйте ще раз або зателефонуйте нам.',
            leadPhoneRequiredMessage: 'Вкажіть номер телефону.',
            leadPhoneFormatMessage: 'Введіть 9 цифр після +380, наприклад 50 111 22 33.',
        },
        fetch: async (...args) => {
            fetchCalls.push(args);

            return {
                ok: true,
                status: 201,
                json: async () => ({ok: true}),
            };
        },
        location: {
            pathname: '/',
            search: '',
            hash: '',
        },
    });

    await component.init();
    await component.trackPhoneLeadAndNavigate('tel:+380667810707');

    assert.equal(fetchCalls.length, 1);
    assert.equal(fetchCalls[0][0], 'https://localhost:8443/capture/leads/phone-click');
    assert.equal(fetchCalls[0][1].keepalive, true);
    assert.equal(globalThis.window.location.href, 'tel:+380667810707');
});

test('landingCapture sends phone-click tracking on each new click after the previous request settles', async () => {
    const fetchCalls = [];

    const component = makeComponent({
        config: {
            clickUrl: '',
            touchUrl: 'https://localhost:8443/capture/touch',
            leadFormUrl: 'https://localhost:8443/capture/leads/form',
            leadPhoneClickUrl: 'https://localhost:8443/capture/leads/phone-click',
            leadPhoneCountryCode: '+380',
            formSuccessMessage: 'Дякуємо! Заявку отримано, ми зв\'яжемося з вами найближчим часом.',
            formValidationMessage: 'Перевірте правильність заповнення форми та надішліть заявку ще раз.',
            formConflictMessage: 'Не вдалося зберегти заявку без активного візиту. Оновіть сторінку та спробуйте ще раз.',
            formFailureMessage: 'Не вдалося надіслати заявку. Спробуйте ще раз або зателефонуйте нам.',
            leadPhoneRequiredMessage: 'Вкажіть номер телефону.',
            leadPhoneFormatMessage: 'Введіть 9 цифр після +380, наприклад 50 111 22 33.',
        },
        fetch: async (...args) => {
            fetchCalls.push(args);

            return {
                ok: true,
                status: 201,
                json: async () => ({ok: true}),
            };
        },
        location: {
            pathname: '/',
            search: '',
            hash: '',
        },
    });

    await component.init();
    await component.trackPhoneLeadAndNavigate('tel:+380667810707');
    await component.trackPhoneLeadAndNavigate('tel:+380667810707');

    assert.equal(fetchCalls.length, 2);
});

test('landingCapture sends messenger touch tracking without leaving the current page', async () => {
    const fetchCalls = [];

    const component = makeComponent({
        config: {
            clickUrl: '',
            touchUrl: 'https://localhost:8443/capture/touch',
            leadFormUrl: 'https://localhost:8443/capture/leads/form',
            leadPhoneCountryCode: '+380',
            formSuccessMessage: 'Дякуємо! Заявку отримано, ми зв\'яжемося з вами найближчим часом.',
            formValidationMessage: 'Перевірте правильність заповнення форми та надішліть заявку ще раз.',
            formConflictMessage: 'Не вдалося зберегти заявку без активного візиту. Оновіть сторінку та спробуйте ще раз.',
            formFailureMessage: 'Не вдалося надіслати заявку. Спробуйте ще раз або зателефонуйте нам.',
            leadPhoneRequiredMessage: 'Вкажіть номер телефону.',
            leadPhoneFormatMessage: 'Введіть 9 цифр після +380, наприклад 50 111 22 33.',
        },
        fetch: async (...args) => {
            fetchCalls.push(args);

            return {
                ok: true,
                status: 200,
                json: async () => ({ok: true}),
            };
        },
        location: {
            pathname: '/',
            search: '',
            hash: '',
        },
    });

    await component.init();
    await component.trackTouch('messenger_click');

    assert.equal(fetchCalls.length, 1);
    assert.equal(fetchCalls[0][0], 'https://localhost:8443/capture/touch');
    assert.equal(fetchCalls[0][1].keepalive, true);
    assert.equal(fetchCalls[0][1].body, JSON.stringify({type: 'messenger_click'}));
    assert.equal(globalThis.window.location.href, undefined);
});

test('landingCapture sends identical touch tracking again after the previous request settles', async () => {
    const fetchCalls = [];

    const component = makeComponent({
        config: {
            clickUrl: '',
            touchUrl: 'https://localhost:8443/capture/touch',
            leadFormUrl: 'https://localhost:8443/capture/leads/form',
            leadPhoneCountryCode: '+380',
            formSuccessMessage: 'Дякуємо! Заявку отримано, ми зв\'яжемося з вами найближчим часом.',
            formValidationMessage: 'Перевірте правильність заповнення форми та надішліть заявку ще раз.',
            formConflictMessage: 'Не вдалося зберегти заявку без активного візиту. Оновіть сторінку та спробуйте ще раз.',
            formFailureMessage: 'Не вдалося надіслати заявку. Спробуйте ще раз або зателефонуйте нам.',
            leadPhoneRequiredMessage: 'Вкажіть номер телефону.',
            leadPhoneFormatMessage: 'Введіть 9 цифр після +380, наприклад 50 111 22 33.',
        },
        fetch: async (...args) => {
            fetchCalls.push(args);

            return {
                ok: true,
                status: 200,
                json: async () => ({ok: true}),
            };
        },
        location: {
            pathname: '/',
            search: '',
            hash: '',
        },
    });

    await component.init();
    await component.trackTouch('messenger_click');
    await component.trackTouch('messenger_click');

    assert.equal(fetchCalls.length, 2);
});

test('landingCapture submits the lead form successfully', async () => {
    const fetchCalls = [];
    const component = makeComponent({
        config: {
            clickUrl: '',
            leadFormUrl: 'https://localhost:8443/capture/leads/form',
            leadPhoneCountryCode: '+380',
            formSuccessMessage: 'Дякуємо! Заявку отримано, ми зв\'яжемося з вами найближчим часом.',
            formValidationMessage: 'Перевірте правильність заповнення форми та надішліть заявку ще раз.',
            formConflictMessage: 'Не вдалося зберегти заявку без активного візиту. Оновіть сторінку та спробуйте ще раз.',
            formFailureMessage: 'Не вдалося надіслати заявку. Спробуйте ще раз або зателефонуйте нам.',
            leadPhoneRequiredMessage: 'Вкажіть номер телефону.',
            leadPhoneFormatMessage: 'Введіть 9 цифр після +380, наприклад 50 111 22 33.',
        },
        fetch: async (...args) => {
            fetchCalls.push(args);

            return {
                status: 201,
                json: async () => ({
                    ok: true,
                    data: {
                        leadId: 'lead-1',
                    },
                }),
            };
        },
        location: {
            pathname: '/',
            search: '',
            hash: '',
        },
    });

    await component.init();

    const form = {
        resetCalls: 0,
        elements: {
            namedItem(name) {
                if (name === 'phone') {
                    return this.phone;
                }

                return null;
            },
            phone: {
                value: '',
            },
        },
        reset() {
            this.resetCalls += 1;
        },
    };

    const event = {
        preventDefaultCalls: 0,
        preventDefault() {
            this.preventDefaultCalls += 1;
        },
        currentTarget: form,
    };

    globalThis.FormData = class {
        constructor() {
            this.values = new Map([
                ['name', 'John Doe'],
                ['phone', '50 111 22 33'],
            ]);
        }

        get(key) {
            return this.values.get(key) ?? null;
        }
    };

    await component.submitLeadForm(event);

    assert.equal(event.preventDefaultCalls, 1);
    assert.equal(component.isSubmittingLeadForm, false);
    assert.equal(component.leadFormState, 'success');
    assert.equal(component.leadFormMessage, 'Дякуємо! Заявку отримано, ми зв\'яжемося з вами найближчим часом.');
    assert.equal(form.resetCalls, 1);
    assert.equal(fetchCalls.length, 1);
    assert.equal(fetchCalls[0][0], 'https://localhost:8443/capture/leads/form');
    assert.equal(JSON.parse(fetchCalls[0][1].body).phone, '+380501112233');
    assert.equal(JSON.parse(fetchCalls[0][1].body).comment, undefined);
    assert.equal(form.elements.phone.value, '50 111 22 33');
});

test('landingCapture shows a local validation error when phone is missing', async () => {
    const component = makeComponent({
        config: {
            clickUrl: '',
            leadFormUrl: 'https://localhost:8443/capture/leads/form',
            leadPhoneCountryCode: '+380',
            formSuccessMessage: 'Дякуємо! Заявку отримано, ми зв\'яжемося з вами найближчим часом.',
            formValidationMessage: 'Перевірте правильність заповнення форми та надішліть заявку ще раз.',
            formConflictMessage: 'Не вдалося зберегти заявку без активного візиту. Оновіть сторінку та спробуйте ще раз.',
            formFailureMessage: 'Не вдалося надіслати заявку. Спробуйте ще раз або зателефонуйте нам.',
            leadPhoneRequiredMessage: 'Вкажіть номер телефону.',
            leadPhoneFormatMessage: 'Введіть 9 цифр після +380, наприклад 50 111 22 33.',
        },
        fetch: async () => ({
            status: 422,
            json: async () => ({
                ok: false,
                code: 'validation_error',
                message: 'The given data was invalid.',
                errors: {
                    phone: ['The phone field is required.'],
                },
            }),
        }),
        location: {
            pathname: '/',
            search: '',
            hash: '',
        },
    });

    await component.init();

    const event = {
        preventDefault() {},
        currentTarget: {
            reset() {},
        },
    };

    globalThis.FormData = class {
        constructor() {
            this.values = new Map();
        }

        get() {
            return null;
        }
    };

    await component.submitLeadForm(event);

    assert.equal(component.leadFormState, 'validation-error');
    assert.equal(component.leadFormMessage, 'Перевірте правильність заповнення форми та надішліть заявку ще раз.');
    assert.deepEqual(component.leadFormFieldErrors.phone, ['Вкажіть номер телефону.']);
});

test('landingCapture validates the phone format locally before sending the form', async () => {
    let fetchCalls = 0;

    const component = makeComponent({
        config: {
            clickUrl: '',
            leadFormUrl: 'https://localhost:8443/capture/leads/form',
            leadPhoneCountryCode: '+380',
            formSuccessMessage: 'Дякуємо! Заявку отримано, ми зв\'яжемося з вами найближчим часом.',
            formValidationMessage: 'Перевірте правильність заповнення форми та надішліть заявку ще раз.',
            formConflictMessage: 'Не вдалося зберегти заявку без активного візиту. Оновіть сторінку та спробуйте ще раз.',
            formFailureMessage: 'Не вдалося надіслати заявку. Спробуйте ще раз або зателефонуйте нам.',
            leadPhoneRequiredMessage: 'Вкажіть номер телефону.',
            leadPhoneFormatMessage: 'Введіть 9 цифр після +380, наприклад 50 111 22 33.',
        },
        fetch: async () => {
            fetchCalls += 1;

            return {
                status: 201,
                json: async () => ({ok: true}),
            };
        },
        location: {
            pathname: '/',
            search: '',
            hash: '',
        },
    });

    await component.init();

    globalThis.FormData = class {
        constructor() {
            this.values = new Map([
                ['name', 'John Doe'],
                ['phone', '12 345'],
            ]);
        }

        get(key) {
            return this.values.get(key) ?? null;
        }
    };

    await component.submitLeadForm({
        preventDefault() {},
        currentTarget: {
            elements: {
                namedItem() {
                    return {value: ''};
                },
            },
            reset() {},
        },
    });

    assert.equal(fetchCalls, 0);
    assert.equal(component.leadFormState, 'validation-error');
    assert.deepEqual(component.leadFormFieldErrors.phone, ['Введіть 9 цифр після +380, наприклад 50 111 22 33.']);
});

test('landingCapture trims a pasted full phone number with +380 to the local nine-digit part', async () => {
    const fetchCalls = [];

    const component = makeComponent({
        config: {
            clickUrl: '',
            leadFormUrl: 'https://localhost:8443/capture/leads/form',
            leadPhoneCountryCode: '+380',
            formSuccessMessage: 'Дякуємо! Заявку отримано, ми зв\'яжемося з вами найближчим часом.',
            formValidationMessage: 'Перевірте правильність заповнення форми та надішліть заявку ще раз.',
            formConflictMessage: 'Не вдалося зберегти заявку без активного візиту. Оновіть сторінку та спробуйте ще раз.',
            formFailureMessage: 'Не вдалося надіслати заявку. Спробуйте ще раз або зателефонуйте нам.',
            leadPhoneRequiredMessage: 'Вкажіть номер телефону.',
            leadPhoneFormatMessage: 'Введіть 9 цифр після +380, наприклад 50 111 22 33.',
        },
        fetch: async (...args) => {
            fetchCalls.push(args);

            return {
                status: 201,
                json: async () => ({ok: true, data: {leadId: 'lead-1'}}),
            };
        },
        location: {
            pathname: '/',
            search: '',
            hash: '',
        },
    });

    await component.init();

    globalThis.FormData = class {
        constructor() {
            this.values = new Map([
                ['name', 'John Doe'],
                ['phone', '+380 (50) 111-22-33-99'],
            ]);
        }

        get(key) {
            return this.values.get(key) ?? null;
        }
    };

    const phoneField = {value: ''};

    await component.submitLeadForm({
        preventDefault() {},
        currentTarget: {
            elements: {
                namedItem() {
                    return phoneField;
                },
            },
            reset() {},
        },
    });

    assert.equal(fetchCalls.length, 1);
    assert.equal(component.leadFormState, 'success');
    assert.equal(JSON.parse(fetchCalls[0][1].body).phone, '+380501112233');
    assert.equal(phoneField.value, '50 111 22 33');
});

test('landingCapture shows the current visit conflict message when the form endpoint returns 409', async () => {
    const component = makeComponent({
        config: {
            clickUrl: '',
            leadFormUrl: 'https://localhost:8443/capture/leads/form',
            leadPhoneCountryCode: '+380',
            formSuccessMessage: 'Дякуємо! Заявку отримано, ми зв\'яжемося з вами найближчим часом.',
            formValidationMessage: 'Перевірте правильність заповнення форми та надішліть заявку ще раз.',
            formConflictMessage: 'Не вдалося зберегти заявку без поточного візиту. Оновіть сторінку та спробуйте ще раз.',
            formFailureMessage: 'Не вдалося надіслати заявку. Спробуйте ще раз або зателефонуйте нам.',
            leadPhoneRequiredMessage: 'Вкажіть номер телефону.',
            leadPhoneFormatMessage: 'Введіть 9 цифр після +380, наприклад 50 111 22 33.',
        },
        fetch: async () => ({
            status: 409,
            json: async () => ({
                ok: false,
                code: 'current_visit_not_found',
                message: 'Cannot create lead from form without a current visit.',
            }),
        }),
        location: {
            pathname: '/',
            search: '',
            hash: '',
        },
    });

    await component.init();

    globalThis.FormData = class {
        constructor() {
            this.values = new Map([
                ['name', 'John Doe'],
                ['phone', '50 111 22 33'],
            ]);
        }

        get(key) {
            return this.values.get(key) ?? null;
        }
    };

    const event = {
        preventDefault() {},
        currentTarget: {
            elements: {
                namedItem() {
                    return {value: '50 111 22 33'};
                },
            },
            reset() {},
        },
    };

    await component.submitLeadForm(event);

    assert.equal(component.leadFormState, 'server-error');
    assert.equal(component.leadFormMessage, 'Не вдалося зберегти заявку без поточного візиту. Оновіть сторінку та спробуйте ще раз.');
});
