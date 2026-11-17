/* ==========================================================================
   CarePoint – appointment booking form
   Department -> doctors -> date -> live time-slot availability
   ========================================================================== */
(function () {
    'use strict';

    const form = document.getElementById('bookingForm');
    if (!form) return;

    const apiDoctors = form.dataset.apiDoctors;
    const apiSlots = form.dataset.apiSlots;
    const deptSelect = document.getElementById('department_id');
    const doctorSelect = document.getElementById('doctor_id');
    const dateInput = document.getElementById('appointment_date');
    const dateHint = document.getElementById('dateHint');
    const preview = document.getElementById('doctorPreview');
    const slotGrid = document.getElementById('slotGrid');
    const slotMessage = document.getElementById('slotMessage');
    const reason = document.getElementById('reason');
    const reasonCount = document.getElementById('reasonCount');

    const DAY_NAMES = { Sun: 'Sunday', Mon: 'Monday', Tue: 'Tuesday', Wed: 'Wednesday', Thu: 'Thursday', Fri: 'Friday', Sat: 'Saturday' };
    const DAY_KEYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    let doctors = [];
    let slotRequest = 0;

    const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    function setSlotMessage(html, type) {
        slotMessage.innerHTML = html;
        slotMessage.className = 'slot-message' + (type ? ' is-' + type : '');
    }

    function clearSlots(message) {
        slotGrid.innerHTML = '';
        setSlotMessage(message || 'Select a doctor and date to view available time slots.');
    }

    function currentDoctor() {
        const id = parseInt(doctorSelect.value, 10);
        return doctors.find((d) => d.id === id) || null;
    }

    /* ---------- Doctors ---------- */
    async function loadDoctors(preselect) {
        const deptId = deptSelect.value;
        doctors = [];
        preview.hidden = true;
        if (!deptId) {
            doctorSelect.innerHTML = '<option value="">Select a department first</option>';
            doctorSelect.disabled = true;
            clearSlots();
            return;
        }
        doctorSelect.disabled = true;
        doctorSelect.innerHTML = '<option value="">Loading doctors…</option>';
        try {
            const res = await fetch(apiDoctors + '?department_id=' + encodeURIComponent(deptId), { headers: { Accept: 'application/json' } });
            const data = await res.json();
            if (!data.success) throw new Error(data.message || 'Unable to load doctors');
            doctors = data.doctors;
            if (!doctors.length) {
                doctorSelect.innerHTML = '<option value="">No doctors available in this department</option>';
                clearSlots('There are currently no doctors available in this department.');
                return;
            }
            doctorSelect.innerHTML = '<option value="">Select a doctor</option>' + doctors.map((d) =>
                '<option value="' + d.id + '">' + esc(d.name) + ' – ' + esc(d.specialisation) + '</option>').join('');
            doctorSelect.disabled = false;
            if (preselect && doctors.some((d) => String(d.id) === String(preselect))) {
                doctorSelect.value = String(preselect);
            } else if (doctors.length === 1) {
                doctorSelect.value = String(doctors[0].id);
            }
            onDoctorChange(true);
        } catch (err) {
            doctorSelect.innerHTML = '<option value="">Could not load doctors</option>';
            clearSlots();
            setSlotMessage('<i class="fa-solid fa-triangle-exclamation"></i> We could not load the list of doctors. Please refresh the page and try again.', 'error');
        }
    }

    function renderPreview(d) {
        if (!d) { preview.hidden = true; return; }
        preview.innerHTML =
            '<img src="' + esc(d.photo) + '" alt="' + esc(d.name) + '">' +
            '<div><h4>' + esc(d.name) + '</h4><p>' + esc(d.specialisation) + ' · ' + d.experience + ' years experience</p>' +
            '<div class="dp-meta"><span><i class="fa-regular fa-calendar"></i>' + esc(d.days_label) + '</span>' +
            '<span><i class="fa-regular fa-clock"></i>' + esc(d.hours) + '</span>' +
            '<span><i class="fa-solid fa-tag"></i>$' + esc(d.fee) + '</span></div></div>';
        preview.hidden = false;
    }

    function onDoctorChange(keepSelection) {
        const d = currentDoctor();
        renderPreview(d);
        if (!keepSelection) slotGrid.dataset.selected = '';
        if (d) {
            dateHint.textContent = d.name + ' consults on: ' + d.days.map((k) => DAY_NAMES[k]).join(', ') + '.';
        } else {
            dateHint.textContent = 'Choose a doctor to see their consulting days.';
        }
        loadSlots();
    }

    /* ---------- Slots ---------- */
    async function loadSlots() {
        const d = currentDoctor();
        const date = dateInput.value;
        if (!doctorSelect.value) { clearSlots(); return; }
        if (!date) { clearSlots('Now choose an appointment date to see available times.'); return; }

        // Quick client-side check of the doctor's working days
        if (d) {
            const day = DAY_KEYS[new Date(date + 'T12:00:00').getDay()];
            if (!d.days.includes(day)) {
                slotGrid.innerHTML = '';
                setSlotMessage('<i class="fa-solid fa-calendar-xmark"></i> ' + esc(d.name) + ' does not consult on ' + DAY_NAMES[day] +
                    's. Please choose one of: ' + d.days.map((k) => DAY_NAMES[k]).join(', ') + '.', 'error');
                return;
            }
        }

        const reqId = ++slotRequest;
        setSlotMessage('<i class="fa-solid fa-spinner fa-spin"></i> Checking availability…');
        slotGrid.innerHTML = '';
        try {
            const res = await fetch(apiSlots + '?doctor_id=' + encodeURIComponent(doctorSelect.value) + '&date=' + encodeURIComponent(date), { headers: { Accept: 'application/json' } });
            const data = await res.json();
            if (reqId !== slotRequest) return; // a newer request superseded this one
            if (!data.success) {
                setSlotMessage(esc(data.message || 'Unable to load time slots.'), 'error');
                return;
            }
            if (!data.works) {
                setSlotMessage('<i class="fa-solid fa-calendar-xmark"></i> ' + esc(data.message), 'error');
                return;
            }
            const selected = slotGrid.dataset.selected;
            slotGrid.innerHTML = data.slots.map((s) => {
                const cls = s.available ? '' : ' slot-' + s.status;
                const title = s.status === 'booked' ? 'Already booked' : s.status === 'past' ? 'No longer available' : 'Available';
                return '<label class="slot' + cls + '" title="' + title + '">' +
                    '<input type="radio" name="appointment_time" value="' + esc(s.time) + '"' +
                    (s.available ? '' : ' disabled') + (s.available && s.time === selected ? ' checked' : '') + '>' +
                    '<span>' + esc(s.label) + '</span></label>';
            }).join('');
            setSlotMessage((data.free ? '<i class="fa-solid fa-circle-check"></i> ' : '<i class="fa-solid fa-circle-exclamation"></i> ') + esc(data.message), data.free ? 'success' : 'error');
        } catch (err) {
            if (reqId !== slotRequest) return;
            setSlotMessage('<i class="fa-solid fa-triangle-exclamation"></i> Could not check availability. Please try again.', 'error');
        }
    }

    slotGrid.addEventListener('change', (e) => {
        if (e.target.name === 'appointment_time') {
            slotGrid.dataset.selected = e.target.value;
            window.CarePoint && window.CarePoint.clearError(e.target);
        }
    });

    /* ---------- Events ---------- */
    deptSelect.addEventListener('change', () => { doctorSelect.dataset.selected = ''; slotGrid.dataset.selected = ''; loadDoctors(null); });
    doctorSelect.addEventListener('change', () => onDoctorChange(false));
    dateInput.addEventListener('change', () => { slotGrid.dataset.selected = ''; loadSlots(); });

    function updateCount() { reasonCount.textContent = reason.value.length; }
    reason.addEventListener('input', updateCount);
    updateCount();

    // Extra validation: a time slot must be chosen
    form.addEventListener('cp:validate', (e) => {
        const chosen = form.querySelector('input[name="appointment_time"]:checked');
        if (!chosen) {
            const group = slotGrid.closest('.form-group');
            group.classList.add('has-error');
            let err = group.querySelector('.field-error');
            if (!err) { err = document.createElement('small'); err.className = 'field-error'; group.appendChild(err); }
            err.textContent = slotGrid.children.length ? 'Please select an available time slot.' : 'Please choose a doctor and a date with available time slots.';
            e.preventDefault();
        }
    });

    // Initial state (e.g. arriving from "Book with this doctor" or after a server-side error)
    if (deptSelect.value) {
        loadDoctors(doctorSelect.dataset.selected || doctorSelect.value);
    }
})();
