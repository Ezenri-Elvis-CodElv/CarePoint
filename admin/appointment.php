<?php
$pageTitle = 'Appointment Details';
$adminPage = 'appointments';
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_helpers.php';

$id   = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare(
    'SELECT a.*, dp.name AS department_name, d.photo, d.specialisation, d.phone AS doctor_phone,
            u.id AS account_id, u.created_at AS account_created
     FROM appointments a
     JOIN departments dp ON dp.id = a.department_id
     LEFT JOIN doctors d ON d.id = a.doctor_id
     LEFT JOIN users u ON u.id = a.user_id
     WHERE a.id = ?'
);
$stmt->execute([$id]);
$a = $stmt->fetch();

if (!$a): ?>
    <div class="panel"><div class="empty-state"><i class="fa-solid fa-circle-question"></i><h3>Appointment not found</h3>
        <a href="<?= url('admin/appointments.php') ?>" class="btn btn-primary">Back to appointments</a></div></div>
<?php require_once __DIR__ . '/includes/admin_footer.php'; exit; endif;

// Patient history (same email)
$hist = db()->prepare('SELECT id, reference, doctor_name, appointment_date, appointment_time, status FROM appointments WHERE email = ? AND id <> ? ORDER BY appointment_date DESC LIMIT 10');
$hist->execute([$a['email'], $a['id']]);
$history = $hist->fetchAll();
$return = 'admin/appointment.php?id=' . (int)$a['id'];
?>

<a href="<?= url('admin/appointments.php') ?>" class="back-link"><i class="fa-solid fa-arrow-left"></i> All appointments</a>

<div class="admin-grid">
    <section class="panel panel-wide">
        <div class="panel-head">
            <div>
                <span class="muted-sm">Booking reference</span>
                <h2 class="ref-title"><?= e($a['reference']) ?></h2>
            </div>
            <?= status_badge($a['status']) ?>
        </div>

        <div class="detail-columns">
            <div>
                <h3 class="detail-title"><i class="fa-solid fa-user"></i> Patient information</h3>
                <dl class="details-list">
                    <div><dt>Full name</dt><dd><?= e($a['patient_name']) ?></dd></div>
                    <div><dt>Email</dt><dd><a href="mailto:<?= e($a['email']) ?>"><?= e($a['email']) ?></a></dd></div>
                    <div><dt>Phone</dt><dd><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $a['phone'])) ?>"><?= e($a['phone']) ?></a></dd></div>
                    <div><dt>Age / Gender</dt><dd><?= (int)$a['age'] ?> · <?= e($a['gender']) ?></dd></div>
                    <div><dt>Account</dt><dd><?= $a['account_id'] ? 'Registered patient (since ' . e(format_date($a['account_created'], 'M Y')) . ')' : 'Guest booking' ?></dd></div>
                </dl>
            </div>
            <div>
                <h3 class="detail-title"><i class="fa-solid fa-calendar-check"></i> Appointment</h3>
                <dl class="details-list">
                    <div><dt>Doctor</dt><dd><?= e($a['doctor_name']) ?><?= $a['doctor_id'] ? '' : ' <em class="muted-sm">(no longer listed)</em>' ?></dd></div>
                    <div><dt>Department</dt><dd><?= e($a['department_name']) ?></dd></div>
                    <div><dt>Date</dt><dd><?= e(format_date($a['appointment_date'], 'l, j F Y')) ?></dd></div>
                    <div><dt>Time</dt><dd><?= e(format_time($a['appointment_time'])) ?></dd></div>
                    <div><dt>Booked on</dt><dd><?= e(format_date($a['created_at'], 'j M Y, g:i A')) ?></dd></div>
                    <div><dt>Last updated</dt><dd><?= e(format_date($a['updated_at'], 'j M Y, g:i A')) ?></dd></div>
                </dl>
            </div>
        </div>
        <h3 class="detail-title"><i class="fa-solid fa-notes-medical"></i> Reason for appointment</h3>
        <p class="reason-box"><?= nl2br(e($a['reason'])) ?></p>
    </section>

    <section class="panel">
        <div class="panel-head"><h2><i class="fa-solid fa-pen-to-square"></i> Update status</h2></div>
        <?php if ($a['status'] === 'Pending'): ?>
            <div class="btn-row">
                <form method="post" action="<?= url('admin/appointment-action.php') ?>">
                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><input type="hidden" name="status" value="Confirmed"><input type="hidden" name="return" value="<?= e($return) ?>">
                    <button class="btn btn-success btn-block" type="submit"><i class="fa-solid fa-check"></i> Approve</button>
                </form>
                <form method="post" action="<?= url('admin/appointment-action.php') ?>" data-confirm="Reject this appointment request?">
                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><input type="hidden" name="status" value="Rejected"><input type="hidden" name="return" value="<?= e($return) ?>">
                    <button class="btn btn-danger btn-block" type="submit"><i class="fa-solid fa-xmark"></i> Reject</button>
                </form>
            </div>
            <hr class="divider">
        <?php endif; ?>
        <form method="post" action="<?= url('admin/appointment-action.php') ?>" class="js-validate" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
            <input type="hidden" name="return" value="<?= e($return) ?>">
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <?php foreach (APPOINTMENT_STATUSES as $s): ?>
                        <option value="<?= $s ?>" <?= $a['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="admin_note">Note to patient (optional)</label>
                <textarea id="admin_note" name="admin_note" rows="3" maxlength="255" placeholder="e.g. Please bring your previous test results."><?= e($a['admin_note']) ?></textarea>
                <small class="hint">Shown to the patient when they track this booking.</small>
            </div>
            <button type="submit" class="btn btn-primary btn-block"><i class="fa-solid fa-floppy-disk"></i> Save changes</button>
        </form>
    </section>

    <section class="panel panel-wide">
        <div class="panel-head"><h2><i class="fa-solid fa-clock-rotate-left"></i> Other bookings by this patient</h2></div>
        <?php if ($history): ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Reference</th><th>Doctor</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($history as $h): ?>
                        <tr>
                            <td data-label="Reference"><a class="ref-link" href="<?= url('admin/appointment.php?id=' . (int)$h['id']) ?>"><?= e($h['reference']) ?></a></td>
                            <td data-label="Doctor"><?= e($h['doctor_name']) ?></td>
                            <td data-label="Date"><?= e(format_date($h['appointment_date'], 'j M Y')) ?>, <?= e(format_time($h['appointment_time'])) ?></td>
                            <td data-label="Status"><?= status_badge($h['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state empty-sm"><p>This is the patient's only booking.</p></div>
        <?php endif; ?>
    </section>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
