<?php
$pageTitle = 'Patients';
$adminPage = 'patients';
require_once __DIR__ . '/includes/admin_header.php';

$q = trim($_GET['q'] ?? '');
$type = ($_GET['type'] ?? '') === 'guest' ? 'guest' : 'registered';

if ($type === 'registered') {
    $sql = "SELECT u.*, COUNT(a.id) AS appts, MAX(a.appointment_date) AS last_appt,
                   SUM(a.status IN ('Pending','Confirmed') AND a.appointment_date >= CURDATE()) AS upcoming
            FROM users u LEFT JOIN appointments a ON a.user_id = u.id";
    $params = [];
    if ($q !== '') {
        $sql .= ' WHERE u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?';
        $params = ["%$q%", "%$q%", "%$q%"];
    }
    $sql .= ' GROUP BY u.id ORDER BY u.created_at DESC';
} else {
    // Guests: people who booked without an account, grouped by email
    $sql = "SELECT a.email, MAX(a.patient_name) AS full_name, MAX(a.phone) AS phone, MAX(a.age) AS age, MAX(a.gender) AS gender,
                   COUNT(*) AS appts, MAX(a.appointment_date) AS last_appt, MIN(a.created_at) AS created_at,
                   SUM(a.status IN ('Pending','Confirmed') AND a.appointment_date >= CURDATE()) AS upcoming
            FROM appointments a WHERE a.user_id IS NULL";
    $params = [];
    if ($q !== '') {
        $sql .= ' AND (a.patient_name LIKE ? OR a.email LIKE ? OR a.phone LIKE ?)';
        $params = ["%$q%", "%$q%", "%$q%"];
    }
    $sql .= ' GROUP BY a.email ORDER BY created_at DESC';
}
$stmt = db()->prepare($sql);
$stmt->execute($params);
$patients = $stmt->fetchAll();
?>

<div class="toolbar">
    <div class="status-tabs status-tabs-inline">
        <a href="<?= url('admin/patients.php') ?>" class="<?= $type === 'registered' ? 'active' : '' ?>">Registered patients</a>
        <a href="<?= url('admin/patients.php?type=guest') ?>" class="<?= $type === 'guest' ? 'active' : '' ?>">Guest bookings</a>
    </div>
    <form class="toolbar-filters" method="get" action="<?= url('admin/patients.php') ?>">
        <?php if ($type === 'guest'): ?><input type="hidden" name="type" value="guest"><?php endif; ?>
        <div class="input-icon"><i class="fa-solid fa-magnifying-glass"></i>
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search name, email or phone" aria-label="Search patients">
        </div>
        <button type="submit" class="btn btn-ghost btn-sm">Search</button>
    </form>
</div>

<section class="panel">
    <div class="panel-head"><h2><?= count($patients) ?> <?= $type === 'guest' ? 'guest patient' : 'registered patient' ?><?= count($patients) === 1 ? '' : 's' ?></h2></div>
    <?php if ($patients): ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Patient</th><th>Contact</th><th>Age / Gender</th><th>Appointments</th><th>Last appointment</th><th><?= $type === 'guest' ? 'First booking' : 'Registered' ?></th><th class="text-right">Bookings</th></tr></thead>
                <tbody>
                <?php foreach ($patients as $p): ?>
                    <tr>
                        <td data-label="Patient"><div class="doc-cell"><span class="avatar-sm"><?= e(initials($p['full_name'])) ?></span><strong><?= e($p['full_name']) ?></strong></div></td>
                        <td data-label="Contact"><a href="mailto:<?= e($p['email']) ?>"><?= e($p['email']) ?></a><small class="cell-sub"><?= e($p['phone']) ?></small></td>
                        <td data-label="Age / Gender"><?= $p['age'] !== null ? (int)$p['age'] : '—' ?> / <?= e($p['gender'] ?: '—') ?></td>
                        <td data-label="Appointments"><?= (int)$p['appts'] ?> total<small class="cell-sub"><?= (int)$p['upcoming'] ?> upcoming</small></td>
                        <td data-label="Last appointment"><?= $p['last_appt'] ? e(format_date($p['last_appt'], 'j M Y')) : '—' ?></td>
                        <td data-label="Since"><?= e(format_date($p['created_at'], 'j M Y')) ?></td>
                        <td data-label="Bookings" class="text-right">
                            <a href="<?= url('admin/appointments.php?q=' . urlencode($p['email'])) ?>" class="btn btn-ghost btn-sm"><i class="fa-solid fa-list"></i> View bookings</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state empty-sm"><i class="fa-solid fa-hospital-user"></i><p>No patients found.</p></div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
