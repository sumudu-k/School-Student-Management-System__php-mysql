<?php
session_start();
include 'db.php';
include 'functions.php';
$deleteConfirmFunction = createDeleteModal('timetable');

if (!isset($_SESSION['teacherID'])) {
    header("Location: login.php");
    exit;
}

// Add Timetable
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_timetable'])) {
    $subject_id = $_POST['subject_id'];
    $exam_date = $_POST['exam_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $term_id = $_POST['term_id'];

    // Validate term_id to ensure it's within the valid range
    if ($term_id < 1 || $term_id > 6) {
        echo "<script>alert('Invalid term selected. Please select a valid term.');</script>";
    } else {
        $query = "INSERT INTO exams (subject_id, exam_date, start_time, end_time, term_id)
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isssi", $subject_id, $exam_date, $start_time, $end_time, $term_id);
        $stmt->execute();
        showToast('Timetable added successfully.', 'success');
    }
}

// Update Timetable
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_timetable'])) {
    $exam_id = $_POST['exam_id'];
    $subject_id = $_POST['subject_id'];
    $exam_date = $_POST['exam_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $term_id = $_POST['term_id'];

    // Validate term_id to ensure it's within the valid range (1 to 6)
    if ($term_id < 1 || $term_id > 6) {
        showToast('Invalid term selected. Please select a valid term.', 'error');
    } else {
        $query = "UPDATE exams SET subject_id = ?, exam_date = ?, start_time = ?, end_time = ?, term_id = ?
                  WHERE exam_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isssii", $subject_id, $exam_date, $start_time, $end_time, $term_id, $exam_id);
        $stmt->execute();
        showToast('Timetable updated successfully.', 'success');
    }
}

// Delete Timetable
if (isset($_GET['delete'])) {
    $exam_id = $_GET['delete'];
    $query = "DELETE FROM exams WHERE exam_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $exam_id);
    $stmt->execute();
    showToast('Timetable deleted successfully.', 'success');
}
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
            <div class="col-12 col-sm-8 col-md-8 col-lg-6 ">
                <form method="POST" class="mb-4">
                    <?php if (isset($_GET['edit'])) : ?>
                    <?php
                        $exam_id = $_GET['edit'];
                        $query = "SELECT * FROM exams WHERE exam_id = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("i", $exam_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $exam = $result->fetch_assoc();
                        ?>
                    <input type="hidden" name="exam_id" value="<?php echo $exam['exam_id']; ?>">
                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <select name="subject_id" id="subject" class="form-select">
                            <?php
                                $query = "SELECT * FROM subjects";
                                $result = $conn->query($query);
                                while ($row = $result->fetch_assoc()) {
                                    $selected = ($row['subject_id'] == $exam['subject_id']) ? 'selected' : '';
                                    echo "<option value='" . $row['subject_id'] . "' $selected>" . $row['subject_name'] . "</option>";
                                }
                                ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="term" class="form-label">Term</label>
                        <select name="term_id" id="term" class="form-select">
                            <?php
                                $terms = ['Term 1', 'Term 2', 'Term 3', 'Term 4', 'Term 5', 'Term 6'];
                                $last_term_id = isset($exam['term_id']) && $exam['term_id'] >= 1 && $exam['term_id'] <= 6 ? $exam['term_id'] : 1;
                                foreach ($terms as $id => $term) {
                                    $selected = ($id + 1 == $last_term_id) ? 'selected' : '';
                                    echo "<option value='" . ($id + 1) . "' $selected>$term</option>";
                                }
                                ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="exam_date" class="form-label">Exam Date</label>
                        <input type="date" class="form-control" id="exam_date" name="exam_date"
                            value="<?php echo $exam['exam_date']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="start_time" class="form-label">Start Time</label>
                        <input type="time" class="form-control" id="start_time" name="start_time"
                            value="<?php echo $exam['start_time']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="end_time" class="form-label">End Time</label>
                        <input type="time" class="form-control" id="end_time" name="end_time"
                            value="<?php echo $exam['end_time']; ?>" required>
                    </div>
                    <button type="submit" name="update_timetable" class="btn btn-primary mt-4 me-4">Update
                        Timetable</button>
                    <a href="timetable.php"><button type="button"
                            class="btn btn-sm btn-outline-dark mt-4">Cancel</button></a>
                    <?php else : ?>
                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <select name="subject_id" id="subject" class="form-select">
                            <?php
                                $query = "SELECT * FROM subjects";
                                $result = $conn->query($query);
                                while ($row = $result->fetch_assoc()) {
                                    echo "<option value='" . $row['subject_id'] . "'>" . $row['subject_name'] . "</option>";
                                }
                                ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="term" class="form-label">Term</label>
                        <select name="term_id" id="term" class="form-select">
                            <?php
                                $terms = ['Term 1', 'Term 2', 'Term 3', 'Term 4', 'Term 5', 'Term 6'];
                                $query = "SELECT term_id FROM exams ORDER BY exam_id DESC LIMIT 1";
                                $result = $conn->query($query);
                                $last_term_id = $result->num_rows > 0 ? $result->fetch_assoc()['term_id'] : 1;
                                $last_term_id = ($last_term_id >= 1 && $last_term_id <= 6) ? $last_term_id : 1;
                                foreach ($terms as $id => $term) {
                                    $selected = ($id + 1 == $last_term_id) ? 'selected' : '';
                                    echo "<option value='" . ($id + 1) . "' $selected>$term</option>";
                                }
                                ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="exam_date" class="form-label">Exam Date</label>
                        <input type="date" class="form-control" id="exam_date" name="exam_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="start_time" class="form-label">Start Time</label>
                        <input type="time" class="form-control" id="start_time" name="start_time" required>
                    </div>
                    <div class="mb-3">
                        <label for="end_time" class="form-label">End Time</label>
                        <input type="time" class="form-control" id="end_time" name="end_time" required>
                    </div>
                    <button type="submit" name="add_timetable" class="btn btn-primary mt-4">Add Timetable</button>
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
                    $query = "SELECT e.*, s.subject_name FROM exams e
                  JOIN subjects s ON e.subject_id = s.subject_id";
                    $result = $conn->query($query);
                    while ($row = $result->fetch_assoc()) {
                        $terms = ['Term 1', 'Term 2', 'Term 3', 'Term 4', 'Term 5', 'Term 6'];
                        $term_name = isset($row['term_id']) && $row['term_id'] >= 1 && $row['term_id'] <= 6 ? $terms[$row['term_id'] - 1] : 'Unknown Term';
                        echo "<tr>
                    <td>" . htmlspecialchars($row['subject_name']) . "</td>
                    <td>" . htmlspecialchars($term_name) . "</td>
                    <td>" . htmlspecialchars($row['exam_date']) . "</td>
                    <td>" . htmlspecialchars($row['start_time']) . "</td>
                    <td>" . htmlspecialchars($row['end_time']) . "</td>
                    <td>
                    <div class='d-flex'>
                    <a href='?edit=" . $row['exam_id'] . "' class='btn btn-primary btn-sm me-2'>Edit</a>
                    <button class='btn btn-danger btn-sm' onclick='" . $deleteConfirmFunction . "(" . $row['exam_id'] . ", \"" . htmlspecialchars($row['subject_name'], ENT_QUOTES) . "\")'>Delete</button>
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
    <?php
    include 'user/footer.php';
    ?>
    <script src="styles/js/bootstrap.bundle.min.js"></script>
</body>

</html>