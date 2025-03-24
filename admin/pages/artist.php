<?php
require_once '../../includes/config_db.php';
require_once './auth_check.php';


$admin_name = $admin['name'];
$admin_username = $admin['username'];
$profile_picture = $admin['profile_picture'];

// Initialize variables
$id = $name = $bio = $image = "";
$errorMsg = $successMsg = "";
$isEdit = false;

// Check for session messages
if (isset($_SESSION['success_msg'])) {
    $successMsg = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']); // Clear the message after use
}
if (isset($_SESSION['error_msg'])) {
    $errorMsg = $_SESSION['error_msg'];
    unset($_SESSION['error_msg']); // Clear the message after use
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        // Handle Create/Update operation
        if ($_POST['action'] == 'save') {
            $name = trim($_POST['name']);
            $bio = trim($_POST['bio']);
            
            // Validate input
            if (empty($name)) {
                $errorMsg = "Artist name is required";
            } else {
                // Process image upload if provided
                $imagePath = null;
                if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
                    $target_dir = "../../uploads/artists/";
                    if (!file_exists($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }
                    
                    $filename = time() . '_' . basename($_FILES["image"]["name"]);
                    $target_file = $target_dir . $filename;
                    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
                    
                    // Check if file is an image
                    $check = getimagesize($_FILES["image"]["tmp_name"]);
                    if ($check !== false) {
                        // Check file size (limit to 5MB)
                        if ($_FILES["image"]["size"] <= 5000000) {
                            // Allow only certain file formats
                            if (in_array($imageFileType, ["jpg", "jpeg", "png", "gif"])) {
                                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                                    $imagePath = $target_file;
                                } else {
                                    $errorMsg = "Sorry, there was an error uploading your file.";
                                }
                            } else {
                                $errorMsg = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
                            }
                        } else {
                            $errorMsg = "Sorry, your file is too large. Max size is 5MB.";
                        }
                    } else {
                        $errorMsg = "File is not an image.";
                    }
                }
                
                // If no errors, proceed with database operation
                if (empty($errorMsg)) {
                    if (isset($_POST['id']) && !empty($_POST['id'])) {
                        // Update existing artist
                        $id = $_POST['id'];
                        $sql = "UPDATE artists SET name = ?, bio = ?";
                        $params = [$name, $bio];
                        
                        if ($imagePath) {
                            $sql .= ", image = ?";
                            $params[] = $imagePath;
                        }
                        
                        $sql .= " WHERE id = ?";
                        $params[] = $id;
                        
                        $stmt = $conn->prepare($sql);
                        if ($stmt) {
                            $types = str_repeat("s", count($params) - 1) . "i";  // All strings except ID which is integer
                            $stmt->bind_param($types, ...$params);
                            if ($stmt->execute()) {
                                $_SESSION['success_msg'] = "Artist updated successfully";
                                header("Location: " . $_SERVER['PHP_SELF']);
                                exit();
                            } else {
                                $errorMsg = "Error updating artist: " . $stmt->error;
                            }
                            $stmt->close();
                        } else {
                            $errorMsg = "Error preparing statement: " . $conn->error;
                        }
                    } else {
                        // Insert new artist
                        $sql = "INSERT INTO artists (name, bio, image) VALUES (?, ?, ?)";
                        $stmt = $conn->prepare($sql);
                        if ($stmt) {
                            $stmt->bind_param("sss", $name, $bio, $imagePath);
                            if ($stmt->execute()) {
                                $_SESSION['success_msg'] = "Artist added successfully";
                                header("Location: " . $_SERVER['PHP_SELF']);
                                exit();
                            } else {
                                $errorMsg = "Error adding artist: " . $stmt->error;
                            }
                            $stmt->close();
                        } else {
                            $errorMsg = "Error preparing statement: " . $conn->error;
                        }
                    }
                }
            }
        } 
        // Handle Delete operation
        elseif ($_POST['action'] == 'delete' && isset($_POST['id'])) {
            $id = $_POST['id'];
            // Using soft delete if the schema has deleted_at column
            $sql = "UPDATE artists SET deleted_at = NOW() WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $_SESSION['success_msg'] = "Artist deleted successfully";
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit();
                } else {
                    $errorMsg = "Error deleting artist: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $errorMsg = "Error preparing statement: " . $conn->error;
            }
        }
        // Handle Edit operation (load data for editing)
        elseif ($_POST['action'] == 'edit' && isset($_POST['id'])) {
            $id = $_POST['id'];
            $sql = "SELECT * FROM artists WHERE id = ? AND deleted_at IS NULL";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $id = $row['id'];
                    $name = $row['name'];
                    $bio = $row['bio'];
                    $image = $row['image'];
                    $isEdit = true;
                } else {
                    $errorMsg = "Artist not found";
                }
                $stmt->close();
            } else {
                $errorMsg = "Error preparing statement: " . $conn->error;
            }
        }
    }
}

