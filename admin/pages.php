<?php
// admin/pages.php
require_once 'includes/admin_header.php';
$message = '';
$error = '';
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_token'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($csrf, $_POST['csrf_token'])) $error = 'Invalid security token. Reload and try again.';
    elseif (isset($_POST['delete_page'])) {
        $id = intval($_POST['page_id'] ?? 0);
        $stmt = $conn->prepare('DELETE FROM pages WHERE id = ?'); $stmt->bind_param('i', $id);
        $message = $stmt->execute() ? 'Static page deleted successfully.' : '';
        if (!$message) $error = 'Could not delete the page.';
        $stmt->close();
    } elseif (isset($_POST['save_page'])) {
        $page_id = intval($_POST['page_id'] ?? 0); $title = trim($_POST['title'] ?? '');
        $slug = strtolower(trim($_POST['slug'] ?? ''));
        $slug = trim(preg_replace('/[^a-z0-9-]+/', '-', $slug), '-');
        $content = trim($_POST['content'] ?? '');
        $status = in_array($_POST['status'] ?? '', ['published', 'draft'], true) ? $_POST['status'] : 'draft';
        if ($title === '' || $slug === '' || $content === '') $error = 'Title, slug, and content are required.';
        elseif ($page_id > 0) {
            $stmt = $conn->prepare('UPDATE pages SET title = ?, slug = ?, content = ?, status = ? WHERE id = ?');
            $stmt->bind_param('ssssi', $title, $slug, $content, $status, $page_id);
            if ($stmt->execute()) $message = 'Page updated successfully.'; else $error = 'Slug already in use or database error occurred.';
            $stmt->close();
        } else {
            $stmt = $conn->prepare('INSERT INTO pages (title, slug, content, status) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('ssss', $title, $slug, $content, $status);
            if ($stmt->execute()) $message = 'New static page created.'; else $error = 'Slug already in use or database error occurred.';
            $stmt->close();
        }
    }
}
$edit_page = null;
if (isset($_GET['edit'])) { $eid = intval($_GET['edit']); $st = $conn->prepare('SELECT * FROM pages WHERE id = ?'); $st->bind_param('i', $eid); $st->execute(); $edit_page = $st->get_result()->fetch_assoc(); $st->close(); }
$pages = $conn->query('SELECT * FROM pages ORDER BY title ASC');
?>
<div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom"><div><h2 class="h3 fw-bold text-dark mb-1">Static Page Manager</h2><p class="text-muted small mb-0">Create and maintain informational pages.</p></div></div>
<?php if ($message): ?><div class="alert alert-success py-2 small"><?= htmlspecialchars($message) ?></div><?php endif; ?><?php if ($error): ?><div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<div class="row g-4"><div class="col-lg-5"><div class="card shadow-sm border-0"><div class="card-header bg-white py-3 fw-bold"><?= $edit_page ? 'Edit Page #' . (int)$edit_page['id'] : 'Create New Page' ?></div><div class="card-body">
<form action="pages.php" method="POST"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>"><input type="hidden" name="page_id" value="<?= (int)($edit_page['id'] ?? 0) ?>">
<div class="mb-3"><label class="form-label fw-semibold">Page Title</label><input type="text" name="title" class="form-control" required value="<?= htmlspecialchars($edit_page['title'] ?? '') ?>"></div>
<div class="mb-3"><label class="form-label fw-semibold">URL Slug</label><input type="text" name="slug" class="form-control" required value="<?= htmlspecialchars($edit_page['slug'] ?? '') ?>" placeholder="terms-of-service"></div>
<div class="mb-3"><label class="form-label fw-semibold">HTML Content</label><textarea name="content" class="form-control" rows="8" required><?= htmlspecialchars($edit_page['content'] ?? '') ?></textarea></div>
<div class="mb-3"><label class="form-label fw-semibold">Status</label><select name="status" class="form-select"><option value="published" <?= ($edit_page['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option><option value="draft" <?= ($edit_page['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option></select></div>
<div class="d-flex justify-content-between"><?php if ($edit_page): ?><a href="pages.php" class="btn btn-light">Cancel Edit</a><?php else: ?><span></span><?php endif; ?><button type="submit" name="save_page" class="btn btn-primary fw-semibold">Save Page</button></div></form>
</div></div></div><div class="col-lg-7"><div class="card shadow-sm border-0"><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th class="ps-3">Page Title</th><th>Slug</th><th>Status</th><th class="text-end pe-3">Actions</th></tr></thead><tbody>
<?php if ($pages && $pages->num_rows): while ($p = $pages->fetch_assoc()): ?><tr><td class="ps-3 fw-bold text-dark"><?= htmlspecialchars($p['title']) ?></td><td><code>/page.php?slug=<?= htmlspecialchars($p['slug']) ?></code></td><td><span class="badge <?= $p['status'] === 'published' ? 'bg-success' : 'bg-secondary' ?>"><?= htmlspecialchars($p['status']) ?></span></td><td class="text-end pe-3"><a href="../page.php?slug=<?= rawurlencode($p['slug']) ?>" target="_blank" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-box-arrow-up-right"></i></a> <a href="pages.php?edit=<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline-secondary me-1"><i class="bi bi-pencil"></i></a><form method="POST" class="d-inline" onsubmit="return confirm('Delete this page?');"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>"><input type="hidden" name="page_id" value="<?= (int)$p['id'] ?>"><button name="delete_page" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form></td></tr><?php endwhile; else: ?><tr><td colspan="4" class="text-center py-4 text-muted">No pages created yet.</td></tr><?php endif; ?>
</tbody></table></div></div></div></div></div>
<?php require_once 'includes/admin_footer.php'; ?>
