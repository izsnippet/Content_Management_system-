<?php
// edit_article.php
require_once 'includes/auth_check.php';
require_once 'config/db.php';

$article_id = intval($_GET['id'] ?? 0);
$user_id    = $_SESSION['user_id'];
$error      = "";

// Strict Ownership Verification: Only the original creator can edit
$stmt = $conn->prepare("SELECT * FROM articles WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $article_id, $user_id);
$stmt->execute();
$article = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$article) {
    // If article doesn't exist OR belongs to another author, block access
    die("Access Denied: You can only edit articles that you created.");
}

$categories = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_title     = trim($_POST['title'] ?? '');
    $category_id   = intval($_POST['category_id'] ?? 0);
    $new_content   = trim($_POST['content'] ?? '');
    $tags          = trim($_POST['tags'] ?? '');
    $status        = in_array($_POST['status'], ['draft', 'published', 'archived']) ? $_POST['status'] : 'draft';
    $edit_summary  = trim($_POST['edit_summary'] ?? '');

    if (empty($new_title) || empty($new_content) || empty($edit_summary)) {
        $error = "Title, Content, and Edit Summary are required.";
    } else {
        // 1. Record revision history snapshot
        $rev_stmt = $conn->prepare("INSERT INTO article_revisions (article_id, edited_by_user_id, old_title, old_content, edit_summary) VALUES (?, ?, ?, ?, ?)");
        $rev_stmt->bind_param("iisss", $article_id, $user_id, $article['title'], $article['content'], $edit_summary);
        $rev_stmt->execute();
        $rev_stmt->close();

        // 2. Handle image replacement if uploaded
        $image_name = $article['featured_image'];
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
            $file_tmp     = $_FILES['featured_image']['tmp_name'];
            $file_name    = $_FILES['featured_image']['name'];
            $ext          = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed_exts) && $_FILES['featured_image']['size'] <= 3 * 1024 * 1024) {
                $image_name = uniqid('img_', true) . '.' . $ext;
                move_uploaded_file($file_tmp, 'uploads/images/' . $image_name);
            }
        }

        // 3. Update the article ensuring ownership holds
        $up_stmt = $conn->prepare("UPDATE articles SET category_id = ?, title = ?, content = ?, tags = ?, featured_image = ?, status = ? WHERE id = ? AND user_id = ?");
        $up_stmt->bind_param("isssssii", $category_id, $new_title, $new_content, $tags, $image_name, $status, $article_id, $user_id);
        
        if ($up_stmt->execute()) {
            header("Location: article.php?id=" . $article_id);
            exit();
        } else {
            $error = "Failed to update article.";
        }
        $up_stmt->close();
    }
}

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
            <i class="bi bi-pencil-square"></i> Edit Article
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
                           value="<?= htmlspecialchars($article['title']) ?>" required>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                        <select name="category_id" id="category_id" class="form-select" required>
                            <?php while ($cat = $categories->fetch_assoc()): ?>
                                <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $article['category_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="tags" class="form-label">Tags (comma separated)</label>
                        <input type="text" name="tags" id="tags" class="form-control"
                               value="<?= htmlspecialchars($article['tags'] ?? '') ?>"
                               placeholder="php, web development, tutorial">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="featured_image" class="form-label">Change Featured Image</label>
                    <?php if (!empty($article['featured_image'])): ?>
                        <div class="d-flex align-items-center gap-2 mb-2 p-2 bg-light border rounded" style="max-width:320px;">
                            <img src="uploads/images/<?= htmlspecialchars($article['featured_image']) ?>"
                                 alt="Current Featured" style="width:48px;height:48px;object-fit:cover;border-radius:4px;">
                            <div class="small text-muted text-truncate">Current: <?= htmlspecialchars($article['featured_image']) ?></div>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="featured_image" id="featured_image" class="form-control" accept="image/*">
                    <div class="form-text">Upload a new image to replace the current one (Max 3 MB).</div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Content <span class="text-danger">*</span></label>
                    <input type="hidden" name="content" id="hiddenContent">
                    <div id="editor" style="height: 360px;" class="bg-white"><?= $article['content'] ?></div>
                </div>

                <!-- Wikipedia-Style Edit Summary Box -->
                <div class="wk-edit-summary-box mb-4">
                    <div class="summary-label">
                        <i class="bi bi-clock-history"></i>
                        <span>Edit Summary (Required for Revision History)</span>
                    </div>
                    <input type="text" name="edit_summary" id="edit_summary" class="form-control bg-white" 
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
                            <option value="published" <?= $article['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                            <option value="draft" <?= $article['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="archived" <?= $article['status'] === 'archived' ? 'selected' : '' ?>>Archived</option>
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

    document.getElementById('editForm').onsubmit = function() {
        document.getElementById('hiddenContent').value = quill.root.innerHTML;
    };
</script>

<?php require_once 'includes/footer.php'; ?>
