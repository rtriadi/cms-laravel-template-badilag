const focusableSelector = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled]):not([type="hidden"])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(',');

const createFocusTrap = (container, initialFocus = null) => {
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

window.createFocusTrap = createFocusTrap;

const isReducedMotionActive = () =>
    Boolean(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) ||
    document.documentElement.classList.contains('a11y-no-motion');

const initNavigation = () => {
    const navGroups = document.querySelectorAll('[data-nav-group]');
    const closeAllSubmenus = () => {
        navGroups.forEach((group) => {
            const btn = group.querySelector('[data-nav-trigger]');
            const submenu = group.querySelector('[data-nav-submenu]');
            if (btn && submenu) {
                btn.setAttribute('aria-expanded', 'false');
                submenu.hidden = true;
            }
        });
    };

    navGroups.forEach((group) => {
        const btn = group.querySelector('[data-nav-trigger]');
        const submenu = group.querySelector('[data-nav-submenu]');
        if (!btn || !submenu) {
            return;
        }

        btn.addEventListener('click', (event) => {
            event.stopPropagation();
            const isCurrentlyOpen = !submenu.hidden;
            closeAllSubmenus();
            if (!isCurrentlyOpen) {
                submenu.hidden = false;
                btn.setAttribute('aria-expanded', 'true');
            }
        });
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest?.('[data-nav-group]')) {
            closeAllSubmenus();
        }
    });

    const mobileOpen = document.getElementById('mobile-menu-open');
    const mobileClose = document.getElementById('mobile-menu-close');
    const mobileDrawer = document.getElementById('mobile-nav-drawer');
    const mobileNavContent = document.getElementById('mobile-nav-content');
    const desktopNav = document.getElementById('desktop-primary-nav');
    let drawerRelease = null;

    const copyLinkAttributes = (source, target) => {
        target.setAttribute('href', source.getAttribute('href') || '#');
        const tgt = source.getAttribute('target');
        const rel = source.getAttribute('rel');
        if (tgt) {
            target.setAttribute('target', tgt);
        }
        if (rel) {
            target.setAttribute('rel', rel);
        }
        target.textContent = (source.textContent || '').trim();
    };

    const hydrateMobileNav = () => {
        if (!mobileNavContent || mobileNavContent.dataset.hydrated || !desktopNav) {
            return;
        }

        const frag = document.createDocumentFragment();
        desktopNav.querySelectorAll('[data-nav-group]').forEach((group) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'border-b border-hairline pb-3';

            const trigger = group.querySelector('[data-nav-trigger]');
            const submenu = group.querySelector('[data-nav-submenu]');

            if (trigger && submenu) {
                const titleEl = document.createElement('p');
                titleEl.className = 'font-serif text-sm font-bold text-primary-deep';
                const labelSpan = trigger.querySelector('span');
                titleEl.textContent = ((labelSpan ? labelSpan.textContent : trigger.textContent) || '').trim();
                wrapper.appendChild(titleEl);

                const childList = document.createElement('ul');
                childList.className = 'mt-2 space-y-1';
                let lastLi = null;

                Array.from(submenu.children).forEach((el) => {
                    if (el.tagName === 'P') {
                        const li = document.createElement('li');
                        const span = document.createElement('span');
                        span.className = 'block px-2 py-2 text-xs font-bold text-ink-muted uppercase';
                        span.textContent = (el.textContent || '').trim();
                        li.appendChild(span);
                        childList.appendChild(li);
                        lastLi = li;
                    } else if (el.tagName === 'A') {
                        const li = document.createElement('li');
                        const link = document.createElement('a');
                        link.className = 'flex min-h-11 items-center rounded px-2 text-xs font-medium text-ink hover:bg-primary-soft hover:text-primary';
                        copyLinkAttributes(el, link);
                        li.appendChild(link);
                        childList.appendChild(li);
                        lastLi = li;
                    } else if (el.tagName === 'UL') {
                        const grandList = document.createElement('ul');
                        grandList.className = 'border-s-2 border-hairline ps-3 ml-2 space-y-1';
                        el.querySelectorAll('a').forEach((gAnchor) => {
                            const gLi = document.createElement('li');
                            const gLink = document.createElement('a');
                            gLink.className = 'flex min-h-11 items-center rounded px-2 text-xs text-ink-body hover:bg-primary-soft hover:text-primary';
                            copyLinkAttributes(gAnchor, gLink);
                            gLi.appendChild(gLink);
                            grandList.appendChild(gLi);
                        });
                        (lastLi || childList).appendChild(grandList);
                    }
                });

                wrapper.appendChild(childList);
            } else {
                const direct = group.querySelector('a');
                if (direct) {
                    const link = document.createElement('a');
                    link.className = 'flex min-h-11 items-center font-serif text-sm font-bold text-primary-deep hover:text-primary';
                    copyLinkAttributes(direct, link);
                    wrapper.appendChild(link);
                }
            }

            frag.appendChild(wrapper);
        });

        mobileNavContent.appendChild(frag);
        mobileNavContent.dataset.hydrated = '1';
    };

    const closeDrawer = (restoreFocus = false) => {
        if (!mobileDrawer || !mobileOpen || mobileDrawer.hidden) {
            return;
        }
        drawerRelease?.();
        drawerRelease = null;
        mobileDrawer.hidden = true;
        mobileOpen.setAttribute('aria-expanded', 'false');
        if (restoreFocus) {
            mobileOpen.focus();
        }
    };

    mobileOpen?.addEventListener('click', () => {
        if (!mobileDrawer) {
            return;
        }
        hydrateMobileNav();
        mobileDrawer.hidden = false;
        mobileOpen.setAttribute('aria-expanded', 'true');
        drawerRelease = createFocusTrap(mobileDrawer, mobileClose);
    });

    mobileClose?.addEventListener('click', () => closeDrawer(true));
    mobileDrawer?.addEventListener('click', (event) => {
        if (event.target === mobileDrawer) {
            closeDrawer(true);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeAllSubmenus();
            if (mobileDrawer && !mobileDrawer.hidden) {
                closeDrawer(true);
            }
        }
    });
};

