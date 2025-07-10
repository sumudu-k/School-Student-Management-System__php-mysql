<?php
session_start();
ob_start();
include 'db.php';
include 'functions.php';

$deleteConfirmFunction = createDeleteModal('student');

if (!isset($_SESSION['teacherID']) || $_SESSION['position'] !== 'classroom_teacher') {
    header("Location: login.php");
    exit;
}

$teacherID = $_SESSION['teacherID'];

$query = "SELECT role FROM teachers WHERE teacherID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $teacherID);
$stmt->execute();
$result = $stmt->get_result();
$teacher_data = $result->fetch_assoc();
$is_demo_account = ($teacher_data['role'] === 'demo');

if (isset($_GET['approve'])) {
    if ($is_demo_account) {
        showToast('Action not allowed: You are using a demo account and cannot approve student registrations.', 'danger');
        header("Location: admin_student_requests.php");
        exit();
    }

    $index_number = filter_input(INPUT_GET, 'approve', FILTER_SANITIZE_STRING);

    if (empty($index_number)) {
        showToast('Invalid student ID for approval.', 'danger');
        header("Location: admin_student_requests.php");
        exit();
    }

    $check_query = "SELECT COUNT(*) FROM users WHERE index_number = ? AND is_approved = FALSE";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("s", $index_number);
    $check_stmt->execute();
    $exists = $check_stmt->get_result()->fetch_row()[0];

    if ($exists == 0) {
        showToast('Student request not found or already approved.', 'warning');
    } else {
        $query = "UPDATE users SET is_approved = TRUE WHERE index_number = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $index_number);
        if ($stmt->execute()) {
            showToast('Student registration approved successfully', 'success');
        } else {
            showToast('Failed to approve student registration.', 'danger');
        }
    }
    header("Location: admin_student_requests.php");
    exit();
}

if (isset($_GET['delete'])) {
    if ($is_demo_account) {
        showToast('Action not allowed: You are using a demo account and cannot reject student registrations.', 'danger');
        header("Location: admin_student_requests.php");
        exit();
    }

    $index_number = filter_input(INPUT_GET, 'delete', FILTER_SANITIZE_STRING);

    if (empty($index_number)) {
        showToast('Invalid student ID for rejection.', 'danger');
        header("Location: admin_student_requests.php");
        exit();
    }

    $check_query = "SELECT COUNT(*) FROM users WHERE index_number = ? AND is_approved = FALSE";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("s", $index_number);
    $check_stmt->execute();
    $exists = $check_stmt->get_result()->fetch_row()[0];

    if ($exists == 0) {
        showToast('Student request not found or already processed.', 'warning');
    } else {
        $query = "DELETE FROM users WHERE index_number = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $index_number);
        if ($stmt->execute()) {
            showToast('Student rejected successfully', 'success');
        } else {
            showToast('Failed to reject student.', 'danger');
        }
    }
    header("Location: admin_student_requests.php");
    exit();
}
ob_end_flush();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Review Student Registration Requests</title>
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
        <div class="container-lg">
            <?php if ($is_demo_account): ?>
            <div class="alert alert-danger mt-3 text-center">
                You are using a **Demo account** on a live hosted website. You **cannot approve or reject student
                registration requests**.
            </div>
            <?php endif; ?>

            <h2 class="text-primary mb-4">Review Student Registration Requests</h2>

            <div class="table-responsive mb-4">
                <table class="table table-bordered table-hover table-sm">
                    <thead class="table-primary">
                        <tr>
                            <th>Index Number</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Class</th>
                            <th>Address</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT * FROM users WHERE is_approved = FALSE ORDER BY index_number ASC";
                        $result = $conn->query($query);

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>
                                    <td>" . htmlspecialchars($row['index_number']) . "</td>
                                    <td>" . htmlspecialchars($row['first_name'] . " " . $row['last_name']) . "</td>
                                    <td>" . htmlspecialchars($row['email']) . "</td>
                                    <td>" . htmlspecialchars($row['grade'] . $row['class']) . "</td>
                                    <td>" . htmlspecialchars($row['address']) . "</td>
                                    <td>
                                        <div class='d-flex'>";
                                if ($is_demo_account) {
                                    echo "<button class='btn btn-sm btn-secondary me-2' disabled>Approve</button>";
                                    echo "<button class='btn btn-sm btn-danger' disabled>Reject</button>";
                                } else {
                                    echo "<a href='?approve=" . htmlspecialchars($row['index_number']) . "' class='btn btn-sm btn-secondary me-2'>Approve</a>";
                                    echo "<button class='btn btn-sm btn-danger' onclick=\"" . $deleteConfirmFunction . "('" . htmlspecialchars($row['index_number']) . "', '" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name'], ENT_QUOTES) . "')\">Reject</button>";
                                }
                                echo "</div>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' class='text-center'>No pending student registration requests.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <button class="btn btn-outline-secondary mb-5">
                <a href="dashboard.php" class="nav-link">Back to Dashboard</a>
            </button>
        </div>
    </main>
    <script src="styles/js/bootstrap.bundle.min.js"></script>
    <?php
    include 'user/footer.php';
    ?>
</body>

</html>