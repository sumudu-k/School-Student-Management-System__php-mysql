<?php
session_start();
ob_start();

include 'db.php';
include 'functions.php';

$deleteConfirmFunction = createDeleteModal('assignment');

if (!isset($_SESSION['teacherID'])) {
    header("Location: login.php");
    exit;
}

$teacherID = $_SESSION['teacherID'];

// Fetch the teacher's registered subject and role
$query = "SELECT teaching_subject, role FROM teachers WHERE teacherID = ?";
$stmt = $conn->prepare($query);
if (!$stmt) {
    showToast('Database error: Could not prepare teacher data query.', 'danger');
    header("Location: dashboard.php");
    exit();
}
$stmt->bind_param("s", $teacherID);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();

if (!$teacher) {
    showToast('Unauthorized access: Teacher data not found.', 'danger');
    header("Location: login.php");
    exit();
}

$subject_id = $teacher['teaching_subject'];
$demo = ($teacher['role'] === 'demo');

// If the teacher has no assigned subject, they cannot manage assignments
if (empty($subject_id)) {
    showToast('You are not assigned to a teaching subject and cannot manage assignments.', 'danger');
    header("Location: dashboard.php");
    exit();
}

// Add Assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_assignment'])) {
    if ($demo === true) {
        showToast('Action not allowed: You are using a demo account.', 'danger');
        header("Location: assignments.php");
        exit();
    }

    $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
    $due_date = filter_input(INPUT_POST, 'due_date', FILTER_SANITIZE_STRING);

    if (empty($title) || empty($due_date)) {
        showToast('Title and Due Date are required.', 'danger');
        header("Location: assignments.php");
        exit();
    }

    // Validate date format if necessary
    if (!DateTime::createFromFormat('Y-m-d', $due_date)) {
        showToast('Invalid due date format.', 'danger');
        header("Location: assignments.php");
        exit();
    }

    $query = "INSERT INTO assignments (subject_id, title, description, due_date) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("isss", $subject_id, $title, $description, $due_date);
        if ($stmt->execute()) {
            showToast('Assignment added successfully.', 'success');
        } else {
            showToast('Failed to add assignment: ' . $stmt->error, 'danger');
        }
    } else {
        showToast('Database error preparing statement for adding assignment.', 'danger');
    }
    header("Location: assignments.php");
    exit();
}

// Update Assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_assignment'])) {
    if ($demo === true) {
        showToast('Action not allowed: You are using a demo account.', 'danger');
        header("Location: assignments.php");
        exit();
    }

    $assignment_id = filter_input(INPUT_POST, 'assignment_id', FILTER_VALIDATE_INT);
    $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
    $due_date = filter_input(INPUT_POST, 'due_date', FILTER_SANITIZE_STRING);

    if (!$assignment_id || empty($title) || empty($due_date)) {
        showToast('Invalid input for updating assignment.', 'danger');
        header("Location: assignments.php");
        exit();
    }

    // Validate date format if necessary
    if (!DateTime::createFromFormat('Y-m-d', $due_date)) {
        showToast('Invalid due date format.', 'danger');
        header("Location: assignments.php?edit=" . htmlspecialchars($assignment_id));
        exit();
    }

    // Ownership check: Ensure the assignment belongs to the teacher's subject
    $check_ownership_query = "SELECT COUNT(*) FROM assignments WHERE assignment_id = ? AND subject_id = ?";
    $check_ownership_stmt = $conn->prepare($check_ownership_query);
    if (!$check_ownership_stmt) {
        showToast('Database error: Could not prepare ownership check for update.', 'danger');
        header("Location: assignments.php");
        exit();
    }
    $check_ownership_stmt->bind_param("ii", $assignment_id, $subject_id);
    $check_ownership_stmt->execute();
    $ownership_result = $check_ownership_stmt->get_result()->fetch_row()[0];

    if ($ownership_result == 0) {
        showToast('Unauthorized attempt to update an assignment not belonging to your subject.', 'danger');
        header("Location: assignments.php");
        exit();
    }

    $query = "UPDATE assignments SET title = ?, description = ?, due_date = ? WHERE assignment_id = ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("sssi", $title, $description, $due_date, $assignment_id);
        if ($stmt->execute()) {
            showToast('Assignment updated successfully.', 'success');
        } else {
            showToast('Failed to update assignment: ' . $stmt->error, 'danger');
        }
    } else {
        showToast('Database error preparing statement for updating assignment.', 'danger');
    }
    header("Location: assignments.php");
    exit();
}

// Delete Assignment
if (isset($_GET['delete'])) {
    $assignment_id = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);

    if ($demo === true) {
        showToast('Action not allowed: You are using a demo account.', 'danger');
        header("Location: assignments.php");
        exit();
    }

    if (!$assignment_id) {
        showToast('Invalid assignment ID for deletion.', 'danger');
        header("Location: assignments.php");
        exit();
    }

    // Ownership check: Ensure the assignment belongs to the teacher's subject before deleting
    $check_ownership_query = "SELECT COUNT(*) FROM assignments WHERE assignment_id = ? AND subject_id = ?";
    $check_ownership_stmt = $conn->prepare($check_ownership_query);
    if (!$check_ownership_stmt) {
        showToast('Database error: Could not prepare ownership check for delete.', 'danger');
        header("Location: assignments.php");
        exit();
    }
    $check_ownership_stmt->bind_param("ii", $assignment_id, $subject_id);
    $check_ownership_stmt->execute();
    $ownership_result = $check_ownership_stmt->get_result()->fetch_row()[0];

    if ($ownership_result == 0) {
        showToast('Unauthorized attempt to delete an assignment not belonging to your subject.', 'danger');
        header("Location: assignments.php");
        exit();
    }

    $query = "DELETE FROM assignments WHERE assignment_id = ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $assignment_id);
        if ($stmt->execute()) {
            showToast('Assignment deleted successfully.', 'success');
        } else {
            showToast('Failed to delete assignment: ' . $stmt->error, 'danger');
        }
    } else {
        showToast('Database error preparing statement for deleting assignment.', 'danger');
    }
    header("Location: assignments.php");
    exit();
}

