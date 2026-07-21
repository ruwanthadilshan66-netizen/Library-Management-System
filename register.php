<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } else {
        $check = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        if (!$check) {
            $error = "Database error: " . $conn->error;
        } else {
            $check->bind_param("ss", $username, $email);
            $check->execute();
            $check->store_result();
            if ($check->num_rows > 0) {
                $error = "Username or email already taken.";
            } else {
                
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'Librarian')");
                if (!$stmt) {
                    $error = "Prepare failed: " . $conn->error;
                } else {
                    $stmt->bind_param("sss", $username, $email, $password);
                    if ($stmt->execute()) {
                        header("Location: index.php?registered=1");
                        exit;
                    } else {
                        $error = "Registration failed: " . $stmt->error;
                    }
                    $stmt->close();
                }
            }
            $check->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register · Library System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="css/register.css" />
</head>
<body>

    <div class="orb orb--1"></div>
    <div class="orb orb--2"></div>
    <div class="orb orb--3"></div>

    <div class="register-card">

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

            <div class="col-md-6 register-form">

                <div class="card-header-custom">
                    <div class="icon-wrapper">
                       <i class="fas fa-book-open"></i>
                    </div>
                    <h1>Create Account</h1>
                    <p>Join our library management system</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert-custom">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <form method="post" autocomplete="off">

                    <div class="form-group">
                        <label for="username"><i class="fas fa-user"></i> Username</label>
                        <div class="input-wrapper">
                            <span class="icon-left"><i class="fas fa-user"></i></span>
                            <input
                            type="text"
                            id="username"
                            name="username"
                            placeholder="Choose a username"
                            value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
                            required
                            />
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> Email</label>
                        <div class="input-wrapper">
                            <span class="icon-left"><i class="fas fa-envelope"></i></span>
                            <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="you@example.com"
                            value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
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
                            placeholder="Min 6 characters"
                            required
                            minlength="6"
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

                <button type="submit" class="btn-register">
                    <i class="fas fa-arrow-right-to-bracket"></i>
                    Register
                </button>

            </form>

            <div class="card-footer-custom">
                <p>Already have an account? <a href="index.php">Sign in</a></p>
            </div>

        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="js/register.js"></script>

</body>
</html>