<?php
// Include database connection file
require_once '../../includes/config_db.php';
require_once './auth_check.php';


$admin_name = $admin['name'];
$admin_username = $admin['username'];
$profile_picture = $admin['profile_picture'];

// Initialize variables
$id = '';
$name = '';
$update = false;
$error = '';
$success = '';

// Create Language
if (isset($_POST['save_language'])) {
    $name = trim($_POST['name']);
    
    // Validate input
    if (empty($name)) {
        $error = "Language name is required";
    } else {
        // Check if language already exists
        $stmt = $conn->prepare("SELECT id FROM languages WHERE name = ?");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Language already exists";
        } else {
            $stmt = $conn->prepare("INSERT INTO languages (name) VALUES (?)");
            $stmt->bind_param("s", $name);
            
            if ($stmt->execute()) {
                $success = "Language added successfully";
                $name = ''; // Clear input field
            } else {
                $error = "Error: " . $conn->error;
            }
        }
    }
}

// Update Language
if (isset($_POST['update_language'])) {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    
    // Validate input
    if (empty($name)) {
        $error = "Language name is required";
    } else {
        // Check if language already exists (excluding current one)
        $stmt = $conn->prepare("SELECT id FROM languages WHERE name = ? AND id != ?");
        $stmt->bind_param("si", $name, $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Language already exists";
        } else {
            $stmt = $conn->prepare("UPDATE languages SET name = ? WHERE id = ?");
            $stmt->bind_param("si", $name, $id);
            
            if ($stmt->execute()) {
                $success = "Language updated successfully";
                $update = false;
                $name = ''; // Clear input field
                $id = '';
            } else {
                $error = "Error: " . $conn->error;
            }
        }
    }
}

// Delete Language
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Check if language is being used in music or videos or albums
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM music WHERE language_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $music_count = $row['count'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM videos WHERE language_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $video_count = $row['count'];
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM albums WHERE language_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $album_count = $row['count'];
    
    $total_count = $music_count + $video_count + $album_count;
    
    if ($total_count > 0) {
        $error = "Cannot delete language. It is being used in $music_count music, $video_count videos, and $album_count albums.";
    } else {
        $stmt = $conn->prepare("DELETE FROM languages WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $success = "Language deleted successfully";
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}

// Edit Language (fetch data for edit form)
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $update = true;
    
    $stmt = $conn->prepare("SELECT * FROM languages WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $name = $row['name'];
}

// Fetch all languages
$result = $conn->query("SELECT * FROM languages ORDER BY name ASC");
$languages = [];
while ($row = $result->fetch_assoc()) {
    $languages[] = $row;
}
include './header.php';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Languages</title>
    <link rel="stylesheet" href="assets/css/admin-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            --admin-border-radius: 12px;
            --admin-box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        body {
            background-color: var(--admin-background);
            color: white !important;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }

        .container {
            color: white;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        label {
            display: block;
            margin-bottom: 10px;
            font-weight: 500;
            font-size: 14px;
            color: var(--admin-light);
        }

        .card {
            background-color: var(--admin-card-bg);
            border-radius: var(--admin-border-radius);
            box-shadow: var(--admin-box-shadow);
            padding: 20px;
            margin-bottom: 20px;
            font-weight: 500;
            font-size: 14px;
            color: var(--admin-light);
        }


        .form-group {
            margin-bottom: 15px;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border-radius: var(--admin-border-radius);
            background-color: var(--admin-dark);
            border: 1px solid var(--admin-border);
            color: white;
            font-size: 14px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: var(--admin-border-radius);
            border: none;
            cursor: pointer;
            font-weight: 500;
            color: white;
            font-size: 14px;
            transition: all 0.3s;
        }

        .btn-primary {
            background-color: var(--admin-primary);
        }

        .btn-secondary {
            background-color: var(--admin-light);
        }

        .btn-danger {
            background-color: var(--admin-danger);
        }

        .btn:hover {
            opacity: 0.9;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th,
        table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--admin-border);
        }

        table th {
            background-color: rgba(0, 0, 0, 0.2);
            font-weight: 500;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            padding: 6px 10px;
            border-radius: var(--admin-border-radius);
            border: none;
            cursor: pointer;
            color: white;
            font-size: 12px;
        }

        .edit-btn {
            background-color: var(--admin-secondary);
        }

        .delete-btn {
            background-color: var(--admin-danger);
        }

        .alert {
            padding: 15px;
            border-radius: var(--admin-border-radius);
            color: var(--admin-light);
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: rgba(0, 230, 118, 0.2);
            border: 1px solid var(--admin-success);
        }

        .alert-danger {
            background-color: rgba(255, 61, 113, 0.2);
            border: 1px solid var(--admin-danger);
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="container">

        <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <?php echo $success; ?>
        </div>
        <?php endif; ?>

        <div class="card">
            <form method="POST" action="">
                <input type="hidden" name="id" value="<?php echo $id; ?>">

                <div class="form-group">
                    <label for="name">Language Name</label>
                    <input type="text" id="name" name="name" class="form-control" value="<?php echo $name; ?>" required>
                </div>

                <div class="form-group">
                    <?php if ($update): ?>
                    <button type="submit" name="update_language" class="btn btn-primary">
                        Update Language
                    </button>
                    <a href="languages.php" class="btn btn-secondary">Cancel</a>
                    <?php else: ?>
                    <button type="submit" name="save_language" class="btn btn-primary">
                        Add Language
                    </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="card">
            <h2>Language List</h2>

            <?php if (count($languages) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Language Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($languages as $language): ?>
                    <tr>
                        <td>
                            <?php echo $language['id']; ?>
                        </td>
                        <td>
                            <?php echo $language['name']; ?>
                        </td>
                        <td class="action-buttons">
                            <a href="languages.php?edit=<?php echo $language['id']; ?>" class="action-btn edit-btn">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $language['id']; ?>)"
                                class="action-btn delete-btn">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>No languages found. Add your first language.</p>
            <?php endif; ?>
        </div>
    </div>




    <script src="../assets/js/app.js"></script>
            
    <script>
        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this language? This action cannot be undone.')) {
                window.location.href = 'languages.php?delete=' + id;
            }
        }

        // Auto-hide alerts after 3 seconds
        setTimeout(function () {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function (alert) {
                alert.style.display = 'none';
            });
        }, 3000);
    </script>
</body>

</html>