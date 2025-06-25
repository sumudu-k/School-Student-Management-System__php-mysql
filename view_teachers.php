<?php
session_start();
include 'db.php';
include 'functions.php';
$deleteConfirmFunction = createDeleteModal('teacher');
if (!isset($_SESSION['teacherID'])) {
    header("Location: login.php");
    exit;
}

$is_super_admin = ($_SESSION['position'] === 'classroom_teacher');
if (!$is_super_admin) {
    showToast('You do not have permission to access this page.', 'error');
    exit;
}

// Delete Teacher
if (isset($_GET['delete'])) {
    $teacherID = $_GET['delete'];
    $query = "DELETE FROM teachers WHERE teacherID = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $teacherID);
    $stmt->execute();
    showToast('Teacher removed successfully.', 'danger');
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Manage Registered Teachers</title>
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
        <h2 class="text-primary mb-4">Manage Registered Teachers</h2>
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-hover mb-5">
                <thead class="table-primary">
                    <tr>
                        <th>Teacher ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Teaching Subject</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetch all registered teachers
                    $query = "SELECT t.*, s.subject_name
                              FROM teachers t
                              LEFT JOIN subjects s ON t.teaching_subject = s.subject_id WHERE t.is_approved = TRUE AND t.position='teacher'";
                    $result = $conn->query($query);
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                            <td>" . htmlspecialchars($row['teacherID']) . "</td>
                            <td>" . htmlspecialchars($row['full_name']) . "</td>
                            <td>" . htmlspecialchars($row['email']) . "</td>
                            <td>" . htmlspecialchars($row['subject_name'] ?? 'No subject') . "</td>
                            <td>
                                <button class='btn btn-sm btn-danger' onclick=\"" . $deleteConfirmFunction . "('" . $row['teacherID'] . "', '" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name'], ENT_QUOTES) . "')\">Remove</button>
                                
                            </td>
                        </tr>";
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