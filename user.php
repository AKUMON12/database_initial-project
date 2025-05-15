<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
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

// Handle note operations
$success_message = '';
$error_message = '';

// Add note
if (isset($_POST['add_note'])) {
    $title = $_POST['title'];
    $content = $_POST['content'] ?? '';
    $user_id = $_SESSION['user_id'];
    
    // Validate required fields
    if (empty($title)) {
        $error_message = 'Title is required';
    } else {
        // Load existing notes
        $notes = json_decode(file_get_contents(NOTES_FILE), true) ?: [];
        
        // Generate new ID
        $max_id = 0;
        foreach ($notes as $note) {
            if ($note['id'] > $max_id) {
                $max_id = $note['id'];
            }
        }
        
        // Create new note
        $new_note = [
            'id' => $max_id + 1,
            'user_id' => $user_id,
            'title' => $title,
            'content' => $content,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Add to array and save
        $notes[] = $new_note;
        file_put_contents(NOTES_FILE, json_encode($notes, JSON_PRETTY_PRINT));
        
        $success_message = 'Note added successfully';
    }
}

// Edit note
if (isset($_POST['edit_note'])) {
    $note_id = (int)$_POST['note_id'];
    $title = $_POST['title'];
    $content = $_POST['content'] ?? '';
    $user_id = $_SESSION['user_id'];
    
    // Validate required fields
    if (empty($title)) {
        $error_message = 'Title is required';
    } else {
        // Load existing notes
        $notes = json_decode(file_get_contents(NOTES_FILE), true) ?: [];
        
        // Update note
        $updated = false;
        foreach ($notes as $key => $note) {
            if ($note['id'] === $note_id && $note['user_id'] === $user_id) {
                $notes[$key] = [
                    'id' => $note_id,
                    'user_id' => $user_id,
                    'title' => $title,
                    'content' => $content,
                    'created_at' => $note['created_at'],
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                $updated = true;
                break;
            }
        }
        
        if ($updated) {
            file_put_contents(NOTES_FILE, json_encode($notes, JSON_PRETTY_PRINT));
            $success_message = 'Note updated successfully';
        } else {
            $error_message = 'Note not found or you do not have permission to edit it';
        }
    }
}

// Delete note
if (isset($_GET['delete_note'])) {
    $note_id = (int)$_GET['delete_note'];
    $user_id = $_SESSION['user_id'];
    
    // Load existing notes
    $notes = json_decode(file_get_contents(NOTES_FILE), true) ?: [];
    
    // Filter out the note to delete (only if it belongs to the current user)
    $filtered_notes = array_filter($notes, function($note) use ($note_id, $user_id) {
        return !($note['id'] === $note_id && $note['user_id'] === $user_id);
    });
    
    if (count($filtered_notes) < count($notes)) {
        file_put_contents(NOTES_FILE, json_encode(array_values($filtered_notes), JSON_PRETTY_PRINT));
        $success_message = 'Note deleted successfully';
    } else {
        $error_message = 'Note not found or you do not have permission to delete it';
    }
}

// Get user notes
$all_notes = json_decode(file_get_contents(NOTES_FILE), true) ?: [];

// Filter notes for current user
$notes = array_filter($all_notes, function($note) {
    return $note['user_id'] === $_SESSION['user_id'];
});

// Sort notes by updated_at (newest first)
usort($notes, function($a, $b) {
    return strcmp($b['updated_at'], $a['updated_at']);
});

// Re-index array
$notes = array_values($notes);

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
    <title>User Dashboard - Student Information System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>Student Information System</h1>
            <p>User Dashboard</p>
        </div>
    </div>
    
    <div class="container">
        <div class="nav">
            <a href="user.php" class="active">My Notes</a>
            <a href="?logout">Logout (<?php echo $_SESSION['username']; ?>)</a>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2>My Notes</h2>
                <button class="btn btn-primary" onclick="document.getElementById('add-note-form').style.display='block'">Add New Note</button>
            </div>
            
            <div id="add-note-form" style="display: none;" class="card">
                <div class="card-header">
                    <h2>Add New Note</h2>
                </div>
                <form method="post">
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" id="title" name="title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="content">Content</label>
                        <textarea id="content" name="content" class="form-control" rows="6"></textarea>
                    </div>
                    <div class="form-group">
                        <button type="submit" name="add_note" class="btn btn-success">Save Note</button>
                        <button type="button" class="btn btn-danger" onclick="document.getElementById('add-note-form').style.display='none'">Cancel</button>
                    </div>
                </form>
            </div>
            
            <div id="edit-note-form" style="display: none;" class="card">
                <div class="card-header">
                    <h2>Edit Note</h2>
                </div>
                <form method="post">
                    <input type="hidden" id="edit_note_id" name="note_id">
                    <div class="form-group">
                        <label for="edit_title">Title</label>
                        <input type="text" id="edit_title" name="title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_content">Content</label>
                        <textarea id="edit_content" name="content" class="form-control" rows="6"></textarea>
                    </div>
                    <div class="form-group">
                        <button type="submit" name="edit_note" class="btn btn-success">Update Note</button>
                        <button type="button" class="btn btn-danger" onclick="document.getElementById('edit-note-form').style.display='none'">Cancel</button>
                    </div>
                </form>
            </div>
            
            <?php if (count($notes) > 0): ?>
                <div class="notes-grid">
                    <?php foreach ($notes as $note): ?>
                        <div class="note-card">
                            <div class="note-header">
                                <h3><?php echo htmlspecialchars($note['title']); ?></h3>
                                <div class="note-date">
                                    <small>Last updated: <?php echo $note['updated_at']; ?></small>
                                </div>
                            </div>
                            <div class="note-content">
                                <p><?php echo nl2br(htmlspecialchars($note['content'])); ?></p>
                            </div>
                            <div class="note-actions">
                                <button class="btn btn-warning" onclick="editNoteForm(<?php echo htmlspecialchars(json_encode($note)); ?>)">Edit</button>
                                <a href="?delete_note=<?php echo $note['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this note?')">Delete</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📝</div>
                    <h3>No Notes Yet</h3>
                    <p>You don't have any notes yet. Click the "Add New Note" button to create one.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Student Information System. All rights reserved.</p>
        </div>
    </div>
    
    <script>
        function editNoteForm(note) {
            document.getElementById('edit_note_id').value = note.id;
            document.getElementById('edit_title').value = note.title;
            document.getElementById('edit_content').value = note.content || '';
            document.getElementById('edit-note-form').style.display = 'block';
            
            // Scroll to the form
            document.getElementById('edit-note-form').scrollIntoView({behavior: 'smooth'});
        }
    </script>
</body>
</html>
