<?php
/**
 * Contact form messages – view, mark as read, delete.
 */
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        flash('error', 'Invalid request. Please try again.');
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $action = $_POST['action'] ?? '';
        if ($action === 'delete') {
            db()->prepare('DELETE FROM contact_messages WHERE id = ?')->execute([$id]);
            flash('success', 'Message deleted.');
        } elseif ($action === 'toggle') {
            db()->prepare('UPDATE contact_messages SET is_read = 1 - is_read WHERE id = ?')->execute([$id]);
            flash('success', 'Message status updated.');
        }
    }
    redirect('admin/messages.php');
}

$messages = db()->query('SELECT * FROM contact_messages ORDER BY is_read ASC, created_at DESC')->fetchAll();

$pageTitle = 'Contact Messages';
$adminPage = 'messages';
require_once __DIR__ . '/includes/admin_header.php';
?>

<section class="panel">
    <div class="panel-head"><h2><?= count($messages) ?> message<?= count($messages) === 1 ? '' : 's' ?></h2></div>
    <?php if ($messages): ?>
        <div class="message-list">
            <?php foreach ($messages as $m): ?>
                <article class="message <?= $m['is_read'] ? '' : 'unread' ?>">
                    <div class="message-head">
                        <div class="doc-cell">
                            <span class="avatar-sm"><?= e(initials($m['name'])) ?></span>
                            <div>
                                <strong><?= e($m['name']) ?></strong>
                                <small class="cell-sub"><a href="mailto:<?= e($m['email']) ?>?subject=<?= rawurlencode('Re: ' . $m['subject']) ?>"><?= e($m['email']) ?></a><?= $m['phone'] ? ' · ' . e($m['phone']) : '' ?></small>
                            </div>
                        </div>
                        <span class="muted-sm"><?= e(format_date($m['created_at'], 'j M Y, g:i A')) ?></span>
                    </div>
                    <h3><?= $m['is_read'] ? '' : '<span class="dot"></span>' ?><?= e($m['subject']) ?></h3>
                    <p><?= nl2br(e($m['message'])) ?></p>
                    <div class="message-actions">
                        <a href="mailto:<?= e($m['email']) ?>?subject=<?= rawurlencode('Re: ' . $m['subject']) ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-reply"></i> Reply by email</a>
                        <form method="post" action="<?= url('admin/messages.php') ?>">
                            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><input type="hidden" name="action" value="toggle">
                            <button type="submit" class="btn btn-ghost btn-sm"><i class="fa-regular <?= $m['is_read'] ? 'fa-envelope' : 'fa-envelope-open' ?>"></i> Mark as <?= $m['is_read'] ? 'unread' : 'read' ?></button>
                        </form>
                        <form method="post" action="<?= url('admin/messages.php') ?>" data-confirm="Delete this message from <?= e($m['name']) ?>?">
                            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><input type="hidden" name="action" value="delete">
                            <button type="submit" class="btn btn-danger-ghost btn-sm"><i class="fa-solid fa-trash"></i> Delete</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state empty-sm"><i class="fa-regular fa-envelope-open"></i><p>No messages yet.</p></div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
