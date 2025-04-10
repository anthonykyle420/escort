    </main>
    
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Escort Directory</h5>
                    <p>Your one-stop solution for finding the best escort services.</p>
                </div>
                <div class="col-md-3">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Home</a></li>
                        <li><a href="listings.php" class="text-white">Escorts</a></li>
                        <li><a href="contact.php" class="text-white">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Legal</h5>
                    <ul class="list-unstyled">
                        <li><a href="terms.php" class="text-white">Terms of Service</a></li>
                        <li><a href="privacy.php" class="text-white">Privacy Policy</a></li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Escort Directory. All rights reserved. | 18+ Only</p>
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@8/swiper-bundle.min.js"></script>
    <script>
        // Fix for footer display issues
        document.addEventListener('DOMContentLoaded', function() {
            // Ensure the footer is visible
            const footer = document.querySelector('footer');
            if (footer) {
                footer.style.display = 'block';
                footer.style.position = 'relative';
                footer.style.zIndex = '10';
            }
            
            // Ensure the main content has proper spacing
            const main = document.querySelector('main.site-main');
            if (main) {
                main.style.minHeight = 'calc(100vh - 350px)';
                main.style.paddingBottom = '50px';
            }
        });
    </script>
</body>
</html> 