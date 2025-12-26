<?php
session_start();

// Only allow existing admins to create new admins
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

$name = $email = '';
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $makeAdmin = isset($_POST['isAdmin']) ? 1 : 0;
    
    // Validation
    if (empty($name)) $errors[] = "Name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (empty($password)) $errors[] = "Password is required";
    if ($password !== $confirmPassword) $errors[] = "Passwords do not match";
    
    // Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }
    
    // Password strength (optional)
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    // Check if email exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM user WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = "Email already registered";
        }
    }
    
    // Create user if no errors
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO user (name, email, password, isAdmin) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $hashedPassword, $makeAdmin]);
        $success = "User registered successfully!";
        $name = $email = ''; // Clear form
    }
}

include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarBuy Auctions | Admin Registration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: rgb(230, 57, 70);
            --primary-dark: rgb(214, 40, 40);
            --primary-light: rgb(255, 107, 107);
            --primary-blue: rgb(29, 53, 87);
            --accent-gold: rgb(255, 209, 102);
            --light-bg: rgb(248, 249, 250);
            --text-dark: rgb(33, 37, 41);
            --text-light: rgb(108, 117, 125);
            --border-color: rgb(233, 236, 239);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-color) 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 500px;
            animation: slideInUp 0.6s ease-out;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .admin-card {
            background-color: rgb(255, 255, 255);
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 50px 40px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .logo {
            font-size: 28px;
            font-weight: 900;
            margin-bottom: 12px;
            letter-spacing: 1px;
        }

        .logo span {
            color: var(--primary-color);
        }

        .welcome-text {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--text-dark);
            margin: 20px 0 12px;
            letter-spacing: 0.5px;
        }

        .subtitle {
            color: var(--text-light);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 30px;
            background: rgba(139, 69, 19, 0.1);
            padding: 10px;
            border-radius: 8px;
            border-left: 4px solid #8B4513;
        }

        .form-group {
            text-align: left;
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background-color: rgba(248, 249, 250, 0.5);
            color: var(--text-dark);
        }

        .form-input::placeholder {
            color: var(--text-light);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            background-color: rgb(255, 255, 255);
            box-shadow: 0 0 0 4px rgba(230, 57, 70, 0.1);
        }

        .checkbox-container {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            gap: 10px;
            background: #f9f5f0;
            padding: 15px;
            border-radius: 8px;
            border: 2px dashed #e8e1d5;
        }

        .checkbox-input {
            width: 20px;
            height: 20px;
            accent-color: var(--primary-color);
            cursor: pointer;
        }

        .checkbox-label {
            font-size: 0.95rem;
            color: var(--text-dark);
            cursor: pointer;
            font-weight: 600;
            color: #8B4513;
        }

        .checkbox-label i {
            color: #8B4513;
            margin-right: 5px;
        }

        .admin-button {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: rgb(255, 255, 255);
            border: none;
            border-radius: 8px;
            padding: 14px 24px;
            font-size: 1rem;
            font-weight: 700;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
            margin-top: 10px;
        }

        .admin-button::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .admin-button:hover::before {
            width: 500px;
            height: 500px;
        }

        .admin-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(230, 57, 70, 0.35);
        }

        .back-link {
            display: inline-block;
            text-align: center;
            margin-top: 20px;
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .message {
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            text-align: center;
            font-size: 0.9rem;
            font-weight: 600;
            animation: slideInDown 0.5s ease-out;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
            margin-bottom: 15px;
        }

        .error-list {
            background-color: rgba(230, 57, 70, 0.1);
            color: rgb(180, 0, 0);
            border: 1px solid rgba(230, 57, 70, 0.3);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: left;
        }

        .error-list ul {
            padding-left: 20px;
            margin: 0;
        }

        .error-list li {
            margin-bottom: 5px;
        }

        @media (max-width: 480px) {
            .admin-card {
                padding: 40px 24px;
            }
            
            .welcome-text {
                font-size: 1.5rem;
            }

            .container {
                max-width: 100%;
            }

            .logo {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="admin-card">
            <div class="logo">Car<span>Buy</span></div>
            
            <h1 class="welcome-text">Admin Registration</h1>
            <p class="subtitle">
                <i class="fas fa-shield-alt"></i> You are creating a new user account. 
                Only administrators can access this page.
            </p>
            
            <?php if ($success): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="error-list">
                    <i class="fas fa-exclamation-circle"></i> 
                    <strong>Please fix the following errors:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="adminForm">
                <div class="form-group">
                    <label class="form-label" for="name">Full Name</label>
                    <input type="text" id="name" name="name" class="form-input" 
                           placeholder="Enter user's full name" 
                           value="<?php echo htmlspecialchars($name); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-input" 
                           placeholder="Enter user's email" 
                           value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-input" 
                           placeholder="Create a password (min. 6 characters)" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" 
                           placeholder="Confirm the password" required>
                </div>
                
                <div class="checkbox-container">
                    <input type="checkbox" id="isAdmin" name="isAdmin" class="checkbox-input" value="1">
                    <label for="isAdmin" class="checkbox-label">
                        <i class="fas fa-crown"></i> Make this user an administrator
                    </label>
                </div>
                
                <button type="submit" class="admin-button">
                    <i class="fas fa-user-plus"></i> Register User
                </button>
                
                <a href="index.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('adminForm').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            let errors = [];
            
            if (!name) errors.push('Name is required');
            if (!email) errors.push('Email is required');
            if (!password) errors.push('Password is required');
            if (password !== confirmPassword) errors.push('Passwords do not match');
            if (password.length < 6) errors.push('Password must be at least 6 characters');
            
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (email && !emailRegex.test(email)) errors.push('Please enter a valid email address');
            
            if (errors.length > 0) {
                e.preventDefault();
                alert('Please fix the following errors:\n\n' + errors.join('\n'));
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>

<?php include 'footer.php'; ?>