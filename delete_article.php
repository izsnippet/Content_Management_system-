<?php
// delete_article.php
require_once 'includes/auth_check.php';
require_once 'config/db.php';

$article_id = intval($_GET['id'] ?? 0);
$user_id    = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT featured_image FROM articles WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $article_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();

if ($article = $res->fetch_assoc()) {
    if (!empty($article['featured_image']) && file_exists('uploads/images/' . $article['featured_image'])) {
        unlink('uploads/images/' . $article['featured_image']);
    }

    $del_stmt = $conn->prepare("DELETE FROM articles WHERE id = ?");
    $del_stmt->bind_param("i", $article_id);
    $del_stmt->execute();
    $del_stmt->close();
}

$stmt->close();
header("Location: dashboard.php");
exit();