<?php
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle different request methods
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['note_id'])) {
            get_note($_GET['note_id'], $user_id);
        } else {
            get_all_notes($user_id);
        }
        break;
    case 'POST':
        create_note($user_id);
        break;
    case 'PUT':
        // Parse PUT data
        parse_str(file_get_contents('php://input'), $_PUT);
        if (isset($_PUT['note_id'])) {
            update_note($_PUT['note_id'], $user_id);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Note ID is required']);
        }
        break;
    case 'DELETE':
        // Parse DELETE data
        parse_str(file_get_contents('php://input'), $_DELETE);
        if (isset($_DELETE['note_id'])) {
            delete_note($_DELETE['note_id'], $user_id);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Note ID is required']);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

// Function to get all notes for a user
function get_all_notes($user_id) {
    global $conn;
    
    try {
        $search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
        $sort_by = isset($_GET['sort_by']) ? sanitize_input($_GET['sort_by']) : 'updated_at';
        $sort_order = isset($_GET['sort_order']) ? sanitize_input($_GET['sort_order']) : 'DESC';
        
        // Validate sort parameters
        $allowed_sort_columns = ['title', 'created_at', 'updated_at'];
        $allowed_sort_orders = ['ASC', 'DESC'];
        
        if (!in_array($sort_by, $allowed_sort_columns)) {
            $sort_by = 'updated_at';
        }
        
        if (!in_array(strtoupper($sort_order), $allowed_sort_orders)) {
            $sort_order = 'DESC';
        }
        
        // Build query with optional search
        $sql = "SELECT * FROM notes WHERE user_id = ?";
        $params = [$user_id];
        
        if (!empty($search)) {
            $sql .= " AND (title LIKE ? OR content LIKE ?)";
            $search_param = "%$search%";
            $params[] = $search_param;
            $params[] = $search_param;
        }
        
        $sql .= " ORDER BY $sort_by $sort_order";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $notes = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'notes' => $notes]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to fetch notes: ' . $e->getMessage()]);
    }
}

// Function to get a specific note
function get_note($note_id, $user_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT * FROM notes WHERE note_id = ? AND user_id = ?");
        $stmt->execute([$note_id, $user_id]);
        
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Note not found']);
            return;
        }
        
        $note = $stmt->fetch();
        echo json_encode(['success' => true, 'note' => $note]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to fetch note: ' . $e->getMessage()]);
    }
}

// Function to create a new note
function create_note($user_id) {
    global $conn;
    
    // Get JSON input
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        // Fall back to POST data if JSON parsing fails
        $title = isset($_POST['title']) ? sanitize_input($_POST['title']) : '';
        $content = isset($_POST['content']) ? $_POST['content'] : '';
        $is_draft = isset($_POST['is_draft']) ? (bool)$_POST['is_draft'] : false;
    } else {
        $title = isset($data['title']) ? sanitize_input($data['title']) : '';
        $content = isset($data['content']) ? $data['content'] : '';
        $is_draft = isset($data['is_draft']) ? (bool)$data['is_draft'] : false;
    }
    
    // Validate data
    if (empty($title) && !$is_draft) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Title is required for non-draft notes']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO notes (user_id, title, content, is_draft) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $content, $is_draft]);
        
        $note_id = $conn->lastInsertId();
        
        // Get the created note
        $stmt = $conn->prepare("SELECT * FROM notes WHERE note_id = ?");
        $stmt->execute([$note_id]);
        $note = $stmt->fetch();
        
        http_response_code(201);
        echo json_encode(['success' => true, 'message' => 'Note created successfully', 'note' => $note]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create note: ' . $e->getMessage()]);
    }
}

// Function to update a note
function update_note($note_id, $user_id) {
    global $conn;
    
    // Get JSON input
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        // Fall back to PUT data
        $title = isset($_PUT['title']) ? sanitize_input($_PUT['title']) : '';
        $content = isset($_PUT['content']) ? $_PUT['content'] : '';
        $is_draft = isset($_PUT['is_draft']) ? (bool)$_PUT['is_draft'] : false;
    } else {
        $title = isset($data['title']) ? sanitize_input($data['title']) : '';
        $content = isset($data['content']) ? $data['content'] : '';
        $is_draft = isset($data['is_draft']) ? (bool)$data['is_draft'] : false;
    }
    
    // Check if note exists and belongs to user
    try {
        $stmt = $conn->prepare("SELECT * FROM notes WHERE note_id = ? AND user_id = ?");
        $stmt->execute([$note_id, $user_id]);
        
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Note not found or access denied']);
            return;
        }
        
        // Update note
        $stmt = $conn->prepare("UPDATE notes SET title = ?, content = ?, is_draft = ? WHERE note_id = ? AND user_id = ?");
        $stmt->execute([$title, $content, $is_draft, $note_id, $user_id]);
        
        // Get the updated note
        $stmt = $conn->prepare("SELECT * FROM notes WHERE note_id = ?");
        $stmt->execute([$note_id]);
        $note = $stmt->fetch();
        
        echo json_encode(['success' => true, 'message' => 'Note updated successfully', 'note' => $note]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update note: ' . $e->getMessage()]);
    }
}

// Function to delete a note
function delete_note($note_id, $user_id) {
    global $conn;
    
    try {
        // Check if note exists and belongs to user
        $stmt = $conn->prepare("SELECT * FROM notes WHERE note_id = ? AND user_id = ?");
        $stmt->execute([$note_id, $user_id]);
        
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Note not found or access denied']);
            return;
        }
        
        // Delete note
        $stmt = $conn->prepare("DELETE FROM notes WHERE note_id = ? AND user_id = ?");
        $stmt->execute([$note_id, $user_id]);
        
        echo json_encode(['success' => true, 'message' => 'Note deleted successfully']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to delete note: ' . $e->getMessage()]);
    }
}

// Function to auto-save a draft
function save_draft($user_id) {
    global $conn;
    
    // Get JSON input
    $data = json_decode(file_get_contents('php://input'), true);
    
    $title = isset($data['title']) ? sanitize_input($data['title']) : '';
    $content = isset($data['content']) ? $data['content'] : '';
    $note_id = isset($data['note_id']) ? (int)$data['note_id'] : null;
    
    try {
        if ($note_id) {
            // Check if note exists and belongs to user
            $stmt = $conn->prepare("SELECT * FROM notes WHERE note_id = ? AND user_id = ?");
            $stmt->execute([$note_id, $user_id]);
            
            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Note not found or access denied']);
                return;
            }
            
            // Update existing draft
            $stmt = $conn->prepare("UPDATE notes SET title = ?, content = ?, is_draft = TRUE WHERE note_id = ? AND user_id = ?");
            $stmt->execute([$title, $content, $note_id, $user_id]);
            
            echo json_encode(['success' => true, 'message' => 'Draft saved', 'note_id' => $note_id]);
        } else {
            // Create new draft
            $stmt = $conn->prepare("INSERT INTO notes (user_id, title, content, is_draft) VALUES (?, ?, ?, TRUE)");
            $stmt->execute([$user_id, $title, $content]);
            
            $note_id = $conn->lastInsertId();
            
            echo json_encode(['success' => true, 'message' => 'Draft created', 'note_id' => $note_id]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to save draft: ' . $e->getMessage()]);
    }
}

// If it's an autosave request
if (isset($_POST['action']) && $_POST['action'] === 'autosave') {
    save_draft($user_id);
}