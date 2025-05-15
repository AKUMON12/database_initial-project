document.addEventListener('DOMContentLoaded', function() {
    // Check if user is logged in and is admin
    fetch('/api/user')
        .then(response => response.json())
        .then(data => {
            if (!data.user) {
                // Redirect to login if not logged in
                window.location.href = '/login.html';
            } else if (data.user.role !== 'admin') {
                // Redirect to user page if not admin
                window.location.href = '/user/index.html';
            } else {
                // Display username
                document.getElementById('username-display').textContent = data.user.username;
                
                // Load initial data
                loadDashboardData();
                loadStudents();
                loadUsers();
            }
        })
        .catch(error => {
            console.error('Error checking user session:', error);
            window.location.href = '/login.html';
        });
    
    // Navigation
    document.getElementById('nav-dashboard').addEventListener('click', function(e) {
        e.preventDefault();
        showSection('dashboard');
    });
    
    document.getElementById('nav-students').addEventListener('click', function(e) {
        e.preventDefault();
        showSection('students');
    });
    
    document.getElementById('nav-users').addEventListener('click', function(e) {
        e.preventDefault();
        showSection('users');
    });
    
    document.getElementById('logout-btn').addEventListener('click', function(e) {
        e.preventDefault();
        logout();
    });
    
    // Student Modal
    const studentModal = document.getElementById('student-modal');
    const closeStudentModal = document.getElementById('close-student-modal');
    const addStudentBtn = document.getElementById('add-student-btn');
    const studentForm = document.getElementById('student-form');
    
    addStudentBtn.addEventListener('click', function() {
        openStudentModal('add');
    });
    
    closeStudentModal.addEventListener('click', function() {
        studentModal.style.display = 'none';
    });
    
    studentForm.addEventListener('submit', function(e) {
        e.preventDefault();
        saveStudent();
    });
    
    // User Modal
    const userModal = document.getElementById('user-modal');
    const closeUserModal = document.getElementById('close-user-modal');
    const addUserBtn = document.getElementById('add-user-btn');
    const userForm = document.getElementById('user-form');
    
    addUserBtn.addEventListener('click', function() {
        openUserModal('add');
    });
    
    closeUserModal.addEventListener('click', function() {
        userModal.style.display = 'none';
    });
    
    userForm.addEventListener('submit', function(e) {
        e.preventDefault();
        saveUser();
    });
    
    // Close modals when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target === studentModal) {
            studentModal.style.display = 'none';
        }
        if (e.target === userModal) {
            userModal.style.display = 'none';
        }
    });
});

