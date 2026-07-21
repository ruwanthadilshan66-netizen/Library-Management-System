<?php
session_start();
require 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Please enter your username/email and password.";
    } else {
        // Use the correct column names: user_id, username, email, password, role
        $stmt = $conn->prepare("SELECT user_id, username, email, password, role FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            // Verify hashed password
            if ($password === $row['password']) {
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];
                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Invalid password.";
            }
        } else {
            $error = "User not found.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login · Library System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="css/index.css" />
</head>
<body>

    <div class="orb orb--1"></div>
    <div class="orb orb--2"></div>
    <div class="orb orb--3"></div>

    <div class="login-card">

        <div class="row g-0">

            <div class="col-md-6 slider-container">

                <div class="slides-wrapper" id="slidesWrapper">
                    <div class="slide">
                        <img src="images/cartoon-bookshop-character-with-glasses-and-books-on-shelves-png.webp" alt="Library bookshelf" />
                    </div>
                    <div class="slide">
                        <img src="images/images.jpeg" alt="Library interior" />
                    </div>
                    <div class="slide">
                        <img src="images/imagesNew.jpeg" alt="Reading space" />
                    </div>
                </div>

                <button class="slider-btn prev" id="prevBtn"><i class="fas fa-chevron-left"></i></button>
                <button class="slider-btn next" id="nextBtn"><i class="fas fa-chevron-right"></i></button>

                <div class="dots-container" id="dotsContainer">
                    <button class="dot active" data-index="0"></button>
                    <button class="dot" data-index="1"></button>
                    <button class="dot" data-index="2"></button>
                </div>

            </div>

            <div class="col-md-6 login-form">

                <div class="card-header-custom">
                    <div class="icon-wrapper">
                         <i class="fas fa-book-open"></i>
                    </div>
                    <h1>Welcome Back</h1>
                   
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert-custom">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <form method="post" autocomplete="off">

                    <div class="form-group">
                        <label for="username"><i class="fas fa-user"></i> Username or Email</label>
                        <div class="input-wrapper">
                            <span class="icon-left"><i class="fas fa-user"></i></span>
                            <input
                            type="text"
                            id="username"
                            name="username"
                            placeholder="Enter your username or email"
                            value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
                            required
                            />
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password"><i class="fas fa-lock"></i> Password</label>
                        <div class="input-wrapper">
                            <span class="icon-left"><i class="fas fa-lock"></i></span>
                            <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            required
                            />
                            <button
                            type="button"
                            class="toggle-password"
                            id="togglePassword"
                            aria-label="Toggle password visibility"
                            tabindex="-1"
                            >
                            <i class="fas fa-eye-slash"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-arrow-right-to-bracket"></i>
                    Sign In
                </button>

            </form>

            <div class="card-footer-custom">
                <p>Don't have an account? <a href="register.php">Create one</a></p>
            </div>

        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="js/index.js"></script>

</body>
</html>