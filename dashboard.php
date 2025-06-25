<?php
session_start();
if (!isset($_SESSION['teacherID'])) {
    header("Location: login.php");
    exit;
}
include 'db.php';

// Fetch the teacher's details 
$teacherID = $_SESSION['teacherID'];
$query = "SELECT * FROM teachers WHERE teacherID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $teacherID);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();

// Fetch the subject name and count of registered students using the teaching_subject ID
if ($teacher && isset($teacher['teaching_subject'])) {
    $subject_id = $teacher['teaching_subject'];

    // Fetch subject name
    $query = "SELECT subject_name FROM subjects WHERE subject_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    $subject_result = $stmt->get_result();
    $subject = $subject_result->fetch_assoc();
    $subject_name = $subject ? $subject['subject_name'] : 'Unknown Subject';

    // Count the number of students registered for the subject
    $query = "SELECT COUNT(*) AS student_count FROM user_subjects 
              JOIN users ON user_subjects.index_number = users.index_number 
              WHERE subject_id = ? AND users.is_approved = TRUE";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    $student_count_result = $stmt->get_result();
    $student_count_row = $student_count_result->fetch_assoc();
    $student_count = $student_count_row['student_count'];
} else {
    $subject_name = 'Unknown Subject';
    $student_count = 0;
}

