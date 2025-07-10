<?php
session_start();
include 'db.php';
include 'functions.php';
$deleteConfirmFunction = createDeleteModal('assignment');
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

// Add Assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_assignment'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $due_date = $_POST['due_date'];

    // Insert assignment with the teacher's registered subject
    $query = "INSERT INTO assignments (subject_id, title, description, due_date)
              VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isss", $subject_id, $title, $description, $due_date);
    $stmt->execute();
    showToast('Assignment added successfully.', 'success');
}

// Update Assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_assignment'])) {
    $assignment_id = $_POST['assignment_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $due_date = $_POST['due_date'];

    $query = "UPDATE assignments SET title = ?, description = ?, due_date = ?
              WHERE assignment_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssi", $title, $description, $due_date, $assignment_id);
    $stmt->execute();
    showToast('Assignment updated successfully.', 'success');
}

// Delete Assignment
if (isset($_GET['delete'])) {
    $assignment_id = $_GET['delete'];
    $query = "DELETE FROM assignments WHERE assignment_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $assignment_id);
    $stmt->execute();
    showToast('Assignment deleted successfully.', 'success');
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Manage Assignments</title>
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

<body style="display: flex; flex-direction: column; min-height: 100vh;">
    <main class="container-lg" style="flex: 1;">
        <h2 class="text-primary">Manage Assignments</h2>

        <form method="POST">

            <?php if (isset($_GET['edit'])) : ?>
            <?php
                $assignment_id = $_GET['edit'];
                $query = "SELECT * FROM assignments WHERE assignment_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $assignment_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $assignment = $result->fetch_assoc();
                ?>

            <input type="hidden" name="assignment_id" value="<?php echo $assignment['assignment_id']; ?>">

            <label for="title" class="form-label">Title</label>
            <input type="text" class="form-control mb-4" name="title" value="<?php echo $assignment['title']; ?>"
                required>

            <label for="Description" class="form-label">Description</label>
            <textarea name="description" class="form-control mb-4"><?php echo $assignment['description']; ?></textarea>

            <label for="Duedate" class="form-label">Due Date</label>
            <input type="date" class="form-control mb-3" name="due_date" value="<?php echo $assignment['due_date']; ?>"
                required>
            <button type="submit" name="update_assignment" class="btn btn-primary mb-5 me-4">Update Assignment</button>
            <a href="assignments.php"><button type="button" name=""
                    class="btn btn-sm btn-outline-dark mb-5">Cancel</button></a>

            <?php else : ?>
            <label for="Title" class="form-label">Title</label>
            <input type="text" class="form-control " name="title" required>

            <label for="Description" class="form-label">Description</label>
            <textarea name="description" class="form-control mb-4"></textarea>

            <div class="col-8 col-sm-5 col-lg-4">
                <label for="due_date" class="form-label">Due Date</label>
                <input type="date" name="due_date" class="form-control mb-3" required>

            </div>


            <button type="submit" name="add_assignment" class="btn btn-primary mb-5">Add Assignment</button>
            <?php endif; ?>
        </form>
        <h3 class="text-primary">Current Assignments</h3>
        <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered">
                <tr class="table-primary">
                    <th>Title</th>
                    <th>Description</th>
                    <th>Due Date</th>
                    <th>Updated At</th>
                    <th>Actions</th>
                </tr>
                <?php
                $query = "SELECT * FROM assignments WHERE subject_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $subject_id);
                $stmt->execute();
                $result = $stmt->get_result();


                while ($row = $result->fetch_assoc()) {
                    $date = htmlspecialchars(date('Y-m-d h:i A', strtotime($row['updated_at'])));

                    echo "<tr>
                    <td>" . $row['title'] . "</td>
                    <td>" . $row['description'] . "</td>
                    <td>" . $row['due_date'] . "</td>
                    <td>" . $date . "</td>
                    <td class='d-flex'>
                        <a href='?edit=" . $row['assignment_id'] . "'><button class='btn btn-sm btn-secondary me-1 me-sm-2'>Edit</button></a> 
                        
                        <button class='btn btn-sm btn-danger' onclick=\"" . $deleteConfirmFunction . "('" . $row['assignment_id'] . "', '" . htmlspecialchars($row['title'], ENT_QUOTES) . "')\">Delete</button>
                                
                    </td>
                  </tr>";
                }
                ?>
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