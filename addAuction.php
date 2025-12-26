<?php
session_start();

// Include functions.php for common functions
include_once 'functions.php';

// Include configuration if it exists
if (file_exists('config.php')) {
    include_once 'config.php';
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Define database credentials if not already defined
if (!defined('DB_HOST')) {
    define('DB_HOST', 'db');
    define('DB_NAME', 'assignment1');
    define('DB_USER', 'user');
    define('DB_PASS', 'password');
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$stmt = $pdo->query("SELECT id, name FROM category ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$title = $description = $categoryId = $endDate = '';
$success = $error = '';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $categoryId = $_POST['category'];
    $endDate = $_POST['auctionEndDate'];
    $userId = $_SESSION['user_id'];

    // Debug: Uncomment to see what data is received
    /*
    echo "<pre>Received Data:\n";
    echo "Title: $title\n";
    echo "Description length: " . strlen($description) . "\n";
    echo "Category: $categoryId\n";
    echo "End Date: $endDate\n";
    echo "User ID: $userId\n";
    echo "</pre>";
    */

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
        $minEndDate = clone $now;
        $minEndDate->modify('+24 hours'); // Minimum 24 hours from now
        
        if ($endDateTime <= $now) {
            $validationErrors[] = "End date must be in the future";
        } elseif ($endDateTime <= $minEndDate) {
            $validationErrors[] = "Auction must run for at least 24 hours";
        }
    }
    
    // File upload validation
    $imagePath = null;
    $imageUploaded = false;
    
    if (empty($_FILES['image']['name'])) {
        $validationErrors[] = "Please select an auction image";
    } elseif ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
       $validationErrors[] = "File upload error: " . getUploadError($_FILES['image']['error']);
    } else {
        // Process file upload
        $targetDir = "images/auctions/";
        if (!file_exists($targetDir)) {
            if (!mkdir($targetDir, 0777, true)) {
                $validationErrors[] = "Failed to create upload directory";
            }
        }
        
        if (empty($validationErrors)) {
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
            }
        }
    }
    
    // If no validation errors, process the form
    if (empty($validationErrors)) {
        // Try to upload the image
        if (move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
            $imageUploaded = true;
        } else {
            $validationErrors[] = "Failed to upload image. Please try again.";
        }
        
        if ($imageUploaded) {
            try {
                // Check what columns exist in your table
                // Try without created_at first
                $stmt = $pdo->prepare("
                    INSERT INTO auction (title, description, categoryId, endDate, userId, image) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                $result = $stmt->execute([$title, $description, $categoryId, $endDate, $userId, $imagePath]);
                
                if ($result) {
                    $auctionId = $pdo->lastInsertId();
                    $success = "Auction created successfully! Your auction ID is: " . $auctionId;
                    
                    // Reset form after successful submission
                    $title = $description = $categoryId = $endDate = '';
                    
                    // Clear any previous errors
                    $error = '';
                } else {
                    // Delete uploaded image if insert failed
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                    $error = "Failed to save auction to database.";
                }
                
            } catch (PDOException $e) {
                // Delete uploaded image if database insert fails
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
                $error = "Database error: " . $e->getMessage();
                
                // Debug: Show SQL error
                /*
                $error .= "<br><small>SQL Error Info: " . $stmt->errorInfo()[2] . "</small>";
                */
            }
        }
    } else {
        // Combine validation errors
        $error = implode("<br>", $validationErrors);
    }
}

// Function to get upload error messages
function getUploadError($errorCode) {
    $errors = [
        UPLOAD_ERR_INI_SIZE => 'File is too large (server limit)',
        UPLOAD_ERR_FORM_SIZE => 'File is too large (form limit)',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'File upload stopped by extension',
    ];
    
    return isset($errors[$errorCode]) ? $errors[$errorCode] : 'Unknown upload error';
}

include 'header.php';
?>

