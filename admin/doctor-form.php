<?php
/**
 * Add / edit a doctor.
 */
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$id      = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$editing = $id > 0;
$doctor  = $editing ? get_doctor($id, false) : null;
if ($editing && !$doctor) {
    flash('error', 'Doctor not found.');
    redirect('admin/doctors.php');
}

$departments = get_departments();
$deptIds     = array_map('intval', array_column($departments, 'id'));
$errors      = [];

$values = $doctor ?: [
    'full_name' => 'Dr. ', 'department_id' => '', 'specialisation' => '', 'qualification' => '',
    'experience_years' => '', 'bio' => '', 'email' => '', 'phone' => '',
    'available_days' => 'Mon,Tue,Wed,Thu,Fri', 'start_time' => '09:00', 'end_time' => '17:00',
    'slot_duration' => 30, 'consultation_fee' => '', 'is_featured' => 0, 'status' => 'Active', 'photo' => null,
];
$values['start_time'] = substr($values['start_time'], 0, 5);
$values['end_time']   = substr($values['end_time'], 0, 5);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = ['full_name', 'department_id', 'specialisation', 'qualification', 'experience_years', 'bio', 'email', 'phone',
               'start_time', 'end_time', 'slot_duration', 'consultation_fee', 'status'];
    foreach ($fields as $f) {
        $values[$f] = post($f);
    }
    $days = array_values(array_intersect(array_keys(WEEK_DAYS), (array)($_POST['available_days'] ?? [])));
    $values['available_days'] = implode(',', $days);
    $values['is_featured'] = isset($_POST['is_featured']) ? 1 : 0;

    if (!verify_csrf()) {
        $errors['form'] = 'Your session has expired. Please try again.';
    }
    if (mb_strlen($values['full_name']) < 5) {
        $errors['full_name'] = 'Please enter the doctor\'s full name.';
    }
    if (!in_array((int)$values['department_id'], $deptIds, true)) {
        $errors['department_id'] = 'Please select a department.';
    }
    if (mb_strlen($values['specialisation']) < 3) {
        $errors['specialisation'] = 'Please enter a specialisation.';
    }
    if (!ctype_digit((string)$values['experience_years']) || (int)$values['experience_years'] > 60) {
        $errors['experience_years'] = 'Enter years of experience (0–60).';
    }
    if (mb_strlen($values['bio']) < 20) {
        $errors['bio'] = 'Please write a short biography (at least 20 characters).';
    }
    if ($values['email'] !== '' && !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    if ($values['phone'] !== '' && !valid_phone($values['phone'])) {
        $errors['phone'] = 'Please enter a valid phone number.';
    }
    if (!$days) {
        $errors['available_days'] = 'Select at least one consulting day.';
    }
    $validTime = fn($t) => (bool)preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $t);
    if (!$validTime($values['start_time']) || !$validTime($values['end_time'])) {
        $errors['start_time'] = 'Please enter valid start and end times.';
    } elseif ($values['end_time'] <= $values['start_time']) {
        $errors['start_time'] = 'End time must be after start time.';
    } elseif ((strtotime("2000-01-01 {$values['end_time']}") - strtotime("2000-01-01 {$values['start_time']}")) / 60 < (int)$values['slot_duration']) {
        $errors['start_time'] = 'Working hours are shorter than a single appointment slot.';
    }
    if (!in_array((int)$values['slot_duration'], [15, 20, 30, 45, 60], true)) {
        $errors['slot_duration'] = 'Please choose a slot duration.';
    }
    if (!is_numeric($values['consultation_fee']) || (float)$values['consultation_fee'] < 0) {
        $errors['consultation_fee'] = 'Please enter a valid fee (0 or more).';
    }
    if (!in_array($values['status'], ['Active', 'Inactive'], true)) {
        $errors['status'] = 'Invalid status.';
    }

    // Photo upload
    $newPhoto = null;
    if (!empty($_FILES['photo']['name'])) {
        $file = $_FILES['photo'];
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $mime = $file['error'] === UPLOAD_ERR_OK ? (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) : '';
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors['photo'] = 'The photo could not be uploaded (error code ' . (int)$file['error'] . ').';
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $errors['photo'] = 'The photo must be smaller than 2 MB.';
        } elseif (!isset($allowed[$mime]) || !getimagesize($file['tmp_name'])) {
            $errors['photo'] = 'Please upload a JPG, PNG or WEBP image.';
        } elseif (!$errors) {
            $newPhoto = 'upload_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
            if (!is_dir(DOCTOR_UPLOAD_DIR) || !is_writable(DOCTOR_UPLOAD_DIR)
                || !move_uploaded_file($file['tmp_name'], DOCTOR_UPLOAD_DIR . $newPhoto)) {
                $errors['photo'] = 'Could not save the photo. Check that assets/images/doctors/ is writable.';
                $newPhoto = null;
            }
        }
    }

    if (!$errors) {
        $photo = $doctor['photo'] ?? null;
        if ($newPhoto || isset($_POST['remove_photo'])) {
            if ($photo && str_starts_with($photo, 'upload_') && is_file(DOCTOR_UPLOAD_DIR . $photo)) {
                @unlink(DOCTOR_UPLOAD_DIR . $photo);
            }
            $photo = $newPhoto;
        }

        $data = [
            (int)$values['department_id'], $values['full_name'], $values['specialisation'], $values['qualification'],
            (int)$values['experience_years'], $values['bio'], $photo, $values['email'] ?: null, $values['phone'] ?: null,
            $values['available_days'], $values['start_time'] . ':00', $values['end_time'] . ':00', (int)$values['slot_duration'],
            round((float)$values['consultation_fee'], 2), $values['is_featured'], $values['status'],
        ];
        $cols = 'department_id = ?, full_name = ?, specialisation = ?, qualification = ?, experience_years = ?, bio = ?, photo = ?,
                 email = ?, phone = ?, available_days = ?, start_time = ?, end_time = ?, slot_duration = ?, consultation_fee = ?,
                 is_featured = ?, status = ?';
        if ($editing) {
            $data[] = $id;
            db()->prepare("UPDATE doctors SET $cols WHERE id = ?")->execute($data);
            flash('success', "{$values['full_name']}'s profile has been updated.");
        } else {
            db()->prepare("INSERT INTO doctors SET $cols")->execute($data);
            flash('success', "{$values['full_name']} has been added to the team.");
        }
        redirect('admin/doctors.php');
    } elseif ($newPhoto) {
        @unlink(DOCTOR_UPLOAD_DIR . $newPhoto);
    }
}

