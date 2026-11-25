import Alpine from 'alpinejs';

window.Alpine = Alpine;

window.ocFormatRupiah = function (amount) {
    const num = Math.round(Number(amount || 0));
    return 'Rp ' + num.toLocaleString('id-ID');
};

window.ocFormatTimeWib = function (isoString) {
    if (!isoString) return '-';
    try {
        const d = new Date(isoString);
        return d.toLocaleTimeString('id-ID', {
            timeZone: 'Asia/Jakarta',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        });
    } catch {
        return '-';
    }
};

window.ocFormatDateWib = function (isoString) {
    if (!isoString) return '-';
    try {
        const d = new Date(isoString);
        return d.toLocaleDateString('id-ID', {
            timeZone: 'Asia/Jakarta',
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        });
    } catch {
        return '-';
    }
};

window.ocFormatDateTimeWib = function (isoString) {
    if (!isoString) return '-';
    return `${window.ocFormatDateWib(isoString)} ${window.ocFormatTimeWib(isoString)} WIB`;
};

window.ocReadJsonScript = function (id, fallback = null) {
    const el = document.getElementById(id);
    if (!el) return fallback;
    try {
        return JSON.parse(el.textContent || 'null') ?? fallback;
    } catch {
        return fallback;
    }
};

window.ocCsrfToken = function () {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') || ''
    );
};

window.ocJsonRequest = async function (url, options = {}) {
    const headers = {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': window.ocCsrfToken(),
        'X-Requested-With': 'XMLHttpRequest',
        ...(options.headers || {}),
    };

    const response = await fetch(url, {
        ...options,
        headers,
    });

    const contentType = response.headers.get('content-type') || '';
    const data = contentType.includes('application/json')
        ? await response.json().catch(() => ({}))
        : {};

    if (!response.ok) {
        let message = data.message || `Terjadi kesalahan (${response.status}).`;
        if (data.errors && typeof data.errors === 'object') {
            const firstKey = Object.keys(data.errors)[0];
            if (
                firstKey &&
                Array.isArray(data.errors[firstKey]) &&
                data.errors[firstKey][0]
            ) {
                message = data.errors[firstKey][0];
            }
        }
        const err = new Error(message);
        err.status = response.status;
        err.errors = data.errors || {};
        err.data = data;
        throw err;
    }

    return data;
};

window.ocPlayChime = function (freq = 880) {
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;
        const ctx = new AudioCtx();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(freq, ctx.currentTime);
        gain.gain.setValueAtTime(0.08, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + 0.35);
    } catch {
        // Ignore browser autoplay restrictions
    }
};

window.ocRenderQrSvg = function (payload, size = 220) {
    const text = String(payload || 'OUR-COFFEE-QRIS');
    const modules = 25;
    const cell = size / modules;
    let hash = 2166136261;
    for (let i = 0; i < text.length; i++) {
        hash ^= text.charCodeAt(i);
        hash = Math.imul(hash, 16777619);
    }

    const isFinder = (r, c) => {
        const inTopLeft = r < 7 && c < 7;
        const inTopRight = r < 7 && c >= modules - 7;
        const inBottomLeft = r >= modules - 7 && c < 7;
        if (!inTopLeft && !inTopRight && !inBottomLeft) return null;
        const lr = r >= modules - 7 ? r - (modules - 7) : r;
        const lc = c >= modules - 7 ? c - (modules - 7) : c;
        if (lr === 0 || lr === 6 || lc === 0 || lc === 6) return true;
        if (lr >= 2 && lr <= 4 && lc >= 2 && lc <= 4) return true;
        return false;
    };

    const rects = [];
    let seed = hash >>> 0;
    for (let r = 0; r < modules; r++) {
        for (let c = 0; c < modules; c++) {
            const finder = isFinder(r, c);
            let filled = false;
            if (finder !== null) {
                filled = finder;
            } else if (
                (r < 8 && c < 8) ||
                (r < 8 && c >= modules - 8) ||
                (r >= modules - 8 && c < 8)
            ) {
                filled = false;
            } else {
                seed = (Math.imul(seed, 1664525) + 1013904223) >>> 0;
                filled = ((seed >>> 24) & 1) === 1;
            }
            if (filled) {
                rects.push(
                    `<rect x="${(c * cell).toFixed(2)}" y="${(r * cell).toFixed(2)}" width="${(cell + 0.15).toFixed(2)}" height="${(cell + 0.15).toFixed(2)}" fill="#1c120d"/>`,
                );
            }
        }
    }

    return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${size} ${size}" width="${size}" height="${size}" class="mx-auto rounded-lg bg-white p-2">${rects.join('')}</svg>`;
};

function applyAppearanceClass(mode) {
    const prefersDark =
        window.matchMedia &&
        window.matchMedia('(prefers-color-scheme: dark)').matches;
    const isDark = mode === 'dark' || (mode === 'system' && prefersDark);
    document.documentElement.classList.toggle('dark', isDark);
}

Alpine.store('theme', {
    mode: localStorage.getItem('appearance') || 'system',
    init() {
        applyAppearanceClass(this.mode);
        if (window.matchMedia) {
            window
                .matchMedia('(prefers-color-scheme: dark)')
                .addEventListener('change', () => {
                    if (this.mode === 'system') applyAppearanceClass('system');
                });
        }
    },
    set(next) {
        this.mode = next;
        localStorage.setItem('appearance', next);
        document.cookie = `appearance=${encodeURIComponent(next)};path=/;max-age=${365 * 24 * 60 * 60};SameSite=Lax`;
        applyAppearanceClass(next);
    },
});

Alpine.store('toasts', {
    items: [],
    nextId: 1,
    init() {
        const initial = window.ocReadJsonScript('flash-toasts-data', []);
        if (Array.isArray(initial)) {
            initial.forEach((toast) => {
                if (toast && toast.message) {
                    this.add(toast.message, toast.type || 'info');
                }
            });
        }
    },
    add(message, type = 'info', duration = 4500) {
        const id = this.nextId++;
        this.items.push({ id, type, message });
        if (duration > 0) {
            setTimeout(() => this.remove(id), duration);
        }
    },
    remove(id) {
        this.items = this.items.filter((t) => t.id !== id);
    },
});

function setupVisibilityPoll(callback, intervalMs) {
    let timer = null;
    const start = () => {
        if (timer) clearInterval(timer);
        timer = setInterval(() => {
            if (!document.hidden) callback();
        }, intervalMs);
    };
    const onVis = () => {
        if (!document.hidden) callback();
    };
    document.addEventListener('visibilitychange', onVis);
    start();
    return () => {
        if (timer) clearInterval(timer);
        document.removeEventListener('visibilitychange', onVis);
    };
}

