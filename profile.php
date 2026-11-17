<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/profile_components.php';
require_login();

$profile = get_patient_profile((int)current_user()['id']);
// The profile-fields migration adds these columns; until it runs we hide them from the form.
$hasExtended = array_key_exists('blood_group', $profile);

$tab = $_GET['tab'] ?? 'overview';
if (!isset(PROFILE_TABS[$tab])) {
    $tab = 'overview';
}
$editing = $tab === 'personal' && !empty($_GET['edit']);

// ---------------------------------------------------------------------
// Save personal information
// ---------------------------------------------------------------------
$errors = [];
$form = $profile;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tab = 'personal';
    $editing = true;
    if (!verify_csrf()) {
        flash('error', 'Your session expired. Please try again.');
        redirect('profile.php?tab=personal&edit=1');
    }

    $form = array_merge($profile, [
        'full_name' => post('full_name'),
        'phone'     => post('phone'),
        'gender'    => post('gender'),
    ]);
    if ($hasExtended) {
        $form = array_merge($form, [
            'date_of_birth'      => post('date_of_birth'),
            'blood_group'        => post('blood_group'),
            'emergency_name'     => post('emergency_name'),
            'emergency_relation' => post('emergency_relation'),
            'emergency_phone'    => post('emergency_phone'),
        ]);
    }

    if (mb_strlen($form['full_name']) < 3 || mb_strlen($form['full_name']) > 100) {
        $errors['full_name'] = 'Please enter your full name (3–100 characters).';
    }
    if (!valid_phone($form['phone'])) {
        $errors['phone'] = 'Please enter a valid phone number.';
    }
    if ($form['gender'] !== '' && !in_array($form['gender'], ['Male', 'Female', 'Other'], true)) {
        $errors['gender'] = 'Please select a valid gender.';
    }
    if ($hasExtended) {
        if ($form['date_of_birth'] !== '') {
            if (!valid_date($form['date_of_birth']) || $form['date_of_birth'] > date('Y-m-d') || age_from_dob($form['date_of_birth']) > 120) {
                $errors['date_of_birth'] = 'Please enter a valid date of birth.';
            }
        }
        if ($form['blood_group'] !== '' && !in_array($form['blood_group'], BLOOD_GROUPS, true)) {
            $errors['blood_group'] = 'Please select a valid blood group.';
        }
        if (mb_strlen($form['emergency_name']) > 100) {
            $errors['emergency_name'] = 'Name is too long.';
        }
        if (mb_strlen($form['emergency_relation']) > 50) {
            $errors['emergency_relation'] = 'Relationship is too long.';
        }
        if ($form['emergency_phone'] !== '' && !valid_phone($form['emergency_phone'])) {
            $errors['emergency_phone'] = 'Please enter a valid phone number.';
        }
        if ($form['emergency_name'] !== '' && $form['emergency_phone'] === '') {
            $errors['emergency_phone'] = 'Add a phone number so we can reach your emergency contact.';
        }
    }

    if (!$errors) {
        $nullIfEmpty = fn($v) => $v === '' ? null : $v;
        if ($hasExtended) {
            $age = age_from_dob($form['date_of_birth']);
            $stmt = db()->prepare(
                'UPDATE users SET full_name = ?, phone = ?, gender = ?, date_of_birth = ?, age = COALESCE(?, age), blood_group = ?,
                        emergency_name = ?, emergency_relation = ?, emergency_phone = ?
                 WHERE id = ?'
            );
            $stmt->execute([
                $form['full_name'], $form['phone'], $nullIfEmpty($form['gender']), $nullIfEmpty($form['date_of_birth']), $age,
                $nullIfEmpty($form['blood_group']), $nullIfEmpty($form['emergency_name']), $nullIfEmpty($form['emergency_relation']),
                $nullIfEmpty($form['emergency_phone']), $profile['id'],
            ]);
        } else {
            $stmt = db()->prepare('UPDATE users SET full_name = ?, phone = ?, gender = ? WHERE id = ?');
            $stmt->execute([$form['full_name'], $form['phone'], $nullIfEmpty($form['gender']), $profile['id']]);
        }
        $_SESSION['user_name'] = $form['full_name'];
        flash('success', 'Your profile has been updated.');
        redirect('profile.php?tab=personal');
    }
}

