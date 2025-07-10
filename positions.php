<?php
session_start();
ob_start();

include 'db.php';
include 'functions.php';

$deleteConfirmFunction = createDeleteModal('position');

if (!isset($_SESSION['teacherID']) || $_SESSION['position'] !== 'classroom_teacher') {
    header("Location: login.php");
    exit;
}

$teacherID = $_SESSION['teacherID'];

$query = "SELECT role FROM teachers WHERE teacherID = ?";
$stmt = $conn->prepare($query);
if (!$stmt) {
    showToast('Database error: Could not prepare teacher role query.', 'danger');
    header("Location: dashboard.php");
    exit();
}
$stmt->bind_param("s", $teacherID);
$stmt->execute();
$result = $stmt->get_result();
$teacher = $result->fetch_assoc();
$demo = ($teacher['role'] === 'demo');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_position'])) {
    if ($demo === true) {
        showToast('Action not allowed: You are using a demo account and cannot assign positions.', 'danger');
        header("Location: positions.php");
        exit();
    }

    $index_number = filter_input(INPUT_POST, 'index_number', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
    $position_name = filter_input(INPUT_POST, 'position_name', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);

    if (empty($index_number) || empty($position_name)) {
        showToast('Both student and position must be selected.', 'danger');
        header("Location: positions.php");
        exit();
    }

    $allowed_positions = ['Monitor', 'Prefect', 'Head Prefect'];
    if (!in_array($position_name, $allowed_positions)) {
        showToast('Invalid position name provided.', 'danger');
        header("Location: positions.php");
        exit();
    }

    $check_student_query = "SELECT COUNT(*) FROM users WHERE index_number = ? AND is_approved = TRUE";
    $check_student_stmt = $conn->prepare($check_student_query);
    if (!$check_student_stmt) {
        showToast('Database error: Could not prepare student check query.', 'danger');
        header("Location: positions.php");
        exit();
    }
    $check_student_stmt->bind_param("s", $index_number);
    $check_student_stmt->execute();
    $student_exists = $check_student_stmt->get_result()->fetch_row()[0];

    if ($student_exists == 0) {
        showToast('Selected student does not exist or is not approved.', 'danger');
        header("Location: positions.php");
        exit();
    }

    $query = "SELECT * FROM positions WHERE index_number = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        showToast('Database error: Could not prepare position check query.', 'danger');
        header("Location: positions.php");
        exit();
    }
    $stmt->bind_param("s", $index_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        showToast('This student already has a position. Please remove the existing position first.', 'warning');
    } else {
        $query = "INSERT INTO positions (index_number, position_name) VALUES (?, ?)";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("ss", $index_number, $position_name);
            if ($stmt->execute()) {
                showToast('Position assigned successfully', 'success');
            } else {
                showToast('Failed to assign position: ' . $stmt->error, 'danger');
            }
        } else {
            showToast('Database error preparing statement for assigning position.', 'danger');
        }
    }
    header("Location: positions.php");
    exit();
}

if (isset($_GET['delete'])) {
    if ($demo === true) {
        showToast('Action not allowed: You are using a demo account and cannot remove positions.', 'danger');
        header("Location: positions.php");
        exit();
    }

    $position_id = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);

    if (!$position_id) {
        showToast('Invalid position ID for deletion.', 'danger');
        header("Location: positions.php");
        exit();
    }

    $check_position_query = "SELECT COUNT(*) FROM positions WHERE position_id = ?";
    $check_position_stmt = $conn->prepare($check_position_query);
    if (!$check_position_stmt) {
        showToast('Database error: Could not prepare position existence check.', 'danger');
        header("Location: positions.php");
        exit();
    }
    $check_position_stmt->bind_param("i", $position_id);
    $check_position_stmt->execute();
    $position_exists = $check_position_stmt->get_result()->fetch_row()[0];

    if ($position_exists == 0) {
        showToast('Position does not exist.', 'danger');
        header("Location: positions.php");
        exit();
    }

    $query = "DELETE FROM positions WHERE position_id = ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $position_id);
        if ($stmt->execute()) {
            showToast('Position removed successfully', 'success');
        } else {
            showToast('Failed to remove position: ' . $stmt->error, 'danger');
        }
    } else {
        showToast('Database error preparing statement for removing position.', 'danger');
    }
    header("Location: positions.php");
    exit();
}

ob_end_flush();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Assign Student Positions</title>
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
        <?php if ($demo === true): ?>
        <div class="alert alert-danger mt-3 text-center">
            You are using a **Demo account** on a live hosted website. You **cannot assign or remove student
            positions**. Please set up your own local environment to access full features.
        </div>
        <?php endif; ?>

        <h2 class="text-primary mb-4">Assign Student Positions</h2>

        <form method="POST" class="mb-4">
            <div class="mb-3">
                <label for="index_number" class="form-label">Student</label>
                <div class="col-12 col-sm-10 col-md-8 col-lg-6">
                    <select name="index_number" id="index_number" class="form-select" required
                        <?php echo $demo === true ? 'disabled' : ''; ?>>
                        <option value="" disabled selected>Select student</option>
                        <?php
                        $query = "SELECT index_number, full_name FROM users WHERE is_approved = TRUE ORDER BY full_name ASC";
                        $result = $conn->query($query);
                        if ($result) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<option value='" . htmlspecialchars($row['index_number']) . "'>" . htmlspecialchars($row['full_name']) . " (" . htmlspecialchars($row['index_number']) . ")</option>";
                            }
                        } else {
                            error_log("Failed to fetch students: " . $conn->error);
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label for="position_name" class="form-label">Position</label>
                <div class="col-sm-8 col-md-4 mb-4">
                    <select name="position_name" id="position_name" class="form-select" required
                        <?php echo $demo === true ? 'disabled' : ''; ?>>
                        <option value="" disabled selected>Select position</option>
                        <option value="Monitor">Class Monitor</option>
                        <option value="Prefect">Prefect</option>
                        <option value="Head Prefect">Head Prefect</option>
                    </select>
                </div>
            </div>

            <?php if ($demo === true): ?>
            <button type="reset" class="btn btn-primary mb-4" disabled>Assign Position</button>
            <?php else: ?>
            <button type="submit" name="assign_position" class="btn btn-primary mb-4">Assign Position</button>
            <?php endif; ?>
        </form>

        <h3 class="text-primary mb-4">Current Student Positions</h3>
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-hover mb-5">
                <thead>
                    <tr class="table-primary">
                        <th>Index Number</th>
                        <th>Name</th>
                        <th>Position</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT p.position_id, p.index_number, u.full_name, p.position_name 
                              FROM positions p
                              JOIN users u ON p.index_number = u.index_number
                              ORDER BY u.full_name ASC";
                    $result = $conn->query($query);

                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>
                                <td>" . htmlspecialchars($row['index_number']) . "</td>
                                <td>" . htmlspecialchars($row['full_name']) . "</td>
                                <td>" . htmlspecialchars($row['position_name']) . "</td>
                                <td>";
                            if ($demo === true) {
                                echo "<button class='btn btn-sm btn-danger' disabled>Delete</button>";
                            } else {
                                echo "<button class='btn btn-sm btn-danger' onclick=\"" . $deleteConfirmFunction . "('" . htmlspecialchars($row['position_id']) . "', '" . htmlspecialchars($row['full_name'] . ' - ' . $row['position_name'], ENT_QUOTES) . "')\">Delete</button>";
                            }
                            echo "</td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' class='text-center'>No student positions currently assigned.</td></tr>";
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