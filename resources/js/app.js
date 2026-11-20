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
