import assert from 'node:assert/strict';
import test from 'node:test';
import mountAdminLeadNavigation, {
    LEAD_DETAILS_BACK_URL_STORAGE_KEY,
    resolveLeadDetailsBackUrl,
    sanitizeBackofficeAdminUrl,
} from '../../resources/js/adminLeadNavigation.js';

function makeStorage(initial = {}) {
    const values = new Map(Object.entries(initial));

    return {
        getItem(key) {
            return values.has(key) ? values.get(key) : null;
        },
        setItem(key, value) {
            values.set(key, String(value));
        },
    };
}

test('adminLeadNavigation stores the current admin URL when opening lead details from a source screen', () => {
    const storage = makeStorage();
    let clickHandler = null;

    globalThis.window = {
        location: {
            href: 'https://localhost:8443/admin/leads?status=new&page=2',
            origin: 'https://localhost:8443',
        },
        sessionStorage: storage,
    };

    globalThis.document = {
        addEventListener(type, handler) {
            if (type === 'click') {
                clickHandler = handler;
            }
        },
        querySelector() {
            return null;
        },
    };

    mountAdminLeadNavigation();

    clickHandler({
        button: 0,
        metaKey: false,
        ctrlKey: false,
        shiftKey: false,
        altKey: false,
        target: {
            closest() {
                return {
                    target: '',
                    hasAttribute() {
                        return false;
                    },
                };
            },
        },
    });

    assert.equal(
        storage.getItem(LEAD_DETAILS_BACK_URL_STORAGE_KEY),
        'https://localhost:8443/admin/leads?status=new&page=2',
    );
});

test('adminLeadNavigation applies a stored admin back URL to the lead details back button', () => {
    const storage = makeStorage({
        [LEAD_DETAILS_BACK_URL_STORAGE_KEY]: 'https://localhost:8443/admin/dashboard',
    });
    const backButton = {
        href: 'https://localhost:8443/admin/leads',
        getAttribute(name) {
            return name === 'href' ? this.href : null;
        },
        setAttribute(name, value) {
            if (name === 'href') {
                this.href = value;
            }
        },
    };

    globalThis.window = {
        location: {
            href: 'https://localhost:8443/admin/leads/lead-123',
            origin: 'https://localhost:8443',
        },
        sessionStorage: storage,
    };

    globalThis.document = {
        addEventListener() {},
        querySelector(selector) {
            return selector === '[data-lead-details-back-button]' ? backButton : null;
        },
    };

    mountAdminLeadNavigation();

    assert.equal(backButton.href, 'https://localhost:8443/admin/dashboard');
});

test('adminLeadNavigation keeps the fallback back URL when the stored value is invalid', () => {
    const storage = makeStorage({
        [LEAD_DETAILS_BACK_URL_STORAGE_KEY]: 'https://malicious.example.test/admin/leads',
    });
    const backButton = {
        href: 'https://localhost:8443/admin/leads',
        getAttribute(name) {
            return name === 'href' ? this.href : null;
        },
        setAttribute(name, value) {
            if (name === 'href') {
                this.href = value;
            }
        },
    };

    globalThis.window = {
        location: {
            href: 'https://localhost:8443/admin/leads/lead-123',
            origin: 'https://localhost:8443',
        },
        sessionStorage: storage,
    };

    globalThis.document = {
        addEventListener() {},
        querySelector(selector) {
            return selector === '[data-lead-details-back-button]' ? backButton : null;
        },
    };

    mountAdminLeadNavigation();

    assert.equal(backButton.href, 'https://localhost:8443/admin/leads');
});

test('sanitizeBackofficeAdminUrl accepts only same-origin admin URLs', () => {
    assert.equal(
        sanitizeBackofficeAdminUrl('/admin/leads?page=2', 'https://localhost:8443'),
        'https://localhost:8443/admin/leads?page=2',
    );
    assert.equal(
        resolveLeadDetailsBackUrl(
            makeStorage({
                [LEAD_DETAILS_BACK_URL_STORAGE_KEY]: 'https://localhost:8443/profile',
            }),
            'https://localhost:8443/admin/leads',
            'https://localhost:8443',
        ),
        'https://localhost:8443/admin/leads',
    );
});
