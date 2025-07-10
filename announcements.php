<?php
session_start();
ob_start();

include 'db.php';
include 'functions.php';

$deleteConfirmFunction = createDeleteModal('subject');

if (!isset($_SESSION['teacherID'])) {
    header("Location: login.php");
    exit;
}

$teacherID = $_SESSION['teacherID'];

// Fetch the teacher's registered subject AND ROLE
$query = "SELECT teaching_subject, role FROM teachers WHERE teacherID = ?";
$stmt = $conn->prepare($query);
if (!$stmt) {
    showToast('Database error: Failed to prepare teacher data query.', 'danger');
    header("Location: dashboard.php");
    exit();
}
$stmt->bind_param("s", $teacherID);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();

if (!$teacher) {
    showToast('Unauthorized access: Teacher data not found.', 'danger');
    header("Location: login.php");
    exit();
}

$subject_id = $teacher['teaching_subject'];
$demo = ($teacher['role'] === 'demo');

// If the teacher has no assigned subject, they cannot manage announcements
if (empty($subject_id)) {
    showToast('You are not assigned to a teaching subject and cannot manage announcements.', 'danger');
    header("Location: dashboard.php");
    exit();
}

// Add Announcement
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_announcement'])) {
    if ($demo === true) {
        showToast('Action not allowed: You are using a demo account and cannot add announcements.', 'danger');
        header("Location: announcements.php");
        exit();
    }

    $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);

    if (empty(trim($title))) {
        showToast('Announcement title cannot be empty.', 'danger');
        header("Location: announcements.php");
        exit();
    }

    $query = "INSERT INTO announcements (subject_id, title, description) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("iss", $subject_id, $title, $description);
        if ($stmt->execute()) {
            showToast('Announcement added successfully.', 'success');
        } else {
            showToast('Failed to add announcement: ' . $stmt->error, 'danger');
        }
    } else {
        showToast('Database error preparing statement for add.', 'danger');
    }
    header("Location: announcements.php");
    exit();
}

// Update Announcement
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_announcement'])) {
    if ($demo === true) {
        showToast('Action not allowed: You are using a demo account and cannot update announcements.', 'danger');
        header("Location: announcements.php");
        exit();
    }

    $announcement_id = filter_input(INPUT_POST, 'announcement_id', FILTER_VALIDATE_INT);
    $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);

    if (!$announcement_id || empty(trim($title))) {
        showToast('Invalid input for announcement update. Title cannot be empty.', 'danger');
        header("Location: announcements.php" . (isset($_POST['announcement_id']) ? "?edit=" . htmlspecialchars($_POST['announcement_id']) : ""));
        exit();
    }

    // IMPORTANT: Verify that the announcement being updated belongs to the current teacher's subject
    $check_ownership_query = "SELECT COUNT(*) FROM announcements WHERE announcement_id = ? AND subject_id = ?";
    $check_ownership_stmt = $conn->prepare($check_ownership_query);
    if (!$check_ownership_stmt) {
        showToast('Database error: Failed to prepare ownership check statement.', 'danger');
        header("Location: announcements.php");
        exit();
    }
    $check_ownership_stmt->bind_param("ii", $announcement_id, $subject_id);
    $check_ownership_stmt->execute();
    $ownership_result = $check_ownership_stmt->get_result()->fetch_row()[0];

    if ($ownership_result == 0) {
        showToast('Unauthorized attempt to update an announcement for a different subject or announcement not found.', 'danger');
        header("Location: announcements.php");
        exit();
    }

    $query = "UPDATE announcements SET title = ?, description = ? WHERE announcement_id = ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("ssi", $title, $description, $announcement_id);
        if ($stmt->execute()) {
            showToast('Announcement updated successfully.', 'success');
        } else {
            showToast('Failed to update announcement: ' . $stmt->error, 'danger');
        }
    } else {
        showToast('Database error preparing statement for update.', 'danger');
    }
    header("Location: announcements.php");
    exit();
}

