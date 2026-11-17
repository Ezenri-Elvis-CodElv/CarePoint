<?php
$pageTitle = 'Doctors';
$adminPage = 'doctors';
require_once __DIR__ . '/includes/admin_header.php';

$q    = trim($_GET['q'] ?? '');
$dept = (int)($_GET['department'] ?? 0);
$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(d.full_name LIKE ? OR d.specialisation LIKE ? OR d.email LIKE ?)';
    array_push($params, "%$q%", "%$q%", "%$q%");
}
if ($dept) {
    $where[] = 'd.department_id = ?';
    $params[] = $dept;
}
$stmt = db()->prepare(
    "SELECT d.*, dp.name AS department_name,
            (SELECT COUNT(*) FROM appointments a WHERE a.doctor_id = d.id) AS total_appts,
            (SELECT COUNT(*) FROM appointments a WHERE a.doctor_id = d.id AND a.status IN ('Pending','Confirmed') AND a.appointment_date >= CURDATE()) AS upcoming_appts
     FROM doctors d JOIN departments dp ON dp.id = d.department_id " .
    ($where ? 'WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY dp.id, d.full_name'
);
$stmt->execute($params);
$doctors = $stmt->fetchAll();
$departments = get_departments();
?>

<div class="toolbar">
    <form class="toolbar-filters" method="get" action="<?= url('admin/doctors.php') ?>">
        <div class="input-icon"><i class="fa-solid fa-magnifying-glass"></i>
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search doctors" aria-label="Search doctors">
        </div>
        <select name="department" class="js-autosubmit" aria-label="Filter by department">
            <option value="">All departments</option>
            <?php foreach ($departments as $d): ?>
                <option value="<?= (int)$d['id'] ?>" <?= $dept === (int)$d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-ghost btn-sm">Search</button>
    </form>
    <a href="<?= url('admin/doctor-form.php') ?>" class="btn btn-primary"><i class="fa-solid fa-user-plus"></i> Add Doctor</a>
</div>

<section class="panel">
    <div class="panel-head"><h2><?= count($doctors) ?> doctor<?= count($doctors) === 1 ? '' : 's' ?></h2></div>
    <?php if ($doctors): ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Doctor</th><th>Department</th><th>Availability</th><th>Fee</th><th>Bookings</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($doctors as $d): ?>
                    <tr>
                        <td data-label="Doctor">
                            <div class="doc-cell">
                                <img src="<?= doctor_photo($d['photo']) ?>" alt="">
                                <div><strong><?= e($d['full_name']) ?></strong><small class="cell-sub"><?= e($d['specialisation']) ?></small></div>
                            </div>
                        </td>
                        <td data-label="Department"><?= e($d['department_name']) ?></td>
                        <td data-label="Availability"><?= e(format_days($d)) ?><small class="cell-sub"><?= e(format_hours($d)) ?> · <?= (int)$d['slot_duration'] ?> min slots</small></td>
                        <td data-label="Fee">$<?= number_format((float)$d['consultation_fee'], 2) ?></td>
                        <td data-label="Bookings"><a href="<?= url('admin/appointments.php?doctor=' . (int)$d['id']) ?>"><?= (int)$d['total_appts'] ?> total</a><small class="cell-sub"><?= (int)$d['upcoming_appts'] ?> upcoming</small></td>
                        <td data-label="Status">
                            <span class="badge <?= $d['status'] === 'Active' ? 'badge-confirmed' : 'badge-cancelled' ?>"><?= e($d['status']) ?></span>
                            <?php if ($d['is_featured']): ?><span class="badge badge-featured"><i class="fa-solid fa-star"></i> Featured</span><?php endif; ?>
                        </td>
                        <td data-label="Actions" class="text-right">
                            <div class="row-actions">
                                <a href="<?= url('doctors.php#doctor-' . (int)$d['id']) ?>" target="_blank" rel="noopener" class="icon-btn" title="View on website"><i class="fa-solid fa-eye"></i></a>
                                <a href="<?= url('admin/doctor-form.php?id=' . (int)$d['id']) ?>" class="icon-btn" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                <form method="post" action="<?= url('admin/doctor-delete.php') ?>"
                                      data-confirm="Delete <?= e($d['full_name']) ?>? <?= $d['total_appts'] ? 'Past appointment records will be kept for reference.' : '' ?> This cannot be undone.">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                                    <button type="submit" class="icon-btn icon-btn-danger" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state empty-sm"><i class="fa-solid fa-user-doctor"></i><p>No doctors found.</p>
            <a href="<?= url('admin/doctor-form.php') ?>" class="btn btn-primary">Add a doctor</a></div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
