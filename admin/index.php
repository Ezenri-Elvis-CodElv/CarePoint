<?php
$pageTitle = 'Dashboard';
$adminPage = 'dashboard';
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_helpers.php';

$pdo = db();
$statusCounts = array_fill_keys(APPOINTMENT_STATUSES, 0);
foreach ($pdo->query('SELECT status, COUNT(*) c FROM appointments GROUP BY status') as $r) {
    $statusCounts[$r['status']] = (int)$r['c'];
}
$totalAppointments = array_sum($statusCounts);
$totalDoctors   = (int)$pdo->query('SELECT COUNT(*) FROM doctors')->fetchColumn();
$activeDoctors  = (int)$pdo->query("SELECT COUNT(*) FROM doctors WHERE status = 'Active'")->fetchColumn();
$totalPatients  = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$todayCount     = (int)$pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE() AND status IN ('Pending','Confirmed')")->fetchColumn();

$pending = $pdo->query(
    "SELECT a.*, dp.name AS department_name FROM appointments a JOIN departments dp ON dp.id = a.department_id
     WHERE a.status = 'Pending' ORDER BY a.appointment_date, a.appointment_time LIMIT 6"
)->fetchAll();

$upcoming = $pdo->query(
    "SELECT a.*, dp.name AS department_name FROM appointments a JOIN departments dp ON dp.id = a.department_id
     WHERE a.status = 'Confirmed' AND a.appointment_date >= CURDATE()
     ORDER BY a.appointment_date, a.appointment_time LIMIT 6"
)->fetchAll();

$byDept = $pdo->query(
    "SELECT dp.name, dp.icon, COUNT(a.id) AS c FROM departments dp
     LEFT JOIN appointments a ON a.department_id = dp.id AND a.status NOT IN ('Cancelled','Rejected')
     GROUP BY dp.id ORDER BY c DESC"
)->fetchAll();
$maxDept = max(1, ...array_map(fn($r) => (int)$r['c'], $byDept));
?>

<div class="stat-grid stat-grid-5">
    <a href="<?= url('admin/appointments.php') ?>" class="stat-card">
        <span class="stat-icon si-blue"><i class="fa-solid fa-calendar-days"></i></span>
        <div><strong><?= $totalAppointments ?></strong><span>Total appointments</span></div>
    </a>
    <a href="<?= url('admin/appointments.php?status=Pending') ?>" class="stat-card">
        <span class="stat-icon si-amber"><i class="fa-solid fa-hourglass-half"></i></span>
        <div><strong><?= $statusCounts['Pending'] ?></strong><span>Pending</span></div>
    </a>
    <a href="<?= url('admin/appointments.php?status=Confirmed') ?>" class="stat-card">
        <span class="stat-icon si-green"><i class="fa-solid fa-circle-check"></i></span>
        <div><strong><?= $statusCounts['Confirmed'] ?></strong><span>Confirmed</span></div>
    </a>
    <a href="<?= url('admin/appointments.php?status=Cancelled') ?>" class="stat-card">
        <span class="stat-icon si-red"><i class="fa-solid fa-ban"></i></span>
        <div><strong><?= $statusCounts['Cancelled'] ?></strong><span>Cancelled</span></div>
    </a>
    <a href="<?= url('admin/doctors.php') ?>" class="stat-card">
        <span class="stat-icon si-navy"><i class="fa-solid fa-user-doctor"></i></span>
        <div><strong><?= $totalDoctors ?></strong><span>Total doctors</span></div>
    </a>
</div>

<div class="mini-stats">
    <span><i class="fa-solid fa-calendar-day"></i> <strong><?= $todayCount ?></strong> active appointment<?= $todayCount === 1 ? '' : 's' ?> today</span>
    <span><i class="fa-solid fa-flag-checkered"></i> <strong><?= $statusCounts['Completed'] ?></strong> completed</span>
    <span><i class="fa-solid fa-circle-xmark"></i> <strong><?= $statusCounts['Rejected'] ?></strong> rejected</span>
    <span><i class="fa-solid fa-hospital-user"></i> <strong><?= $totalPatients ?></strong> registered patients</span>
    <span><i class="fa-solid fa-user-check"></i> <strong><?= $activeDoctors ?></strong> active doctors</span>