const initPublicCommandSearch = () => {
    const modal = document.getElementById('public-search-modal');
    if (!modal) {
        return;
    }

    const input = document.getElementById('modal-search-input');
    const closeBtn = document.getElementById('public-search-close');
    const triggers = document.querySelectorAll('[data-open-public-search]');
    let releaseTrap = null;

    const openModal = () => {
        if (!modal.hidden) {
            return;
        }
        modal.hidden = false;
        triggers.forEach((btn) => btn.setAttribute('aria-expanded', 'true'));
        releaseTrap = createFocusTrap(modal, input);
    };

    const closeModal = (restoreFocus = true) => {
        if (modal.hidden) {
            return;
        }
        if (restoreFocus) {
            releaseTrap?.();
        }
        releaseTrap = null;
        modal.hidden = true;
        triggers.forEach((btn) => btn.setAttribute('aria-expanded', 'false'));
    };

    triggers.forEach((btn) => {
        btn.addEventListener('click', openModal);
    });

    closeBtn?.addEventListener('click', () => closeModal(true));
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal(true);
        }
    });

    document.addEventListener('keydown', (event) => {
        const tag = (event.target?.tagName || '').toLowerCase();
        const isTyping = tag === 'input' || tag === 'textarea' || tag === 'select' || event.target?.isContentEditable;

        if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k' && !isTyping) {
            event.preventDefault();
            openModal();
        } else if (event.key === 'Escape' && !modal.hidden) {
            closeModal(true);
        }
    });
};

const initScrollReveal = () => {
    const revealItems = Array.from(document.querySelectorAll('.pub-reveal'));

    if (!isReducedMotionActive() && 'IntersectionObserver' in window && revealItems.length > 0) {
        document.documentElement.classList.add('pub-motion-ready');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { rootMargin: '0px 0px -32px 0px', threshold: 0.05 });

        revealItems.forEach((el) => observer.observe(el));
    } else {
        revealItems.forEach((el) => el.classList.add('is-visible'));
    }
};

