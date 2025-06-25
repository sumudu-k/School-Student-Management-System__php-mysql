<?php
session_start();
include 'db.php';
include 'functions.php';
$deleteConfirmFunction = createDeleteModal('student');

if (!isset($_SESSION['teacherID'])) {
    header("Location: login.php");
    exit;
}

$is_super_admin = ($_SESSION['position'] === 'classroom_teacher');
if (!$is_super_admin) {
    showToast('You do not have permission to access this page.', 'error');
    exit;
}

// Delete Student
if (isset($_GET['delete'])) {
    $index_number = $_GET['delete'];
    $query = "DELETE FROM users WHERE index_number = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $index_number);
    $stmt->execute();
    showToast('Student deleted successfully.', 'success');
}
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
        <h2>Manage Registered Students</h2>
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
                    <th>Mother's name</th>
                    <th>Mother's contact</th>
                    <th>Father's name</th>
                    <th>Father's contact</th>
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
                          GROUP BY u.index_number";
                $result = $conn->query($query);
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                            <td>" . htmlspecialchars($row['index_number']) . "</td>
                            <td>" . htmlspecialchars($row['first_name']) . " " . htmlspecialchars($row['last_name']) . "</td>
                            <td>" . htmlspecialchars($row['birthday']) . "</td>
                            <td>" . htmlspecialchars($row['email']) . "</td>
                            <td>" . htmlspecialchars($row['address']) . "</td>
                            <td>" . htmlspecialchars($row['grade'] . ' ' . $row['class']) . "</td>
                            <td>" . htmlspecialchars($row['subjects']) . "</td>
                            <td>" . htmlspecialchars($row['mother_name']) . "</td>
                            <td>" . htmlspecialchars($row['mother_contact']) . "</td>
                            <td>" . htmlspecialchars($row['father_name']) . "</td>
                            <td>" . htmlspecialchars($row['father_contact']) . "</td>
                            <td>
                               <button class='btn btn-sm btn-danger' onclick=\"" . $deleteConfirmFunction . "('" . $row['index_number'] . "', '" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name'], ENT_QUOTES) . "')\">Remove</button>
                                
                            </td>
                          </tr>";
                }
                ?>
            </tbody>
        </table>
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