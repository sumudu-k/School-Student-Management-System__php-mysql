<?php
session_start();
ob_start();
include 'db.php';
include 'functions.php';

$deleteConfirmFunction = createDeleteModal('result');

if (!isset($_SESSION['teacherID'])) {
    header("Location: login.php");
    exit;
}

$teacherID = $_SESSION['teacherID'];

$query = "SELECT teaching_subject, role FROM teachers WHERE teacherID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $teacherID);
$stmt->execute();
$result = $stmt->get_result();
$teacher_data = $result->fetch_assoc();
$subject_id = $teacher_data['teaching_subject'];
$demo = ($teacher_data['role'] === 'demo');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_result'])) {
    if ($demo === true) {
        showToast('Action not allowed: You are using a demo account and cannot add results.', 'danger');
        header("Location: results.php");
        exit();
    }

    $index_number = trim($_POST['index_number']);
    $term_id = trim($_POST['term_id']);
    $marks = trim($_POST['marks']);
    $comment = trim($_POST['comment']);

    $_SESSION['last_term_id'] = $term_id;

    if (empty($index_number) || empty($term_id) || $marks === '' || !is_numeric($marks)) {
        showToast('Student, Term, and Marks are required. Marks must be a number.', 'danger');
        header("Location: results.php");
        exit();
    }

    $marks = floatval($marks);
    if ($marks < 0 || $marks > 100) {
        showToast('Marks must be between 0 and 100.', 'danger');
        header("Location: results.php");
        exit();
    }

    $index_number = htmlspecialchars($index_number);
    $comment = htmlspecialchars($comment);

    $check_student_subject_query = "SELECT COUNT(*) FROM user_subjects WHERE index_number = ? AND subject_id = ?";
    $check_student_subject_stmt = $conn->prepare($check_student_subject_query);
    $check_student_subject_stmt->bind_param("si", $index_number, $subject_id);
    $check_student_subject_stmt->execute();
    $student_registered = $check_student_subject_stmt->get_result()->fetch_row()[0];

    if ($student_registered == 0) {
        showToast('Selected student is not registered for your subject.', 'danger');
        header("Location: results.php");
        exit();
    }

    $query = "SELECT * FROM exam_results WHERE index_number = ? AND subject_id = ? AND term_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sii", $index_number, $subject_id, $term_id);
    $stmt->execute();
    $result_check = $stmt->get_result();

    if ($result_check->num_rows > 0) {
        showToast('Result already exists for this student in this term.', 'warning');
    } else {
        $query = "INSERT INTO exam_results (index_number, subject_id, term_id, marks, comment)
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("siiis", $index_number, $subject_id, $term_id, $marks, $comment);
        if ($stmt->execute()) {
            showToast('Result added successfully.', 'success');
        } else {
            showToast('Failed to add result.', 'danger');
        }
    }
    header("Location: results.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_result'])) {
    if ($demo === true) {
        showToast('Action not allowed: You are using a demo account and cannot update results.', 'danger');
        header("Location: results.php");
        exit();
    }

    $result_id = $_POST['result_id'];
    $marks = trim($_POST['marks']);
    $comment = trim($_POST['comment']);

    if ($marks === '' || !is_numeric($marks)) {
        showToast('Marks are required and must be a number.', 'danger');
        header("Location: results.php?edit=" . htmlspecialchars($result_id));
        exit();
    }

    $marks = floatval($marks);
    if ($marks < 0 || $marks > 100) {
        showToast('Marks must be between 0 and 100.', 'danger');
        header("Location: results.php?edit=" . htmlspecialchars($result_id));
        exit();
    }

    $comment = htmlspecialchars($comment);

    $check_ownership_query = "SELECT COUNT(*) FROM exam_results WHERE result_id = ? AND subject_id = ?";
    $check_ownership_stmt = $conn->prepare($check_ownership_query);
    $check_ownership_stmt->bind_param("ii", $result_id, $subject_id);
    $check_ownership_stmt->execute();
    $is_owner = $check_ownership_stmt->get_result()->fetch_row()[0];

    if ($is_owner == 0) {
        showToast('Unauthorized attempt to update a result not belonging to your subject.', 'danger');
        header("Location: results.php");
        exit();
    }

    $query = "UPDATE exam_results SET marks = ?, comment = ? WHERE result_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssi", $marks, $comment, $result_id);
    if ($stmt->execute()) {
        showToast('Result updated successfully.', 'success');
    } else {
        showToast('Failed to update result.', 'danger');
    }
    header("Location: results.php");
    exit();
}