// =========================================================================
// 1. KIOSK APP
// =========================================================================
Alpine.data('kioskApp', (configId) => ({
    device: {},
    isLocalDemo: false,
    settings: {
        cafe_name: 'Our Coffee',
        idle_timeout_seconds: 60,
        idle_warning_seconds: 50,
        confirmation_reset_seconds: 8,
        qris_expiry_seconds: 300,
    },
    categories: [],
    screen: 'welcome', // welcome | order_type | menu | confirmation
    orderType: 'dine_in',
    customerName: '',
    activeCategoryId: null,
    cart: [],
    cartOpen: false,

    // Modifier sheet
    modifierItem: null,
    selectedModifiers: {}, // { [groupId]: [optionId, ...] }
    itemNotes: '',
    itemQty: 1,

    // Payment & checkout
    paymentModalOpen: false,
    checkoutBusy: false,
    checkoutError: '',
    paymentMode: 'select', // select | qris
    currentOrder: null,
    qrisPayment: null,
    qrisCountdown: 0,
    qrisTimer: null,
    qrisPollTimer: null,
    simulatingPaid: false,
    confirmationMethod: 'qris',
    confirmationCountdown: 8,
    confirmationTimer: null,

    // Idle guard
    idleSeconds: 0,
    idleWarningOpen: false,
    idleTimer: null,

    // 5-tap exit modal
    exitTapCount: 0,
    exitTapTimer: null,
    exitModalOpen: false,
    exitPin: '',
    exitError: '',
    exitBusy: false,

    availabilityCleanup: null,

    init() {
        const data = window.ocReadJsonScript(configId, {});
        this.device = data.device || {};
        this.isLocalDemo = Boolean(data.isLocalDemo);
        this.settings = { ...this.settings, ...(data.settings || {}) };
        this.categories = Array.isArray(data.categories) ? data.categories : [];
        if (this.categories.length > 0) {
            this.activeCategoryId = this.categories[0].id;
        }

        const touchEvents = ['pointerdown', 'keydown', 'touchstart', 'scroll'];
        this._resetIdle = () => {
            if (this.screen !== 'welcome' && !this.idleWarningOpen) {
                this.idleSeconds = 0;
            }
        };
        touchEvents.forEach((ev) =>
            window.addEventListener(ev, this._resetIdle, { passive: true }),
        );

        this.idleTimer = setInterval(() => {
            if (
                this.screen === 'welcome' ||
                this.screen === 'confirmation' ||
                this.paymentMode === 'qris'
            ) {
                return;
            }
            this.idleSeconds++;
            if (this.idleSeconds >= this.settings.idle_warning_seconds) {
                this.idleWarningOpen = true;
            }
            if (this.idleSeconds >= this.settings.idle_timeout_seconds) {
                this.resetSession();
            }
        }, 1000);

        this.availabilityCleanup = setupVisibilityPoll(
            () => this.refreshAvailability(),
            15000,
        );
    },

    destroy() {
        if (this.idleTimer) clearInterval(this.idleTimer);
        if (this.qrisTimer) clearInterval(this.qrisTimer);
        if (this.qrisPollTimer) clearInterval(this.qrisPollTimer);
        if (this.confirmationTimer) clearInterval(this.confirmationTimer);
        if (this.availabilityCleanup) this.availabilityCleanup();
    },

    get activeCategory() {
        return (
            this.categories.find((c) => c.id === this.activeCategoryId) ||
            this.categories[0] ||
            null
        );
    },

    get cartCount() {
        return this.cart.reduce((sum, item) => sum + item.quantity, 0);
    },

    get cartSubtotal() {
        return this.cart.reduce(
            (sum, item) => sum + item.unitTotal * item.quantity,
            0,
        );
    },

    get qrisSvg() {
        return window.ocRenderQrSvg(
            this.qrisPayment?.qris_payload ||
                this.currentOrder?.order_number ||
                'OURCOFFEE',
            220,
        );
    },

    startOrdering() {
        this.idleSeconds = 0;
        this.screen = 'order_type';
    },

    chooseOrderType(type) {
        this.orderType = type;
        this.idleSeconds = 0;
        this.screen = 'menu';
    },

    continueAfterIdle() {
        this.idleSeconds = 0;
        this.idleWarningOpen = false;
    },

    resetSession() {
        if (this.qrisTimer) clearInterval(this.qrisTimer);
        if (this.qrisPollTimer) clearInterval(this.qrisPollTimer);
        if (this.confirmationTimer) clearInterval(this.confirmationTimer);
        this.screen = 'welcome';
        this.orderType = 'dine_in';
        this.customerName = '';
        this.cart = [];
        this.cartOpen = false;
        this.modifierItem = null;
        this.paymentModalOpen = false;
        this.paymentMode = 'select';
        this.currentOrder = null;
        this.qrisPayment = null;
        this.idleSeconds = 0;
        this.idleWarningOpen = false;
    },

    async refreshAvailability() {
        try {
            const res = await window.ocJsonRequest('/kiosk/availability');
            const map = res.availability || {};
            this.categories = this.categories.map((cat) => ({
                ...cat,
                items: cat.items.map((item) => {
                    const av = map[item.id];
                    if (!av) return item;
                    return {
                        ...item,
                        is_available: av.is_available,
                        unavailable_reason: av.reason,
                        max_servings: av.max_servings,
                    };
                }),
            }));
        } catch {
            // Silent fail on background poll
        }
    },

    openItemSheet(item) {
        if (!item.is_available) return;
        this.modifierItem = item;
        this.itemNotes = '';
        this.itemQty = 1;
        const defaults = {};
        (item.modifier_groups || []).forEach((group) => {
            const defIds = (group.options || [])
                .filter((o) => o.is_default)
                .map((o) => o.id);
            if (group.selection_type === 'single') {
                if (defIds.length > 0) {
                    defaults[group.id] = [defIds[0]];
                } else if (group.is_required && group.options.length > 0) {
                    defaults[group.id] = [group.options[0].id];
                } else {
                    defaults[group.id] = [];
                }
            } else {
                defaults[group.id] = defIds;
            }
        });
        this.selectedModifiers = defaults;
    },

    isOptionSelected(groupId, optionId) {
        return (this.selectedModifiers[groupId] || []).includes(optionId);
    },

    toggleModifierOption(group, option) {
        const current = [...(this.selectedModifiers[group.id] || [])];
        if (group.selection_type === 'single') {
            if (current.includes(option.id) && !group.is_required) {
                this.selectedModifiers[group.id] = [];
            } else {
                this.selectedModifiers[group.id] = [option.id];
            }
        } else {
            const idx = current.indexOf(option.id);
            if (idx >= 0) {
                current.splice(idx, 1);
            } else if (!group.max_select || current.length < group.max_select) {
                current.push(option.id);
            }
            this.selectedModifiers[group.id] = current;
        }
    },

    get modifierSelectionValid() {
        if (!this.modifierItem) return false;
        return (this.modifierItem.modifier_groups || []).every((group) => {
            const count = (this.selectedModifiers[group.id] || []).length;
            if (group.is_required && count < Math.max(1, group.min_select || 1))
                return false;
            return true;
        });
    },

    get modifierUnitPrice() {
        if (!this.modifierItem) return 0;
        let extra = 0;
        (this.modifierItem.modifier_groups || []).forEach((group) => {
            const selIds = this.selectedModifiers[group.id] || [];
            (group.options || []).forEach((opt) => {
                if (selIds.includes(opt.id))
                    extra += Number(opt.price_delta || 0);
            });
        });
        return Number(this.modifierItem.price) + extra;
    },

    addConfiguredItemToCart() {
        if (!this.modifierItem || !this.modifierSelectionValid) return;
        const selectedOptions = [];
        const optionIds = [];
        (this.modifierItem.modifier_groups || []).forEach((group) => {
            const selIds = this.selectedModifiers[group.id] || [];
            (group.options || []).forEach((opt) => {
                if (selIds.includes(opt.id)) {
                    optionIds.push(opt.id);
                    selectedOptions.push({
                        group_name: group.name,
                        option_name: opt.name,
                        price_delta: opt.price_delta,
                    });
                }
            });
        });
        optionIds.sort((a, b) => a - b);
        const notes = this.itemNotes.trim();
        const sig = `${this.modifierItem.id}:${optionIds.join(',')}:${notes}`;
        const existing = this.cart.find((c) => c.signature === sig);
        if (existing) {
            existing.quantity += this.itemQty;
        } else {
            this.cart.push({
                signature: sig,
                menu_item_id: this.modifierItem.id,
                name: this.modifierItem.name,
                base_price: this.modifierItem.price,
                unitTotal: this.modifierUnitPrice,
                quantity: this.itemQty,
                notes,
                modifier_option_ids: optionIds,
                selected_options: selectedOptions,
            });
        }
        this.modifierItem = null;
        Alpine.store('toasts').add(
            'Item ditambahkan ke keranjang',
            'success',
            2000,
        );
    },

    updateCartQty(index, delta) {
        const item = this.cart[index];
        if (!item) return;
        const next = item.quantity + delta;
        if (next <= 0) {
            this.cart.splice(index, 1);
        } else {
            item.quantity = next;
        }
    },

    openPaymentModal() {
        if (this.cart.length === 0) return;
        this.checkoutError = '';
        this.paymentMode = 'select';
        this.paymentModalOpen = true;
    },

    async submitCheckout(method) {
        if (this.checkoutBusy || this.cart.length === 0) return;
        this.checkoutBusy = true;
        this.checkoutError = '';
        try {
            const res = await window.ocJsonRequest('/kiosk/checkout', {
                method: 'POST',
                body: JSON.stringify({
                    order_type: this.orderType,
                    customer_name: this.customerName.trim() || null,
                    payment_method: method,
                    items: this.cart.map((c) => ({
                        menu_item_id: c.menu_item_id,
                        quantity: c.quantity,
                        notes: c.notes || null,
                        modifier_option_ids: c.modifier_option_ids,
                    })),
                }),
            });

            this.currentOrder = res.order;
            if (method === 'qris' && res.payment) {
                this.qrisPayment = res.payment;
                this.paymentMode = 'qris';
                this.startQrisWatcher(res.payment.expires_at);
            } else {
                this.enterConfirmation('cashier');
            }
        } catch (err) {
            this.checkoutError = err.message || 'Gagal memproses pesanan.';
        } finally {
            this.checkoutBusy = false;
        }
    },

    startQrisWatcher(expiresAt) {
        if (this.qrisTimer) clearInterval(this.qrisTimer);
        if (this.qrisPollTimer) clearInterval(this.qrisPollTimer);

        const updateCountdown = () => {
            if (!expiresAt) {
                this.qrisCountdown = this.settings.qris_expiry_seconds;
                return;
            }
            const diff = Math.max(
                0,
                Math.floor((new Date(expiresAt).getTime() - Date.now()) / 1000),
            );
            this.qrisCountdown = diff;
        };
        updateCountdown();
        this.qrisTimer = setInterval(updateCountdown, 1000);

        this.qrisPollTimer = setInterval(async () => {
            if (!this.currentOrder) return;
            try {
                const res = await window.ocJsonRequest(
                    `/kiosk/orders/${this.currentOrder.id}/qris-status`,
                );
                if (
                    res.payment_status === 'paid' ||
                    res.order_status === 'paid'
                ) {
                    window.ocPlayChime(980);
                    this.enterConfirmation('qris');
                }
            } catch {
                // Retry on next tick
            }
        }, 3000);
    },

    async regenerateQris() {
        if (!this.currentOrder) return;
        try {
            const res = await window.ocJsonRequest(
                `/kiosk/orders/${this.currentOrder.id}/qris-regenerate`,
                {
                    method: 'POST',
                },
            );
            this.qrisPayment = res.payment;
            this.startQrisWatcher(res.payment.expires_at);
        } catch (err) {
            this.checkoutError = err.message;
        }
    },

    async simulateQrisPaid() {
        if (!this.currentOrder || this.simulatingPaid) return;
        this.simulatingPaid = true;
        try {
            const res = await window.ocJsonRequest(
                `/kiosk/orders/${this.currentOrder.id}/qris-simulate-paid`,
                {
                    method: 'POST',
                },
            );
            if (res.payment_status === 'paid') {
                window.ocPlayChime(980);
                this.enterConfirmation('qris');
            }
        } catch (err) {
            this.checkoutError = err.message;
        } finally {
            this.simulatingPaid = false;
        }
    },

    enterConfirmation(method) {
        if (this.qrisTimer) clearInterval(this.qrisTimer);
        if (this.qrisPollTimer) clearInterval(this.qrisPollTimer);
        this.paymentModalOpen = false;
        this.cartOpen = false;
        this.confirmationMethod = method;
        this.confirmationCountdown =
            this.settings.confirmation_reset_seconds || 8;
        this.screen = 'confirmation';
        if (this.confirmationTimer) clearInterval(this.confirmationTimer);
        this.confirmationTimer = setInterval(() => {
            this.confirmationCountdown--;
            if (this.confirmationCountdown <= 0) {
                this.resetSession();
            }
        }, 1000);
    },

    registerTopLeftTap() {
        this.exitTapCount++;
        if (this.exitTapTimer) clearTimeout(this.exitTapTimer);
        if (this.exitTapCount >= 5) {
            this.exitTapCount = 0;
            this.exitPin = '';
            this.exitError = '';
            this.exitModalOpen = true;
            return;
        }
        this.exitTapTimer = setTimeout(() => {
            this.exitTapCount = 0;
        }, 1500);
    },

    appendExitPin(digit) {
        if (this.exitPin.length < 6) {
            this.exitPin += String(digit);
            this.exitError = '';
        }
    },

    backspaceExitPin() {
        this.exitPin = this.exitPin.slice(0, -1);
    },

    async submitExitPin() {
        if (this.exitPin.length !== 6 || this.exitBusy) return;
        this.exitBusy = true;
        this.exitError = '';
        try {
            const res = await window.ocJsonRequest('/kiosk/exit', {
                method: 'POST',
                body: JSON.stringify({ pin: this.exitPin }),
            });
            window.location.href = res.redirect || '/login';
        } catch (err) {
            this.exitError = err.message || 'PIN Admin tidak valid.';
            this.exitPin = '';
        } finally {
            this.exitBusy = false;
        }
    },
}));

