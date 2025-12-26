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

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: deleteAuction.php"); 
    exit;
}

$auctionId = $_GET['id'];
$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT a.id, a.title, a.description, a.categoryId, a.endDate, a.image, a.userId, 
           c.name AS categoryName, u.name AS userName
    FROM auction a 
    JOIN category c ON a.categoryId = c.id
    JOIN user u ON a.userId = u.id
    WHERE a.id = ? AND a.userId = ?
");
$stmt->execute([$auctionId, $userId]);
$auction = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$auction) {
    $error = "Auction not found or you do not have permission to edit it.";
    include 'header.php';
    echo "<main style='max-width: 800px; margin: 0 auto; padding: 30px;'><div class='alert alert-error'><i class='fas fa-exclamation-triangle'></i> <div><strong>Error:</strong> $error</div></div><p><a href='deleteAuction.php' class='back-link'><i class='fas fa-arrow-left'></i> Back to your auctions</a></p></main>";
    include 'footer.php';
    exit;
}

$stmt = $pdo->query("SELECT id, name FROM category ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $categoryId = $_POST['category'];
    $endDate = $_POST['auctionEndDate'];

    // Validation
    $validationErrors = [];
    
    if (empty($title)) {
        $validationErrors[] = "Title is required";
    } elseif (strlen($title) < 5) {
        $validationErrors[] = "Title must be at least 5 characters long";
    }
    
    if (empty($description)) {
        $validationErrors[] = "Description is required";
    } elseif (strlen($description) < 20) {
        $validationErrors[] = "Description must be at least 20 characters long";
    }
    
    if (empty($categoryId)) {
        $validationErrors[] = "Category is required";
    }
    
    if (empty($endDate)) {
        $validationErrors[] = "End date is required";
    }
    
    // Check if end date is in the future
    if (!empty($endDate)) {
        $endDateTime = new DateTime($endDate);
        $now = new DateTime();
        
        if ($endDateTime <= $now) {
            $validationErrors[] = "End date must be in the future";
        }
    }
    
    if (empty($validationErrors)) {
        $imagePath = $auction['image']; 
        
        // Handle image upload
        if (!empty($_FILES['image']['name'])) {
            $targetDir = "images/auctions/";
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }
            
            $filename = uniqid() . "_" . preg_replace("/[^a-zA-Z0-9\.]/", "_", basename($_FILES['image']['name']));
            $imagePath = $targetDir . $filename;
            $imageFileType = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            // Check if image file is actual image
            $check = @getimagesize($_FILES['image']['tmp_name']);
            if ($check === false) {
                $validationErrors[] = "File is not a valid image";
            } elseif (!in_array($imageFileType, $allowedTypes)) {
                $validationErrors[] = "Only JPG, JPEG, PNG, GIF, and WEBP files are allowed";
            } elseif ($_FILES['image']['size'] > 5000000) { 
                $validationErrors[] = "Image size must be less than 5MB";
            } elseif (!move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
                $validationErrors[] = "Failed to upload image";
            } else {
                // Delete old image if new one uploaded successfully
                if ($auction['image'] && file_exists($auction['image']) && $auction['image'] != $imagePath) {
                    unlink($auction['image']);
                }
            }
        }
        
        if (empty($validationErrors)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE auction 
                    SET title = ?, description = ?, categoryId = ?, endDate = ?, image = ? 
                    WHERE id = ? AND userId = ?
                ");
                $stmt->execute([$title, $description, $categoryId, $endDate, $imagePath, $auctionId, $userId]);
                $success = "Auction updated successfully!";
                
                // Refresh auction data
                $stmt = $pdo->prepare("
                    SELECT a.id, a.title, a.description, a.categoryId, a.endDate, a.image, a.userId, 
                           c.name AS categoryName, u.name AS userName
                    FROM auction a 
                    JOIN category c ON a.categoryId = c.id
                    JOIN user u ON a.userId = u.id
                    WHERE a.id = ? AND a.userId = ?
                ");
                $stmt->execute([$auctionId, $userId]);
                $auction = $stmt->fetch(PDO::FETCH_ASSOC);
                
            } catch (PDOException $e) {
                $error = "Error updating auction: " . $e->getMessage();
            }
        } else {
            $error = implode("<br>", $validationErrors);
        }
    } else {
        $error = implode("<br>", $validationErrors);
    }
}

