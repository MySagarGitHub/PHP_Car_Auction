<?php
session_start();

// Include functions.php for common functions
include_once 'functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

try {
    $pdo = new PDO("mysql:host=db;dbname=assignment1", "user", "password");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle auction deletion
if (isset($_GET['delete'])) {
    $auctionId = $_GET['delete'];
    $userId = $_SESSION['user_id'];

    $stmt = $pdo->prepare("SELECT userId, image FROM auction WHERE id = ?");
    $stmt->execute([$auctionId]);
    $auction = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($auction && $auction['userId'] == $userId) {
        try {
            $pdo->beginTransaction();

            // Delete bids first (foreign key constraint)
            $stmt = $pdo->prepare("DELETE FROM bid WHERE auctionId = ?");
            $stmt->execute([$auctionId]);

            // Delete reviews associated with this auction
            $stmt = $pdo->prepare("DELETE FROM review WHERE auctionId = ?");
            $stmt->execute([$auctionId]);

            // Delete auction
            $stmt = $pdo->prepare("DELETE FROM auction WHERE id = ? AND userId = ?");
            $stmt->execute([$auctionId, $userId]);

            // Delete associated image file if exists
            if (!empty($auction['image']) && file_exists($auction['image'])) {
                unlink($auction['image']);
            }

            $pdo->commit();
            $success = "Auction deleted successfully!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error deleting auction: " . $e->getMessage();
        }
    } else {
        $error = "You can only delete your own auctions.";
    }
}

// Get user's auctions with additional details
$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT 
        a.id, 
        a.title, 
        a.description, 
        a.endDate, 
        a.image,
        c.name AS categoryName,
        (SELECT COUNT(*) FROM bid WHERE auctionId = a.id) AS bidCount,
        COALESCE((SELECT MAX(amount) FROM bid WHERE auctionId = a.id), 0) AS highestBid
    FROM auction a 
    JOIN category c ON a.categoryId = c.id 
    WHERE a.userId = ?
    ORDER BY a.endDate ASC
");
$stmt->execute([$userId]);
$auctions = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>

<style>
/* AUCTION MANAGEMENT STYLES */
.management-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 30px;
}

/* Page Header */
.management-header {
    text-align: center;
    margin-bottom: 40px;
    padding-bottom: 25px;
    border-bottom: 2px solid #f0e9dd;
}

.management-header h1 {
    font-size: 2.2rem;
    color: #8B4513;
    margin-bottom: 10px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
}

.management-header p {
    color: #666;
    font-size: 1rem;
    max-width: 600px;
    margin: 0 auto;
}

/* Stats Cards */
.stats-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.stat-card {
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 3px 15px rgba(0, 0, 0, 0.08);
    border: 1px solid #e8e1d5;
    text-align: center;
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-icon {
    font-size: 2.5rem;
    color: #8B4513;
    margin-bottom: 15px;
}

.stat-value {
    font-size: 2rem;
    font-weight: 800;
    color: #333;
    margin-bottom: 5px;
}

.stat-label {
    color: #666;
    font-size: 0.95rem;
    font-weight: 500;
}

/* Alerts */
.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 12px;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.alert-success {
    background: linear-gradient(135deg, #d4edda, #c3e6cb);
    border: 1px solid #b1dfbb;
    color: #155724;
}

.alert-error {
    background: linear-gradient(135deg, #f8d7da, #f5c6cb);
    border: 1px solid #f1b0b7;
    color: #721c24;
}

.alert i {
    font-size: 1.2rem;
}

/* Auctions Grid */
.auctions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 25px;
    margin-top: 30px;
}

.auction-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    border: 1px solid #e8e1d5;
    transition: all 0.3s ease;
    position: relative;
}

.auction-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(139, 69, 19, 0.15);
}

.auction-status {
    position: absolute;
    top: 15px;
    left: -30px;
    padding: 5px 35px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transform: rotate(-45deg);
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
}

.status-active {
    background: #28a745;
    color: white;
}

.status-ended {
    background: #6c757d;
    color: white;
}

.auction-image {
    width: 100%;
    height: 180px;
    background: #f5f0e8;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
}

.auction-image img {
    max-width: 80%;
    max-height: 80%;
    object-fit: contain;
    transition: transform 0.5s ease;
}

.auction-card:hover .auction-image img {
    transform: scale(1.05);
}

.auction-content {
    padding: 20px;
}

.auction-title {
    font-size: 1.1rem;
    color: #333;
    font-weight: 700;
    margin-bottom: 10px;
    line-height: 1.4;
}

.auction-category {
    display: inline-block;
    background: #f0e9dd;
    color: #8B4513;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 600;
    margin-bottom: 15px;
}

