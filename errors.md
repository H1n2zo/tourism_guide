# admin/routes.php
When adding or editing in admin/routes.php the description isnt used and results to 0 

# UI change
Change Route Finder from manually choosing destination from/to 
Into relying to routes table showing only routes saved data 
  `transport_mode` enum('jeepney','taxi','bus','van','tricycle','walking') NOT NULL,
  `distance_km` decimal(6,2) DEFAULT NULL,
  `estimated_time_minutes` int(11) DEFAULT NULL,
  `base_fare` decimal(8,2) DEFAULT NULL,
  `fare_per_km` decimal(8,2) DEFAULT NULL,
  `description` text DEFAULT NULL,

# minor changes login.html
i like this

            <div class="card-header">
                <div>
                    <div class="brand"><i class="fas fa-map-marked-alt"></i></div>
                    <h3>Tourism Guide</h3>
                    <p class="mb-0">Explore Amazing Destinations</p>
                </div>
                
                <div class="features d-none d-md-block">
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


            <!-- Tab Content -->
                <div class="tab-content" id="authTabsContent">
                    <!-- Login Tab -->
                    <div class="tab-pane fade show active" id="login" role="tabpanel">
                        <form method="POST" action="/login">
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

                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </button>
                        </form>

                        <div class="text-center text-muted">
                            <small>Don't have an account? <a href="#" onclick="document.getElementById('register-tab').click(); return false;">Register here</a></small>
                        </div>
                    </div>

                    <!-- Register Tab -->
                    <div class="tab-pane fade" id="register" role="tabpanel">
                        <form method="POST" action="/register">
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

                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="fas fa-user-plus"></i> Create Account
                            </button>
                        </form>

                        <div class="text-center text-muted">
                            <small>Already have an account? <a href="#" onclick="document.getElementById('login-tab').click(); return false;">Login here</a></small>
                        </div>