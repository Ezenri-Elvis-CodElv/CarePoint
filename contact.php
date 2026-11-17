<?php
require_once __DIR__ . '/includes/functions.php';

$errors = [];
$values = ['name' => '', 'email' => '', 'phone' => '', 'subject' => '', 'message' => ''];
if ($u = current_user()) {
    $values['name'] = $u['full_name'];
    $values['email'] = $u['email'];
    $values['phone'] = $u['phone'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $k => $_) {
        $values[$k] = post($k);
    }
    if (!verify_csrf()) {
        $errors['form'] = 'Your session has expired. Please try again.';
    }
    if (mb_strlen($values['name']) < 2) {
        $errors['name'] = 'Please enter your name.';
    }
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    if ($values['phone'] !== '' && !valid_phone($values['phone'])) {
        $errors['phone'] = 'Please enter a valid phone number or leave it blank.';
    }
    if (mb_strlen($values['subject']) < 3) {
        $errors['subject'] = 'Please enter a subject.';
    }
    if (mb_strlen($values['message']) < 10) {
        $errors['message'] = 'Your message should be at least 10 characters long.';
    }

    if (!$errors) {
        $stmt = db()->prepare('INSERT INTO contact_messages (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$values['name'], $values['email'], $values['phone'] ?: null, mb_substr($values['subject'], 0, 150), $values['message']]);
        flash('success', 'Thank you, ' . $values['name'] . '! Your message has been sent. Our team will reply within one working day.');
        redirect('contact.php');
    }
}

$pageTitle  = 'Contact Us';
$activePage = 'contact';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/components.php';
page_hero('Contact Us', 'We\'re here to help. Reach out with any questions or feedback.', 'Contact');
$telPhone = preg_replace('/[^0-9+]/', '', CLINIC_PHONE);
?>

<section class="section">
    <div class="container">
        <div class="contact-cards">
            <div class="info-card reveal"><span class="ci-icon"><i class="fa-solid fa-location-dot"></i></span><h3>Clinic Address</h3><p><?= e(CLINIC_ADDRESS) ?></p></div>
            <div class="info-card reveal"><span class="ci-icon"><i class="fa-solid fa-phone"></i></span><h3>Phone Number</h3><p><a href="tel:<?= e($telPhone) ?>"><?= e(CLINIC_PHONE) ?></a><br><small>Emergency: <?= e(CLINIC_EMERGENCY) ?></small></p></div>
            <div class="info-card reveal"><span class="ci-icon"><i class="fa-solid fa-envelope"></i></span><h3>Email Address</h3><p><a href="mailto:<?= e(CLINIC_EMAIL) ?>"><?= e(CLINIC_EMAIL) ?></a><br><small>We reply within 1 working day</small></p></div>
            <div class="info-card reveal"><span class="ci-icon"><i class="fa-regular fa-clock"></i></span><h3>Opening Hours</h3><p>Mon – Fri: 8:00 AM – 6:00 PM<br>Sat: 9:00 AM – 2:00 PM<br>Sun: Closed</p></div>
        </div>

        <div class="contact-layout">
            <div class="card form-card reveal">
                <h2>Send us a message</h2>
                <p class="muted">Have a question about our services, fees or insurance? Fill in the form and we'll get back to you.</p>
                <?php if (!empty($errors['form'])): ?>
                    <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i><span><?= e($errors['form']) ?></span></div>
                <?php elseif ($errors): ?>
                    <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i><span>Please correct the highlighted fields.</span></div>
                <?php endif; ?>
                <form method="post" action="<?= url('contact.php') ?>" novalidate class="js-validate">
                    <?= csrf_field() ?>
                    <div class="form-grid">
                        <div class="form-group <?= isset($errors['name']) ? 'has-error' : '' ?>">
                            <label for="name">Your name <span class="req">*</span></label>
                            <input type="text" id="name" name="name" value="<?= e($values['name']) ?>" required minlength="2" maxlength="100">
                            <?= field_error($errors, 'name') ?>
                        </div>
                        <div class="form-group <?= isset($errors['email']) ? 'has-error' : '' ?>">
                            <label for="email">Email address <span class="req">*</span></label>
                            <input type="email" id="email" name="email" value="<?= e($values['email']) ?>" required maxlength="150">
                            <?= field_error($errors, 'email') ?>
                        </div>
                        <div class="form-group <?= isset($errors['phone']) ? 'has-error' : '' ?>">
                            <label for="phone">Phone (optional)</label>
                            <input type="tel" id="phone" name="phone" value="<?= e($values['phone']) ?>" pattern="^\+?[0-9\s\-().]{7,20}$">
                            <?= field_error($errors, 'phone') ?>
                        </div>
                        <div class="form-group <?= isset($errors['subject']) ? 'has-error' : '' ?>">
                            <label for="subject">Subject <span class="req">*</span></label>
                            <input type="text" id="subject" name="subject" value="<?= e($values['subject']) ?>" required minlength="3" maxlength="150">
                            <?= field_error($errors, 'subject') ?>
                        </div>
                        <div class="form-group col-2 <?= isset($errors['message']) ? 'has-error' : '' ?>">
                            <label for="message">Message <span class="req">*</span></label>
                            <textarea id="message" name="message" rows="5" required minlength="10" maxlength="2000"><?= e($values['message']) ?></textarea>
                            <?= field_error($errors, 'message') ?>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg"><i class="fa-solid fa-paper-plane"></i> Send Message</button>
                </form>
            </div>

            <div class="map-card reveal" id="map">
                <div class="map-placeholder" role="img" aria-label="Map placeholder showing the CarePoint Clinic location">
                    <div class="map-grid"></div>
                    <div class="map-road map-road-1"></div>
                    <div class="map-road map-road-2"></div>
                    <div class="map-pin"><i class="fa-solid fa-location-dot"></i></div>
                    <div class="map-label"><strong>CarePoint Clinic</strong><span><?= e(CLINIC_ADDRESS) ?></span></div>
                </div>
                <div class="map-foot">
                    <p><i class="fa-solid fa-square-parking"></i> Free patient parking on site · <i class="fa-solid fa-wheelchair"></i> Step-free access</p>
                    <a href="https://www.google.com/maps/search/?api=1&amp;query=<?= urlencode(CLINIC_ADDRESS) ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm"><i class="fa-solid fa-diamond-turn-right"></i> Open in Google Maps</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
