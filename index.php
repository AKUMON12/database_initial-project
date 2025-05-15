<?php
// File paths for our data storage
define('USERS_FILE', 'data/users.json');
define('STUDENTS_FILE', 'data/students.json');
define('NOTES_FILE', 'data/notes.json');

// Create data directory if it doesn't exist
if (!file_exists('data')) {
    mkdir('data', 0777, true);
}

// Initialize users file if it doesn't exist
if (!file_exists(USERS_FILE)) {
    // Create admin user with password 'admin123'
    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $users = [
        [
            'id' => 1,
            'username' => 'admin',
            'password' => $hashedPassword,
            'role' => 'admin',
            'created_at' => date('Y-m-d H:i:s')
        ]
    ];
    file_put_contents(USERS_FILE, json_encode($users, JSON_PRETTY_PRINT));
}

// Initialize students file if it doesn't exist
if (!file_exists(STUDENTS_FILE)) {
    file_put_contents(STUDENTS_FILE, json_encode([], JSON_PRETTY_PRINT));
}

// Initialize notes file if it doesn't exist
if (!file_exists(NOTES_FILE)) {
    file_put_contents(NOTES_FILE, json_encode([], JSON_PRETTY_PRINT));
}

// Check if admin user exists, if not create one
$users = json_decode(file_get_contents(USERS_FILE), true) ?: [];

$admin_exists = false;
foreach ($users as $user) {
    if ($user['username'] === 'admin') {
        $admin_exists = true;
        break;
    }
}

if (!$admin_exists) {
    $admin_username = 'admin';
    $admin_password = 'admin123';
    $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
    $admin_role = 'admin';
    
    // Generate new ID
    $max_id = 0;
    foreach ($users as $user) {
        if ($user['id'] > $max_id) {
            $max_id = $user['id'];
        }
    }
    
    // Create admin user
    $admin_user = [
        'id' => $max_id + 1,
        'username' => $admin_username,
        'password' => $hashed_password,
        'role' => $admin_role,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // Add to array and save
    $users[] = $admin_user;
    file_put_contents(USERS_FILE, json_encode($users, JSON_PRETTY_PRINT));
}

// Start session
session_start();

// Process login
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Validate required fields
    if (empty($username) || empty($password)) {
        $error_message = 'Username and password are required';
    } else {
        // Load users from file
        $users = json_decode(file_get_contents(USERS_FILE), true) ?: [];
        
        // Find user by username
        $user = null;
        foreach ($users as $u) {
            if ($u['username'] === $username) {
                $user = $u;
                break;
            }
        }
        
        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Redirect based on role
            if ($user['role'] === 'admin') {
                header('Location: admin.php');
            } else {
                header('Location: user.php');
            }
            exit;
        } else {
            $error_message = 'Invalid username or password';
        }
    }
}

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    // Redirect based on role
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin.php');
    } else {
        header('Location: user.php');
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Student Information System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>Student Information System</h1>
            <p>Manage students, users, and notes efficiently</p>
        </div>
    </div>
    
    <div class="container">
        <div class="card login-container">
            <div class="login-logo">
                <h1>Welcome Back</h1>
                <p>Please login to continue</p>
            </div>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="post">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" name="login" class="btn btn-primary" style="width: 100%;">Login</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Student Information System. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
