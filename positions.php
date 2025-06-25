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

// Assign Position
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_position'])) {
    $index_number = $_POST['index_number'];
    $position_name = $_POST['position_name'];

    // Check if the student already has a position
    $query = "SELECT * FROM positions WHERE index_number = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $index_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        showToast('This student already has a position. Please remove the existing position first.', 'warning');
    } else {
        // Insert new position
        $query = "INSERT INTO positions (index_number, position_name) VALUES (?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $index_number, $position_name);
        $stmt->execute();
        showToast('Position assigned successfully', 'success');
    }
}
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
        <h2 class="text-primary mb-4">Assign Student Positions</h2>

        <form method="POST" class="mb-4">
            <div class="mb-3">
                <label for="index_number" class="form-label">Student</label>
                <div class="col-12 col-sm-10 col-md-8 col-lg-6"><select name="index_number" id="index_number"
                        class="form-select" required>
                        <option value="" disabled selected>Select student</option>
                        <?php
                        $query = "SELECT index_number, first_name,full_name, last_name FROM users";
                        $result = $conn->query($query);

                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . $row['index_number'] . "'>" . $row['full_name']  . "</option>";
                        }
                        ?>
                    </select></div>
            </div>

            <div class="mb-3">
                <label for="position_name" class="form-label">Position</label>
                <div class="col-sm-8 col-md-4 mb-4"><select name="position_name" id="position_name" class="form-select"
                        required>
                        <option value="" disabled selected>Select position</option>
                        <option value="Monitor">Class Monitor</option>
                        <option value="Prefect">Prefect</option>
                        <option value="Head Prefect">Head Prefect</option>
                    </select></div>
            </div>

            <button type="submit" name="assign_position" class="btn btn-primary mb-4">Assign Position</button>
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
                    $query = "SELECT p.position_id, p.index_number, u.full_name,p.position_name 
                  FROM positions p
                  JOIN users u ON p.index_number = u.index_number";
                    $result = $conn->query($query);

                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                    <td>" . $row['index_number'] . "</td>
                    <td>" . $row['full_name'] . "</td>
                    <td>" . $row['position_name'] . "</td>
                    <td>
                    <button class='btn btn-sm btn-danger' onclick='" . $deleteConfirmFunction . "(" . $row['position_id'] . ", \"" . htmlspecialchars($row['position_name'], ENT_QUOTES) . "\")'>Delete</button>
                    </td>
                  </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <?php
        // Remove Position
        if (isset($_GET['delete'])) {
            $position_id = $_GET['delete'];
            $query = "DELETE FROM positions WHERE position_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $position_id);
            $stmt->execute();
            showToast('Position removed successfully', 'success');
            header("Location: positions.php");
        }
        ?>

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