<?php
$pageTitle  = 'Our Doctors';
$activePage = 'doctors';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/components.php';

$departments = get_departments();
$selected    = null;
$slug        = $_GET['department'] ?? '';
foreach ($departments as $d) {
    if ($d['slug'] === $slug) {
        $selected = $d;
    }
}
$doctors = get_active_doctors($selected ? (int)$selected['id'] : null);

page_hero('Our Doctors', 'Experienced, caring specialists ready to help you feel your best.', 'Our Doctors');
?>

<section class="section">
    <div class="container">
        <div class="filter-bar reveal" role="tablist" aria-label="Filter doctors by department">
            <a href="<?= url('doctors.php') ?>" class="chip <?= !$selected ? 'active' : '' ?>">All departments</a>
            <?php foreach ($departments as $d): ?>
                <a href="<?= url('doctors.php?department=' . e($d['slug'])) ?>" class="chip <?= $selected && $selected['id'] == $d['id'] ? 'active' : '' ?>">
                    <i class="fa-solid <?= e($d['icon']) ?>"></i> <?= e($d['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($doctors): ?>
            <p class="results-count"><?= count($doctors) ?> doctor<?= count($doctors) === 1 ? '' : 's' ?> <?= $selected ? 'in ' . e($selected['name']) : 'available' ?></p>
            <div class="doctors-grid doctors-grid-detailed">
                <?php foreach ($doctors as $doc) { render_doctor_card($doc, true); } ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa-solid fa-user-doctor"></i>
                <h3>No doctors found</h3>
                <p>There are currently no doctors available in this department.</p>
                <a href="<?= url('doctors.php') ?>" class="btn btn-primary">View all doctors</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
