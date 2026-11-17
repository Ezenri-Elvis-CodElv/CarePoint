<?php
/**
 * Patient Log in / Sign up – sliding double-panel design.
 * Shared by login.php and register.php.
 *
 * Expects: $mode ('login'|'register'), $loginErrors, $loginEmail,
 *          $regErrors, $regValues
 */
$pageTitle  = $mode === 'register' ? 'Create Account' : 'Patient Login';
$activePage = 'login';
require_once __DIR__ . '/header.php';

$le = $loginErrors ?? [];
$re = $regErrors ?? [];
$rv = $regValues ?? ['full_name' => '', 'email' => '', 'phone' => '', 'age' => '', 'gender' => ''];
$err = function (array $errors, string $k): string {
    return isset($errors[$k]) ? '<small class="field-error">' . e($errors[$k]) . '</small>' : '';
};
$cls = fn(array $errors, string $k) => isset($errors[$k]) ? ' has-error' : '';
?>

<section class="auth-stage">
    <div class="auth-orb auth-orb-1"></div>
    <div class="auth-orb auth-orb-2"></div>

    <div class="cp-auth <?= $mode === 'register' ? 'panel-active' : '' ?>" id="cpAuth">

        <!-- ============ SIGN UP ============ -->
        <div class="cp-auth-box cp-register-box">
            <form method="post" action="<?= url('register.php') ?>" novalidate class="js-validate cp-auth-form">
                <?= csrf_field() ?>
                <span class="cp-auth-icon"><i class="fa-solid fa-user-plus"></i></span>
                <h1>Create Account</h1>
                <p class="cp-auth-sub">Register to manage all your appointments in one place.</p>

                <?php if (!empty($re['form'])): ?>
                    <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i><span><?= e($re['form']) ?></span></div>
                <?php elseif ($re): ?>
                    <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i><span>Please correct the highlighted fields.</span></div>
                <?php endif; ?>

                <div class="form-group<?= $cls($re, 'full_name') ?>">
                    <div class="input-icon"><i class="fa-solid fa-user"></i>
                        <input type="text" name="full_name" value="<?= e($rv['full_name']) ?>" placeholder="Full name" aria-label="Full name" required minlength="3" maxlength="100" autocomplete="name">
                    </div>
                    <?= $err($re, 'full_name') ?>
                </div>
                <div class="cp-row">
                    <div class="form-group<?= $cls($re, 'email') ?>">
                        <div class="input-icon"><i class="fa-solid fa-envelope"></i>
                            <input type="email" name="email" value="<?= e($rv['email']) ?>" placeholder="Email address" aria-label="Email address" required maxlength="150" autocomplete="email">
                        </div>
                        <?= $err($re, 'email') ?>
                    </div>
                    <div class="form-group<?= $cls($re, 'phone') ?>">
                        <div class="input-icon"><i class="fa-solid fa-phone"></i>
                            <input type="tel" name="phone" value="<?= e($rv['phone']) ?>" placeholder="Phone number" aria-label="Phone number" required pattern="^\+?[0-9\s\-().]{7,20}$" autocomplete="tel">
                        </div>
                        <?= $err($re, 'phone') ?>
                    </div>
                </div>
                <div class="cp-row">
                    <div class="form-group<?= $cls($re, 'age') ?>">
                        <div class="input-icon"><i class="fa-solid fa-cake-candles"></i>
                            <input type="number" name="age" value="<?= e($rv['age']) ?>" placeholder="Age (optional)" aria-label="Age" min="0" max="120">
                        </div>
                        <?= $err($re, 'age') ?>
                    </div>
                    <div class="form-group<?= $cls($re, 'gender') ?>">
                        <select name="gender" aria-label="Gender">
                            <option value="">Gender (optional)</option>
                            <?php foreach (['Male', 'Female', 'Other'] as $g): ?>
                                <option value="<?= $g ?>" <?= $rv['gender'] === $g ? 'selected' : '' ?>><?= $g ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= $err($re, 'gender') ?>
                    </div>
                </div>
                <div class="cp-row">
                    <div class="form-group<?= $cls($re, 'password') ?>">
                        <div class="input-icon"><i class="fa-solid fa-lock"></i>
                            <input type="password" id="reg_password" name="password" placeholder="Password" aria-label="Password" required minlength="8" autocomplete="new-password"
                                   pattern="^(?=.*[A-Za-z])(?=.*\d).{8,}$" data-error="At least 8 characters, with letters and numbers.">
                            <button type="button" class="toggle-password" aria-label="Show password"><i class="fa-regular fa-eye"></i></button>
                        </div>
                        <?= $err($re, 'password') ?>
                    </div>
                    <div class="form-group<?= $cls($re, 'confirm_password') ?>">
                        <div class="input-icon"><i class="fa-solid fa-lock"></i>
                            <input type="password" name="confirm_password" placeholder="Confirm password" aria-label="Confirm password" required data-match="reg_password" data-error="Passwords do not match." autocomplete="new-password">
                        </div>
                        <?= $err($re, 'confirm_password') ?>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block"><i class="fa-solid fa-user-plus"></i> Sign Up</button>
                <div class="cp-mobile-switch">
                    <span>Already have an account?</span>
                    <button type="button" class="btn btn-outline btn-sm" data-auth="login">Log in</button>
                </div>
            </form>
        </div>

        <!-- ============ LOG IN ============ -->
        <div class="cp-auth-box cp-login-box">
            <form method="post" action="<?= url('login.php') ?>" novalidate class="js-validate cp-auth-form" id="patientLoginForm">
                <?= csrf_field() ?>
                <span class="cp-auth-icon"><i class="fa-solid fa-hospital-user"></i></span>
                <h1>Patient Login</h1>
                <p class="cp-auth-sub">Welcome back! Sign in to view and manage your appointments.</p>

                <?php if (!empty($le['form'])): ?>
                    <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i><span><?= e($le['form']) ?></span></div>
                <?php endif; ?>

                <div class="form-group<?= $cls($le, 'email') ?>">
                    <div class="input-icon"><i class="fa-solid fa-envelope"></i>
                        <input type="email" name="email" value="<?= e($loginEmail ?? '') ?>" placeholder="Email address" aria-label="Email address" required autocomplete="email">
                    </div>
                    <?= $err($le, 'email') ?>
                </div>
                <div class="form-group<?= $cls($le, 'password') ?>">
                    <div class="input-icon"><i class="fa-solid fa-lock"></i>
                        <input type="password" name="password" placeholder="Password" aria-label="Password" required autocomplete="current-password">
                        <button type="button" class="toggle-password" aria-label="Show password"><i class="fa-regular fa-eye"></i></button>
                    </div>
                    <?= $err($le, 'password') ?>
                </div>
                <a href="<?= url('track.php') ?>" class="cp-auth-link">Booked as a guest? Track your booking</a>
                <button type="submit" class="btn btn-primary btn-block"><i class="fa-solid fa-right-to-bracket"></i> Log in</button>

                <div class="quick-access" aria-label="Quick demo access">
                    <p class="quick-title"><i class="fa-solid fa-bolt"></i> One-click demo login (for the supervisor)</p>
                    <div class="quick-btns">
                        <button type="button" class="quick-btn quick-patient" data-quick="patient" data-form="#patientLoginForm" data-user-field="email" data-user="patient@carepoint.com" data-pass="Patient@123"><i class="fa-solid fa-hospital-user"></i> Patient</button>
                        <a href="<?= url('admin/login.php?quick=admin') ?>" class="quick-btn quick-admin"><i class="fa-solid fa-user-shield"></i> Admin</a>
                    </div>
                    <p class="quick-creds">Patient: <strong>patient@carepoint.com</strong> / <strong>Patient@123</strong><br>Admin: <strong>admin</strong> / <strong>Admin@123</strong></p>
                </div>

                <div class="cp-mobile-switch">
                    <span>Don't have an account?</span>
                    <button type="button" class="btn btn-outline btn-sm" data-auth="register">Sign up</button>
                </div>
            </form>
        </div>

        <!-- ============ SLIDING PANEL ============ -->
        <div class="cp-slide-wrap" aria-hidden="false">
            <div class="cp-slide">
                <svg class="cp-pulse" viewBox="0 0 600 80" preserveAspectRatio="none" aria-hidden="true">
                    <path d="M0 40 H170 L190 40 L205 12 L222 68 L238 22 L250 40 H600" />
                </svg>
                <div class="cp-panel cp-panel-left">
                    <span class="cp-panel-icon"><i class="fa-solid fa-heart-pulse"></i></span>
                    <h2>Welcome Back!</h2>
                    <p>Already registered with CarePoint? Log in to see your upcoming visits, their status and messages from the clinic.</p>
                    <button type="button" class="btn btn-outline-light" data-auth="login"><i class="fa-solid fa-right-to-bracket"></i> Log in</button>
                </div>
                <div class="cp-panel cp-panel-right">
                    <span class="cp-panel-icon"><i class="fa-solid fa-user-doctor"></i></span>
                    <h2>New to CarePoint?</h2>
                    <p>Create a free account in under a minute. Bookings made with your email are linked to your dashboard automatically.</p>
                    <ul class="cp-panel-points">
                        <li><i class="fa-solid fa-circle-check"></i> Track every appointment</li>
                        <li><i class="fa-solid fa-circle-check"></i> Faster booking</li>
                        <li><i class="fa-solid fa-circle-check"></i> Cancel online</li>
                    </ul>
                    <button type="button" class="btn btn-outline-light" data-auth="register"><i class="fa-solid fa-user-plus"></i> Sign up</button>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
(function () {
    var box = document.getElementById('cpAuth');
    document.querySelectorAll('[data-auth]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var reg = btn.dataset.auth === 'register';
            box.classList.toggle('panel-active', reg);
            history.replaceState(null, '', reg ? '<?= url('register.php') ?>' : '<?= url('login.php') ?>');
            document.title = (reg ? 'Create Account' : 'Patient Login') + ' | CarePoint Clinic';
        });
    });
})();
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
