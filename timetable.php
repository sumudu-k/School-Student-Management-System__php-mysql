<?php
session_start();
ob_start();

include 'db.php';
include 'functions.php';

$deleteConfirmFunction = createDeleteModal('timetable');

if (!isset($_SESSION['teacherID'])) {
    header("Location: login.php");
    exit;
}

$teacherID = $_SESSION['teacherID'];

// Fetch teacher's role to check for demo account
$stmt = $conn->prepare("SELECT position, role FROM teachers WHERE teacherID = ?");
$stmt->bind_param("s", $teacherID);
$stmt->execute();
$teacher_data = $stmt->get_result()->fetch_assoc();
$is_super_admin = ($teacher_data['position'] === 'classroom_teacher');
$is_demo_account = ($teacher_data['role'] === 'demo');

if (!$is_super_admin) {
    showToast('You do not have permission to access this page.', 'error');
    header("Location: login.php");
    exit;
}

// Add Timetable
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_timetable'])) {
    if ($is_demo_account) {
        showToast('Action not allowed: You are using a demo account and cannot add timetable entries.', 'danger');
        header("Location: timetable.php");
        exit();
    }

    $subject_id = filter_input(INPUT_POST, 'subject_id', FILTER_VALIDATE_INT);
    $exam_date = filter_input(INPUT_POST, 'exam_date', FILTER_SANITIZE_STRING);
    $start_time = filter_input(INPUT_POST, 'start_time', FILTER_SANITIZE_STRING);
    $end_time = filter_input(INPUT_POST, 'end_time', FILTER_SANITIZE_STRING);
    $term_id = filter_input(INPUT_POST, 'term_id', FILTER_VALIDATE_INT);

    // Validate inputs
    if (!$subject_id || empty($exam_date) || empty($start_time) || empty($end_time) || !$term_id || $term_id < 1 || $term_id > 6) {
        showToast('Invalid input for timetable entry. Please fill all fields correctly.', 'danger');
        header("Location: timetable.php");
        exit();
    }

    // Further date and time format validation 
    if (!isValidDate($exam_date) || !isValidTime($start_time) || !isValidTime($end_time)) {
        showToast('Invalid date or time format.', 'danger');
        header("Location: timetable.php");
        exit();
    }

    $query = "INSERT INTO exams (subject_id, exam_date, start_time, end_time, term_id) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("isssi", $subject_id, $exam_date, $start_time, $end_time, $term_id);
        if ($stmt->execute()) {
            showToast('Timetable added successfully.', 'success');
        } else {
            showToast('Failed to add timetable: ' . $stmt->error, 'danger');
        }
    } else {
        showToast('Database error preparing statement for add.', 'danger');
    }
    header("Location: timetable.php");
    exit();
}