include 'header.php';
?>

<style>
/* EDIT AUCTION STYLES - Matching addAuction.php design */
.edit-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 30px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid #e8e1d5;
}

.page-header {
    text-align: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f0e9dd;
}

.page-header h1 {
    font-size: 2.2rem;
    color: #8B4513;
    margin-bottom: 10px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.page-header p {
    color: #666;
    font-size: 0.95rem;
}

/* Auction Info Card */
.auction-info {
    background: linear-gradient(135deg, #f9f5f0, #f0e9dd);
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 30px;
    border: 1px solid #e8e1d5;
}

.auction-info h3 {
    color: #8B4513;
    margin-bottom: 15px;
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.auction-meta {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #666;
    font-size: 0.9rem;
}

.meta-item i {
    color: #8B4513;
    width: 20px;
}

/* ALERT MESSAGES */
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

/* FORM STYLES */
.edit-form {
    display: grid;
    gap: 25px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-group label {
    font-weight: 600;
    color: #333;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 5px;
}

.required::after {
    content: '*';
    color: #dc3545;
    margin-left: 4px;
}

.form-group input[type="text"],
.form-group select,
.form-group input[type="datetime-local"],
.form-group textarea {
    padding: 12px 16px;
    border: 2px solid #e8e1d5;
    border-radius: 6px;
    font-size: 0.95rem;
    font-family: inherit;
    transition: all 0.3s ease;
    background-color: #f9f5f0;
}

.form-group input[type="text"]:focus,
.form-group select:focus,
.form-group input[type="datetime-local"]:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #8B4513;
    background-color: white;
    box-shadow: 0 0 0 3px rgba(139, 69, 19, 0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 120px;
    line-height: 1.5;
}

/* Current Image Preview */
.current-image {
    margin-top: 10px;
    text-align: center;
}

.current-image img {
    max-width: 300px;
    max-height: 200px;
    border-radius: 8px;
    border: 2px solid #e8e1d5;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    margin-bottom: 10px;
}

.current-image p {
    color: #666;
    font-size: 0.9rem;
    margin: 5px 0;
}

/* Image Upload */
.file-upload {
    position: relative;
    margin-top: 5px;
}

.file-upload input[type="file"] {
    position: absolute;
    left: 0;
    top: 0;
    opacity: 0;
    width: 100%;
    height: 100%;
    cursor: pointer;
}

.file-upload-label {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 15px;
    padding: 30px 20px;
    background: linear-gradient(135deg, #f9f5f0, #f0e9dd);
    border: 2px dashed #d4c4ac;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
}

.file-upload-label:hover {
    background: linear-gradient(135deg, #f0e9dd, #e8e1d5);
    border-color: #8B4513;
}

.file-upload-label i {
    font-size: 2.5rem;
    color: #8B4513;
    opacity: 0.7;
}

.file-upload-label span {
    font-size: 1rem;
    color: #666;
    font-weight: 500;
}

.file-upload-label small {
    font-size: 0.85rem;
    color: #888;
}

.file-preview {
    margin-top: 15px;
    display: none;
    text-align: center;
}

.file-preview img {
    max-width: 200px;
    max-height: 150px;
    border-radius: 6px;
    border: 2px solid #e8e1d5;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
}

/* FORM ACTIONS */
.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
    justify-content: center;
}

.submit-btn {
    padding: 14px 40px;
    background: linear-gradient(to bottom, #28a745, #218838);
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 180px;
    justify-content: center;
}

.submit-btn:hover {
    background: linear-gradient(to bottom, #218838, #1e7e34);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
}

.cancel-btn {
    padding: 14px 40px;
    background: linear-gradient(to bottom, #6c757d, #5a6268);
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 180px;
    justify-content: center;
    text-decoration: none;
}

.cancel-btn:hover {
    background: linear-gradient(to bottom, #5a6268, #545b62);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(108, 117, 125, 0.3);
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #8B4513;
    text-decoration: none;
    font-weight: 600;
    margin-top: 20px;
    transition: all 0.3s ease;
}

.back-link:hover {
    color: #A0522D;
    transform: translateX(-5px);
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .edit-container {
        padding: 20px;
        margin: 0 15px;
    }
    
    .page-header h1 {
        font-size: 1.8rem;
        flex-direction: column;
        gap: 5px;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .submit-btn, .cancel-btn {
        width: 100%;
    }
}

@media (max-width: 480px) {
    .edit-container {
        padding: 15px;
    }
    
    .alert {
        flex-direction: column;
        text-align: center;
        gap: 8px;
    }
    
    .auction-meta {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // File preview functionality
    const imageInput = document.getElementById('image');
    const filePreview = document.getElementById('filePreview');
    const previewImage = document.getElementById('previewImage');
    const fileName = document.getElementById('fileName');
    
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                fileName.textContent = file.name;
                
                // Show preview for image files
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImage.src = e.target.result;
                        filePreview.style.display = 'block';
                    }
                    reader.readAsDataURL(file);
                } else {
                    filePreview.style.display = 'block';
                    previewImage.style.display = 'none';
                }
            } else {
                filePreview.style.display = 'none';
            }
        });
    }
    
    // Set minimum date/time to current time
    const dateInput = document.getElementById('auctionEndDate');
    if (dateInput) {
        const now = new Date();
        const formattedNow = now.toISOString().slice(0, 16);
        dateInput.min = formattedNow;
    }
    
    // Form validation feedback
    const form = document.querySelector('.edit-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            let valid = true;
            const requiredFields = form.querySelectorAll('[required]');
            
            // Clear previous error styles
            requiredFields.forEach(field => {
                field.style.borderColor = '#e8e1d5';
            });
            
            // Check each required field
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = '#dc3545';
                    valid = false;
                }
            });
            
            // Check end date
            const endDateInput = document.getElementById('auctionEndDate');
            if (endDateInput && endDateInput.value) {
                const selectedDate = new Date(endDateInput.value);
                const now = new Date();
                
                if (selectedDate <= now) {
                    alert('End date must be in the future');
                    endDateInput.style.borderColor = '#dc3545';
                    valid = false;
                }
            }
            
            if (!valid) {
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            const submitBtn = form.querySelector('.submit-btn');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating Auction...';
                submitBtn.disabled = true;
            }
        });
    }
});
</script>

