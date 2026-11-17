<?php
require_once __DIR__ . '/includes/functions.php';

$departments = get_departments();
$deptById    = array_column($departments, null, 'id');
$errors      = [];
$user        = current_user();

// ---------------------------------------------------------------------
// Default form values (pre-filled from the logged-in patient / query string)
// ---------------------------------------------------------------------
$values = [
    'patient_name'  => $user['full_name'] ?? '',
    'email'         => $user['email'] ?? '',
    'phone'         => $user['phone'] ?? '',
    'age'           => $user['age'] ?? '',
    'gender'        => $user['gender'] ?? '',
    'department_id' => '',
    'doctor_id'     => '',
    'appointment_date' => '',
    'appointment_time' => '',
    'reason'        => '',
];

if (!empty($_GET['doctor']) && ($doc = get_doctor((int)$_GET['doctor']))) {
    $values['doctor_id']     = $doc['id'];
    $values['department_id'] = $doc['department_id'];
} elseif (!empty($_GET['department'])) {
    foreach ($departments as $d) {
        if ($d['slug'] === $_GET['department']) {
            $values['department_id'] = $d['id'];
        }
    }
}

// ---------------------------------------------------------------------
// Handle submission
// ---------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $k => $_) {
        $values[$k] = post($k);
    }

    if (!verify_csrf()) {
        $errors['form'] = 'Your session has expired. Please submit the form again.';
    }

    // Personal details
    if (mb_strlen($values['patient_name']) < 3 || !preg_match("/^[\p{L}\s.'-]+$/u", $values['patient_name'])) {
        $errors['patient_name'] = 'Please enter your full name (letters only, at least 3 characters).';
    }
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    if (!valid_phone($values['phone'])) {
        $errors['phone'] = 'Please enter a valid phone number (7–20 digits).';
    }
    if (!ctype_digit($values['age']) || (int)$values['age'] < 0 || (int)$values['age'] > 120) {
        $errors['age'] = 'Please enter a valid age between 0 and 120.';
    }
    if (!in_array($values['gender'], ['Male', 'Female', 'Other'], true)) {
        $errors['gender'] = 'Please select a gender.';
    }

    // Department & doctor
    $doctor = null;
    if (!isset($deptById[(int)$values['department_id']])) {
        $errors['department_id'] = 'Please select a medical department.';
    }
    if (!ctype_digit($values['doctor_id']) || !($doctor = get_doctor((int)$values['doctor_id']))) {
        $errors['doctor_id'] = 'Please select an available doctor.';
    } elseif ((int)$doctor['department_id'] !== (int)$values['department_id']) {
        $errors['doctor_id'] = 'The selected doctor does not belong to this department.';
        $doctor = null;
    }

    // Date & time
    $today = date('Y-m-d');
    $max   = date('Y-m-d', strtotime('+' . BOOKING_WINDOW_DAYS . ' days'));
    if (!valid_date($values['appointment_date'])) {
        $errors['appointment_date'] = 'Please choose an appointment date.';
    } elseif ($values['appointment_date'] < $today) {
        $errors['appointment_date'] = 'The appointment date cannot be in the past.';
    } elseif ($values['appointment_date'] > $max) {
        $errors['appointment_date'] = 'Appointments can only be booked up to ' . BOOKING_WINDOW_DAYS . ' days ahead.';
    } elseif ($doctor && !doctor_works_on($doctor, $values['appointment_date'])) {
        $errors['appointment_date'] = $doctor['full_name'] . ' is not available on ' . date('l', strtotime($values['appointment_date'])) . 's.';
    }

    if (!isset($errors['appointment_date']) && $doctor) {
        $slotStatus = null;
        foreach (slot_availability($doctor, $values['appointment_date']) as $s) {
            if ($s['time'] === $values['appointment_time']) {
                $slotStatus = $s['status'];
            }
        }
        if ($slotStatus === null) {
            $errors['appointment_time'] = 'Please select an available time slot.';
        } elseif ($slotStatus === 'booked') {
            $errors['appointment_time'] = 'Sorry, that time slot has just been reserved. Please choose another time.';
        } elseif ($slotStatus === 'past') {
            $errors['appointment_time'] = 'That time slot is no longer available today. Please choose a later time.';
        }
    }

    if (mb_strlen($values['reason']) < 10) {
        $errors['reason'] = 'Please describe the reason for your visit (at least 10 characters).';
    } elseif (mb_strlen($values['reason']) > 1000) {
        $errors['reason'] = 'Please keep the reason under 1000 characters.';
    }

    // Same patient can't hold two appointments at the exact same time
    if (!$errors) {
        $stmt = db()->prepare('SELECT reference FROM appointments WHERE email = ? AND appointment_date = ? AND appointment_time = ? AND slot_lock = 1');
        $stmt->execute([$values['email'], $values['appointment_date'], $values['appointment_time']]);
        if ($ref = $stmt->fetchColumn()) {
            $errors['appointment_time'] = "You already have an appointment at this time (reference $ref).";
        }
    }

    if (!$errors) {
        $reference = generate_reference();
        try {
            $stmt = db()->prepare(
                'INSERT INTO appointments (reference, user_id, doctor_id, department_id, doctor_name, patient_name, email, phone, age, gender,
                                           appointment_date, appointment_time, reason, status, slot_lock)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'Pending\', 1)'
            );
            $stmt->execute([
                $reference,
                $user['id'] ?? null,
                $doctor['id'],
                $doctor['department_id'],
                $doctor['full_name'],
                $values['patient_name'],
                strtolower($values['email']),
                $values['phone'],
                (int)$values['age'],
                $values['gender'],
                $values['appointment_date'],
                $values['appointment_time'] . ':00',
                $values['reason'],
            ]);
            $_SESSION['recent_bookings'][] = $reference;
            redirect('booking-success.php?ref=' . urlencode($reference));
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                // The UNIQUE(doctor, date, time, slot_lock) key caught a simultaneous booking
                $errors['appointment_time'] = 'Sorry, that time slot was booked by another patient a moment ago. Please choose another time.';
            } else {
                throw $e;
            }
        }
    }

    if ($errors && !isset($errors['form'])) {
        $errors['form'] = 'Please correct the highlighted fields and try again.';
    }
}

