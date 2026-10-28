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

require_once 'includes/header.php';
?>

<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">

<div class="card shadow-sm border-0 my-3">
    <div class="card-header bg-white py-3">
        <h4 class="fw-bold mb-0">Create New Article</h4>
    </div>
    <div class="card-body p-4">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="create_article.php" method="POST" enctype="multipart/form-data" id="articleForm">
            <div class="mb-3">
                <label for="title" class="form-label fw-semibold">Article Title</label>
                <input type="text" name="title" id="title" class="form-control" required placeholder="e.g. Relational Database Management Systems">
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="category_id" class="form-label fw-semibold">Category</label>
                    <select name="category_id" id="category_id" class="form-select" required>
                        <option value="">-- Select Category --</option>
                        <?php while ($cat = $categories->fetch_assoc()): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="tags" class="form-label fw-semibold">Tags (comma separated)</label>
                    <input type="text" name="tags" id="tags" class="form-control" placeholder="php, mysql, database">
                </div>
            </div>

            <div class="mb-3">
                <label for="featured_image" class="form-label fw-semibold">Featured Image (Optional)</label>
                <input type="file" name="featured_image" id="featured_image" class="form-control" accept="image/*">
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Content</label>
                <input type="hidden" name="content" id="hiddenContent">
                <div id="editor" style="height: 320px;" class="bg-white"></div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" name="status" value="draft" class="btn btn-warning text-dark">Save as Draft</button>
                <button type="submit" name="status" value="published" class="btn btn-success">Publish Now</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script>
    const quill = new Quill('#editor', {
        theme: 'snow',
        placeholder: 'Write your article here... You can use [[Article Name]] to link to other articles!',
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