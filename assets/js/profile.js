/* ==========================================================================
   CarePoint – patient profile
   ========================================================================== */
(function () {
    'use strict';

    if (window.lucide) {
        window.lucide.createIcons({ attrs: { 'stroke-width': 1.75 } });
    }

    /* Keep the active tab visible when the tab bar scrolls horizontally (mobile). */
    const track = document.querySelector('.pf-tabs-track');
    const active = track && track.querySelector('.pf-tab.is-active');
    if (track && active && track.scrollWidth > track.clientWidth) {
        track.scrollLeft = active.offsetLeft - (track.clientWidth - active.offsetWidth) / 2;
    }

    /* Show edge fades only where there is more to scroll. */
    const tabs = document.querySelector('.pf-tabs');
    function updateFades() {
        if (!track || !tabs) return;
        const max = track.scrollWidth - track.clientWidth;
        tabs.classList.toggle('fade-left', track.scrollLeft > 4);
        tabs.classList.toggle('fade-right', track.scrollLeft < max - 4);
    }
    if (track) {
        track.addEventListener('scroll', updateFades, { passive: true });
        window.addEventListener('resize', updateFades);
        updateFades();
    }

    /* Copy patient ID. */
    document.querySelectorAll('.pf-copy').forEach((btn) => {
        btn.addEventListener('click', () => {
            const value = btn.dataset.copy;
            const done = () => {
                btn.classList.add('is-copied');
                btn.setAttribute('aria-label', 'Patient ID copied');
                setTimeout(() => {
                    btn.classList.remove('is-copied');
                    btn.setAttribute('aria-label', 'Copy patient ID ' + value);
                }, 1600);
            };
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(value).then(done).catch(() => {});
            } else {
                const ta = document.createElement('textarea');
                ta.value = value;
                ta.setAttribute('readonly', '');
                ta.style.position = 'absolute';
                ta.style.left = '-9999px';
                document.body.appendChild(ta);
                ta.select();
                try { document.execCommand('copy'); done(); } catch (e) { /* ignore */ }
                document.body.removeChild(ta);
            }
        });
    });
})();