// Server-side data for rendering (also used when JS is disabled)
$formDoctors = [];
if ($values['department_id']) {
    require_once __DIR__ . '/includes/components.php';
    $formDoctors = get_active_doctors((int)$values['department_id']);
}
$formSlots = [];
if ($values['doctor_id'] && valid_date($values['appointment_date']) && ($d = get_doctor((int)$values['doctor_id'])) && doctor_works_on($d, $values['appointment_date']) && $values['appointment_date'] >= date('Y-m-d')) {
    $formSlots = slot_availability($d, $values['appointment_date']);
}

$pageTitle   = 'Book an Appointment';
$activePage  = 'book';
$pageScripts = ['booking.js'];
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/components.php';
page_hero('Book an Appointment', 'Choose your department, doctor and a time that works for you.', 'Book Appointment');
?>

<section class="section">
    <div class="container booking-layout">
        <form id="bookingForm" class="card form-card" method="post" action="<?= url('book.php') ?>" novalidate
              data-api-doctors="<?= url('api/doctors.php') ?>" data-api-slots="<?= url('api/slots.php') ?>">
            <?= csrf_field() ?>

            <?php if (!empty($errors['form'])): ?>
                <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i><span><?= e($errors['form']) ?></span></div>
            <?php endif; ?>

            <?php if (!$user): ?>
                <div class="alert alert-info"><i class="fa-solid fa-circle-info"></i>
                    <span>Booking as a guest. <a href="<?= url('login.php') ?>">Log in</a> or <a href="<?= url('register.php') ?>">create an account</a> to manage all your appointments from your dashboard.</span></div>
            <?php endif; ?>

            <!-- Step 1 -->
            <fieldset class="form-step">
                <legend><span class="step-badge">1</span> Patient details</legend>
                <div class="form-grid">
                    <div class="form-group col-2 <?= isset($errors['patient_name']) ? 'has-error' : '' ?>">
                        <label for="patient_name">Full name <span class="req">*</span></label>
                        <input type="text" id="patient_name" name="patient_name" value="<?= e($values['patient_name']) ?>" placeholder="e.g. Jane Doe" required minlength="3" maxlength="100" autocomplete="name">
                        <?= field_error($errors, 'patient_name') ?>
                    </div>
                    <div class="form-group <?= isset($errors['email']) ? 'has-error' : '' ?>">
                        <label for="email">Email address <span class="req">*</span></label>
                        <input type="email" id="email" name="email" value="<?= e($values['email']) ?>" placeholder="you@example.com" required maxlength="150" autocomplete="email">
                        <?= field_error($errors, 'email') ?>
                    </div>
                    <div class="form-group <?= isset($errors['phone']) ? 'has-error' : '' ?>">
                        <label for="phone">Phone number <span class="req">*</span></label>
                        <input type="tel" id="phone" name="phone" value="<?= e($values['phone']) ?>" placeholder="+1 (555) 123-4567" required pattern="^\+?[0-9\s\-().]{7,20}$" autocomplete="tel">
                        <?= field_error($errors, 'phone') ?>
                    </div>
                    <div class="form-group <?= isset($errors['age']) ? 'has-error' : '' ?>">
                        <label for="age">Age <span class="req">*</span></label>
                        <input type="number" id="age" name="age" value="<?= e($values['age']) ?>" min="0" max="120" placeholder="e.g. 32" required>
                        <?= field_error($errors, 'age') ?>
                    </div>
                    <div class="form-group <?= isset($errors['gender']) ? 'has-error' : '' ?>">
                        <label for="gender">Gender <span class="req">*</span></label>
                        <select id="gender" name="gender" required>
                            <option value="">Select gender</option>
                            <?php foreach (['Male', 'Female', 'Other'] as $g): ?>
                                <option value="<?= $g ?>" <?= $values['gender'] === $g ? 'selected' : '' ?>><?= $g ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= field_error($errors, 'gender') ?>
                    </div>
                </div>
            </fieldset>

            <!-- Step 2 -->
            <fieldset class="form-step">
                <legend><span class="step-badge">2</span> Department &amp; doctor</legend>
                <div class="form-grid">
                    <div class="form-group <?= isset($errors['department_id']) ? 'has-error' : '' ?>">
                        <label for="department_id">Medical department <span class="req">*</span></label>
                        <select id="department_id" name="department_id" required>
                            <option value="">Select a department</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= (int)$d['id'] ?>" <?= (string)$values['department_id'] === (string)$d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= field_error($errors, 'department_id') ?>
                    </div>
                    <div class="form-group <?= isset($errors['doctor_id']) ? 'has-error' : '' ?>">
                        <label for="doctor_id">Preferred doctor <span class="req">*</span></label>
                        <select id="doctor_id" name="doctor_id" required <?= $formDoctors ? '' : 'disabled' ?> data-selected="<?= e($values['doctor_id']) ?>">
                            <option value=""><?= $formDoctors ? 'Select a doctor' : 'Select a department first' ?></option>
                            <?php foreach ($formDoctors as $fd): ?>
                                <option value="<?= (int)$fd['id'] ?>" <?= (string)$values['doctor_id'] === (string)$fd['id'] ? 'selected' : '' ?>><?= e($fd['full_name']) ?> – <?= e($fd['specialisation']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= field_error($errors, 'doctor_id') ?>
                    </div>
                </div>
                <div id="doctorPreview" class="doctor-preview" hidden></div>
            </fieldset>

            <!-- Step 3 -->
            <fieldset class="form-step">
                <legend><span class="step-badge">3</span> Date &amp; time</legend>
                <div class="form-grid">
                    <div class="form-group <?= isset($errors['appointment_date']) ? 'has-error' : '' ?>">
                        <label for="appointment_date">Appointment date <span class="req">*</span></label>
                        <input type="date" id="appointment_date" name="appointment_date" value="<?= e($values['appointment_date']) ?>"
                               min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+' . BOOKING_WINDOW_DAYS . ' days')) ?>" required>
                        <?= field_error($errors, 'appointment_date') ?>
                        <small class="hint" id="dateHint">Choose a doctor to see their consulting days.</small>
                    </div>
                </div>
                <div class="form-group <?= isset($errors['appointment_time']) ? 'has-error' : '' ?>">
                    <label>Available time slots <span class="req">*</span></label>
                    <div id="slotMessage" class="slot-message">
                        <?= $formSlots ? 'Select one of the available times below.' : 'Select a doctor and date to view available time slots.' ?>
                    </div>
                    <div id="slotGrid" class="slot-grid" data-selected="<?= e($values['appointment_time']) ?>">
                        <?php foreach ($formSlots as $s): ?>
                            <label class="slot <?= $s['available'] ? '' : 'slot-' . e($s['status']) ?>">
                                <input type="radio" name="appointment_time" value="<?= e($s['time']) ?>" <?= $s['available'] ? '' : 'disabled' ?> <?= $values['appointment_time'] === $s['time'] && $s['available'] ? 'checked' : '' ?>>
                                <span><?= e($s['label']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="slot-legend">
                        <span><i class="lg lg-free"></i> Available</span>
                        <span><i class="lg lg-selected"></i> Selected</span>
                        <span><i class="lg lg-booked"></i> Already booked</span>
                    </div>
                    <?= field_error($errors, 'appointment_time') ?>
                </div>
            </fieldset>

            <!-- Step 4 -->
            <fieldset class="form-step">
                <legend><span class="step-badge">4</span> Reason for appointment</legend>
                <div class="form-group <?= isset($errors['reason']) ? 'has-error' : '' ?>">
                    <label for="reason">Briefly describe your symptoms or reason for visit <span class="req">*</span></label>
                    <textarea id="reason" name="reason" rows="4" minlength="10" maxlength="1000" required placeholder="e.g. Persistent headaches for two weeks, mostly in the mornings."><?= e($values['reason']) ?></textarea>
                    <small class="hint"><span id="reasonCount">0</span>/1000 characters</small>
                    <?= field_error($errors, 'reason') ?>
                </div>
            </fieldset>

            <div class="form-actions">
                <p class="muted-sm"><i class="fa-solid fa-lock"></i> Your information is kept private and only shared with your doctor.</p>
                <button type="submit" class="btn btn-primary btn-lg" id="bookBtn"><i class="fa-regular fa-calendar-check"></i> Confirm Booking</button>
            </div>
        </form>

        <aside class="booking-aside">
            <div class="card aside-card">
                <h3><i class="fa-solid fa-circle-info"></i> Before your visit</h3>
                <ul class="check-list check-list-sm">
                    <li><i class="fa-solid fa-check"></i> <span>Arrive 10–15 minutes before your appointment time.</span></li>
                    <li><i class="fa-solid fa-check"></i> <span>Bring a photo ID and any previous test results or prescriptions.</span></li>
                    <li><i class="fa-solid fa-check"></i> <span>New bookings are <strong>Pending</strong> until confirmed by our team (usually within a few hours).</span></li>
                    <li><i class="fa-solid fa-check"></i> <span>You can cancel online up until your appointment time.</span></li>
                </ul>
            </div>
            <div class="card aside-card aside-navy">
                <h3><i class="fa-solid fa-magnifying-glass"></i> Already booked?</h3>
                <p>Check the status of your appointment with your booking reference.</p>
                <a href="<?= url('track.php') ?>" class="btn btn-white btn-block">Track my booking</a>
            </div>
            <div class="card aside-card">
                <h3><i class="fa-solid fa-truck-medical"></i> Emergency?</h3>
                <p>For medical emergencies do not book online. Call our 24/7 line on <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', CLINIC_EMERGENCY)) ?>"><strong><?= e(CLINIC_EMERGENCY) ?></strong></a>.</p>
            </div>
        </aside>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
