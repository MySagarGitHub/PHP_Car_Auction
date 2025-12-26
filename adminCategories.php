<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['isAdmin']) || !$_SESSION['isAdmin']) {
    header("Location: login.php");
    exit;
}

try {
    $pdo = new PDO("mysql:host=db;dbname=assignment1", "user", "password");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $name = $_POST['name'];
    if (empty($name)) {
        $error = "Category name is required.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO category (name) VALUES (?)");
        $stmt->execute([$name]);
        $success = "Category added successfully!";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit'])) {
    $id = (int)$_POST['id'];
    $name = $_POST['name'];
    if (empty($name)) {
        $error = "Category name is required.";
    } else {
        $stmt = $pdo->prepare("UPDATE category SET name = ? WHERE id = ?");
        $stmt->execute([$name, $id]);
        $success = "Category updated successfully!";
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM auction WHERE categoryId = ?");
    $stmt->execute([$id]);
    $auctionCount = $stmt->fetchColumn();

    if ($auctionCount > 0) {
        $error = "Cannot delete category '$id' because $auctionCount auction(s) are associated with it.";
    } else {
        $stmt = $pdo->prepare("DELETE FROM category WHERE id = ?");
        $stmt->execute([$id]);
        $success = "Category deleted successfully!";
        header("Location: adminCategories.php"); 
        exit;
    }
}

$editCategory = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM category WHERE id = ?");
    $stmt->execute([$id]);
    $editCategory = $stmt->fetch(PDO::FETCH_ASSOC);
}

$stmt = $pdo->query("SELECT * FROM category");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>

<main>
    <h1>Manage Categories</h1>
    
    <h2><?php echo $editCategory ? 'Edit Category' : 'Add Category'; ?></h2>
    <?php if (isset($success)): ?>
        <p style="color: green;"><?php echo $success; ?></p>
    <?php elseif (isset($error)): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>
    <form method="POST" action="">
        <?php if ($editCategory): ?>
            <input type="hidden" name="id" value="<?php echo $editCategory['id']; ?>">
        <?php endif; ?>
        <label for="name">Category Name:</label><br>
        <input type="text" id="name" name="name" value="<?php echo $editCategory ? htmlspecialchars($editCategory['name']) : ''; ?>" required><br><br>
        <input type="submit" name="<?php echo $editCategory ? 'edit' : 'add'; ?>" value="<?php echo $editCategory ? 'Update Category' : 'Add Category'; ?>">
        <?php if ($editCategory): ?>
            <a href="adminCategories.php">Cancel</a>
        <?php endif; ?>
    </form>

    <h2>Existing Categories</h2>
    <?php if (empty($categories)): ?>
        <p>No categories found.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($categories as $category): ?>
                <li>
                    <?php echo htmlspecialchars($category['name']); ?>
                    <a href="adminCategories.php?edit=<?php echo $category['id']; ?>">Edit</a>
                    <a href="adminCategories.php?delete=<?php echo $category['id']; ?>" onclick="return confirm('Are you sure?');">Delete</a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</main>

<?php  ?>