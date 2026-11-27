const focusableSelector = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled]):not([type="hidden"])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(',');

window.createFocusTrap = (container, initialFocus = null) => {
    const previouslyFocused = document.activeElement;

    const onKeydown = (event) => {
        if (event.key !== 'Tab') {
            return;
        }

        const nodes = Array.from(container.querySelectorAll(focusableSelector))
            .filter((node) => node.offsetParent !== null);

        if (nodes.length === 0) {
            return;
        }

        const first = nodes[0];
        const last = nodes[nodes.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    };

    container.addEventListener('keydown', onKeydown);
    (initialFocus || container.querySelector(focusableSelector))?.focus();

    return () => {
        container.removeEventListener('keydown', onKeydown);
        previouslyFocused?.focus?.();
    };
};

document.addEventListener('livewire:init', () => {
    // Only action calls: a plain wire:model update must not flicker the submit buttons.
    Livewire.hook('commit', ({ component, commit, succeed, fail }) => {
        if (! commit.calls?.length) {
            return;
        }

        const buttons = component.el.querySelectorAll('form button[type="submit"]');
        const restore = () => buttons.forEach((button) => {
            button.disabled = false;
            button.removeAttribute('aria-busy');
        });

        buttons.forEach((button) => {
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
        });

        succeed(restore);
        fail(restore);
    });
});

// Warning before closing a tab with unsaved Livewire form input.
const dirtyForms = new Set();
const markDirty = (event) => {
    const form = event.target.closest?.('form[wire\\:submit]');
    if (form) {
        dirtyForms.add(form);
    }
};

document.addEventListener('input', markDirty, true);
document.addEventListener('change', markDirty, true);
document.addEventListener('submit', (event) => dirtyForms.delete(event.target), true);
window.addEventListener('beforeunload', (event) => {
    if (dirtyForms.size > 0) {
        event.preventDefault();
        event.returnValue = '';
    }
});

// Modern searchable select registry for all backend admin dropdowns
const satkerSelectInstances = new Set();

window.satkerSearchSelect = (config = {}) => ({
    isOpen: false,
    query: '',
    activeIndex: 0,
    options: [],
    selectedValue: '',
    selectedLabel: '',
    accessibleLabel: '',
    isDisabled: false,
    placeholder: config.placeholder || 'Pilih salah satu...',
    searchPlaceholder: config.searchPlaceholder || 'Cari pilihan...',
    panelStyle: '',
    _native: null,
    _trigger: null,
    _observer: null,
    _onWindowMove: null,

    init() {
        this._native = this.$refs.native;
        this._trigger = this.$refs.trigger;
        this.syncFromNative();
        satkerSelectInstances.add(this);

        if (this._native && typeof MutationObserver !== 'undefined') {
            this._observer = new MutationObserver(() => this.syncFromNative());
            this._observer.observe(this._native, {
                childList: true,
                subtree: true,
                attributes: true,
                characterData: true,
            });
        }

        this._onWindowMove = (event) => {
            if (! this.isOpen) {
                return;
            }
            if (event?.type === 'scroll' && this.$refs.panel && this.$refs.panel.contains(event.target)) {
                return;
            }
            this.positionPanel();
        };

        window.addEventListener('resize', this._onWindowMove, { passive: true });
        window.addEventListener('scroll', this._onWindowMove, { passive: true, capture: true });
    },

    destroy() {
        satkerSelectInstances.delete(this);
        this._observer?.disconnect();
        if (this._onWindowMove) {
            window.removeEventListener('resize', this._onWindowMove);
            window.removeEventListener('scroll', this._onWindowMove, true);
        }
    },

    syncFromNative() {
        const el = this._native || this.$refs.native;
        if (! el) {
            return;
        }

        this.isDisabled = Boolean(el.disabled);
        const id = el.getAttribute('id');
        const labelEl = id ? document.querySelector(`label[for="${CSS.escape(id)}"]`) : null;
        this.accessibleLabel = el.getAttribute('aria-label') || labelEl?.textContent?.trim() || this.placeholder;

        const opts = Array.from(el.options).map((opt, idx) => ({
            key: `${idx}:${opt.value}`,
            value: String(opt.value),
            label: (opt.textContent || '').trim(),
            group: opt.parentElement?.tagName === 'OPTGROUP' ? (opt.parentElement.getAttribute('label') || '') : '',
            disabled: Boolean(opt.disabled),
        }));
        this.options = opts;

        const currentVal = String(el.value ?? '');
        const match = opts.find((o) => o.value === currentVal) || opts[el.selectedIndex] || opts[0];
        this.selectedValue = match ? match.value : '';
        this.selectedLabel = match ? match.label : '';
    },

    get filteredOptions() {
        const q = this.query.trim().toLowerCase();
        if (! q) {
            return this.options;
        }

        return this.options.filter((opt) =>
            opt.label.toLowerCase().includes(q) || (opt.group && opt.group.toLowerCase().includes(q))
        );
    },

    toggle() {
        if (this.isDisabled) {
            return;
        }
        if (this.isOpen) {
            this.close(false);
        } else {
            this.open();
        }
    },

    open() {
        if (this.isDisabled) {
            return;
        }
        satkerSelectInstances.forEach((inst) => {
            if (inst !== this && inst.isOpen) {
                inst.close(false);
            }
        });

        this.syncFromNative();
        this.query = '';
        this.isOpen = true;
        const currentIdx = this.options.findIndex((o) => o.value === this.selectedValue);
        this.activeIndex = currentIdx >= 0 ? currentIdx : 0;

        this.$nextTick(() => {
            this.positionPanel();
            this.$refs.searchInput?.focus({ preventScroll: true });
            this.scrollActiveIntoView();
        });
    },

    close(returnFocus = false) {
        if (! this.isOpen) {
            return;
        }
        this.isOpen = false;
        this.query = '';
        if (returnFocus) {
            this.$nextTick(() => this.$refs.trigger?.focus());
        }
    },

    handleOutside(event) {
        if (this.$refs.trigger && this.$refs.trigger.contains(event.target)) {
            return;
        }
        this.close(false);
    },

    positionPanel() {
        const trigger = this.$refs.trigger;
        if (! trigger) {
            return;
        }
        const rect = trigger.getBoundingClientRect();
        const margin = 8;
        const width = Math.min(Math.max(rect.width, 240), window.innerWidth - margin * 2);
        const left = Math.max(margin, Math.min(rect.left, window.innerWidth - width - margin));
        const spaceBelow = window.innerHeight - rect.bottom - margin;
        const spaceAbove = rect.top - margin;
        const openUp = spaceBelow < 240 && spaceAbove > spaceBelow;

        if (openUp) {
            const bottom = Math.max(margin, window.innerHeight - rect.top + 6);
            this.panelStyle = `left:${Math.round(left)}px;bottom:${Math.round(bottom)}px;width:${Math.round(width)}px;`;
        } else {
            const top = Math.max(margin, rect.bottom + 6);
            this.panelStyle = `left:${Math.round(left)}px;top:${Math.round(top)}px;width:${Math.round(width)}px;`;
        }
    },

    move(delta) {
        const list = this.filteredOptions;
        if (list.length === 0) {
            return;
        }
        this.activeIndex = (this.activeIndex + delta + list.length) % list.length;
        this.$nextTick(() => this.scrollActiveIntoView());
    },

    scrollActiveIntoView() {
        const box = this.$refs.listbox;
        if (! box) {
            return;
        }
        const item = box.querySelector(`[data-opt-idx="${this.activeIndex}"]`);
        item?.scrollIntoView({ block: 'nearest' });
    },

    selectHighlighted() {
        const opt = this.filteredOptions[this.activeIndex];
        if (opt && ! opt.disabled) {
            this.selectOption(opt);
        }
    },

    selectOption(opt) {
        if (! opt || opt.disabled) {
            return;
        }
        const el = this._native || this.$refs.native;
        this.selectedValue = opt.value;
        this.selectedLabel = opt.label;

        if (el) {
            Array.from(el.options).forEach((o) => {
                o.selected = String(o.value) === opt.value;
            });
            el.value = opt.value;
            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.dispatchEvent(new Event('change', { bubbles: true }));
        }

        this.close(true);
    },
});

document.addEventListener('livewire:init', () => {
    Livewire.hook('morph.updated', () => {
        window.requestAnimationFrame(() => {
            satkerSelectInstances.forEach((inst) => inst.syncFromNative());
        });
    });
});