// =========================================================================
// 2. POS KASIR APP
// =========================================================================
Alpine.data('posApp', (configId) => ({
    categories: [],
    pendingKioskOrders: [],
    recentOrders: [],
    cafeSettings: {},
    activeTab: 'walk_in', // walk_in | kiosk_queue | recent_orders
    activeCategoryId: null,
    searchQuery: '',

    // Walk-in cart state
    orderType: 'dine_in',
    customerName: '',
    cart: [],
    modifierItem: null,
    selectedModifiers: {},
    itemNotes: '',
    itemQty: 1,

    // Discount & PIN
    discountAmount: 0,
    discountReason: '',
    discountAdminPin: '',
    discountModalOpen: false,
    tempDiscountAmount: 0,
    tempDiscountReason: '',
    tempDiscountPin: '',

    // Payment modal
    paymentModalOpen: false,
    settlingOrder: null, // null = walk-in cart, object = pending Kiosk order
    paymentMethod: 'cash', // cash | edc | qris_manual
    cashReceivedStr: '',
    paymentBusy: false,
    paymentError: '',

    // Void modal
    voidModalOpen: false,
    voidTargetOrder: null,
    voidReason: '',
    voidPin: '',
    voidBusy: false,
    voidError: '',

    // Thermal receipt modal
    receiptOrder: null,
    receiptPaper: '80mm', // 58mm | 80mm

    pollCleanup: null,

    init() {
        const data = window.ocReadJsonScript(configId, {});
        this.categories = Array.isArray(data.categories) ? data.categories : [];
        this.pendingKioskOrders = Array.isArray(data.pendingKioskOrders)
            ? data.pendingKioskOrders
            : [];
        this.recentOrders = Array.isArray(data.recentOrders)
            ? data.recentOrders
            : [];
        this.cafeSettings = data.cafeSettings || {};
        if (this.categories.length > 0) {
            this.activeCategoryId = 'all';
        }
        this.pollCleanup = setupVisibilityPoll(() => this.pollQueue(), 5000);
    },

    destroy() {
        if (this.pollCleanup) this.pollCleanup();
    },

    get filteredItems() {
        const q = this.searchQuery.trim().toLowerCase();
        const items = [];
        this.categories.forEach((cat) => {
            if (
                this.activeCategoryId !== 'all' &&
                cat.id !== this.activeCategoryId
            )
                return;
            (cat.items || []).forEach((item) => {
                if (!q || item.name.toLowerCase().includes(q)) {
                    items.push({ ...item, category_name: cat.name });
                }
            });
        });
        return items;
    },

    get cartSubtotal() {
        return this.cart.reduce(
            (sum, item) => sum + item.unitTotal * item.quantity,
            0,
        );
    },

    get activePayTotal() {
        if (this.settlingOrder) {
            return Math.max(
                0,
                Number(
                    this.settlingOrder.subtotal ||
                        this.settlingOrder.total_amount,
                ) - Number(this.discountAmount || 0),
            );
        }
        return Math.max(
            0,
            this.cartSubtotal - Number(this.discountAmount || 0),
        );
    },

    get cashReceivedNum() {
        return Number(this.cashReceivedStr || 0);
    },

    get cashChange() {
        return this.cashReceivedNum - this.activePayTotal;
    },

    async pollQueue() {
        try {
            const prevIds = new Set(this.pendingKioskOrders.map((o) => o.id));
            const res = await window.ocJsonRequest('/pos/queue');
            const nextPending = Array.isArray(res.pendingKioskOrders)
                ? res.pendingKioskOrders
                : [];
            const hasNewKiosk = nextPending.some((o) => !prevIds.has(o.id));
            if (hasNewKiosk) {
                window.ocPlayChime(780);
                Alpine.store('toasts').add(
                    'Pesanan Kiosk baru menunggu pembayaran!',
                    'info',
                );
            }
            this.pendingKioskOrders = nextPending;
            this.recentOrders = Array.isArray(res.recentOrders)
                ? res.recentOrders
                : [];
            const map = res.availability || {};
            this.categories = this.categories.map((cat) => ({
                ...cat,
                items: cat.items.map((item) => {
                    const av = map[item.id];
                    return av
                        ? {
                              ...item,
                              is_available: av.is_available,
                              unavailable_reason: av.reason,
                              max_servings: av.max_servings,
                          }
                        : item;
                }),
            }));
        } catch {
            // Silent on poll error
        }
    },

    openItemSheet(item) {
        if (!item.is_available) return;
        if (!item.modifier_groups || item.modifier_groups.length === 0) {
            this.pushCartItem(item, [], '', 1, Number(item.price));
            return;
        }
        this.modifierItem = item;
        this.itemNotes = '';
        this.itemQty = 1;
        const defaults = {};
        item.modifier_groups.forEach((group) => {
            const defIds = (group.options || [])
                .filter((o) => o.is_default)
                .map((o) => o.id);
            if (group.selection_type === 'single') {
                defaults[group.id] =
                    defIds.length > 0
                        ? [defIds[0]]
                        : group.is_required && group.options.length > 0
                          ? [group.options[0].id]
                          : [];
            } else {
                defaults[group.id] = defIds;
            }
        });
        this.selectedModifiers = defaults;
    },

    isOptionSelected(groupId, optionId) {
        return (this.selectedModifiers[groupId] || []).includes(optionId);
    },

    toggleModifierOption(group, option) {
        const current = [...(this.selectedModifiers[group.id] || [])];
        if (group.selection_type === 'single') {
            this.selectedModifiers[group.id] =
                current.includes(option.id) && !group.is_required
                    ? []
                    : [option.id];
        } else {
            const idx = current.indexOf(option.id);
            if (idx >= 0) current.splice(idx, 1);
            else if (!group.max_select || current.length < group.max_select)
                current.push(option.id);
            this.selectedModifiers[group.id] = current;
        }
    },

    get modifierSelectionValid() {
        if (!this.modifierItem) return false;
        return (this.modifierItem.modifier_groups || []).every((g) => {
            const count = (this.selectedModifiers[g.id] || []).length;
            return !g.is_required || count >= Math.max(1, g.min_select || 1);
        });
    },

    get modifierUnitPrice() {
        if (!this.modifierItem) return 0;
        let extra = 0;
        (this.modifierItem.modifier_groups || []).forEach((g) => {
            const selIds = this.selectedModifiers[g.id] || [];
            (g.options || []).forEach((o) => {
                if (selIds.includes(o.id)) extra += Number(o.price_delta || 0);
            });
        });
        return Number(this.modifierItem.price) + extra;
    },

    confirmModifierAdd() {
        if (!this.modifierItem || !this.modifierSelectionValid) return;
        const selectedOptions = [];
        const optionIds = [];
        (this.modifierItem.modifier_groups || []).forEach((group) => {
            const selIds = this.selectedModifiers[group.id] || [];
            (group.options || []).forEach((opt) => {
                if (selIds.includes(opt.id)) {
                    optionIds.push(opt.id);
                    selectedOptions.push({
                        group_name: group.name,
                        option_name: opt.name,
                        price_delta: opt.price_delta,
                    });
                }
            });
        });
        this.pushCartItem(
            this.modifierItem,
            selectedOptions,
            this.itemNotes.trim(),
            this.itemQty,
            this.modifierUnitPrice,
            optionIds,
        );
        this.modifierItem = null;
    },

    pushCartItem(item, selectedOptions, notes, qty, unitTotal, optionIds = []) {
        const sortedIds = [...optionIds].sort((a, b) => a - b);
        const sig = `${item.id}:${sortedIds.join(',')}:${notes}`;
        const existing = this.cart.find((c) => c.signature === sig);
        if (existing) {
            existing.quantity += qty;
        } else {
            this.cart.push({
                signature: sig,
                menu_item_id: item.id,
                name: item.name,
                unitTotal,
                quantity: qty,
                notes,
                modifier_option_ids: sortedIds,
                selected_options: selectedOptions,
            });
        }
    },

    updateCartQty(index, delta) {
        const item = this.cart[index];
        if (!item) return;
        if (item.quantity + delta <= 0) this.cart.splice(index, 1);
        else item.quantity += delta;
    },

    openDiscountModal() {
        this.tempDiscountAmount = this.discountAmount || 0;
        this.tempDiscountReason = this.discountReason || '';
        this.tempDiscountPin = this.discountAdminPin || '';
        this.discountModalOpen = true;
    },

    applyDiscountModal() {
        this.discountAmount = Math.max(0, Number(this.tempDiscountAmount || 0));
        this.discountReason = this.tempDiscountReason.trim();
        this.discountAdminPin = this.tempDiscountPin.trim();
        this.discountModalOpen = false;
    },

    clearDiscount() {
        this.discountAmount = 0;
        this.discountReason = '';
        this.discountAdminPin = '';
    },

    openWalkInPayment() {
        if (this.cart.length === 0) return;
        this.settlingOrder = null;
        this.paymentMethod = 'cash';
        this.cashReceivedStr = String(this.activePayTotal);
        this.paymentError = '';
        this.paymentModalOpen = true;
    },

    openKioskPayment(order) {
        this.settlingOrder = order;
        this.clearDiscount();
        this.paymentMethod = 'cash';
        this.cashReceivedStr = String(order.total_amount);
        this.paymentError = '';
        this.paymentModalOpen = true;
    },

    pressNumPad(key) {
        if (key === 'C') {
            this.cashReceivedStr = '';
        } else if (key === 'BACK') {
            this.cashReceivedStr = this.cashReceivedStr.slice(0, -1);
        } else if (key === 'EXACT') {
            this.cashReceivedStr = String(this.activePayTotal);
        } else {
            this.cashReceivedStr =
                (this.cashReceivedStr === '0' ? '' : this.cashReceivedStr) +
                String(key);
        }
    },

    addCashPreset(amount) {
        if (amount === 'EXACT') {
            this.cashReceivedStr = String(this.activePayTotal);
            return;
        }
        this.cashReceivedStr = String(amount);
    },

    async submitPayment() {
        if (this.paymentBusy) return;
        if (
            this.paymentMethod === 'cash' &&
            this.cashReceivedNum < this.activePayTotal
        ) {
            this.paymentError = 'Nominal uang tunai kurang dari total tagihan.';
            return;
        }

        this.paymentBusy = true;
        this.paymentError = '';
        try {
            let res;
            const payload = {
                payment_method: this.paymentMethod,
                cash_received:
                    this.paymentMethod === 'cash' ? this.cashReceivedNum : null,
                discount_amount: this.discountAmount || 0,
                discount_reason:
                    this.discountAmount > 0 ? this.discountReason : null,
                admin_pin:
                    this.discountAmount > 0 ? this.discountAdminPin : null,
            };

            if (this.settlingOrder) {
                res = await window.ocJsonRequest(
                    `/pos/orders/${this.settlingOrder.id}/pay`,
                    {
                        method: 'POST',
                        body: JSON.stringify(payload),
                    },
                );
            } else {
                res = await window.ocJsonRequest('/pos/orders', {
                    method: 'POST',
                    body: JSON.stringify({
                        ...payload,
                        order_type: this.orderType,
                        customer_name: this.customerName.trim() || null,
                        items: this.cart.map((c) => ({
                            menu_item_id: c.menu_item_id,
                            quantity: c.quantity,
                            notes: c.notes || null,
                            modifier_option_ids: c.modifier_option_ids,
                        })),
                    }),
                });
                this.cart = [];
                this.customerName = '';
            }

            this.clearDiscount();
            this.paymentModalOpen = false;
            this.receiptOrder = res.order;
            Alpine.store('toasts').add(
                `Transaksi ${res.order.order_number} berhasil dibayar!`,
                'success',
            );
            await this.pollQueue();
        } catch (err) {
            this.paymentError = err.message || 'Gagal memproses pembayaran.';
        } finally {
            this.paymentBusy = false;
        }
    },

    openVoidModal(order) {
        this.voidTargetOrder = order;
        this.voidReason = '';
        this.voidPin = '';
        this.voidError = '';
        this.voidModalOpen = true;
    },

    async submitVoid() {
        if (!this.voidTargetOrder || this.voidBusy) return;
        this.voidBusy = true;
        this.voidError = '';
        try {
            await window.ocJsonRequest(
                `/pos/orders/${this.voidTargetOrder.id}/void`,
                {
                    method: 'POST',
                    body: JSON.stringify({
                        reason: this.voidReason.trim(),
                        admin_pin: this.voidPin.trim(),
                    }),
                },
            );
            this.voidModalOpen = false;
            Alpine.store('toasts').add(
                `Pesanan ${this.voidTargetOrder.order_number} berhasil di-void.`,
                'success',
            );
            await this.pollQueue();
        } catch (err) {
            this.voidError = err.message || 'Gagal melakukan void pesanan.';
        } finally {
            this.voidBusy = false;
        }
    },

    printReceipt() {
        window.print();
    },
}));

