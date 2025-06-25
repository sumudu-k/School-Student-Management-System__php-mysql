<?php
session_start();
include 'db.php';
include 'functions.php';
$deleteConfirmFunction = createDeleteModal('subject');

if (!isset($_SESSION['teacherID'])) {
    header("Location: login.php");
    exit;
}

$teacherID = $_SESSION['teacherID'];

// Fetch the teacher's registered subject
$query = "SELECT teaching_subject FROM teachers WHERE teacherID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $teacherID);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();
$subject_id = $teacher['teaching_subject'];

// Add Announcement
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_announcement'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];

    $query = "INSERT INTO announcements (subject_id, title, description)
              VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iss", $subject_id, $title, $description);
    $stmt->execute();
    showToast('Announcement added successfully.', 'success');
}

// Update Announcement
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_announcement'])) {
    $announcement_id = $_POST['announcement_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];

    $query = "UPDATE announcements SET title = ?, description = ?
              WHERE announcement_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssi", $title, $description, $announcement_id);
    $stmt->execute();
    showToast('Announcement updated successfully.', 'success');
}

// Delete Announcement
if (isset($_GET['delete'])) {
    $announcement_id = $_GET['delete'];
    $query = "DELETE FROM announcements WHERE announcement_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $announcement_id);
    $stmt->execute();
    showToast('Announcement deleted successfully.', 'success');
}
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
        <h2 class="text-primary">Manage Announcements</h2>

        <form method="POST" class="mb-4">
            <?php if (isset($_GET['edit'])) : ?>
            <?php
                // Fetch the announcement details for editing
                $announcement_id = $_GET['edit'];
                $query = "SELECT * FROM announcements WHERE announcement_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $announcement_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $announcement = $result->fetch_assoc();
                ?>
            <input type="hidden" name="announcement_id" value="<?php echo $announcement['announcement_id']; ?>">
            <div class="mb-3">
                <label for="title" class="form-label">Title:</label>
                <input type="text" class="form-control" id="title" name="title"
                    value="<?php echo $announcement['title']; ?>" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description:</label>
                <textarea class="form-control" id="description" name="description"
                    rows="4"><?php echo $announcement['description']; ?></textarea>
            </div>
            <button type="submit" name="update_announcement" class="btn btn-primary me-4">Update Announcement</button>
            <a href="announcements.php"><button type="button" class="btn btn-sm btn-outline-dark">Cancel</button></a>
            <?php else : ?>
            <div class="mb-3">
                <label for="title" class="form-label">Title:</label>
                <input type="text" class="form-control" id="title" name="title" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description:</label>
                <textarea class="form-control mb-4" id="description" name="description" rows="4"></textarea>
            </div>
            <button type="submit" name="add_announcement" class="btn btn-primary me-4">Add Announcement</button>
            <button class="btn btn-sm btn-outline-dark" type="button" onclick="clearForm()">Clear</button>

            <?php endif; ?>
        </form>


        <script>
        function clearForm() {
            // Clear all input fields
            document.querySelector('input[name="title"]').value = '';
            document.querySelector('textarea[name="description"]').value = '';
            if (document.querySelector('input[name="announced_at"]')) {
                document.querySelector('input[name="announced_at"]').value = '';
            }

            window.location.href = window.location.pathname;
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
                    $query = "SELECT * FROM announcements WHERE subject_id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("i", $subject_id);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                <td>" . $row['title'] . "</td>
                <td>" . $row['description'] . "</td>
                <td>" . $row['announced_at'] . "</td>
                <td>
                <div class='d-flex flex-row'>
                    <a href='?edit=" . $row['announcement_id'] . "' class='btn btn-sm btn-secondary me-2'>Edit</a>
                        <button class='btn btn-sm btn-danger' onclick=\"" . $deleteConfirmFunction . "('" . $row['announcement_id'] . "', '" . htmlspecialchars($row['title'], ENT_QUOTES) . "')\">Reject</button>
                                
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
    </main>
    <script src="styles/js/bootstrap.bundle.min.js"></script>
    <?php
    include 'user/footer.php';
    ?>
</body>

</html>