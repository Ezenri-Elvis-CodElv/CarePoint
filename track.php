<?php
require_once __DIR__ . '/includes/functions.php';

$errors = [];
$ref    = strtoupper(trim($_GET['ref'] ?? ''));
$email  = '';

// Lookup by reference + email
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ref   = strtoupper(post('reference'));
    $email = post('email');
    if (!verify_csrf()) {
        $errors['form'] = 'Your session has expired. Please try again.';
    }
    if (!preg_match('/^CP-[A-Z0-9]{6}$/', $ref)) {
        $errors['reference'] = 'Booking references look like CP-XXXXXX.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter the email address used when booking.';
    }
    if (!$errors) {
        $stmt = db()->prepare('SELECT id FROM appointments WHERE reference = ? AND email = ?');
        $stmt->execute([$ref, $email]);
        if ($stmt->fetchColumn()) {
            $_SESSION['tracked'][$ref] = true;
            redirect('track.php?ref=' . urlencode($ref));
        }
        $errors['form'] = 'No appointment matches that reference and email address. Please check the details and try again.';
    }
}

// Can this browser see the appointment?
$appt = null;
if ($ref !== '' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = db()->prepare(
        'SELECT a.*, dp.name AS department_name, d.photo, d.specialisation
         FROM appointments a
         JOIN departments dp ON dp.id = a.department_id
         LEFT JOIN doctors d ON d.id = a.doctor_id
         WHERE a.reference = ?'
    );
    $stmt->execute([$ref]);
    $row = $stmt->fetch();
    $allowed = $row && (
        !empty($_SESSION['tracked'][$ref])
        || in_array($ref, $_SESSION['recent_bookings'] ?? [], true)
        || (is_logged_in() && (int)$row['user_id'] === (int)$_SESSION['user_id'])
    );
    if ($allowed) {
        $appt = $row;
    }
}

$pageTitle  = 'Track Your Booking';
$activePage = 'track';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/components.php';
page_hero('Track Your Booking', 'Check the status of your appointment using your booking reference.', 'Track Booking');
?>

