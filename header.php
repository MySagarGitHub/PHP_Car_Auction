<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Car Auction</title>
    <link rel="stylesheet" href="/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Georgia&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="top-banner">
        <div class="top-banner-left">
            <a href="#"><i class="fas fa-phone"></i> 9847367785</a>
            <a href="#"><i class="fas fa-envelope"></i> support@carbuy.com</a>
            <a href="#"><i class="fas fa-clock"></i> Live Auctions 24/7</a>
        </div>
        <div class="top-banner-right">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="profile.php"><i class="fas fa-user"></i> My Account</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <?php else: ?>
                <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                <a href="register.php"><i class="fas fa-user-plus"></i> Register</a>
            <?php endif; ?>
        </div>
    </div>
    
    <header>
        <h1>
            <span class="C">C</span>
            <span class="a">a</span>
            <span class="r">r</span>
            <span class="b">b</span>
            <span class="u">u</span>
            <span class="y">y</span>
            <span class="auctions"> AUCTIONS</span>
        </h1>
        
        <form action="search.php" method="GET" class="cookbook-search">
            <input type="text" name="search" placeholder="Search for a car, make, or model..." />
            <button type="submit"><i class="fas fa-search"></i> </button>
        </form>
    </header>

    <nav>
        <ul class="navbar">
            <li><a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Home
            </a></li>
            <li><a href="category.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'category.php' ? 'active' : ''; ?>">
                <i class="fas fa-list"></i> Categories
            </a></li>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if (isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']): ?>
                    <li><a href="adminCategories.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'adminCategories.php' ? 'active' : ''; ?>">
                        <i class="fas fa-cog"></i> Manage Categories
                    </a></li>
                <?php else: ?>
                    <li><a href="addAuction.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'addAuction.php' ? 'active' : ''; ?>">
                        <i class="fas fa-plus-circle"></i> Add Auction
                    </a></li>
                    <li><a href="deleteAuction.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'deleteAuction.php' ? 'active' : ''; ?>">
                        <i class="fas fa-trash"></i> Delete Auction
                    </a></li>
                <?php endif; ?>
                <li><a href="logout.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'logout.php' ? 'active' : ''; ?>">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a></li>
            <?php else: ?>
                <li><a href="login.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'login.php' ? 'active' : ''; ?>">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a></li>
                <li><a href="register.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'register.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-plus"></i> Register
                </a></li>
            <?php endif; ?>
        </ul
    </nav>