</div>

<div class="admin-grid">
    <section class="panel panel-wide">
        <div class="panel-head">
            <h2><i class="fa-solid fa-inbox"></i> Pending requests</h2>
            <a href="<?= url('admin/appointments.php?status=Pending') ?>" class="btn btn-ghost btn-sm">View all</a>
        </div>
        <?php if ($pending): ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Reference</th><th>Patient</th><th>Doctor</th><th>Date &amp; time</th><th class="text-right">Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($pending as $a): ?>
                        <tr>
                            <td data-label="Reference"><a href="<?= url('admin/appointment.php?id=' . (int)$a['id']) ?>" class="ref-link"><?= e($a['reference']) ?></a></td>
                            <td data-label="Patient"><strong><?= e($a['patient_name']) ?></strong><small class="cell-sub"><?= e($a['phone']) ?></small></td>
                            <td data-label="Doctor"><?= e($a['doctor_name']) ?><small class="cell-sub"><?= e($a['department_name']) ?></small></td>
                            <td data-label="Date & time"><?= e(format_date($a['appointment_date'], 'j M Y')) ?><small class="cell-sub"><?= e(format_time($a['appointment_time'])) ?></small></td>
                            <td data-label="Actions" class="text-right"><?= admin_quick_actions($a, 'admin/index.php') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state empty-sm"><i class="fa-solid fa-mug-hot"></i><p>No pending requests – you're all caught up!</p></div>
        <?php endif; ?>
    </section>

    <section class="panel">
        <div class="panel-head"><h2><i class="fa-solid fa-chart-simple"></i> Bookings by department</h2></div>
        <ul class="bar-list">
            <?php foreach ($byDept as $d): ?>
                <li>
                    <div class="bar-label"><span><i class="fa-solid <?= e($d['icon']) ?>"></i> <?= e($d['name']) ?></span><strong><?= (int)$d['c'] ?></strong></div>
                    <div class="bar"><span style="width: <?= round((int)$d['c'] / $maxDept * 100) ?>%"></span></div>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="panel panel-wide">
        <div class="panel-head">
            <h2><i class="fa-solid fa-calendar-check"></i> Upcoming confirmed appointments</h2>
            <a href="<?= url('admin/appointments.php?status=Confirmed') ?>" class="btn btn-ghost btn-sm">View all</a>
        </div>
        <?php if ($upcoming): ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>Reference</th><th>Patient</th><th>Doctor</th><th>Date &amp; time</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($upcoming as $a): ?>
                        <tr>
                            <td data-label="Reference"><a href="<?= url('admin/appointment.php?id=' . (int)$a['id']) ?>" class="ref-link"><?= e($a['reference']) ?></a></td>
                            <td data-label="Patient"><?= e($a['patient_name']) ?></td>
                            <td data-label="Doctor"><?= e($a['doctor_name']) ?><small class="cell-sub"><?= e($a['department_name']) ?></small></td>
                            <td data-label="Date & time"><?= e(format_date($a['appointment_date'], 'j M Y')) ?><small class="cell-sub"><?= e(format_time($a['appointment_time'])) ?></small></td>
                            <td data-label="Status"><?= status_badge($a['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state empty-sm"><i class="fa-regular fa-calendar"></i><p>No upcoming confirmed appointments.</p></div>
        <?php endif; ?>
    </section>

    <section class="panel">
        <div class="panel-head"><h2><i class="fa-solid fa-bolt"></i> Quick actions</h2></div>
        <div class="quick-links">
            <a href="<?= url('admin/doctor-form.php') ?>"><i class="fa-solid fa-user-plus"></i> Add new doctor</a>
            <a href="<?= url('admin/appointments.php?date=' . date('Y-m-d')) ?>"><i class="fa-solid fa-calendar-day"></i> Today's appointments</a>
            <a href="<?= url('admin/patients.php') ?>"><i class="fa-solid fa-hospital-user"></i> View patients</a>
            <a href="<?= url('admin/messages.php') ?>"><i class="fa-solid fa-envelope-open-text"></i> Contact messages <?= $unreadCount ? "<em class=\"menu-badge\">$unreadCount</em>" : '' ?></a>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