// =========================================================================
// 3. POS SHIFT APP
// =========================================================================
Alpine.data('posShiftApp', (configId) => ({
    expectedCash: 0,
    actualCash: 0,
    varianceReason: '',
    init() {
        const data = window.ocReadJsonScript(configId, {});
        this.expectedCash = Number(data.expectedCash || 0);
        this.actualCash = this.expectedCash;
    },
    get variance() {
        return Number(this.actualCash || 0) - this.expectedCash;
    },
}));

// =========================================================================
// 4. KDS (KITCHEN DISPLAY SYSTEM) APP
// =========================================================================
Alpine.data('kdsApp', (configId) => ({
    tickets: [],
    serverOffsetMs: 0,
    nowMs: Date.now(),
    soundEnabled: true,
    busyOrderId: null,
    clockTimer: null,
    pollCleanup: null,

    init() {
        const data = window.ocReadJsonScript(configId, {});
        this.tickets = Array.isArray(data.tickets) ? data.tickets : [];
        if (data.serverTime) {
            this.serverOffsetMs =
                new Date(data.serverTime).getTime() - Date.now();
        }
        this.clockTimer = setInterval(() => {
            this.nowMs = Date.now() + this.serverOffsetMs;
        }, 1000);
        this.pollCleanup = setupVisibilityPoll(() => this.fetchTickets(), 4000);
    },

    destroy() {
        if (this.clockTimer) clearInterval(this.clockTimer);
        if (this.pollCleanup) this.pollCleanup();
    },

    async fetchTickets() {
        try {
            const oldIds = new Set(this.tickets.map((t) => t.id));
            const res = await window.ocJsonRequest('/kds/tickets');
            if (res.serverTime) {
                this.serverOffsetMs =
                    new Date(res.serverTime).getTime() - Date.now();
            }
            const nextTickets = Array.isArray(res.tickets) ? res.tickets : [];
            const hasNew = nextTickets.some(
                (t) => !oldIds.has(t.id) && t.status === 'paid',
            );
            if (hasNew && this.soundEnabled) {
                window.ocPlayChime(880);
            }
            this.tickets = nextTickets;
        } catch {
            // Retry next cycle
        }
    },

    get paidTickets() {
        return this.tickets.filter((t) => t.status === 'paid');
    },
    get preparingTickets() {
        return this.tickets.filter((t) => t.status === 'preparing');
    },
    get readyTickets() {
        return this.tickets.filter((t) => t.status === 'ready');
    },

    elapsedMinutes(ticket) {
        const ref = ticket.paid_at
            ? new Date(ticket.paid_at).getTime()
            : this.nowMs;
        return Math.max(0, Math.floor((this.nowMs - ref) / 60000));
    },

    elapsedLabel(ticket) {
        const ref = ticket.paid_at
            ? new Date(ticket.paid_at).getTime()
            : this.nowMs;
        const sec = Math.max(0, Math.floor((this.nowMs - ref) / 1000));
        const m = String(Math.floor(sec / 60)).padStart(2, '0');
        const s = String(sec % 60).padStart(2, '0');
        return `${m}:${s}`;
    },

    async transition(ticket, nextStatus) {
        if (this.busyOrderId === ticket.id) return;
        this.busyOrderId = ticket.id;
        try {
            const res = await window.ocJsonRequest(
                `/kds/orders/${ticket.id}/status`,
                {
                    method: 'PATCH',
                    body: JSON.stringify({ status: nextStatus }),
                },
            );
            if (nextStatus === 'completed') {
                this.tickets = this.tickets.filter((t) => t.id !== ticket.id);
            } else if (res.ticket) {
                const idx = this.tickets.findIndex((t) => t.id === ticket.id);
                if (idx >= 0) this.tickets.splice(idx, 1, res.ticket);
            }
        } catch (err) {
            Alpine.store('toasts').add(
                err.message || 'Gagal memperbarui status pesanan.',
                'error',
            );
        } finally {
            this.busyOrderId = null;
        }
    },
}));

