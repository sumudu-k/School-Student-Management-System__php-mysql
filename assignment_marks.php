<?php
session_start();
include 'db.php';
include 'functions.php';
$deleteConfirmFunction = createDeleteModal('assignment mark');
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

// Initialize variables for edit mode
$edit_mode = false;
$edit_id = '';
$edit_index_number = '';
$edit_assignment_id = '';
$edit_marks = '';
$edit_comment = '';

// Check if edit mode is requested
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $edit_id = $_GET['edit'];
    $query = "SELECT * FROM assignment_marks WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_data = $result->fetch_assoc();

    $edit_index_number = $edit_data['index_number'];
    $edit_assignment_id = $edit_data['assignment_id'];
    $edit_marks = $edit_data['marks'];
    $edit_comment = $edit_data['comment'];
}

// Add or Update Assignment Marks
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['assign_marks'])) {
        $index_number = $_POST['index_number'];
        $assignment_id = $_POST['assignment_id'];
        $marks = $_POST['marks'];
        $comment = $_POST['comment'];

        if (isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
            // Update existing marks
            $id = $_POST['edit_id'];
            $query = "UPDATE assignment_marks SET marks = ?, comment = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("dsi", $marks, $comment, $id);
            $stmt->execute();
            showToast('Marks updated successfully', 'success');
        } else {
            // Check if marks already exist for this student and assignment
            $query = "SELECT * FROM assignment_marks WHERE index_number = ? AND assignment_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $index_number, $assignment_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // Update existing marks
                $query = "UPDATE assignment_marks SET marks = ?, comment = ? WHERE index_number = ? AND assignment_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("dsis", $marks, $comment, $index_number, $assignment_id);
                $stmt->execute();
                showToast('Marks updated successfully', 'success');
            } else {
                // Insert new marks 
                $query = "INSERT INTO assignment_marks (assignment_id, index_number, marks, comment)
                        VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("isds", $assignment_id, $index_number, $marks, $comment);
                $stmt->execute();
                showToast('Marks assigned successfully', 'success');
            }
        }
    }
}

// Delete Assignment Marks
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $query = "DELETE FROM assignment_marks WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    showToast('Marks deleted successfully', 'success');
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Assign Assignment Marks</title>
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

<body class="d-flex flex-column min-vh-100 overflow-x-hidden">

    <main class="container-lg mt-4">
        <h2 class="text-primary mb-3"><?php echo $edit_mode ? 'Update' : 'Assign'; ?> Marks for Assignments</h2>
        <form method="POST">
            <?php if ($edit_mode): ?>
            <input type="hidden" name="edit_id" value="<?php echo $edit_id; ?>">
            <?php endif; ?>
            <div class="mb-3">
                <label for="student" class="form-label">Student</label>
                <div class="col-md-7 col-sm-8 col-lg-6 col-xl-4">
                    <select name="index_number" id="student" class="form-select" required
                        <?php echo $edit_mode ? 'disabled' : ''; ?>>
                        <option value="">Select Student</option>
                        <?php
                        $query = "SELECT u.index_number, u.first_name, u.last_name 
                                    FROM users u
                                    JOIN user_subjects us ON u.index_number = us.index_number
                                    WHERE us.subject_id = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("i", $subject_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        while ($row = $result->fetch_assoc()) {
                            $selected = ($edit_mode && $row['index_number'] == $edit_index_number) ? 'selected' : '';
                            echo "<option value='" . $row['index_number'] . "' $selected>" . $row['first_name'] . " " . $row['last_name'] . "</option>";
                        }
                        ?>
                    </select>
                    <?php if ($edit_mode): ?>
                    <input type="hidden" name="index_number" value="<?php echo $edit_index_number; ?>">
                    <?php endif; ?>
                </div>
            </div>
            <div class="mb-3">
                <label for="assignment" class="form-label">Assignment</label>
                <select name="assignment_id" id="assignment" class="form-select" required
                    <?php echo $edit_mode ? 'disabled' : ''; ?>>
                    <option value="">Select Assignment</option>
                    <?php
                    $query = "SELECT * FROM assignments WHERE subject_id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("i", $subject_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        $selected = ($edit_mode && $row['assignment_id'] == $edit_assignment_id) ? 'selected' : '';
                        echo "<option value='" . $row['assignment_id'] . "' $selected>" . $row['title'] . "</option>";
                    }
                    ?>
                </select>
                <?php if ($edit_mode): ?>
                <input type="hidden" name="assignment_id" value="<?php echo $edit_assignment_id; ?>">
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label for="marks" class="form-label">Marks</label>
                <div class="col-6 col-md-4 col-lg-2 ">
                    <input type="number" step="0.01" name="marks" id="marks" class="form-control"
                        value="<?php echo $edit_mode ? $edit_marks : ''; ?>" required>
                </div>
            </div>
            <div class="mb-3">
                <label for="comment" class="form-label">Comment</label>
                <textarea name="comment" id="comment" class="form-control" rows="3"
                    placeholder="Provide feedback to the student"><?php echo $edit_mode ? $edit_comment : ''; ?></textarea>
            </div>
            <button type="submit" name="assign_marks" class="btn btn-primary mb-4">
                <?php echo $edit_mode ? 'Update Marks' : 'Assign Marks'; ?>
            </button>
            <?php if ($edit_mode): ?>
            <a href="assignment_marks.php" class="btn btn-sm btn-outline-dark mb-4 ms-2">Cancel</a>
            <?php endif; ?>
        </form>

        <h3 class="text-primary mb-4">Current Assignment Marks</h3>
        <div class="table-responsive">
            <table class="table table-hover table-sm table-bordered mb-4">
                <thead>
                    <tr class="table-primary">
                        <th>Student Name</th>
                        <th>Assignment Title</th>
                        <th>Marks</th>
                        <th>Comment</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT am.id, u.*, a.title, am.marks, am.comment 
                FROM assignment_marks am
                JOIN users u ON am.index_number = u.index_number
                JOIN assignments a ON am.assignment_id = a.assignment_id
                WHERE a.subject_id = ?
                ORDER BY u.first_name ASC, a.title ASC";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("i", $subject_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                    <td>" . htmlspecialchars($row['first_name']) . " " . htmlspecialchars($row['last_name']) . "</td>
                    <td>" . htmlspecialchars($row['title']) . "</td>
                    <td>" . htmlspecialchars($row['marks']) . "</td>
                    <td>" . htmlspecialchars($row['comment']) . "</td>
                    <td>
                    <div class='d-flex flex-row'>
                        <a href='?edit=" . $row['id'] . "' class='btn btn-sm btn-secondary me-2'>Edit</a>

                        <button class='btn btn-sm btn-danger' onclick=\"" . $deleteConfirmFunction . "('" . $row['id'] . "', '" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name'], ENT_QUOTES) . "')\">Delete</button>
                                
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