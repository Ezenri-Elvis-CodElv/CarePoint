<?php
$pageTitle  = 'About Us';
$activePage = 'about';
$pageStyles = ['showcase.css'];
$pageScripts = ['showcase.js'];
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/components.php';
require_once __DIR__ . '/includes/showcase.php';

$doctors = get_active_doctors();
page_hero('About CarePoint Clinic', 'Modern, compassionate healthcare built around you and your family.', 'About Us');
?>

<section class="section">
    <div class="container split">
        <div class="split-media reveal">
            <img src="<?= asset('images/about.jpg') ?>" alt="CarePoint Clinic reception area" class="rounded-img">
            <div class="experience-badge"><strong>15+</strong><span>Years serving<br>our community</span></div>
        </div>
        <div class="split-content reveal">
            <span class="eyebrow">Who We Are</span>
            <h2>A multi-specialty clinic that puts patients first</h2>
            <p>CarePoint Clinic opened its doors in 2010 with a simple idea: healthcare should be easy to access, unhurried and personal. What began as a small family practice has grown into a multi-specialty clinic with seven departments, an accredited in-house laboratory and a team of experienced doctors, nurses and support staff.</p>
            <p>Every year we care for thousands of patients – from newborns attending their first check-up to older adults managing long-term conditions. Our online booking system means you can see real availability, choose your preferred doctor and secure your appointment without waiting on hold.</p>
            <ul class="check-list">
                <li><i class="fa-solid fa-circle-check"></i> Accredited laboratory with same-day results for many tests</li>
                <li><i class="fa-solid fa-circle-check"></i> Specialists in cardiology, paediatrics, dermatology, dental and eye care</li>
                <li><i class="fa-solid fa-circle-check"></i> Wheelchair-accessible facility with on-site parking</li>
            </ul>
        </div>
    </div>
</section>

<section class="section ecard-section">
    <?php electric_filters(); ?>
    <div class="container">
        <div class="section-head reveal">
            <span class="eyebrow eyebrow-light">What drives us</span>
            <h2>Mission, vision &amp; values</h2>
        </div>
        <div class="ecard-grid">
            <?php
            electric_card(1, '#4b97f2', '75, 151, 242', 'Mission', 'fa-bullseye', 'Our <span>Mission</span>',
                'To deliver safe, high-quality and affordable healthcare with compassion and respect, making it simple for every patient to see the right doctor at the right time.');
            electric_card(2, '#48c4dd', '72, 196, 221', 'Vision', 'fa-eye', 'Our <span>Vision</span>',
                'To be the most trusted community clinic in the region – recognised for clinical excellence, innovation in patient experience and a commitment to lifelong wellbeing.');
            electric_card(3, '#48dd84', '72, 221, 132', 'Values', 'fa-hand-holding-heart', 'Our <span>Values</span>',
                'Compassion, integrity, respect for every patient, continuous learning and teamwork guide every decision we make and every consultation we hold.');
            ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head reveal">
            <span class="eyebrow">Why Choose Us</span>
            <h2>Reasons patients trust CarePoint</h2>
        </div>
        <div class="features-grid">
            <div class="feature reveal"><i class="fa-solid fa-user-doctor"></i><h3>Experienced specialists</h3><p>Board-certified doctors with an average of more than 10 years of clinical experience.</p></div>
            <div class="feature reveal"><i class="fa-solid fa-calendar-check"></i><h3>Easy online booking</h3><p>Real-time availability, instant booking references and online cancellation.</p></div>
            <div class="feature reveal"><i class="fa-solid fa-microscope"></i><h3>Modern diagnostics</h3><p>On-site laboratory, ECG, echocardiography and digital imaging for faster answers.</p></div>
            <div class="feature reveal"><i class="fa-solid fa-hourglass-half"></i><h3>Minimal waiting</h3><p>Scheduled time slots mean less time in the waiting room and more time with your doctor.</p></div>
            <div class="feature reveal"><i class="fa-solid fa-people-roof"></i><h3>Care for all ages</h3><p>From newborn checks to senior care, the whole family can be looked after in one place.</p></div>
            <div class="feature reveal"><i class="fa-solid fa-lock"></i><h3>Privacy &amp; safety</h3><p>Your personal and medical information is handled securely and confidentially.</p></div>
        </div>
    </div>
</section>

<section class="section section-alt">
    <div class="container">
        <div class="section-head section-head-row reveal">
            <div>
                <span class="eyebrow">Our Healthcare Professionals</span>
                <h2>The team behind your care</h2>
                <p>Meet the doctors who make CarePoint a place patients trust.</p>
            </div>
            <a href="<?= url('doctors.php') ?>" class="btn btn-outline">Full doctor profiles <i class="fa-solid fa-arrow-right"></i></a>
        </div>
        <div class="team-grid">
            <?php foreach ($doctors as $doc): ?>
                <a href="<?= url('doctors.php#doctor-' . (int)$doc['id']) ?>" class="team-member reveal">
                    <img src="<?= doctor_photo($doc['photo']) ?>" alt="<?= e($doc['full_name']) ?>" loading="lazy">
                    <h4><?= e($doc['full_name']) ?></h4>
                    <span><?= e($doc['specialisation']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="stats-band reveal">
            <div><strong>12,000+</strong><span>Patients treated</span></div>
            <div><strong><?= count($doctors) ?></strong><span>Specialist doctors</span></div>
            <div><strong>7</strong><span>Medical departments</span></div>
            <div><strong>98%</strong><span>Patient satisfaction</span></div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
