<?php
// search.php
require_once 'config/db.php';

$search_term = trim($_GET['q'] ?? '');
$search_like = "%" . $search_term . "%";

$query = "
    SELECT a.id, a.title, a.content, a.created_at, u.username, c.name AS category_name 
    FROM articles a
    JOIN users u ON a.user_id = u.id
    JOIN categories c ON a.category_id = c.id
    WHERE a.status = 'published' 
      AND (a.title LIKE ? OR a.content LIKE ? OR a.tags LIKE ?)
    ORDER BY a.created_at DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("sss", $search_like, $search_like, $search_like);
$stmt->execute();
$results = $stmt->get_result();

require_once 'includes/header.php';
?>

<h3 class="fw-bold mb-3">Search Results for: <span class="text-primary">"<?= htmlspecialchars($search_term) ?>"</span></h3>
<p class="text-muted small mb-4">Found <?= $results->num_rows ?> matching article(s)</p>

<?php if ($results->num_rows > 0): ?>
    <?php while ($row = $results->fetch_assoc()): ?>
        <div class="card mb-3 shadow-sm border-0">
            <div class="card-body">
                <span class="badge bg-secondary mb-2"><?= htmlspecialchars($row['category_name']) ?></span>
                <h4 class="card-title fw-bold">
                    <a href="article.php?id=<?= $row['id'] ?>" class="text-decoration-none text-dark">
                        <?= htmlspecialchars($row['title']) ?>
                    </a>
                </h4>
                <div class="text-muted small mb-2">
                    By <?= htmlspecialchars($row['username']) ?> &bull; <?= date('M d, Y', strtotime($row['created_at'])) ?>
                </div>
                <p class="card-text text-secondary mb-0">
                    <?= substr(strip_tags($row['content']), 0, 180) ?>...
                </p>
            </div>
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <div class="alert alert-light border">No articles found matching that term. Try another search!</div>
<?php endif; ?>

<?php 
$stmt->close();
require_once 'includes/footer.php'; 
?>