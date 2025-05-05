<?php
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Note App</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="app-container">
        <header class="app-header">
            <div class="app-title">
                <h1>Note App</h1>
            </div>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($username); ?></span>
                <button id="logoutBtn" class="btn btn-outline">Logout</button>
            </div>
        </header>
        
        <div class="main-content">
            <div class="sidebar">
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search notes...">
                </div>
                <div class="filter-options">
                    <label for="sortBy">Sort by:</label>
                    <select id="sortBy">
                        <option value="updated_at-DESC">Last updated</option>
                        <option value="created_at-DESC">Newest first</option>
                        <option value="created_at-ASC">Oldest first</option>
                        <option value="title-ASC">Title (A-Z)</option>
                        <option value="title-DESC">Title (Z-A)</option>
                    </select>
                </div>
                <div class="notes-list" id="notesList">
                    <!-- Notes will be loaded here -->
                    <div class="loading">Loading notes...</div>
                </div>
                <button id="newNoteBtn" class="btn btn-primary btn-block">New Note</button>
            </div>
            
            <div class="note-editor">
                <div class="editor-header">
                    <input type="text" id="noteTitle" placeholder="Note title" class="note-title-input">
                    <div class="editor-actions">
                        <span id="saveStatus"></span>
                        <button id="saveNoteBtn" class="btn btn-primary">Save</button>
                        <button id="deleteNoteBtn" class="btn btn-danger">Delete</button>
                    </div>
                </div>
                <textarea id="noteContent" placeholder="Start typing your note..."></textarea>
            </div>
        </div>
    </div>

    <div id="confirmDeleteModal" class="modal">
        <div class="modal-content">
            <h3>Delete Note</h3>
            <p>Are you sure you want to delete this note? This action cannot be undone.</p>
            <div class="modal-actions">
                <button id="confirmDeleteBtn" class="btn btn-danger">Delete</button>
                <button id="cancelDeleteBtn" class="btn btn-outline">Cancel</button>
            </div>
        </div>
    </div>

    <script src="js/app.js"></script>
</body>
</html>