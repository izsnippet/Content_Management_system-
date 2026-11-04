<?php
// create_article.php
require_once 'includes/auth_check.php';
require_once 'config/db.php';

$error = "";
$categories = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $content     = trim($_POST['content'] ?? '');
    $tags        = trim($_POST['tags'] ?? '');
    $status      = in_array($_POST['status'], ['draft', 'published']) ? $_POST['status'] : 'draft';
    $user_id     = $_SESSION['user_id'];
    $image_name  = null;

    if (empty($title) || empty($content) || $category_id <= 0) {
        $error = "Title, Category, and Content are required.";
    } else {
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
            $file_tmp     = $_FILES['featured_image']['tmp_name'];
            $file_name    = $_FILES['featured_image']['name'];
            $file_size    = $_FILES['featured_image']['size'];
            $ext          = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed_exts)) {
                $error = "Only JPG, PNG, and WebP images are permitted.";
            } elseif ($file_size > 3 * 1024 * 1024) {
                $error = "Image size cannot exceed 3 MB.";
            } else {
                $image_name = uniqid('img_', true) . '.' . $ext;
                move_uploaded_file($file_tmp, 'uploads/images/' . $image_name);
            }
        }

        if (empty($error)) {
            $stmt = $conn->prepare("INSERT INTO articles (user_id, category_id, title, content, tags, featured_image, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisssss", $user_id, $category_id, $title, $content, $tags, $image_name, $status);
            
            if ($stmt->execute()) {
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Failed to save article. Please try again.";
            }
            $stmt->close();
        }
    }
}

$page_title = 'Create New Article';
require_once 'includes/header.php';
?>

<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">

<div class="wk-container" style="max-width:1100px;padding-top:1.75rem;padding-bottom:3rem;">

    <!-- Breadcrumb / Header navigation -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 animate-fade-in">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1" style="font-size:.82rem;">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none text-muted">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page" style="color:var(--text);font-weight:600;">Create Article</li>
                </ol>
            </nav>
            <h2 style="font-size:1.35rem;font-weight:800;letter-spacing:-.02em;margin:0;">
                <i class="bi bi-pencil-square text-accent me-2"></i>Create New Article
            </h2>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>

    <!-- Main Editor Wrap -->
    <div class="wk-editor-wrap animate-slide-up">
        <div class="wk-editor-header">
            <h4><i class="bi bi-file-earmark-plus text-accent"></i> Article Details &amp; Content</h4>
            <span class="badge bg-light text-dark border" style="font-size:.75rem;">Markdown &amp; Rich Text</span>
        </div>

        <div class="wk-editor-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2 mb-4" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="create_article.php" method="POST" enctype="multipart/form-data" id="articleForm">
                <div class="mb-3">
                    <label for="title" class="form-label">Article Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" id="title" class="form-control"
                           required
                           placeholder="e.g. Relational Database Management Systems"
                           value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                        <select name="category_id" id="category_id" class="form-select" required>
                            <option value="">-- Select Category --</option>
                            <?php if ($categories && $categories->num_rows > 0): ?>
                                <?php while ($cat = $categories->fetch_assoc()): ?>
                                    <option value="<?= $cat['id'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="tags" class="form-label">Tags (comma separated)</label>
                        <input type="text" name="tags" id="tags" class="form-control"
                               placeholder="php, mysql, database"
                               value="<?= htmlspecialchars($_POST['tags'] ?? '') ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="featured_image" class="form-label">Featured Image (Optional)</label>
                    <input type="file" name="featured_image" id="featured_image" class="form-control" accept="image/*">
                    <div class="form-text">Supported formats: JPG, PNG, WebP (Max 3 MB).</div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Article Content <span class="text-danger">*</span></label>
                    <div class="small text-muted mb-2">
                        <i class="bi bi-lightbulb text-warning me-1"></i>
                        Tip: You can use Wikipedia-style wiki links like <code>[[Article Name]]</code> to automatically link related topics!
                    </div>
                    <input type="hidden" name="content" id="hiddenContent">
                    <div id="editor" style="height: 360px;" class="bg-white"></div>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-3 border-top flex-wrap gap-2">
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x me-1"></i> Cancel
                    </a>
                    <div class="d-flex gap-2">
                        <button type="submit" name="status" value="draft" class="btn btn-warning">
                            <i class="bi bi-file-earmark me-1"></i> Save as Draft
                        </button>
                        <button type="submit" name="status" value="published" class="btn btn-success">
                            <i class="bi bi-cloud-arrow-up me-1"></i> Publish Now
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
        placeholder: 'Write your article here... You can use [[Article Name]] to automatically link to other articles!',
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

    document.getElementById('articleForm').onsubmit = function() {
        document.getElementById('hiddenContent').value = quill.root.innerHTML;
    };
</script>

<?php require_once 'includes/footer.php'; ?>
