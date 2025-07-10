<?php
session_start();
ob_start();
include 'db.php';
include 'functions.php';

$deleteConfirmFunction = createDeleteModal('adminrequest');

// Initial check for login and specific position (classroom_teacher)
if (!isset($_SESSION['teacherID']) || $_SESSION['position'] !== 'classroom_teacher') {
    header("Location: login.php");
    exit;
}

$teacherID = $_SESSION['teacherID'];

// Fetch the current teacher's role to determine if it's a demo account
$query = "SELECT role FROM teachers WHERE teacherID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $teacherID);
$stmt->execute();
$result = $stmt->get_result();
$current_teacher_data = $result->fetch_assoc();
$demo = ($current_teacher_data['role'] === 'demo');


// Approve Request
if (isset($_GET['approve'])) {
    if ($demo === true) {
        showToast('Action not allowed: You are using a demo account and cannot approve requests.', 'danger');
        header("Location: admin_requests.php");
        exit();
    }

    $request_teacherID = $_GET['approve'];

    // Input validation
    if (empty($request_teacherID)) {
        showToast('Invalid teacher ID for approval.', 'danger');
        header("Location: admin_requests.php");
        exit();
    }

    // Sanitize the input
    $request_teacherID = htmlspecialchars($request_teacherID);

    // IMPORTANT: Verify that the request_teacherID exists and is currently unapproved
    $check_query = "SELECT COUNT(*) FROM teachers WHERE teacherID = ? AND is_approved = FALSE";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("s", $request_teacherID);
    $check_stmt->execute();
    $exists = $check_stmt->get_result()->fetch_row()[0];

    if ($exists == 0) {
        showToast('Request not found or already approved.', 'warning');
    } else {
        $query = "UPDATE teachers SET is_approved = TRUE WHERE teacherID = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $request_teacherID);
        if ($stmt->execute()) {
            showToast('Request approved successfully', 'success');
        } else {
            showToast('Failed to approve request.', 'danger');
        }
    }
    header("Location: admin_requests.php");
    exit();
}

// Reject Request (Delete)
if (isset($_GET['delete'])) {
    if ($demo === true) {
        showToast('Action not allowed: You are using a demo account and cannot reject requests.', 'danger');
        header("Location: admin_requests.php");
        exit();
    }

    $request_teacherID = $_GET['delete'];

    // Input validation
    if (empty($request_teacherID)) {
        showToast('Invalid teacher ID for rejection.', 'danger');
        header("Location: admin_requests.php");
        exit();
    }

    // Sanitize the input
    $request_teacherID = htmlspecialchars($request_teacherID);

    // verify that the request_teacherID exists and is currently unapproved
    $check_query = "SELECT COUNT(*) FROM teachers WHERE teacherID = ? AND is_approved = FALSE";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("s", $request_teacherID);
    $check_stmt->execute();
    $exists = $check_stmt->get_result()->fetch_row()[0];

    if ($exists == 0) {
        showToast('Request not found or already processed.', 'warning');
    } else {
        $query = "DELETE FROM teachers WHERE teacherID = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $request_teacherID);
        if ($stmt->execute()) {
            showToast('Request rejected successfully', 'success');
        } else {
            showToast('Failed to reject request.', 'danger');
        }
    }
    header("Location: admin_requests.php");
    exit();
}
ob_end_flush();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Review Admin Requests</title>
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
            You are using a **Demo account** on a live hosted website. You **cannot approve or reject teacher
            registration requests**. Please
            set up your own local environment to access full features.
        </div>
        <?php endif; ?>

        <h2 class="text-primary mb-4">Review Teacher Registration Requests</h2>
        <div class="table-responsive">
            <table class="table table-hover table-bordered table-sm mb-5">
                <thead class="table-primary">
                    <tr>
                        <th>Teacher ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Position</th>
                        <th>Teaching Subject</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetch unapproved teachers
                    $query = "SELECT t.*, s.subject_name FROM teachers t
                              LEFT JOIN subjects s ON t.teaching_subject = s.subject_id
                              WHERE t.is_approved = FALSE
                              ORDER BY t.teacherID ASC";
                    $result = $conn->query($query);

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>
                                <td>" . htmlspecialchars($row['teacherID']) . "</td>
                                <td>" . htmlspecialchars($row['first_name']) . "</td>
                                <td>" . htmlspecialchars($row['last_name']) . "</td>
                                <td>" . htmlspecialchars($row['full_name']) . "</td>
                                <td>" . htmlspecialchars($row['email']) . "</td>
                                <td>" . htmlspecialchars($row['position']) . "</td>
                                <td>" . htmlspecialchars($row['subject_name']) . "</td>
                                <td>
                                <div class='d-flex'>";

                            if ($demo === true) {
                                echo "<button class='btn btn-secondary btn-sm me-2' disabled>Approve</button>";
                                echo "<button class='btn btn-sm btn-danger' disabled>Reject</button>";
                            } else {
                                echo "<a href='?approve=" . htmlspecialchars($row['teacherID']) . "' class='btn btn-secondary btn-sm me-2'>Approve</a>";
                                // Pass the teacherID and full name to the delete confirmation function
                                echo "<button class='btn btn-sm btn-danger' onclick=\"" . $deleteConfirmFunction . "('" . htmlspecialchars($row['teacherID']) . "', '" . htmlspecialchars($row['full_name'], ENT_QUOTES) . "')\">Reject</button>";
                            }
                            echo "</div>
                                </td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='8' class='text-center'>No pending teacher requests.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
            <button class="btn btn-outline-secondary mb-5">
                <a href="dashboard.php" class="nav-link">Back to Dashboard</a>
            </button>
        </div>
    </main>
    <?php
    include 'user/footer.php';
    ?>
    <script src="styles/js/bootstrap.bundle.min.js"></script>

</body>

</html>