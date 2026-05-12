<?php 
// Absolute path to prevent include errors
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(SITE_NAME) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<!-- Favicon -->
<link rel="icon" type="image/png" href="/assets/images/favicon.png">

</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <main class="container">
        <section class="hero">
            <h1>Welcome to <?= htmlspecialchars(SITE_NAME) ?></h1>
            <p>Cast your vote securely from anywhere at any time</p>
            <div class="cta-buttons">
                <a href="<?= BASE_URL ?>src/register.php" class="btn">Register Now</a>
                <a href="<?= BASE_URL ?>src/login.php" class="btn">Login</a>
            </div>
        </section>
        
        <section class="features">
            <div class="feature-card">
                <h3>Secure Voting</h3>
                <p>Our system uses advanced encryption to protect your vote.</p>
            </div>
            <div class="feature-card">
                <h3>Transparent Results</h3>
                <p>View real-time results with complete transparency.</p>
            </div>
            <div class="feature-card">
                <h3>Easy to Use</h3>
                <p>Simple interface that works on any device.</p>
            </div>
        </section>
    </main>
    
    <?php include __DIR__ . '/includes/footer.php'; ?>
    
    <script src="<?= BASE_URL ?>assets/js/script.js"></script>
</body>
</html>