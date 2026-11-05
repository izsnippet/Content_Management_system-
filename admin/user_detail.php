<?php
// admin/user_detail.php
require_once 'includes/admin_header.php';
$uid = intval($_GET['id'] ?? 0);
if ($uid <= 0) { header('Location: users.php'); exit(); }
$stmt = $conn->prepare('SELECT id, username, email, role, status, bio, profile_pic, reset_expires_at, created_at FROM users WHERE id = ?');
$stmt->bind_param('i', $uid); $stmt->execute(); $user = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$user) { http_response_code(404); die('User not found.'); }
$arts = $conn->prepare('SELECT id, title, status, created_at, updated_at FROM articles WHERE user_id = ? ORDER BY created_at DESC');
$arts->bind_param('i', $uid); $arts->execute(); $user_articles = $arts->get_result(); $arts->close();
$cmts = $conn->prepare('SELECT c.id, c.comment_text, c.created_at, a.id AS article_id, a.title AS article_title FROM comments c JOIN articles a ON a.id = c.article_id WHERE c.user_id = ? ORDER BY c.created_at DESC');
$cmts->bind_param('i', $uid); $cmts->execute(); $user_comments = $cmts->get_result(); $cmts->close();
$avatar_path = '../uploads/profiles/' . ($user['profile_pic'] ?? '');
$avatar_url = !empty($user['profile_pic']) && $user['profile_pic'] !== 'default_avatar.png' && is_file($avatar_path) ? $avatar_path : 'https://cdn-icons-png.flaticon.com/512/149/149071.png';
?>
<div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom"><div><h2 class="h3 fw-bold text-dark mb-1">User Audit Record: <?= htmlspecialchars($user['username']) ?></h2><p class="text-muted small mb-0">Profile, account status, and activity.</p></div><a href="users.php" class="btn btn-outline-secondary btn-sm">&larr; Back to Directory</a></div>
<div class="row g-4 mb-4"><div class="col-lg-4"><div class="card shadow-sm border-0 p-4 text-center">
<img src="<?= htmlspecialchars($avatar_url, ENT_QUOTES, 'UTF-8') ?>" alt="Profile picture" class="rounded-circle mx-auto mb-3 border shadow-sm" style="width:100px;height:100px;object-fit:cover">
<h4 class="fw-bold mb-1"><?= htmlspecialchars($user['username']) ?></h4><div class="text-muted small mb-2"><?= htmlspecialchars($user['email']) ?></div><div><span class="badge bg-secondary"><?= htmlspecialchars($user['role']) ?></span> <span class="badge <?= $user['status'] === 'banned' ? 'bg-danger' : 'bg-success' ?>"><?= htmlspecialchars($user['status']) ?></span></div><hr>
<div class="text-start small"><div class="mb-2"><strong>Database ID:</strong> #<?= (int)$user['id'] ?></div><div class="mb-2"><strong>Registered:</strong> <?= htmlspecialchars(date('F d, Y H:i:s', strtotime($user['created_at']))) ?></div><div class="mb-2"><strong>Password:</strong> Stored as a password hash</div><div class="mb-2"><strong>Password reset:</strong> <?= !empty($user['reset_expires_at']) ? 'Expires ' . htmlspecialchars(date('M d, Y H:i', strtotime($user['reset_expires_at']))) : 'No active reset request' ?></div><div><strong>Bio:</strong> <?= !empty($user['bio']) ? nl2br(htmlspecialchars($user['bio'])) : '<em>No bio provided.</em>' ?></div></div>
</div></div><div class="col-lg-8">
<div class="card shadow-sm border-0 mb-4"><div class="card-header bg-white py-3 fw-bold">Authored Articles (<?= $user_articles->num_rows ?>)</div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th class="ps-3">Title</th><th>Status</th><th>Created</th><th class="text-end pe-3">Action</th></tr></thead><tbody>
<?php if ($user_articles->num_rows): while ($art = $user_articles->fetch_assoc()): ?><tr><td class="ps-3 fw-semibold"><?= htmlspecialchars(mb_strimwidth($art['title'], 0, 45, '…')) ?></td><td><span class="badge <?= $art['status'] === 'published' ? 'bg-success' : 'bg-warning text-dark' ?>"><?= htmlspecialchars($art['status']) ?></span></td><td class="text-muted small"><?= htmlspecialchars(date('M d, Y', strtotime($art['created_at']))) ?></td><td class="text-end pe-3"><a href="../article.php?id=<?= (int)$art['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">View</a></td></tr><?php endwhile; else: ?><tr><td colspan="4" class="text-center py-3 text-muted">No articles authored.</td></tr><?php endif; ?>
</tbody></table></div></div></div>
<div class="card shadow-sm border-0"><div class="card-header bg-white py-3 fw-bold">Activity: Posted Comments (<?= $user_comments->num_rows ?>)</div><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th class="ps-3">Comment</th><th>On Article</th><th>Date</th></tr></thead><tbody>
<?php if ($user_comments->num_rows): while ($c = $user_comments->fetch_assoc()): ?><tr><td class="ps-3 small text-break"><?= htmlspecialchars(mb_strimwidth($c['comment_text'], 0, 80, '…')) ?></td><td class="small fw-semibold"><a href="../article.php?id=<?= (int)$c['article_id'] ?>" target="_blank"><?= htmlspecialchars(mb_strimwidth($c['article_title'], 0, 30, '…')) ?></a></td><td class="text-muted small"><?= htmlspecialchars(date('M d, Y', strtotime($c['created_at']))) ?></td></tr><?php endwhile; else: ?><tr><td colspan="3" class="text-center py-3 text-muted">No comments submitted.</td></tr><?php endif; ?>
</tbody></table></div></div></div></div></div>
<?php require_once 'includes/admin_footer.php'; ?>
