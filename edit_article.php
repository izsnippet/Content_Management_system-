<?php
// edit_article.php
require_once 'includes/auth_check.php';
require_once 'config/db.php';

$article_id = intval($_GET['id'] ?? 0);
$user_id    = $_SESSION['user_id'];
$error      = "";

$stmt = $conn->prepare("SELECT * FROM articles WHERE id = ?");
$stmt->bind_param("i", $article_id);
$stmt->execute();
$article = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$article) {
    die("Article not found.");
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
        // 1. Preserve current version into article_revisions
        $rev_stmt = $conn->prepare("INSERT INTO article_revisions (article_id, edited_by_user_id, old_title, old_content, edit_summary) VALUES (?, ?, ?, ?, ?)");
        $rev_stmt->bind_param("iisss", $article_id, $user_id, $article['title'], $article['content'], $edit_summary);
        $rev_stmt->execute();
        $rev_stmt->close();

        // 2. Handle featured image update if provided
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

        // 3. Update the article record
        $up_stmt = $conn->prepare("UPDATE articles SET category_id = ?, title = ?, content = ?, tags = ?, featured_image = ?, status = ? WHERE id = ?");
        $up_stmt->bind_param("isssssi", $category_id, $new_title, $new_content, $tags, $image_name, $status, $article_id);
        
        if ($up_stmt->execute()) {
            header("Location: article.php?id=" . $article_id);
            exit();
        } else {
            $error = "Failed to update article.";
        }
        $up_stmt->close();
    }
}

require_once 'includes/header.php';
?>

<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">

<div class="card shadow-sm border-0 my-3">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h4 class="fw-bold mb-0">Edit Article / Edit Source</h4>
        <a href="history.php?id=<?= $article_id ?>" class="btn btn-outline-info btn-sm">
            <i class="bi bi-clock-history"></i> View History
        </a>
    </div>
    <div class="card-body p-4">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="edit_article.php?id=<?= $article_id ?>" method="POST" enctype="multipart/form-data" id="editForm">
            <div class="mb-3">
                <label for="title" class="form-label fw-semibold">Article Title</label>
                <input type="text" name="title" id="title" class="form-control" value="<?= htmlspecialchars($article['title']) ?>" required>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="category_id" class="form-label fw-semibold">Category</label>
                    <select name="category_id" id="category_id" class="form-select" required>
                        <?php while ($cat = $categories->fetch_assoc()): ?>
                            <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $article['category_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="tags" class="form-label fw-semibold">Tags</label>
                    <input type="text" name="tags" id="tags" class="form-control" value="<?= htmlspecialchars($article['tags'] ?? '') ?>">
                </div>
            </div>

            <div class="mb-3">
                <label for="featured_image" class="form-label fw-semibold">Change Featured Image</label>
                <input type="file" name="featured_image" id="featured_image" class="form-control" accept="image/*">
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Content</label>
                <input type="hidden" name="content" id="hiddenContent">
                <div id="editor" style="height: 320px;" class="bg-white"><?= $article['content'] ?></div>
            </div>

            <!-- Wikipedia Edit Summary -->
            <div class="mb-4 p-3 bg-light rounded border">
                <label for="edit_summary" class="form-label fw-bold text-primary mb-1">
                    <i class="bi bi-pencil-fill"></i> Edit Summary (Required)
                </label>
                <input type="text" name="edit_summary" id="edit_summary" class="form-control" 
                       placeholder="Briefly describe what you changed (e.g. Added section on architecture, fixed typo)" required>
                <small class="text-muted">This will be permanently archived in the revision history for this page.</small>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="article.php?id=<?= $article_id ?>" class="btn btn-outline-secondary">Cancel</a>
                <select name="status" class="form-select w-auto d-inline-block">
                    <option value="published" <?= $article['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                    <option value="draft" <?= $article['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="archived" <?= $article['status'] === 'archived' ? 'selected' : '' ?>>Archived</option>
                </select>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
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