// Helper Functions
function showSection(section) {
    // Hide all sections
    document.getElementById('dashboard-section').style.display = 'none';
    document.getElementById('students-section').style.display = 'none';
    document.getElementById('users-section').style.display = 'none';
    
    // Show selected section
    if (section === 'dashboard') {
        document.getElementById('dashboard-section').style.display = 'block';
        loadDashboardData();
    } else if (section === 'students') {
        document.getElementById('students-section').style.display = 'block';
        loadStudents();
    } else if (section === 'users') {
        document.getElementById('users-section').style.display = 'block';
        loadUsers();
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

// Dashboard Functions
function loadDashboardData() {
    // Load student count
    fetch('/api/students')
        .then(response => response.json())
        .then(data => {
            document.getElementById('student-count').textContent = data.students ? data.students.length : 0;
        })
        .catch(error => console.error('Error loading students:', error));
    
    // Load user count (this would need a new API endpoint in a real app)
    // For now, we'll just set it to 1 (the admin)
    document.getElementById('user-count').textContent = '1';
}

// Student Functions
function loadStudents() {
    fetch('/api/students')
        .then(response => response.json())
        .then(data => {
            const studentsList = document.getElementById('students-list');
            studentsList.innerHTML = '';
            
            if (data.students && data.students.length > 0) {
                data.students.forEach(student => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${student.id}</td>
                        <td>${student.first_name} ${student.last_name}</td>
                        <td>${student.email}</td>
                        <td>${student.course || '-'}</td>
                        <td>${student.year_level ? student.year_level + ' Year' : '-'}</td>
                        <td>
                            <button class="btn btn-secondary btn-sm" onclick="viewStudent(${student.id})">View</button>
                            <button class="btn btn-primary btn-sm" onclick="editStudent(${student.id})">Edit</button>
                            <button class="btn btn-danger btn-sm" onclick="deleteStudent(${student.id})">Delete</button>
                        </td>
                    `;
                    studentsList.appendChild(row);
                });
            } else {
                const row = document.createElement('tr');
                row.innerHTML = '<td colspan="6" class="text-center">No students found</td>';
                studentsList.appendChild(row);
            }
        })
        .catch(error => console.error('Error loading students:', error));
}

function openStudentModal(mode, studentId = null) {
    const modal = document.getElementById('student-modal');
    const modalTitle = document.getElementById('student-modal-title');
    const studentForm = document.getElementById('student-form');
    const errorMessage = document.getElementById('student-error-message');
    const successMessage = document.getElementById('student-success-message');
    
    // Reset form and messages
    studentForm.reset();
    errorMessage.style.display = 'none';
    successMessage.style.display = 'none';
    
    if (mode === 'add') {
        modalTitle.textContent = 'Add New Student';
        document.getElementById('student-id').value = '';
    } else if (mode === 'edit') {
        modalTitle.textContent = 'Edit Student';
        document.getElementById('student-id').value = studentId;
        
        // Fetch student data
        fetch(`/api/students/${studentId}`)
            .then(response => response.json())
            .then(data => {
                if (data.student) {
                    const student = data.student;
                    document.getElementById('first-name').value = student.first_name;
                    document.getElementById('last-name').value = student.last_name;
                    document.getElementById('email').value = student.email;
                    document.getElementById('phone').value = student.phone || '';
                    document.getElementById('address').value = student.address || '';
                    document.getElementById('date-of-birth').value = student.date_of_birth || '';
                    document.getElementById('gender').value = student.gender || '';
                    document.getElementById('course').value = student.course || '';
                    document.getElementById('year-level').value = student.year_level || '';
                }
            })
            .catch(error => console.error('Error fetching student:', error));
    }
    
    modal.style.display = 'block';
}

function saveStudent() {
    const studentId = document.getElementById('student-id').value;
    const firstName = document.getElementById('first-name').value;
    const lastName = document.getElementById('last-name').value;
    const email = document.getElementById('email').value;
    const phone = document.getElementById('phone').value;
    const address = document.getElementById('address').value;
    const dateOfBirth = document.getElementById('date-of-birth').value;
    const gender = document.getElementById('gender').value;
    const course = document.getElementById('course').value;
    const yearLevel = document.getElementById('year-level').value;
    
    const errorMessage = document.getElementById('student-error-message');
    const successMessage = document.getElementById('student-success-message');
    
    // Reset messages
    errorMessage.style.display = 'none';
    successMessage.style.display = 'none';
    
    // Validate required fields
    if (!firstName || !lastName || !email) {
        errorMessage.textContent = 'First name, last name, and email are required';
        errorMessage.style.display = 'block';
        return;
    }
    
    const studentData = {
        first_name: firstName,
        last_name: lastName,
        email: email,
        phone: phone,
        address: address,
        date_of_birth: dateOfBirth,
        gender: gender,
        course: course,
        year_level: yearLevel
    };
    
    let url = '/api/students';
    let method = 'POST';
    
    if (studentId) {
        url = `/api/students/${studentId}`;
        method = 'PUT';
    }
    
    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(studentData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            successMessage.textContent = studentId ? 'Student updated successfully' : 'Student added successfully';
            successMessage.style.display = 'block';
            
            // Reload students after a short delay
            setTimeout(() => {
                document.getElementById('student-modal').style.display = 'none';
                loadStudents();
                loadDashboardData();
            }, 1500);
        } else {
            errorMessage.textContent = data.error || 'An error occurred';
            errorMessage.style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Error saving student:', error);
        errorMessage.textContent = 'An error occurred. Please try again.';
        errorMessage.style.display = 'block';
    });
}

function viewStudent(studentId) {
    // For simplicity, we'll just use the edit modal to view
    openStudentModal('edit', studentId);
}

function editStudent(studentId) {
    openStudentModal('edit', studentId);
}

function deleteStudent(studentId) {
    if (confirm('Are you sure you want to delete this student?')) {
        fetch(`/api/students/${studentId}`, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Student deleted successfully');
                loadStudents();
                loadDashboardData();
            } else {
                alert(data.error || 'An error occurred');
            }
        })
        .catch(error => {
            console.error('Error deleting student:', error);
            alert('An error occurred. Please try again.');
        });
    }
}

// User Functions
function loadUsers() {
    // In a real app, you would fetch users from the server
    // For simplicity, we'll just display the admin user
    const usersList = document.getElementById('users-list');
    usersList.innerHTML = '';
    
    const row = document.createElement('tr');
    row.innerHTML = `
        <td>1</td>
        <td>admin</td>
        <td>admin</td>
        <td>${new Date().toLocaleDateString()}</td>
        <td>
            <button class="btn btn-secondary btn-sm" disabled>View</button>
            <button class="btn btn-primary btn-sm" disabled>Edit</button>
            <button class="btn btn-danger btn-sm" disabled>Delete</button>
        </td>
    `;
    usersList.appendChild(row);
}

function openUserModal(mode, userId = null) {
    const modal = document.getElementById('user-modal');
    const modalTitle = document.getElementById('user-modal-title');
    const userForm = document.getElementById('user-form');
    const errorMessage = document.getElementById('user-error-message');
    const successMessage = document.getElementById('user-success-message');
    
    // Reset form and messages
    userForm.reset();
    errorMessage.style.display = 'none';
    successMessage.style.display = 'none';
    
    modalTitle.textContent = 'Add New User';
    document.getElementById('user-id').value = '';
    
    modal.style.display = 'block';
}

function saveUser() {
    const username = document.getElementById('username-input').value;
    const password = document.getElementById('password-input').value;
    const role = document.getElementById('role').value;
    
    const errorMessage = document.getElementById('user-error-message');
    const successMessage = document.getElementById('user-success-message');
    
    // Reset messages
    errorMessage.style.display = 'none';
    successMessage.style.display = 'none';
    
    // Validate required fields
    if (!username || !password || !role) {
        errorMessage.textContent = 'All fields are required';
        errorMessage.style.display = 'block';
        return;
    }
    
    const userData = {
        username: username,
        password: password,
        role: role
    };
    
    fetch('/api/users', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(userData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            successMessage.textContent = 'User added successfully';
            successMessage.style.display = 'block';
            
            // Reload users after a short delay
            setTimeout(() => {
                document.getElementById('user-modal').style.display = 'none';
                loadUsers();
                loadDashboardData();
            }, 1500);
        } else {
            errorMessage.textContent = data.error || 'An error occurred';
            errorMessage.style.display = 'block';
        }
    })
    .catch(error => {
        console.error('Error saving user:', error);
        errorMessage.textContent = 'An error occurred. Please try again.';
        errorMessage.style.display = 'block';
    });
}
