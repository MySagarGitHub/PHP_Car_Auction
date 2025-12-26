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

// Get all categories
$stmt = $pdo->query("SELECT * FROM category ORDER BY name ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categoryId = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($categoryId) {
    // Get category details
    $stmt = $pdo->prepare("SELECT name FROM category WHERE id = ?");
    $stmt->execute([$categoryId]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$category) {
        die("Category not found.");
    }

    // Get auctions for this category with highest bid
    $stmt = $pdo->prepare("
        SELECT 
            a.id, a.title, a.description, a.endDate, a.image, 
            c.name AS categoryName, u.name AS userName,
            COALESCE((
                SELECT MAX(b.amount) 
                FROM bid b 
                WHERE b.auctionId = a.id
            ), 0) AS highestBid
        FROM auction a
        JOIN category c ON a.categoryId = c.id
        JOIN user u ON a.userId = u.id
        WHERE a.categoryId = ? 
        ORDER BY a.endDate ASC
    ");
    $stmt->execute([$categoryId]);
    $auctions = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

include 'header.php';
?>

<style>
/* CATEGORY PAGE STYLES */
main {
    max-width: 1400px;
    margin: 0 auto;
    padding: 30px;
    min-height: 60vh;
}

.page-title {
    text-align: center;
    color: #8B4513;
    margin-bottom: 30px;
    font-size: 2.2rem;
    font-weight: 800;
    position: relative;
    padding-bottom: 15px;
}

.page-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 3px;
    background: linear-gradient(to right, #8B4513, #A0522D);
    border-radius: 2px;
}

/* Category Navigation */
.category-nav {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 40px;
    padding: 20px;
    background: linear-gradient(135deg, rgba(139, 69, 19, 0.08), rgba(255, 215, 0, 0.08));
    border-radius: 10px;
    border: 1px solid #e8e1d5;
    justify-content: center;
}

.category-link {
    text-decoration: none;
    padding: 10px 20px;
    background: white;
    border: 2px solid #e8e1d5;
    border-radius: 30px;
    color: #333;
    font-weight: 600;
    transition: all 0.3s ease;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.category-link:hover {
    background: #8B4513;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(139, 69, 19, 0.2);
    border-color: #8B4513;
}

.category-link.active {
    background: linear-gradient(to bottom, #8B4513, #A0522D);
    color: white;
    border-color: #8B4513;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(139, 69, 19, 0.3);
}

/* Category Header */
.category-header {
    text-align: center;
    padding: 30px 20px;
    margin-bottom: 40px;
    background: linear-gradient(135deg, rgba(139, 69, 19, 0.08), rgba(255, 215, 0, 0.08));
    border-radius: 10px;
    border: 1px solid #e8e1d5;
}

.category-header h1 {
    font-size: 2rem;
    color: #8B4513;
    margin-bottom: 10px;
    font-weight: 800;
}

.category-icon {
    font-size: 2.5rem;
    color: #8B4513;
    margin-bottom: 15px;
}

/* Category Grid - Matching Index.php Design */
.category-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 25px;
    padding: 20px 0;
}

.category-item {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    border: 1px solid #e8e1d5;
    transition: all 0.4s ease;
    position: relative;
    height: 480px;
    display: flex;
    flex-direction: column;
    animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.category-item:hover {
    box-shadow: 0 8px 24px rgba(139, 69, 19, 0.15);
    transform: translateY(-5px);
    border-color: #8B4513;
}

.category-image {
    width: 100%;
    height: 180px;
    overflow: hidden;
    position: relative;
    background: #f5f0e8;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 15px;
}

.category-image img {
    max-width: 80%;
    max-height: 80%;
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

.category-item:hover .category-image img {
    transform: translate(-50%, -50%) scale(1.05);
}

.category-ribbon {
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

.category-content {
    padding: 15px;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    text-align: center;
}

.category-content h2 {
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

.category-description {
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

.category-price {
    font-size: 1.5rem;
    color: #8B4513;
    font-weight: 900;
    margin: 10px 0;
    padding: 8px 0;
    border-top: 1px dashed #e8e1d5;
    border-bottom: 1px dashed #e8e1d5;
}

.category-meta {
    display: flex;
    justify-content: space-around;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #e8e1d5;
    font-size: 0.8rem;
    color: #888;
}

.meta-item {
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 3px;
}

.meta-item i {
    font-size: 0.9rem;
    color: #8B4513;
}

.category-action {
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

.category-action:hover {
    background: linear-gradient(to bottom, #A0522D, #8B4513);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(139, 69, 19, 0.3);
}

/* No Auctions Message */
.no-auctions {
    text-align: center;
    padding: 60px 40px;
    background: linear-gradient(135deg, rgba(139, 69, 19, 0.05), rgba(255, 215, 0, 0.05));
    border-radius: 10px;
    border: 2px dashed #e8e1d5;
    margin: 30px 0;
}

.no-auctions i {
    font-size: 4rem;
    color: #8B4513;
    margin-bottom: 20px;
    opacity: 0.5;
}

.no-auctions h2 {
    font-size: 1.8rem;
    color: #333;
    margin-bottom: 10px;
    font-weight: 800;
}

.no-auctions p {
    color: #666;
    font-size: 0.95rem;
    margin-bottom: 20px;
}

.no-auctions a {
    display: inline-block;
    padding: 10px 25px;
    background: linear-gradient(to bottom, #8B4513, #A0522D);
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-weight: 700;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.no-auctions a:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(139, 69, 19, 0.3);
}

/* Responsive Design */
@media (max-width: 768px) {
    .category-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
    }
    
    .category-item {
        height: 460px;
    }
    
    .category-image {
        height: 160px;
    }
    
    .category-nav {
        padding: 15px;
    }
    
    .category-link {
        padding: 8px 16px;
        font-size: 0.85rem;
    }
}

@media (max-width: 480px) {
    .category-grid {
        grid-template-columns: 1fr;
    }
    
    .category-item {
        height: 440px;
    }
    
    .category-image {
        height: 150px;
    }
    
    .category-nav {
        justify-content: flex-start;
        overflow-x: auto;
        padding: 10px;
        white-space: nowrap;
    }
}
</style>

<main>
    <h1 class="page-title"><i class="fas fa-tags"></i> Browse by Category</h1>
    
    <!-- Category Navigation -->
    <nav class="category-nav">
        <?php foreach ($categories as $cat): 
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
            $icon = $icons[$cat['name']] ?? 'fas fa-car';
        ?>
            <a href="category.php?id=<?php echo $cat['id']; ?>" 
               class="category-link <?php echo ($cat['id'] == $categoryId) ? 'active' : ''; ?>">
               <i class="<?php echo $icon; ?>"></i>
               <?php echo htmlspecialchars($cat['name']); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <?php if ($categoryId): ?>
        <!-- Category Header -->
        <div class="category-header">
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
            <div class="category-icon">
                <i class="<?php echo $icon; ?>"></i>
            </div>
            <h1><?php echo htmlspecialchars($category['name']); ?> Auctions</h1>
            <p>Browse all vehicles in the <?php echo htmlspecialchars($category['name']); ?> category</p>
        </div>

        <?php if (empty($auctions)): ?>
            <div class="no-auctions">
                <i class="fas fa-inbox"></i>
                <h2>No Auctions Found</h2>
                <p>There are currently no auctions in the <?php echo htmlspecialchars($category['name']); ?> category.</p>
                <a href="addAuction.php"><i class="fas fa-plus-circle"></i> Create First Auction</a>
            </div>
        <?php else: ?>
            <div class="category-grid">
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
                    <div class="category-item">
                        <div class="category-image">
                            <?php if (!empty($auction['image']) && file_exists($auction['image'])): ?>
                                <img src="<?php echo htmlspecialchars($auction['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($auction['title']); ?>"
                                     onerror="this.src='car.png'">
                            <?php else: ?>
                                <img src="car.png" alt="<?php echo htmlspecialchars($auction['title']); ?>">
                            <?php endif; ?>
                            <div class="category-ribbon"><?php echo htmlspecialchars($auction['categoryName']); ?></div>
                            
                            <?php if ($auction['highestBid'] == 0): ?>
                                <div style="position: absolute; top: 10px; left: 10px; background: #FFD700; color: #8B4513; padding: 4px 10px; font-size: 0.7rem; font-weight: 700; border-radius: 12px; z-index: 1; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);">
                                    NO BIDS YET
                                </div>
                            <?php elseif ($endDate < $now): ?>
                                <div style="position: absolute; top: 10px; left: 10px; background: #e63946; color: white; padding: 4px 10px; font-size: 0.7rem; font-weight: 700; border-radius: 12px; z-index: 1; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);">
                                    SOLD
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="category-content">
                            <h2><?php echo htmlspecialchars($auction['title']); ?></h2>
                            
                            <p class="category-description"><?php echo htmlspecialchars($shortDescription); ?></p>
                            
                            <!-- Using simple_price() function from functions.php -->
                            <div class="category-price"><?php echo simple_price($auction['highestBid']); ?></div>
                            
                            <div class="category-meta">
                                <div class="meta-item">
                                    <i class="fas fa-user"></i>
                                    <span><?php echo htmlspecialchars($auction['userName']); ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-clock"></i>
                                    <span><?php echo $timeLeft; ?></span>
                                </div>
                            </div>
                            
                            <a href="auction.php?id=<?php echo $auction['id']; ?>" class="category-action">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="no-auctions" style="margin-top: 50px;">
            <i class="fas fa-hand-point-up"></i>
            <h2>Select a Category</h2>
            <p>Choose a category from the navigation above to browse specific vehicle types.</p>
            <p>Each category contains specialized auctions tailored to different vehicle preferences.</p>
        </div>
    <?php endif; ?>
</main>

<?php include 'footer.php'; ?>