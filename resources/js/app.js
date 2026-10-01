//

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

// import './echo';
/**
 * Alpine + Livewire are started from HERE (bundled), so every Alpine.data() component is
 * registered BEFORE Alpine scans the page. (Relying on the auto-injected Livewire script created a
 * race: components were undefined, `message` resolved to <textarea id="message"> on window.)
 */
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import { animate, stagger, inView } from 'motion';

/* ------------------------------------------------------------------ *
 * Motion system: the 7 principles as reusable tokens
 *  1 Timing & spacing : three durations only (fast/base/slow), spaced on a 1 : 2 : 4 scale
 *  2 Easing           : ease-out for entrances, ease-in-out for moves, back-out for playful settles
 *  3 Anticipation     : press = small counter-move before release
 *  4 Squash & stretch : volume-preserving scale on press/release and toast landing
 *  5 Follow-through   : children settle after their parent, staggered
 *  6 Arcs             : x and y use different easings, so objects travel on a curve
 *  7 Staging          : hero plays one focal thing at a time; reveals fire once
 * ------------------------------------------------------------------ */
const T = { fast: 0.18, base: 0.38, slow: 0.7 };
const EASE = { out: [0.22, 1, 0.36, 1], inOut: [0.65, 0, 0.35, 1], back: [0.34, 1.45, 0.64, 1], linear: 'linear' };
const reduced = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const gf = {
    // 5. follow-through: direct children settle one after another after their parent lands
    follow(el, delay = 0.12) {
        if (!el || reduced()) return;
        animate(el.querySelectorAll(':scope > *'), { opacity: [0, 1], y: [14, 0] }, { duration: T.base, delay: stagger(0.05, { startDelay: delay }), ease: EASE.out });
    },
    // 6. arc: sideways travel is linear-ish, vertical travel eases, so the path curves
    arc(el, from = { x: 60, y: -70 }, delay = 0) {
        if (reduced()) { el.style.opacity = 1; return; }
        animate(el, { opacity: [0, 1] }, { duration: T.fast, delay });
        animate(el, { x: [from.x, 0] }, { duration: T.slow * 1.3, delay, ease: EASE.out });
        animate(el, { y: [from.y, 0] }, { duration: T.slow * 1.3, delay, ease: EASE.back });
    },
    // 4. squash & stretch, volume preserved (x * y ≈ 1)
    press(el) { animate(el, { scaleX: 1.05, scaleY: 0.93 }, { duration: T.fast * 0.6, ease: EASE.out }); },
    release(el) { animate(el, { scaleX: [0.96, 1], scaleY: [1.06, 1] }, { type: 'spring', stiffness: 520, damping: 13 }); },
};
window.gf = gf;

function initMotion() {
    const hero = document.querySelector('[data-hero]');
    if (hero) {
        if (reduced()) { hero.querySelectorAll(':scope > *').forEach((e) => (e.style.opacity = 1)); }
        else {
            // 7. staging: text → visual → chips, one focal point at a time
            animate(hero.querySelectorAll(':scope > *'), { opacity: [0, 1], y: [22, 0] }, { duration: T.slow, delay: stagger(0.09), ease: EASE.out });
        }
    }
    document.querySelectorAll('[data-chip]').forEach((c, i) => gf.arc(c, { x: i % 2 ? -50 : 50, y: -60 }, 0.9 + i * 0.28));

    // Scroll reveals: fire once, staggered by sibling position (max 4 steps so nothing feels slow)
    document.querySelectorAll('[data-reveal]').forEach((el) => {
        if (reduced()) { el.style.opacity = 1; return; }
        const i = Math.min([...el.parentElement.querySelectorAll(':scope > [data-reveal]')].indexOf(el), 4);
        inView(el, () => { animate(el, { opacity: [0, 1], y: [24, 0] }, { duration: T.slow, delay: i * 0.08, ease: EASE.out }); }, { margin: '0px 0px -12% 0px' });
    });
    window.__motionReady = true;
}
document.addEventListener('DOMContentLoaded', initMotion);
document.addEventListener('livewire:navigated', () => { if (window.__motionInit) initMotion(); window.__motionInit = true; });

// 3 + 4: every [data-squash] control anticipates and rebounds
if (!reduced()) {
    const target = (e) => e.target.closest?.('[data-squash]');
    document.addEventListener('pointerdown', (e) => { const el = target(e); if (el) gf.press(el); }, { passive: true });
    ['pointerup', 'pointercancel', 'pointerleave'].forEach((ev) =>
        document.addEventListener(ev, (e) => { const el = target(e); if (el && el.dataset.pressed !== '0') gf.release(el); }, { passive: true, capture: ev === 'pointerleave' }));
}

/* ---------------------------- Alpine components ---------------------------- */
Alpine.data('carousel', (total, autoplay = 0) => ({
    i: 0, total, timer: null, paused: false, x0: 0,
    init() { if (autoplay && !reduced()) this.timer = setInterval(() => !this.paused && this.next(), autoplay); },
    destroy() { clearInterval(this.timer); },
    next() { this.i = (this.i + 1) % this.total; },
    prev() { this.i = (this.i - 1 + this.total) % this.total; },
    swipeStart(e) { this.x0 = e.touches[0].clientX; this.paused = true; },
    swipeEnd(e) { const d = e.changedTouches[0].clientX - this.x0; if (Math.abs(d) > 40) d < 0 ? this.next() : this.prev(); this.paused = false; },
}));

Alpine.data('pricingEstimator', (plans) => ({
    plans, users: 5,
    get fill() { return ((this.users - 1) / 49) * 100 + '%'; },
    cost(p) { return p.base + Math.max(0, this.users - p.included) * p.extra; },
    kes(n) { return 'KES ' + Math.round(n).toLocaleString('en-KE'); },
}));

Alpine.data('toast', (initial = null) => ({
    message: initial, timer: null,
    init() { if (this.message) this.show(this.message); },
    show(m) {
        this.message = m; clearTimeout(this.timer); this.timer = setTimeout(() => (this.message = null), 5000);
        this.$nextTick(() => { // 4 squash/stretch landing, 5 icon settles after the box
            if (reduced() || !this.$refs.box) return;
            animate(this.$refs.box, { y: [30, 0], scaleX: [0.92, 1], scaleY: [1.1, 1] }, { type: 'spring', stiffness: 380, damping: 18 });
            animate(this.$refs.icon, { scale: [0, 1] }, { type: 'spring', stiffness: 500, damping: 12, delay: 0.12 });
        });
    },
}));

Livewire.start();
