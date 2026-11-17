/* ==========================================================================
   CarePoint – shared front-end behaviour
   ========================================================================== */
(function () {
    'use strict';
    document.documentElement.classList.add('js');

    /* ---------- Mobile navigation ---------- */
    const toggle = document.getElementById('navToggle');
    const nav = document.getElementById('mainNav');
    const overlay = document.getElementById('navOverlay');
    function setNav(open) {
        if (!toggle || !nav) return;
        nav.classList.toggle('open', open);
        toggle.classList.toggle('open', open);
        overlay && overlay.classList.toggle('show', open);
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        document.body.style.overflow = open ? 'hidden' : '';
    }
    toggle && toggle.addEventListener('click', () => setNav(!nav.classList.contains('open')));
    overlay && overlay.addEventListener('click', () => setNav(false));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setNav(false); });
    window.addEventListener('resize', () => { if (window.innerWidth > 1080) setNav(false); });

    /* ---------- Header shadow & back-to-top ---------- */
    const header = document.getElementById('siteHeader');
    const toTop = document.getElementById('backToTop');
    function onScroll() {
        const y = window.scrollY;
        header && header.classList.toggle('scrolled', y > 10);
        toTop && toTop.classList.toggle('show', y > 600);
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
    toTop && toTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));

    /* ---------- Scroll reveal ---------- */
    const reveals = document.querySelectorAll('.reveal');
    if ('IntersectionObserver' in window) {
        const io = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    const siblings = Array.from(entry.target.parentElement.children).filter((c) => c.classList.contains('reveal'));
                    const idx = Math.max(0, siblings.indexOf(entry.target));
                    entry.target.style.transitionDelay = Math.min(idx * 70, 350) + 'ms';
                    entry.target.classList.add('visible');
                    io.unobserve(entry.target);
                }
            });
        }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
        reveals.forEach((el) => io.observe(el));
    } else {
        reveals.forEach((el) => el.classList.add('visible'));
    }

    /* ---------- Alerts ---------- */
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.alert-close');
        if (btn) {
            const alert = btn.closest('.alert');
            alert.style.transition = 'opacity .3s, transform .3s';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-6px)';
            setTimeout(() => alert.remove(), 300);
        }
    });
    document.querySelectorAll('.flash-stack .alert-success, .flash-stack .alert-info').forEach((el) => {
        setTimeout(() => { const b = el.querySelector('.alert-close'); b && b.click(); }, 7000);
    });

    /* ---------- Confirm dialogs for destructive forms ---------- */
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
            e.preventDefault();
            e.stopImmediatePropagation();
        }
    }, true);

    /* ---------- Auto-submit selects ---------- */
    document.querySelectorAll('.js-autosubmit').forEach((sel) => {
        sel.addEventListener('change', () => {
            const form = sel.form;
            if (form.dataset.confirm || form.requestSubmit) {
                form.requestSubmit ? form.requestSubmit() : form.submit();
            } else {
                form.submit();
            }
        });
    });

    /* ---------- Show / hide password ---------- */
    document.querySelectorAll('.toggle-password').forEach((btn) => {
        btn.addEventListener('click', () => {
            const input = btn.parentElement.querySelector('input');
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.innerHTML = show ? '<i class="fa-regular fa-eye-slash"></i>' : '<i class="fa-regular fa-eye"></i>';
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });
    });

    /* ---------- Copy to clipboard ---------- */
    document.querySelectorAll('[data-copy]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const text = btn.dataset.copy;
            try {
                await navigator.clipboard.writeText(text);
            } catch (err) {
                const ta = document.createElement('textarea');
                ta.value = text; document.body.appendChild(ta); ta.select();
                document.execCommand('copy'); ta.remove();
            }
            const original = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Copied';
            setTimeout(() => { btn.innerHTML = original; }, 1800);
        });
    });

    /* ---------- Uppercase inputs ---------- */
    document.querySelectorAll('input.uppercase').forEach((input) => {
        input.addEventListener('input', () => {
            const pos = input.selectionStart;
            input.value = input.value.toUpperCase();
            input.setSelectionRange(pos, pos);
        });
    });

    /* ======================================================================
       Form validation (works with the HTML5 constraint attributes)
       ====================================================================== */
    function messageFor(el) {
        const v = el.validity;
        if (v.customError) return el.validationMessage;
        if (v.valueMissing) {
            if (el.tagName === 'SELECT') return 'Please select an option.';
            if (el.type === 'radio') return 'Please select an option.';
            return 'This field is required.';
        }
        if (v.typeMismatch) return el.type === 'email' ? 'Please enter a valid email address.' : 'Please enter a valid value.';
        if (v.patternMismatch) return el.dataset.error || (el.type === 'tel' ? 'Please enter a valid phone number (7–20 digits).' : 'Please use the requested format.');
        if (v.tooShort) return 'Please enter at least ' + el.minLength + ' characters (currently ' + el.value.length + ').';
        if (v.tooLong) return 'Please use no more than ' + el.maxLength + ' characters.';
        if (v.rangeUnderflow) return 'The value must be ' + el.min + ' or more.';
        if (v.rangeOverflow) return 'The value must be ' + el.max + ' or less.';
        if (v.stepMismatch) return 'Please enter a valid number.';
        if (v.badInput) return 'Please enter a valid value.';
        return el.validationMessage || 'Please check this field.';
    }

    function showError(el, msg) {
        const group = el.closest('.form-group');
        if (!group) return;
        group.classList.add('has-error');
        let err = group.querySelector('.field-error');
        if (!err) {
            err = document.createElement('small');
            err.className = 'field-error';
            group.appendChild(err);
        }
        err.textContent = msg;
        el.setAttribute('aria-invalid', 'true');
    }

    function clearError(el) {
        const group = el.closest('.form-group');
        if (!group) return;
        group.classList.remove('has-error');
        const err = group.querySelector('.field-error');
        err && err.remove();
        el.removeAttribute('aria-invalid');
    }

    function checkMatch(el) {
        if (!el.dataset.match) return;
        const other = el.form.querySelector('#' + el.dataset.match);
        el.setCustomValidity(other && other.value !== el.value ? (el.dataset.error || 'Values do not match.') : '');
    }

    function validateField(el) {
        if (el.disabled || el.type === 'hidden' || el.type === 'submit' || el.type === 'button' || el.type === 'file') return true;
        if (el.value && el.type !== 'radio' && el.type !== 'checkbox' && el.tagName !== 'SELECT' && el.value !== el.value.trim() && el.value.trim() === '') {
            el.value = '';
        }
        checkMatch(el);
        if (!el.checkValidity()) {
            showError(el, messageFor(el));
            return false;
        }
        clearError(el);
        return true;
    }

    window.CarePoint = { validateField, showError, clearError };

    function attachValidation(form) {
        const fields = () => Array.from(form.querySelectorAll('input, select, textarea'));
        form.addEventListener('submit', (e) => {
            let firstInvalid = null;
            fields().forEach((el) => {
                if (!validateField(el) && !firstInvalid) firstInvalid = el;
            });
            const extra = form.dispatchEvent(new CustomEvent('cp:validate', { cancelable: true }));
            if (firstInvalid || !extra) {
                e.preventDefault();
                const target = firstInvalid || form.querySelector('.has-error');
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    setTimeout(() => { if (target.focus) target.focus({ preventScroll: true }); }, 300);
                }
                return;
            }
            const btn = form.querySelector('button[type=submit]');
            if (btn && !form.dataset.confirm) {
                btn.classList.add('is-loading');
                btn.dataset.original = btn.innerHTML;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Please wait…';
            }
        });
        form.addEventListener('input', (e) => {
            const el = e.target;
            if (el.closest('.form-group.has-error')) validateField(el);
        });
        form.addEventListener('change', (e) => {
            const el = e.target;
            if (el.closest('.form-group.has-error') && el.type !== 'radio') validateField(el);
        });
        form.addEventListener('focusout', (e) => {
            const el = e.target;
            if ((el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') && el.value !== '' && el.type !== 'radio') validateField(el);
        });
    }

    document.querySelectorAll('form.js-validate, form#bookingForm').forEach(attachValidation);

    // Re-enable submit buttons if the user navigates back (bfcache)
    window.addEventListener('pageshow', () => {
        document.querySelectorAll('button.is-loading').forEach((btn) => {
            btn.classList.remove('is-loading');
            if (btn.dataset.original) btn.innerHTML = btn.dataset.original;
        });
    });
})();

/* One-click demo login buttons (supervisor quick access) */
(function () {
    'use strict';
    function quickLogin(btn) {
        const form = document.querySelector(btn.dataset.form);
        if (!form) return;
        form.querySelector('[name="' + btn.dataset.userField + '"]').value = btn.dataset.user;
        form.querySelector('[name="password"]').value = btn.dataset.pass;
        btn.classList.add('is-busy');
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Signing in…';
        form.submit();
    }
    document.querySelectorAll('[data-quick]').forEach((btn) => btn.addEventListener('click', () => quickLogin(btn)));
    const want = new URLSearchParams(location.search).get('quick');
    if (want) {
        const btn = document.querySelector('[data-quick="' + want + '"]');
        if (btn) quickLogin(btn);
    }
})();
