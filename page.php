<?php
// page.php
require_once 'config/db.php';
$slug = trim($_GET['slug'] ?? '');
if ($slug === '') { header('Location: index.php'); exit(); }
$stmt = $conn->prepare("SELECT title, content, updated_at FROM pages WHERE slug = ? AND status = 'published' LIMIT 1");
$stmt->bind_param('s', $slug);
$stmt->execute();
$page = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$page) { http_response_code(404); die('Page not found or is in draft mode.'); }
$page_title = $page['title'];
require_once 'includes/header.php';
?>
<div class="row justify-content-center"><div class="col-lg-9"><div class="card shadow-sm border-0 p-4 p-md-5 my-4">
<h1 class="fw-bold mb-3"><?= htmlspecialchars($page['title']) ?></h1>
<div class="text-muted small pb-3 mb-4 border-bottom">Last modified: <?= date('F d, Y', strtotime($page['updated_at'])) ?></div>
<div class="page-content fs-5 lh-lg"><?= $page['content'] ?></div>
</div></div></div>
<?php require_once 'includes/footer.php'; ?>
