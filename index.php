<?php
$pageTitle   = 'Your Health, Our Priority';
$activePage  = 'home';
$pageStyles  = ['showcase.css'];
$pageScripts = ['showcase.js'];
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/components.php';
require_once __DIR__ . '/includes/showcase.php';

$departments = get_departments();
$doctorCount = (int)db()->query("SELECT COUNT(*) FROM doctors WHERE status = 'Active'")->fetchColumn();

// Doctors for the 3D carousel (those with a photo, featured first)
$showDoctors = array_values(array_filter(get_active_doctors(), fn($d) => $d['photo'] && is_file(DOCTOR_UPLOAD_DIR . $d['photo'])));
$carouselColours = ['#1e78ea', '#13315c', '#2a8fd6', '#1d4475', '#1565d8', '#0f4c81', '#3a7bd5', '#0b2545'];
$docData = array_map(fn($d, $i) => [
    'id'    => (int)$d['id'],
    'name'  => $d['full_name'],
    'spec'  => $d['specialisation'],
    'dept'  => $d['department_name'],
    'icon'  => $d['department_icon'],
    'ghost' => strtoupper(explode(' ', $d['department_name'])[0]),
    'bio'   => mb_strimwidth($d['bio'], 0, 150, '…'),
    'days'  => format_days($d),
    'hours' => format_hours($d),
    'exp'   => (int)$d['experience_years'],
    'photo' => doctor_photo($d['photo']),
    'book'  => url('book.php?doctor=' . (int)$d['id']),
    'bg'    => $carouselColours[$i % count($carouselColours)],
], $showDoctors, array_keys($showDoctors));

// Hero slides – the 2nd slide is the one shown first
$slides = [
    ['img' => 'images/slider/care.jpg', 'kicker' => 'fa-hand-holding-heart', 'k' => 'Compassionate care', 'thumb' => 'Care that listens',
     'title' => 'Care That <em>Listens</em>', 'desc' => 'Unhurried consultations with doctors who take the time to understand you, your history and your concerns.',
     'cta' => ['About CarePoint', 'about.php']],
    ['img' => 'images/hero.jpg', 'kicker' => 'fa-shield-heart', 'k' => 'Trusted since 2010', 'thumb' => 'Your health first', 'h1' => true,
     'title' => 'Your Health, <em>Our Priority</em>', 'desc' => 'Experienced doctors, modern diagnostics and genuinely caring staff under one roof. Choose your specialist and book online in minutes.',
     'cta' => ['Explore Services', 'services.php']],
    ['img' => 'images/slider/team.jpg', 'kicker' => 'fa-user-doctor', 'k' => $doctorCount . ' specialists', 'thumb' => 'Expert specialists',
     'title' => 'Expert <em>Specialists</em>', 'desc' => 'Cardiology, paediatrics, dermatology, eye care and more – meet board-certified doctors with years of experience.',
     'cta' => ['Meet Our Doctors', 'doctors.php']],
    ['img' => 'images/slider/room.jpg', 'kicker' => 'fa-microscope', 'k' => 'On-site diagnostics', 'thumb' => 'Modern diagnostics',
     'title' => 'Modern <em>Diagnostics</em>', 'desc' => 'In-house laboratory, ECG and echocardiography mean faster answers and fewer trips between clinics.',
     'cta' => ['Laboratory Services', 'services.php#laboratory-services']],
    ['img' => 'images/slider/dental.jpg', 'kicker' => 'fa-tooth', 'k' => 'Dental care', 'thumb' => 'Healthy smiles',
     'title' => 'Smiles That <em>Last</em>', 'desc' => 'Gentle preventive, restorative and cosmetic dentistry for the whole family – anxious patients welcome.',
     'cta' => ['Book a Dentist', 'book.php?department=dental-care']],
    ['img' => 'images/clinic.jpg', 'kicker' => 'fa-magnifying-glass', 'k' => 'Already booked?', 'thumb' => 'Track your visit',
     'title' => 'Track Your <em>Visit</em>', 'desc' => 'Use your booking reference to check your appointment status, read notes from the clinic or cancel online.',
     'cta' => ['Track Booking', 'track.php']],
];
?>