const initLinkPrefetch = () => {
    const prefetched = new Set();
    const adminPrefix = document.body?.dataset?.adminPrefix || '';

    const canPrefetch = (anchor) => {
        if (!anchor || !anchor.href || anchor.target === '_blank' || anchor.hasAttribute('download') || anchor.hasAttribute('data-no-prefetch')) {
            return false;
        }
        try {
            const u = new URL(anchor.href, window.location.origin);
            if (u.origin !== window.location.origin || u.href === window.location.href || u.hash) {
                return false;
            }
            if (adminPrefix !== '' && (u.pathname === adminPrefix || u.pathname.startsWith(`${adminPrefix}/`))) {
                return false;
            }
            if (u.pathname.startsWith('/setup') || u.pathname.startsWith('/storage') || u.pathname.startsWith('/login') || u.pathname.startsWith('/sitemap.xml')) {
                return false;
            }
            return !prefetched.has(u.href);
        } catch {
            return false;
        }
    };

    const triggerPrefetch = (href) => {
        if (prefetched.has(href) || prefetched.size > 20) {
            return;
        }
        prefetched.add(href);
        const link = document.createElement('link');
        link.rel = 'prefetch';
        link.href = href;
        link.as = 'document';
        document.head.appendChild(link);
    };

    let hoverTimer = null;
    document.addEventListener('mouseover', (e) => {
        const a = e.target.closest?.('a[href]');
        if (!canPrefetch(a)) {
            return;
        }
        clearTimeout(hoverTimer);
        hoverTimer = setTimeout(() => triggerPrefetch(a.href), 65);
    }, { passive: true });

    document.addEventListener('touchstart', (e) => {
        const a = e.target.closest?.('a[href]');
        if (canPrefetch(a)) {
            triggerPrefetch(a.href);
        }
    }, { passive: true });
};

const initAccessibilityWidget = () => {
    const STORAGE_KEY = 'satkercms_a11y_v1';
    const toggleBtn = document.getElementById('a11y-toggle-btn');
    const closeBtn = document.getElementById('a11y-close-btn');
    const panel = document.getElementById('a11y-panel');
    const scaleLabel = document.getElementById('a11y-scale-label');
    const ttsStatus = document.getElementById('a11y-tts-status');

    if (!toggleBtn || !panel) {
        return;
    }

    const defaultState = { scale: 100, hc: false, dyslexia: false, links: false, motion: false };
    let state = { ...defaultState };

    try {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (saved) {
            state = { ...defaultState, ...JSON.parse(saved) };
        }
    } catch {
        state = { ...defaultState };
    }

    const applyState = () => {
        const clampedScale = Math.min(200, Math.max(100, Number(state.scale) || 100));
        state.scale = clampedScale;
        document.documentElement.style.fontSize = `${clampedScale}%`;
        document.documentElement.classList.toggle('a11y-hc', Boolean(state.hc));
        document.documentElement.classList.toggle('a11y-dyslexia', Boolean(state.dyslexia));
        document.documentElement.classList.toggle('a11y-highlight-links', Boolean(state.links));
        document.documentElement.classList.toggle('a11y-no-motion', Boolean(state.motion));

        if (scaleLabel) {
            scaleLabel.textContent = `${clampedScale}%`;
        }

        ['hc', 'dyslexia', 'links', 'motion'].forEach((key) => {
            const btn = panel.querySelector(`[data-a11y-toggle="${key}"]`);
            const badge = panel.querySelector(`[data-a11y-state="${key}"]`);
            const active = Boolean(state[key]);
            if (btn) {
                btn.setAttribute('aria-pressed', active ? 'true' : 'false');
            }
            if (badge) {
                badge.textContent = active ? 'Aktif' : 'Nonaktif';
                badge.classList.toggle('text-primary', active);
                badge.classList.toggle('font-bold', active);
            }
        });

        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
        } catch {
            // Ignore storage quota issues
        }

        window.dispatchEvent(new CustomEvent('satker:motion-change', {
            detail: { reduced: isReducedMotionActive() },
        }));
    };

    const openPanel = () => {
        panel.hidden = false;
        toggleBtn.setAttribute('aria-expanded', 'true');
        closeBtn?.focus();
    };

    const closePanel = (returnFocus = false) => {
        panel.hidden = true;
        toggleBtn.setAttribute('aria-expanded', 'false');
        if (returnFocus) {
            toggleBtn.focus();
        }
    };

    toggleBtn.addEventListener('click', () => {
        if (panel.hidden) {
            openPanel();
        } else {
            closePanel(false);
        }
    });

    document.querySelectorAll('[data-open-a11y]').forEach((trigger) => {
        trigger.addEventListener('click', () => openPanel());
    });

    closeBtn?.addEventListener('click', () => closePanel(true));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !panel.hidden) {
            closePanel(true);
        }
    });

    document.getElementById('a11y-scale-up')?.addEventListener('click', () => {
        state.scale = Math.min(200, state.scale + 25);
        applyState();
    });

    document.getElementById('a11y-scale-down')?.addEventListener('click', () => {
        state.scale = Math.max(100, state.scale - 25);
        applyState();
    });

    panel.querySelectorAll('[data-a11y-toggle]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const key = btn.getAttribute('data-a11y-toggle');
            if (key && Object.prototype.hasOwnProperty.call(state, key)) {
                state[key] = !state[key];
                applyState();
            }
        });
    });

    document.getElementById('a11y-reset-btn')?.addEventListener('click', () => {
        state = { ...defaultState };
        if ('speechSynthesis' in window) {
            window.speechSynthesis.cancel();
        }
        applyState();
        if (ttsStatus) {
            ttsStatus.textContent = 'Pengaturan aksesibilitas dikembalikan ke awal.';
        }
    });

    const speakText = (text) => {
        if (!('speechSynthesis' in window)) {
            if (ttsStatus) {
                ttsStatus.textContent = 'Peramban tidak mendukung fitur pembaca suara Web Speech API.';
            }
            return;
        }
        const cleanText = (text || '').replace(/\s+/g, ' ').trim();
        if (!cleanText) {
            if (ttsStatus) {
                ttsStatus.textContent = 'Tidak ada teks yang dipilih untuk dibacakan.';
            }
            return;
        }
        window.speechSynthesis.cancel();
        const utterance = new SpeechSynthesisUtterance(cleanText.slice(0, 6000));
        utterance.lang = 'id-ID';
        utterance.rate = 0.95;
        utterance.onstart = () => {
            if (ttsStatus) {
                ttsStatus.textContent = 'Sedang membacakan teks (id-ID)...';
            }
        };
        utterance.onend = () => {
            if (ttsStatus) {
                ttsStatus.textContent = 'Pembacaan selesai.';
            }
        };
        window.speechSynthesis.speak(utterance);
    };

    document.getElementById('a11y-tts-read-page')?.addEventListener('click', () => {
        const mainEl = document.getElementById('konten-utama');
        speakText(mainEl ? mainEl.innerText : document.body.innerText);
    });

    document.getElementById('a11y-tts-read-selection')?.addEventListener('click', () => {
        const selected = window.getSelection ? window.getSelection().toString() : '';
        speakText(selected);
    });

    document.getElementById('a11y-tts-stop')?.addEventListener('click', () => {
        if ('speechSynthesis' in window) {
            window.speechSynthesis.cancel();
        }
        if (ttsStatus) {
            ttsStatus.textContent = 'Pembacaan suara dihentikan.';
        }
    });

    applyState();
};