// =========================================================================
// 5. QUEUE DISPLAY BOARD APP
// =========================================================================
Alpine.data('queueDisplayApp', (configId) => ({
    cafeName: 'Our Coffee',
    preparing: [],
    ready: [],
    soundEnabled: true,
    clockText: '',
    clockTimer: null,
    pollCleanup: null,

    init() {
        const data = window.ocReadJsonScript(configId, {});
        this.cafeName = data.cafeName || 'Our Coffee';
        this.preparing = Array.isArray(data.preparing) ? data.preparing : [];
        this.ready = Array.isArray(data.ready) ? data.ready : [];

        const tick = () => {
            this.clockText =
                new Date().toLocaleTimeString('id-ID', {
                    timeZone: 'Asia/Jakarta',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false,
                }) + ' WIB';
        };
        tick();
        this.clockTimer = setInterval(tick, 1000);
        this.pollCleanup = setupVisibilityPoll(() => this.fetchFeed(), 4000);
    },

    destroy() {
        if (this.clockTimer) clearInterval(this.clockTimer);
        if (this.pollCleanup) this.pollCleanup();
    },

    async fetchFeed() {
        try {
            const oldReadyIds = new Set(this.ready.map((r) => r.id));
            const res = await window.ocJsonRequest('/display/queue/feed');
            const nextReady = Array.isArray(res.ready) ? res.ready : [];
            const hasNewReady = nextReady.some((r) => !oldReadyIds.has(r.id));
            if (hasNewReady && this.soundEnabled) {
                window.ocPlayChime(1046);
            }
            this.preparing = Array.isArray(res.preparing) ? res.preparing : [];
            this.ready = nextReady;
        } catch {
            // Retry on next cycle
        }
    },
}));

