<?php
session_start();
ob_start();

include 'db.php';
include 'functions.php';

$deleteConfirmFunction = createDeleteModal('subject');

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

// Add Subject
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_subject'])) {
    if ($is_demo_account) {
        showToast('Action not allowed: You are using a demo account and cannot add subjects.', 'danger');
        header("Location: subjects.php");
        exit();
    }

    $subject_name = trim($_POST['subject_name']);

    if (empty($subject_name)) {
        showToast('Subject name cannot be empty.', 'danger');
        header("Location: subjects.php");
        exit();
    }

    $subject_name = htmlspecialchars($subject_name, ENT_QUOTES, 'UTF-8');

    // Check if subject already exists
    $check_query = "SELECT COUNT(*) FROM subjects WHERE subject_name = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("s", $subject_name);
    $check_stmt->execute();
    $exists = $check_stmt->get_result()->fetch_row()[0];

    if ($exists > 0) {
        showToast('Subject already exists.', 'warning');
    } else {
        $query = "INSERT INTO subjects (subject_name) VALUES (?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $subject_name);
        if ($stmt->execute()) {
            showToast('Subject added successfully.', 'success');
        } else {
            showToast('Failed to add subject.', 'danger');
        }
    }
    header("Location: subjects.php");
    exit();
}

// Update Subject
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_subject'])) {
    if ($is_demo_account) {
        showToast('Action not allowed: You are using a demo account and cannot update subjects.', 'danger');
        header("Location: subjects.php");
        exit();
    }

    $subject_id = filter_input(INPUT_POST, 'subject_id', FILTER_VALIDATE_INT);
    $subject_name = trim($_POST['subject_name']);

    if (!$subject_id) {
        showToast('Invalid subject ID for update.', 'danger');
        header("Location: subjects.php");
        exit();
    }

    if (empty($subject_name)) {
        showToast('Subject name cannot be empty.', 'danger');
        header("Location: subjects.php?edit=" . htmlspecialchars($subject_id));
        exit();
    }

    $subject_name = htmlspecialchars($subject_name, ENT_QUOTES, 'UTF-8');

    // Check for duplicate subject name, excluding the current subject being updated
    $check_query = "SELECT COUNT(*) FROM subjects WHERE subject_name = ? AND subject_id != ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("si", $subject_name, $subject_id);
    $check_stmt->execute();
    $exists = $check_stmt->get_result()->fetch_row()[0];

    if ($exists > 0) {
        showToast('Another subject with this name already exists.', 'warning');
    } else {
        $query = "UPDATE subjects SET subject_name = ? WHERE subject_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $subject_name, $subject_id);
        if ($stmt->execute()) {
            showToast('Subject updated successfully.', 'success');
        } else {
            showToast('Failed to update subject.', 'danger');
        }
    }
    header("Location: subjects.php");
    exit();
}

// Delete Subject
if (isset($_GET['delete'])) {
    if ($is_demo_account) {
        showToast('Action not allowed: You are using a demo account and cannot delete subjects.', 'danger');
        header("Location: subjects.php");
        exit();
    }

    $subject_id = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);

    if (!$subject_id) {
        showToast('Invalid subject ID for deletion.', 'danger');
        header("Location: subjects.php");
        exit();
    }

    // Check if subject exists before attempting to delete
    $check_query = "SELECT COUNT(*) FROM subjects WHERE subject_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $subject_id);
    $check_stmt->execute();
    $exists = $check_stmt->get_result()->fetch_row()[0];

    if ($exists == 0) {
        showToast('Subject not found.', 'warning');
    } else {
        $query = "DELETE FROM subjects WHERE subject_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $subject_id);
        if ($stmt->execute()) {
            showToast('Subject deleted successfully.', 'danger');
        } else {
            showToast('Failed to delete subject. It might be referenced by other records (e.g., results or teacher assignments).', 'danger');
        }
    }
    header("Location: subjects.php");
    exit();
}
ob_end_flush();
?>
<!DOCTYPE html>
<html>

<head>
    <title>Manage Subjects</title>
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
        <?php if ($is_demo_account): ?>
        <div class="alert alert-danger mt-3 text-center">
            You are using a **Demo account** on a live hosted website. You **cannot add, edit, or delete subjects**.
            Please set up your own local environment to access full features.
        </div>
        <?php endif; ?>

        <h2 class="text-primary mb-5">Manage Subjects</h2>
        <h4 class="text-primary"><?php echo isset($_GET['edit']) ? 'Edit Subject' : 'Add New Subject'; ?></h4>
        <form method="POST">
            <?php if (isset($_GET['edit'])) : ?>
            <?php
                $subject = null;
                $subject_id_edit = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
                if ($subject_id_edit) {
                    $query = "SELECT * FROM subjects WHERE subject_id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("i", $subject_id_edit);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $subject = $result->fetch_assoc();
                    if (!$subject) {
                        showToast('Subject not found for editing.', 'danger');
                        header("Location: subjects.php");
                        exit();
                    }
                } else {
                    showToast('Invalid subject ID for editing.', 'danger');
                    header("Location: subjects.php");
                    exit();
                }
                ?>
            <input type="hidden" name="subject_id" value="<?php echo htmlspecialchars($subject['subject_id']); ?>">
            <?php endif; ?>

            <div class="mb-3">
                <label for="subject_name" class="form-label">Subject Name</label>
                <div class="col-12 col-md-8 col-lg-6">
                    <input type="text" class="form-control" id="subject_name" name="subject_name"
                        value="<?php echo isset($subject) ? htmlspecialchars($subject['subject_name']) : ''; ?>"
                        required <?php echo $is_demo_account ? 'disabled' : ''; ?>>
                </div>
            </div>

            <div class="d-flex align-items-center mb-4">
                <button type="submit" class="btn btn-primary"
                    name="<?php echo isset($_GET['edit']) ? 'update_subject' : 'add_subject'; ?>"
                    <?php echo $is_demo_account ? 'disabled' : ''; ?>>
                    <?php echo isset($_GET['edit']) ? 'Update Subject' : 'Add Subject'; ?>
                </button>

                <?php if (isset($_GET['edit'])) : ?>
                <a href="subjects.php" class="btn btn-sm btn-outline-dark ms-4">Cancel</a>
                <?php endif; ?>
            </div>
        </form>

        <h3 class="text-primary">Current Subjects</h3>
        <div class="table-responsive col-lg-8">
            <table class="table table-hover table-bordered mt-4">
                <thead class="table-primary">
                    <tr>
                        <th>Subject Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT * FROM subjects ORDER BY subject_name ASC";
                    $result = $conn->query($query);
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>
                            <td>" . htmlspecialchars($row['subject_name']) . "</td>
                            <td style='width: 200px; white-space: nowrap;'>";
                            if ($is_demo_account) {
                                echo "<button class='btn btn-sm btn-secondary me-2' disabled>Edit</button>";
                                echo "<button class='btn btn-sm btn-danger' disabled>Delete</button>";
                            } else {
                                echo "<a href='?edit=" . htmlspecialchars($row['subject_id']) . "' class='btn btn-sm btn-secondary me-2'>Edit</a>";
                                echo "<button class='btn btn-sm btn-danger' onclick='" . $deleteConfirmFunction . "(" . htmlspecialchars($row['subject_id']) . ", \"" . htmlspecialchars($row['subject_name'], ENT_QUOTES) . "\")'>Delete</button>";
                            }
                            echo "</td>
                        </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='2' class='text-center'>No subjects found.</td></tr>";
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