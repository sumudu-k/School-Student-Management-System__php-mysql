<?php
session_start();
ob_start();

include 'db.php';
include 'functions.php';

$deleteConfirmFunction = createDeleteModal('assignment mark');

if (!isset($_SESSION['teacherID'])) {
    header("Location: login.php");
    exit;
}

$teacherID = $_SESSION['teacherID'];

// Fetch the teacher's registered subject AND ROLE
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

// If the teacher has no assigned subject, they cannot manage assignment marks
if (empty($subject_id)) {
    showToast('You are not assigned to a teaching subject and cannot manage assignment marks.', 'danger');
    header("Location: dashboard.php");
    exit();
}

// Initialize variables for edit mode
$edit_mode = false;
$edit_id = '';
$edit_index_number = '';
$edit_assignment_id = '';
$edit_marks = '';
$edit_comment = '';

// Check if edit mode is requested
if (isset($_GET['edit'])) {
    $edit_id = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);

    // Prevent editing if it's a demo account or invalid ID
    if ($demo === true) {
        showToast('Action not allowed: You are using a demo account and cannot edit marks.', 'danger');
        header("Location: assignment_marks.php");
        exit();
    }
    if (!$edit_id) {
        showToast('Invalid assignment mark ID for editing.', 'danger');
        header("Location: assignment_marks.php");
        exit();
    }

    $query = "SELECT am.id, am.index_number, am.assignment_id, am.marks, am.comment, a.subject_id 
              FROM assignment_marks am 
              JOIN assignments a ON am.assignment_id = a.assignment_id 
              WHERE am.id = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        showToast('Database error: Could not prepare edit data query.', 'danger');
        header("Location: assignment_marks.php");
        exit();
    }
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_data = $result->fetch_assoc();

    // Ensure the data being edited belongs to the teacher's subject
    if (!$edit_data || $edit_data['subject_id'] != $subject_id) {
        showToast('Unauthorized access or invalid assignment mark.', 'danger');
        header("Location: assignment_marks.php");
        exit();
    }

    $edit_mode = true;
    $edit_index_number = $edit_data['index_number'];
    $edit_assignment_id = $edit_data['assignment_id'];
    $edit_marks = $edit_data['marks'];
    $edit_comment = $edit_data['comment'];
}