<main>
    <div class="edit-container">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-edit"></i> Edit Auction</h1>
            <p>Update your auction details below. All changes will be reflected immediately.</p>
        </div>
        
        <!-- Auction Info Card -->
        <div class="auction-info">
            <h3><i class="fas fa-info-circle"></i> Auction Information</h3>
            <div class="auction-meta">
                <div class="meta-item">
                    <i class="fas fa-hashtag"></i>
                    <span>Auction ID: <strong>#<?php echo $auction['id']; ?></strong></span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-user"></i>
                    <span>Created by: <strong><?php echo htmlspecialchars($auction['userName']); ?></strong></span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-tags"></i>
                    <span>Current Category: <strong><?php echo htmlspecialchars($auction['categoryName']); ?></strong></span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Original End Date: <strong><?php echo date('F j, Y \a\t g:i A', strtotime($auction['endDate'])); ?></strong></span>
                </div>
            </div>
        </div>
        
        <!-- Success/Error Messages -->
        <?php if (isset($success) && !empty($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong>Success!</strong> <?php echo $success; ?>
                </div>
            </div>
        <?php elseif (isset($error) && !empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>Please fix the following errors:</strong><br>
                    <?php echo $error; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Edit Form -->
        <form method="POST" action="" enctype="multipart/form-data" class="edit-form" id="editForm">
            <!-- Title -->
            <div class="form-group">
                <label for="title" class="required">
                    <i class="fas fa-heading"></i> Auction Title
                </label>
                <input type="text" 
                       id="title" 
                       name="title" 
                       placeholder="Enter a descriptive title for your auction"
                       value="<?php echo htmlspecialchars($auction['title']); ?>"
                       required
                       minlength="5">
                <small style="color: #666; font-size: 0.85rem;">Minimum 5 characters</small>
            </div>
            
            <!-- Description -->
            <div class="form-group">
                <label for="description" class="required">
                    <i class="fas fa-align-left"></i> Description
                </label>
                <textarea id="description" 
                          name="description" 
                          placeholder="Describe your vehicle in detail..."
                          required
                          minlength="20"><?php echo htmlspecialchars($auction['description']); ?></textarea>
                <small style="color: #666; font-size: 0.85rem;">Minimum 20 characters</small>
            </div>
            
            <!-- Category & End Date Row -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
                <!-- Category -->
                <div class="form-group">
                    <label for="category" class="required">
                        <i class="fas fa-tags"></i> Category
                    </label>
                    <select id="category" name="category" required>
                        <option value="">Select a category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"
                                <?php echo ($category['id'] == $auction['categoryId']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- End Date -->
                <div class="form-group">
                    <label for="auctionEndDate" class="required">
                        <i class="fas fa-calendar-times"></i> New End Date/Time
                    </label>
                    <input type="datetime-local" 
                           id="auctionEndDate" 
                           name="auctionEndDate" 
                           value="<?php echo date('Y-m-d\TH:i', strtotime($auction['endDate'])); ?>"
                           required>
                    <small style="color: #666; font-size: 0.85rem;">Must be in the future</small>
                </div>
            </div>
            
            <!-- Current Image -->
            <?php if ($auction['image']): ?>
                <div class="form-group">
                    <label>
                        <i class="fas fa-image"></i> Current Auction Image
                    </label>
                    <div class="current-image">
                        <img src="<?php echo htmlspecialchars($auction['image']); ?>" 
                             alt="Current Auction Image"
                             onerror="this.src='car.png'">
                        <p>Current image will be kept unless replaced</p>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- New Image Upload -->
            <div class="form-group">
                <label>
                    <i class="fas fa-camera"></i> Replace Auction Image (Optional)
                </label>
                <div class="file-upload">
                    <input type="file" 
                           id="image" 
                           name="image" 
                           accept="image/*">
                    <div class="file-upload-label">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <div>
                            <span>Click to upload new vehicle image</span><br>
                            <small>PNG, JPG, GIF, WEBP up to 5MB</small>
                        </div>
                    </div>
                </div>
                <div id="filePreview" class="file-preview">
                    <img id="previewImage" src="" alt="Preview">
                    <p id="fileName" style="margin-top: 10px; font-size: 0.9rem;"></p>
                </div>
                <small style="color: #666; font-size: 0.85rem;">Leave empty to keep current image</small>
            </div>
            
            <!-- Form Actions -->
            <div class="form-actions">
                <button type="submit" class="submit-btn" id="submitButton">
                    <i class="fas fa-save"></i> Update Auction
                </button>
                <a href="deleteAuction.php" class="cancel-btn">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
        
        <!-- Back Link -->
        <div style="text-align: center; margin-top: 30px;">
            <a href="auction.php?id=<?php echo $auctionId; ?>" class="back-link">
                <i class="fas fa-eye"></i> View Auction Page
            </a>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>