// =========================================================================
// 6. ADMIN DASHBOARD APP
// =========================================================================
Alpine.data('adminDashboardApp', (configId) => ({
    days: 7,
    kpis: {
        today_revenue: 0,
        today_orders: 0,
        today_aov: 0,
        low_stock_count: 0,
    },
    revenueTrend: [],
    channelSplit: {
        kiosk_orders: 0,
        kiosk_revenue: 0,
        pos_orders: 0,
        pos_revenue: 0,
    },
    peakHours: [],
    topMenus: [],
    lowStockIngredients: [],
    loading: false,
    pollCleanup: null,

    init() {
        const data = window.ocReadJsonScript(configId, {});
        this.applyData(data);
        this.pollCleanup = setupVisibilityPoll(() => this.refresh(), 30000);
    },

    destroy() {
        if (this.pollCleanup) this.pollCleanup();
    },

    applyData(data) {
        if (!data) return;
        this.days = Number(data.days || 7);
        this.kpis = data.kpis || this.kpis;
        this.revenueTrend = Array.isArray(data.revenueTrend)
            ? data.revenueTrend
            : [];
        this.channelSplit = data.channelSplit || this.channelSplit;
        this.peakHours = Array.isArray(data.peakHours) ? data.peakHours : [];
        this.topMenus = Array.isArray(data.topMenus) ? data.topMenus : [];
        this.lowStockIngredients = Array.isArray(data.lowStockIngredients)
            ? data.lowStockIngredients
            : [];
    },

    async switchDays(nextDays) {
        this.days = nextDays;
        await this.refresh();
    },

    async refresh() {
        this.loading = true;
        try {
            const res = await window.ocJsonRequest(
                `/admin/dashboard/metrics?days=${this.days}`,
            );
            this.applyData(res);
        } catch {
            // Silent on poll error
        } finally {
            this.loading = false;
        }
    },

    get maxTrendRevenue() {
        return Math.max(
            1,
            ...this.revenueTrend.map((r) => Number(r.revenue || 0)),
        );
    },

    get maxPeakOrders() {
        return Math.max(1, ...this.peakHours.map((h) => Number(h.orders || 0)));
    },

    get totalChannelRevenue() {
        return (
            Number(this.channelSplit.kiosk_revenue || 0) +
            Number(this.channelSplit.pos_revenue || 0)
        );
    },

    get kioskPercent() {
        if (this.totalChannelRevenue <= 0) return 0;
        return Math.round(
            (Number(this.channelSplit.kiosk_revenue || 0) /
                this.totalChannelRevenue) *
                100,
        );
    },

    get posPercent() {
        if (this.totalChannelRevenue <= 0) return 0;
        return 100 - this.kioskPercent;
    },

    get donutCircumference() {
        return 2 * Math.PI * 52;
    },

    get kioskDashOffset() {
        return this.donutCircumference * (1 - this.kioskPercent / 100);
    },
}));