// Add or Update Assignment Marks
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_marks'])) {
    if ($demo === true) {
        showToast('Action not allowed: You are using a demo account and cannot assign or update marks.', 'danger');
        header("Location: assignment_marks.php");
        exit();
    }

    $index_number = filter_input(INPUT_POST, 'index_number', FILTER_SANITIZE_STRING);
    $assignment_id = filter_input(INPUT_POST, 'assignment_id', FILTER_VALIDATE_INT);
    $marks = filter_input(INPUT_POST, 'marks', FILTER_VALIDATE_FLOAT);
    $comment = filter_input(INPUT_POST, 'comment', FILTER_SANITIZE_STRING);
    $edit_id_post = filter_input(INPUT_POST, 'edit_id', FILTER_VALIDATE_INT);

    // Validate inputs
    if (empty($index_number) || !$assignment_id || $marks === false || $marks < 0 || $marks > 100) {
        showToast('Invalid input. Please ensure student, assignment, and marks (0-100) are valid.', 'danger');
        header("Location: assignment_marks.php" . ($edit_id_post ? "?edit=" . htmlspecialchars($edit_id_post) : ""));
        exit();
    }

    if ($edit_id_post) {
        // Update existing marks
        $check_ownership_query = "SELECT COUNT(*) FROM assignment_marks am JOIN assignments a ON am.assignment_id = a.assignment_id WHERE am.id = ? AND a.subject_id = ?";
        $check_ownership_stmt = $conn->prepare($check_ownership_query);
        if (!$check_ownership_stmt) {
            showToast('Database error: Could not prepare ownership check statement for update.', 'danger');
            header("Location: assignment_marks.php");
            exit();
        }
        $check_ownership_stmt->bind_param("ii", $edit_id_post, $subject_id);
        $check_ownership_stmt->execute();
        $ownership_result = $check_ownership_stmt->get_result()->fetch_row()[0];

        if ($ownership_result == 0) {
            showToast('Unauthorized attempt to update marks for a different subject or mark not found.', 'danger');
            header("Location: assignment_marks.php");
            exit();
        }

        $query = "UPDATE assignment_marks SET marks = ?, comment = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("dsi", $marks, $comment, $edit_id_post);
            if ($stmt->execute()) {
                showToast('Marks updated successfully.', 'success');
            } else {
                showToast('Failed to update marks: ' . $stmt->error, 'danger');
            }
        } else {
            showToast('Database error preparing statement for update.', 'danger');
        }
    } else {
        // Add new marks
        $check_student_query = "SELECT COUNT(*) FROM user_subjects WHERE index_number = ? AND subject_id = ?";
        $check_student_stmt = $conn->prepare($check_student_query);
        if (!$check_student_stmt) {
            showToast('Database error: Could not prepare student check query.', 'danger');
            header("Location: assignment_marks.php");
            exit();
        }
        $check_student_stmt->bind_param("si", $index_number, $subject_id);
        $check_student_stmt->execute();
        $student_exists = $check_student_stmt->get_result()->fetch_row()[0];

        if ($student_exists == 0) {
            showToast('Selected student does not belong to your subject or is not registered.', 'danger');
            header("Location: assignment_marks.php");
            exit();
        }

        // Ensure that the assignment selected belongs to this teacher's subject
        $check_assignment_query = "SELECT COUNT(*) FROM assignments WHERE assignment_id = ? AND subject_id = ?";
        $check_assignment_stmt = $conn->prepare($check_assignment_query);
        if (!$check_assignment_stmt) {
            showToast('Database error: Could not prepare assignment check query.', 'danger');
            header("Location: assignment_marks.php");
            exit();
        }
        $check_assignment_stmt->bind_param("ii", $assignment_id, $subject_id);
        $check_assignment_stmt->execute();
        $assignment_exists = $check_assignment_stmt->get_result()->fetch_row()[0];

        if ($assignment_exists == 0) {
            showToast('Selected assignment does not belong to your subject or is not found.', 'danger');
            header("Location: assignment_marks.php");
            exit();
        }

        // Check if marks already exist for this student and assignment combination
        $query = "SELECT id FROM assignment_marks WHERE index_number = ? AND assignment_id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            showToast('Database error: Could not prepare existing marks check query.', 'danger');
            header("Location: assignment_marks.php");
            exit();
        }
        $stmt->bind_param("si", $index_number, $assignment_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // If marks already exist, update them
            $existing_mark_id = $result->fetch_assoc()['id'];
            $query = "UPDATE assignment_marks SET marks = ?, comment = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param("dsi", $marks, $comment, $existing_mark_id);
                if ($stmt->execute()) {
                    showToast('Marks for existing entry updated successfully.', 'info');
                } else {
                    showToast('Failed to update existing marks: ' . $stmt->error, 'danger');
                }
            } else {
                showToast('Database error preparing statement for existing marks update.', 'danger');
            }
        } else {
            // Insert new marks
            $query = "INSERT INTO assignment_marks (assignment_id, index_number, marks, comment) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param("isds", $assignment_id, $index_number, $marks, $comment);
                if ($stmt->execute()) {
                    showToast('Marks assigned successfully.', 'success');
                } else {
                    showToast('Failed to assign marks: ' . $stmt->error, 'danger');
                }
            } else {
                showToast('Database error preparing statement for new marks insertion.', 'danger');
            }
        }
    }
    header("Location: assignment_marks.php");
    exit();
}