<!-- CSS remains the same -->
<style>
/* AUCTION FORM STYLES */
.auction-container {
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
.auction-form {
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

/* FILE UPLOAD STYLE */
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
    padding: 40px 20px;
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
    font-size: 3rem;
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

/* SUBMIT BUTTON */
.form-actions {
    display: flex;
    justify-content: center;
    margin-top: 20px;
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
    min-width: 200px;
    justify-content: center;
}

.submit-btn:hover {
    background: linear-gradient(to bottom, #218838, #1e7e34);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
}

.submit-btn:active {
    transform: translateY(0);
}

/* DELETE SECTION */
.delete-section {
    margin-top: 50px;
    padding: 30px;
    background: linear-gradient(135deg, #fff5f5, #ffe6e6);
    border-radius: 10px;
    border: 1px solid #f8d7da;
    text-align: center;
}

.delete-section h2 {
    color: #721c24;
    margin-bottom: 15px;
    font-size: 1.4rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.delete-section p {
    color: #856404;
    margin-bottom: 20px;
    font-size: 0.95rem;
}

.delete-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 25px;
    background: linear-gradient(to bottom, #dc3545, #c82333);
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.delete-btn:hover {
    background: linear-gradient(to bottom, #c82333, #bd2130);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

/* FORM TIPS */
.form-tips {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #8B4513;
    margin-top: 10px;
}

.form-tips h3 {
    color: #333;
    font-size: 1rem;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-tips ul {
    list-style: none;
    padding-left: 0;
}

.form-tips li {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 8px;
    padding-left: 20px;
    position: relative;
}

.form-tips li:before {
    content: '✓';
    color: #28a745;
    position: absolute;
    left: 0;
    font-weight: bold;
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .auction-container {
        padding: 20px;
        margin: 0 15px;
    }
    
    .page-header h1 {
        font-size: 1.8rem;
        flex-direction: column;
        gap: 5px;
    }
    
    .submit-btn {
        width: 100%;
    }
}

@media (max-width: 480px) {
    .auction-container {
        padding: 15px;
    }
    
    .alert {
        flex-direction: column;
        text-align: center;
        gap: 8px;
    }
    
    .delete-section {
        padding: 20px 15px;
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
    
    // Set minimum date/time to current time + 24 hours
    const dateInput = document.getElementById('auctionEndDate');
    if (dateInput) {
        const now = new Date();
        // Add 24 hours minimum for auction duration
        const minDate = new Date(now.getTime() + (24 * 60 * 60 * 1000));
        const formattedMin = minDate.toISOString().slice(0, 16);
        dateInput.min = formattedMin;
        
        // Set default to 7 days from now
        const defaultDate = new Date(now.getTime() + (7 * 24 * 60 * 60 * 1000));
        const formattedDefault = defaultDate.toISOString().slice(0, 16);
        
        // Only set default if not already set (to preserve form data on error)
        if (!dateInput.value) {
            dateInput.value = formattedDefault;
        }
    }
    
    // Form validation feedback
    const form = document.querySelector('.auction-form');
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
                const minDate = new Date(now.getTime() + (24 * 60 * 60 * 1000));
                
                if (selectedDate <= now) {
                    alert('End date must be in the future');
                    endDateInput.style.borderColor = '#dc3545';
                    valid = false;
                } else if (selectedDate <= minDate) {
                    alert('Auction must run for at least 24 hours');
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
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Auction...';
                submitBtn.disabled = true;
            }
        });
    }
});
</script>

<main>
    <div class="auction-container">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-gavel"></i> Create New Auction</h1>
            <p>List your vehicle for auction. Fill in all required details below.</p>
        </div>
        
        <!-- Success/Error Messages -->
        <?php if (isset($success) && !empty($success)): ?>
            <div class="alert alert-success" id="successMessage">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong>Success!</strong> <?php echo $success; ?>
                    <br>
                    <small>Your auction is now live. <a href="index.php" style="color: #155724; font-weight: 600; text-decoration: underline;">Browse all auctions</a> or <a href="deleteAuction.php" style="color: #155724; font-weight: 600; text-decoration: underline;">manage your auctions</a>.</small>
                </div>
            </div>
            
            <!-- Auto-hide success message after 10 seconds -->
            <script>
                setTimeout(function() {
                    const successMsg = document.getElementById('successMessage');
                    if (successMsg) {
                        successMsg.style.opacity = '0';
                        successMsg.style.transition = 'opacity 1s';
                        setTimeout(() => successMsg.style.display = 'none', 1000);
                    }
                }, 10000);
            </script>
        <?php elseif (isset($error) && !empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>Please fix the following errors:</strong><br>
                    <?php echo $error; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Auction Form -->
        <form method="POST" action="" enctype="multipart/form-data" class="auction-form" id="auctionForm">
            <!-- Title -->
            <div class="form-group">
                <label for="title" class="required">
                    <i class="fas fa-heading"></i> Auction Title
                </label>
                <input type="text" 
                       id="title" 
                       name="title" 
                       placeholder="Enter a descriptive title for your auction (e.g., '1970 Ford Mustang Boss 302')"
                       value="<?php echo htmlspecialchars($title); ?>"
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
                          placeholder="Describe your vehicle in detail. Include make, model, year, condition, modifications, service history, and any notable features..."
                          required
                          minlength="20"><?php echo htmlspecialchars($description); ?></textarea>
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
                                <?php echo ($categoryId == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- End Date -->
                <div class="form-group">
                    <label for="auctionEndDate" class="required">
                        <i class="fas fa-calendar-times"></i> Auction End Date/Time
                    </label>
                    <input type="datetime-local" 
                           id="auctionEndDate" 
                           name="auctionEndDate" 
                           value="<?php echo htmlspecialchars($endDate); ?>"
                           required>
                    <small style="color: #666; font-size: 0.85rem;">Auction must run for at least 24 hours</small>
                </div>
            </div>
            
            <!-- Image Upload -->
            <div class="form-group">
                <label class="required">
                    <i class="fas fa-camera"></i> Auction Image
                </label>
                <div class="file-upload">
                    <input type="file" 
                           id="image" 
                           name="image" 
                           accept="image/*" 
                           required>
                    <div class="file-upload-label">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <div>
                            <span>Click to upload vehicle image</span><br>
                            <small>PNG, JPG, GIF, WEBP up to 5MB</small>
                        </div>
                    </div>
                </div>
                <div id="filePreview" class="file-preview">
                    <img id="previewImage" src="" alt="Preview">
                    <p id="fileName" style="margin-top: 10px; font-size: 0.9rem;"></p>
                </div>
            </div>
            
            <!-- Form Tips -->
            <div class="form-tips">
                <h3><i class="fas fa-lightbulb"></i> Tips for a successful auction:</h3>
                <ul>
                    <li>Use clear, high-quality images showing all angles</li>
                    <li>Be honest about the vehicle's condition</li>
                    <li>Set a reasonable auction duration (7-14 days recommended)</li>
                    <li>Include service history and documentation if available</li>
                </ul>
            </div>
            
            <!-- Submit Button -->
            <div class="form-actions">
                <button type="submit" class="submit-btn" id="submitButton">
                    <i class="fas fa-plus-circle"></i> Create Auction
                </button>
            </div>
        </form>
        
        <!-- Delete Section -->
        <div class="delete-section">
            <h2><i class="fas fa-trash-alt"></i> Manage Your Auctions</h2>
            <p>Need to remove an existing auction? Visit the auction management page.</p>
            <a href="deleteAuction.php" class="delete-btn">
                <i class="fas fa-external-link-alt"></i> Manage Auctions
            </a>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>