<section class="section">
    <div class="container narrow">
        <?php if ($appt): ?>
            <?php
            $steps   = ['Pending' => 1, 'Confirmed' => 2, 'Completed' => 3];
            $current = $steps[$appt['status']] ?? 0;
            ?>
            <div class="card track-result reveal">
                <div class="track-head">
                    <div>
                        <span class="muted-sm">Booking reference</span>
                        <h2><?= e($appt['reference']) ?></h2>
                    </div>
                    <?= status_badge($appt['status']) ?>
                </div>

                <?php if (in_array($appt['status'], ['Cancelled', 'Rejected'], true)): ?>
                    <div class="alert alert-<?= $appt['status'] === 'Cancelled' ? 'warning' : 'error' ?>">
                        <i class="fa-solid fa-circle-info"></i>
                        <span>This appointment was <?= strtolower(e($appt['status'])) ?>.<?= $appt['admin_note'] ? ' Note: ' . e($appt['admin_note']) : '' ?></span>
                    </div>
                <?php else: ?>
                    <ol class="progress-track">
                        <li class="<?= $current >= 1 ? 'done' : '' ?>"><span><i class="fa-solid fa-paper-plane"></i></span>Requested</li>
                        <li class="<?= $current >= 2 ? 'done' : '' ?>"><span><i class="fa-solid fa-circle-check"></i></span>Confirmed</li>
                        <li class="<?= $current >= 3 ? 'done' : '' ?>"><span><i class="fa-solid fa-flag-checkered"></i></span>Completed</li>
                    </ol>
                    <?php if ($appt['admin_note']): ?>
                        <div class="alert alert-info"><i class="fa-solid fa-comment-medical"></i><span><strong>Message from the clinic:</strong> <?= e($appt['admin_note']) ?></span></div>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="summary-grid">
                    <div class="summary-doctor">
                        <img src="<?= doctor_photo($appt['photo']) ?>" alt="<?= e($appt['doctor_name']) ?>">
                        <div><strong><?= e($appt['doctor_name']) ?></strong><span><?= e($appt['department_name']) ?></span></div>
                    </div>
                    <dl class="details-list">
                        <div><dt>Patient</dt><dd><?= e($appt['patient_name']) ?> (<?= (int)$appt['age'] ?>, <?= e($appt['gender']) ?>)</dd></div>
                        <div><dt>Date</dt><dd><?= e(format_date($appt['appointment_date'], 'l, j F Y')) ?></dd></div>
                        <div><dt>Time</dt><dd><?= e(format_time($appt['appointment_time'])) ?></dd></div>
                        <div><dt>Booked on</dt><dd><?= e(format_date($appt['created_at'], 'j M Y, g:i A')) ?></dd></div>
                        <div class="full"><dt>Reason</dt><dd><?= nl2br(e($appt['reason'])) ?></dd></div>
                    </dl>
                </div>

                <div class="confirm-actions">
                    <?php if (can_cancel($appt)): ?>
                        <form method="post" action="<?= url('cancel-appointment.php') ?>" class="inline-form" data-confirm="Are you sure you want to cancel appointment <?= e($appt['reference']) ?>? This cannot be undone.">
                            <?= csrf_field() ?>
                            <input type="hidden" name="reference" value="<?= e($appt['reference']) ?>">
                            <input type="hidden" name="return" value="track">
                            <button type="submit" class="btn btn-danger"><i class="fa-solid fa-ban"></i> Cancel appointment</button>
                        </form>
                    <?php endif; ?>
                    <a href="<?= url('book.php') ?>" class="btn btn-outline"><i class="fa-regular fa-calendar-plus"></i> Book another</a>
                    <a href="<?= url('track.php') ?>" class="btn btn-ghost">Track a different booking</a>
                </div>
            </div>
        <?php else: ?>
            <div class="cp-flip reveal" id="cpFlip">
                <div class="cp-flip-shape"></div>
                <div class="cp-flip-shape2"></div>

                <!-- Track booking -->
                <div class="cp-flip-panel cp-flip-a">
                    <h2 class="slide-element"><i class="fa-solid fa-magnifying-glass"></i> Track Booking</h2>
                    <form method="post" action="<?= url('track.php') ?>" novalidate class="js-validate">
                        <?= csrf_field() ?>
                        <?php if (!empty($errors['form'])): ?>
                            <div class="cp-flip-alert slide-element"><i class="fa-solid fa-circle-exclamation"></i> <?= e($errors['form']) ?></div>
                        <?php else: ?>
                            <p class="cp-flip-note slide-element">Use the reference from your confirmation and the email you booked with.</p>
                        <?php endif; ?>
                        <div class="form-group line-field slide-element<?= isset($errors['reference']) ? ' has-error' : '' ?>">
                            <input type="text" id="reference" name="reference" value="<?= e($ref) ?>" placeholder=" " required pattern="^[Cc][Pp]-[A-Za-z0-9]{6}$" maxlength="9" class="uppercase" data-error="Booking references look like CP-XXXXXX.">
                            <label for="reference">Booking reference</label>
                            <i class="fa-solid fa-hashtag"></i>
                            <?= field_error($errors, 'reference') ?>
                        </div>
                        <div class="form-group line-field slide-element<?= isset($errors['email']) ? ' has-error' : '' ?>">
                            <input type="email" id="track_email" name="email" value="<?= e($email) ?>" placeholder=" " required>
                            <label for="track_email">Email address</label>
                            <i class="fa-solid fa-envelope"></i>
                            <?= field_error($errors, 'email') ?>
                        </div>
                        <div class="slide-element">
                            <button type="submit" class="cp-sweep-btn">Track Booking</button>
                        </div>
                        <div class="cp-flip-switch slide-element">
                            <?php if (is_logged_in()): ?>
                                Have an account? <a href="<?= url('dashboard.php') ?>">Go to my dashboard</a>
                            <?php else: ?>
                                Have an account? <a href="#" data-flip="on">Sign in instead</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <div class="cp-flip-welcome cp-flip-welcome-a">
                    <h2 class="slide-element">Where's my<br>appointment?</h2>
                    <p class="slide-element">Check its status, read clinic notes or cancel – no account needed.</p>
                </div>

                <!-- Patient sign in -->
                <div class="cp-flip-panel cp-flip-b">
                    <h2 class="slide-element"><i class="fa-solid fa-hospital-user"></i> Patient Sign In</h2>
                    <form method="post" action="<?= url('login.php') ?>" novalidate class="js-validate">
                        <?= csrf_field() ?>
                        <p class="cp-flip-note slide-element">See every booking in your dashboard.</p>
                        <div class="form-group line-field slide-element">
                            <input type="email" id="flip_email" name="email" placeholder=" " required autocomplete="email">
                            <label for="flip_email">Email address</label>
                            <i class="fa-solid fa-envelope"></i>
                        </div>
                        <div class="form-group line-field slide-element">
                            <input type="password" id="flip_password" name="password" placeholder=" " required autocomplete="current-password">
                            <label for="flip_password">Password</label>
                            <i class="fa-solid fa-lock"></i>
                        </div>
                        <div class="slide-element">
                            <button type="submit" class="cp-sweep-btn">Sign In</button>
                        </div>
                        <div class="cp-flip-switch slide-element">
                            Booked as a guest? <a href="#" data-flip="off">Track a booking</a><br>
                            New here? <a href="<?= url('register.php') ?>">Create an account</a>
                        </div>
                    </form>
                </div>

                <div class="cp-flip-welcome cp-flip-welcome-b">
                    <h2 class="slide-element">Welcome<br>back!</h2>
                    <p class="slide-element">Your appointments, all in one place.</p>
                </div>
            </div>
            <script>
            // Stagger index for each animated element
            document.querySelectorAll('#cpFlip .cp-flip-panel, #cpFlip .cp-flip-welcome').forEach(function (panel) {
                panel.querySelectorAll('.slide-element').forEach(function (el, i) { el.style.setProperty('--i', i); });
            });
            document.querySelectorAll('[data-flip]').forEach(function (a) {
                a.addEventListener('click', function (e) {
                    e.preventDefault();
                    document.getElementById('cpFlip').classList.toggle('toggled', a.dataset.flip === 'on');
                });
            });
            </script>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
