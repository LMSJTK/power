<?php
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $email = trim($_POST['email'] ?? '');

    // Validation
    if (empty($username) || empty($password)) {
        $error = 'Username and password are required.';
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $error = 'Username must be between 3 and 50 characters.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $db = getDB();

        // Check if username exists
        $stmt = $db->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->execute([$username]);

        if ($stmt->fetch()) {
            $error = 'Username already exists.';
        } else {
            // Create user
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, password_hash, email) VALUES (?, ?, ?)");

            if ($stmt->execute([$username, $password_hash, $email])) {
                $success = 'Account created successfully! You can now log in.';
            } else {
                $error = 'An error occurred. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Power: The Game</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1>Power: The Game</h1>
            <h2>Create Account</h2>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <p><a href="login.php">Click here to login</a></p>
            <?php else: ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" required
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="email">Email (optional):</label>
                        <input type="email" id="email" name="email"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password:</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>

                    <button type="submit" class="btn btn-primary">Register</button>
                </form>

                <p class="text-center">
                    Already have an account? <a href="login.php">Login here</a>
                </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
