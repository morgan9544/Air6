<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/functions.php';

// After the other require_once statements
if (!function_exists('time_remaining')) {
    function time_remaining($datetime) {
        $now = new DateTime();
        $future = new DateTime($datetime);
        
        if ($future < $now) {
            return "Completed";
        }
        
        $interval = $now->diff($future);
        
        $parts = [];
        if ($interval->y > 0) $parts[] = $interval->y . " year" . ($interval->y > 1 ? "s" : "");
        if ($interval->m > 0) $parts[] = $interval->m . " month" . ($interval->m > 1 ? "s" : "");
        if ($interval->d > 0) $parts[] = $interval->d . " day" . ($interval->d > 1 ? "s" : "");
        if ($interval->h > 0) $parts[] = $interval->h . " hour" . ($interval->h > 1 ? "s" : "");
        if ($interval->i > 0) $parts[] = $interval->i . " minute" . ($interval->i > 1 ? "s" : "");
        if ($interval->s > 0) $parts[] = $interval->s . " second" . ($interval->s > 1 ? "s" : "");
        
        if (empty($parts)) {
            return "Less than a minute";
        }
        
        return implode(", ", $parts);
    }
}
// Authentication check
if (!is_logged_in()) {
    redirect('../login.php');
}

// Get election settings
$settings = $pdo->query("SELECT setting_key, setting_value FROM election_settings")->fetchAll(PDO::FETCH_KEY_PAIR);

// Get user information
$user_id = $_SESSION['user_id'];
$user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user->execute([$user_id]);
$user = $user->fetch();

// Get voting status
$has_voted = $user['has_voted'];
$current_time = time();
$voting_start = strtotime($settings['voting_start']);
$voting_end = strtotime($settings['voting_end']);
$election_active = ($current_time >= $voting_start && $current_time <= $voting_end && $settings['election_status'] === 'active');
$registration_open = $settings['registration_open'];

// Check if results are visible
$results_visible = false;
if (is_admin()) {
    $results_visible = true;
} else {
    switch ($settings['results_visibility']) {
        case 'always':
            $results_visible = true;
            break;
        case 'after_voting':
            $results_visible = $has_voted;
            break;
        case 'after_election':
            $results_visible = ($current_time > $voting_end);
            break;
    }
}