<!-- ================= HERO SLIDER ================= -->
<section class="cp-hero-slider" id="heroSlider" aria-roledescription="carousel" aria-label="CarePoint highlights">
    <div class="cp-hero-track" id="heroTrack">
        <?php foreach ($slides as $i => $s): ?>
            <div class="cp-slide-card" style="background-image: url('<?= asset($s['img']) ?>');" data-index="<?= $i ?>" role="group" aria-label="<?= e($s['thumb']) ?>">
                <span class="cp-thumb-label" aria-hidden="true"><small><?= e($s['k']) ?></small><?= e($s['thumb']) ?></span>
                <div class="cp-slide-info">
                    <span class="cp-slide-kicker"><i class="fa-solid <?= e($s['kicker']) ?>"></i> <?= e($s['k']) ?></span>
                    <?php $tag = !empty($s['h1']) ? 'h1' : 'h2'; ?>
                    <<?= $tag ?> class="cp-slide-title"><?= $s['title'] ?></<?= $tag ?>>
                    <p class="cp-slide-desc"><?= e($s['desc']) ?></p>
                    <div class="cp-slide-actions">
                        <a href="<?= url('book.php') ?>" class="btn btn-primary btn-lg"><i class="fa-regular fa-calendar-check"></i> Book an Appointment</a>
                        <a href="<?= url($s['cta'][1]) ?>" class="btn btn-outline-light btn-lg"><?= e($s['cta'][0]) ?> <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="cp-slider-ui">
        <button type="button" class="cp-slide-btn" id="heroPrev" aria-label="Previous slide"><i class="fa-solid fa-chevron-left"></i></button>
        <button type="button" class="cp-slide-btn" id="heroNext" aria-label="Next slide"><i class="fa-solid fa-chevron-right"></i></button>
        <span class="cp-slide-count"><strong id="heroNum">02</strong> / <?= sprintf('%02d', count($slides)) ?></span>
        <span class="cp-slide-progress"><span id="heroProgress"></span></span>
    </div>
</section>

<!-- ================= QUICK FINDER ================= -->
<section class="quick-finder-wrap">
    <div class="container">
        <form class="quick-finder reveal" action="<?= url('book.php') ?>" method="get">
            <div class="qf-title">
                <i class="fa-solid fa-magnifying-glass-plus"></i>
                <div><strong>Find the right care</strong><small>Start your booking by department</small></div>
            </div>
            <div class="qf-field">
                <label for="qfDept">Department</label>
                <select id="qfDept" name="department" required>
                    <option value="">Choose a department</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= e($d['slug']) ?>"><?= e($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Continue <i class="fa-solid fa-arrow-right"></i></button>
        </form>
    </div>
</section>

<!-- ================= SERVICES ================= -->
<section class="section">
    <div class="container">
        <div class="section-head reveal">
            <span class="eyebrow">Our Medical Services</span>
            <h2>Complete care for every stage of life</h2>
            <p>From routine check-ups to specialist consultations and laboratory tests, our departments work together so you receive joined-up, high-quality care.</p>
        </div>
        <div class="services-grid">
            <?php foreach ($departments as $d): ?>
                <a href="<?= url('services.php#' . e($d['slug'])) ?>" class="service-tile reveal">
                    <span class="service-icon"><i class="fa-solid <?= e($d['icon']) ?>"></i></span>
                    <h3><?= e($d['name']) ?></h3>
                    <p><?= e($d['short_description']) ?></p>
                    <span class="link-arrow">Learn more <i class="fa-solid fa-arrow-right"></i></span>
                </a>
            <?php endforeach; ?>
            <a href="<?= url('book.php') ?>" class="service-tile service-tile-cta reveal">
                <span class="service-icon"><i class="fa-solid fa-calendar-plus"></i></span>
                <h3>Not sure where to start?</h3>
                <p>Book a general consultation and our family physicians will guide you to the right specialist.</p>
                <span class="link-arrow">Book now <i class="fa-solid fa-arrow-right"></i></span>
            </a>
        </div>
    </div>
</section>

<!-- ================= HOW IT WORKS – ELECTRIC CARDS ================= -->
<section class="section ecard-section">
    <?php electric_filters(); ?>
    <div class="container">
        <div class="section-head reveal">
            <span class="eyebrow eyebrow-light">Simple &amp; Fast</span>
            <h2>Book your visit in three easy steps</h2>
            <p>From choosing a specialist to holding your booking reference – it takes less than two minutes.</p>
        </div>
        <div class="ecard-grid">
            <?php
            electric_card(1, '#4b97f2', '75, 151, 242', 'Step 01', 'fa-hospital-user', 'Choose a <span>department</span>',
                'Select the service you need and instantly see the doctors available in that department.', ['Browse services', url('services.php')]);
            electric_card(2, '#48c4dd', '72, 196, 221', 'Step 02', 'fa-clock', 'Pick a <span>date &amp; time</span>',
                'View real-time availability. Slots already reserved are blocked automatically – no double bookings.', ['Book now', url('book.php')]);
            electric_card(3, '#48dd84', '72, 221, 132', 'Step 03', 'fa-ticket', 'Get your <span>reference</span>',
                'Receive a unique booking reference straight away and track, manage or cancel it any time.', ['Track a booking', url('track.php')]);
            ?>
        </div>
    </div>
</section>

