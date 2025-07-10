<?php
session_start();
ob_start();

include 'db.php';
include 'functions.php';

$deleteConfirmFunction = createDeleteModal('teacher');

if (!isset($_SESSION['teacherID'])) {
    header("Location: login.php");
    exit;
}

$current_teacher_id = $_SESSION['teacherID'];

// Fetch current teacher's role to check for demo account and position
$stmt = $conn->prepare("SELECT position, role FROM teachers WHERE teacherID = ?");
$stmt->bind_param("s", $current_teacher_id);
$stmt->execute();
$teacher_data = $stmt->get_result()->fetch_assoc();
$is_super_admin = ($teacher_data['position'] === 'classroom_teacher');
$is_demo_account = ($teacher_data['role'] === 'demo');

if (!$is_super_admin) {
    showToast('You do not have permission to access this page.', 'error');
    header("Location: dashboard.php");
    exit;
}

// Delete Teacher
if (isset($_GET['delete'])) {
    if ($is_demo_account) {
        showToast('Action not allowed: You are using a demo account and cannot remove teachers.', 'danger');
        header("Location: manage_teachers.php");
        exit();
    }

    $teacherID_to_delete = filter_input(INPUT_GET, 'delete', FILTER_SANITIZE_STRING);

    if (empty($teacherID_to_delete)) {
        showToast('Invalid teacher ID for deletion.', 'danger');
        header("Location: manage_teachers.php");
        exit();
    }

    // Prevent self-deletion
    if ($teacherID_to_delete === $current_teacher_id) {
        showToast('You cannot remove your own account.', 'warning');
        header("Location: manage_teachers.php");
        exit();
    }

    // Check if the teacher to be deleted exists and is not a classroom_teacher
    $check_query = "SELECT COUNT(*) FROM teachers WHERE teacherID = ? AND position != 'classroom_teacher'";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("s", $teacherID_to_delete);
    $check_stmt->execute();
    $exists = $check_stmt->get_result()->fetch_row()[0];

    if ($exists == 0) {
        showToast('Teacher not found or cannot be removed (e.g., is an admin).', 'warning');
    } else {
        $query = "DELETE FROM teachers WHERE teacherID = ?";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("s", $teacherID_to_delete);
            if ($stmt->execute()) {
                showToast('Teacher removed successfully.', 'danger');
            } else {
                showToast('Failed to remove teacher: ' . $stmt->error, 'danger');
            }
        } else {
            showToast('Database error preparing statement for delete.', 'danger');
        }
    }
    header("Location: manage_teachers.php");
    exit();
}
ob_end_flush();
?>
<!DOCTYPE html>
<html>

<head>
    <title>Manage Registered Teachers</title>
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
        <h2 class="text-primary mb-4">Manage Registered Teachers</h2>
        <?php if ($is_demo_account): ?>
        <div class="alert alert-danger mt-3 text-center">
            You are using a **Demo account** on a live hosted website. You **cannot remove teachers**.
        </div>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-hover mb-5">
                <thead class="table-primary">
                    <tr>
                        <th>Teacher ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Teaching Subject</th>
                        <th>Position</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetch all registered teachers 
                    $query = "SELECT t.*, s.subject_name
                              FROM teachers t
                              LEFT JOIN subjects s ON t.teaching_subject = s.subject_id
                              WHERE t.is_approved = TRUE ORDER BY t.full_name ASC";
                    $result = $conn->query($query);
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>
                                <td>" . htmlspecialchars($row['teacherID']) . "</td>
                                <td>" . htmlspecialchars($row['full_name']) . "</td>
                                <td>" . htmlspecialchars($row['email']) . "</td>
                                <td>" . htmlspecialchars($row['subject_name'] ?? 'N/A') . "</td>
                                <td>" . htmlspecialchars(ucwords(str_replace('_', ' ', $row['position']))) . "</td>
                                <td>";

                            // Only allow deletion if not a demo account, not self, and not a classroom_teacher
                            if ($is_demo_account || $row['teacherID'] === $current_teacher_id || $row['position'] === 'classroom_teacher') {
                                echo "<button class='btn btn-sm btn-danger' disabled>Remove</button>";
                            } else {
                                echo "<button class='btn btn-sm btn-danger' onclick=\"" . $deleteConfirmFunction . "('" . htmlspecialchars($row['teacherID']) . "', '" . htmlspecialchars($row['full_name'], ENT_QUOTES) . "')\">Remove</button>";
                            }
                            echo "</td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' class='text-center'>No approved teachers found.</td></tr>";
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