$is_super_admin = ($_SESSION['position'] === 'classroom_teacher');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="styles/css/custom.css">
    <link rel="stylesheet" href="styles/css/bootstrap.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oleo+Script:wght@400;700&display=swap" rel="stylesheet">
    <style>
    .card-1 {
        background: linear-gradient(45deg, #4e73df, #6f8df7);
    }

    .card-2 {
        background: linear-gradient(45deg, #1cc88a, #47d7ac);
    }

    .card-3 {
        background: linear-gradient(45deg, #36b9cc, #4dd6e7);
    }

    .card-4 {
        background: linear-gradient(45deg, #f6c23e, #fbd283);
    }

    .card-7 {
        background: linear-gradient(45deg, #6b5b95, #9083bc);
    }

    .card-8 {
        background: linear-gradient(45deg, #45b7af, #7fd5cf);
    }

    .card-9 {
        background: linear-gradient(45deg, #cf556c, #e78c9c);
    }

    .card-10 {
        background: linear-gradient(45deg, #e77e23, #f1a667);
    }

    .card-11 {
        background: linear-gradient(45deg, #786fa6, #9f97c1);
    }

    .card-12 {
        background: linear-gradient(45deg, #2e86de, #70aae8);
    }

    .dashboard-card {
        transition: transform 0.3s;
    }

    .dashboard-card:hover {
        transform: translateY(-5px);
    }
    </style>
</head>

<body class="d-flex flex-column min-vh-100 bg-light">
    <?php
    include 'admin_navbar.php';
    ?>

    <main class="container-lg" style="flex: 1;">
        <div class="container mt-4">
            <h4 class="text-dark fw-bold mb-3">Welcome, <?= $teacher['full_name'] ?></h4>
            <?php if ($teacher): ?>
            <div class="row mb-4">
                <div class="col-md-6 pb-1">
                    <div class="bg-white shadow-sm rounded">
                        <div class="d-flex align-items-center ps-3">
                            <span class="badge rounded-circle bg-dark p-2 me-2">
                                <i class="fas fa-envelope fa-sm"></i>
                            </span>
                            <div>
                                <h6 class="mb-0 small text-muted">Email</h6>
                                <p class="mb-0 fw-bold"><?php echo htmlspecialchars($teacher['email']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 pb-1">
                    <div class="bg-white shadow-sm rounded">
                        <div class="d-flex align-items-center ps-3">
                            <span class="badge rounded-circle bg-warning p-2 me-2">
                                <i class="fas fa-book fa-sm"></i>
                            </span>
                            <div>
                                <h6 class="mb-0 small text-muted">Subject</h6>
                                <p class="mb-0 fw-bold"><?php echo htmlspecialchars($subject_name); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3 pb-1">
                    <div class="bg-white shadow-sm rounded">
                        <div class="d-flex align-items-center ps-3">
                            <span class="badge rounded-circle bg-danger p-2 me-2">
                                <i class="fas fa-users fa-sm"></i>
                            </span>
                            <div>
                                <h6 class="mb-0 small text-muted">Students Registered</h6>
                                <p class="mb-0 fw-bold"><?php echo $student_count; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <h4 class="pb-2 mb-4 border-bottom text-secondary fw-bold">Management Menu</h4>
            <div class="row g-4 mb-5">
                <div class="col-4 col-md-3 col-xl-2">
                    <div class="card h-100 dashboard-card card-1">
                        <a href="assignments.php" class="card-body text-center text-white text-decoration-none">
                            <i class="fas fa-tasks fs-1 d-block mb-3"></i>
                            <h6>Manage Assignments</h6>
                        </a>
                    </div>
                </div>
                <div class="col-4 col-md-3 col-xl-2">
                    <div class="card h-100 dashboard-card card-2">
                        <a href="assignment_marks.php" class="card-body text-center text-white text-decoration-none">
                            <i class="fas fa-clipboard-check fs-1 d-block mb-3"></i>
                            <h6>Assignments Marks</h6>
                        </a>
                    </div>
                </div>
                <div class="col-4 col-md-3 col-xl-2">
                    <div class="card h-100 dashboard-card card-3">
                        <a href="announcements.php" class="card-body text-center text-white text-decoration-none">
                            <i class="fas fa-bullhorn fs-1 d-block mb-3"></i>
                            <h6>Manage Announcements</h6>
                        </a>
                    </div>
                </div>
                <div class="col-4 col-md-3 col-xl-2">
                    <div class="card h-100 dashboard-card card-4">
                        <a href="results.php" class="card-body text-center text-white text-decoration-none">
                            <i class="fas fa-chart-line fs-1 d-block mb-3"></i>
                            <h6>Add Exam Results</h6>
                        </a>
                    </div>
                </div>

                <?php if ($is_super_admin): ?>
                <div class="col-12 mt-4">
                    <h4 class="pb-2 mb-4 border-bottom text-secondary fw-bold mt-5">Super Admin Functions</h4>
                </div>

                <div class="col-4 col-md-3 col-xl-2">
                    <div class="card h-100 dashboard-card card-7">
                        <a href="subjects.php" class="card-body text-center text-white text-decoration-none">
                            <i class="fas fa-book-open fs-1 d-block mb-3"></i>
                            <h6>Manage Subjects</h6>
                        </a>
                    </div>
                </div>
                <div class="col-4 col-md-3 col-xl-2">
                    <div class="card h-100 dashboard-card card-8">
                        <a href="timetable.php" class="card-body text-center text-white text-decoration-none">
                            <i class="fas fa-calendar-alt fs-1 d-block mb-3"></i>
                            <h6>Manage Exam Timetables</h6>
                        </a>
                    </div>
                </div>
                <div class="col-4 col-md-3 col-xl-2">
                    <div class="card h-100 dashboard-card card-9">
                        <a href="positions.php" class="card-body text-center text-white text-decoration-none">
                            <i class="fas fa-user-tag fs-1 d-block mb-3"></i>
                            <h6>Assign Student Positions</h6>
                        </a>
                    </div>
                </div>
                <div class="col-4 col-md-3 col-xl-2">
                    <div class="card h-100 dashboard-card card-10">
                        <a href="requests.php" class="card-body text-center text-white text-decoration-none">
                            <i class="fas fa-user-check fs-1 d-block mb-3"></i>
                            <h6>Review Admin Registration</h6>
                        </a>
                    </div>
                </div>
                <div class="col-4 col-md-3 col-xl-2">
                    <div class="card h-100 dashboard-card card-11">
                        <a href="review_users.php" class="card-body text-center text-white text-decoration-none">
                            <i class="fas fa-user-graduate fs-1 d-block mb-3"></i>
                            <h6>Review Student Registration</h6>
                        </a>
                    </div>
                </div>
                <div class="col-4 col-md-3 col-xl-2">
                    <div class="card h-100 dashboard-card card-12">
                        <a href="view_users.php" class="card-body text-center text-white text-decoration-none">
                            <i class="fas fa-users fs-1 d-block mb-3"></i>
                            <h6>View Students</h6>
                        </a>
                    </div>
                </div>
                <div class="col-4 col-md-3 col-xl-2">
                    <div class="card h-100 dashboard-card card-1">
                        <a href="view_teachers.php" class="card-body text-center text-white text-decoration-none">
                            <i class="fas fa-chalkboard-teacher fs-1 d-block mb-3"></i>
                            <h6>View Teachers</h6>
                        </a>
                    </div>
                </div>
                <div class="col-4 col-md-3 col-xl-2">
                    <div class="card h-100 dashboard-card card-2">
                        <a href="review_profile_updates.php"
                            class="card-body text-center text-white text-decoration-none">
                            <i class="fas fa-user-edit fs-1 d-block mb-3"></i>
                            <h6>Review Profile Updates</h6>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
<?php
include 'user/footer.php';
?>

</html>