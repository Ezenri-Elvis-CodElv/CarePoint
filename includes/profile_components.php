<?php
/**
 * Patient profile UI fragments (used by profile.php).
 * Icons are Lucide, rendered client-side from <i data-lucide="…"> placeholders.
 */

const PROFILE_TABS = [
    'overview'      => ['Overview', 'layout-dashboard'],
    'personal'      => ['Personal Information', 'user-round'],
    'history'       => ['Medical History', 'clipboard-list'],
    'appointments'  => ['Appointments', 'calendar-days'],
    'prescriptions' => ['Prescriptions', 'pill'],
    'records'       => ['Medical Records', 'folder-heart'],
    'settings'      => ['Account Settings', 'settings'],
];

/** Lucide icon placeholder. */
function lc(string $name, string $class = ''): string
{
    return '<i data-lucide="' . e($name) . '" class="lc ' . e($class) . '" aria-hidden="true"></i>';
}

function profile_url(string $tab = 'overview', array $extra = []): string
{
    $q = http_build_query(array_merge(['tab' => $tab], $extra));
    return url('profile.php?' . $q);
}

/**
 * Header: identity on the left, actions on the right, tab bar underneath.
 * $status = ['verified' => bool, 'membership' => string, 'upcoming' => int]
 */
function render_profile_header(array $p, array $status, string $activeTab): void
{
    $first = explode(' ', trim($p['full_name']))[0];
    $code  = patient_code((int)$p['id']);
    ?>
    <section class="pf-header" aria-labelledby="pfName">
        <div class="container">
            <nav class="pf-crumb" aria-label="Breadcrumb">
                <a href="<?= url('dashboard.php') ?>">Dashboard</a>
                <?= lc('chevron-right') ?>
                <span aria-current="page">My Profile</span>
            </nav>

            <div class="pf-top">
                <div class="pf-identity">
                    <div class="pf-avatar" aria-hidden="true">
                        <span class="pf-avatar-initials"><?= e(initials($p['full_name'])) ?></span>
                        <?php if ($status['verified']): ?>
                            <span class="pf-avatar-badge"><?= lc('check') ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="pf-id-text">
                        <p class="pf-greeting">Welcome back, <?= e($first) ?>.</p>
                        <h1 class="pf-name" id="pfName"><?= e($p['full_name']) ?></h1>
                        <ul class="pf-meta">
                            <li>
                                <span class="pf-meta-label">Patient ID</span>
                                <button type="button" class="pf-copy" data-copy="<?= e($code) ?>" aria-label="Copy patient ID <?= e($code) ?>">
                                    <span class="pf-mono"><?= e($code) ?></span>
                                    <?= lc('copy', 'pf-copy-icon') ?><?= lc('check', 'pf-copied-icon') ?>
                                </button>
                            </li>
                            <li class="<?= $status['verified'] ? 'pf-ok' : 'pf-pending' ?>">
                                <?= lc($status['verified'] ? 'shield-check' : 'shield') ?>
                                <?= $status['verified'] ? 'Identity verified' : 'Verification at first visit' ?>
                            </li>
                            <li>
                                <?= lc('heart-pulse') ?>
                                <?= e($status['membership']) ?>
                                <span class="pf-muted">· since <?= e(format_date($p['created_at'], 'M Y')) ?></span>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="pf-actions">
                    <a href="<?= profile_url('appointments') ?>" class="pf-icon-btn" aria-label="<?= (int)$status['upcoming'] ?> upcoming appointment<?= $status['upcoming'] === 1 ? '' : 's' ?>" title="Upcoming appointments">
                        <?= lc('bell') ?>
                        <?php if ($status['upcoming'] > 0): ?><span class="pf-dot"><?= (int)$status['upcoming'] ?></span><?php endif; ?>
                    </a>
                    <a href="<?= profile_url('settings') ?>" class="pf-btn pf-btn-quiet <?= $activeTab === 'settings' ? 'is-active' : '' ?>">
                        <?= lc('settings') ?><span>Manage account</span>
                    </a>
                    <a href="<?= profile_url('personal', ['edit' => 1]) ?>#profileForm" class="pf-btn pf-btn-solid">
                        <?= lc('pen-line') ?><span>Edit profile</span>
                    </a>
                </div>
            </div>

            <nav class="pf-tabs" aria-label="Profile sections">
                <div class="pf-tabs-track">
                    <?php foreach (PROFILE_TABS as $key => [$label, $icon]): ?>
                        <a href="<?= profile_url($key) ?>" class="pf-tab <?= $activeTab === $key ? 'is-active' : '' ?>" <?= $activeTab === $key ? 'aria-current="page"' : '' ?>>
                            <?= lc($icon) ?><span><?= e($label) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </nav>
        </div>
    </section>
    <?php
}

