/* ==========================================================================
   CarePoint – showcase interactions
   1. Hero slider (rotating cards)   2. 3D doctor carousel
   ========================================================================== */
(function () {
    'use strict';
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ======================================================================
       1. Interactive hero slider
       ====================================================================== */
    const slider = document.getElementById('heroSlider');
    if (slider) {
        const track = document.getElementById('heroTrack');
        const num = document.getElementById('heroNum');
        const bar = document.getElementById('heroProgress');
        const total = track.children.length;
        const DELAY = 7000;
        let timer = null;
        let remaining = DELAY;
        let started = 0;

        const current = () => track.children[1];
        function update() {
            const idx = parseInt(current().dataset.index, 10) + 1;
            num.textContent = String(idx).padStart(2, '0');
            Array.from(track.children).forEach((c, i) => {
                c.setAttribute('aria-hidden', i === 1 ? 'false' : 'true');
                c.querySelectorAll('a').forEach((a) => { a.tabIndex = i === 1 ? 0 : -1; });
            });
        }
        function next() { track.appendChild(track.children[0]); update(); restart(); }
        function prev() { track.prepend(track.children[total - 1]); update(); restart(); }
        function goTo(card) {
            // bring the clicked thumbnail into the "current" position
            let guard = total;
            while (track.children[1] !== card && guard--) track.appendChild(track.children[0]);
            update(); restart();
        }

        function startTimer(ms) {
            clearTimeout(timer);
            if (reduceMotion) return;
            started = Date.now();
            remaining = ms;
            timer = setTimeout(next, ms);
        }
        function restart() {
            bar.classList.remove('run');
            void bar.offsetWidth; // restart CSS animation
            if (!reduceMotion) {
                bar.style.animationDuration = DELAY + 'ms';
                bar.classList.add('run');
            }
            startTimer(DELAY);
        }
        function pause() {
            if (slider.classList.contains('paused')) return;
            slider.classList.add('paused');
            clearTimeout(timer);
            remaining -= Date.now() - started;
        }
        function resume() {
            if (!slider.classList.contains('paused')) return;
            slider.classList.remove('paused');
            startTimer(Math.max(800, remaining));
        }

        document.getElementById('heroNext').addEventListener('click', next);
        document.getElementById('heroPrev').addEventListener('click', prev);
        track.addEventListener('click', (e) => {
            const card = e.target.closest('.cp-slide-card');
            if (!card || e.target.closest('a')) return;
            const pos = Array.prototype.indexOf.call(track.children, card);
            if (pos >= 2) goTo(card);
        });
        slider.addEventListener('mouseenter', pause);
        slider.addEventListener('mouseleave', resume);
        slider.addEventListener('focusin', pause);
        slider.addEventListener('focusout', resume);
        slider.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowRight') next();
            if (e.key === 'ArrowLeft') prev();
        });
        // swipe on touch screens
        let sx = null;
        slider.addEventListener('touchstart', (e) => { sx = e.touches[0].clientX; }, { passive: true });
        slider.addEventListener('touchend', (e) => {
            if (sx === null) return;
            const dx = e.changedTouches[0].clientX - sx;
            if (Math.abs(dx) > 50) (dx < 0 ? next : prev)();
            sx = null;
        });
        document.addEventListener('visibilitychange', () => (document.hidden ? pause() : resume()));

        update();
        restart();
    }

    /* ======================================================================
       2. 3D doctor carousel (Toonhub style)
       ====================================================================== */
    const show = document.getElementById('docShow');
    const dataEl = document.getElementById('docData');
    if (show && dataEl) {
        const DOCS = JSON.parse(dataEl.textContent);
        const N = DOCS.length;
        const stage = document.getElementById('docStage');
        const info = document.getElementById('docInfo');
        const ghost = document.getElementById('docGhost');
        const $ = (id) => document.getElementById(id);

        // film-grain overlay
        const grain = `<svg xmlns='http://www.w3.org/2000/svg' width='200' height='200'><filter id='n'><feTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/><feColorMatrix type='saturate' values='0'/></filter><rect width='200' height='200' filter='url(#n)' opacity='0.08'/></svg>`;
        $('docGrain').style.backgroundImage = `url("data:image/svg+xml,${encodeURIComponent(grain)}")`;

        let active = 0;
        let busy = false;
        let isMobile = window.innerWidth < 860;

        const items = DOCS.map((d, i) => {
            const el = document.createElement('div');
            el.className = 'cp-doc-item';
            el.dataset.i = i;
            const img = document.createElement('img');
            img.src = d.photo;
            img.alt = d.name;
            img.draggable = false;
            el.appendChild(img);
            el.addEventListener('click', () => { if (i !== active) go(i); });
            stage.appendChild(el);
            return el;
        });

        function offset(i) {
            let d = (i - active + N) % N;
            if (d > N / 2) d -= N;
            return d; // negative = left, positive = right
        }

        function layout() {
            items.forEach((el, i) => {
                const d = offset(i);
                let s;
                if (d === 0) {
                    s = { transform: `translateX(-50%) scale(1)`, filter: 'none', opacity: '1', zIndex: '20',
                          left: '50%', height: isMobile ? '40%' : '74%', bottom: isMobile ? '45%' : '7%' };
                } else if (Math.abs(d) === 1) {
                    s = { transform: 'translateX(-50%) scale(1)', filter: 'blur(2px)', opacity: '.85', zIndex: '10',
                          left: d < 0 ? (isMobile ? '14%' : '35%') : (isMobile ? '86%' : '65%'),
                          height: isMobile ? '20%' : '36%', bottom: isMobile ? '52%' : '22%' };
                } else if (Math.abs(d) === 2) {
                    s = { transform: 'translateX(-50%) scale(1)', filter: 'blur(4px)', opacity: '.7', zIndex: '5',
                          left: d < 0 ? '44%' : '56%', height: isMobile ? '16%' : '28%', bottom: isMobile ? '54%' : '26%' };
                } else {
                    s = { transform: 'translateX(-50%) scale(.8)', filter: 'blur(6px)', opacity: '0', zIndex: '1',
                          left: '50%', height: isMobile ? '15%' : '24%', bottom: isMobile ? '54%' : '16%' };
                }
                Object.assign(el.style, s);
                el.classList.toggle('is-center', d === 0);
                el.setAttribute('aria-hidden', d === 0 ? 'false' : 'true');
            });
        }

        function render() {
            const d = DOCS[active];
            show.style.backgroundColor = d.bg;
            info.classList.add('swap');
            ghost.classList.add('swap');
            setTimeout(() => {
                $('docDept').textContent = d.dept;
                $('docIcon').className = 'fa-solid ' + d.icon;
                $('docName').textContent = d.name;
                $('docSpec').textContent = d.spec + ' · ' + d.exp + ' yrs experience';
                $('docBio').textContent = d.bio;
                $('docDays').textContent = d.days;
                $('docHours').textContent = d.hours;
                $('docBook').href = d.book;
                $('docBook').setAttribute('aria-label', 'Book an appointment with ' + d.name);
                ghost.textContent = d.ghost;
                info.classList.remove('swap');
                ghost.classList.remove('swap');
            }, 280);
            layout();
        }

        function go(i) {
            if (busy) return;
            busy = true;
            active = (i + N) % N;
            render();
            setTimeout(() => { busy = false; }, 650);
        }

        $('docNext').addEventListener('click', () => go(active + 1));
        $('docPrev').addEventListener('click', () => go(active - 1));
        show.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowRight') go(active + 1);
            if (e.key === 'ArrowLeft') go(active - 1);
        });
        let tx = null;
        show.addEventListener('touchstart', (e) => { tx = e.touches[0].clientX; }, { passive: true });
        show.addEventListener('touchend', (e) => {
            if (tx === null) return;
            const dx = e.changedTouches[0].clientX - tx;
            if (Math.abs(dx) > 50) go(active + (dx < 0 ? 1 : -1));
            tx = null;
        });
        window.addEventListener('resize', () => {
            const m = window.innerWidth < 860;
            if (m !== isMobile) { isMobile = m; layout(); }
        });

        DOCS.forEach((d) => { const im = new Image(); im.src = d.photo; });
        render();
    }

    /* ======================================================================
       3. Electric cards – run the animated SVG filter only while visible
       ====================================================================== */
    const ecards = document.querySelectorAll('.ecard-main');
    if (ecards.length && 'IntersectionObserver' in window) {
        ecards.forEach((el) => { el.dataset.filter = el.style.filter; if (reduceMotion) el.style.filter = 'none'; });
        if (!reduceMotion) {
            const io = new IntersectionObserver((entries) => {
                entries.forEach((en) => { en.target.style.filter = en.isIntersecting ? en.target.dataset.filter : 'none'; });
            }, { rootMargin: '100px' });
            ecards.forEach((el) => io.observe(el));
        }
    }
})();
