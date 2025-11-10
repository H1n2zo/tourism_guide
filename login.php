<?php
require_once 'config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        $username = sanitizeInput($_POST['username']);
        $password = $_POST['password'];
        
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                if ($user['role'] === 'admin') {
                    header('Location: admin/dashboard.php');
                } else {
                    header('Location: index.php');
                }
                exit();
            } else {
                $error = 'Invalid username or password';
            }
        } else {
            $error = 'Invalid username or password';
        }
        $stmt->close();
        $conn->close();
    } elseif (isset($_POST['register'])) {
        $username = sanitizeInput($_POST['reg_username']);
        $email = sanitizeInput($_POST['reg_email']);
        $password = password_hash($_POST['reg_password'], PASSWORD_DEFAULT);
        
        $conn = getDBConnection();
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
        $stmt->bind_param("sss", $username, $email, $password);
        
        if ($stmt->execute()) {
            $success = 'Registration successful! Please login.';
        } else {
            $error = 'Username already exists';
        }
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Tourism Guide System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #132365ff 0%, #4b59a3ff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            max-width: 500px;
            margin: 50px auto;
            width: 100%;
        }
        
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, #132365ff 0%, #4b59a3ff 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
            border-bottom: none;
        }
        
        .brand {
            font-size: 3rem;
            margin-bottom: 10px;
        }
        
        .card-header h3 {
            margin: 10px 0 5px;
            font-weight: 600;
        }
        
        .card-header p {
            opacity: 0.9;
            font-size: 0.95rem;
        }
        
        .features {
            display: flex;
            justify-content: space-around;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.2);
        }
        
        .feature-item {
            text-align: center;
            font-size: 0.85rem;
        }
        
        .feature-item i {
            display: block;
            font-size: 1.5rem;
            margin-bottom: 5px;
        }
        
        .nav-tabs {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .nav-tabs .nav-link {
            border: none;
            color: #666;
            font-weight: 500;
            padding: 15px 30px;
            transition: all 0.3s;
        }
        
        .nav-tabs .nav-link:hover {
            color: #132365ff;
        }
        
        .nav-tabs .nav-link.active {
            color: #132365ff;
            background: none;
            border-bottom: 3px solid #132365ff;
        }
        
        .card-body {
            padding: 30px;
        }
        
        .input-group-text {
            background: #f8f9fa;
            border-right: none;
        }
        
        .form-control {
            border-left: none;
            padding: 12px;
        }
        
        .form-control:focus {
            box-shadow: none;
            border-color: #dee2e6;
        }
        
        .password-wrapper {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            z-index: 10;
        }
        
        .password-toggle:hover {
            color: #132365ff;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #132365ff 0%, #4b59a3ff 100%);
            border: none;
            padding: 12px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(19, 35, 101, 0.4);
        }
        
        .btn-link {
            color: #132365ff;
            text-decoration: none;
        }
        
        .btn-link:hover {
            color: #4b59a3ff;
        }
        
        .alert {
            border-radius: 10px;
        }
        
        a {
            color: #132365ff;
            text-decoration: none;
        }
        
        a:hover {
            color: #4b59a3ff;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="brand"><i class="fas fa-map-marked-alt"></i></div>
                        <h3>Tourism Guide</h3>
                        <p class="mb-0">Explore Amazing Destinations</p>
                    </div>
                    
                    <div class="features d-none d-md-flex">
                        <div class="feature-item">
                            <i class="fas fa-map-pin"></i>
                            <span>Discover Tourist Spots</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-route"></i>
                            <span>Find Best Routes</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-star"></i>
                            <span>Read Reviews</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-compass"></i>
                            <span>Plan Your Journey</span>
                        </div>
                    </div>
                </div>

                <ul class="nav nav-tabs justify-content-center" id="authTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button">
                            <i class="fas fa-user-plus"></i> Register
                        </button>
                    </li>
                </ul>

                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Tab Content -->
                    <div class="tab-content" id="authTabsContent">
                        <!-- Login Tab -->
                        <div class="tab-pane fade show active" id="login" role="tabpanel">
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="username" class="form-label">
                                        <i class="fas fa-user"></i> Username
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" id="username" name="username" 
                                               placeholder="Enter your username" required autofocus>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">
                                        <i class="fas fa-lock"></i> Password
                                    </label>
                                    <div class="password-wrapper">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            <input type="password" class="form-control" id="password" name="password" 
                                                   placeholder="Enter your password" required>
                                        </div>
                                        <i class="fas fa-eye password-toggle" onclick="togglePassword('password')"></i>
                                    </div>
                                </div>

                                <button type="submit" name="login" class="btn btn-primary w-100 mb-3">
                                    <i class="fas fa-sign-in-alt"></i> Login
                                </button>
                            </form>

                            <div class="text-center text-muted">
                                <small>Don't have an account? <a href="#" onclick="document.getElementById('register-tab').click(); return false;">Register here</a></small>
                            </div>
                        </div>

                        <!-- Register Tab -->
                        <div class="tab-pane fade" id="register" role="tabpanel">
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="reg_username" class="form-label">
                                        <i class="fas fa-user"></i> Username
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" id="reg_username" name="reg_username" 
                                               placeholder="Choose a username" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="reg_email" class="form-label">
                                        <i class="fas fa-envelope"></i> Email
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        <input type="email" class="form-control" id="reg_email" name="reg_email" 
                                               placeholder="your@email.com" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="reg_password" class="form-label">
                                        <i class="fas fa-lock"></i> Password
                                    </label>
                                    <div class="password-wrapper">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            <input type="password" class="form-control" id="reg_password" name="reg_password" 
                                                   placeholder="Create a strong password" required minlength="6">
                                        </div>
                                        <i class="fas fa-eye password-toggle" onclick="togglePassword('reg_password')"></i>
                                    </div>
                                    <small class="text-muted">Minimum 6 characters</small>
                                </div>

                                <button type="submit" name="register" class="btn btn-primary w-100 mb-3">
                                    <i class="fas fa-user-plus"></i> Create Account
                                </button>
                            </form>

                            <div class="text-center text-muted">
                                <small>Already have an account? <a href="#" onclick="document.getElementById('login-tab').click(); return false;">Login here</a></small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="index.php" class="btn btn-link">
                            <i class="fas fa-arrow-left"></i> Continue as Guest
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const toggle = field.parentElement.parentElement.querySelector('.password-toggle');
            
            if (field.type === 'password') {
                field.type = 'text';
                toggle.classList.remove('fa-eye');
                toggle.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                toggle.classList.remove('fa-eye-slash');
                toggle.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>