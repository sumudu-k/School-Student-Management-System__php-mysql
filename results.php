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

// Add Result
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_result'])) {
    $index_number = $_POST['index_number'];
    $term_id = $_POST['term_id'];
    $marks = $_POST['marks'];
    $comment = $_POST['comment'];

    $_SESSION['last_term_id'] = $term_id;

    // Check if the result already exists for this student, subject, and term
    $query = "SELECT * FROM exam_results WHERE index_number = ? AND subject_id = ? AND term_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sii", $index_number, $subject_id, $term_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        showToast('Result already exists for this student in this term.', 'error');
    } else {
        // Insert new result
        $query = "INSERT INTO exam_results (index_number, subject_id, term_id, marks, comment)
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("siiis", $index_number, $subject_id, $term_id, $marks, $comment);
        $stmt->execute();
        showToast('Result added successfully.', 'success');
    }
}

// Edit Result
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_result'])) {
    $result_id = $_POST['result_id'];
    $marks = $_POST['marks'];
    $comment = $_POST['comment'];
    $query = "UPDATE exam_results SET marks = ?, comment = ? WHERE result_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssi", $marks, $comment, $result_id);
    $stmt->execute();
    showToast('Result updated successfully.', 'success');
}

// Delete Result
if (isset($_GET['delete'])) {
    $result_id = $_GET['delete'];
    $query = "DELETE FROM exam_results WHERE result_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $result_id);
    $stmt->execute();
    showToast('Result deleted successfully.', 'success');
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Manage Exam Results fgfd</title>
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
        <h2 class="text-primary mb-5">Manage Exam Results</h2>
        <h5 class="text-primary"><?= isset($_GET['edit']) ? 'Edit Result' : 'Add New Result'; ?></h5>
        <form method="POST" class="needs-validation" novalidate>
            <?php if (isset($_GET['edit'])) : ?>
            <?php
                $result_id = $_GET['edit'];
                $query = "SELECT * FROM exam_results WHERE result_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $result_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                ?>
            <input type="hidden" name="result_id" value="<?php echo $row['result_id']; ?>">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="index_number" class="form-label">Student</label>
                    <select name="index_number" id="index_number" class="form-select" disabled>

                        <?php
                            $query = "SELECT * FROM users";
                            $result = $conn->query($query);
                            while ($student = $result->fetch_assoc()) {
                                $selected = ($student['index_number'] == $row['index_number']) ? 'selected' : '';
                                echo "<option value='" . $student['index_number'] . "' $selected>" . $student['first_name'] . " " . $student['last_name'] . "</option>";
                            }
                            ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="subject_id" class="form-label">Subject</label>
                    <select name="subject_id" id="subject_id" class="form-select" disabled>
                        <?php
                            $query = "SELECT * FROM subjects";
                            $result = $conn->query($query);
                            while ($subject = $result->fetch_assoc()) {
                                $selected = ($subject['subject_id'] == $row['subject_id']) ? 'selected' : '';
                                echo "<option value='" . $subject['subject_id'] . "' $selected>" . $subject['subject_name'] . "</option>";
                            }
                            ?>
                    </select>
                </div>
            </div>
            <div class="row mb-3">
                <div class=" col-6 col-md-6">
                    <label for="term_id" class="form-label">Term</label>
                    <select name="term_id" id="term_id" class="form-select" disabled>
                        <?php
                            $query = "SELECT * FROM terms";
                            $result = $conn->query($query);
                            while ($term = $result->fetch_assoc()) {
                                $selected = ($term['term_id'] == $row['term_id']) ? 'selected' : '';
                                echo "<option value='" . $term['term_id'] . "' $selected>" . $term['term_name'] . "</option>";
                            }
                            ?>
                    </select>
                </div>
                <div class="col-6 col-md-6">
                    <label for="marks" class="form-label">Marks</label>
                    <input type="number" step="0.01" name="marks" id="marks" class="form-control"
                        value="<?php echo $row['marks']; ?>" required>
                </div>
                <div class="col-12 mt-3">
                    <label for="comment" class="form-label">Comment</label>
                    <textarea name="comment" id="comment" class="form-control"
                        rows="3"><?php echo $row['comment']; ?></textarea>
                </div>
            </div>
            <button type="submit" name="update_result" class="btn btn-primary me-4">Update Result</button>
            <a href="results.php" class="btn btn-outline-dark btn-sm">Cancel</a>
            <?php else : ?>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="index_number" class="form-label">Student</label>
                    <select name="index_number" id="index_number" class="form-select" required>
                        <option value="" disabled selected>Select student</option>
                        <?php
                            // Fetch only students registered for the teacher's subject
                            $query = "SELECT u.index_number, u.first_name, u.last_name 
                                      FROM users u
                                      JOIN user_subjects us ON u.index_number = us.index_number
                                      WHERE us.subject_id = ?";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param("i", $subject_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            while ($student = $result->fetch_assoc()) {
                                echo "<option value='" . $student['index_number'] . "'>" . $student['first_name'] . " " . $student['last_name'] . "</option>";
                            }
                            ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <input type="hidden" name="subject_id" value="<?php echo $subject_id; ?>">
                    <label class="form-label">Subject</label>
                    <div class="form-control bg-light">
                        <?php
                            $query = "SELECT subject_name FROM subjects WHERE subject_id = ?";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param("i", $subject_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $subject = $result->fetch_assoc();
                            echo htmlspecialchars($subject['subject_name']);
                            ?>
                    </div>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-6 col-md-6">
                    <label for="term_id" class="form-label">Term</label>
                    <select name="term_id" id="term_id" class="form-select" required>
                        <option value="" disabled selected>Select term</option>
                        <?php
                            $query = "SELECT * FROM terms";
                            $result = $conn->query($query);
                            $last_term_id = isset($_SESSION['last_term_id']) ? $_SESSION['last_term_id'] : 1;
                            while ($term = $result->fetch_assoc()) {
                                $selected = ($term['term_id'] == $last_term_id) ? 'selected' : '';
                                echo "<option value='" . $term['term_id'] . "' $selected>" . htmlspecialchars($term['term_name']) . "</option>";
                            }
                            ?>
                    </select>
                </div>
                <div class="col-6 col-md-6">
                    <label for="marks" class="form-label">Marks</label>
                    <input type="number" name="marks" id="marks" class="form-control" required>
                </div>
                <div class="col-12 mt-3">
                    <label for="comment" class="form-label">Comment</label>
                    <textarea name="comment" id="comment" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <button type="submit" name="add_result" class="btn btn-primary me-4 mt-4">Add Result</button>
            <button type="reset" class="btn btn-sm btn-outline-dark mt-4">Reset</button>
            <?php endif; ?>
        </form>

        <!-- View All Results -->
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
                  ORDER BY u.first_name ASC, t.term_name ASC";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("i", $subject_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                    <td>" . htmlspecialchars($row['first_name']) . " " . htmlspecialchars($row['last_name']) . "</td>
                    <td>" . htmlspecialchars($row['subject_name']) . "</td>
                    <td>" . htmlspecialchars($row['term_name']) . "</td>
                    <td>" . htmlspecialchars($row['marks']) . "</td>
                    <td>" . htmlspecialchars($row['comment']) . "</td>
                    <td>
                    <div class='d-flex'>
                    <a href='?edit=" . $row['result_id'] . "' class='btn btn-secondary btn-sm me-2'>Edit</a>
                    <button class='btn btn-danger btn-sm' onclick=\"" . $deleteConfirmFunction . "(" . $row['result_id'] . ", '" . htmlspecialchars($row['first_name'] . " " . $row['last_name'], ENT_QUOTES) . "')\">Delete</button>
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
    <?php include 'user/footer.php'; ?>
</body>

</html>