<?php
session_start();
include 'db.php';
include 'functions.php';
$deleteConfirmFunction = createDeleteModal('adminrequest');

if (!isset($_SESSION['teacherID']) || $_SESSION['position'] !== 'classroom_teacher') {
    header("Location: login.php");
    exit;
}

// Approve Request
if (isset($_GET['approve'])) {
    $teacherID = $_GET['approve'];
    $query = "UPDATE teachers SET is_approved = TRUE WHERE teacherID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $teacherID);
    $stmt->execute();
    showToast('Request approved successfully', 'success');
}

// Reject Request
if (isset($_GET['delete'])) {
    $teacherID = $_GET['delete'];
    $query = "DELETE FROM teachers WHERE teacherID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $teacherID);
    $stmt->execute();
    showToast('Request rejected ', 'success');
}
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
                    $query = "SELECT t.*, s.subject_name FROM teachers t
                  JOIN subjects s ON t.teaching_subject = s.subject_id
                  WHERE t.is_approved = FALSE";
                    $result = $conn->query($query);

                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                    <td>" . $row['teacherID'] . "</td>
                    <td>" . $row['first_name'] . "</td>
                    <td>" . $row['last_name'] . "</td>
                    <td>" . $row['full_name'] . "</td>
                    <td>" . $row['email'] . "</td>
                    <td>" . $row['position'] . "</td>
                    <td>" . $row['subject_name'] . "</td>
                    <td>
                    <div class='d-flex'>
                    <a href='?approve=" . $row['teacherID'] . "' class='btn btn-secondary btn-sm me-2'>Approve</a>
                    <button class='btn btn-sm btn-danger' onclick='" . $deleteConfirmFunction . "(\"" . $row['teacherID'] . "\", \"" . htmlspecialchars($row['full_name'], ENT_QUOTES) . "\", \"?delete=" . $row['teacherID'] . "\")'>Reject</button></div>
                    </td>
                  </tr>";
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