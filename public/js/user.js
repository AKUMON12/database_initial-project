document.addEventListener('DOMContentLoaded', function() {
    // Check if user is logged in
    fetch('/api/user')
        .then(response => response.json())
        .then(data => {
            if (!data.user) {
                // Redirect to login if not logged in
                window.location.href = '/login.html';
            } else {
                // Display username
                document.getElementById('username-display').textContent = data.user.username;
                document.getElementById('profile-username').textContent = data.user.username;
                document.getElementById('profile-role').textContent = data.user.role;
                
                // Load initial data
                loadNotes();
            }
        })
        .catch(error => {
            console.error('Error checking user session:', error);
            window.location.href = '/login.html';
        });
    
    // Navigation
    document.getElementById('nav-profile').addEventListener('click', function(e) {
        e.preventDefault();
        showSection('profile');
    });
    
    document.getElementById('nav-notes').addEventListener('click', function(e) {
        e.preventDefault();
        showSection('notes');
    });
    
    document.getElementById('logout-btn').addEventListener('click', function(e) {
        e.preventDefault();
        logout();
    });
    
    // Notes
    const addNoteBtn = document.getElementById('add-note-btn');
    const cancelNoteBtn = document.getElementById('cancel-note-btn');
    const noteForm = document.getElementById('note-form');
    
    addNoteBtn.addEventListener('click', function() {
        showNoteEditor('add');
    });
    
    cancelNoteBtn.addEventListener('click', function() {
        hideNoteEditor();
    });
    
    noteForm.addEventListener('submit', function(e) {
        e.preventDefault();
        saveNote();
    });
});

// Helper Functions
function showSection(section) {
    // Hide all sections
    document.getElementById('profile-section').style.display = 'none';
    document.getElementById('notes-section').style.display = 'none';
    
    // Show selected section
    if (section === 'profile') {
        document.getElementById('profile-section').style.display = 'block';
    } else if (section === 'notes') {
        document.getElementById('notes-section').style.display = 'block';
        loadNotes();
    }
}

function logout() {
    fetch('/api/logout')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = '/login.html';
            }
        })
        .catch(error => console.error('Error during logout:', error));
}

// Notes Functions
function loadNotes() {
    fetch('/api/notes')
        .then(response => response.json())
        .then(data => {
            const notesContainer = document.getElementById('notes-container');
            notesContainer.innerHTML = '';
            
            if (data.notes && data.notes.length > 0) {
                data.notes.forEach(note => {
                    const noteCard = document.createElement('div');
                    noteCard.className = 'note-card';
                    noteCard.innerHTML = `
                        <h3>${note.title}</h3>
                        <p>${note.content || ''}</p>
                        <div class="note-actions">
                            <button class="btn btn-primary btn-sm" onclick="editNote(${note.id})">Edit</button>
                            <button class="btn btn-danger btn-sm" onclick="deleteNote(${note.id})">Delete</button>
                        </div>
                    `;
                    notesContainer.appendChild(noteCard);
                });
            } else {
                notesContainer.innerHTML = '<p class="text-center">No notes found. Click "Add New Note" to create one.</p>';
            }
        })
        .catch(error => console.error('Error loading notes:', error));
}

function showNoteEditor(mode, noteId = null) {
    const noteEditor = document.getElementById('note-editor');
    const noteForm = document.getElementById('note-form');
    const errorMessage = document.getElementById('note-error-message');
    const successMessage = document.getElementById('note-success-message');
    
    // Reset form and messages
    noteForm.reset();
    errorMessage.style.display = 'none';
    successMessage.style.display = 'none';
    
    if (mode === 'add') {
        document.getElementById('note-id').value = '';
    } else if (mode === 'edit') {
        document.getElementById('note-id').value = noteId;
        
        // Fetch note data
        fetch(`/api/notes/${noteId}`)
            .then(response => response.json())
            .then(data => {
                if (data.note) {
                    document.getElementById('note-title').value = data.note.title;
                    document.getElementById('note-content').value = data.note.content || '';
                }
            })
            .catch(error => console.error('Error fetching note:', error));
    }
    
    noteEditor.style.display = 'block';
}

function hideNoteEditor() {
    document.getElementById('note-editor').style.display = 'none';
}

function saveNote() {
    const noteId = document.getElementById('note-id').value;
    const title = document.getElementById('note-title').value;
    const content = document.getElementById('note-content').value;
    
    const errorMessage = document.getElementById('note-error-message');
    const successMessage = document.getElementById('note-success-message');
    
    // Reset messages
    errorMessage.style.display = 'none';
    successMessage.style.display = 'none';
    
    // Validate required fields
    if (!title) {
        errorMessage.textContent = 'Title is required';
        errorMessage.style.display = 'block';
        return;
    }
    
    const noteData = {
        title: title,
        content: content
    };
    
    let url = '/api/notes';
    let method = 'POST';
    
    if (noteId) {
        url = `/api/notes/${noteId}`;
        method = 'PUT';
    }
    
    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(noteData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            successMessage.textContent = noteId ? 'Note updated successfully' : 'Note added successfully';
            successMessage.style.display = 'block';
            
            // Reload notes after a short delay
            setTimeout(() => {
                hideNoteEditor();
                loadNotes();
            }, 1500);
        } else {
            errorMessage.textContent = data.error || 'An error occurred';
            errorMessage.style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Error saving note:', error);
        errorMessage.textContent = 'An error occurred. Please try again.';
        errorMessage.style.display = 'block';
    });
}

function editNote(noteId) {
    showNoteEditor('edit', noteId);
}

function deleteNote(noteId) {
    if (confirm('Are you sure you want to delete this note?')) {
        fetch(`/api/notes/${noteId}`, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Note deleted successfully');
                loadNotes();
            } else {
                alert(data.error || 'An error occurred');
            }
        })
        .catch(error => {
            console.error('Error deleting note:', error);
            alert('An error occurred. Please try again.');
        });
    }
}