const initHeroSlider = () => {
    const container = document.querySelector('[data-hero-slider]');
    if (!container) {
        return;
    }

    const slides = Array.from(container.querySelectorAll('[data-hero-slide]'));
    if (slides.length <= 1) {
        return;
    }

    const dots = Array.from(container.querySelectorAll('[data-slider-dot]'));
    const prevBtn = container.querySelector('[data-slider-prev]');
    const nextBtn = container.querySelector('[data-slider-next]');
    const pauseBtn = container.querySelector('[data-slider-pause]');
    let index = 0;
    let autoTimer = null;
    let paused = false;

    const hydrateSlideImage = (slideEl) => {
        const img = slideEl?.querySelector('img[data-lazy-src]');
        if (!img) {
            return;
        }
        const lazySrc = img.getAttribute('data-lazy-src');
        const lazySrcset = img.getAttribute('data-lazy-srcset');
        const lazySizes = img.getAttribute('data-lazy-sizes');
        if (lazySrcset) {
            img.setAttribute('srcset', lazySrcset);
            img.removeAttribute('data-lazy-srcset');
        }
        if (lazySizes) {
            img.setAttribute('sizes', lazySizes);
            img.removeAttribute('data-lazy-sizes');
        }
        if (lazySrc) {
            img.setAttribute('src', lazySrc);
            img.removeAttribute('data-lazy-src');
        }
    };

    const showSlide = (nextIdx) => {
        index = (nextIdx + slides.length) % slides.length;
        hydrateSlideImage(slides[index]);
        slides.forEach((el, i) => {
            el.hidden = i !== index;
        });
        dots.forEach((dot, i) => {
            dot.classList.toggle('bg-gold', i === index);
            dot.classList.toggle('text-ink', i === index);
            dot.setAttribute('aria-current', i === index ? 'true' : 'false');
        });
        window.dispatchEvent(new CustomEvent('satker:slide-change', {
            detail: { index, slideEl: slides[index] },
        }));
    };

    const stopAuto = () => {
        if (autoTimer) {
            window.clearInterval(autoTimer);
            autoTimer = null;
        }
    };

    const startAuto = () => {
        stopAuto();
        if (!paused && !isReducedMotionActive() && slides.length > 1 && !document.hidden) {
            autoTimer = window.setInterval(() => showSlide(index + 1), 7000);
        }
    };

    const setPaused = (value) => {
        paused = value;
        if (pauseBtn) {
            pauseBtn.setAttribute('aria-pressed', paused ? 'true' : 'false');
            pauseBtn.textContent = paused ? 'Lanjutkan' : 'Jeda';
        }
        if (paused) {
            stopAuto();
        } else {
            startAuto();
        }
    };

    prevBtn?.addEventListener('click', () => {
        showSlide(index - 1);
        startAuto();
    });
    nextBtn?.addEventListener('click', () => {
        showSlide(index + 1);
        startAuto();
    });
    dots.forEach((dot) => {
        dot.addEventListener('click', () => {
            showSlide(Number(dot.getAttribute('data-slider-dot')));
            startAuto();
        });
    });
    pauseBtn?.addEventListener('click', () => setPaused(!paused));

    container.addEventListener('mouseenter', () => {
        if (!paused) {
            stopAuto();
        }
    });
    container.addEventListener('mouseleave', startAuto);
    container.addEventListener('focusin', () => {
        if (!paused) {
            stopAuto();
        }
    });
    container.addEventListener('focusout', (event) => {
        if (!container.contains(event.relatedTarget)) {
            startAuto();
        }
    });
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            stopAuto();
        } else {
            startAuto();
        }
    });
    window.addEventListener('satker:motion-change', (event) => {
        if (event.detail?.reduced) {
            setPaused(true);
        }
    });

    showSlide(0);
    setPaused(isReducedMotionActive());
};

