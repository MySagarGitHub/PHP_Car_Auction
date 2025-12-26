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

$auctionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("
    SELECT a.id, a.title, a.description, a.endDate, a.image, a.userId AS auctionUserId, c.name AS categoryName, u.name AS userName
    FROM auction a
    JOIN category c ON a.categoryId = c.id
    JOIN user u ON a.userId = u.id
    WHERE a.id = ?
");
$stmt->execute([$auctionId]);
$auction = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$auction) {
    die("Auction not found.");
}

$bidStmt = $pdo->prepare("
    SELECT b.amount AS highestBid, u.name AS bidderName
    FROM bid b
    JOIN user u ON b.userId = u.id
    WHERE b.auctionId = ?
    AND b.amount = (
        SELECT MAX(amount)
        FROM bid
        WHERE auctionId = ?
    )
    LIMIT 1
");
$bidStmt->execute([$auctionId, $auctionId]);
$highestBid = $bidStmt->fetch(PDO::FETCH_ASSOC);


$bidHistoryStmt = $pdo->prepare("
    SELECT b.amount, u.name AS bidderName
    FROM bid b
    JOIN user u ON b.userId = u.id
    WHERE b.auctionId = ?
    ORDER BY b.amount DESC
");
$bidHistoryStmt->execute([$auctionId]);
$bidHistory = $bidHistoryStmt->fetchAll(PDO::FETCH_ASSOC);


$reviewStmt = $pdo->prepare("
    SELECT r.reviewText, r.datePosted, u.name AS reviewerName, u.email AS reviewerEmail
    FROM review r
    JOIN user u ON r.userId = u.id
    WHERE r.reviewedUserId = ?
");
$reviewStmt->execute([$auction['auctionUserId']]);
$reviews = $reviewStmt->fetchAll(PDO::FETCH_ASSOC);

function getRemainingTime($endDate) {
    $now = new DateTime();
    $end = new DateTime($endDate);
    if ($end < $now) {
        return "Auction ended";
    }
    $interval = $now->diff($end);
    return $interval->format('%d days, %h hours, %i minutes');
}

// Calculate minimum bid
$minBid = $highestBid ? $highestBid['highestBid'] + 1 : 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bid']) && isset($_SESSION['user_id'])) {
    $bidAmount = filter_input(INPUT_POST, 'bid', FILTER_VALIDATE_FLOAT);
    $userId = $_SESSION['user_id'];

    if ($bidAmount === false || $bidAmount <= 0) {
        $bidError = "Please enter a valid bid amount.";
    } elseif ($bidAmount < $minBid) {
        $bidError = "Your bid must be at least " . simple_price($minBid) . ".";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO bid (auctionId, userId, amount) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$auctionId, $userId, $bidAmount]);
            $bidSuccess = "Bid placed successfully!";
            header("Location: auction.php?id=$auctionId");
            exit;
        } catch (PDOException $e) {
            $bidError = "Error placing bid: " . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reviewText']) && isset($_SESSION['user_id'])) {
    $reviewText = $_POST['reviewText'];
    $userId = $_SESSION['user_id'];
    $reviewedUserId = $auction['auctionUserId'];

    if (empty($reviewText)) {
        $reviewError = "Review cannot be empty.";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO review (reviewText, userId, reviewedUserId, auctionId) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$reviewText, $userId, $reviewedUserId, $auctionId]);
            $reviewSuccess = "Review added successfully!";
            header("Location: auction.php?id=$auctionId");
            exit;
        } catch (PDOException $e) {
            $reviewError = "Error adding review: " . $e->getMessage();
        }
    }
}

include 'header.php';
?>

<style>
/* Auction page specific styles */
.car {
    max-width: 1200px;
    margin: 0 auto;
    padding: 30px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
}

.car img {
    width: 100%;
    height: auto;
    max-height: 500px;
    object-fit: cover;
    border-radius: 15px;
    margin-bottom: 25px;
    border: 3px solid #e8e1d5;
}

.details {
    background: #f9f5f0;
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 30px;
    border: 1px solid #e8e1d5;
}

.details h2 {
    color: #8B4513;
    margin-bottom: 15px;
    font-size: 2.2rem;
    font-weight: 800;
}

