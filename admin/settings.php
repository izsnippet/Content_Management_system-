<?php
// admin/settings.php
require_once 'includes/admin_header.php';

$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $comments_enabled = isset($_POST['comments_enabled']) ? '1' : '0';
    $stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('comments_enabled', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    if ($stmt) {
        $stmt->bind_param('s', $comments_enabled);
        if ($stmt->execute()) $message = 'Platform settings saved successfully.';
        else $error = 'Could not save platform settings.';
        $stmt->close();
    } else $error = 'Site settings storage is unavailable. Create the site_settings table first.';
}
$res = $conn->query("SELECT setting_value FROM site_settings WHERE setting_key = 'comments_enabled'");
$row = $res ? $res->fetch_assoc() : null;
$is_comment_enabled = ($row['setting_value'] ?? '1') === '1';
?>
<div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom"><div><h2 class="h3 fw-bold text-dark mb-1">Platform Settings</h2><p class="text-muted small mb-0">Master toggles and community discussion switches.</p></div></div>
<?php if ($message): ?><div class="alert alert-success py-2 small"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<div class="row"><div class="col-md-8 col-lg-6"><div class="card shadow-sm border-0 p-4"><form action="settings.php" method="POST">
<h5 class="fw-bold mb-3 border-bottom pb-2">Community &amp; Moderation Rules</h5>
<div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" role="switch" id="comments_enabled" name="comments_enabled" value="1" <?= $is_comment_enabled ? 'checked' : '' ?>><label class="form-check-label fw-semibold" for="comments_enabled">Enable Global User Comments</label><div class="text-muted small">When switched off, comments are locked site-wide.</div></div>
<hr class="my-4"><button type="submit" name="save_settings" class="btn btn-primary fw-semibold px-4">Save Configuration</button></form></div></div></div>
<?php require_once 'includes/admin_footer.php'; ?>