// Handle GET request for edit mode
$edit_assignment = null;
if (isset($_GET['edit'])) {
    $edit_id = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
    if ($edit_id && $demo === false) {
        $query = "SELECT * FROM assignments WHERE assignment_id = ? AND subject_id = ?";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("ii", $edit_id, $subject_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $edit_assignment = $result->fetch_assoc();
            if (!$edit_assignment) {
                showToast('Assignment not found or you do not have permission to edit it.', 'danger');
                header("Location: assignments.php");
                exit();
            }
        } else {
            showToast('Database error preparing statement for fetching assignment details.', 'danger');
            header("Location: assignments.php");
            exit();
        }
    } else if ($demo === true) {
        showToast('Action not allowed: You are using a demo account and cannot edit assignments.', 'danger');
        header("Location: assignments.php");
        exit();
    }
}
ob_end_flush();
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
        <?php if ($demo === true): ?>
        <div class="alert alert-danger mt-3 text-center">
            You are using a **Demo account** on a live hosted website. You **cannot add, edit, or delete assignments**.
        </div>
        <?php endif; ?>
        <h2 class="text-primary">Manage Assignments</h2>

        <form method="POST">

            <?php if ($edit_assignment) : ?>
            <input type="hidden" name="assignment_id"
                value="<?php echo htmlspecialchars($edit_assignment['assignment_id']); ?>">

            <label for="title" class="form-label">Title</label>
            <input type="text" class="form-control mb-4" name="title"
                value="<?php echo htmlspecialchars($edit_assignment['title']); ?>" required
                <?php echo $demo === true ? 'disabled' : ''; ?>>

            <label for="Description" class="form-label">Description</label>
            <textarea name="description" class="form-control mb-4"
                <?php echo $demo === true ? 'disabled' : ''; ?>><?php echo htmlspecialchars($edit_assignment['description']); ?></textarea>

            <label for="Duedate" class="form-label">Due Date</label>
            <input type="date" class="form-control mb-3" name="due_date"
                value="<?php echo htmlspecialchars($edit_assignment['due_date']); ?>" required
                <?php echo $demo === true ? 'disabled' : ''; ?>>

            <?php if ($demo === true): ?>
            <button type="button" class="btn btn-primary mb-5" disabled>Update Assignment</button>
            <a href="assignments.php" class="btn btn-sm btn-outline-dark mb-5 ms-2">Cancel</a>
            <?php else: ?>
            <button type="submit" name="update_assignment" class="btn btn-primary mb-5 me-4">Update Assignment</button>
            <a href="assignments.php" class="btn btn-sm btn-outline-dark mb-5">Cancel</a>
            <?php endif; ?>

            <?php else : ?>
            <label for="Title" class="form-label">Title</label>
            <input type="text" class="form-control" name="title" required
                <?php echo $demo === true ? 'disabled' : ''; ?>>

            <label for="Description" class="form-label">Description</label>
            <textarea name="description" class="form-control mb-4"
                <?php echo $demo === true ? 'disabled' : ''; ?>></textarea>

            <div class="col-8 col-sm-5 col-lg-4">
                <label for="due_date" class="form-label">Due Date</label>
                <input type="date" name="due_date" class="form-control mb-3" required
                    <?php echo $demo === true ? 'disabled' : ''; ?>>
            </div>

            <?php if ($demo === true): ?>
            <button type="button" class="btn btn-primary mb-5" disabled>Add Assignment</button>
            <?php else: ?>
            <button type="submit" name="add_assignment" class="btn btn-primary mb-5">Add Assignment</button>
            <?php endif ?>

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
                $query = "SELECT * FROM assignments WHERE subject_id = ? ORDER BY due_date DESC, updated_at DESC";
                $stmt = $conn->prepare($query);
                if ($stmt) {
                    $stmt->bind_param("i", $subject_id);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $date = htmlspecialchars(date('Y-m-d h:i A', strtotime($row['updated_at'])));

                            echo "<tr>
                                <td>" . htmlspecialchars($row['title']) . "</td>
                                <td>" . htmlspecialchars($row['description']) . "</td>
                                <td>" . htmlspecialchars($row['due_date']) . "</td>
                                <td>" . $date . "</td>
                                <td class='d-flex'>";

                            if ($demo === true) {
                                echo "<button class='btn btn-sm btn-secondary me-1 me-sm-2' disabled>Edit</button>";
                                echo "<button class='btn btn-sm btn-danger' disabled>Delete</button>";
                            } else {
                                echo "<a href='?edit=" . htmlspecialchars($row['assignment_id']) . "'><button class='btn btn-sm btn-secondary me-1 me-sm-2'>Edit</button></a>";
                                echo "<button class='btn btn-sm btn-danger' onclick=\"" . $deleteConfirmFunction . "('" . htmlspecialchars($row['assignment_id']) . "', '" . htmlspecialchars($row['title'], ENT_QUOTES) . "')\">Delete</button>";
                            }
                            echo "</td></tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' class='text-center'>No assignments added for this subject yet.</td></tr>";
                    }
                } else {
                    echo "<tr><td colspan='5' class='text-center'>Error retrieving assignments.</td></tr>";
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