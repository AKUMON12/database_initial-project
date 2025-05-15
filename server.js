const express = require('express');
const bodyParser = require('body-parser');
const session = require('express-session');
const path = require('path');
const sqlite3 = require('sqlite3').verbose();
const bcrypt = require('bcryptjs');

// Initialize express app
const app = express();
const PORT = 3000;

// Set up middleware
app.use(bodyParser.urlencoded({ extended: false }));
app.use(bodyParser.json());
app.use(express.static(path.join(__dirname, 'public')));
app.use(session({
  secret: 'student-info-system-secret',
  resave: false,
  saveUninitialized: false
}));

// Initialize database
const db = new sqlite3.Database('./database.db', (err) => {
  if (err) {
    console.error('Error opening database', err);
  } else {
    console.log('Connected to the SQLite database');
    createTables();
  }
});

// Create tables if they don't exist
function createTables() {
  // Users table
  db.run(`CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    role TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
  )`, (err) => {
    if (err) {
      console.error('Error creating users table', err);
    } else {
      // Check if admin user exists, if not create one
      db.get('SELECT * FROM users WHERE username = ?', ['admin'], (err, row) => {
        if (err) {
          console.error('Error checking admin user', err);
        } else if (!row) {
          // Create admin user
          const hashedPassword = bcrypt.hashSync('admin123', 10);
          db.run('INSERT INTO users (username, password, role) VALUES (?, ?, ?)', 
            ['admin', hashedPassword, 'admin'], 
            (err) => {
              if (err) {
                console.error('Error creating admin user', err);
              } else {
                console.log('Admin user created');
              }
            });
        }
      });
    }
  });

  // Students table
  db.run(`CREATE TABLE IF NOT EXISTS students (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    first_name TEXT NOT NULL,
    last_name TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    phone TEXT,
    address TEXT,
    date_of_birth DATE,
    gender TEXT,
    course TEXT,
    year_level INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
  )`, (err) => {
    if (err) {
      console.error('Error creating students table', err);
    }
  });

  // Notes table
  db.run(`CREATE TABLE IF NOT EXISTS notes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    content TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id)
  )`, (err) => {
    if (err) {
      console.error('Error creating notes table', err);
    }
  });
}

// Authentication middleware
function isAuthenticated(req, res, next) {
  if (req.session.user) {
    return next();
  }
  res.redirect('/login.html');
}

function isAdmin(req, res, next) {
  if (req.session.user && req.session.user.role === 'admin') {
    return next();
  }
  res.status(403).json({ error: 'Access denied' });
}

// Routes
// Login route
app.post('/api/login', (req, res) => {
  const { username, password } = req.body;
  
  db.get('SELECT * FROM users WHERE username = ?', [username], (err, user) => {
    if (err) {
      return res.status(500).json({ error: 'Database error' });
    }
    
    if (!user) {
      return res.status(401).json({ error: 'Invalid username or password' });
    }
    
    const isPasswordValid = bcrypt.compareSync(password, user.password);
    
    if (!isPasswordValid) {
      return res.status(401).json({ error: 'Invalid username or password' });
    }
    
    req.session.user = {
      id: user.id,
      username: user.username,
      role: user.role
    };
    
    res.json({ 
      success: true, 
      user: { 
        id: user.id, 
        username: user.username, 
        role: user.role 
      } 
    });
  });
});

// Logout route
app.get('/api/logout', (req, res) => {
  req.session.destroy();
  res.json({ success: true });
});

// Get current user
app.get('/api/user', (req, res) => {
  if (req.session.user) {
    res.json({ user: req.session.user });
  } else {
    res.json({ user: null });
  }
});

// User routes
app.post('/api/users', isAdmin, (req, res) => {
  const { username, password, role } = req.body;
  
  if (!username || !password || !role) {
    return res.status(400).json({ error: 'All fields are required' });
  }
  
  const hashedPassword = bcrypt.hashSync(password, 10);
  
  db.run('INSERT INTO users (username, password, role) VALUES (?, ?, ?)', 
    [username, hashedPassword, role], 
    function(err) {
      if (err) {
        if (err.message.includes('UNIQUE constraint failed')) {
          return res.status(400).json({ error: 'Username already exists' });
        }
        return res.status(500).json({ error: 'Database error' });
      }
      
      res.json({ 
        success: true, 
        user: { 
          id: this.lastID, 
          username, 
          role 
        } 
      });
    });
});

// Student routes
// Get all students
app.get('/api/students', isAuthenticated, (req, res) => {
  db.all('SELECT * FROM students ORDER BY last_name', [], (err, students) => {
    if (err) {
      return res.status(500).json({ error: 'Database error' });
    }
    res.json({ students });
  });
});

// Get student by ID
app.get('/api/students/:id', isAuthenticated, (req, res) => {
  const { id } = req.params;
  
  db.get('SELECT * FROM students WHERE id = ?', [id], (err, student) => {
    if (err) {
      return res.status(500).json({ error: 'Database error' });
    }
    
    if (!student) {
      return res.status(404).json({ error: 'Student not found' });
    }
    
    res.json({ student });
  });
});

