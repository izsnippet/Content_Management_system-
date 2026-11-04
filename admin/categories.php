<?php
// admin/categories.php
require_once 'includes/admin_header.php';

$message = "";
$error   = "";

// Add Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $cat_name = trim($_POST['name'] ?? '');
    if (!empty($cat_name)) {
        $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->bind_param("s", $cat_name);
        if ($stmt->execute()) {
            $message = "Category added successfully.";
        } else {
            $error = "Category already exists or database error occurred.";
        }
        $stmt->close();
    }
}

// Delete Category
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    // Check if category has articles
    $chk = $conn->prepare("SELECT COUNT(*) AS total FROM articles WHERE category_id = ?");
    $chk->bind_param("i", $del_id);
    $chk->execute();
    $in_use = $chk->get_result()->fetch_assoc()['total'];
    $chk->close();

    if ($in_use > 0) {
        $error = "Cannot delete this category: {$in_use} article(s) are currently assigned to it.";
    } else {
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", $del_id);
        if ($stmt->execute()) {
            $message = "Category deleted successfully.";
        }
        $stmt->close();
    }
}

$categories = $conn->query("
    SELECT c.id, c.name, COUNT(a.id) AS article_count 
    FROM categories c 
    LEFT JOIN articles a ON c.id = a.category_id 
    GROUP BY c.id, c.name 
    ORDER BY c.name ASC
");
?>

<div class="d-flex justify-content-between align-items-center pb-2 mb-4 border-bottom">
    <h2 class="h3 fw-bold mb-0">Category Manager</h2>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-success py-2 small"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="row g-4">
    <!-- Add Category Form -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0">Add New Category</h6>
            </div>
            <div class="card-body">
                <form action="categories.php" method="POST">
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Category Name</label>
                        <input type="text" name="name" id="name" class="form-control" required placeholder="e.g. Cloud Computing">
                    </div>
                    <button type="submit" name="add_category" class="btn btn-primary w-100">
                        <i class="bi bi-plus-lg me-1"></i> Create Category
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Categories List -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">ID</th>
                                <th>Category Name</th>
                                <th>Articles</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($categories && $categories->num_rows > 0): ?>
                                <?php while ($c = $categories->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-3 text-muted small"><?= $c['id'] ?></td>
                                        <td class="fw-semibold text-dark"><?= htmlspecialchars($c['name']) ?></td>
                                        <td><span class="badge bg-secondary"><?= $c['article_count'] ?></span></td>
                                        <td class="text-end pe-3">
                                            <a href="categories.php?delete=<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this category?');">
                                                <i class="bi bi-trash"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">No categories created yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/admin_footer.php'; ?>