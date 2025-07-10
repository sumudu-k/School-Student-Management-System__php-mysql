<?php
session_start();
ob_start();

include 'db.php';
include 'functions.php';

$deleteConfirmFunction = createDeleteModal('student');

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

// Delete Student
if (isset($_GET['delete'])) {
    if ($is_demo_account) {
        showToast('Action not allowed: You are using a demo account and cannot delete student records.', 'danger');
        header("Location: manage_students.php");
        exit();
    }

    $index_number = filter_input(INPUT_GET, 'delete', FILTER_SANITIZE_STRING);
    if (empty($index_number)) {
        showToast('Invalid student index number for deletion.', 'danger');
        header("Location: manage_students.php");
        exit();
    }

    // Check if the student exists before attempting to delete
    $check_query = "SELECT COUNT(*) FROM users WHERE index_number = ? AND is_approved = 1";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("s", $index_number);
    $check_stmt->execute();
    $exists = $check_stmt->get_result()->fetch_row()[0];

    if ($exists == 0) {
        showToast('Student not found or already deleted.', 'warning');
    } else {
        $query = "DELETE FROM users WHERE index_number = ?";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("s", $index_number);
            if ($stmt->execute()) {
                showToast('Student deleted successfully.', 'success');
            } else {
                showToast('Failed to delete student: ' . $stmt->error, 'danger');
            }
        } else {
            showToast('Database error preparing statement for delete.', 'danger');
        }
    }
    header("Location: manage_students.php");
    exit();
}
ob_end_flush();
?>
<!DOCTYPE html>
<html>

<head>
    <title>Manage Registered Students</title>
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
        <h2 class="text-primary mb-4">Manage Registered Students</h2>
        <?php if ($is_demo_account): ?>
        <div class="alert alert-danger mt-3 text-center">
            You are using a **Demo account** on a live hosted website. You **cannot delete student records**.
        </div>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="table table-hover table-bordered mb-4">
                <thead class="table-primary">
                    <tr>
                        <th>Index Number</th>
                        <th>Full Name</th>
                        <th>Birthday</th>
                        <th>Email</th>
                        <th>Address</th>
                        <th>Class</th>
                        <th>Registered Subjects</th>
                        <th>Mother's Name</th>
                        <th>Mother's Contact</th>
                        <th>Father's Name</th>
                        <th>Father's Contact</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetch all APPROVED students
                    $query = "SELECT u.*, GROUP_CONCAT(s.subject_name SEPARATOR ', ') AS subjects
                              FROM users u
                              LEFT JOIN user_subjects us ON u.index_number = us.index_number
                              LEFT JOIN subjects s ON us.subject_id = s.subject_id
                              WHERE u.is_approved = 1
                              GROUP BY u.index_number ORDER BY u.index_number ASC";
                    $result = $conn->query($query);

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>
                                <td>" . htmlspecialchars($row['index_number']) . "</td>
                                <td>" . htmlspecialchars($row['first_name'] . " " . $row['last_name']) . "</td>
                                <td>" . htmlspecialchars($row['birthday']) . "</td>
                                <td>" . htmlspecialchars($row['email']) . "</td>
                                <td>" . htmlspecialchars($row['address']) . "</td>
                                <td>" . htmlspecialchars($row['grade'] . ' ' . $row['class']) . "</td>
                                <td>" . htmlspecialchars($row['subjects'] ?? 'N/A') . "</td>
                                <td>" . htmlspecialchars($row['mother_name']) . "</td>
                                <td>" . htmlspecialchars($row['mother_contact']) . "</td>
                                <td>" . htmlspecialchars($row['father_name']) . "</td>
                                <td>" . htmlspecialchars($row['father_contact']) . "</td>
                                <td>";

                            if ($is_demo_account) {
                                echo "<button class='btn btn-sm btn-danger' disabled>Remove</button>";
                            } else {
                                echo "<button class='btn btn-sm btn-danger' onclick=\"" . $deleteConfirmFunction . "('" . htmlspecialchars($row['index_number']) . "', '" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name'], ENT_QUOTES) . "')\">Remove</button>";
                            }
                            echo "</td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='12' class='text-center'>No approved students found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <button class="btn btn-outline-secondary mb-5">
            <a href="dashboard.php" class="nav-link">Back to Dashboard</a>
        </button>
    </main>
    <?php
    include 'user/footer.php';
    ?>
    <script src="styles/js/bootstrap.bundle.min.js"></script>

</body>

</html>