import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

/**
 * Progressive GSAP choreography below the fold & on hero slide change.
 * Strictly honors prefers-reduced-motion and the portal .a11y-no-motion switch.
 */
export function initGsapMotion() {
    let mm = gsap.matchMedia();

    const setup = () => {
        mm.revert();
        if (document.documentElement.classList.contains('a11y-no-motion')) {
            return;
        }

        mm.add('(prefers-reduced-motion: no-preference)', () => {
            const groups = document.querySelectorAll('[data-gsap-stagger]');
            groups.forEach((group) => {
                const cards = group.querySelectorAll('[data-gsap-item]');
                if (!cards.length) {
                    return;
                }

                gsap.from(cards, {
                    y: 16,
                    opacity: 0,
                    duration: 0.45,
                    stagger: 0.06,
                    ease: 'power2.out',
                    clearProps: 'transform,opacity',
                    scrollTrigger: {
                        trigger: group,
                        start: 'top 88%',
                        once: true,
                    },
                });
            });
        });
    };

    setup();

    window.addEventListener('satker:slide-change', (event) => {
        if (
            document.documentElement.classList.contains('a11y-no-motion') ||
            (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches)
        ) {
            return;
        }
        const activeSlide = event.detail?.slideEl;
        const copyBlock = activeSlide?.querySelector('[data-hero-copy]');
        if (copyBlock) {
            gsap.fromTo(
                copyBlock,
                { y: 8, opacity: 0.85 },
                { y: 0, opacity: 1, duration: 0.35, ease: 'power2.out', clearProps: 'transform,opacity' }
            );
        }
    });

    window.addEventListener('satker:motion-change', (event) => {
        if (event.detail?.reduced) {
            mm.revert();
        } else {
            setup();
        }
    });
}