$selectedDays = explode(',', $values['available_days']);
$pageTitle = $editing ? 'Edit Doctor' : 'Add Doctor';
$adminPage = 'doctors';
require_once __DIR__ . '/includes/admin_header.php';
?>

<a href="<?= url('admin/doctors.php') ?>" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to doctors</a>

<form method="post" enctype="multipart/form-data" class="panel doctor-form js-validate" novalidate
      action="<?= url('admin/doctor-form.php' . ($editing ? '?id=' . $id : '')) ?>">
    <?= csrf_field() ?>
    <?php if ($editing): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i><span><?= e($errors['form'] ?? 'Please correct the highlighted fields.') ?></span></div>
    <?php endif; ?>

    <div class="doctor-form-grid">
        <div class="photo-col">
            <div class="photo-preview">
                <img id="photoPreview" src="<?= doctor_photo($values['photo'] ?? null) ?>" alt="Doctor photo preview">
            </div>
            <div class="form-group <?= isset($errors['photo']) ? 'has-error' : '' ?>">
                <label for="photo" class="btn btn-ghost btn-block file-btn"><i class="fa-solid fa-camera"></i> <?= !empty($values['photo']) ? 'Change photo' : 'Upload photo' ?></label>
                <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp" class="visually-hidden-file">
                <small class="hint">JPG, PNG or WEBP · max 2 MB · square images look best</small>
                <?= field_error($errors, 'photo') ?>
            </div>
            <?php if (!empty($values['photo'])): ?>
                <label class="checkbox"><input type="checkbox" name="remove_photo" value="1"> Remove current photo</label>
            <?php endif; ?>
        </div>

        <div class="fields-col">
            <h3 class="detail-title">Profile</h3>
            <div class="form-grid">
                <div class="form-group <?= isset($errors['full_name']) ? 'has-error' : '' ?>">
                    <label for="full_name">Full name <span class="req">*</span></label>
                    <input type="text" id="full_name" name="full_name" value="<?= e($values['full_name']) ?>" required minlength="5" maxlength="100">
                    <?= field_error($errors, 'full_name') ?>
                </div>
                <div class="form-group <?= isset($errors['department_id']) ? 'has-error' : '' ?>">
                    <label for="department_id">Department <span class="req">*</span></label>
                    <select id="department_id" name="department_id" required>
                        <option value="">Select department</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= (int)$d['id'] ?>" <?= (string)$values['department_id'] === (string)$d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= field_error($errors, 'department_id') ?>
                </div>
                <div class="form-group <?= isset($errors['specialisation']) ? 'has-error' : '' ?>">
                    <label for="specialisation">Specialisation <span class="req">*</span></label>
                    <input type="text" id="specialisation" name="specialisation" value="<?= e($values['specialisation']) ?>" placeholder="e.g. Consultant Cardiologist" required minlength="3" maxlength="120">
                    <?= field_error($errors, 'specialisation') ?>
                </div>
                <div class="form-group">
                    <label for="qualification">Qualifications</label>
                    <input type="text" id="qualification" name="qualification" value="<?= e($values['qualification']) ?>" placeholder="e.g. MBBS, MD" maxlength="150">
                </div>
                <div class="form-group <?= isset($errors['experience_years']) ? 'has-error' : '' ?>">
                    <label for="experience_years">Years of experience <span class="req">*</span></label>
                    <input type="number" id="experience_years" name="experience_years" value="<?= e($values['experience_years']) ?>" min="0" max="60" required>
                    <?= field_error($errors, 'experience_years') ?>
                </div>
                <div class="form-group <?= isset($errors['consultation_fee']) ? 'has-error' : '' ?>">
                    <label for="consultation_fee">Consultation fee ($) <span class="req">*</span></label>
                    <input type="number" id="consultation_fee" name="consultation_fee" value="<?= e($values['consultation_fee']) ?>" min="0" step="0.01" required>
                    <?= field_error($errors, 'consultation_fee') ?>
                </div>
                <div class="form-group <?= isset($errors['email']) ? 'has-error' : '' ?>">
                    <label for="demail">Email</label>
                    <input type="email" id="demail" name="email" value="<?= e($values['email']) ?>" maxlength="150">
                    <?= field_error($errors, 'email') ?>
                </div>
                <div class="form-group <?= isset($errors['phone']) ? 'has-error' : '' ?>">
                    <label for="dphone">Phone</label>
                    <input type="tel" id="dphone" name="phone" value="<?= e($values['phone']) ?>" pattern="^\+?[0-9\s\-().]{7,20}$">
                    <?= field_error($errors, 'phone') ?>
                </div>
                <div class="form-group col-2 <?= isset($errors['bio']) ? 'has-error' : '' ?>">
                    <label for="bio">Short biography <span class="req">*</span></label>
                    <textarea id="bio" name="bio" rows="4" required minlength="20" maxlength="1500"><?= e($values['bio']) ?></textarea>
                    <?= field_error($errors, 'bio') ?>
                </div>
            </div>

            <h3 class="detail-title">Availability</h3>
            <div class="form-group <?= isset($errors['available_days']) ? 'has-error' : '' ?>">
                <label>Consulting days <span class="req">*</span></label>
                <div class="day-picker">
                    <?php foreach (WEEK_DAYS as $short => $long): ?>
                        <label class="day-chip"><input type="checkbox" name="available_days[]" value="<?= $short ?>" <?= in_array($short, $selectedDays, true) ? 'checked' : '' ?>><span title="<?= $long ?>"><?= $short ?></span></label>
                    <?php endforeach; ?>
                </div>
                <?= field_error($errors, 'available_days') ?>
            </div>
            <div class="form-grid form-grid-3">
                <div class="form-group <?= isset($errors['start_time']) ? 'has-error' : '' ?>">
                    <label for="start_time">Start time <span class="req">*</span></label>
                    <input type="time" id="start_time" name="start_time" value="<?= e($values['start_time']) ?>" required>
                    <?= field_error($errors, 'start_time') ?>
                </div>
                <div class="form-group">
                    <label for="end_time">End time <span class="req">*</span></label>
                    <input type="time" id="end_time" name="end_time" value="<?= e($values['end_time']) ?>" required>
                </div>
                <div class="form-group <?= isset($errors['slot_duration']) ? 'has-error' : '' ?>">
                    <label for="slot_duration">Slot length</label>
                    <select id="slot_duration" name="slot_duration">
                        <?php foreach ([15, 20, 30, 45, 60] as $m): ?>
                            <option value="<?= $m ?>" <?= (int)$values['slot_duration'] === $m ? 'selected' : '' ?>><?= $m ?> minutes</option>
                        <?php endforeach; ?>
                    </select>
                    <?= field_error($errors, 'slot_duration') ?>
                </div>
            </div>

            <h3 class="detail-title">Visibility</h3>
            <div class="form-grid">
                <div class="form-group">
                    <label for="dstatus">Status</label>
                    <select id="dstatus" name="status">
                        <option value="Active" <?= $values['status'] === 'Active' ? 'selected' : '' ?>>Active – accepting bookings</option>
                        <option value="Inactive" <?= $values['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive – hidden from website</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <label class="checkbox"><input type="checkbox" name="is_featured" value="1" <?= $values['is_featured'] ? 'checked' : '' ?>> Feature on the home page</label>
                </div>
            </div>

            <div class="form-actions">
                <a href="<?= url('admin/doctors.php') ?>" class="btn btn-ghost">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> <?= $editing ? 'Save changes' : 'Add doctor' ?></button>
            </div>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
