<?php
// Start session
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// File paths for our data storage
define('USERS_FILE', 'data/users.json');
define('STUDENTS_FILE', 'data/students.json');
define('NOTES_FILE', 'data/notes.json');

// Create data directory if it doesn't exist
if (!file_exists('data')) {
    mkdir('data', 0777, true);
}

// Initialize files if they don't exist
if (!file_exists(USERS_FILE)) {
    file_put_contents(USERS_FILE, json_encode([], JSON_PRETTY_PRINT));
}
if (!file_exists(STUDENTS_FILE)) {
    file_put_contents(STUDENTS_FILE, json_encode([], JSON_PRETTY_PRINT));
}
if (!file_exists(NOTES_FILE)) {
    file_put_contents(NOTES_FILE, json_encode([], JSON_PRETTY_PRINT));
}

// Handle student operations
$success_message = '';
$error_message = '';

// Add student
if (isset($_POST['add_student'])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $course = $_POST['course'] ?? '';
    $year_level = $_POST['year_level'] ?? '';
    
    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $error_message = 'First name, last name, and email are required';
    } else {
        // Load existing students
        $students = json_decode(file_get_contents(STUDENTS_FILE), true) ?: [];
        
        // Check if email already exists
        $email_exists = false;
        foreach ($students as $student) {
            if ($student['email'] === $email) {
                $email_exists = true;
                break;
            }
        }
        
        if ($email_exists) {
            $error_message = 'Email already exists';
        } else {
            // Generate new ID
            $max_id = 0;
            foreach ($students as $student) {
                if ($student['id'] > $max_id) {
                    $max_id = $student['id'];
                }
            }
            
            // Create new student
            $new_student = [
                'id' => $max_id + 1,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
                'date_of_birth' => $date_of_birth,
                'gender' => $gender,
                'course' => $course,
                'year_level' => $year_level,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Add to array and save
            $students[] = $new_student;
            file_put_contents(STUDENTS_FILE, json_encode($students, JSON_PRETTY_PRINT));
            
            $success_message = 'Student added successfully';
        }
    }
}

