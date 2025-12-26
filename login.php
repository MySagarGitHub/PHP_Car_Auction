<?php
session_start(); // MUST be at the very beginning, before any output

try {
    $pdo = new PDO("mysql:host=db;dbname=assignment1", "user", "password");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Initialize variables
$email = '';
$remember = false;
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields";
    } else {
        try {
            // MODIFIED: Added isAdmin to SELECT query
            $stmt = $pdo->prepare("SELECT id, email, password, name, isAdmin FROM user WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Use password_verify for hashed passwords
                if (password_verify($password, $user['password'])) {
                    // MODIFIED: Set admin status in session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['isAdmin'] = (bool)$user['isAdmin']; // ADDED: Set admin flag
                    
                    if ($remember) {
                        setcookie('user_email', $email, time() + (30 * 24 * 60 * 60), '/');
                    }
                    
                    $success = "Login successful! Redirecting...";
                    header("refresh:2;url=index.php");
                    exit;
                } else {
                    $error = "Invalid email or password";
                }
            } else {
                $error = "Invalid email or password";
            }
        } catch(PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

if (isset($_COOKIE['user_email'])) {
    $email = $_COOKIE['user_email'];
    $remember = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarBuy Auctions | Sign In</title>
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

        html {
            scroll-behavior: smooth;
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
            max-width: 450px;
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

        .login-card {
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
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-dark);
            margin: 30px 0 12px;
            letter-spacing: 0.5px;
        }

        .subtitle {
            color: var(--text-light);
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 35px;
        }

        .form-group {
            text-align: left;
            margin-bottom: 24px;
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
            padding: 14px 16px;
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
            margin-bottom: 30px;
            gap: 10px;
        }

        .checkbox-input {
            width: 18px;
            height: 18px;
            accent-color: var(--primary-color);
            cursor: pointer;
        }

        .checkbox-label {
            font-size: 0.9rem;
            color: var(--text-dark);
            cursor: pointer;
            font-weight: 500;
        }

        .login-button {
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
        }

        .login-button::before {
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

        .login-button:hover::before {
            width: 500px;
            height: 500px;
        }

        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(230, 57, 70, 0.35);
        }

        .login-button:active {
            transform: translateY(0);
        }

        .forgot-password {
            display: inline-block;
            text-align: center;
            margin-top: 18px;
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .forgot-password:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 32px 0;
            color: var(--text-light);
        }

        .divider::before,
        .divider::after {
            content: "";
            flex: 1;
            border-bottom: 1px solid var(--border-color);
        }

        .divider span {
            padding: 0 16px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .google-button {
            background: rgb(255, 255, 255) !important;
            color: var(--text-dark) !important;
            border: 2px solid var(--border-color) !important;
            margin-bottom: 24px;
        }

        .google-button:hover {
            border-color: var(--primary-color) !important;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1) !important;
        }

        .google-button i {
            margin-right: 8px;
            color: var(--primary-color);
        }

        .signup-link {
            text-align: center;
            font-size: 0.9rem;
            color: var(--text-light);
            margin-top: 24px;
        }

        .signup-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .signup-link a:hover {
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
        }

        /* Admin badge styling */
        .admin-badge {
            display: inline-block;
            background: linear-gradient(135deg, #8B4513 0%, #A0522D 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            margin-left: 10px;
            vertical-align: middle;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 0.8; }
            50% { opacity: 1; }
            100% { opacity: 0.8; }
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 40px 24px;
            }
            
            .welcome-text {
                font-size: 1.6rem;
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
        <div class="login-card">
            <div class="logo">Car<span>Buy</span></div>
            
            <h1 class="welcome-text">Welcome Back</h1>
            <p class="subtitle">Sign in to your CarBuy Auctions account</p>
            
            <?php
            if (!empty($error)) {
                echo '<div class="message error"><i class="fas fa-exclamation-circle"></i> ' . htmlspecialchars($error) . '</div>';
            }
            
            if (!empty($success)) {
                echo '<div class="message success"><i class="fas fa-check-circle"></i> ' . htmlspecialchars($success) . '</div>';
            }
            ?>
            
            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-input" 
                           placeholder="Enter your email" 
                           value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-input" 
                           placeholder="Enter your password" required>
                </div>
                
                <div class="checkbox-container">
                    <input type="checkbox" id="remember" name="remember" class="checkbox-input" 
                           <?php echo $remember ? 'checked' : ''; ?>>
                    <label for="remember" class="checkbox-label">Keep me signed in</label>
                </div>
                
                <button type="submit" class="login-button">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
                
                <a href="forgot-password.php" class="forgot-password">Forgot your password?</a>
                
                <div class="divider">
                    <span>or</span>
                </div>
                
                <button type="button" class="login-button google-button">
                    <i class="fab fa-google"></i> Sign in with Google
                </button>
                
                <p class="signup-link">
                    Don't have an account? <a href="register.php">Sign up now</a>
                </p>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('Please fill in all fields');
                return false;
            }
            
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>