const initReadProgress = () => {
    const bar = document.querySelector('[data-read-progress]');
    if (!bar) {
        return;
    }

    let ticking = false;
    const update = () => {
        ticking = false;
        const doc = document.documentElement;
        const max = Math.max(1, doc.scrollHeight - window.innerHeight);
        const ratio = Math.min(1, Math.max(0, window.scrollY / max));
        bar.style.setProperty('--read-progress', ratio.toFixed(4));
    };

    const onScroll = () => {
        if (!ticking) {
            ticking = true;
            window.requestAnimationFrame(update);
        }
    };

    window.addEventListener('scroll', onScroll, { passive: true });
    update();
};

const initSimtepaTabs = () => {
    document.querySelectorAll('[data-simtepa-embed-block]').forEach((container) => {
        if (container.dataset.simtepaBound) {
            return;
        }
        container.dataset.simtepaBound = '1';

        const iframe = container.querySelector('[data-simtepa-iframe]');
        const htmlLink = container.querySelector('[data-simtepa-html-link]');
        const jsonLink = container.querySelector('[data-simtepa-json-link]');
        const buttons = Array.from(container.querySelectorAll('[data-simtepa-tab-btn]'));

        buttons.forEach((btn) => {
            btn.addEventListener('click', (e) => {
                const htmlUrl = btn.getAttribute('data-html-url');
                const jsonUrl = btn.getAttribute('data-json-url');
                if (!iframe || !htmlUrl) {
                    return;
                }
                e.preventDefault();
                iframe.src = htmlUrl;
                if (htmlLink) {
                    htmlLink.href = htmlUrl;
                }
                if (jsonLink && jsonUrl) {
                    jsonLink.href = jsonUrl;
                }
                buttons.forEach((b) => {
                    const active = b === btn;
                    b.setAttribute('aria-selected', active ? 'true' : 'false');
                    b.classList.toggle('bg-primary', active);
                    b.classList.toggle('text-white', active);
                    b.classList.toggle('border', !active);
                    b.classList.toggle('border-hairline', !active);
                    b.classList.toggle('bg-white', !active);
                    b.classList.toggle('text-ink', !active);
                });
            });
        });
    });
};