// =========================================================================
// 7. ADMIN MODALS (CATEGORIES / MENUS / MODIFIERS / INVENTORY / DISTRIBUTORS / STAFF / SETTINGS)
// =========================================================================
Alpine.data('adminCategoriesApp', () => ({
    modalOpen: false,
    editing: null,
    form: { name: '', icon: 'coffee', sort_order: 0, is_active: true },
    openCreate() {
        this.editing = null;
        this.form = {
            name: '',
            icon: 'coffee',
            sort_order: 0,
            is_active: true,
        };
        this.modalOpen = true;
    },
    openEdit(cat) {
        this.editing = cat;
        this.form = {
            name: cat.name,
            icon: cat.icon || 'coffee',
            sort_order: cat.sort_order ?? 0,
            is_active: Boolean(cat.is_active),
        };
        this.modalOpen = true;
    },
}));

Alpine.data('adminMenusApp', (configId) => ({
    ingredients: [],
    modalOpen: false,
    recipeModalOpen: false,
    editing: null,
    recipeMenu: null,
    form: {
        category_id: '',
        name: '',
        description: '',
        price: 0,
        badge: '',
        sort_order: 0,
        is_available: true,
        is_active: true,
        remove_image: false,
        modifier_group_ids: [],
    },
    recipeRows: [],

    init() {
        const data = window.ocReadJsonScript(configId, {});
        this.ingredients = Array.isArray(data.ingredients)
            ? data.ingredients
            : [];
    },

    openCreate(defaultCatId) {
        this.editing = null;
        this.form = {
            category_id: defaultCatId || '',
            name: '',
            description: '',
            price: 25000,
            badge: '',
            sort_order: 0,
            is_available: true,
            is_active: true,
            remove_image: false,
            modifier_group_ids: [],
        };
        this.modalOpen = true;
    },

    openEdit(menu) {
        this.editing = menu;
        this.form = {
            category_id: String(menu.category_id),
            name: menu.name,
            description: menu.description || '',
            price: menu.price,
            badge: menu.badge || '',
            sort_order: menu.sort_order ?? 0,
            is_available: Boolean(menu.is_available),
            is_active: Boolean(menu.is_active),
            remove_image: false,
            modifier_group_ids: [
                ...(menu.modifier_group_ids || []).map(Number),
            ],
        };
        this.modalOpen = true;
    },

    toggleModifierGroup(id) {
        const num = Number(id);
        const idx = this.form.modifier_group_ids.indexOf(num);
        if (idx >= 0) this.form.modifier_group_ids.splice(idx, 1);
        else this.form.modifier_group_ids.push(num);
    },

    openRecipe(menu) {
        this.recipeMenu = menu;
        this.recipeRows = (menu.recipe_items || []).map((r) => ({
            ingredient_id: String(r.ingredient_id),
            quantity: r.quantity,
        }));
        if (this.recipeRows.length === 0 && this.ingredients.length > 0) {
            this.recipeRows.push({
                ingredient_id: String(this.ingredients[0].id),
                quantity: 1,
            });
        }
        this.recipeModalOpen = true;
    },

    addRecipeRow() {
        const firstId =
            this.ingredients.length > 0 ? String(this.ingredients[0].id) : '';
        this.recipeRows.push({ ingredient_id: firstId, quantity: 1 });
    },

    removeRecipeRow(index) {
        this.recipeRows.splice(index, 1);
    },

    get estimatedRecipeCogs() {
        return this.recipeRows.reduce((sum, row) => {
            const ing = this.ingredients.find(
                (i) => String(i.id) === String(row.ingredient_id),
            );
            if (!ing) return sum;
            return (
                sum + Number(ing.cost_per_unit || 0) * Number(row.quantity || 0)
            );
        }, 0);
    },
}));

