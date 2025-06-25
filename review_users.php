<?php
session_start();
include 'db.php';
include 'functions.php';

if (!isset($_SESSION['teacherID']) || $_SESSION['position'] !== 'classroom_teacher') {
    header("Location: login.php");
    exit;
}

$deleteConfirmFunction = createDeleteModal('student');

// Approve Request
if (isset($_GET['approve'])) {
    $index_number = $_GET['approve'];
    $query = "UPDATE users SET is_approved = TRUE WHERE index_number = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $index_number);
    $stmt->execute();
    showToast('Student registration approved successfully', 'success');
}

// Reject Request
if (isset($_GET['delete'])) {
    $index_number = $_GET['delete'];
    $query = "DELETE FROM users WHERE index_number = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $index_number);
    $stmt->execute();
    showToast('Student rejected successfully', 'success');
}
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
                        $query = "SELECT * FROM users WHERE is_approved = FALSE";
                        $result = $conn->query($query);

                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>
                            <td>" . $row['index_number'] . "</td>
                            <td>" . $row['first_name'] . " " . $row['last_name'] . "</td>
                            <td>" . $row['email'] . "</td>
                            <td>" . $row['grade'] . $row['class'] . "</td>
                            <td>" . $row['address'] . "</td>
                            <td>
                                <div class='d-flex'>
                                <a href='?approve=" . $row['index_number'] . "' class='btn btn-sm btn-secondary me-2'>Approve</a>
                                <button class='btn btn-sm btn-danger' onclick='" . $deleteConfirmFunction . "(\"" . $row['index_number'] . "\", \"" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name'], ENT_QUOTES) . "\")'>Reject</button>
                                </div>
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
        </div>
    </main>
    <script src="styles/js/bootstrap.bundle.min.js"></script>
    <?php
    include 'user/footer.php';
    ?>
</body>

</html>