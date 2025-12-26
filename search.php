<?php
session_start();

// Include functions.php for common functions like simple_price()
include_once 'functions.php';

try {
    $pdo = new PDO("mysql:host=db;dbname=assignment1", "user", "password");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get search query
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$results = [];
$total_results = 0;

if (!empty($search)) {
    // Search in auctions
    $stmt = $pdo->prepare("
        SELECT 
            a.id, a.title, a.description, a.endDate, a.image, 
            c.name AS categoryName, u.name AS userName,
            COALESCE((SELECT MAX(b.amount) FROM bid b WHERE b.auctionId = a.id), 0) AS highestBid,
            (SELECT COUNT(*) FROM bid WHERE auctionId = a.id) AS bidCount
        FROM auction a
        JOIN category c ON a.categoryId = c.id
        JOIN user u ON a.userId = u.id
        WHERE a.title LIKE :search 
           OR a.description LIKE :search 
           OR c.name LIKE :search
        ORDER BY a.id DESC
    ");
    
    $search_term = "%{$search}%";
    $stmt->bindParam(':search', $search_term);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_results = count($results);
}

include 'header.php';
?>

<style>
/* SEARCH PAGE STYLES - MATCHING INDEX.PHP DESIGN */

.search-header {
    text-align: center;
    padding: 40px 20px;
    margin-bottom: 40px;
    background: linear-gradient(135deg, rgba(139, 69, 19, 0.08), rgba(255, 215, 0, 0.08));
    border-radius: 10px;
    border: 1px solid #e8e1d5;
}

.search-header h1 {
    font-size: 2.2rem;
    color: #8B4513;
    margin-bottom: 10px;
    font-weight: 800;
    letter-spacing: 1px;
}

.search-query {
    color: #8B4513;
    font-size: 1.2rem;
    font-weight: 700;
}

.results-info {
    color: #666;
    font-size: 0.95rem;
    margin-top: 15px;
    line-height: 1.6;
}

.search-form {
    max-width: 700px;
    margin: 0 auto 40px;
    padding: 0 20px;
}

.search-input-group {
    display: flex;
    gap: 10px;
}

/* CHANGED: Black border, visible typed text */
.search-input-group input {
    flex: 1;
    padding: 12px 16px 12px 45px; /* Added left padding for icon */
    border: 2px solid black !important; /* BLACK BORDER */
    border-radius: 5px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    background-color: white !important; /* White background */
    color: black !important; /* BLACK TYPED TEXT */
    position: relative;
}

.search-input-group input:focus {
    outline: none;
    border-color: rgb(230, 57, 70) !important; /* Red on focus */
    background-color: white !important;
    box-shadow: 0 0 0 3px rgba(230, 57, 70, 0.1);
    color: black !important; /* Keep text black */
}

/* Add search icon inside the input */
.search-input-group {
    position: relative;
}

.search-input-group::before {
    content: '\f002'; /* FontAwesome search icon */
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: black; /* BLACK SEARCH ICON */
    font-size: 1.1rem;
    z-index: 2;
    pointer-events: none;
}

/* CHANGED: Icon-only button, no text */
.search-input-group button {
    padding: 12px 15px; /* Reduced padding for icon-only */
    background: black !important; /* BLACK BUTTON */
    color: white;
    border: none;
    border-radius: 5px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    white-space: nowrap;
    width: 45px; /* Fixed width for icon */
    display: flex;
    align-items: center;
    justify-content: center;
}

.search-input-group button:hover {
    background: rgb(230, 57, 70) !important; /* Red on hover */
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(230, 57, 70, 0.3);
}

.search-input-group button i {
    color: white; /* White icon on black button */
    font-size: 1.2rem;
}

/* Remove text, keep only icon */
.search-input-group button span {
    display: none; /* Hide the "Search" text */
}

/* RESULTS GRID - MATCHING INDEX.PHP */
.results-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 25px;
    padding: 20px 0;
}

.result-card {
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
}

.result-card:hover {
    box-shadow: 0 8px 20px rgba(139, 69, 19, 0.2);
    transform: translateY(-5px);
}

.result-image {
    width: 100%;
    height: 180px;
    background: #f5f0e8;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
    padding: 15px;
}

.result-image img {
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

.result-card:hover .result-image img {
    transform: translate(-50%, -50%) scale(1.05);
}

.result-category {
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

.result-content {
    padding: 15px;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    text-align: center;
}

.result-title {
    font-size: 1.1rem;
    color: #333;
    font-weight: 700;
    margin-bottom: 8px;
    line-height: 1.3;
    min-height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.result-description {
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

.result-price {
    font-size: 1.5rem;
    color: #8B4513;
    font-weight: 900;
    margin: 10px 0;
    padding: 8px 0;
    border-top: 1px dashed #e8e1d5;
    border-bottom: 1px dashed #e8e1d5;
}

.result-meta {
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

.result-button {
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

.result-button:hover {
    background: linear-gradient(to bottom, #A0522D, #8B4513);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(139, 69, 19, 0.3);
}

/* NO RESULTS */
.no-results {
    text-align: center;
    padding: 60px 40px;
    background: linear-gradient(135deg, rgba(139, 69, 19, 0.05), rgba(255, 215, 0, 0.05));
    border-radius: 10px;
    border: 2px dashed #e8e1d5;
    margin: 30px 0;
}

.no-results i {
    font-size: 4rem;
    color: #8B4513;
    margin-bottom: 20px;
    opacity: 0.5;
}

.no-results h2 {
    font-size: 1.8rem;
    color: #333;
    margin-bottom: 10px;
    font-weight: 800;
}

.no-results p {
    color: #666;
    font-size: 0.95rem;
    margin-bottom: 20px;
}

.no-results a {
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

.no-results a:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(139, 69, 19, 0.3);
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .results-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
    }
    
    .result-card {
        height: 460px;
    }
    
    .result-image {
        height: 160px;
    }
    
    .result-image img {
        max-width: 75%;
        max-height: 75%;
    }
}

@media (max-width: 480px) {
    .results-grid {
        grid-template-columns: 1fr;
    }
    
    .result-card {
        height: 440px;
    }
    
    .result-image {
        height: 150px;
    }
    
    .search-input-group {
        flex-direction: column;
    }
    
    .search-input-group button {
        width: 100%;
        height: 45px;
    }
}
</style>

<main>
    <!-- Search Header -->
    <div class="search-header">
        <h1><i class="fas fa-search"></i> Search Results</h1>
        <?php if (!empty($search)): ?>
            <p class="results-info">
                Found <span class="search-query"><?php echo $total_results; ?></span> result<?php echo $total_results !== 1 ? 's' : ''; ?> 
                for "<span class="search-query"><?php echo htmlspecialchars($search); ?></span>"
            </p>
        <?php else: ?>
            <p class="results-info">Please enter a search term</p>
        <?php endif; ?>
    </div>

    <!-- Search Form -->
    <div class="search-form">
        <form method="GET" action="search.php">
            <div class="search-input-group">
                <input type="text" name="search" placeholder="Search for a car, make, or model..." 
                       value="<?php echo htmlspecialchars($search); ?>" required>
                <button type="submit" title="Search"><i class="fas fa-search"></i></button>
            </div>
        </form>
    </div>

    <!-- Results -->
    <?php if (empty($search)): ?>
        <div class="no-results">
            <i class="fas fa-search"></i>
            <h2>No Search Query</h2>
            <p>Enter a search term to find auctions</p>
            <a href="index.php"><i class="fas fa-home"></i> Back to Home</a>
        </div>
    <?php elseif ($total_results === 0): ?>
        <div class="no-results">
            <i class="fas fa-inbox"></i>
            <h2>No Results Found</h2>
            <p>Sorry, we couldn't find any auctions matching "<?php echo htmlspecialchars($search); ?>"</p>
            <a href="index.php"><i class="fas fa-home"></i> Browse All Auctions</a>
        </div>
    <?php else: ?>
        <div class="results-grid">
            <?php foreach ($results as $auction): 
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
                <div class="result-card">
                    <div class="result-image">
                        <?php if (!empty($auction['image']) && file_exists($auction['image'])): ?>
                            <img src="<?php echo htmlspecialchars($auction['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($auction['title']); ?>"
                                 onerror="this.src='car.png'">
                        <?php else: ?>
                            <img src="car.png" alt="<?php echo htmlspecialchars($auction['title']); ?>">
                        <?php endif; ?>
                        <div class="result-category"><?php echo htmlspecialchars($auction['categoryName']); ?></div>
                    </div>
                    
                    <div class="result-content">
                        <h3 class="result-title"><?php echo htmlspecialchars($auction['title']); ?></h3>
                        
                        <p class="result-description"><?php echo htmlspecialchars(substr($auction['description'], 0, 100)) . '...'; ?></p>
                        
                        <!-- NOW USING simple_price() FUNCTION from functions.php -->
                        <div class="result-price"><?php echo simple_price($auction['highestBid']); ?></div>
                        
                        <div class="result-meta">
                            <div class="meta-item">
                                <i class="fas fa-user"></i>
                                <span><?php echo htmlspecialchars($auction['userName']); ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-clock"></i>
                                <span><?php echo $timeLeft; ?></span>
                            </div>
                        </div>
                        
                        <a href="auction.php?id=<?php echo $auction['id']; ?>" class="result-button">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php include 'footer.php'; ?>