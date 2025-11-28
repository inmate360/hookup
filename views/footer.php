    </main>
    
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>📋 About Turnpage</h4>
                    <ul>
                        <li><a href="/about.php">About Us</a></li>
                        <li><a href="/how-it-works.php">How It Works</a></li>
                        <li><a href="/blog.php">Blog</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>🛡️ Safety & Legal</h4>
                    <ul>
                        <li><a href="/terms.php">Terms of Service</a></li>
                        <li><a href="/privacy.php">Privacy Policy</a></li>
                        <li><a href="/safety.php">Safety Tips</a></li>
                        <li><a href="/report-abuse.php">Report Abuse</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>💎 Features</h4>
                    <ul>
                        <li><a href="/membership.php">Premium Membership</a></li>
                        <li><a href="/featured-ads.php">Featured Ads</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>📞 Support</h4>
                    <ul>
                        <li><a href="/support.php">Help Center</a></li>
                        <li><a href="/faq.php">FAQ</a></li>
                        <li><a href="/contact.php">Contact Us</a></li>
                        <li><a href="/feedback.php">Send Feedback</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Turnpage - Local Hookup Classifieds. All rights reserved.</p>
                <p style="margin-top: 0.5rem; font-size: 0.9rem;">
                    <a href="https://facebook.com" target="_blank" style="margin: 0 0.5rem;">Facebook</a> |
                    <a href="https://twitter.com" target="_blank" style="margin: 0 0.5rem;">Twitter</a> |
                    <a href="https://instagram.com" target="_blank" style="margin: 0 0.5rem;">Instagram</a>
                </p>
            </div>
        </div>
    </footer>
    
    <script>
        <?php if(isset($_SESSION['user_id'])): ?>
        const userId = <?php echo $_SESSION['user_id']; ?>;
        <?php endif; ?>
    </script>
    <script src="/assets/js/app.js"></script>
</body>
</html>