// Edit student
if (isset($_POST['edit_student'])) {
    $student_id = (int)$_POST['student_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $course = $_POST['course'] ?? '';
    $year_level = $_POST['year_level'] ?? '';
    
    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $error_message = 'First name, last name, and email are required';
    } else {
        // Load existing students
        $students = json_decode(file_get_contents(STUDENTS_FILE), true) ?: [];
        
        // Check if email already exists for other students
        $email_exists = false;
        foreach ($students as $student) {
            if ($student['email'] === $email && $student['id'] !== $student_id) {
                $email_exists = true;
                break;
            }
        }
        
        if ($email_exists) {
            $error_message = 'Email already exists';
        } else {
            // Update student
            $updated = false;
            foreach ($students as $key => $student) {
                if ($student['id'] === $student_id) {
                    $students[$key] = [
                        'id' => $student_id,
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'email' => $email,
                        'phone' => $phone,
                        'address' => $address,
                        'date_of_birth' => $date_of_birth,
                        'gender' => $gender,
                        'course' => $course,
                        'year_level' => $year_level,
                        'created_at' => $student['created_at'],
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    $updated = true;
                    break;
                }
            }
            
            if ($updated) {
                file_put_contents(STUDENTS_FILE, json_encode($students, JSON_PRETTY_PRINT));
                $success_message = 'Student updated successfully';
            } else {
                $error_message = 'Student not found';
            }
        }
    }
}

// Delete student
if (isset($_GET['delete_student'])) {
    $student_id = (int)$_GET['delete_student'];
    
    // Load existing students
    $students = json_decode(file_get_contents(STUDENTS_FILE), true) ?: [];
    
    // Filter out the student to delete
    $filtered_students = array_filter($students, function($student) use ($student_id) {
        return $student['id'] !== $student_id;
    });
    
    if (count($filtered_students) < count($students)) {
        file_put_contents(STUDENTS_FILE, json_encode(array_values($filtered_students), JSON_PRETTY_PRINT));
        $success_message = 'Student deleted successfully';
    } else {
        $error_message = 'Student not found';
    }
}

// Add user
if (isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    // Validate required fields
    if (empty($username) || empty($password) || empty($role)) {
        $error_message = 'Username, password, and role are required';
    } else {
        // Load existing users
        $users = json_decode(file_get_contents(USERS_FILE), true) ?: [];
        
        // Check if username already exists
        $username_exists = false;
        foreach ($users as $user) {
            if ($user['username'] === $username) {
                $username_exists = true;
                break;
            }
        }
        
        if ($username_exists) {
            $error_message = 'Username already exists';
        } else {
            // Generate new ID
            $max_id = 0;
            foreach ($users as $user) {
                if ($user['id'] > $max_id) {
                    $max_id = $user['id'];
                }
            }
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Create new user
            $new_user = [
                'id' => $max_id + 1,
                'username' => $username,
                'password' => $hashedPassword,
                'role' => $role,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Add to array and save
            $users[] = $new_user;
            file_put_contents(USERS_FILE, json_encode($users, JSON_PRETTY_PRINT));
            
            $success_message = 'User added successfully';
        }
    }
}

// Get all students
$students = json_decode(file_get_contents(STUDENTS_FILE), true) ?: [];

// Sort students by last name
usort($students, function($a, $b) {
    return strcmp($a['last_name'], $b['last_name']);
});

// Get all users
$users = json_decode(file_get_contents(USERS_FILE), true) ?: [];

// Sort users by username
usort($users, function($a, $b) {
    return strcmp($a['username'], $b['username']);
});

// Get counts
$student_count = count($students);
$user_count = count($users);

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Student Information System</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">


    <style>
        /* Custom styles can be added here if needed */
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <h1>Student Information System</h1>
                </div>
                <div class="nav-menu">
                    <ul>
                        <li><a href="#" onclick="showTab('dashboard')" id="nav-dashboard" class="active">Dashboard</a></li>
                        <li><a href="#" onclick="showTab('students')" id="nav-students">Students</a></li>
                        <li><a href="#" onclick="showTab('users')" id="nav-users">Users</a></li>
                        <li><a href="?logout=1">Logout</a></li>
                    </ul>
                </div>
                <div class="user-info">
                    <span>Welcome, <strong><?php echo $_SESSION['username']; ?></strong></span>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <?php if (!empty($success_message)): ?>
            <div class="success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <!-- Dashboard Tab -->
        <div id="dashboard" class="tab-content active">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Admin Dashboard</h2>
                    <div class="last-update">Last updated: <?php echo date('F j, Y, g:i a'); ?></div>
                </div>
                <div class="dashboard">
                    <div class="dashboard-card">
                        <div class="dashboard-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <h3>Total Students</h3>
                        <div class="count"><?php echo $student_count; ?></div>
                        <a href="#" onclick="showTab('students')" class="dashboard-link">Manage Students</a>
                    </div>
                    <div class="dashboard-card">
                        <div class="dashboard-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3>Total Users</h3>
                        <div class="count"><?php echo $user_count; ?></div>
                        <a href="#" onclick="showTab('users')" class="dashboard-link">Manage Users</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Students Tab -->
        <div id="students" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Student Management</h2>
                    <button class="btn btn-primary" onclick="showModal('add-student-modal')">Add New Student</button>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Course</th>
                                <th>Year Level</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($students) > 0): ?>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><?php echo $student['id']; ?></td>
                                        <td><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></td>
                                        <td><?php echo $student['email']; ?></td>
                                        <td><?php echo $student['course'] ?: '-'; ?></td>
                                        <td><?php echo $student['year_level'] ? $student['year_level'] . ' Year' : '-'; ?></td>
                                        <td>
                                            <button class="btn btn-primary btn-sm" onclick="editStudent(<?php echo htmlspecialchars(json_encode($student)); ?>)">Edit</button>
                                            <a href="?delete_student=<?php echo $student['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this student?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center;">No students found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Users Tab -->
        <div id="users" class="tab-content">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">User Management</h2>
                    <button class="btn btn-primary" onclick="showModal('add-user-modal')">Add New User</button>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($users) > 0): ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
                                        <td><?php echo $user['username']; ?></td>
                                        <td><?php echo $user['role']; ?></td>
                                        <td><?php echo $user['created_at']; ?></td>
                                        <td>
                                            <?php if ($user['username'] !== 'admin'): ?>
                                                <a href="?delete_user=<?php echo $user['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                                            <?php else: ?>
                                                <span class="text-muted">Admin (Cannot Delete)</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center;">No users found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Add Student Form -->
        <div id="add-student-form" style="display: none;" class="card">
                <div class="card-header">
                    <h2>Add New Student</h2>
                </div>
                <form method="post">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="text" id="phone" name="phone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender" class="form-control">
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="course">Course</label>
                        <input type="text" id="course" name="course" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="year_level">Year Level</label>
                        <input type="text" id="year_level" name="year_level" class="form-control">
                    </div>
                    <div class="form-group">
                        <button type="submit" name="add_student" class="btn btn-success">Add Student</button>
                        <button type="button" class="btn btn-danger" onclick="document.getElementById('add-student-form').style.display='none'">Cancel</button>
                    </div>
                </form>
            </div>
            
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Course</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo $student['id']; ?></td>
                                <td><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></td>
                                <td><?php echo $student['email']; ?></td>
                                <td><?php echo $student['phone']; ?></td>
                                <td><?php echo $student['course']; ?></td>
                                <td>
                                    <button class="btn btn-warning" onclick="openEditStudentForm(<?php echo htmlspecialchars(json_encode($student)); ?>)">Edit</button>
                                    <a href="?delete_student=<?php echo $student['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this student?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div id="edit-student-form" style="display: none;" class="card">
                <div class="card-header">
                    <h2>Edit Student</h2>
                </div>
                <form method="post">
                    <input type="hidden" id="edit_student_id" name="student_id">
                    <div class="form-group">
                        <label for="edit_first_name">First Name</label>
                        <input type="text" id="edit_first_name" name="first_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_last_name">Last Name</label>
                        <input type="text" id="edit_last_name" name="last_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_email">Email</label>
                        <input type="email" id="edit_email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_phone">Phone</label>
                        <input type="text" id="edit_phone" name="phone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="edit_address">Address</label>
                        <input type="text" id="edit_address" name="address" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="edit_date_of_birth">Date of Birth</label>
                        <input type="date" id="edit_date_of_birth" name="date_of_birth" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="edit_gender">Gender</label>
                        <select id="edit_gender" name="gender" class="form-control">
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_course">Course</label>
                        <input type="text" id="edit_course" name="course" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="edit_year_level">Year Level</label>
                        <input type="text" id="edit_year_level" name="year_level" class="form-control">
                    </div>
                    <div class="form-group">
                        <button type="submit" name="edit_student" class="btn btn-success">Update Student</button>
                        <button type="button" class="btn btn-danger" onclick="document.getElementById('edit-student-form').style.display='none'">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Users Section -->
        <div class="card" id="users">
            <div class="card-header">
                <h2>Manage Users</h2>
                <button class="btn btn-primary" onclick="document.getElementById('add-user-form').style.display='block'">Add New User</button>
            </div>
            
            <div id="add-user-form" style="display: none;" class="card">
                <div class="card-header">
                    <h2>Add New User</h2>
                </div>
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
                        <label for="role">Role</label>
                        <select id="role" name="role" class="form-control" required>
                            <option value="">Select Role</option>
                            <option value="admin">Admin</option>
                            <option value="user">User</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" name="add_user" class="btn btn-success">Add User</button>
                        <button type="button" class="btn btn-danger" onclick="document.getElementById('add-user-form').style.display='none'">Cancel</button>
                    </div>
                </form>
            </div>
            
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo $user['username']; ?></td>
                                <td><?php echo $user['role']; ?></td>
                                <td><?php echo $user['created_at']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Student Information System. All rights reserved.</p>
        </div>
    </div>
    
    <!-- Add Student Modal -->
    <div id="add-student-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="hideModal('add-student-modal')">&times;</span>
            <h2>Add New Student</h2>
            <form method="post">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone">
                </div>
                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address">
                </div>
                <div class="form-group">
                    <label for="date_of_birth">Date of Birth</label>
                    <input type="date" id="date_of_birth" name="date_of_birth">
                </div>
                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender">
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="course">Course</label>
                    <input type="text" id="course" name="course">
                </div>
                <div class="form-group">
                    <label for="year_level">Year Level</label>
                    <select id="year_level" name="year_level">
                        <option value="">Select Year Level</option>
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                        <option value="5">5th Year</option>
                    </select>
                </div>
                <button type="submit" name="add_student" class="btn btn-primary">Add Student</button>
                <button type="button" class="btn btn-danger" onclick="hideModal('add-student-modal')">Cancel</button>
            </form>
        </div>
    </div>
    
    <!-- Edit Student Modal -->
    <div id="edit-student-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="hideModal('edit-student-modal')">&times;</span>
            <h2>Edit Student</h2>
            <form method="post">
                <input type="hidden" id="edit_student_id" name="student_id">
                <div class="form-group">
                    <label for="edit_first_name">First Name</label>
                    <input type="text" id="edit_first_name" name="first_name" required>
                </div>
                <div class="form-group">
                    <label for="edit_last_name">Last Name</label>
                    <input type="text" id="edit_last_name" name="last_name" required>
                </div>
                <div class="form-group">
                    <label for="edit_email">Email</label>
                    <input type="email" id="edit_email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="edit_phone">Phone</label>
                    <input type="text" id="edit_phone" name="phone">
                </div>
                <div class="form-group">
                    <label for="edit_address">Address</label>
                    <input type="text" id="edit_address" name="address">
                </div>
                <div class="form-group">
                    <label for="edit_date_of_birth">Date of Birth</label>
                    <input type="date" id="edit_date_of_birth" name="date_of_birth">
                </div>
                <div class="form-group">
                    <label for="edit_gender">Gender</label>
                    <select id="edit_gender" name="gender">
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_course">Course</label>
                    <input type="text" id="edit_course" name="course">
                </div>
                <div class="form-group">
                    <label for="edit_year_level">Year Level</label>
                    <select id="edit_year_level" name="year_level">
                        <option value="">Select Year Level</option>
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                        <option value="5">5th Year</option>
                    </select>
                </div>
                <button type="submit" name="edit_student" class="btn btn-primary">Update Student</button>
                <button type="button" class="btn btn-danger" onclick="hideModal('edit-student-modal')">Cancel</button>
            </form>
        </div>
    </div>
    
    <!-- Add User Modal -->
    <div id="add-user-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="hideModal('add-user-modal')">&times;</span>
            <h2>Add New User</h2>
            <form method="post">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <option value="">Select Role</option>
                        <option value="admin">Admin</option>
                        <option value="user">User</option>
                    </select>
                </div>
                <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                <button type="button" class="btn btn-danger" onclick="hideModal('add-user-modal')">Cancel</button>
            </form>
        </div>
    </div>
    
    <script>
        // Show/hide modals
        function showModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }
        
        function hideModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Show/hide tabs
        function showTab(tabId) {
            // Hide all tab contents
            var tabContents = document.getElementsByClassName('tab-content');
            for (var i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove('active');
            }
            
            // Remove active class from all nav items
            var navItems = document.querySelectorAll('.nav-menu a');
            for (var i = 0; i < navItems.length; i++) {
                navItems[i].classList.remove('active');
            }
            
            // Show the selected tab content
            document.getElementById(tabId).classList.add('active');
            
            // Add active class to the clicked nav item
            document.getElementById('nav-' + tabId).classList.add('active');
        }
        
        function editStudent(student) {
            // Populate form fields
            document.getElementById('edit_student_id').value = student.id;
            document.getElementById('edit_first_name').value = student.first_name;
            document.getElementById('edit_last_name').value = student.last_name;
            document.getElementById('edit_email').value = student.email;
            document.getElementById('edit_phone').value = student.phone || '';
            document.getElementById('edit_address').value = student.address || '';
            document.getElementById('edit_date_of_birth').value = student.date_of_birth || '';
            document.getElementById('edit_gender').value = student.gender || '';
            document.getElementById('edit_course').value = student.course || '';
            document.getElementById('edit_year_level').value = student.year_level || '';
            
            // Show the modal
            showModal('edit-student-modal');
        }
        
        function openEditStudentForm(student) {
            document.getElementById('edit_student_id').value = student.id;
            document.getElementById('edit_first_name').value = student.first_name;
            document.getElementById('edit_last_name').value = student.last_name;
            document.getElementById('edit_email').value = student.email;
            document.getElementById('edit_phone').value = student.phone || '';
            document.getElementById('edit_address').value = student.address || '';
            document.getElementById('edit_date_of_birth').value = student.date_of_birth || '';
            document.getElementById('edit_gender').value = student.gender || '';
            document.getElementById('edit_course').value = student.course || '';
            document.getElementById('edit_year_level').value = student.year_level || '';
            document.getElementById('edit-student-form').style.display = 'block';
            
            // Scroll to the form
            document.getElementById('edit-student-form').scrollIntoView({behavior: 'smooth'});
        }
    </script>
</body>
</html>
