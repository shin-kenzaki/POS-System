<footer class="site-footer">
                <div class="footer-content">
                    <div class="copyright">
                        &copy; <?php echo date('Y'); ?> Kenzaki Systems. All rights reserved.
                    </div>
                    <div class="footer-links">
                        <a href="about.php">About</a>
                        <a href="privacy.php">Privacy Policy</a>
                        <a href="terms.php">Terms of Use</a>
                        <a href="contact.php">Contact</a>
                    </div>
                </div>
            </footer>
        </main>
    </div>

    <script src="assets/js/ui-enhancer.js"></script>
    <?php if (basename($_SERVER['PHP_SELF']) === 'dashboard.php'): ?>
    <script src="assets/js/dashboard.js"></script>
    <?php endif; ?>
    
    <!-- Fallback for Chart.js -->
    <script>
    if (typeof Chart !== 'undefined' && typeof Chart === 'function') {
        console.log('Chart.js loaded successfully');
    } else if (document.querySelector('canvas')) {
        console.log('Loading local Chart.js fallback');
        document.write('<script src="assets/js/chart.min.js"><\/script>');
    }
    </script>
</body>
</html>
