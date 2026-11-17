/* ==========================================================================
   CarePoint – admin panel behaviour
   ========================================================================== */
(function () {
    'use strict';

    // Sidebar toggle on small screens
    const sidebar = document.getElementById('adminSidebar');
    const toggle = document.getElementById('adminToggle');
    const overlay = document.getElementById('adminOverlay');
    function setSidebar(open) {
        sidebar.classList.toggle('open', open);
        overlay.classList.toggle('show', open);
    }
    toggle && toggle.addEventListener('click', () => setSidebar(!sidebar.classList.contains('open')));
    overlay && overlay.addEventListener('click', () => setSidebar(false));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && sidebar) setSidebar(false); });

    // Live photo preview on the doctor form
    const photo = document.getElementById('photo');
    const preview = document.getElementById('photoPreview');
    if (photo && preview) {
        photo.addEventListener('change', () => {
            const file = photo.files && photo.files[0];
            const group = photo.closest('.form-group');
            if (!file) return;
            if (!/^image\/(jpeg|png|webp)$/.test(file.type)) {
                window.CarePoint.showError(photo, 'Please choose a JPG, PNG or WEBP image.');
                photo.value = '';
                return;
            }
            if (file.size > 2 * 1024 * 1024) {
                window.CarePoint.showError(photo, 'The photo must be smaller than 2 MB.');
                photo.value = '';
                return;
            }
            group.classList.remove('has-error');
            const err = group.querySelector('.field-error');
            err && err.remove();
            preview.src = URL.createObjectURL(file);
        });
    }

    // Doctor form: at least one consulting day & end time after start time
    const doctorForm = document.querySelector('.doctor-form');
    if (doctorForm) {
        doctorForm.addEventListener('cp:validate', (e) => {
            const days = doctorForm.querySelectorAll('input[name="available_days[]"]:checked');
            const dayGroup = doctorForm.querySelector('.day-picker').closest('.form-group');
            if (!days.length) {
                window.CarePoint.showError(dayGroup.querySelector('input'), 'Select at least one consulting day.');
                e.preventDefault();
            }
            const start = doctorForm.querySelector('#start_time');
            const end = doctorForm.querySelector('#end_time');
            if (start.value && end.value && end.value <= start.value) {
                window.CarePoint.showError(start, 'End time must be after start time.');
                e.preventDefault();
            }
        });
        doctorForm.querySelectorAll('input[name="available_days[]"]').forEach((cb) => {
            cb.addEventListener('change', () => window.CarePoint.clearError(cb));
        });
    }
})();