// ---------------------------------------------------------------------
// Appointments & derived status
// ---------------------------------------------------------------------
$stmt = db()->prepare(
    'SELECT a.*, dp.name AS department_name
     FROM appointments a JOIN departments dp ON dp.id = a.department_id
     WHERE a.user_id = ?
     ORDER BY a.appointment_date DESC, a.appointment_time DESC'
);
$stmt->execute([$profile['id']]);
$appointments = $stmt->fetchAll();

$upcoming = $completed = [];
foreach ($appointments as $a) {
    $ts = strtotime($a['appointment_date'] . ' ' . $a['appointment_time']);
    if ($ts >= time() && in_array($a['status'], ['Pending', 'Confirmed'], true)) {
        $upcoming[] = $a;
    }
    if ($a['status'] === 'Completed') {
        $completed[] = $a;
    }
}
usort($upcoming, fn($x, $y) => strcmp($x['appointment_date'] . $x['appointment_time'], $y['appointment_date'] . $y['appointment_time']));
$next = $upcoming[0] ?? null;

// A completed visit means reception has checked the patient's ID in person.
$status = [
    'verified'   => count($completed) > 0,
    'membership' => count($completed) > 0 ? 'Returning patient' : 'New patient',
    'upcoming'   => count($upcoming),
];

$pageTitle     = 'My Profile';
$activePage    = 'dashboard';
$vendorScripts = ['https://unpkg.com/lucide@0.460.0/dist/umd/lucide.min.js'];
$pageScripts   = ['profile.js'];
$pageStyles    = ['profile.css'];
require_once __DIR__ . '/includes/header.php';

render_profile_header($profile, $status, $tab);
?>

