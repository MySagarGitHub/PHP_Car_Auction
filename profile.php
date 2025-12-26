<?php
session_start();

// Include functions.php for common functions
include_once 'functions.php';

try {
    $pdo = new PDO("mysql:host=db;dbname=assignment1", "user", "password");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

// Get user profile information
$stmt = $pdo->prepare("SELECT * FROM user WHERE id = :id");
$stmt->bindParam(':id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Get user's auctions
$stmt = $pdo->prepare("
    SELECT a.id, a.title, a.image, a.endDate, 
    COALESCE((SELECT MAX(b.amount) FROM bid b WHERE b.auctionId = a.id), 0) AS highestBid,
    (SELECT COUNT(*) FROM bid WHERE auctionId = a.id) AS bidCount
    FROM auction a 
    WHERE a.userId = :userId 
    ORDER BY a.id DESC
");
$stmt->bindParam(':userId', $user_id);
$stmt->execute();
$user_auctions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's bids
$stmt = $pdo->prepare("
    SELECT a.id, a.title, a.image, a.endDate, 
    MAX(b.amount) AS bidAmount,
    (SELECT MAX(b2.amount) FROM bid b2 WHERE b2.auctionId = a.id) AS highestBid,
    MAX(b.id) AS lastBidId
    FROM bid b
    JOIN auction a ON b.auctionId = a.id
    WHERE b.userId = :userId
    GROUP BY a.id, a.title, a.image, a.endDate
    ORDER BY lastBidId DESC
");
$stmt->bindParam(':userId', $user_id);
$stmt->execute();
$user_bids = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $city = $_POST['city'] ?? '';
    $country = $_POST['country'] ?? '';
    $zipcode = $_POST['zipcode'] ?? '';

    if (empty($name) || empty($email)) {
        $error_msg = "Name and email are required";
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE user SET 
                name = :name, 
                email = :email, 
                phone = :phone, 
                address = :address,
                city = :city,
                country = :country,
                zipcode = :zipcode
                WHERE id = :id
            ");
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':address', $address);
            $stmt->bindParam(':city', $city);
            $stmt->bindParam(':country', $country);
            $stmt->bindParam(':zipcode', $zipcode);
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();
            
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $success_msg = "Profile updated successfully!";
            
            // Refresh user data
            $user['name'] = $name;
            $user['email'] = $email;
            $user['phone'] = $phone;
            $user['address'] = $address;
            $user['city'] = $city;
            $user['country'] = $country;
            $user['zipcode'] = $zipcode;
        } catch (PDOException $e) {
            $error_msg = "Error updating profile: " . $e->getMessage();
        }
    }
}

include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - CarBuy Auctions</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ===================== PROFILE PAGE STYLES ===================== */
        
        main {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 30px;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 30px;
            background: linear-gradient(135deg, rgba(230, 57, 70, 0.08), rgba(255, 209, 102, 0.08));
            padding: 40px;
            border-radius: 14px;
            margin-bottom: 40px;
            animation: slideDown 0.6s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgb(230, 57, 70), rgb(255, 107, 107));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            box-shadow: 0 8px 20px rgba(230, 57, 70, 0.3);
        }

        .profile-info h1 {
            font-size: 2rem;
            color: rgb(33, 37, 41);
            margin-bottom: 8px;
        }

        .profile-info p {
            color: rgb(108, 117, 125);
            font-size: 0.95rem;
            margin-bottom: 5px;
        }

        .profile-info .joined {
            color: rgb(230, 57, 70);
            font-weight: 600;
            margin-top: 10px;
        }

        .profile-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }

        .section-card {
            background: white;
            border-radius: 14px;
            padding: 30px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            border: 1px solid rgb(233, 236, 239);
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .section-title {
            font-size: 1.3rem;
            color: rgb(33, 37, 41);
            margin-bottom: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: rgb(230, 57, 70);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: rgb(33, 37, 41);
            font-size: 0.9rem;
        }

        .form-input {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid rgb(233, 236, 239);
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background-color: rgba(248, 249, 250, 0.5);
        }

        .form-input:focus {
            outline: none;
            border-color: rgb(230, 57, 70);
            background-color: white;
            box-shadow: 0 0 0 3px rgba(230, 57, 70, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .btn-update {
            background: linear-gradient(135deg, rgb(230, 57, 70), rgb(255, 107, 107));
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-update:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(230, 57, 70, 0.3);
        }

        .message {
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .success {
            background-color: rgba(76, 175, 80, 0.1);
            color: rgb(27, 94, 32);
            border: 1px solid rgba(76, 175, 80, 0.3);
        }

        .error {
            background-color: rgba(230, 57, 70, 0.1);
            color: rgb(180, 0, 0);
            border: 1px solid rgba(230, 57, 70, 0.3);
        }

        /* STATS GRID */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-box {
            background: linear-gradient(135deg, rgba(230, 57, 70, 0.1), rgba(255, 209, 102, 0.1));
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border: 1px solid rgba(230, 57, 70, 0.2);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 900;
            color: rgb(230, 57, 70);
        }

        .stat-label {
            color: rgb(108, 117, 125);
            font-size: 0.85rem;
            margin-top: 5px;
        }

        /* AUCTIONS & BIDS SECTION */
        .auctions-section {
            grid-column: 1 / -1;
        }

        .auction-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }

        .auction-item {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border: 1px solid rgb(233, 236, 239);
            transition: all 0.3s ease;
        }

        .auction-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(230, 57, 70, 0.2);
        }

        .auction-image {
            width: 100%;
            height: 140px;
            background: linear-gradient(135deg, rgb(248, 249, 250), rgb(240, 244, 248));
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .auction-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 10px;
        }

        .auction-details {
            padding: 15px;
        }

        .auction-title {
            font-weight: 700;
            color: rgb(33, 37, 41);
            margin-bottom: 8px;
            font-size: 0.95rem;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .auction-meta {
            font-size: 0.8rem;
            color: rgb(108, 117, 125);
            margin-bottom: 10px;
        }

        .auction-price {
            font-size: 1.2rem;
            font-weight: 900;
            color: rgb(230, 57, 70);
        }

        .auction-btn {
            display: block;
            width: 100%;
            text-align: center;
            padding: 8px;
            background: linear-gradient(135deg, rgb(230, 57, 70), rgb(255, 107, 107));
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.85rem;
            margin-top: 10px;
            transition: all 0.3s ease;
        }

        .auction-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(230, 57, 70, 0.3);
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: rgb(108, 117, 125);
        }

        .empty-state i {
            font-size: 3rem;
            color: rgb(230, 57, 70);
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .empty-state p {
            margin-bottom: 10px;
        }

        .empty-state a {
            color: rgb(230, 57, 70);
            text-decoration: none;
            font-weight: 700;
        }

        /* RESPONSIVE */
        @media (max-width: 1024px) {
            .profile-content {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            main {
                padding: 20px;
            }

            .profile-header {
                flex-direction: column;
                text-align: center;
                padding: 30px 20px;
            }

            .profile-avatar {
                width: 100px;
                height: 100px;
                font-size: 2.5rem;
            }

            .profile-info h1 {
                font-size: 1.5rem;
            }

            .auction-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .section-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<main>
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="profile-avatar">
            <i class="fas fa-user"></i>
        </div>
        <div class="profile-info">
            <h1><?php echo htmlspecialchars($user['name'] ?? 'User'); ?></h1>
            <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
            <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($user['phone'] ?? 'Not provided'); ?></p>
            <p class="joined"><i class="fas fa-calendar-alt"></i> Member since 2024</p>
        </div>
    </div>

    <!-- Messages -->
    <?php
    if (!empty($success_msg)) {
        echo '<div class="message success"><i class="fas fa-check-circle"></i> ' . htmlspecialchars($success_msg) . '</div>';
    }
    if (!empty($error_msg)) {
        echo '<div class="message error"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($error_msg) . '</div>';
    }
    ?>

    <!-- Profile Content -->
    <div class="profile-content">
        <!-- Edit Profile Section -->
        <div class="section-card">
            <h2 class="section-title"><i class="fas fa-edit"></i> Edit Profile</h2>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-input" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-input" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" name="phone" class="form-input" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Address</label>
                    <input type="text" name="address" class="form-input" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-input" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Zip Code</label>
                        <input type="text" name="zipcode" class="form-input" value="<?php echo htmlspecialchars($user['zipcode'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Country</label>
                    <input type="text" name="country" class="form-input" value="<?php echo htmlspecialchars($user['country'] ?? ''); ?>">
                </div>

                <button type="submit" name="update_profile" class="btn-update">
                    <i class="fas fa-save"></i> Update Profile
                </button>
            </form>
        </div>

        <!-- Account Stats -->
        <div class="section-card">
            <h2 class="section-title"><i class="fas fa-chart-bar"></i> Account Statistics</h2>
            
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-value"><?php echo count($user_auctions); ?></div>
                    <div class="stat-label">Auctions Listed</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo count($user_bids); ?></div>
                    <div class="stat-label">Active Bids</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value">
                        <?php 
                        $soldCount = 0;
                        foreach ($user_auctions as $auction) {
                            $endDate = new DateTime($auction['endDate']);
                            if ($endDate < new DateTime()) {
                                $soldCount++;
                            }
                        }
                        echo $soldCount;
                        ?>
                    </div>
                    <div class="stat-label">Sold Items</div>
                </div>
            </div>

            <a href="addAuction.php" style="display: block; text-align: center; margin-top: 20px; padding: 12px; background: linear-gradient(135deg, rgb(230, 57, 70), rgb(255, 107, 107)); color: white; text-decoration: none; border-radius: 8px; font-weight: 700;">
                <i class="fas fa-plus"></i> Create New Auction
            </a>
        </div>
    </div>

    <!-- My Auctions Section -->
    <div class="section-card auctions-section">
        <h2 class="section-title"><i class="fas fa-gavel"></i> My Auctions</h2>
        
        <?php if (empty($user_auctions)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>You haven't listed any auctions yet</p>
                <a href="addAuction.php">Create your first auction</a>
            </div>
        <?php else: ?>
            <div class="auction-grid">
                <?php foreach ($user_auctions as $auction): 
                    $endDate = new DateTime($auction['endDate']);
                    $now = new DateTime();
                    $isEnded = $endDate < $now;
                ?>
                    <div class="auction-item">
                        <div class="auction-image">
                            <img src="<?php echo htmlspecialchars($auction['image'] ?? 'car.png'); ?>" 
                                 alt="<?php echo htmlspecialchars($auction['title']); ?>"
                                 onerror="this.src='car.png'">
                        </div>
                        <div class="auction-details">
                            <div class="auction-title"><?php echo htmlspecialchars($auction['title']); ?></div>
                            <div class="auction-meta">
                                <i class="fas fa-gavel"></i> <?php echo $auction['bidCount']; ?> bids
                            </div>
                            <!-- Using simple_price() function from functions.php -->
                            <div class="auction-price"><?php echo simple_price($auction['highestBid']); ?></div>
                            <a href="auction.php?id=<?php echo $auction['id']; ?>" class="auction-btn">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- My Bids Section -->
    <div class="section-card auctions-section">
        <h2 class="section-title"><i class="fas fa-heart"></i> My Bids</h2>
        
        <?php if (empty($user_bids)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>You haven't placed any bids yet</p>
                <a href="index.php">Browse auctions</a>
            </div>
        <?php else: ?>
            <div class="auction-grid">
                <?php foreach ($user_bids as $auction): ?>
                    <div class="auction-item">
                        <div class="auction-image">
                            <img src="<?php echo htmlspecialchars($auction['image'] ?? 'car.png'); ?>" 
                                 alt="<?php echo htmlspecialchars($auction['title']); ?>"
                                 onerror="this.src='car.png'">
                        </div>
                        <div class="auction-details">
                            <div class="auction-title"><?php echo htmlspecialchars($auction['title']); ?></div>
                            <!-- Using simple_price() function from functions.php -->
                            <div class="auction-meta">
                                Your bid: <strong><?php echo simple_price($auction['bidAmount']); ?></strong>
                            </div>
                            <!-- Using simple_price() function from functions.php -->
                            <div class="auction-price">Highest: <?php echo simple_price($auction['highestBid']); ?></div>
                            <a href="auction.php?id=<?php echo $auction['id']; ?>" class="auction-btn">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'footer.php'; ?>

</body>
</html>