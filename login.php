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
                
                if ($user['role'] === 'ADMIN') {
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
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'USER')");
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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            height: 100vh;
            overflow: hidden;
        }
        
        .login-wrapper {
            display: flex;
            height: 100vh;
        }

        /* Left Panel - Brand Section */
        .brand-panel {
            flex: 1;
            background: linear-gradient(135deg, #132365ff 0%, #4b59a3ff 100%);
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px;
            position: relative;
            overflow: hidden;
        }

        .brand-panel::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 30s linear infinite;
        }

        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .brand-content {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 500px;
        }

        .brand-icon {
            font-size: 5rem;
            margin-bottom: 30px;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .brand-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .brand-subtitle {
            font-size: 1.3rem;
            margin-bottom: 40px;
            opacity: 0.95;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            margin-top: 50px;
        }

        .feature-item {
            text-align: center;
            padding: 20px;
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            backdrop-filter: blur(10px);
            transition: all 0.3s;
        }

        .feature-item:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-5px);
        }

        .feature-item i {
            font-size: 2.5rem;
            margin-bottom: 15px;
            display: block;
        }

        .feature-item h4 {
            font-size: 1.1rem;
            margin-bottom: 8px;
        }

        .feature-item p {
            font-size: 0.9rem;
            opacity: 0.9;
            margin: 0;
        }

        /* Right Panel - Form Section */
        .form-panel {
            flex: 1;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            overflow-y: auto;
        }

        .form-container {
            width: 100%;
            max-width: 450px;
        }

        .form-header {
            margin-bottom: 40px;
        }

        .form-header h2 {
            color: #132365ff;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .form-header p {
            color: #666;
            font-size: 1rem;
        }

        .nav-tabs {
            border: none;
            margin-bottom: 30px;
            display: flex;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 5px;
        }

        .nav-tabs .nav-link {
            flex: 1;
            border: none;
            color: #666;
            font-weight: 600;
            padding: 12px 24px;
            transition: all 0.3s;
            border-radius: 8px;
            text-align: center;
        }

        .nav-tabs .nav-link:hover {
            color: #132365ff;
            background: rgba(19, 35, 101, 0.05);
        }

        .nav-tabs .nav-link.active {
            color: white;
            background: linear-gradient(135deg, #132365ff 0%, #4b59a3ff 100%);
            box-shadow: 0 4px 15px rgba(19, 35, 101, 0.3);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            display: block;
        }

        .input-group {
            position: relative;
        }

        .input-group-text {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-right: none;
            color: #666;
        }

        .form-control {
            border-left: none;
            padding: 12px 15px;
            font-size: 0.95rem;
        }

        .form-control:focus {
            border-color: #132365ff;
            box-shadow: 0 0 0 0.2rem rgba(19, 35, 101, 0.1);
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
            transition: color 0.3s;
        }

        .password-toggle:hover {
            color: #132365ff;
        }

        .btn-primary {
            background: linear-gradient(135deg, #132365ff 0%, #4b59a3ff 100%);
            border: none;
            padding: 14px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s;
            width: 100%;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(19, 35, 101, 0.4);
        }

        .btn-link {
            color: #132365ff;
            text-decoration: none;
            font-weight: 500;
        }

        .btn-link:hover {
            color: #4b59a3ff;
            text-decoration: underline;
        }

        .alert {
            border-radius: 10px;
            border: none;
            padding: 15px;
        }

        .alert-danger {
            background: #fee;
            color: #c33;
        }

        .alert-success {
            background: #efe;
            color: #3c3;
        }

        .divider {
            text-align: center;
            margin: 20px 0;
            position: relative;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #dee2e6;
        }

        .divider span {
            background: white;
            padding: 0 15px;
            position: relative;
            color: #666;
            font-size: 0.9rem;
        }

        .guest-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #e0e0e0;
        }

        /* Responsive */
        @media (max-width: 968px) {
            .login-wrapper {
                flex-direction: column;
            }

            .brand-panel {
                padding: 40px 20px;
                min-height: 40vh;
            }

            .brand-title {
                font-size: 2rem;
            }

            .brand-subtitle {
                font-size: 1rem;
            }

            .features-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }

            .form-panel {
                padding: 30px 20px;
            }

            .feature-item {
                padding: 15px;
            }

            .feature-item i {
                font-size: 2rem;
            }
        }

        @media (max-width: 576px) {
            .features-grid {
                grid-template-columns: 1fr;
            }

            .brand-icon {
                font-size: 3.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <!-- Left Panel - Brand -->
        <div class="brand-panel">
            <div class="brand-content">
                <div class="brand-icon">
                    <i class="fas fa-map-marked-alt"></i>
                </div>
                <h1 class="brand-title">Tourism Guide</h1>
                <p class="brand-subtitle">Discover Amazing Destinations in Ormoc City</p>
                
                <div class="features-grid">
                    <div class="feature-item">
                        <i class="fas fa-map-pin"></i>
                        <h4>Discover</h4>
                        <p>Tourist Spots</p>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-route"></i>
                        <h4>Navigate</h4>
                        <p>Best Routes</p>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-star"></i>
                        <h4>Review</h4>
                        <p>Share Experience</p>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-compass"></i>
                        <h4>Explore</h4>
                        <p>Plan Journey</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Panel - Forms -->
        <div class="form-panel">
            <div class="form-container">
                <div class="form-header">
                    <h2>Welcome Back!</h2>
                    <p>Please login to your account or create a new one</p>
                </div>

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

                <!-- Tabs -->
                <ul class="nav nav-tabs" id="authTabs" role="tablist">
                    <li class="nav-item flex-fill" role="presentation">
                        <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </button>
                    </li>
                    <li class="nav-item flex-fill" role="presentation">
                        <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button">
                            <i class="fas fa-user-plus"></i> Register
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="authTabsContent">
                    <!-- Login Tab -->
                    <div class="tab-pane fade show active" id="login" role="tabpanel">
                        <form method="POST">
                            <div class="form-group">
                                <label for="username" class="form-label">
                                    <i class="fas fa-user"></i> Username
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           placeholder="Enter your username" required autofocus>
                                </div>
                            </div>

                            <div class="form-group">
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

                            <button type="submit" name="login" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt"></i> Login to Account
                            </button>
                        </form>

                        <div class="divider">
                            <span>or</span>
                        </div>

                        <div class="text-center">
                            <p class="mb-0">Don't have an account? 
                                <a href="#" class="btn-link" onclick="document.getElementById('register-tab').click(); return false;">
                                    Create one now
                                </a>
                            </p>
                        </div>
                    </div>

                    <!-- Register Tab -->
                    <div class="tab-pane fade" id="register" role="tabpanel">
                        <form method="POST">
                            <div class="form-group">
                                <label for="reg_username" class="form-label">
                                    <i class="fas fa-user"></i> Username
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="reg_username" name="reg_username" 
                                           placeholder="Choose a username" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="reg_email" class="form-label">
                                    <i class="fas fa-envelope"></i> Email Address
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="reg_email" name="reg_email" 
                                           placeholder="your@email.com" required>
                                </div>
                            </div>

                            <div class="form-group">
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

                            <button type="submit" name="register" class="btn btn-primary">
                                <i class="fas fa-user-plus"></i> Create Account
                            </button>
                        </form>

                        <div class="divider">
                            <span>or</span>
                        </div>

                        <div class="text-center">
                            <p class="mb-0">Already have an account? 
                                <a href="#" class="btn-link" onclick="document.getElementById('login-tab').click(); return false;">
                                    Login here
                                </a>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="guest-link">
                    <a href="index.php" class="btn-link">
                        <i class="fas fa-arrow-left"></i> Continue as Guest
                    </a>
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