.details h3 {
    color: #666;
    margin-bottom: 15px;
    font-size: 1.2rem;
    background: #e8e1d5;
    padding: 8px 15px;
    border-radius: 20px;
    display: inline-block;
}

.details .price {
    font-size: 2.5rem;
    color: #8B4513;
    font-weight: 900;
    margin: 20px 0;
    padding: 15px 0;
    border-top: 2px solid #e8e1d5;
    border-bottom: 2px solid #e8e1d5;
}

.details time {
    display: block;
    color: #e63946;
    font-weight: bold;
    margin-bottom: 25px;
    font-size: 1.2rem;
    padding: 10px;
    background: #fff5f5;
    border-radius: 8px;
    border-left: 4px solid #e63946;
}

/* LARGER BID FORM STYLES */
.bid-form {
    background: white;
    padding: 25px;
    border-radius: 12px;
    border: 2px solid #8B4513;
    margin-top: 25px;
}

.bid-form label {
    display: block;
    margin-bottom: 12px;
    font-weight: bold;
    font-size: 1.3rem;
    color: #333;
}

.bid-form-container {
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
}

.bid-form input[type="text"],
.bid-form input[type="number"] {
    flex: 1;
    min-width: 300px;
    padding: 18px 20px;
    border: 3px solid #8B4513;
    border-radius: 10px;
    font-size: 1.4rem;
    font-weight: bold;
    height: 60px;
    background-color: white;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.bid-form input[type="text"]:focus,
.bid-form input[type="number"]:focus {
    outline: none;
    border-color: #A0522D;
    box-shadow: 0 0 0 4px rgba(139, 69, 19, 0.2);
}

.bid-form input[type="submit"] {
    padding: 18px 35px;
    background: linear-gradient(to bottom, #28a745, #218838);
    color: white;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-weight: bold;
    font-size: 1.3rem;
    height: 60px;
    min-width: 180px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.bid-form input[type="submit"]:hover {
    background: linear-gradient(to bottom, #218838, #1e7e34);
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.3);
}

.bid-form input[type="submit"]:active {
    transform: translateY(0);
}

.bid-minimum {
    font-size: 1rem;
    color: #666;
    margin-top: 10px;
    padding: 8px 12px;
    background: #f8f9fa;
    border-radius: 6px;
    display: inline-block;
    border-left: 3px solid #28a745;
}

/* MESSAGE STYLES */
.bid-message {
    padding: 15px;
    border-radius: 8px;
    margin-top: 15px;
    font-weight: 600;
}

.bid-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.bid-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.description {
    margin: 40px 0;
    line-height: 1.8;
    color: #333;
    font-size: 1.1rem;
    padding: 25px;
    background: #f9f5f0;
    border-radius: 12px;
}

.description p {
    margin-bottom: 0;
}

.bid-history, .reviews {
    margin: 40px 0;
    padding: 30px;
    background: #f8f9fa;
    border-radius: 15px;
    border: 1px solid #e8e1d5;
}

.bid-history h3, .reviews h3 {
    color: #8B4513;
    margin-bottom: 25px;
    font-size: 1.6rem;
    display: flex;
    align-items: center;
    gap: 10px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e8e1d5;
}

.bid-history ul {
    padding-left: 0;
    list-style: none;
}

.bid-history li {
    margin-bottom: 12px;
    padding: 12px 20px;
    background: white;
    border-radius: 8px;
    border-left: 4px solid #2a9d8f;
    font-size: 1.1rem;
    color: #2a9d8f;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.reviews ul {
    padding-left: 0;
    list-style: none;
}

.reviews li {
    margin-bottom: 25px;
    padding-bottom: 25px;
    border-bottom: 1px solid #e8e1d5;
    background: white;
    padding: 20px;
    border-radius: 10px;
}

.reviews textarea {
    width: 100%;
    padding: 20px;
    border: 2px solid #8B4513;
    border-radius: 10px;
    margin-bottom: 20px;
    min-height: 150px;
    font-family: inherit;
    font-size: 1rem;
    line-height: 1.6;
    resize: vertical;
}

.reviews input[type="submit"] {
    padding: 15px 35px;
    background: linear-gradient(to bottom, #8B4513, #A0522D);
    color: white;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-weight: bold;
    font-size: 1.2rem;
    transition: all 0.3s ease;
}

.reviews input[type="submit"]:hover {
    background: linear-gradient(to bottom, #A0522D, #8B4513);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(139, 69, 19, 0.3);
}

.car a {
    color: #8B4513;
    text-decoration: none;
    font-weight: bold;
    transition: all 0.3s ease;
}

.car a:hover {
    color: #A0522D;
    text-decoration: underline;
}

/* EDIT AUCTION BUTTON */
.edit-auction {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 25px;
    background: linear-gradient(to bottom, #007bff, #0056b3);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    margin-top: 20px;
    transition: all 0.3s ease;
}

.edit-auction:hover {
    background: linear-gradient(to bottom, #0056b3, #004085);
    transform: translateY(-2px);
    text-decoration: none;
}

/* BACK BUTTON */
.back-link {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 25px;
    background: #6c757d;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    margin-top: 30px;
    transition: all 0.3s ease;
}

.back-link:hover {
    background: #5a6268;
    transform: translateY(-2px);
    text-decoration: none;
}

/* RESPONSIVE DESIGN */
@media (max-width: 768px) {
    .car {
        padding: 20px;
    }
    
    .bid-form-container {
        flex-direction: column;
    }
    
    .bid-form input[type="text"],
    .bid-form input[type="number"] {
        min-width: 100%;
        width: 100%;
    }
    
    .bid-form input[type="submit"] {
        width: 100%;
    }
    
    .details h2 {
        font-size: 1.8rem;
    }
    
    .details .price {
        font-size: 2rem;
    }
}

@media (max-width: 480px) {
    .car {
        padding: 15px;
    }
    
    .details, .description, .bid-history, .reviews {
        padding: 20px;
    }
    
    .bid-form input[type="text"],
    .bid-form input[type="number"] {
        padding: 15px;
        font-size: 1.2rem;
        height: 55px;
    }
    
    .bid-form input[type="submit"] {
        padding: 15px;
        font-size: 1.1rem;
        height: 55px;
    }
}
</style>

<main>
    <article class="car">
        <img src="<?php echo !empty($auction['image']) ? htmlspecialchars($auction['image']) : 'car.png'; ?>" alt="<?php echo htmlspecialchars($auction['title']); ?>" onerror="this.src='car.png'">
        
        <section class="details">
            <h2><?php echo htmlspecialchars($auction['title']); ?></h2>
            <h3><?php echo htmlspecialchars($auction['categoryName']); ?></h3>
            <p>Auction created by <strong><?php echo htmlspecialchars($auction['userName']); ?></strong></p>
            
            <div class="price">Current bid: <?php echo $highestBid ? simple_price($highestBid['highestBid']) : simple_price(0); ?></div>
            
            <time>⏰ Time left: <?php echo getRemainingTime($auction['endDate']); ?></time>

            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="bid-form">
                    <label for="bidAmount">Place Your Bid:</label>
                    <form action="" method="POST" class="bid-form-container">
                        <input type="number" 
                               name="bid" 
                               id="bidAmount"
                               placeholder="Enter amount (e.g., 10000)" 
                               min="<?php echo $minBid; ?>"
                               step="0.01"
                               required
                               value="<?php echo isset($bidAmount) ? $bidAmount : $minBid; ?>">
                        <input type="submit" value="🚀 Place Bid">
                    </form>
                    <div class="bid-minimum">
                        <i class="fas fa-info-circle"></i> Minimum bid: <?php echo simple_price($minBid); ?>
                    </div>
                    
                    <?php if (isset($bidSuccess)): ?>
                        <div class="bid-message bid-success">
                            <i class="fas fa-check-circle"></i> <?php echo $bidSuccess; ?>
                        </div>
                    <?php elseif (isset($bidError)): ?>
                        <div class="bid-message bid-error">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo $bidError; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="bid-form" style="background: #e9f7ef; border-color: #28a745;">
                    <p style="font-size: 1.2rem; margin: 0;">
                        <i class="fas fa-sign-in-alt"></i> <a href="login.php" style="color: #28a745; font-weight: bold;">Log in</a> to place a bid.
                    </p>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $auction['auctionUserId']): ?>
                <a href="editAuction.php?id=<?php echo $auction['id']; ?>" class="edit-auction">
                    <i class="fas fa-edit"></i> Edit Auction Details
                </a>
            <?php endif; ?>
        </section>

        <section class="description">
            <h3><i class="fas fa-align-left"></i> Vehicle Description</h3>
            <p><?php echo nl2br(htmlspecialchars($auction['description'])); ?></p>
        </section>

        <section class="bid-history">
            <h3><i class="fas fa-history"></i> Bid History</h3>
            <?php if (empty($bidHistory)): ?>
                <p style="padding: 20px; background: white; border-radius: 8px; text-align: center; color: #666;">
                    <i class="fas fa-comment-slash"></i> No bids placed yet. Be the first to bid!
                </p>
            <?php else: ?>
                <ul>
                    <?php foreach ($bidHistory as $bid): ?>
                        <li>
                            <i class="fas fa-user"></i> <strong><?php echo htmlspecialchars($bid['bidderName']); ?></strong> 
                            bid <span style="color: #8B4513; font-weight: bold;"><?php echo simple_price($bid['amount']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="reviews">
            <h3><i class="fas fa-star"></i> Reviews for <?php echo htmlspecialchars($auction['userName']); ?></h3>
            <?php if (empty($reviews)): ?>
                <p style="padding: 20px; background: white; border-radius: 8px; text-align: center; color: #666;">
                    <i class="fas fa-star-half-alt"></i> No reviews yet. Be the first to review this seller!
                </p>
            <?php else: ?>
                <ul>
                    <?php foreach ($reviews as $review): ?>
                        <li>
                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                <div style="width: 40px; height: 40px; background: #8B4513; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                    <?php echo strtoupper(substr($review['reviewerName'], 0, 1)); ?>
                                </div>
                                <div>
                                    <strong><?php echo htmlspecialchars($review['reviewerName']); ?></strong><br>
                                    <small style="color: #888;"><?php echo htmlspecialchars($review['reviewerEmail']); ?></small>
                                </div>
                            </div>
                            <p style="margin: 10px 0 0 50px; padding: 15px; background: #f9f5f0; border-radius: 8px; border-left: 3px solid #8B4513;">
                                <?php echo nl2br(htmlspecialchars($review['reviewText'])); ?>
                            </p>
                            <small style="display: block; text-align: right; color: #888; margin-top: 5px;">
                                <i class="far fa-clock"></i> <?php echo htmlspecialchars($review['datePosted']); ?>
                            </small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if (isset($_SESSION['user_id'])): ?>
                <div style="background: white; padding: 25px; border-radius: 10px; margin-top: 30px;">
                    <h4><i class="fas fa-pencil-alt"></i> Add Your Review</h4>
                    <form method="POST" action="">
                        <label for="reviewText"><strong>Your Review:</strong></label><br>
                        <textarea name="reviewText" id="reviewText" placeholder="Share your experience with this seller..." required></textarea><br>
                        <div style="text-align: right;">
                            <input type="submit" value="📝 Submit Review">
                        </div>
                    </form>
                    <?php if (isset($reviewSuccess)): ?>
                        <div class="bid-message bid-success" style="margin-top: 15px;">
                            <i class="fas fa-check-circle"></i> <?php echo $reviewSuccess; ?>
                        </div>
                    <?php elseif (isset($reviewError)): ?>
                        <div class="bid-message bid-error" style="margin-top: 15px;">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo $reviewError; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 20px; background: white; border-radius: 10px; margin-top: 20px;">
                    <p style="margin: 0; font-size: 1.1rem;">
                        <i class="fas fa-sign-in-alt"></i> <a href="login.php" style="color: #8B4513; font-weight: bold;">Log in</a> to leave a review.
                    </p>
                </div>
            <?php endif; ?>
        </section>

        <a href="index.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to All Auctions
        </a>
    </article>
</main>

<?php include 'footer.php'; ?>