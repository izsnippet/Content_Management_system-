<?php
// edit_article.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/auth_check.php';

$article_id = intval($_GET['id'] ?? 0);
$user_id = intval($_SESSION['user_id'] ?? 0);
$user_role = $_SESSION['role'] ?? 'author';

if ($user_role === 'admin') {
    header("Location: admin/articles.php?error=admin_cannot_edit");
    exit();
}

if ($article_id <= 0 || $user_id <= 0) {
    header("Location: dashboard.php");
    exit();
}

require_once 'config/db.php';

$stmt = $conn->prepare("SELECT * FROM articles WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->bind_param("ii", $article_id, $user_id);
$stmt->execute();
$article = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$article) {
    header("Location: dashboard.php?error=unauthorized_edit");
    exit();
}

$categories = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_title = trim($_POST['title'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $new_content = trim($_POST['content'] ?? '');
    $tags = trim($_POST['tags'] ?? '');
    $status = in_array($_POST['status'] ?? '', ['draft', 'published'], true) ? $_POST['status'] : 'draft';
    $edit_summary = trim($_POST['edit_summary'] ?? 'Updated article content');

    if ($new_title === '' || $new_content === '' || $category_id <= 0) {
        $error = "Title, Category, and Content are required fields.";
    } else {
        $image_data = $article['featured_image'];

        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $file_tmp = $_FILES['featured_image']['tmp_name'];
            $file_size = $_FILES['featured_image']['size'];
            $mime_type = mime_content_type($file_tmp);

            if (!in_array($mime_type, $allowed_types, true)) {
                $error = "Only JPG, PNG, WebP, and GIF images are allowed.";
            } elseif ($file_size > 2 * 1024 * 1024) {
                $error = "Image size cannot exceed 2MB.";
            } else {
                $raw_binary = file_get_contents($file_tmp);
                if ($raw_binary === false) {
                    $error = "Unable to read the uploaded image.";
                } else {
                    $image_data = 'data:' . $mime_type . ';base64,' . base64_encode($raw_binary);
                }
            }
        } elseif (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $error = "The featured image could not be uploaded.";
        }

        if ($error === '') {
            $rev_stmt = $conn->prepare("INSERT INTO article_revisions (article_id, edited_by_user_id, old_title, old_content, edit_summary) VALUES (?, ?, ?, ?, ?)");
            $rev_stmt->bind_param("iisss", $article_id, $user_id, $article['title'], $article['content'], $edit_summary);
            if (!$rev_stmt->execute()) {
                $error = "Failed to save article revision: " . $conn->error;
            }
            $rev_stmt->close();
        }

        if ($error === '') {
            $up_stmt = $conn->prepare("UPDATE articles SET category_id = ?, title = ?, content = ?, tags = ?, featured_image = ?, status = ? WHERE id = ? AND user_id = ?");
            $up_stmt->bind_param("isssssii", $category_id, $new_title, $new_content, $tags, $image_data, $status, $article_id, $user_id);

            if ($up_stmt->execute()) {
                $up_stmt->close();
                header("Location: article.php?id=" . $article_id);
                exit();
            } else {
                $error = "Database update error: " . $conn->error;
                $up_stmt->close();
            }
        }
    }
}

$form_title = $_POST['title'] ?? $article['title'];
$form_content = $_POST['content'] ?? $article['content'];
$form_category_id = intval($_POST['category_id'] ?? $article['category_id']);
$form_tags = $_POST['tags'] ?? ($article['tags'] ?? '');
$form_status = $_POST['status'] ?? $article['status'];
$form_summary = $_POST['edit_summary'] ?? '';
$page_title = 'Edit: ' . $article['title'];
require_once 'includes/header.php';
?>

<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">