if (isset($_GET['delete'])) {
    if ($demo === true) {
        showToast('Action not allowed: You are using a demo account and cannot delete results.', 'danger');
        header("Location: results.php");
        exit();
    }

    $result_id = $_GET['delete'];

    $check_ownership_query = "SELECT COUNT(*) FROM exam_results WHERE result_id = ? AND subject_id = ?";
    $check_ownership_stmt = $conn->prepare($check_ownership_query);
    $check_ownership_stmt->bind_param("ii", $result_id, $subject_id);
    $check_ownership_stmt->execute();
    $is_owner = $check_ownership_stmt->get_result()->fetch_row()[0];

    if ($is_owner == 0) {
        showToast('Unauthorized attempt to delete a result not belonging to your subject.', 'danger');
        header("Location: results.php");
        exit();
    }

    $query = "DELETE FROM exam_results WHERE result_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $result_id);
    if ($stmt->execute()) {
        showToast('Result deleted successfully.', 'success');
    } else {
        showToast('Failed to delete result.', 'danger');
    }
    header("Location: results.php");
    exit();
}
ob_end_flush();
?>
<!DOCTYPE html>
<html>

<head>
    <title>Manage Exam Results</title>
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
    <main class="container-lg " style="flex: 1;">
        <?php if ($demo === true): ?>
        <div class="alert alert-danger mt-3 text-center">
            You are using a **Demo account** on a live hosted website. You **cannot add, edit, or delete exam results**.
            Please
            set up your own local environment to access full features.
        </div>
        <?php endif; ?>

        <h2 class="text-primary mb-5">Manage Exam Results</h2>
        <h5 class="text-primary"><?= isset($_GET['edit']) ? 'Edit Result' : 'Add New Result'; ?></h5>
        <form method="POST" class="needs-validation" novalidate>
            <?php if (isset($_GET['edit'])) : ?>
            <?php
                if ($demo === true) {
                    showToast('Action not allowed: You are using a demo account and cannot edit results.', 'danger');
                    header("Location: results.php");
                    exit();
                }

                $result_id = $_GET['edit'];

                $query = "SELECT er.*, u.first_name, u.last_name, s.subject_name, t.term_name FROM exam_results er
                          JOIN users u ON er.index_number = u.index_number
                          JOIN subjects s ON er.subject_id = s.subject_id
                          JOIN terms t ON er.term_id = t.term_id
                          WHERE er.result_id = ? AND er.subject_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ii", $result_id, $subject_id);
                $stmt->execute();
                $result_data = $stmt->get_result();
                $row = $result_data->fetch_assoc();

                if (!$row) {
                    showToast('Unauthorized access or result not found.', 'danger');
                    header("Location: results.php");
                    exit();
                }
                ?>
            <input type="hidden" name="result_id" value="<?php echo htmlspecialchars($row['result_id']); ?>">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="index_number" class="form-label">Student</label>
                    <select name="index_number" id="index_number" class="form-select" disabled>
                        <option value="<?php echo htmlspecialchars($row['index_number']); ?>" selected>
                            <?php echo htmlspecialchars($row['first_name'] . " " . $row['last_name']); ?>
                        </option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="subject_id" class="form-label">Subject</label>
                    <select name="subject_id" id="subject_id" class="form-select" disabled>
                        <option value="<?php echo htmlspecialchars($row['subject_id']); ?>" selected>
                            <?php echo htmlspecialchars($row['subject_name']); ?>
                        </option>
                    </select>
                </div>
            </div>
            <div class="row mb-3">
                <div class=" col-6 col-md-6">
                    <label for="term_id" class="form-label">Term</label>
                    <select name="term_id" id="term_id" class="form-select" disabled>
                        <option value="<?php echo htmlspecialchars($row['term_id']); ?>" selected>
                            <?php echo htmlspecialchars($row['term_name']); ?>
                        </option>
                    </select>
                </div>
                <div class="col-6 col-md-6">
                    <label for="marks" class="form-label">Marks</label>
                    <input type="number" step="0.01" name="marks" id="marks" class="form-control"
                        value="<?php echo htmlspecialchars($row['marks']); ?>" required
                        <?php echo $demo === true ? 'disabled' : ''; ?>>
                    <div class="invalid-feedback">
                        Please enter marks between 0 and 100.
                    </div>
                </div>
                <div class="col-12 mt-3">
                    <label for="comment" class="form-label">Comment</label>
                    <textarea name="comment" id="comment" class="form-control" rows="3"
                        <?php echo $demo === true ? 'disabled' : ''; ?>><?php echo htmlspecialchars($row['comment']); ?></textarea>
                </div>
            </div>
            <?php if ($demo === true): ?>
            <button type="reset" name="" class="btn btn-primary me-4" disabled>Update Result</button>
            <a href="results.php" class="btn btn-outline-dark btn-sm">Cancel</a>
            <?php else: ?>
            <button type="submit" name="update_result" class="btn btn-primary me-4">Update Result</button>
            <a href="results.php" class="btn btn-outline-dark btn-sm">Cancel</a>
            <?php endif; ?>

            <?php else : ?>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="index_number" class="form-label">Student</label>
                    <select name="index_number" id="index_number" class="form-select" required
                        <?php echo $demo === true ? 'disabled' : ''; ?>>
                        <option value="" disabled selected>Select student</option>
                        <?php
                            $query = "SELECT u.index_number, u.first_name, u.last_name
                                      FROM users u
                                      JOIN user_subjects us ON u.index_number = us.index_number
                                      WHERE us.subject_id = ? ORDER BY u.first_name ASC";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param("i", $subject_id);
                            $stmt->execute();
                            $students_result = $stmt->get_result();
                            while ($student = $students_result->fetch_assoc()) {
                                echo "<option value='" . htmlspecialchars($student['index_number']) . "'>" . htmlspecialchars($student['first_name'] . " " . $student['last_name']) . "</option>";
                            }
                            ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <input type="hidden" name="subject_id" value="<?php echo htmlspecialchars($subject_id); ?>">
                    <label class="form-label">Subject</label>
                    <div class="form-control bg-light">
                        <?php
                            $query = "SELECT subject_name FROM subjects WHERE subject_id = ?";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param("i", $subject_id);
                            $stmt->execute();
                            $subject_result = $stmt->get_result();
                            $subject = $subject_result->fetch_assoc();
                            echo htmlspecialchars($subject['subject_name']);
                            ?>
                    </div>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-6 col-md-6">
                    <label for="term_id" class="form-label">Term</label>
                    <select name="term_id" id="term_id" class="form-select" required
                        <?php echo $demo === true ? 'disabled' : ''; ?>>
                        <option value="" disabled selected>Select term</option>
                        <?php
                            $query = "SELECT * FROM terms ORDER BY term_id ASC";
                            $terms_result = $conn->query($query);
                            $last_term_id = isset($_SESSION['last_term_id']) ? $_SESSION['last_term_id'] : '';
                            while ($term = $terms_result->fetch_assoc()) {
                                $selected = ($term['term_id'] == $last_term_id) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($term['term_id']) . "' $selected>" . htmlspecialchars($term['term_name']) . "</option>";
                            }
                            ?>
                    </select>
                </div>
                <div class="col-6 col-md-6">
                    <label for="marks" class="form-label">Marks</label>
                    <input type="number" name="marks" id="marks" class="form-control" required min="0" max="100"
                        step="0.01" <?php echo $demo === true ? 'disabled' : ''; ?>>
                    <div class="invalid-feedback">
                        Please enter marks between 0 and 100.
                    </div>
                </div>
                <div class="col-12 mt-3">
                    <label for="comment" class="form-label">Comment</label>
                    <textarea name="comment" id="comment" class="form-control" rows="2"
                        <?php echo $demo === true ? 'disabled' : ''; ?>></textarea>
                </div>
            </div>
            <?php if ($demo === true): ?>
            <button type="reset" name="" class="btn btn-primary me-4 mt-4" disabled>Add Result</button>
            <button type="reset" class="btn btn-sm btn-outline-dark mt-4" disabled>Reset</button>
            <?php else: ?>
            <button type="submit" name="add_result" class="btn btn-primary me-4 mt-4">Add Result</button>
            <button type="reset" class="btn btn-sm btn-outline-dark mt-4">Reset</button>
            <?php endif; ?>
            <?php endif; ?>
        </form>

        <h3 class="mt-5 mb-4 text-primary">Current Exam Results</h3>
        <div class="table-responsive">
            <table class="table table-hover table-sm table-bordered mb-4">
                <thead>
                    <tr class="table-primary">
                        <th>Student Name</th>
                        <th>Subject</th>
                        <th>Term</th>
                        <th>Marks</th>
                        <th>Comment</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT er.result_id, u.first_name, u.last_name, s.subject_name, t.term_name, er.marks, er.comment
                              FROM exam_results er
                              JOIN users u ON er.index_number = u.index_number
                              JOIN subjects s ON er.subject_id = s.subject_id
                              JOIN terms t ON er.term_id = t.term_id
                              WHERE er.subject_id = ?
                              ORDER BY u.first_name ASC, t.term_id ASC";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("i", $subject_id);
                    $stmt->execute();
                    $results_table = $stmt->get_result();
                    while ($row = $results_table->fetch_assoc()) {
                        echo "<tr>
                            <td>" . htmlspecialchars($row['first_name'] . " " . $row['last_name']) . "</td>
                            <td>" . htmlspecialchars($row['subject_name']) . "</td>
                            <td>" . htmlspecialchars($row['term_name']) . "</td>
                            <td>" . htmlspecialchars($row['marks']) . "</td>
                            <td>" . htmlspecialchars($row['comment']) . "</td>
                            <td>
                            <div class='d-flex'>";

                        if ($demo === true) {
                            echo "<button class='btn btn-secondary btn-sm me-2' disabled>Edit</button>";
                            echo "<button class='btn btn-danger btn-sm' disabled>Delete</button>";
                        } else {
                            echo "<a href='?edit=" . htmlspecialchars($row['result_id']) . "' class='btn btn-secondary btn-sm me-2'>Edit</a>";
                            echo "<button class='btn btn-danger btn-sm' onclick=\"" . $deleteConfirmFunction . "(" . htmlspecialchars($row['result_id']) . ", '" . htmlspecialchars($row['first_name'] . " " . $row['last_name'] . " - " . $row['subject_name'] . " - " . $row['term_name'], ENT_QUOTES) . "')\">Delete</button>";
                        }
                        echo "</div>
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