// Delete Announcement
if (isset($_GET['delete'])) {
    if ($demo === true) {
        showToast('Action not allowed: You are using a demo account and cannot delete announcements.', 'danger');
        header("Location: announcements.php");
        exit();
    }

    $announcement_id = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);

    if (!$announcement_id) {
        showToast('Invalid announcement ID for deletion.', 'danger');
        header("Location: announcements.php");
        exit();
    }

    $check_ownership_query = "SELECT COUNT(*) FROM announcements WHERE announcement_id = ? AND subject_id = ?";
    $check_ownership_stmt = $conn->prepare($check_ownership_query);
    if (!$check_ownership_stmt) {
        showToast('Database error: Failed to prepare ownership check statement for delete.', 'danger');
        header("Location: announcements.php");
        exit();
    }
    $check_ownership_stmt->bind_param("ii", $announcement_id, $subject_id);
    $check_ownership_stmt->execute();
    $ownership_result = $check_ownership_stmt->get_result()->fetch_row()[0];

    if ($ownership_result == 0) {
        showToast('Unauthorized attempt to delete an announcement for a different subject or announcement not found.', 'danger');
        header("Location: announcements.php");
        exit();
    }

    $query = "DELETE FROM announcements WHERE announcement_id = ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $announcement_id);
        if ($stmt->execute()) {
            showToast('Announcement deleted successfully.', 'success');
        } else {
            showToast('Failed to delete announcement: ' . $stmt->error, 'danger');
        }
    } else {
        showToast('Database error preparing statement for delete.', 'danger');
    }
    header("Location: announcements.php");
    exit();
}
ob_end_flush();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Manage Announcements</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="styles/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles/css/custom.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oleo+Script:wght@400;700&display=swap" rel="stylesheet">
</head>
<?php
include 'admin_navbar.php';
?>