// Update Timetable
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_timetable'])) {
    if ($is_demo_account) {
        showToast('Action not allowed: You are using a demo account and cannot update timetable entries.', 'danger');
        header("Location: timetable.php");
        exit();
    }

    $exam_id = filter_input(INPUT_POST, 'exam_id', FILTER_VALIDATE_INT);
    $subject_id = filter_input(INPUT_POST, 'subject_id', FILTER_VALIDATE_INT);
    $exam_date = filter_input(INPUT_POST, 'exam_date', FILTER_SANITIZE_STRING);
    $start_time = filter_input(INPUT_POST, 'start_time', FILTER_SANITIZE_STRING);
    $end_time = filter_input(INPUT_POST, 'end_time', FILTER_SANITIZE_STRING);
    $term_id = filter_input(INPUT_POST, 'term_id', FILTER_VALIDATE_INT);

    // Validate inputs
    if (!$exam_id || !$subject_id || empty($exam_date) || empty($start_time) || empty($end_time) || !$term_id || $term_id < 1 || $term_id > 6) {
        showToast('Invalid input for timetable update. Please fill all fields correctly.', 'danger');
        header("Location: timetable.php" . (isset($_GET['edit']) ? "?edit=" . htmlspecialchars($exam_id) : ""));
        exit();
    }

    // Further date and time format validation 
    if (!isValidDate($exam_date) || !isValidTime($start_time) || !isValidTime($end_time)) {
        showToast('Invalid date or time format.', 'danger');
        header("Location: timetable.php" . (isset($_GET['edit']) ? "?edit=" . htmlspecialchars($exam_id) : ""));
        exit();
    }

    // Check if exam exists before updating
    $check_query = "SELECT COUNT(*) FROM exams WHERE exam_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $exam_id);
    $check_stmt->execute();
    $exists = $check_stmt->get_result()->fetch_row()[0];

    if ($exists == 0) {
        showToast('Exam entry not found for update.', 'warning');
    } else {
        $query = "UPDATE exams SET subject_id = ?, exam_date = ?, start_time = ?, end_time = ?, term_id = ? WHERE exam_id = ?";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("isssii", $subject_id, $exam_date, $start_time, $end_time, $term_id, $exam_id);
            if ($stmt->execute()) {
                showToast('Timetable updated successfully.', 'success');
            } else {
                showToast('Failed to update timetable: ' . $stmt->error, 'danger');
            }
        } else {
            showToast('Database error preparing statement for update.', 'danger');
        }
    }
    header("Location: timetable.php");
    exit();
}

// Delete Timetable
if (isset($_GET['delete'])) {
    if ($is_demo_account) {
        showToast('Action not allowed: You are using a demo account and cannot delete timetable entries.', 'danger');
        header("Location: timetable.php");
        exit();
    }

    $exam_id = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);

    if (!$exam_id) {
        showToast('Invalid exam ID for deletion.', 'danger');
        header("Location: timetable.php");
        exit();
    }

    // Check if exam exists before deleting
    $check_query = "SELECT COUNT(*) FROM exams WHERE exam_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $exam_id);
    $check_stmt->execute();
    $exists = $check_stmt->get_result()->fetch_row()[0];

    if ($exists == 0) {
        showToast('Exam entry not found for deletion.', 'warning');
    } else {
        $query = "DELETE FROM exams WHERE exam_id = ?";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("i", $exam_id);
            if ($stmt->execute()) {
                showToast('Timetable deleted successfully.', 'success');
            } else {
                showToast('Failed to delete timetable: ' . $stmt->error, 'danger');
            }
        } else {
            showToast('Database error preparing statement for delete.', 'danger');
        }
    }
    header("Location: timetable.php");
    exit();
}

function isValidDate($dateString)
{
    return (bool)strtotime($dateString);
}

function isValidTime($timeString)
{
    return (bool)strtotime("1970-01-01 " . $timeString);
}

ob_end_flush();
?>
<!DOCTYPE html>
<html>

