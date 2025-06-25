<?php
session_start();
if (!isset($_SESSION['index_number'])) {
    header("Location:login.php");
    exit;
}
include '../db.php';
include 'navbar.php';
$index_number = $_SESSION['index_number'];

// Fetch user details
$userName = "SELECT * FROM users WHERE index_number = ?";
$stmt = $conn->prepare($userName);
$stmt->bind_param("s", $index_number);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();


// Fetch ALL exams for ALL subjects 
$query = "SELECT e.exam_date, s.subject_name, e.start_time, e.end_time 
          FROM exams e
          JOIN subjects s ON e.subject_id = s.subject_id
          ORDER BY e.exam_date";
$exams = $conn->query($query);

// Fetch assignments for subjects the student is enrolled in
$query = "SELECT a.assignment_id, a.title, a.description, a.due_date, s.subject_name, IFNULL(am.marks, 'Not Graded') AS marks, IFNULL(am.comment, '-') AS comment
          FROM assignments a
          LEFT JOIN assignment_marks am ON a.assignment_id = am.assignment_id AND am.index_number = ?
          JOIN subjects s ON a.subject_id = s.subject_id
          JOIN user_subjects us ON a.subject_id = us.subject_id
          WHERE us.index_number = ?
          ORDER BY a.due_date ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $index_number, $index_number);
$stmt->execute();
$assignments = $stmt->get_result();

// Fetch exam results with term name
$query = "SELECT s.subject_name, t.term_name, er.marks, er.comment 
          FROM exam_results er
          JOIN subjects s ON er.subject_id = s.subject_id
          JOIN terms t ON er.term_id = t.term_id
          WHERE er.index_number = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $index_number);
$stmt->execute();
$results = $stmt->get_result();


// Fetch announcements for subjects the student is enrolled in
$query = "SELECT s.subject_name, a.title, a.description, a.announced_at 
          FROM announcements a
          JOIN subjects s ON a.subject_id = s.subject_id
          JOIN user_subjects us ON a.subject_id = us.subject_id
          WHERE us.index_number = ?
          ORDER BY a.announced_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $index_number);
$stmt->execute();
$announcements = $stmt->get_result();

// Fetch positions
$query = "SELECT position_name FROM positions WHERE index_number = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $index_number);
$stmt->execute();
$positions = $stmt->get_result();
$position = $positions->fetch_assoc();
?>
<!DOCTYPE html>
<html>

<head>
    <title> Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="../styles/css/bootstrap.min.css">
    <link rel="stylesheet" href="../styles/css/custom.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oleo+Script:wght@400;700&display=swap" rel="stylesheet">
</head>
<style>
.gradient-bg {
    background: linear-gradient(to bottom right, #d0f0ff, #ffffff);
}
</style>

<body style="display: flex; flex-direction: column; min-height: 100vh;" class="gradient-bg">

    <main class="container-lg" style="flex: 1;">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h5>Welcome, <b class="me-4"><?= htmlspecialchars($user['full_name']) ?></b> </h5>
            <?php
            if ($position) {
                echo '<span class="badge bg-success fs-6">' . htmlspecialchars($position['position_name']) . '</span>';
            }
            ?>
        </div>
        <ul class="nav nav-pills nav-fill ">
            <li class="nav-item  ">
                <button type="button " class="nav-link active mb-3" data-bs-toggle="pill"
                    data-bs-target="#announcements">Announcements</button>
            </li>
            <li class="nav-item">
                <button type="button" class="nav-link  mb-3" data-bs-toggle="pill"
                    data-bs-target="#assignments">Assignments</button>
            </li>
            <li class="nav-item">
                <button type="button" class="nav-link  mb-3" data-bs-toggle="pill" data-bs-target="#termexams">Term
                    Exams</button>
            </li>
            <li class="nav-item">
                <button type="button" class="nav-link mb-3" data-bs-toggle="pill" data-bs-target="#examresults">Exam
                    Results</button>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active " id="announcements">
                <table class="table table-sm table-hover table-bordered mb-5">
                    <tr class="table-primary">
                        <th>Subject</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Announced At</th>
                    </tr>
                    <?php while ($row = $announcements->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                        <td><?php echo date('M d, Y h:i A', strtotime($row['announced_at'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
            <div class="tab-pane fade" id="assignments">
                <table class="table table-sm table-hover table-bordered  mb-5">
                    <tr class="table-primary">
                        <th>Subject</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Due Date</th>
                        <th>Marks</th>
                        <th>Comment</th>
                    </tr>
                    <?php while ($row = $assignments->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                        <td><?php echo htmlspecialchars($row['due_date']); ?></td>
                        <td><?php echo htmlspecialchars($row['marks']); ?></td>
                        <td><?php echo htmlspecialchars($row['comment']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
            <div class="tab-pane fade" id="termexams">
                <table class="table table-sm table-hover table-bordered mb-5">
                    <tr class="table-primary">
                        <th>Date</th>
                        <th>Subject</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                    </tr>
                    <?php while ($row = $exams->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['exam_date']); ?></td>
                        <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['start_time']); ?></td>
                        <td><?php echo htmlspecialchars($row['end_time']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
            <div class="tab-pane fade" id="examresults">
                <table class="table table-sm table-hover table-bordered mb-5">
                    <tr class="table-primary">
                        <th>Subject</th>
                        <th>Term</th>
                        <th>Marks</th>
                        <th>Comment</th>
                    </tr>
                    <?php while ($row = $results->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['term_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['marks']); ?></td>
                        <td><?php echo htmlspecialchars($row['comment']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
            </ul>
        </div>
    </main>

    <?php
    include 'footer.php';
    ?>
    <script src="../styles/js/bootstrap.bundle.min.js"></script>
    <script>
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))
    </script>
</body>

</html>