const initPostCommentCaptcha = () => {
    const img = document.getElementById('comment-captcha-img');
    const text = document.getElementById('comment-captcha-text');
    const refresh = document.getElementById('comment-captcha-refresh');
    const mode = document.getElementById('comment-captcha-mode');

    if (!img || !refresh || !mode) {
        return;
    }

    const imgEndpoint = img.dataset.captchaImgUrl || '/captcha-image';
    const textEndpoint = img.dataset.captchaTextUrl || '/captcha-text';
    let textMode = false;

    const loadImage = () => {
        img.hidden = false;
        if (text) {
            text.hidden = true;
        }
        img.src = `${imgEndpoint}?t=${Date.now()}`;
    };

    const loadText = async () => {
        if (!text) {
            loadImage();
            return;
        }
        try {
            const response = await fetch(textEndpoint, { cache: 'no-store' });
            text.textContent = (await response.text()).trim();
            text.hidden = false;
            img.hidden = true;
        } catch {
            textMode = false;
            mode.setAttribute('aria-expanded', 'false');
            mode.textContent = 'Mode teks';
            loadImage();
        }
    };

    refresh.addEventListener('click', () => (textMode ? loadText() : loadImage()));

    mode.addEventListener('click', () => {
        textMode = !textMode;
        mode.setAttribute('aria-expanded', textMode ? 'true' : 'false');
        mode.textContent = textMode ? 'Mode gambar' : 'Mode teks';
        if (textMode) {
            loadText();
        } else {
            loadImage();
        }
    });
};

const initShareCopy = () => {
    document.querySelectorAll('[data-copy-share-url]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const url = btn.getAttribute('data-copy-share-url') || window.location.href;
            const status = btn.parentElement?.querySelector('[data-share-status]');
            try {
                await navigator.clipboard.writeText(url);
                if (status) {
                    status.textContent = 'Tautan disalin';
                    window.setTimeout(() => {
                        status.textContent = '';
                    }, 2500);
                }
            } catch {
                if (status) {
                    status.textContent = url;
                }
            }
        });
    });
};

const initProgressiveEnhancements = () => {
    const heroStage = document.querySelector('[data-hero-3d-stage]');
    const hasGsapTargets = document.querySelector('[data-gsap-stagger]') || document.querySelector('[data-hero-slider]');
    let hero3dController = null;
    let currentSlideIndex = 0;

    const scheduleIdle = (fn) => {
        if ('requestIdleCallback' in window) {
            window.requestIdleCallback(fn, { timeout: 1200 });
        } else {
            window.setTimeout(fn, 250);
        }
    };

    if (hasGsapTargets && !isReducedMotionActive()) {
        scheduleIdle(() => {
            import('./motion-gsap.js')
                .then(({ initGsapMotion }) => initGsapMotion())
                .catch(() => {});
        });
    }

    const canMount3D = () =>
        Boolean(heroStage) &&
        !isReducedMotionActive() &&
        !document.documentElement.classList.contains('a11y-hc') &&
        Boolean(window.matchMedia && window.matchMedia('(min-width: 1024px)').matches);

    const boot3D = () => {
        if (!canMount3D() || hero3dController) {
            return;
        }
        import('./hero-3d.js')
            .then(({ mountHero3D }) => {
                if (!canMount3D()) {
                    return;
                }
                hero3dController = mountHero3D(heroStage);
                hero3dController?.setSlide(currentSlideIndex);
            })
            .catch(() => {});
    };

    if (canMount3D()) {
        scheduleIdle(boot3D);
    }

    window.addEventListener('satker:slide-change', (event) => {
        currentSlideIndex = Number(event.detail?.index ?? 0);
        hero3dController?.setSlide(currentSlideIndex);
    });

    window.addEventListener('satker:motion-change', (event) => {
        if (event.detail?.reduced || document.documentElement.classList.contains('a11y-hc')) {
            hero3dController?.destroy();
            hero3dController = null;
        } else if (canMount3D() && !hero3dController) {
            scheduleIdle(boot3D);
        }
    });
};

const boot = () => {
    initNavigation();
    initPublicCommandSearch();
    initScrollReveal();
    initLinkPrefetch();
    initAccessibilityWidget();
    initHeroSlider();
    initReadProgress();
    initSimtepaTabs();
    initPostCommentCaptcha();
    initShareCopy();
    initProgressiveEnhancements();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
} else {
    boot();
}
