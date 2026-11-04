<?php
// includes/footer.php
?>
<!-- ══════════════════════════════════════════
     FOOTER
══════════════════════════════════════════ -->
<footer class="wk-footer">
    <div class="wk-container" style="max-width:1300px;">
        <div class="wk-footer-grid">

            <!-- Brand Column -->
            <div class="wk-footer-brand">
                <a href="index.php" class="wk-brand" style="display:inline-flex;margin-bottom:.75rem;">
                    <span class="brand-icon"><i class="bi bi-journal-richtext"></i></span>
                    <span class="brand-text" style="color:#fff;">Wiki<span>CMS</span></span>
                </a>
                <p>A collaborative, open knowledge platform where anyone can explore, write, and improve articles on any topic.</p>
            </div>

            <!-- Explore Column -->
            <div>
                <h6>Explore</h6>
                <ul class="wk-footer-links">
                    <li><a href="index.php">Browse Articles</a></li>
                    <li><a href="search.php">Search</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="dashboard.php">My Dashboard</a></li>
                        <li><a href="create_article.php">Write Article</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Sign In</a></li>
                        <li><a href="register.php">Create Account</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- About Column -->
            <div>
                <h6>Platform</h6>
                <ul class="wk-footer-links">
                    <li><a href="#">About WikiCMS</a></li>
                    <li><a href="#">Guidelines</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Help Center</a></li>
                </ul>
            </div>

        </div><!-- /.wk-footer-grid -->

        <div class="wk-footer-bottom">
            <p>&copy; <?= date('Y') ?> WikiCMS. Built with PHP &amp; MySQL. All rights reserved.</p>
            <p>Open knowledge, shared freely.</p>
        </div>

    </div><!-- /.wk-container -->
</footer>

<!-- Bootstrap 5.3 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS (with cache buster) -->
<?php $js_v = file_exists(__DIR__ . '/../assets/js/script.js') ? filemtime(__DIR__ . '/../assets/js/script.js') : time(); ?>
<script src="assets/js/script.js?v=<?= $js_v ?>"></script>
</body>
</html>
