<?php
$pageTitle  = 'Medical Services';
$activePage = 'services';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/components.php';

$departments = db()->query(
    "SELECT dp.*, COUNT(d.id) AS doctor_count
     FROM departments dp LEFT JOIN doctors d ON d.department_id = dp.id AND d.status = 'Active'
     GROUP BY dp.id ORDER BY dp.id"
)->fetchAll();

page_hero('Medical Services', 'Expert care across seven departments – all bookable online.', 'Services');
?>

<section class="section">
    <div class="container">
        <div class="section-head reveal">
            <span class="eyebrow">What We Offer</span>
            <h2>Specialist care, all under one roof</h2>
            <p>Choose a service below to learn more and book an appointment with one of our experienced doctors.</p>
        </div>

        <div class="service-cards">
            <?php foreach ($departments as $d): ?>
                <article class="service-card reveal" id="<?= e($d['slug']) ?>">
                    <div class="service-card-head">
                        <span class="service-icon service-icon-lg"><i class="fa-solid <?= e($d['icon']) ?>"></i></span>
                        <div>
                            <h3><?= e($d['name']) ?></h3>
                            <span class="muted-sm"><i class="fa-solid fa-user-doctor"></i> <?= (int)$d['doctor_count'] ?> doctor<?= $d['doctor_count'] == 1 ? '' : 's' ?> available</span>
                        </div>
                    </div>
                    <p><?= e($d['description']) ?></p>
                    <?php if ($d['features']): ?>
                        <ul class="check-list check-list-sm">
                            <?php foreach (explode('|', $d['features']) as $f): ?>
                                <li><i class="fa-solid fa-check"></i> <?= e($f) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <div class="service-card-foot">
                        <?php if ($d['doctor_count'] > 0): ?>
                            <a href="<?= url('book.php?department=' . e($d['slug'])) ?>" class="btn btn-primary"><i class="fa-regular fa-calendar-check"></i> Book Appointment</a>
                            <a href="<?= url('doctors.php?department=' . e($d['slug'])) ?>" class="btn btn-ghost">View doctors</a>
                        <?php else: ?>
                            <span class="muted-sm">No doctors currently available – please <a href="<?= url('contact.php') ?>">contact us</a>.</span>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section-alt section-tight">
    <div class="container">
        <div class="cta-banner cta-banner-light reveal">
            <div>
                <h2>Need help choosing a service?</h2>
                <p>Call our patient care team on <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', CLINIC_PHONE)) ?>"><?= e(CLINIC_PHONE) ?></a> or send us a message and we'll point you in the right direction.</p>
            </div>
            <div class="cta-actions">
                <a href="<?= url('contact.php') ?>" class="btn btn-primary btn-lg"><i class="fa-solid fa-paper-plane"></i> Contact Us</a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
