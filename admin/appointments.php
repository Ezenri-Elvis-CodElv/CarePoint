<?php
$pageTitle = 'Appointments';
$adminPage = 'appointments';
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_helpers.php';

$departments = get_departments();
$doctors     = db()->query('SELECT id, full_name FROM doctors ORDER BY full_name')->fetchAll();

// Filters
$f = [
    'q'          => trim($_GET['q'] ?? ''),
    'status'     => in_array($_GET['status'] ?? '', APPOINTMENT_STATUSES, true) ? $_GET['status'] : '',
    'department' => (int)($_GET['department'] ?? 0),
    'doctor'     => (int)($_GET['doctor'] ?? 0),
    'date'       => valid_date($_GET['date'] ?? '') ? $_GET['date'] : '',
];

$where  = [];
$params = [];
if ($f['q'] !== '') {
    $where[] = '(a.reference LIKE ? OR a.patient_name LIKE ? OR a.email LIKE ? OR a.phone LIKE ?)';
    array_push($params, "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%");
}
if ($f['status'])     { $where[] = 'a.status = ?';           $params[] = $f['status']; }
if ($f['department']) { $where[] = 'a.department_id = ?';    $params[] = $f['department']; }
if ($f['doctor'])     { $where[] = 'a.doctor_id = ?';        $params[] = $f['doctor']; }
if ($f['date'])       { $where[] = 'a.appointment_date = ?'; $params[] = $f['date']; }

$sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Pagination
$perPage = 15;
$page    = max(1, (int)($_GET['page'] ?? 1));
$count   = db()->prepare("SELECT COUNT(*) FROM appointments a $sqlWhere");
$count->execute($params);
$total   = (int)$count->fetchColumn();
$pages   = max(1, (int)ceil($total / $perPage));
$page    = min($page, $pages);
$offset  = ($page - 1) * $perPage;

$stmt = db()->prepare(
    "SELECT a.*, dp.name AS department_name FROM appointments a
     JOIN departments dp ON dp.id = a.department_id
     $sqlWhere
     ORDER BY (a.status = 'Pending') DESC, a.appointment_date DESC, a.appointment_time DESC
     LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$query = array_filter($f);
$returnUrl = 'admin/appointments.php' . ($query || $page > 1 ? '?' . http_build_query($query + ($page > 1 ? ['page' => $page] : [])) : '');
?>

<div class="status-tabs">
    <?php $base = array_diff_key($query, ['status' => 1]); ?>
    <a href="<?= url('admin/appointments.php' . ($base ? '?' . http_build_query($base) : '')) ?>" class="<?= !$f['status'] ? 'active' : '' ?>">All</a>
    <?php foreach (APPOINTMENT_STATUSES as $s): ?>
        <a href="<?= url('admin/appointments.php?' . http_build_query($base + ['status' => $s])) ?>" class="<?= $f['status'] === $s ? 'active' : '' ?>"><?= $s ?></a>
    <?php endforeach; ?>
</div>

<form class="panel filter-panel" method="get" action="<?= url('admin/appointments.php') ?>">
    <div class="filter-fields">
        <div class="form-group">
            <label for="q">Search</label>
            <div class="input-icon"><i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" id="q" name="q" value="<?= e($f['q']) ?>" placeholder="Reference, name, email or phone">
            </div>
        </div>
        <div class="form-group">
            <label for="fstatus">Status</label>
            <select id="fstatus" name="status">
                <option value="">All statuses</option>
                <?php foreach (APPOINTMENT_STATUSES as $s): ?>
                    <option value="<?= $s ?>" <?= $f['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="fdept">Department</label>
            <select id="fdept" name="department">
                <option value="">All departments</option>
                <?php foreach ($departments as $d): ?>
                    <option value="<?= (int)$d['id'] ?>" <?= $f['department'] === (int)$d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="fdoc">Doctor</label>
            <select id="fdoc" name="doctor">
                <option value="">All doctors</option>
                <?php foreach ($doctors as $d): ?>
                    <option value="<?= (int)$d['id'] ?>" <?= $f['doctor'] === (int)$d['id'] ? 'selected' : '' ?>><?= e($d['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="fdate">Date</label>
            <input type="date" id="fdate" name="date" value="<?= e($f['date']) ?>">
        </div>
    </div>
    <div class="filter-actions">
        <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-filter"></i> Apply</button>
        <a href="<?= url('admin/appointments.php') ?>" class="btn btn-ghost btn-sm">Reset</a>
    </div>
</form>

<section class="panel">
    <div class="panel-head">
        <h2><?= $total ?> appointment<?= $total === 1 ? '' : 's' ?> found</h2>
    </div>
    <?php if ($rows): ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                <tr><th>Reference</th><th>Patient</th><th>Doctor / Dept.</th><th>Date &amp; time</th><th>Status</th><th>Change status</th><th class="text-right">Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $a): ?>
                    <tr>
                        <td data-label="Reference"><a href="<?= url('admin/appointment.php?id=' . (int)$a['id']) ?>" class="ref-link"><?= e($a['reference']) ?></a><small class="cell-sub">Booked <?= e(format_date($a['created_at'], 'j M')) ?></small></td>
                        <td data-label="Patient"><strong><?= e($a['patient_name']) ?></strong><small class="cell-sub"><?= e($a['email']) ?></small></td>
                        <td data-label="Doctor"><?= e($a['doctor_name']) ?><small class="cell-sub"><?= e($a['department_name']) ?></small></td>
                        <td data-label="Date & time"><?= e(format_date($a['appointment_date'], 'j M Y')) ?><small class="cell-sub"><?= e(format_time($a['appointment_time'])) ?></small></td>
                        <td data-label="Status"><?= status_badge($a['status']) ?></td>
                        <td data-label="Change status">
                            <form method="post" action="<?= url('admin/appointment-action.php') ?>" class="status-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                                <input type="hidden" name="return" value="<?= e($returnUrl) ?>">
                                <select name="status" class="select-sm js-autosubmit" aria-label="Change status for <?= e($a['reference']) ?>">
                                    <?php foreach (APPOINTMENT_STATUSES as $s): ?>
                                        <option value="<?= $s ?>" <?= $a['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <noscript><button type="submit" class="btn btn-sm btn-primary">Save</button></noscript>
                            </form>
                        </td>
                        <td data-label="Actions" class="text-right"><?= admin_quick_actions($a, $returnUrl) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pages > 1): ?>
            <nav class="pagination" aria-label="Pagination">
                <?php for ($p = 1; $p <= $pages; $p++): ?>
                    <a href="<?= url('admin/appointments.php?' . http_build_query($query + ['page' => $p])) ?>" class="<?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
                <?php endfor; ?>
            </nav>
        <?php endif; ?>
    <?php else: ?>
        <div class="empty-state empty-sm"><i class="fa-regular fa-calendar-xmark"></i><p>No appointments match your filters.</p></div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