// Delete Assignment Marks
if (isset($_GET['delete'])) {
    $id_to_delete = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);

    if ($demo === true) {
        showToast('Action not allowed: You are using a demo account and cannot delete marks.', 'danger');
        header("Location: assignment_marks.php");
        exit();
    }
    if (!$id_to_delete) {
        showToast('Invalid assignment mark ID for deletion.', 'danger');
        header("Location: assignment_marks.php");
        exit();
    }

    // Verify that the mark being deleted belongs to the current teacher's subject
    $check_ownership_query = "SELECT COUNT(*) FROM assignment_marks am JOIN assignments a ON am.assignment_id = a.assignment_id WHERE am.id = ? AND a.subject_id = ?";
    $check_ownership_stmt = $conn->prepare($check_ownership_query);
    if (!$check_ownership_stmt) {
        showToast('Database error: Could not prepare ownership check statement for delete.', 'danger');
        header("Location: assignment_marks.php");
        exit();
    }
    $check_ownership_stmt->bind_param("ii", $id_to_delete, $subject_id);
    $check_ownership_stmt->execute();
    $ownership_result = $check_ownership_stmt->get_result()->fetch_row()[0];

    if ($ownership_result == 0) {
        showToast('Unauthorized attempt to delete marks for a different subject or mark not found.', 'danger');
        header("Location: assignment_marks.php");
        exit();
    }

    $query = "DELETE FROM assignment_marks WHERE id = ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $id_to_delete);
        if ($stmt->execute()) {
            showToast('Marks deleted successfully.', 'success');
        } else {
            showToast('Failed to delete marks: ' . $stmt->error, 'danger');
        }
    } else {
        showToast('Database error preparing statement for delete.', 'danger');
    }
    header("Location: assignment_marks.php");
    exit();
}
ob_end_flush();
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
        <?php if ($demo === true): ?>
        <div class="alert alert-danger mt-3 text-center">
            You are using a **Demo account** on a live hosted website. You **cannot assign, update, or delete
            assignment marks**.
        </div>
        <?php endif; ?>

        <h2 class="text-primary mb-3"><?php echo $edit_mode ? 'Update' : 'Assign'; ?> Marks for Assignments</h2>
        <form method="POST">
            <?php if ($edit_mode): ?>
            <input type="hidden" name="edit_id" value="<?php echo htmlspecialchars($edit_id); ?>">
            <?php endif; ?>
            <div class="mb-3">
                <label for="student" class="form-label">Student</label>
                <div class="col-md-7 col-sm-8 col-lg-6 col-xl-4">
                    <select name="index_number" id="student" class="form-select" required
                        <?php echo $edit_mode || $demo === true ? 'disabled' : ''; ?>>
                        <option value="">Select Student</option>
                        <?php
                        // Fetch students for the current teacher's subject
                        $query_students = "SELECT u.index_number, u.first_name, u.last_name 
                                         FROM users u
                                         JOIN user_subjects us ON u.index_number = us.index_number
                                         WHERE us.subject_id = ? ORDER BY u.first_name ASC";
                        $stmt_students = $conn->prepare($query_students);
                        if ($stmt_students) {
                            $stmt_students->bind_param("i", $subject_id);
                            $stmt_students->execute();
                            $result_students = $stmt_students->get_result();
                            while ($row_student = $result_students->fetch_assoc()) {
                                $selected = ($edit_mode && $row_student['index_number'] == $edit_index_number) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($row_student['index_number']) . "' $selected>" . htmlspecialchars($row_student['first_name']) . " " . htmlspecialchars($row_student['last_name']) . "</option>";
                            }
                        } else {
                            echo "<option value=''>Error loading students</option>";
                        }
                        ?>
                    </select>
                    <?php if ($edit_mode || $demo === true): ?>
                    <input type="hidden" name="index_number"
                        value="<?php echo htmlspecialchars($edit_index_number); ?>">
                    <?php endif; ?>
                </div>
            </div>
            <div class="mb-3">
                <label for="assignment" class="form-label">Assignment</label>
                <div class="col-md-7 col-sm-8 col-lg-6 col-xl-4">
                    <select name="assignment_id" id="assignment" class="form-select" required
                        <?php echo $edit_mode || $demo === true ? 'disabled' : ''; ?>>
                        <option value="">Select Assignment</option>
                        <?php
                        // Fetch assignments for the current teacher's subject
                        $query_assignments = "SELECT assignment_id, title FROM assignments WHERE subject_id = ? ORDER BY title ASC";
                        $stmt_assignments = $conn->prepare($query_assignments);
                        if ($stmt_assignments) {
                            $stmt_assignments->bind_param("i", $subject_id);
                            $stmt_assignments->execute();
                            $result_assignments = $stmt_assignments->get_result();
                            while ($row_assignment = $result_assignments->fetch_assoc()) {
                                $selected = ($edit_mode && $row_assignment['assignment_id'] == $edit_assignment_id) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($row_assignment['assignment_id']) . "' $selected>" . htmlspecialchars($row_assignment['title']) . "</option>";
                            }
                        } else {
                            echo "<option value=''>Error loading assignments</option>";
                        }
                        ?>
                    </select>
                    <?php if ($edit_mode || $demo === true): ?>
                    <input type="hidden" name="assignment_id"
                        value="<?php echo htmlspecialchars($edit_assignment_id); ?>">
                    <?php endif; ?>
                </div>
            </div>
            <div class="mb-3">
                <label for="marks" class="form-label">Marks</label>
                <div class="col-6 col-md-4 col-lg-2 ">
                    <input type="number" step="0.01" name="marks" id="marks" class="form-control"
                        value="<?php echo $edit_mode ? htmlspecialchars($edit_marks) : ''; ?>" required
                        <?php echo $demo === true ? 'disabled' : ''; ?>>
                </div>
            </div>
            <div class="mb-3">
                <label for="comment" class="form-label">Comment</label>
                <textarea name="comment" id="comment" class="form-control" rows="3"
                    placeholder="Provide feedback to the student"
                    <?php echo $demo === true ? 'disabled' : ''; ?>><?php echo $edit_mode ? htmlspecialchars($edit_comment) : ''; ?></textarea>
            </div>

            <?php if ($demo === true): ?>
            <button type="button" class="btn btn-primary mb-4" disabled>
                <?php echo $edit_mode ? 'Update Marks' : 'Assign Marks'; ?>
            </button>
            <?php if ($edit_mode): ?>
            <a href="assignment_marks.php" class="btn btn-sm btn-outline-dark mb-4 ms-2">Cancel</a>
            <?php endif; ?>
            <?php else: ?>
            <button type="submit" name="assign_marks" class="btn btn-primary mb-4">
                <?php echo $edit_mode ? 'Update Marks' : 'Assign Marks'; ?>
            </button>
            <?php if ($edit_mode): ?>
            <a href="assignment_marks.php" class="btn btn-sm btn-outline-dark mb-4 ms-2">Cancel</a>
            <?php endif; ?>
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
                    // Fetch assignment marks for the current teacher's subject
                    $query_display_marks = "SELECT am.id, u.first_name, u.last_name, a.title, am.marks, am.comment 
                                         FROM assignment_marks am
                                         JOIN users u ON am.index_number = u.index_number
                                         JOIN assignments a ON am.assignment_id = a.assignment_id
                                         WHERE a.subject_id = ?
                                         ORDER BY u.first_name ASC, a.title ASC";
                    $stmt_display_marks = $conn->prepare($query_display_marks);
                    if ($stmt_display_marks) {
                        $stmt_display_marks->bind_param("i", $subject_id);
                        $stmt_display_marks->execute();
                        $result_display_marks = $stmt_display_marks->get_result();

                        if ($result_display_marks->num_rows > 0) {
                            while ($row_mark = $result_display_marks->fetch_assoc()) {
                                echo "<tr>
                                    <td>" . htmlspecialchars($row_mark['first_name']) . " " . htmlspecialchars($row_mark['last_name']) . "</td>
                                    <td>" . htmlspecialchars($row_mark['title']) . "</td>
                                    <td>" . htmlspecialchars($row_mark['marks']) . "</td>
                                    <td>" . htmlspecialchars($row_mark['comment']) . "</td>
                                    <td>
                                        <div class='d-flex flex-row'>";

                                if ($demo === true) {
                                    echo "<button class='btn btn-sm btn-secondary me-2' disabled>Edit</button>";
                                    echo "<button class='btn btn-sm btn-danger' disabled>Delete</button>";
                                } else {
                                    echo "<a href='?edit=" . htmlspecialchars($row_mark['id']) . "' class='btn btn-sm btn-secondary me-2'>Edit</a>";
                                    echo "<button class='btn btn-sm btn-danger' onclick=\"" . $deleteConfirmFunction . "('" . htmlspecialchars($row_mark['id']) . "', '" . htmlspecialchars($row_mark['first_name'] . ' ' . $row_mark['last_name'] . ' - ' . $row_mark['title'], ENT_QUOTES) . "')\">Delete</button>";
                                }
                                echo "</div>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-center'>No assignment marks recorded for your subject.</td></tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' class='text-center'>Error retrieving assignment marks.</td></tr>";
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