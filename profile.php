<?php
// profile.php
require_once 'includes/auth_check.php';
require_once 'config/db.php';

$user_id = $_SESSION['user_id'];
$message = "";
$error   = "";

// Fetch current user details
$stmt = $conn->prepare("SELECT username, email, bio, profile_pic, role, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$upload_dir = 'uploads/profiles/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// 1. Handle Profile Picture Removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_pic'])) {
    if (!empty($user['profile_pic']) && $user['profile_pic'] !== 'default_avatar.png') {
        $old_file = $upload_dir . $user['profile_pic'];
        if (file_exists($old_file)) {
            unlink($old_file);
        }
    }

    $default_avatar = 'default_avatar.png';
    $del_stmt = $conn->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
    $del_stmt->bind_param("si", $default_avatar, $user_id);
    if ($del_stmt->execute()) {
        $user['profile_pic'] = $default_avatar;
        $message = "Profile picture removed successfully.";
    } else {
        $error = "Failed to remove profile picture.";
    }
    $del_stmt->close();
}

// 2. Handle Profile Info & Picture Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $bio = trim($_POST['bio'] ?? '');
    $new_pic_name = $user['profile_pic'];

    // Check if a new profile picture file was uploaded
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
        $file_tmp     = $_FILES['profile_pic']['tmp_name'];
        $file_name    = $_FILES['profile_pic']['name'];
        $file_size    = $_FILES['profile_pic']['size'];
        $ext          = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed_exts)) {
            $error = "Only JPG, PNG, and WebP images are allowed.";
        } elseif ($file_size > 2 * 1024 * 1024) {
            $error = "Profile picture size must be under 2 MB.";
        } else {
            // Generate unique filename to avoid overwrites
            $new_pic_name = 'user_' . $user_id . '_' . time() . '.' . $ext;
            $destination  = $upload_dir . $new_pic_name;

            if (move_uploaded_file($file_tmp, $destination)) {
                // Delete previous custom avatar if one existed
                if (!empty($user['profile_pic']) && $user['profile_pic'] !== 'default_avatar.png') {
                    $previous_file = $upload_dir . $user['profile_pic'];
                    if (file_exists($previous_file)) {
                        unlink($previous_file);
                    }
                }
            } else {
                $error = "Failed to upload image. Please verify folder permissions.";
            }
        }
    }

    // Save bio and profile pic
    if (empty($error)) {
        $up_stmt = $conn->prepare("UPDATE users SET bio = ?, profile_pic = ? WHERE id = ?");
        $up_stmt->bind_param("ssi", $bio, $new_pic_name, $user_id);
        if ($up_stmt->execute()) {
            $user['bio'] = $bio;
            $user['profile_pic'] = $new_pic_name;
            $message = "Profile updated successfully!";
        } else {
            $error = "Failed to update profile details.";
        }
        $up_stmt->close();
    }
}

require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow-sm border-0 my-3">
            <div class="card-header bg-white py-3">
                <h4 class="fw-bold mb-0">My Profile</h4>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-success py-2 small"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <!-- Avatar Display & Remove Button -->
                <div class="text-center mb-4">
                    <?php 
                        $has_custom_pic = (!empty($user['profile_pic']) && $user['profile_pic'] !== 'default_avatar.png' && file_exists('uploads/profiles/' . $user['profile_pic']));
                        $avatar_path = $has_custom_pic ? 'uploads/profiles/' . htmlspecialchars($user['profile_pic']) : 'https://cdn-icons-png.flflaticon.com/512/149/149071.png';
                    ?>
                    <img src="<?= $avatar_path ?>" alt="Avatar" class="rounded-circle shadow-sm mb-3 border" style="width: 120px; height: 120px; object-fit: cover;">
                    
                    <?php if ($has_custom_pic): ?>
                        <form action="profile.php" method="POST" onsubmit="return confirm('Are you sure you want to remove your profile picture?');">
                            <button type="submit" name="remove_pic" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-trash"></i> Remove Picture
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <form action="profile.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">Username</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-semibold">Email Address</label>
                        <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                    </div>

                    <div class="mb-3">
                        <label for="profile_pic" class="form-label fw-semibold">Change Profile Picture</label>
                        <input type="file" name="profile_pic" id="profile_pic" class="form-control" accept="image/jpeg,image/png,image/webp">
                        <small class="text-muted">Accepted formats: JPG, PNG, WebP (Max 2 MB).</small>
                    </div>

                    <div class="mb-3">
                        <label for="bio" class="form-label fw-semibold">Biography</label>
                        <textarea name="bio" id="bio" rows="4" class="form-control" placeholder="Write a short bio about yourself..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <span class="text-muted small">Member since: <?= date('M d, Y', strtotime($user['created_at'])) ?></span>
                        <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

