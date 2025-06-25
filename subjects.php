<?php
session_start();
include 'db.php';
include 'functions.php';
$deleteConfirmFunction = createDeleteModal('subject');

if (!isset($_SESSION['teacherID'])) {
    header("Location: login.php");
    exit;
}

$is_super_admin = ($_SESSION['position'] === 'classroom_teacher');
if (!$is_super_admin) {
    header("Location: login.php");
    exit;
}

// Add Subject
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_subject'])) {
    $subject_name = $_POST['subject_name'];
    $query = "INSERT INTO subjects (subject_name) VALUES (?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $subject_name);
    $stmt->execute();
    showToast('Subject added successfully.', 'success');
}

// Update Subject
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_subject'])) {
    $subject_id = $_POST['subject_id'];
    $subject_name = $_POST['subject_name'];
    $query = "UPDATE subjects SET subject_name = ? WHERE subject_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $subject_name, $subject_id);
    $stmt->execute();
    showToast('Subject updated successfully.', 'success');
}

// Delete Subject
if (isset($_GET['delete'])) {
    $subject_id = $_GET['delete'];
    $query = "DELETE FROM subjects WHERE subject_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    showToast('Subject deleted successfully.', 'danger');
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Manage Subjects</title>
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
        <h2 class="text-primary mb-5">Manage Subjects</h2>
        <h4 class="text-primary"><?php echo isset($_GET['edit']) ? 'Edit Subject' : 'Add New Subject'; ?></h4>
        <form method="POST">
            <?php if (isset($_GET['edit'])) : ?>
            <?php
                $subject_id = $_GET['edit'];
                $query = "SELECT * FROM subjects WHERE subject_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $subject_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $subject = $result->fetch_assoc();
                ?>
            <input type="hidden" name="subject_id" value="<?php echo $subject['subject_id']; ?>">
            <?php endif; ?>

            <div class="mb-3">
                <label for="subject_name" class="form-label">Subject Name</label>
                <div class="col-12 col-md-8 col-lg-6">
                    <input type="text" class="form-control" id="subject_name" name="subject_name"
                        value="<?php echo isset($subject) ? htmlspecialchars($subject['subject_name']) : ''; ?>"
                        required>
                </div>
            </div>

            <div class="d-flex align-items-center mb-4">
                <button type="submit" class="btn btn-primary"
                    name="<?php echo isset($_GET['edit']) ? 'update_subject' : 'add_subject'; ?>">
                    <?php echo isset($_GET['edit']) ? 'Update Subject' : 'Add Subject'; ?>
                </button>

                <?php if (isset($_GET['edit'])) : ?>
                <a href="subjects.php" class="btn btn-sm btn-outline-dark ms-4">Cancel</a>
                <?php endif; ?>
            </div>
        </form>

        <h3 class="text-primary">Current Subjects</h3>
        <div class="table-responsive col-lg-8">
            <table class="table table-hover table-bordered mt-4">
                <thead class="table-primary">
                    <tr>
                        <th>Subject Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT * FROM subjects";
                    $result = $conn->query($query);
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                    <td>" . htmlspecialchars($row['subject_name']) . "</td>
                    <td style='width: 200px; white-space: nowrap;'>
                        <a href='?edit=" . $row['subject_id'] . "' class='btn btn-sm btn-secondary me-2'>Edit</a>
                        <button class='btn btn-sm btn-danger' onclick='" . $deleteConfirmFunction . "(" . $row['subject_id'] . ", \"" . htmlspecialchars($row['subject_name'], ENT_QUOTES) . "\")'>Delete</button>
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
    <?php include 'user/footer.php'; ?>
</body>

</html>