<head>
    <title>Manage Exam Timetable</title>
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
        <h2 class="text-primary mb-4">Manage Exam Timetable</h2>
        <div class="">
            <?php if ($is_demo_account): ?>
            <div class="alert alert-danger mt-3 text-center">
                You are using a **Demo account** on a live hosted website. You **cannot add, edit, or delete
                timetable entries**.
            </div>
            <?php endif; ?>
            <div class="col-12 col-sm-8 col-md-8 col-lg-6 ">


                <form method="POST" class="mb-4">
                    <?php if (isset($_GET['edit'])) : ?>
                    <?php
                        $exam = null;
                        $exam_id_edit = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
                        if ($exam_id_edit) {
                            $query = "SELECT * FROM exams WHERE exam_id = ?";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param("i", $exam_id_edit);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $exam = $result->fetch_assoc();
                            if (!$exam) {
                                showToast('Exam entry not found for editing.', 'danger');
                                header("Location: timetable.php");
                                exit();
                            }
                        } else {
                            showToast('Invalid exam ID for editing.', 'danger');
                            header("Location: timetable.php");
                            exit();
                        }
                        ?>
                    <input type="hidden" name="exam_id" value="<?php echo htmlspecialchars($exam['exam_id']); ?>">
                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <select name="subject_id" id="subject" class="form-select"
                            <?php echo $is_demo_account ? 'disabled' : ''; ?>>
                            <?php
                                $query_subjects = "SELECT subject_id, subject_name FROM subjects ORDER BY subject_name ASC";
                                $result_subjects = $conn->query($query_subjects);
                                if ($result_subjects->num_rows > 0) {
                                    while ($row_subject = $result_subjects->fetch_assoc()) {
                                        $selected = ($row_subject['subject_id'] == $exam['subject_id']) ? 'selected' : '';
                                        echo "<option value='" . htmlspecialchars($row_subject['subject_id']) . "' $selected>" . htmlspecialchars($row_subject['subject_name']) . "</option>";
                                    }
                                } else {
                                    echo "<option value=''>No Subjects Available</option>";
                                }
                                ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="term" class="form-label">Term</label>
                        <select name="term_id" id="term" class="form-select"
                            <?php echo $is_demo_account ? 'disabled' : ''; ?>>
                            <?php
                                $terms = ['Term 1', 'Term 2', 'Term 3', 'Term 4', 'Term 5', 'Term 6'];
                                $last_term_id = isset($exam['term_id']) && $exam['term_id'] >= 1 && $exam['term_id'] <= 6 ? $exam['term_id'] : 1;
                                foreach ($terms as $id => $term) {
                                    $selected = (($id + 1) == $last_term_id) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($id + 1) . "' $selected>" . htmlspecialchars($term) . "</option>";
                                }
                                ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="exam_date" class="form-label">Exam Date</label>
                        <input type="date" class="form-control" id="exam_date" name="exam_date"
                            value="<?php echo htmlspecialchars($exam['exam_date']); ?>" required
                            <?php echo $is_demo_account ? 'disabled' : ''; ?>>
                    </div>
                    <div class="mb-3">
                        <label for="start_time" class="form-label">Start Time</label>
                        <input type="time" class="form-control" id="start_time" name="start_time"
                            value="<?php echo htmlspecialchars($exam['start_time']); ?>" required
                            <?php echo $is_demo_account ? 'disabled' : ''; ?>>
                    </div>
                    <div class="mb-3">
                        <label for="end_time" class="form-label">End Time</label>
                        <input type="time" class="form-control" id="end_time" name="end_time"
                            value="<?php echo htmlspecialchars($exam['end_time']); ?>" required
                            <?php echo $is_demo_account ? 'disabled' : ''; ?>>
                    </div>
                    <button type="submit" name="update_timetable" class="btn btn-primary mt-4 me-4"
                        <?php echo $is_demo_account ? 'disabled' : ''; ?>>Update Timetable</button>
                    <a href="timetable.php"><button type="button"
                            class="btn btn-sm btn-outline-dark mt-4">Cancel</button></a>
                    <?php else : ?>
                    <h4 class="text-primary">Add New Timetable Entry</h4>
                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <select name="subject_id" id="subject" class="form-select"
                            <?php echo $is_demo_account ? 'disabled' : ''; ?>>
                            <?php
                                $query_subjects = "SELECT subject_id, subject_name FROM subjects ORDER BY subject_name ASC";
                                $result_subjects = $conn->query($query_subjects);
                                if ($result_subjects->num_rows > 0) {
                                    while ($row_subject = $result_subjects->fetch_assoc()) {
                                        echo "<option value='" . htmlspecialchars($row_subject['subject_id']) . "'>" . htmlspecialchars($row_subject['subject_name']) . "</option>";
                                    }
                                } else {
                                    echo "<option value=''>No Subjects Available</option>";
                                }
                                ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="term" class="form-label">Term</label>
                        <select name="term_id" id="term" class="form-select"
                            <?php echo $is_demo_account ? 'disabled' : ''; ?>>
                            <?php
                                $terms = ['Term 1', 'Term 2', 'Term 3', 'Term 4', 'Term 5', 'Term 6'];
                                $query_last_term = "SELECT term_id FROM exams ORDER BY exam_id DESC LIMIT 1";
                                $result_last_term = $conn->query($query_last_term);
                                $last_term_id = $result_last_term->num_rows > 0 ? $result_last_term->fetch_assoc()['term_id'] : 1;
                                $last_term_id = ($last_term_id >= 1 && $last_term_id <= 6) ? $last_term_id : 1;
                                foreach ($terms as $id => $term) {
                                    $selected = (($id + 1) == $last_term_id) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($id + 1) . "' $selected>" . htmlspecialchars($term) . "</option>";
                                }
                                ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="exam_date" class="form-label">Exam Date</label>
                        <input type="date" class="form-control" id="exam_date" name="exam_date" required
                            <?php echo $is_demo_account ? 'disabled' : ''; ?>>
                    </div>
                    <div class="mb-3">
                        <label for="start_time" class="form-label">Start Time</label>
                        <input type="time" class="form-control" id="start_time" name="start_time" required
                            <?php echo $is_demo_account ? 'disabled' : ''; ?>>
                    </div>
                    <div class="mb-3">
                        <label for="end_time" class="form-label">End Time</label>
                        <input type="time" class="form-control" id="end_time" name="end_time" required
                            <?php echo $is_demo_account ? 'disabled' : ''; ?>>
                    </div>
                    <button type="submit" name="add_timetable" class="btn btn-primary mt-4"
                        <?php echo $is_demo_account ? 'disabled' : ''; ?>>Add Timetable</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <h3 class="text-primary mb-4">Current Exam Timetable</h3>
        <div class="table-responsive">
            <table class="table table-hover table-sm table-bordered mb-4">
                <thead class="table-primary">
                    <tr>
                        <th>Subject</th>
                        <th>Term</th>
                        <th>Exam Date</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query_timetable = "SELECT e.*, s.subject_name FROM exams e JOIN subjects s ON e.subject_id = s.subject_id ORDER BY e.exam_date ASC, e.start_time ASC";
                    $result_timetable = $conn->query($query_timetable);
                    if ($result_timetable->num_rows > 0) {
                        while ($row = $result_timetable->fetch_assoc()) {
                            $terms = ['Term 1', 'Term 2', 'Term 3', 'Term 4', 'Term 5', 'Term 6'];
                            $term_name = isset($row['term_id']) && $row['term_id'] >= 1 && $row['term_id'] <= 6 ? $terms[$row['term_id'] - 1] : 'Unknown Term';
                            echo "<tr>
                                <td>" . htmlspecialchars($row['subject_name']) . "</td>
                                <td>" . htmlspecialchars($term_name) . "</td>
                                <td>" . htmlspecialchars($row['exam_date']) . "</td>
                                <td>" . htmlspecialchars($row['start_time']) . "</td>
                                <td>" . htmlspecialchars($row['end_time']) . "</td>
                                <td>
                                    <div class='d-flex'>";
                            if ($is_demo_account) {
                                echo "<button class='btn btn-primary btn-sm me-2' disabled>Edit</button>";
                                echo "<button class='btn btn-danger btn-sm' disabled>Delete</button>";
                            } else {
                                echo "<a href='?edit=" . htmlspecialchars($row['exam_id']) . "' class='btn btn-primary btn-sm me-2'>Edit</a>";
                                echo "<button class='btn btn-danger btn-sm' onclick='" . $deleteConfirmFunction . "(" . htmlspecialchars($row['exam_id']) . ", \"" . htmlspecialchars($row['subject_name'] . ' on ' . $row['exam_date'], ENT_QUOTES) . "\")'>Delete</button>";
                            }
                            echo "</div>
                                </td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' class='text-center'>No exam timetable entries found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
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