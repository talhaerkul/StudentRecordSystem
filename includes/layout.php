<?php
require_once __DIR__ . '/../config/config.php';

// Determine base URL for assets
$script_name = $_SERVER['SCRIPT_NAME'];
$base_dir = dirname(dirname($script_name));
$base_url = ($base_dir == '/' ? '' : $base_dir);
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    
    <!-- CSS Kütüphaneleri -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" />
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/style.css" />
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/tailwind-custom.css" />
    
    <!-- Tailwind Yapılandırması -->
    <script>
    tailwind.config = {
        important: true,
        corePlugins: {
            preflight: false,
        }
    }
    </script>
    
    <!-- Genel CSS Stilleri -->
    <style>
    body {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }
    
    main {
        flex: 1;
    }
    
    /* Navbar genel stilleri */
    .navbar-dark .navbar-nav .nav-link {
        color: white !important;
    }
    
    .navbar-dark .navbar-brand {
        color: white !important;
    }
    
    /* Özel stillendirmeler - alt sayfalar için ek stiller burada */
    <?php if(isset($page_specific_css)): ?>
        <?php echo $page_specific_css; ?>
    <?php endif; ?>
    </style>
    
    <!-- Özel sayfa CSS'i eklemek için -->
    <?php if(isset($additional_css)): ?>
        <?php echo $additional_css; ?>
    <?php endif; ?>
</head>

<body>
    <!-- İçerik başlangıcı -->
    <div class="d-flex flex-column min-vh-100">
        <!-- Header içeriği ayrı dosyada -->
        <?php include __DIR__ . '/header.php'; ?>
        
        <!-- Ana içerik -->
        <main class="container py-4">
            <!-- Uyarı/bildirimler -->
            <?php if(isset($_SESSION['alert'])): ?>
            <div class="alert alert-<?php echo $_SESSION['alert_type']; ?> alert-dismissible fade show shadow-sm rounded-md">
                <?php echo $_SESSION['alert']; ?>
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
            <?php
                unset($_SESSION['alert']);
                unset($_SESSION['alert_type']);
                ?>
            <?php endif; ?>
            
            <!-- Ana içerik buraya gelecek -->
            <?php if(isset($content)): ?>
                <?php echo $content; ?>
            <?php endif; ?>
        </main>
        
        <!-- Footer -->
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
    </div>

    <!-- JavaScript Kütüphaneleri -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="<?php echo $base_url; ?>/assets/js/main.js"></script>
    
    <!-- Özel sayfa JS'i eklemek için -->
    <?php if(isset($additional_js)): ?>
        <?php echo $additional_js; ?>
    <?php endif; ?>
</body>

</html>
