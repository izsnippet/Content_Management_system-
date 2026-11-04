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

$has_custom_pic = (!empty($user['profile_pic']) && $user['profile_pic'] !== 'default_avatar.png' && file_exists('uploads/profiles/' . $user['profile_pic']));
$avatar_path = $has_custom_pic ? 'uploads/profiles/' . htmlspecialchars($user['profile_pic']) : 'https://cdn-icons-png.flaticon.com/512/149/149071.png';

$page_title = 'My Profile';
require_once 'includes/header.php';
?>

<div class="wk-container" style="max-width:1300px;padding-top:1.75rem;padding-bottom:3rem;">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-xl-6">

            <!-- Profile Header Card -->
            <div class="wk-card wk-card-static mb-4 animate-fade-in" style="text-align:center;padding:2rem;">
                <img src="<?= $avatar_path ?>" alt="Profile picture of <?= htmlspecialchars($user['username']) ?>"
                     class="wk-profile-avatar">
                <h4 style="font-weight:800;letter-spacing:-.02em;margin-bottom:.3rem;"><?= htmlspecialchars($user['username']) ?></h4>
                <span class="wk-profile-role">
                    <i class="bi bi-shield-check" style="font-size:.75rem;"></i>
                    <?= htmlspecialchars(ucfirst($user['role'] ?? 'author')) ?>
                </span>
                <div class="wk-profile-since mt-2 justify-content-center">
                    <i class="bi bi-calendar3"></i>
                    Member since <?= date('F Y', strtotime($user['created_at'])) ?>
                </div>

                <?php if ($has_custom_pic): ?>
                    <form action="profile.php" method="POST" style="margin-top:.85rem;"
                          onsubmit="return confirm('Remove your profile picture?');">
                        <button type="submit" name="remove_pic" class="btn btn-sm btn-outline-danger" style="font-size:.8rem;">
                            <i class="bi bi-trash me-1"></i> Remove Picture
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Edit Profile Card -->
            <div class="wk-card wk-card-static animate-slide-up">
                <div style="padding:1.1rem 1.5rem;border-bottom:1px solid var(--border);background:var(--surface-alt);">
                    <h5 style="margin:0;font-size:.95rem;font-weight:700;display:flex;align-items:center;gap:.4rem;">
                        <i class="bi bi-person-gear text-accent"></i> Edit Profile
                    </h5>
                </div>
                <div style="padding:1.75rem;">

                    <?php if (!empty($message)): ?>
                        <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($message) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form action="profile.php" method="POST" enctype="multipart/form-data">

                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                            <div class="form-text">Username cannot be changed.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                            <div class="form-text">Email cannot be changed from this page.</div>
                        </div>

                        <div class="mb-3">
                            <label for="profile_pic" class="form-label">Change Profile Picture</label>
                            <input type="file" name="profile_pic" id="profile_pic" class="form-control"
                                   accept="image/jpeg,image/png,image/webp">
                            <div class="form-text">Accepted formats: JPG, PNG, WebP — Max 2 MB.</div>
                        </div>

                        <div class="mb-4">
                            <label for="bio" class="form-label">Biography</label>
                            <textarea name="bio" id="bio" rows="4" class="form-control"
                                      placeholder="Write a short bio about yourself, your interests, or expertise…"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                            <div class="form-text">This will appear on your public profile.</div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="bi bi-check2 me-1"></i> Save Changes
                            </button>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