<body class="d-flex flex-column min-vh-100">
    <main class="container-lg" style="flex: 1;">
        <?php if ($demo === true): ?>
        <div class="alert alert-danger mt-3 text-center">
            You are using a **Demo account** on a live hosted website. You **cannot add, edit, or delete
            announcements**. Please set up your own local environment to access full features.
        </div>
        <?php endif; ?>

        <h2 class="text-primary">Manage Announcements</h2>

        <form method="POST" class="mb-4">
            <?php if (isset($_GET['edit'])) : ?>
            <?php
                $announcement = null;
                $announcement_id_edit = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);

                if ($demo === true) {
                } elseif ($announcement_id_edit) {
                    // Fetch the announcement details for editing
                    $query_edit = "SELECT * FROM announcements WHERE announcement_id = ? AND subject_id = ?";
                    $stmt_edit = $conn->prepare($query_edit);
                    if ($stmt_edit) {
                        $stmt_edit->bind_param("ii", $announcement_id_edit, $subject_id);
                        $stmt_edit->execute();
                        $result_edit = $stmt_edit->get_result();
                        $announcement = $result_edit->fetch_assoc();

                        if (!$announcement) {
                            showToast('Unauthorized access or invalid announcement.', 'danger');
                            header("Location: announcements.php");
                            exit();
                        }
                    } else {
                        showToast('Database error preparing statement for edit form.', 'danger');
                        header("Location: announcements.php");
                        exit();
                    }
                } else {
                    showToast('Invalid announcement ID for editing.', 'danger');
                    header("Location: announcements.php");
                    exit();
                }
                ?>
            <input type="hidden" name="announcement_id"
                value="<?php echo htmlspecialchars($announcement['announcement_id'] ?? ''); ?>">
            <div class="mb-3">
                <label for="title" class="form-label">Title:</label>
                <input type="text" class="form-control" id="title" name="title"
                    value="<?php echo htmlspecialchars($announcement['title'] ?? ''); ?>" required
                    <?php echo $demo === true ? 'disabled' : ''; ?>>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description:</label>
                <textarea class="form-control" id="description" name="description" rows="4"
                    <?php echo $demo === true ? 'disabled' : ''; ?>><?php echo htmlspecialchars($announcement['description'] ?? ''); ?></textarea>
            </div>
            <?php if ($demo === true): ?>
            <button type="button" class="btn btn-primary me-4" disabled>Update Announcement</button>
            <a href="announcements.php"><button type="button" class="btn btn-sm btn-outline-dark">Cancel</button></a>
            <?php else: ?>
            <button type="submit" name="update_announcement" class="btn btn-primary me-4">Update Announcement</button>
            <a href="announcements.php"><button type="button" class="btn btn-sm btn-outline-dark">Cancel</button></a>
            <?php endif; ?>

            <?php else : ?>
            <h4 class="text-primary">Add New Announcement</h4>
            <div class="mb-3">
                <label for="title" class="form-label">Title:</label>
                <input type="text" class="form-control" id="title" name="title" required
                    <?php echo $demo === true ? 'disabled' : ''; ?>>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description:</label>
                <textarea class="form-control mb-4" id="description" name="description" rows="4"
                    <?php echo $demo === true ? 'disabled' : ''; ?>></textarea>
            </div>
            <?php if ($demo === true): ?>
            <button type="button" class="btn btn-primary me-4" disabled>Add Announcement</button>
            <button class="btn btn-sm btn-outline-dark" type="button" disabled>Clear</button>
            <?php else: ?>
            <button type="submit" name="add_announcement" class="btn btn-primary me-4">Add Announcement</button>
            <button class="btn btn-sm btn-outline-dark" type="button" onclick="clearAddForm()">Clear</button>
            <?php endif; ?>

            <?php endif; ?>
        </form>

        <script>
        function clearAddForm() {
            // Only clear if in "add" mode
            if (!<?php echo (isset($_GET['edit'])) ? 'true' : 'false'; ?>) {
                document.getElementById('title').value = '';
                document.getElementById('description').value = '';
            }
        }
        </script>

        <h3 class="text-primary mb-4">Current Announcements</h3>
        <div class="table-responsive mb-4">
            <table class="table table-sm table-hover table-bordered">
                <thead class="bg-primary text-white">
                    <tr class="table-primary">
                        <th>Title</th>
                        <th>Description</th>
                        <th>Announced At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetch announcements for the teacher's registered subject
                    $query_display = "SELECT * FROM announcements WHERE subject_id = ? ORDER BY announced_at DESC";
                    $stmt_display = $conn->prepare($query_display);
                    if ($stmt_display) {
                        $stmt_display->bind_param("i", $subject_id);
                        $stmt_display->execute();
                        $result_display = $stmt_display->get_result();

                        if ($result_display->num_rows > 0) {
                            while ($row = $result_display->fetch_assoc()) {
                                echo "<tr>
                                    <td>" . htmlspecialchars($row['title']) . "</td>
                                    <td>" . htmlspecialchars($row['description']) . "</td>
                                    <td>" . htmlspecialchars($row['announced_at']) . "</td>
                                    <td>
                                    <div class='d-flex flex-row'>";

                                if ($demo === true) {
                                    echo "<button class='btn btn-sm btn-secondary me-2' disabled>Edit</button>";
                                    echo "<button class='btn btn-sm btn-danger' disabled>Delete</button>";
                                } else {
                                    echo "<a href='?edit=" . htmlspecialchars($row['announcement_id']) . "' class='btn btn-sm btn-secondary me-2'>Edit</a>";
                                    echo "<button class='btn btn-sm btn-danger' onclick=\"" . $deleteConfirmFunction . "('" . htmlspecialchars($row['announcement_id']) . "', '" . htmlspecialchars($row['title'], ENT_QUOTES) . "')\">Delete</button>";
                                }
                                echo "</div>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4' class='text-center'>No announcements found for your subject.</td></tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' class='text-center'>Error retrieving announcements.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <button class="btn btn-outline-secondary mb-5">
            <a href="dashboard.php" class="nav-link">Back to Dashboard</a>
        </button>
    </main>
    <script src="styles/js/bootstrap.bundle.min.js"></script>
    <?php
    include 'user/footer.php';
    ?>
</body>

</html>