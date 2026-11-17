<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$user = current_user();

$stmt = db()->prepare(
    'SELECT a.*, dp.name AS department_name, dp.icon AS department_icon, d.photo
     FROM appointments a
     JOIN departments dp ON dp.id = a.department_id
     LEFT JOIN doctors d ON d.id = a.doctor_id
     WHERE a.user_id = ?
     ORDER BY a.appointment_date DESC, a.appointment_time DESC'
);
$stmt->execute([$user['id']]);
$all = $stmt->fetchAll();

$now = time();
$upcoming = $past = [];
$counts = ['total' => count($all), 'Pending' => 0, 'Confirmed' => 0, 'Completed' => 0, 'Cancelled' => 0];
foreach ($all as $a) {
    $ts = strtotime($a['appointment_date'] . ' ' . $a['appointment_time']);
    if ($ts >= $now && in_array($a['status'], ['Pending', 'Confirmed'], true)) {
        $upcoming[] = $a;
    } else {
        $past[] = $a;
    }
    $key = $a['status'] === 'Rejected' ? 'Cancelled' : $a['status'];
    $counts[$key]++;
}
usort($upcoming, fn($x, $y) => strcmp($x['appointment_date'] . $x['appointment_time'], $y['appointment_date'] . $y['appointment_time']));
$next = $upcoming[0] ?? null;

$tab = ($_GET['tab'] ?? 'upcoming') === 'history' ? 'history' : 'upcoming';
$list = $tab === 'history' ? $past : $upcoming;

$pageTitle  = 'My Dashboard';
$activePage = 'dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<section class="dash-hero">
    <div class="container dash-hero-inner">
        <div class="dash-welcome">
            <span class="avatar-lg"><?= e(initials($user['full_name'])) ?></span>
            <div>
                <span class="muted-light">Patient dashboard</span>
                <h1>Hello, <?= e(explode(' ', $user['full_name'])[0]) ?> 👋</h1>
                <p><?= e($user['email']) ?> · Member since <?= e(format_date($user['created_at'], 'F Y')) ?> · <a href="<?= url('profile.php') ?>" class="dash-profile-link">My profile</a></p>
            </div>
        </div>
        <a href="<?= url('book.php') ?>" class="btn btn-white btn-lg"><i class="fa-regular fa-calendar-plus"></i> Book New Appointment</a>
    </div>
</section>

