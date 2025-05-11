<?php
/**
 * Bu dosya legacy uyumluluk için tutulmuştur.
 * Yeni geliştirmelerinizde lütfen layout.php yapısını kullanın.
 */

// İçeriği bufferdan al
$content = ob_get_clean();

// Ana layout dosyasını dahil et
require_once 'includes/layout.php';
?>

</div><!-- /.container -->
    
    <footer class="bg-gradient-to-r from-gray-900 to-gray-800 text-white py-4 mt-auto shadow-inner">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0 text-gray-300">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?></p>
                </div>
                <div class="col-md-6 text-md-right">
                    <p class="mb-0 text-gray-300">Doğukan Yiğit</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>