// Fetch all artists for display in the tables
$artists = [];
$sql = "SELECT * FROM artists WHERE deleted_at IS NULL ORDER BY name ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $artists[] = $row;
    }
}
include './header.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artist Management</title>
    <style>
:root {
    --admin-primary: #00c6ff;
    --admin-secondary: #6e42c1;
    --admin-success: #00e676;
    --admin-danger: #ff3d71;
    --admin-dark: #1e1e2d;
    --admin-light: #a2a3b7;
    --admin-background: #0f0f1a;
    --admin-card-bg: #1e1e2d;
    --admin-hover: rgba(255, 255, 255, 0.05);
    --admin-border: rgba(255, 255, 255, 0.05);
}

body {
    font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
    background-color: var(--admin-background);
    color: #ffffff;
    margin: 0;
    padding: 0;
    line-height: 1.6;
    overflow-x: hidden !important;
}

.container {
    width: 90%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

h1 {
    color: #ffffff;
    margin-bottom: 30px;
    font-weight: 600;
    font-size: 28px;
}

h2 {
    font-size: 20px;
    font-weight: 500;
    margin-bottom: 20px;
}

.card {
    background-color: var(--admin-card-bg);
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
}

.form-group {
    margin-bottom: 22px;
}

label {
    display: block;
    margin-bottom: 10px;
    font-weight: 500;
    font-size: 14px;
    color: var(--admin-light);
}

input[type="text"],
textarea,
select {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid var(--admin-border);
    border-radius: 8px;
    background-color: var(--admin-dark);
    color: #ffffff;
    box-sizing: border-box;
    font-size: 15px;
    transition: border-color 0.3s, box-shadow 0.3s;
}

input[type="text"]:focus,
textarea:focus,
select:focus {
    outline: none;
    border-color: var(--admin-primary);
    box-shadow: 0 0 0 2px rgba(0, 198, 255, 0.2);
}

/* Custom select styling */
.select-wrapper {
    position: relative;
}

.select-wrapper select {
    appearance: none;
    -webkit-appearance: none;
    padding-right: 30px;
    cursor: pointer;
}

.select-wrapper::after {
    content: '▼';
    font-size: 10px;
    color: var(--admin-light);
    position: absolute;
    right: 15px;
    top: 15px;
    pointer-events: none;
}

/* File input styling */
.file-input {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.file-input-button {
    display: inline-flex;
    align-items: center;
    background-color: var(--admin-dark);
    border: 1px solid var(--admin-border);
    border-radius: 8px;
    padding: 12px 15px;
    font-size: 15px;
    color: #ffffff;
    cursor: pointer;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.file-input-button:hover {
    background-color: var(--admin-hover);
}

input[type="file"] {
    position: absolute;
    left: 0;
    top: 0;
    opacity: 0;
    cursor: pointer;
    width: 100%;
    height: 100%;
}

.file-status {
    font-size: 13px;
    color: var(--admin-light);
    margin-top: 5px;
}

/* Checkbox styling */
.checkbox-group {
    display: flex;
    gap: 15px;
    margin: 15px 0;
}

.checkbox-container {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.checkbox-container input[type="checkbox"] {
    appearance: none;
    -webkit-appearance: none;
    width: 18px;
    height: 18px;
    border: 1px solid var(--admin-border);
    border-radius: 4px;
    background-color: var(--admin-dark);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.checkbox-container input[type="checkbox"]:checked::after {
    content: "✓";
    font-size: 12px;
    color: #ffffff;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.checkbox-container input[type="checkbox"]:checked {
    background-color: var(--admin-primary);
    border-color: var(--admin-primary);
}

.checkbox-container label {
    margin: 0;
    cursor: pointer;
    user-select: none;
}

/* Button styling */
.btn {
    padding: 12px 22px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    font-size: 15px;
    transition: all 0.3s ease;
}

.btn-primary {
    background-color: var(--admin-primary);
    color: white;
}

.btn-primary:hover {
    background-color: #00b2e6;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 198, 255, 0.2);
}

.btn-secondary {
    background-color: var(--admin-secondary);
    color: #ffffff;
}

.btn-secondary:hover {
    background-color: #5e35b1;
    transform: translateY(-2px);
}

.btn-danger {
    background-color: var(--admin-danger);
    color: white;
}

.btn-danger:hover {
    background-color: #ff2957;
}

.button-group {
    display: flex;
    gap: 15px;
    margin-top: 25px;
}

/* Table styling */
/* Table styling */
.tablesss {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin-top: 20px;
    background-color: var(--admin-card-bg);
    border-radius: 8px;
    overflow: hidden;
}

.tables th, 
.tables td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid var(--admin-border);
    color: white;
}

.tables th {
    color: var(--admin-light);
    font-weight: 500;
    font-size: 14px;
    background-color: rgba(0, 0, 0, 0.2);
}

.tables tbody tr {
    transition: background-color 0.2s ease;
}

.tables tbody tr:hover {
    background-color: var(--admin-hover);
}

.tables tbody tr:last-child td {
    border-bottom: none;
}

/* Image cell styling */
.tables td:nth-child(2) {
    width: 80px;
}

/* Image preview */
.image-preview {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid var(--admin-border);
}

/* Action buttons styling */
.action-buttons {
    display: flex;
    gap: 10px;
}

.action-buttons .btn {
    padding: 8px 15px;
    font-size: 14px;
    border-radius: 6px;
}

/* Ensure the buttons match your screenshot */
.btn-primary {
    background-color: #00c6ff;
}

.btn-danger {
    background-color: #ff3d71;
}
/* Image preview */
.image-preview {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 6px;
}

/* Action buttons */
.action-buttons {
    display: flex;
    gap: 10px;
}

.action-buttons .btn {
    padding: 8px 15px;
    font-size: 14px;
}

/* Alert messaging */
.alert {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert::before {
    font-family: sans-serif;
    font-weight: bold;
    font-size: 16px;
}

.alert-danger {
    background-color: rgba(255, 61, 113, 0.1);
    color: var(--admin-danger);
    border: 1px solid rgba(255, 61, 113, 0.3);
}

.alert-danger::before {
    content: "!";
}

.alert-success {
    background-color: rgba(0, 230, 118, 0.1);
    color: var(--admin-success);
    border: 1px solid rgba(0, 230, 118, 0.3);
}

.alert-success::before {
    content: "✓";
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .button-group {
        flex-direction: column;
    }
    
    .tables th:nth-child(1),
    .tables td:nth-child(1) {
        display: none;
    }
    
    .action-buttons {
        flex-direction: column;
    }
}

/* Dark scrollbar */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background-color: var(--admin-dark);
}

::-webkit-scrollbar-thumb {
    background-color: #3d3d57;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background-color: #4d4d69;
}
    </style>
</head>

<body>
    <div class="page-header">
        <h1><?php echo $isEdit ? 'Edit Artist' : 'Add New Artist'; ?></h1>
    </div>
    <div class="container">
        <?php if (!empty($errorMsg)): ?>
        <div class="alert alert-danger"><?php echo $errorMsg; ?></div>
        <?php endif; ?>

        <?php if (!empty($successMsg)): ?>
        <div class="alert alert-success"><?php echo $successMsg; ?></div>
        <?php endif; ?>

        <!-- Artist Form -->
        <div class="card">
            <h2></h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="save">
                <?php if ($isEdit): ?>
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="name">Artist Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                </div>

                <div class="form-group">
                    <label for="bio">Biography</label>
                    <textarea id="bio" name="bio" rows="4"><?php echo htmlspecialchars($bio); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="image">Artist Image</label>
                    <div class="file-input">
                        <div class="file-input-button">
                            Choose Image
                            <input type="file" id="image" name="image" accept="image/*">
                        </div>
                        <div class="file-status" id="fileStatus">No file chosen</div>
                    </div>
                    <?php if (!empty($image)): ?>
                    <p>Current image: <?php echo htmlspecialchars(basename($image)); ?></p>
                    <img src="<?php echo htmlspecialchars($image); ?>" alt="Artist Image" class="image-preview">
                    <?php endif; ?>
                </div>

                <div style="margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $isEdit ? 'Update Artist' : 'Add Artist'; ?>
                    </button>

                    <?php if ($isEdit): ?>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Artists Table -->
        <div class="card">
            <h2 style="color:white;">Artists List</h2>
            <?php if (count($artists) > 0): ?>
            <table class="tables">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($artists as $artist): ?>
                    <tr>
                        <td><?php echo $artist['id']; ?></td>
                        <td>
                            <?php if (!empty($artist['image'])): ?>
                            <img src="<?php echo htmlspecialchars($artist['image']); ?>" alt="Artist Image"
                                class="image-preview">
                            <?php else: ?>
                            <span>No image</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($artist['name']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($artist['created_at'])); ?></td>
                        <td class="action-buttons">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="id" value="<?php echo $artist['id']; ?>">
                                <button type="submit" class="btn btn-primary">Edit</button>
                            </form>

                            <form method="POST" style="display: inline;"
                                onsubmit="return confirm('Are you sure you want to delete this artist?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $artist['id']; ?>">
                                <button type="submit" class="btn btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>No artists found. Add your first artist using the form above.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
    // Display selected filename
    document.getElementById('image').addEventListener('change', function() {
        var fileName = this.files[0] ? this.files[0].name : 'No file chosen';
        document.getElementById('fileStatus').textContent = fileName;
    });

    // Prevent form resubmission on page refresh
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    </script>
</body>

</html>