/** A single label/value row; empty values render as a muted "Not provided". */
function info_row(string $icon, string $label, ?string $value, string $extra = ''): void
{
    ?>
    <div class="pf-row">
        <span class="pf-row-icon"><?= lc($icon) ?></span>
        <dt><?= e($label) ?></dt>
        <dd><?php if ($value !== null && $value !== ''): ?><?= e($value) ?><?= $extra ?><?php else: ?><span class="pf-empty-val">Not provided</span><?php endif; ?></dd>
    </div>
    <?php
}

/** Personal information card: key facts strip, contact rows and emergency contact. */
function render_personal_info(array $p, bool $showEditLink = true): void
{
    $age = age_from_dob($p['date_of_birth'] ?? null) ?? ($p['age'] !== null ? (int)$p['age'] : null);
    $dob = !empty($p['date_of_birth']) ? format_date($p['date_of_birth'], 'j F Y') : null;
    ?>
    <article class="pf-card">
        <header class="pf-card-head">
            <div>
                <h2>Personal information</h2>
                <p>Details your care team sees when you book.</p>
            </div>
            <?php if ($showEditLink): ?>
                <a href="<?= profile_url('personal', ['edit' => 1]) ?>#profileForm" class="pf-link"><?= lc('pen-line') ?> Edit</a>
            <?php endif; ?>
        </header>

        <div class="pf-facts">
            <div><span><?= lc('droplet') ?> Blood group</span><strong><?= e($p['blood_group'] ?? '') ?: '—' ?></strong></div>
            <div><span><?= lc('cake') ?> Age</span><strong><?= $age !== null ? $age . '<small> yrs</small>' : '—' ?></strong></div>
            <div><span><?= lc('contact-round') ?> Gender</span><strong><?= e($p['gender'] ?: '—') ?></strong></div>
        </div>

        <dl class="pf-rows">
            <?php info_row('user-round', 'Full name', $p['full_name']); ?>
            <?php info_row('mail', 'Email address', $p['email']); ?>
            <?php info_row('phone', 'Phone number', $p['phone']); ?>
            <?php info_row('calendar-days', 'Date of birth', $dob); ?>
        </dl>

        <div class="pf-emergency">
            <span class="pf-emergency-icon"><?= lc('phone-call') ?></span>
            <div>
                <span class="pf-eyebrow">Emergency contact</span>
                <?php if (!empty($p['emergency_name'])): ?>
                    <strong><?= e($p['emergency_name']) ?></strong>
                    <span class="pf-muted"><?= e($p['emergency_relation'] ?? '') ?><?= !empty($p['emergency_relation']) && !empty($p['emergency_phone']) ? ' · ' : '' ?><?= e($p['emergency_phone'] ?? '') ?></span>
                <?php else: ?>
                    <strong class="pf-empty-val">No emergency contact on file</strong>
                    <a href="<?= profile_url('personal', ['edit' => 1]) ?>#profileForm" class="pf-link">Add one</a>
                <?php endif; ?>
            </div>
        </div>
    </article>
    <?php
}

/** Compact appointment row. */
function render_appt_row(array $a): void
{
    ?>
    <li class="pf-appt">
        <span class="pf-appt-date">
            <span><?= e(format_date($a['appointment_date'], 'M')) ?></span>
            <strong><?= e(format_date($a['appointment_date'], 'd')) ?></strong>
        </span>
        <div class="pf-appt-body">
            <strong><?= e($a['doctor_name']) ?></strong>
            <span class="pf-muted"><?= e($a['department_name']) ?> · <?= e(format_time($a['appointment_time'])) ?> · <?= e($a['reference']) ?></span>
        </div>
        <?= status_badge($a['status']) ?>
        <a href="<?= url('track.php?ref=' . urlencode($a['reference'])) ?>" class="pf-icon-btn pf-icon-btn-sm" aria-label="View booking <?= e($a['reference']) ?>"><?= lc('chevron-right') ?></a>
    </li>
    <?php
}

function render_profile_empty(string $icon, string $title, string $text, ?array $action = null): void
{
    ?>
    <div class="pf-empty">
        <span class="pf-empty-icon"><?= lc($icon) ?></span>
        <h3><?= e($title) ?></h3>
        <p><?= e($text) ?></p>
        <?php if ($action): ?>
            <a href="<?= e($action[1]) ?>" class="pf-btn pf-btn-quiet"><?= lc($action[2] ?? 'arrow-right') ?><span><?= e($action[0]) ?></span></a>
        <?php endif; ?>
    </div>
    <?php
}
