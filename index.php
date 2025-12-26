<?php
session_start();

// Include functions.php for common functions
include_once 'functions.php';

// REMOVE the old function definition
// function simple_price($price) {
//     // Format the price with currency symbol
//     return '£' . number_format($price, 0);
// }

// REMOVE the config.php check since you deleted it
// if (!defined('CONFIG_LOADED')) {
//     include_once 'config.php';
// }

try {
    $pdo = new PDO("mysql:host=db;dbname=assignment1", "user", "password");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$stmt = $pdo->prepare("
    SELECT 
        a.id, a.title, a.description, a.endDate, a.image, c.name AS categoryName, u.name AS userName,
        COALESCE((
            SELECT MAX(b.amount) 
            FROM bid b 
            WHERE b.auctionId = a.id
        ), 0) AS highestBid
    FROM auction a
    JOIN category c ON a.categoryId = c.id
    JOIN user u ON a.userId = u.id
    ORDER BY a.id DESC 
    LIMIT 12
");
$stmt->execute();
$auctions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// INCLUDE HEADER ONLY ONCE HERE
include 'header.php';
?>

<style>
/* Inline CSS for car image size control */
.cookbook-catalog {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 25px;
    padding: 20px 0;
}

.cookbook-item {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    transition: all 0.4s ease;
    border: 1px solid #e8e1d5;
    position: relative;
    height: 480px; /* Fixed height for all cards */
    display: flex;
    flex-direction: column;
}

.cookbook-image {
    width: 100%;
    height: 180px; /* Fixed height for all images */
    overflow: hidden;
    position: relative;
    background: #f5f0e8;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 15px;
}

/* CAR IMAGE SIZE CONTROL - SMALL AND CENTERED */
.cookbook-image img {
    max-width: 80%; /* Small width */
    max-height: 80%; /* Small height */
    width: auto;
    height: auto;
    object-fit: contain;
    transition: transform 0.5s ease;
    display: block;
    margin: 0 auto;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.cookbook-item:hover .cookbook-image img {
    transform: translate(-50%, -50%) scale(1.05); /* Minimal zoom */
}

.cookbook-content {
    padding: 15px;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    text-align: center;
}

.cookbook-content h3 {
    font-size: 1.1rem;
    color: #333;
    margin-bottom: 8px;
    font-weight: 700;
    line-height: 1.3;
    min-height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.item-description {
    color: #666;
    font-size: 0.85rem;
    line-height: 1.5;
    margin-bottom: 10px;
    flex-grow: 1;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
}

.item-price {
    font-size: 1.5rem;
    color: #8B4513;
    font-weight: 900;
    margin: 10px 0;
    padding: 8px 0;
    border-top: 1px dashed #e8e1d5;
    border-bottom: 1px dashed #e8e1d5;
}

.item-details {
    display: flex;
    justify-content: space-around;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #e8e1d5;
    font-size: 0.8rem;
    color: #888;
}

.detail-item {
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 3px;
}

.detail-item i {
    font-size: 0.9rem;
    color: #8B4513;
}

.auction-action {
    display: block;
    width: 100%;
    padding: 10px;
    background: linear-gradient(to bottom, #8B4513, #A0522D);
    color: white;
    border: none;
    border-radius: 5px;
    font-family: inherit;
    font-weight: 700;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    text-decoration: none;
    text-align: center;
    margin-top: auto;
}

.auction-action:hover {
    background: linear-gradient(to bottom, #A0522D, #8B4513);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(139, 69, 19, 0.3);
}

/* Category ribbon */
.item-category {
    position: absolute;
    top: 10px;
    right: -30px;
    background: #8B4513;
    color: white;
    padding: 4px 30px;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transform: rotate(45deg);
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
}

.featured-badge {
    position: absolute;
    top: 10px;
    left: 10px;
    background: #FFD700;
    color: #8B4513;
    padding: 4px 10px;
    font-size: 0.7rem;
    font-weight: 700;
    border-radius: 12px;
    z-index: 1;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
}

.featured-badge.auction-ended {
    background: #e63946;
    color: white;
}

/* Responsive design */
@media (max-width: 768px) {
    .cookbook-catalog {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
    }
    
    .cookbook-item {
        height: 460px;
    }
    
    .cookbook-image {
        height: 160px;
    }
    
    .cookbook-image img {
        max-width: 75%;
        max-height: 75%;
    }
}

@media (max-width: 480px) {
    .cookbook-catalog {
        grid-template-columns: 1fr;
    }
    
    .cookbook-item {
        height: 440px;
    }
    
    .cookbook-image {
        height: 150px;
    }
}
</style>

<main>
    <div class="catalog-intro">
        <h2>LATEST CAR AUCTIONS</h2>
        <p>Browse our exclusive collection of premium vehicles from around the world. Each car is meticulously inspected and ready for its new journey with you.</p>
    </div>
    
    <div class="decorative-border"></div>
    
    <?php if (empty($auctions)): ?>
        <div class="no-auctions">
            <h3>No auctions available</h3>
            <p>Check back soon or <a href="addAuction.php">add your own auction</a>.</p>
        </div>
    <?php else: ?>
        <div class="cookbook-catalog">
            <?php foreach ($auctions as $auction): 
                // Truncate description for card display
                $shortDescription = strlen($auction['description']) > 100 
                    ? substr($auction['description'], 0, 100) . '...' 
                    : $auction['description'];
                    
                // Format end date
                $endDate = new DateTime($auction['endDate']);
                $now = new DateTime();
                $interval = $now->diff($endDate);
                $timeLeft = '';
                
                if ($endDate < $now) {
                    $timeLeft = 'Auction ended';
                } else if ($interval->d > 0) {
                    $timeLeft = $interval->d . ' day' . ($interval->d > 1 ? 's' : '');
                } else if ($interval->h > 0) {
                    $timeLeft = $interval->h . ' hour' . ($interval->h > 1 ? 's' : '');
                } else {
                    $timeLeft = 'Less than 1 hour';
                }
            ?>
                <div class="cookbook-item">
                    <div class="cookbook-image">
                        <?php if (!empty($auction['image']) && file_exists($auction['image'])): ?>
                            <img src="<?php echo htmlspecialchars($auction['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($auction['title']); ?>"
                                 onerror="this.src='car.png'"
                                 style="width: auto; height: auto; max-width: 80%; max-height: 80%; object-fit: contain;">
                        <?php else: ?>
                            <img src="car.png" 
                                 alt="<?php echo htmlspecialchars($auction['title']); ?>"
                                 style="width: auto; height: auto; max-width: 80%; max-height: 80%; object-fit: contain;">
                        <?php endif; ?>
                        <div class="item-category"><?php echo htmlspecialchars($auction['categoryName']); ?></div>
                        
                        <?php if ($auction['highestBid'] == 0): ?>
                            <div class="featured-badge">NO BIDS YET</div>
                        <?php elseif ($endDate < $now): ?>
                            <div class="featured-badge auction-ended">SOLD</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="cookbook-content">
                        <h3><?php echo htmlspecialchars($auction['title']); ?></h3>
                        
                        <p class="item-description"><?php echo htmlspecialchars($shortDescription); ?></p>
                        
                        <!-- NOW USING simple_price() FUNCTION from functions.php -->
                        <div class="item-price"><?php echo simple_price($auction['highestBid']); ?></div>
                        
                        <div class="item-details">
                            <div class="detail-item">
                                <i class="fas fa-user"></i>
                                <span><?php echo htmlspecialchars($auction['userName']); ?></span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-clock"></i>
                                <span><?php echo $timeLeft; ?></span>
                            </div>
                        </div>
                        
                        <a href="auction.php?id=<?php echo $auction['id']; ?>" class="auction-action">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="view-all">
            <a href="category.php" class="view-all-btn">
                <i class="fas fa-list"></i> View All Auctions
            </a>
        </div>
    <?php endif; ?>
    
    <div class="featured-categories">
        <h2><i class="fas fa-tags"></i> Browse by Category</h2>
        <div class="categories-grid">
            <?php
            // Get all categories
            $categoryStmt = $pdo->prepare("SELECT id, name FROM category ORDER BY name");
            $categoryStmt->execute();
            $categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($categories as $category):
            ?>
                <a href="category.php?id=<?php echo $category['id']; ?>" class="category-item">
                    <div class="category-icon">
                        <?php 
                        $icons = [
                            'Sports' => 'fas fa-bolt',
                            'SUV' => 'fas fa-truck',
                            'Sedan' => 'fas fa-car',
                            'Luxury' => 'fas fa-gem',
                            'Classic' => 'fas fa-history',
                            'Electric' => 'fas fa-charging-station',
                            'Convertible' => 'fas fa-wind',
                            'Truck' => 'fas fa-truck-pickup',
                            'Van' => 'fas fa-shuttle-van',
                            'Motorcycle' => 'fas fa-motorcycle'
                        ];
                        $icon = $icons[$category['name']] ?? 'fas fa-car';
                        ?>
                        <i class="<?php echo $icon; ?>"></i>
                    </div>
                    <h4><?php echo htmlspecialchars($category['name']); ?></h4>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>