<section class="section section-tight">
    <div class="container">
        <div class="stat-grid">
            <div class="stat-card"><span class="stat-icon si-blue"><i class="fa-solid fa-calendar-days"></i></span><div><strong><?= $counts['total'] ?></strong><span>Total appointments</span></div></div>
            <div class="stat-card"><span class="stat-icon si-amber"><i class="fa-solid fa-hourglass-half"></i></span><div><strong><?= $counts['Pending'] ?></strong><span>Pending</span></div></div>
            <div class="stat-card"><span class="stat-icon si-green"><i class="fa-solid fa-circle-check"></i></span><div><strong><?= $counts['Confirmed'] ?></strong><span>Confirmed</span></div></div>
            <div class="stat-card"><span class="stat-icon si-red"><i class="fa-solid fa-ban"></i></span><div><strong><?= $counts['Cancelled'] ?></strong><span>Cancelled / rejected</span></div></div>
        </div>

        <div class="dash-grid">
            <div class="dash-main">
                <div class="card">
                    <div class="card-head">
                        <h2>My Appointments</h2>
                        <div class="tabs">
                            <a href="<?= url('dashboard.php?tab=upcoming') ?>" class="tab <?= $tab === 'upcoming' ? 'active' : '' ?>">Upcoming (<?= count($upcoming) ?>)</a>
                            <a href="<?= url('dashboard.php?tab=history') ?>" class="tab <?= $tab === 'history' ? 'active' : '' ?>">History (<?= count($past) ?>)</a>
                        </div>
                    </div>

                    <?php if (!$list): ?>
                        <div class="empty-state">
                            <i class="fa-regular fa-calendar-xmark"></i>
                            <h3><?= $tab === 'upcoming' ? 'No upcoming appointments' : 'No past appointments yet' ?></h3>
                            <p><?= $tab === 'upcoming' ? 'When you book an appointment it will appear here.' : 'Completed and cancelled appointments will be listed here.' ?></p>
                            <a href="<?= url('book.php') ?>" class="btn btn-primary"><i class="fa-regular fa-calendar-plus"></i> Book an appointment</a>
                        </div>
                    <?php else: ?>
                        <div class="appt-list">
                            <?php foreach ($list as $a): ?>
                                <article class="appt-item" id="<?= e($a['reference']) ?>">
                                    <div class="appt-date">
                                        <span><?= e(format_date($a['appointment_date'], 'M')) ?></span>
                                        <strong><?= e(format_date($a['appointment_date'], 'd')) ?></strong>
                                        <span><?= e(format_date($a['appointment_date'], 'D')) ?></span>
                                    </div>
                                    <div class="appt-info">
                                        <div class="appt-top">
                                            <h3><?= e($a['doctor_name']) ?></h3>
                                            <?= status_badge($a['status']) ?>
                                        </div>
                                        <p class="appt-meta">
                                            <span><i class="fa-solid <?= e($a['department_icon']) ?>"></i> <?= e($a['department_name']) ?></span>
                                            <span><i class="fa-regular fa-clock"></i> <?= e(format_time($a['appointment_time'])) ?></span>
                                            <span><i class="fa-solid fa-hashtag"></i> <?= e($a['reference']) ?></span>
                                        </p>
                                        <p class="appt-reason"><?= e(mb_strimwidth($a['reason'], 0, 140, '…')) ?></p>
                                        <?php if ($a['admin_note']): ?>
                                            <p class="appt-note"><i class="fa-solid fa-comment-medical"></i> <?= e($a['admin_note']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="appt-actions">
                                        <a href="<?= url('track.php?ref=' . urlencode($a['reference'])) ?>" class="btn btn-ghost btn-sm"><i class="fa-solid fa-eye"></i> Details</a>
                                        <?php if (can_cancel($a)): ?>
                                            <form method="post" action="<?= url('cancel-appointment.php') ?>" data-confirm="Cancel your appointment with <?= e($a['doctor_name']) ?> on <?= e(format_date($a['appointment_date'])) ?>?">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="reference" value="<?= e($a['reference']) ?>">
                                                <input type="hidden" name="return" value="dashboard">
                                                <button type="submit" class="btn btn-danger-ghost btn-sm"><i class="fa-solid fa-ban"></i> Cancel</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <aside class="dash-side">
                <div class="card next-card">
                    <h3><i class="fa-solid fa-bell"></i> Next appointment</h3>
                    <?php if ($next): ?>
                        <div class="summary-doctor">
                            <img src="<?= doctor_photo($next['photo']) ?>" alt="<?= e($next['doctor_name']) ?>">
                            <div><strong><?= e($next['doctor_name']) ?></strong><span><?= e($next['department_name']) ?></span></div>
                        </div>
                        <p class="next-when"><i class="fa-regular fa-calendar"></i> <?= e(format_date($next['appointment_date'], 'l, j F Y')) ?><br>
                            <i class="fa-regular fa-clock"></i> <?= e(format_time($next['appointment_time'])) ?></p>
                        <?= status_badge($next['status']) ?>
                    <?php else: ?>
                        <p class="muted">You have no upcoming appointments.</p>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <h3><i class="fa-solid fa-magnifying-glass"></i> Track a booking</h3>
                    <p class="muted-sm">Enter a booking reference to view its full details and status.</p>
                    <form method="post" action="<?= url('track.php') ?>" class="js-validate" novalidate>
                        <?= csrf_field() ?>
                        <input type="hidden" name="email" value="<?= e($user['email']) ?>">
                        <div class="form-group">
                            <label for="dashRef" class="sr-only">Booking reference</label>
                            <input type="text" id="dashRef" name="reference" placeholder="CP-XXXXXX" required pattern="^[Cc][Pp]-[A-Za-z0-9]{6}$" maxlength="9" class="uppercase" data-error="Booking references look like CP-XXXXXX.">
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">Track</button>
                    </form>
                </div>

                <div class="card">
                    <h3><i class="fa-solid fa-id-card"></i> My details</h3>
                    <dl class="details-list details-compact">
                        <div><dt>Name</dt><dd><?= e($user['full_name']) ?></dd></div>
                        <div><dt>Phone</dt><dd><?= e($user['phone']) ?></dd></div>
                        <div><dt>Age</dt><dd><?= $user['age'] !== null ? (int)$user['age'] : '—' ?></dd></div>
                        <div><dt>Gender</dt><dd><?= e($user['gender'] ?: '—') ?></dd></div>
                    </dl>
                    <a href="<?= url('profile.php') ?>" class="btn btn-ghost btn-sm btn-block mt-2"><i class="fa-solid fa-user"></i> View full profile</a>
                </div>
            </aside>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