Alpine.data('adminModifiersApp', () => ({
    groupModalOpen: false,
    editingGroup: null,
    groupForm: {
        name: '',
        selection_type: 'single',
        is_required: false,
        min_select: 0,
        max_select: 1,
        sort_order: 0,
    },

    optionModalOpen: false,
    optionGroupId: null,
    editingOption: null,
    optionForm: {
        name: '',
        price_delta: 0,
        is_default: false,
        is_available: true,
        ingredient_id: '',
        ingredient_qty: '',
        sort_order: 0,
    },

    openCreateGroup() {
        this.editingGroup = null;
        this.groupForm = {
            name: '',
            selection_type: 'single',
            is_required: false,
            min_select: 0,
            max_select: 1,
            sort_order: 0,
        };
        this.groupModalOpen = true;
    },

    openEditGroup(group) {
        this.editingGroup = group;
        this.groupForm = {
            name: group.name,
            selection_type: group.selection_type,
            is_required: Boolean(group.is_required),
            min_select: group.min_select ?? 0,
            max_select: group.max_select ?? 1,
            sort_order: group.sort_order ?? 0,
        };
        this.groupModalOpen = true;
    },

    openCreateOption(groupId) {
        this.optionGroupId = groupId;
        this.editingOption = null;
        this.optionForm = {
            name: '',
            price_delta: 0,
            is_default: false,
            is_available: true,
            ingredient_id: '',
            ingredient_qty: '',
            sort_order: 0,
        };
        this.optionModalOpen = true;
    },

    openEditOption(groupId, opt) {
        this.optionGroupId = groupId;
        this.editingOption = opt;
        this.optionForm = {
            name: opt.name,
            price_delta: opt.price_delta ?? 0,
            is_default: Boolean(opt.is_default),
            is_available: Boolean(opt.is_available),
            ingredient_id: opt.ingredient_id ? String(opt.ingredient_id) : '',
            ingredient_qty:
                opt.ingredient_qty !== null ? String(opt.ingredient_qty) : '',
            sort_order: opt.sort_order ?? 0,
        };
        this.optionModalOpen = true;
    },
}));

Alpine.data('adminInventoryApp', () => ({
    ingredientModalOpen: false,
    editingIngredient: null,
    ingForm: {
        name: '',
        sku: '',
        unit: 'g',
        current_stock: 0,
        min_stock: 0,
        cost_per_unit: 0,
        default_distributor_id: '',
        is_active: true,
    },

    restockModalOpen: false,
    wasteModalOpen: false,
    opnameModalOpen: false,
    targetIngredient: null,
    restockForm: {
        quantity: '',
        total_cost: '',
        distributor_id: '',
        notes: '',
    },
    wasteForm: { quantity: '', reason: '' },
    opnameForm: { actual_stock: '', reason: '' },

    openCreateIngredient() {
        this.editingIngredient = null;
        this.ingForm = {
            name: '',
            sku: '',
            unit: 'g',
            current_stock: 0,
            min_stock: 100,
            cost_per_unit: 0,
            default_distributor_id: '',
            is_active: true,
        };
        this.ingredientModalOpen = true;
    },

    openEditIngredient(ing) {
        this.editingIngredient = ing;
        this.ingForm = {
            name: ing.name,
            sku: ing.sku || '',
            unit: ing.unit,
            current_stock: ing.current_stock,
            min_stock: ing.min_stock,
            cost_per_unit: ing.cost_per_unit,
            default_distributor_id: ing.default_distributor_id
                ? String(ing.default_distributor_id)
                : '',
            is_active: Boolean(ing.is_active),
        };
        this.ingredientModalOpen = true;
    },

    openRestock(ing) {
        this.targetIngredient = ing;
        this.restockForm = {
            quantity: '',
            total_cost: '',
            distributor_id: ing.default_distributor_id
                ? String(ing.default_distributor_id)
                : '',
            notes: '',
        };
        this.restockModalOpen = true;
    },

    openWaste(ing) {
        this.targetIngredient = ing;
        this.wasteForm = { quantity: '', reason: '' };
        this.wasteModalOpen = true;
    },

    openOpname(ing) {
        this.targetIngredient = ing;
        this.opnameForm = {
            actual_stock: String(ing.current_stock),
            reason: '',
        };
        this.opnameModalOpen = true;
    },
}));

Alpine.data('adminDistributorsApp', () => ({
    modalOpen: false,
    editing: null,
    form: {
        name: '',
        contact_person: '',
        phone: '',
        email: '',
        address: '',
        notes: '',
        is_active: true,
    },
    openCreate() {
        this.editing = null;
        this.form = {
            name: '',
            contact_person: '',
            phone: '',
            email: '',
            address: '',
            notes: '',
            is_active: true,
        };
        this.modalOpen = true;
    },
    openEdit(dist) {
        this.editing = dist;
        this.form = {
            name: dist.name,
            contact_person: dist.contact_person || '',
            phone: dist.phone || '',
            email: dist.email || '',
            address: dist.address || '',
            notes: dist.notes || '',
            is_active: Boolean(dist.is_active),
        };
        this.modalOpen = true;
    },
}));

Alpine.data('adminStaffApp', () => ({
    modalOpen: false,
    editing: null,
    form: {
        name: '',
        email: '',
        password: '',
        role: 'kasir',
        pin: '',
        is_active: true,
    },

    pinModalOpen: false,
    pinTarget: null,
    pinValue: '',

    openCreate() {
        this.editing = null;
        this.form = {
            name: '',
            email: '',
            password: '',
            role: 'kasir',
            pin: '',
            is_active: true,
        };
        this.modalOpen = true;
    },

    openEdit(user) {
        this.editing = user;
        this.form = {
            name: user.name,
            email: user.email,
            password: '',
            role: user.role,
            pin: '',
            is_active: Boolean(user.is_active),
        };
        this.modalOpen = true;
    },

    openPinModal(user) {
        this.pinTarget = user;
        this.pinValue = '';
        this.pinModalOpen = true;
    },
}));

Alpine.data('adminSettingsApp', () => ({
    async copyPairUrl(url) {
        try {
            await navigator.clipboard.writeText(url);
            Alpine.store('toasts').add(
                'Link pairing berhasil disalin ke clipboard.',
                'success',
            );
        } catch {
            Alpine.store('toasts').add('Gagal menyalin link pairing.', 'error');
        }
    },
}));

Alpine.start();