<!-- ================= FEATURED DOCTORS – 3D CAROUSEL ================= -->
<?php if ($docData): ?>
<section class="cp-docshow" id="docShow" aria-roledescription="carousel" aria-label="Meet our specialists" style="background-color: <?= e($docData[0]['bg']) ?>;">
    <div class="cp-docshow-grain" id="docGrain"></div>
    <div class="cp-ghost" id="docGhost" aria-hidden="true"><?= e($docData[0]['ghost']) ?></div>

    <div class="cp-docshow-head">
        <div class="container">
            <div class="cp-docshow-brand">Meet our specialists<strong>CarePoint Doctors</strong></div>
            <a href="<?= url('doctors.php') ?>" class="cp-round-link" aria-label="View all doctors" title="View all doctors"><i class="fa-solid fa-user-doctor"></i></a>
        </div>
    </div>

    <div class="cp-docshow-stage" id="docStage"></div>

    <div class="cp-docshow-info" id="docInfo" aria-live="polite">
        <p class="cp-docshow-dept"><i class="fa-solid <?= e($docData[0]['icon']) ?>" id="docIcon"></i> <span id="docDept"><?= e($docData[0]['dept']) ?></span></p>
        <h2 class="cp-docshow-name" id="docName"><?= e($docData[0]['name']) ?></h2>
        <p class="cp-docshow-spec" id="docSpec"><?= e($docData[0]['spec']) ?></p>
        <p class="cp-docshow-desc" id="docBio"><?= e($docData[0]['bio']) ?></p>
        <p class="cp-docshow-meta"><span><i class="fa-regular fa-calendar"></i> <span id="docDays"><?= e($docData[0]['days']) ?></span></span><span><i class="fa-regular fa-clock"></i> <span id="docHours"><?= e($docData[0]['hours']) ?></span></span></p>
        <div class="cp-docshow-nav">
            <button type="button" class="cp-nav-btn" id="docPrev" aria-label="Previous doctor"><i class="fa-solid fa-chevron-left"></i></button>
            <button type="button" class="cp-nav-btn" id="docNext" aria-label="Next doctor"><i class="fa-solid fa-chevron-right"></i></button>
        </div>
    </div>

    <a class="cp-discover" id="docBook" href="<?= e($docData[0]['book']) ?>">
        <span><small>Appointments open</small>Book now</span> <i class="fa-solid fa-arrow-right"></i>
    </a>
    <script type="application/json" id="docData"><?= json_encode($docData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
</section>
<?php endif; ?>

<!-- ================= TESTIMONIALS ================= -->
<section class="section section-navy">
    <div class="container">
        <div class="section-head reveal">
            <span class="eyebrow eyebrow-light">Patient Testimonials</span>
            <h2>What our patients say</h2>
        </div>
        <div class="testimonials-grid">
            <?php foreach (testimonials() as $t): ?>
                <figure class="testimonial reveal">
                    <div class="stars" aria-label="<?= $t['rating'] ?> out of 5 stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?><i class="fa-<?= $i <= $t['rating'] ? 'solid' : 'regular' ?> fa-star"></i><?php endfor; ?>
                    </div>
                    <blockquote>“<?= e($t['text']) ?>”</blockquote>
                    <figcaption>
                        <span class="avatar-sm avatar-light"><?= e(initials($t['name'])) ?></span>
                        <span><strong><?= e($t['name']) ?></strong><small><?= e($t['role']) ?></small></span>
                    </figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ================= CTA ================= -->
<section class="section">
    <div class="container">
        <div class="cta-banner reveal">
            <div>
                <h2>Ready to see a doctor?</h2>
                <p>Same-week appointments are available across most departments. Already booked? Track your appointment with your reference number.</p>
            </div>
            <div class="cta-actions">
                <a href="<?= url('book.php') ?>" class="btn btn-white btn-lg"><i class="fa-regular fa-calendar-check"></i> Book an Appointment</a>
                <a href="<?= url('track.php') ?>" class="btn btn-outline-light btn-lg"><i class="fa-solid fa-magnifying-glass"></i> Track Booking</a>
            </div>
        </div>
    </div>
</section>

<!-- ================= CONTACT STRIP ================= -->
<section class="section section-alt section-tight">
    <div class="container contact-strip">
        <div class="contact-item reveal">
            <span class="ci-icon"><i class="fa-solid fa-location-dot"></i></span>
            <div><h4>Visit us</h4><p><?= e(CLINIC_ADDRESS) ?></p></div>
        </div>
        <div class="contact-item reveal">
            <span class="ci-icon"><i class="fa-solid fa-phone"></i></span>
            <div><h4>Call us</h4><p><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', CLINIC_PHONE)) ?>"><?= e(CLINIC_PHONE) ?></a></p></div>
        </div>
        <div class="contact-item reveal">
            <span class="ci-icon"><i class="fa-solid fa-envelope"></i></span>
            <div><h4>Email us</h4><p><a href="mailto:<?= e(CLINIC_EMAIL) ?>"><?= e(CLINIC_EMAIL) ?></a></p></div>
        </div>
        <div class="contact-item reveal">
            <span class="ci-icon"><i class="fa-regular fa-clock"></i></span>
            <div><h4>Opening hours</h4><p>Mon–Fri 8 AM – 6 PM · Sat 9 AM – 2 PM</p></div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
