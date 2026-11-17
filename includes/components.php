<?php
/**
 * Reusable UI fragments.
 */

function render_doctor_card(array $doc, bool $detailed = false): void
{
    ?>
    <article class="doctor-card reveal" id="doctor-<?= (int)$doc['id'] ?>">
        <div class="doctor-photo">
            <img src="<?= doctor_photo($doc['photo']) ?>" alt="Portrait of <?= e($doc['full_name']) ?>" loading="lazy">
            <span class="doctor-dept"><i class="fa-solid <?= e($doc['department_icon'] ?? 'fa-stethoscope') ?>"></i> <?= e($doc['department_name']) ?></span>
        </div>
        <div class="doctor-body">
            <h3><?= e($doc['full_name']) ?></h3>
            <p class="doctor-spec"><?= e($doc['specialisation']) ?></p>
            <?php if ($detailed): ?>
                <p class="doctor-qual"><i class="fa-solid fa-graduation-cap"></i> <?= e($doc['qualification']) ?> · <?= (int)$doc['experience_years'] ?> yrs experience</p>
                <p class="doctor-bio"><?= e($doc['bio']) ?></p>
                <ul class="doctor-meta">
                    <li><i class="fa-regular fa-calendar"></i> <span><?= e(format_days($doc)) ?></span></li>
                    <li><i class="fa-regular fa-clock"></i> <span><?= e(format_hours($doc)) ?></span></li>
                    <li><i class="fa-solid fa-tag"></i> <span>Consultation fee: $<?= number_format((float)$doc['consultation_fee'], 2) ?></span></li>
                </ul>
            <?php else: ?>
                <p class="doctor-avail"><i class="fa-regular fa-calendar"></i> <?= e(format_days($doc)) ?></p>
            <?php endif; ?>
            <a href="<?= url('book.php?doctor=' . (int)$doc['id']) ?>" class="btn btn-primary btn-block"><i class="fa-regular fa-calendar-check"></i> Book Appointment</a>
        </div>
    </article>
    <?php
}

function page_hero(string $title, string $subtitle, string $crumb): void
{
    ?>
    <section class="page-hero">
        <div class="container">
            <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= url('index.php') ?>">Home</a><i class="fa-solid fa-chevron-right"></i><span><?= e($crumb) ?></span></nav>
            <h1><?= e($title) ?></h1>
            <p><?= e($subtitle) ?></p>
        </div>
    </section>
    <?php
}

function get_active_doctors(?int $departmentId = null, bool $featuredOnly = false): array
{
    $sql = "SELECT d.*, dp.name AS department_name, dp.slug AS department_slug, dp.icon AS department_icon
            FROM doctors d JOIN departments dp ON dp.id = d.department_id
            WHERE d.status = 'Active'";
    $params = [];
    if ($departmentId) {
        $sql .= ' AND d.department_id = ?';
        $params[] = $departmentId;
    }
    if ($featuredOnly) {
        $sql .= ' AND d.is_featured = 1';
    }
    $sql .= ' ORDER BY d.is_featured DESC, d.id';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function testimonials(): array
{
    return [
        ['name' => 'Maria Gonzalez', 'role' => 'Dermatology patient', 'rating' => 5,
         'text' => 'Booking online took less than two minutes and I got a confirmation reference straight away. Dr. Rossi finally got my eczema under control after years of trying different creams.'],
        ['name' => 'Liam O\'Connor', 'role' => 'Cardiology patient', 'rating' => 5,
         'text' => 'The cardiology team was thorough and reassuring. Having my ECG, blood tests and consultation all in one place saved me so much time and worry.'],
        ['name' => 'Aisha Bello', 'role' => 'Mother of two', 'rating' => 5,
         'text' => 'Dr. Okafor is wonderful with children. My kids actually look forward to their check-ups now! The reception staff are always friendly and organised.'],
        ['name' => 'Ethan Brooks', 'role' => 'General consultation', 'rating' => 4,
         'text' => 'I needed travel vaccinations at short notice and found an available slot the next day. Clean clinic, no waiting around, and clear advice before my trip.'],
    ];
}