// Create student
app.post('/api/students', isAdmin, (req, res) => {
  const { 
    first_name, 
    last_name, 
    email, 
    phone, 
    address, 
    date_of_birth, 
    gender, 
    course, 
    year_level 
  } = req.body;
  
  if (!first_name || !last_name || !email) {
    return res.status(400).json({ error: 'First name, last name, and email are required' });
  }
  
  db.run(`INSERT INTO students 
    (first_name, last_name, email, phone, address, date_of_birth, gender, course, year_level) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)`, 
    [first_name, last_name, email, phone, address, date_of_birth, gender, course, year_level], 
    function(err) {
      if (err) {
        if (err.message.includes('UNIQUE constraint failed')) {
          return res.status(400).json({ error: 'Email already exists' });
        }
        return res.status(500).json({ error: 'Database error' });
      }
      
      res.json({ 
        success: true, 
        student: { 
          id: this.lastID, 
          first_name, 
          last_name, 
          email 
        } 
      });
    });
});

// Update student
app.put('/api/students/:id', isAdmin, (req, res) => {
  const { id } = req.params;
  const { 
    first_name, 
    last_name, 
    email, 
    phone, 
    address, 
    date_of_birth, 
    gender, 
    course, 
    year_level 
  } = req.body;
  
  if (!first_name || !last_name || !email) {
    return res.status(400).json({ error: 'First name, last name, and email are required' });
  }
  
  db.run(`UPDATE students SET 
    first_name = ?, 
    last_name = ?, 
    email = ?, 
    phone = ?, 
    address = ?, 
    date_of_birth = ?, 
    gender = ?, 
    course = ?, 
    year_level = ?,
    updated_at = CURRENT_TIMESTAMP
    WHERE id = ?`, 
    [first_name, last_name, email, phone, address, date_of_birth, gender, course, year_level, id], 
    function(err) {
      if (err) {
        if (err.message.includes('UNIQUE constraint failed')) {
          return res.status(400).json({ error: 'Email already exists' });
        }
        return res.status(500).json({ error: 'Database error' });
      }
      
      if (this.changes === 0) {
        return res.status(404).json({ error: 'Student not found' });
      }
      
      res.json({ 
        success: true, 
        student: { 
          id: parseInt(id), 
          first_name, 
          last_name, 
          email 
        } 
      });
    });
});

// Delete student
app.delete('/api/students/:id', isAdmin, (req, res) => {
  const { id } = req.params;
  
  db.run('DELETE FROM students WHERE id = ?', [id], function(err) {
    if (err) {
      return res.status(500).json({ error: 'Database error' });
    }
    
    if (this.changes === 0) {
      return res.status(404).json({ error: 'Student not found' });
    }
    
    res.json({ success: true });
  });
});

// Notes routes
// Get all notes for a user
app.get('/api/notes', isAuthenticated, (req, res) => {
  const userId = req.session.user.id;
  
  db.all('SELECT * FROM notes WHERE user_id = ? ORDER BY updated_at DESC', [userId], (err, notes) => {
    if (err) {
      return res.status(500).json({ error: 'Database error' });
    }
    res.json({ notes });
  });
});

// Get note by ID
app.get('/api/notes/:id', isAuthenticated, (req, res) => {
  const { id } = req.params;
  const userId = req.session.user.id;
  
  db.get('SELECT * FROM notes WHERE id = ? AND user_id = ?', [id, userId], (err, note) => {
    if (err) {
      return res.status(500).json({ error: 'Database error' });
    }
    
    if (!note) {
      return res.status(404).json({ error: 'Note not found' });
    }
    
    res.json({ note });
  });
});

// Create note
app.post('/api/notes', isAuthenticated, (req, res) => {
  const { title, content } = req.body;
  const userId = req.session.user.id;
  
  if (!title) {
    return res.status(400).json({ error: 'Title is required' });
  }
  
  db.run('INSERT INTO notes (user_id, title, content) VALUES (?, ?, ?)', 
    [userId, title, content || ''], 
    function(err) {
      if (err) {
        return res.status(500).json({ error: 'Database error' });
      }
      
      res.json({ 
        success: true, 
        note: { 
          id: this.lastID, 
          user_id: userId, 
          title, 
          content 
        } 
      });
    });
});

// Update note
app.put('/api/notes/:id', isAuthenticated, (req, res) => {
  const { id } = req.params;
  const { title, content } = req.body;
  const userId = req.session.user.id;
  
  if (!title) {
    return res.status(400).json({ error: 'Title is required' });
  }
  
  db.run(`UPDATE notes SET 
    title = ?, 
    content = ?,
    updated_at = CURRENT_TIMESTAMP
    WHERE id = ? AND user_id = ?`, 
    [title, content || '', id, userId], 
    function(err) {
      if (err) {
        return res.status(500).json({ error: 'Database error' });
      }
      
      if (this.changes === 0) {
        return res.status(404).json({ error: 'Note not found' });
      }
      
      res.json({ 
        success: true, 
        note: { 
          id: parseInt(id), 
          user_id: userId, 
          title, 
          content 
        } 
      });
    });
});

// Delete note
app.delete('/api/notes/:id', isAuthenticated, (req, res) => {
  const { id } = req.params;
  const userId = req.session.user.id;
  
  db.run('DELETE FROM notes WHERE id = ? AND user_id = ?', [id, userId], function(err) {
    if (err) {
      return res.status(500).json({ error: 'Database error' });
    }
    
    if (this.changes === 0) {
      return res.status(404).json({ error: 'Note not found' });
    }
    
    res.json({ success: true });
  });
});

// Redirect to login if not authenticated
app.get('/admin/*', isAuthenticated, isAdmin, (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'admin', 'index.html'));
});

app.get('/user/*', isAuthenticated, (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'user', 'index.html'));
});

// Start server
app.listen(PORT, () => {
  console.log(`Server running on http://localhost:${PORT}`);
});