<section class="pf-body">
    <div class="container">

    <?php if ($tab === 'overview'): ?>
        <div class="pf-grid">
            <div class="pf-stack">
                <?php render_personal_info($profile); ?>

                <article class="pf-card">
                    <header class="pf-card-head">
                        <div><h2>Recent appointments</h2><p>Your latest bookings at CarePoint.</p></div>
                        <a href="<?= profile_url('appointments') ?>" class="pf-link">View all <?= lc('chevron-right') ?></a>
                    </header>
                    <?php if ($appointments): ?>
                        <ul class="pf-appts"><?php foreach (array_slice($appointments, 0, 3) as $a) { render_appt_row($a); } ?></ul>
                    <?php else: ?>
                        <?php render_profile_empty('calendar-days', 'No appointments yet', 'Book your first consultation and it will appear here.', ['Book appointment', url('book.php'), 'calendar-plus']); ?>
                    <?php endif; ?>
                </article>
            </div>

            <aside class="pf-stack">
                <article class="pf-card pf-next">
                    <span class="pf-eyebrow">Next appointment</span>
                    <?php if ($next): ?>
                        <h3><?= e($next['doctor_name']) ?></h3>
                        <p class="pf-muted"><?= e($next['department_name']) ?></p>
                        <ul class="pf-next-when">
                            <li><?= lc('calendar-days') ?> <?= e(format_date($next['appointment_date'], 'l, j F')) ?></li>
                            <li><?= lc('clock') ?> <?= e(format_time($next['appointment_time'])) ?></li>
                        </ul>
                        <?= status_badge($next['status']) ?>
                    <?php else: ?>
                        <p class="pf-muted">Nothing scheduled.</p>
                        <a href="<?= url('book.php') ?>" class="pf-btn pf-btn-solid pf-btn-block"><?= lc('calendar-plus') ?><span>Book appointment</span></a>
                    <?php endif; ?>
                </article>

                <article class="pf-card">
                    <span class="pf-eyebrow">Care summary</span>
                    <dl class="pf-summary">
                        <div><dt>Completed visits</dt><dd><?= count($completed) ?></dd></div>
                        <div><dt>Upcoming</dt><dd><?= count($upcoming) ?></dd></div>
                        <div><dt>Total bookings</dt><dd><?= count($appointments) ?></dd></div>
                    </dl>
                </article>
            </aside>
        </div>

    <?php elseif ($tab === 'personal'): ?>
        <?php if ($editing): ?>
            <form method="post" action="<?= profile_url('personal', ['edit' => 1]) ?>" class="pf-card pf-form" id="profileForm" novalidate>
                <?= csrf_field() ?>
                <header class="pf-card-head">
                    <div><h2>Edit personal information</h2><p>Keep these up to date so our team can reach you and treat you safely.</p></div>
                </header>

                <fieldset class="pf-fieldset">
                    <legend>Basic details</legend>
                    <div class="form-grid">
                        <div class="form-group <?= isset($errors['full_name']) ? 'has-error' : '' ?>">
                            <label for="full_name">Full name <span class="req">*</span></label>
                            <input type="text" id="full_name" name="full_name" value="<?= e($form['full_name']) ?>" required maxlength="100" autocomplete="name">
                            <?= field_error($errors, 'full_name') ?>
                        </div>
                        <div class="form-group">
                            <label for="email">Email address</label>
                            <input type="email" id="email" value="<?= e($profile['email']) ?>" disabled>
                            <small class="hint">Your sign-in email. Contact the clinic to change it.</small>
                        </div>
                        <div class="form-group <?= isset($errors['phone']) ? 'has-error' : '' ?>">
                            <label for="phone">Phone number <span class="req">*</span></label>
                            <input type="tel" id="phone" name="phone" value="<?= e($form['phone']) ?>" required maxlength="30" autocomplete="tel">
                            <?= field_error($errors, 'phone') ?>
                        </div>
                        <div class="form-group <?= isset($errors['gender']) ? 'has-error' : '' ?>">
                            <label for="gender">Gender</label>
                            <select id="gender" name="gender">
                                <option value="">Prefer not to say</option>
                                <?php foreach (['Male', 'Female', 'Other'] as $g): ?>
                                    <option <?= ($form['gender'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= field_error($errors, 'gender') ?>
                        </div>
                        <?php if ($hasExtended): ?>
                            <div class="form-group <?= isset($errors['date_of_birth']) ? 'has-error' : '' ?>">
                                <label for="date_of_birth">Date of birth</label>
                                <input type="date" id="date_of_birth" name="date_of_birth" value="<?= e($form['date_of_birth']) ?>" max="<?= date('Y-m-d') ?>" autocomplete="bday">
                                <?= field_error($errors, 'date_of_birth') ?>
                            </div>
                            <div class="form-group <?= isset($errors['blood_group']) ? 'has-error' : '' ?>">
                                <label for="blood_group">Blood group</label>
                                <select id="blood_group" name="blood_group">
                                    <option value="">Unknown</option>
                                    <?php foreach (BLOOD_GROUPS as $bg): ?>
                                        <option <?= ($form['blood_group'] ?? '') === $bg ? 'selected' : '' ?>><?= $bg ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?= field_error($errors, 'blood_group') ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </fieldset>

                <?php if ($hasExtended): ?>
                    <fieldset class="pf-fieldset">
                        <legend>Emergency contact</legend>
                        <div class="form-grid form-grid-3">
                            <div class="form-group <?= isset($errors['emergency_name']) ? 'has-error' : '' ?>">
                                <label for="emergency_name">Name</label>
                                <input type="text" id="emergency_name" name="emergency_name" value="<?= e($form['emergency_name']) ?>" maxlength="100">
                                <?= field_error($errors, 'emergency_name') ?>
                            </div>
                            <div class="form-group <?= isset($errors['emergency_relation']) ? 'has-error' : '' ?>">
                                <label for="emergency_relation">Relationship</label>
                                <input type="text" id="emergency_relation" name="emergency_relation" value="<?= e($form['emergency_relation']) ?>" maxlength="50" placeholder="e.g. Spouse">
                                <?= field_error($errors, 'emergency_relation') ?>
                            </div>
                            <div class="form-group <?= isset($errors['emergency_phone']) ? 'has-error' : '' ?>">
                                <label for="emergency_phone">Phone</label>
                                <input type="tel" id="emergency_phone" name="emergency_phone" value="<?= e($form['emergency_phone']) ?>" maxlength="30">
                                <?= field_error($errors, 'emergency_phone') ?>
                            </div>
                        </div>
                    </fieldset>
                <?php else: ?>
                    <div class="alert alert-info"><i class="fa-solid fa-circle-info"></i><span>Date of birth, blood group and emergency contact need a database update. Run <code>database/migrations/001_patient_profile_fields.sql</code> in phpMyAdmin to turn them on.</span></div>
                <?php endif; ?>

                <div class="pf-form-actions">
                    <a href="<?= profile_url('personal') ?>" class="pf-btn pf-btn-quiet"><span>Cancel</span></a>
                    <button type="submit" class="pf-btn pf-btn-solid"><?= lc('save') ?><span>Save changes</span></button>
                </div>
            </form>
        <?php else: ?>
            <div class="pf-narrow"><?php render_personal_info($profile); ?></div>
        <?php endif; ?>

    <?php elseif ($tab === 'history'): ?>
        <article class="pf-card pf-narrow">
            <header class="pf-card-head">
                <div><h2>Medical history</h2><p>Consultations you have completed at CarePoint, with notes from your care team.</p></div>
            </header>
            <?php if ($completed): ?>
                <ol class="pf-timeline">
                    <?php foreach ($completed as $a): ?>
                        <li>
                            <span class="pf-timeline-date"><?= e(format_date($a['appointment_date'], 'j M Y')) ?></span>
                            <strong><?= e($a['department_name']) ?> consultation</strong>
                            <span class="pf-muted"><?= e($a['doctor_name']) ?></span>
                            <?php if ($a['reason']): ?><p><?= e(mb_strimwidth($a['reason'], 0, 180, '…')) ?></p><?php endif; ?>
                            <?php if ($a['admin_note']): ?><p class="pf-note"><?= lc('stethoscope') ?> <?= e($a['admin_note']) ?></p><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php else: ?>
                <?php render_profile_empty('clipboard-list', 'No medical history yet', 'After your first completed consultation, a summary of each visit will be kept here.'); ?>
            <?php endif; ?>
        </article>

    <?php elseif ($tab === 'appointments'): ?>
        <article class="pf-card pf-narrow">
            <header class="pf-card-head">
                <div><h2>Appointments</h2><p><?= count($upcoming) ?> upcoming · <?= count($appointments) ?> in total</p></div>
                <a href="<?= url('dashboard.php') ?>" class="pf-link">Manage in dashboard <?= lc('chevron-right') ?></a>
            </header>
            <?php if ($appointments): ?>
                <ul class="pf-appts"><?php foreach ($appointments as $a) { render_appt_row($a); } ?></ul>
            <?php else: ?>
                <?php render_profile_empty('calendar-days', 'No appointments yet', 'Book a consultation with one of our doctors in under two minutes.', ['Book appointment', url('book.php'), 'calendar-plus']); ?>
            <?php endif; ?>
        </article>

    <?php elseif ($tab === 'prescriptions'): ?>
        <article class="pf-card pf-narrow">
            <?php render_profile_empty('pill', 'No prescriptions on file', 'Prescriptions issued by your CarePoint doctor will be listed here with dosage and refill details.', ['Book a consultation', url('book.php'), 'calendar-plus']); ?>
        </article>

    <?php elseif ($tab === 'records'): ?>
        <article class="pf-card pf-narrow">
            <?php render_profile_empty('folder-heart', 'No medical records yet', 'Lab results, scans and referral letters shared by the clinic will appear here.', ['Contact the clinic', url('contact.php'), 'phone']); ?>
        </article>

    <?php elseif ($tab === 'settings'): ?>
        <article class="pf-card pf-narrow">
            <header class="pf-card-head">
                <div><h2>Account settings</h2><p>How you sign in to CarePoint.</p></div>
            </header>
            <dl class="pf-rows pf-rows-single">
                <?php info_row('mail', 'Sign-in email', $profile['email']); ?>
                <?php info_row('id-card', 'Patient ID', patient_code((int)$profile['id'])); ?>
                <?php info_row('calendar-days', 'Member since', format_date($profile['created_at'], 'j F Y')); ?>
                <?php info_row('lock', 'Password', '••••••••••'); ?>
            </dl>
            <p class="pf-help"><?= lc('info') ?> To change your sign-in email or reset your password, call reception on <?= e(CLINIC_PHONE) ?>. We'll verify your identity first.</p>
            <div class="pf-form-actions">
                <a href="<?= url('logout.php') ?>" class="pf-btn pf-btn-quiet"><?= lc('log-out') ?><span>Sign out</span></a>
                <a href="<?= profile_url('personal', ['edit' => 1]) ?>#profileForm" class="pf-btn pf-btn-solid"><?= lc('pen-line') ?><span>Edit profile</span></a>
            </div>
        </article>
    <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