.auction-details {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    margin: 15px 0;
    padding: 15px 0;
    border-top: 1px solid #eee;
    border-bottom: 1px solid #eee;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.detail-label {
    font-size: 0.8rem;
    color: #888;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.detail-value {
    font-size: 0.95rem;
    color: #333;
    font-weight: 600;
}

.highest-bid {
    color: #28a745;
    font-weight: 700;
}

.auction-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.action-btn {
    flex: 1;
    padding: 10px;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-decoration: none;
}

.action-btn-edit {
    background: linear-gradient(to bottom, #007bff, #0056b3);
    color: white;
}

.action-btn-edit:hover {
    background: linear-gradient(to bottom, #0056b3, #004085);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
}

.action-btn-view {
    background: linear-gradient(to bottom, #8B4513, #A0522D);
    color: white;
}

.action-btn-view:hover {
    background: linear-gradient(to bottom, #A0522D, #8B4513);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(139, 69, 19, 0.3);
}

.action-btn-delete {
    background: linear-gradient(to bottom, #dc3545, #c82333);
    color: white;
}

.action-btn-delete:hover {
    background: linear-gradient(to bottom, #c82333, #bd2130);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

/* Empty State */
.empty-state {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 40px;
    background: linear-gradient(135deg, #f9f5f0, #f0e9dd);
    border-radius: 10px;
    border: 2px dashed #d4c4ac;
    margin: 30px 0;
}

.empty-state i {
    font-size: 4rem;
    color: #8B4513;
    margin-bottom: 20px;
    opacity: 0.5;
}

.empty-state h2 {
    font-size: 1.8rem;
    color: #333;
    margin-bottom: 10px;
    font-weight: 700;
}

.empty-state p {
    color: #666;
    font-size: 1rem;
    margin-bottom: 25px;
}

.primary-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 30px;
    background: linear-gradient(to bottom, #8B4513, #A0522D);
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-weight: 700;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.primary-btn:hover {
    background: linear-gradient(to bottom, #A0522D, #8B4513);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(139, 69, 19, 0.3);
}

/* Confirmation Modal */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 1000;
    justify-content: center;
    align-items: center;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-content {
    background: white;
    padding: 30px;
    border-radius: 12px;
    max-width: 500px;
    width: 90%;
    text-align: center;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.modal-icon {
    font-size: 4rem;
    color: #dc3545;
    margin-bottom: 20px;
}

.modal h2 {
    color: #333;
    margin-bottom: 15px;
    font-size: 1.5rem;
}

.modal p {
    color: #666;
    margin-bottom: 25px;
    line-height: 1.6;
}

.modal-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
}

.btn-cancel, .btn-confirm {
    padding: 12px 30px;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    min-width: 120px;
}

.btn-cancel {
    background: #6c757d;
    color: white;
}

.btn-cancel:hover {
    background: #5a6268;
    transform: translateY(-2px);
}

.btn-confirm {
    background: linear-gradient(to bottom, #dc3545, #c82333);
    color: white;
}

.btn-confirm:hover {
    background: linear-gradient(to bottom, #c82333, #bd2130);
    transform: translateY(-2px);
}

/* Responsive */
@media (max-width: 768px) {
    .management-container {
        padding: 20px;
    }
    
    .auctions-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-container {
        grid-template-columns: 1fr;
    }
    
    .modal-actions {
        flex-direction: column;
    }
    
    .btn-cancel, .btn-confirm {
        width: 100%;
    }
    
    .auction-actions {
        flex-direction: column;
    }
}

@media (max-width: 480px) {
    .management-header h1 {
        font-size: 1.8rem;
        flex-direction: column;
        gap: 8px;
    }
    
    .auction-details {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Confirmation modal functionality
function confirmDelete(auctionId, auctionTitle) {
    const modal = document.getElementById('confirmModal');
    const confirmBtn = document.getElementById('confirmDelete');
    const auctionTitleSpan = document.getElementById('auctionTitle');
    
    auctionTitleSpan.textContent = auctionTitle;
    
    // Set up confirmation action
    confirmBtn.onclick = function() {
        window.location.href = 'deleteAuction.php?delete=' + auctionId;
    };
    
    // Show modal
    modal.style.display = 'flex';
    
    // Close modal when clicking outside
    modal.onclick = function(e) {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    };
    
    // Close modal with cancel button
    document.getElementById('cancelDelete').onclick = function() {
        modal.style.display = 'none';
    };
}
</script>

<main>
    <div class="management-container">
        <!-- Page Header -->
        <div class="management-header">
            <h1><i class="fas fa-tasks"></i> Manage Your Auctions</h1>
            <p>View, edit, or delete your active auctions. Track bids and manage your listings.</p>
        </div>
        
        <!-- Stats Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-gavel"></i>
                </div>
                <div class="stat-value"><?php echo count($auctions); ?></div>
                <div class="stat-label">Total Auctions</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-hand-paper"></i>
                </div>
                <div class="stat-value">
                    <?php 
                        $activeAuctions = array_filter($auctions, function($a) {
                            return strtotime($a['endDate']) > time();
                        });
                        echo count($activeAuctions);
                    ?>
                </div>
                <div class="stat-label">Active Auctions</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-rupee-sign"></i>
                </div>
                <div class="stat-value">
                    <?php 
                        $totalBids = array_sum(array_column($auctions, 'highestBid'));
                        echo simple_price($totalBids);
                    ?>
                </div>
                <div class="stat-label">Total Highest Bids</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value">
                    <?php 
                        $totalBidCount = array_sum(array_column($auctions, 'bidCount'));
                        echo $totalBidCount;
                    ?>
                </div>
                <div class="stat-label">Total Bids Received</div>
            </div>
        </div>
        
        <!-- Success/Error Messages -->
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong>Success!</strong> <?php echo $success; ?>
                </div>
            </div>
        <?php elseif (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>Error!</strong> <?php echo $error; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Auctions Grid -->
        <div class="auctions-grid">
            <?php if (empty($auctions)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h2>No Auctions Found</h2>
                    <p>You haven't created any auctions yet. Start selling your vehicles today!</p>
                    <a href="addAuction.php" class="primary-btn">
                        <i class="fas fa-plus-circle"></i> Create Your First Auction
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($auctions as $auction): 
                    $endDate = new DateTime($auction['endDate']);
                    $now = new DateTime();
                    $isActive = $endDate > $now;
                    $timeLeft = '';
                    
                    if ($isActive) {
                        $interval = $now->diff($endDate);
                        if ($interval->d > 0) {
                            $timeLeft = $interval->d . ' day' . ($interval->d > 1 ? 's' : '');
                        } elseif ($interval->h > 0) {
                            $timeLeft = $interval->h . ' hour' . ($interval->h > 1 ? 's' : '');
                        } else {
                            $timeLeft = 'Less than 1 hour';
                        }
                    } else {
                        $timeLeft = 'Auction ended';
                    }
                ?>
                    <div class="auction-card">
                        <!-- Status Badge -->
                        <div class="auction-status <?php echo $isActive ? 'status-active' : 'status-ended'; ?>">
                            <?php echo $isActive ? 'Active' : 'Ended'; ?>
                        </div>
                        
                        <!-- Auction Image -->
                        <div class="auction-image">
                            <?php if (!empty($auction['image']) && file_exists($auction['image'])): ?>
                                <img src="<?php echo htmlspecialchars($auction['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($auction['title']); ?>"
                                     onerror="this.src='car.png'">
                            <?php else: ?>
                                <img src="car.png" alt="<?php echo htmlspecialchars($auction['title']); ?>">
                            <?php endif; ?>
                        </div>
                        
                        <!-- Auction Content -->
                        <div class="auction-content">
                            <h3 class="auction-title"><?php echo htmlspecialchars($auction['title']); ?></h3>
                            
                            <span class="auction-category">
                                <?php echo htmlspecialchars($auction['categoryName']); ?>
                            </span>
                            
                            <div class="auction-details">
                                <div class="detail-item">
                                    <span class="detail-label">Ends</span>
                                    <span class="detail-value"><?php echo date('M j, Y H:i', strtotime($auction['endDate'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Time Left</span>
                                    <span class="detail-value"><?php echo $timeLeft; ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Highest Bid</span>
                                    <span class="detail-value highest-bid"><?php echo simple_price($auction['highestBid']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Total Bids</span>
                                    <span class="detail-value"><?php echo $auction['bidCount']; ?></span>
                                </div>
                            </div>
                            
                            <div class="auction-actions">
                                <a href="auction.php?id=<?php echo $auction['id']; ?>" 
                                   class="action-btn action-btn-view">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="editAuction.php?id=<?php echo $auction['id']; ?>" 
                                   class="action-btn action-btn-edit">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <button onclick="confirmDelete(<?php echo $auction['id']; ?>, '<?php echo addslashes($auction['title']); ?>')" 
                                        class="action-btn action-btn-delete">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Create Auction Button -->
        <?php if (!empty($auctions)): ?>
            <div style="text-align: center; margin-top: 50px;">
                <a href="addAuction.php" class="primary-btn">
                    <i class="fas fa-plus-circle"></i> Create New Auction
                </a>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- Confirmation Modal -->
<div id="confirmModal" class="modal">
    <div class="modal-content">
        <div class="modal-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h2>Confirm Deletion</h2>
        <p>Are you sure you want to delete auction "<span id="auctionTitle"></span>"? This action cannot be undone and will also delete all associated bids.</p>
        <div class="modal-actions">
            <button id="cancelDelete" class="btn-cancel">Cancel</button>
            <button id="confirmDelete" class="btn-confirm">Delete Auction</button>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>