// Get announcements (with error handling)
try {
    $announcements = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 3")->fetchAll();
} catch (PDOException $e) {
    $announcements = []; // Default to empty array if table doesn't exist
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/images/favicon.png">

    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        .dashboard-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1.5rem;
        }
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .status-badge.pending {
            background: #ffc107;
            color: #212529;
        }
        .status-badge.active {
            background: #28a745;
            color: white;
        }
        .status-badge.completed {
            background: #6c757d;
            color: white;
        }
        .announcement-item {
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        .announcement-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        .countdown {
            font-size: 1.2rem;
            font-weight: bold;
            margin: 0.5rem 0;
        }
        .btn-group-vertical {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .alert {
            padding: 0.75rem 1.25rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
        }
        .alert-secondary {
            background-color: #e2e3e5;
            color: #383d41;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="container">
        <h1>Welcome, <?= htmlspecialchars($user['name']) ?></h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <h2>Election Status</h2>
                <p><strong>Current Status:</strong> 
                    <span class="status-badge <?= $settings['election_status'] ?>">
                        <?= ucfirst($settings['election_status']) ?>
                    </span>
                </p>
                
                <?php if ($election_active): ?>
                    <p><strong>Voting Period:</strong> Active</p>
                    <p><strong>Time Remaining:</strong></p>
                    <div class="countdown" id="voting-countdown">
                        <?= time_remaining($settings['voting_end']) ?>
                    </div>
                <?php elseif ($current_time < $voting_start): ?>
                    <p><strong>Voting Starts In:</strong></p>
                    <div class="countdown" id="voting-countdown">
                        <?= time_remaining($settings['voting_start']) ?>
                    </div>
                <?php else: ?>
                    <p><strong>Voting Period:</strong> Ended</p>
                <?php endif; ?>
                
                <p><strong>Voter Registration:</strong> 
                    <?= $registration_open ? 'Open' : 'Closed' ?>
                </p>
            </div>
            
            <div class="dashboard-card">
                <h2>Your Voting Status</h2>
                <?php if ($has_voted): ?>
                    <div class="alert alert-success">
                        <p>You have already voted in this election.</p>
                        <p>Thank you for participating!</p>
                    </div>
                <?php elseif ($election_active): ?>
                    <div class="alert alert-info">
                        <p>Voting is currently active.</p>
                        <p>Please cast your vote before the deadline.</p>
                    </div>
                    <a href="vote.php" class="btn btn-primary">Vote Now</a>
                <?php elseif ($current_time < $voting_start): ?>
                    <div class="alert alert-warning">
                        <p>Voting has not started yet.</p>
                        <p>Please check back when voting begins.</p>
                    </div>
                <?php else: ?>
                    <div class="alert alert-secondary">
                        <p>Voting period has ended.</p>
                        <?php if (!$has_voted): ?>
                            <p>You did not cast your vote in this election.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($results_visible): ?>
                    <div style="margin-top: 1.5rem;">
                        <a href="results.php" class="btn btn-secondary">View Results</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (is_admin()): ?>
            <div class="dashboard-card">
                <h2>Admin Quick Actions</h2>
                <div class="btn-group-vertical">
                    <a href="election_settings.php" class="btn">Election Settings</a>
                    <a href="manage_candidates.php" class="btn">Manage Candidates</a>
                    <a href="manage_positions.php" class="btn">Manage Positions</a>
                    <a href="view_results.php" class="btn">Detailed Results</a>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($announcements) || is_admin()): ?>
        <div class="dashboard-card">
            <h2>Latest Announcements</h2>
            <?php if (empty($announcements)): ?>
                <p>No announcements available.</p>
                <?php if (is_admin()): ?>
                    <a href="manage_announcements.php" class="btn">Create Announcement</a>
                <?php endif; ?>
            <?php else: ?>
                <?php foreach ($announcements as $announcement): ?>
                    <div class="announcement-item">
                        <h3><?= htmlspecialchars($announcement['title']) ?></h3>
                        <p><small><?= date('M j, Y g:i a', strtotime($announcement['created_at'])) ?></small></p>
                        <p><?= nl2br(htmlspecialchars($announcement['content'])) ?></p>
                    </div>
                <?php endforeach; ?>
                <a href="announcements.php" class="btn">View All Announcements</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Real-time countdown
        function updateCountdown(endTime, elementId) {
            const end = new Date(endTime);
            const now = new Date();
            
            if (end <= now) {
                document.getElementById(elementId).textContent = "Completed";
                return;
            }
            
            const diff = end - now;
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);
            
            let parts = [];
            if (days > 0) parts.push(`${days} day${days > 1 ? 's' : ''}`);
            if (hours > 0) parts.push(`${hours} hour${hours > 1 ? 's' : ''}`);
            if (minutes > 0) parts.push(`${minutes} minute${minutes > 1 ? 's' : ''}`);
            parts.push(`${seconds} second${seconds > 1 ? 's' : ''}`);
            
            document.getElementById(elementId).textContent = parts.join(', ');
        }
        
        // Initialize countdowns
        document.addEventListener('DOMContentLoaded', function() {
            const votingEnd = "<?= $settings['voting_end'] ?>";
            const votingStart = "<?= $settings['voting_start'] ?>";
            
            if (document.getElementById('voting-countdown')) {
                const targetTime = new Date() < new Date(votingStart) ? votingStart : votingEnd;
                updateCountdown(targetTime, 'voting-countdown');
                setInterval(() => updateCountdown(targetTime, 'voting-countdown'), 1000);
            }
        });
    </script>
</body>
</html>