<div class="wk-container" style="max-width:1100px;padding-top:1.75rem;padding-bottom:3rem;">

    <!-- Article Navigation Tabs -->
    <div class="wk-article-tabs">
        <a href="article.php?id=<?= $article_id ?>">
            <i class="bi bi-book"></i> Read
        </a>
        <a href="edit_article.php?id=<?= $article_id ?>" class="active">
            <i class="bi bi-pencil-square"></i> Edit My Article
        </a>
        <a href="history.php?id=<?= $article_id ?>">
            <i class="bi bi-clock-history"></i> View History
        </a>
    </div>

    <!-- Header info banner -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 animate-fade-in">
        <div>
            <h2 style="font-size:1.35rem;font-weight:800;letter-spacing:-.02em;margin-bottom:.2rem;">
                <i class="bi bi-pencil-square text-accent me-2"></i>Edit Article
            </h2>
            <div style="font-size:.85rem;color:var(--muted);">
                Editing: <strong style="color:var(--text);"><?= htmlspecialchars($article['title']) ?></strong>
            </div>
        </div>
        <a href="history.php?id=<?= $article_id ?>" class="btn btn-outline-info btn-sm">
            <i class="bi bi-clock-history me-1"></i> View Revision History
        </a>
    </div>

    <!-- Editor Card -->
    <div class="wk-editor-wrap animate-slide-up">
        <div class="wk-editor-header">
            <h4><i class="bi bi-sliders text-accent"></i> Modify Article Details &amp; Content</h4>
            <span class="badge badge-category" style="font-size:.73rem;">
                Status: <?= htmlspecialchars(ucfirst($article['status'])) ?>
            </span>
        </div>

        <div class="wk-editor-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2 mb-4" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="edit_article.php?id=<?= $article_id ?>" method="POST" enctype="multipart/form-data" id="editForm">
                <div class="mb-3">
                    <label for="title" class="form-label">Article Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" id="title" class="form-control"
                           value="<?= htmlspecialchars($form_title) ?>" required>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                        <select name="category_id" id="category_id" class="form-select" required>
                            <?php while ($cat = $categories->fetch_assoc()): ?>
                                <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $form_category_id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="tags" class="form-label">Tags (comma separated)</label>
                        <input type="text" name="tags" id="tags" class="form-control"
                               value="<?= htmlspecialchars($form_tags) ?>"
                               placeholder="php, web development, tutorial">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="featured_image" class="form-label">Change Featured Image</label>
                    <?php if (!empty($article['featured_image'])): ?>
                        <?php $preview_image_src = str_starts_with($article['featured_image'], 'data:image/') ? $article['featured_image'] : 'uploads/images/' . rawurlencode($article['featured_image']); ?>
                        <div class="d-flex align-items-center gap-2 mb-2 p-2 bg-light border rounded" style="max-width:320px;">
                            <img src="<?= htmlspecialchars($preview_image_src, ENT_QUOTES, 'UTF-8') ?>"
                                 alt="Current Featured" style="width:100%;max-width:320px;max-height:220px;object-fit:contain;border-radius:4px;background:#f1f5f9;">
                            <div class="small text-muted text-truncate">Current: <?= htmlspecialchars($article['featured_image']) ?></div>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="featured_image" id="featured_image" class="form-control" accept="image/*">
                    <div class="form-text">Upload a new image to replace the current one (Max 2 MB).</div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Content <span class="text-danger">*</span></label>
                    <input type="hidden" name="content" id="hiddenContent">
                    <div id="editor" style="height: 360px;" class="bg-white"></div>
                </div>

                <!-- Wikipedia-Style Edit Summary Box -->
                <div class="wk-edit-summary-box mb-4">
                    <div class="summary-label">
                        <i class="bi bi-clock-history"></i>
                        <span>Edit Summary (Required for Revision History)</span>
                    </div>
                    <input type="text" name="edit_summary" id="edit_summary" class="form-control bg-white"
                           value="<?= htmlspecialchars($form_summary) ?>"
                           placeholder="Briefly describe what you changed (e.g. Added section on architecture, fixed typos, updated references)" required>
                    <div class="form-text mt-1" style="color:var(--text-secondary);font-size:.78rem;">
                        <i class="bi bi-shield-check text-success me-1"></i>
                        This summary will be permanently archived in the revision history for transparency and peer review.
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-3 border-top flex-wrap gap-2">
                    <a href="article.php?id=<?= $article_id ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-x me-1"></i> Cancel
                    </a>
                    <div class="d-flex align-items-center gap-2">
                        <label for="statusSelect" class="form-label mb-0 small text-muted">Status:</label>
                        <select name="status" id="statusSelect" class="form-select w-auto d-inline-block">
                            <option value="published" <?= $form_status === 'published' ? 'selected' : '' ?>>Published</option>
                            <option value="draft" <?= $form_status === 'draft' ? 'selected' : '' ?>>Draft</option>
                        </select>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2-circle me-1"></i> Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script>
    const quill = new Quill('#editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ 'header': [2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                ['blockquote', 'code-block'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['link', 'clean']
            ]
        }
    });

    const initialContent = <?= json_encode($form_content, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    quill.clipboard.dangerouslyPasteHTML(initialContent);

    document.getElementById('editForm').addEventListener('submit', function(event) {
        document.getElementById('hiddenContent').value = quill.root.innerHTML;
        if (quill.getText().trim().length === 0) {
            event.preventDefault();
            alert('Article content cannot be empty.');
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?>
