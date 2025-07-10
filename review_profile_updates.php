<?php
session_start();
ob_start();
include 'db.php';
include 'functions.php';

$deleteConfirmFunction = createDeleteModal('profile change request');

if (!isset($_SESSION['teacherID'])) {
    header("Location: login.php");
    exit;
}

$teacherID = $_SESSION['teacherID'];

$query = "SELECT position, role FROM teachers WHERE teacherID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $teacherID);
$stmt->execute();
$result = $stmt->get_result();
$teacher_data = $result->fetch_assoc();

$is_super_admin = ($teacher_data['position'] === 'classroom_teacher');
$is_demo_account = ($teacher_data['role'] === 'demo');

if (!$is_super_admin) {
    showToast('You do not have permission to access this page.', 'error');
    header("Location: login.php");
    exit;
}

if (isset($_GET['approve'])) {
    if ($is_demo_account) {
        showToast('Action not allowed: You are using a demo account and cannot approve profile updates.', 'danger');
        header("Location: admin_profile_updates.php");
        exit();
    }

    $update_id = filter_input(INPUT_GET, 'approve', FILTER_VALIDATE_INT);

    if (!$update_id) {
        showToast('Invalid update ID provided for approval.', 'danger');
        header("Location: admin_profile_updates.php");
        exit();
    }

    $query = "SELECT * FROM profile_updates WHERE update_id = ? AND status = 'pending'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $update_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $update_data = $result->fetch_assoc();

    if (!$update_data) {
        showToast('No pending update found with the provided ID.', 'warning');
        header("Location: admin_profile_updates.php");
        exit();
    }

    $conn->begin_transaction();
    try {
        $update_user_query = "UPDATE users 
                              SET first_name = ?, last_name = ?, full_name = ?, email = ?, class = ?, birthday = ?, address = ?, 
                                  mother_name = ?, mother_contact = ?, father_name = ?, father_contact = ?
                              WHERE index_number = ?";
        $update_user_stmt = $conn->prepare($update_user_query);
        $update_user_stmt->bind_param(
            "ssssssssssss",
            $update_data['first_name'],
            $update_data['last_name'],
            $update_data['full_name'],
            $update_data['email'],
            $update_data['class'],
            $update_data['birthday'],
            $update_data['address'],
            $update_data['mother_name'],
            $update_data['mother_contact'],
            $update_data['father_name'],
            $update_data['father_contact'],
            $update_data['index_number']
        );
        $update_user_stmt->execute();

        $delete_update_query = "DELETE FROM profile_updates WHERE update_id = ?";
        $delete_update_stmt = $conn->prepare($delete_update_query);
        $delete_update_stmt->bind_param("i", $update_id);
        $delete_update_stmt->execute();

        $conn->commit();
        showToast('Profile update approved successfully.', 'success');
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        showToast('Failed to approve profile update: ' . $e->getMessage(), 'danger');
    }

    header("Location: admin_profile_updates.php");
    exit();
}

if (isset($_GET['delete'])) {
    if ($is_demo_account) {
        showToast('Action not allowed: You are using a demo account and cannot reject profile updates.', 'danger');
        header("Location: admin_profile_updates.php");
        exit();
    }

    $update_id = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);

    if (!$update_id) {
        showToast('Invalid update ID provided for rejection.', 'danger');
        header("Location: admin_profile_updates.php");
        exit();
    }

    $check_query = "SELECT COUNT(*) FROM profile_updates WHERE update_id = ? AND status = 'pending'";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $update_id);
    $check_stmt->execute();
    $exists = $check_stmt->get_result()->fetch_row()[0];

    if ($exists == 0) {
        showToast('No pending update found with the provided ID to reject.', 'warning');
        header("Location: admin_profile_updates.php");
        exit();
    }

    $query = "DELETE FROM profile_updates WHERE update_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $update_id);
    if ($stmt->execute()) {
        showToast('Profile update rejected.', 'danger');
    } else {
        showToast('Failed to reject profile update.', 'danger');
    }
    header("Location: admin_profile_updates.php");
    exit();
}
ob_end_flush();
?>
<!DOCTYPE html>
<html>

<head>
    <title>Review Profile Updates</title>
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
                You are using a **Demo account** on a live hosted website. You **cannot approve or reject profile update
                requests**.
            </div>
        <?php endif; ?>

        <h2 class="text-primary mb-4">Review Profile Updates</h2>
        <div class="table-responsive mb-5">
            <table class="table table-hover table-sm table-bordered">
                <thead class="table-primary">
                    <tr>
                        <th>Index Number</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Class</th>
                        <th>Birthday</th>
                        <th>Address</th>
                        <th>Mother's Name</th>
                        <th>Mother's Contact</th>
                        <th>Father's Name</th>
                        <th>Father's Contact</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT 
                                pu.update_id, 
                                pu.index_number, 
                                pu.first_name, 
                                pu.last_name, 
                                pu.full_name,
                                pu.email, 
                                pu.class, 
                                pu.birthday, 
                                pu.address, 
                                pu.mother_name, 
                                pu.mother_contact, 
                                pu.father_name, 
                                pu.father_contact, 
                                pu.status
                              FROM profile_updates pu
                              WHERE pu.status = 'pending'
                              ORDER BY pu.update_id DESC";
                    $result = $conn->query($query);

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>
                                <td>" . htmlspecialchars($row['index_number']) . "</td>
                                <td>" . htmlspecialchars($row['first_name']) . "</td>
                                <td>" . htmlspecialchars($row['last_name']) . "</td>
                                <td>" . htmlspecialchars($row['full_name']) . "</td>
                                <td>" . htmlspecialchars($row['email']) . "</td>
                                <td>" . htmlspecialchars($row['class']) . "</td>
                                <td>" . htmlspecialchars($row['birthday']) . "</td>
                                <td>" . htmlspecialchars($row['address']) . "</td>
                                <td>" . htmlspecialchars($row['mother_name']) . "</td>
                                <td>" . htmlspecialchars($row['mother_contact']) . "</td>
                                <td>" . htmlspecialchars($row['father_name']) . "</td>
                                <td>" . htmlspecialchars($row['father_contact']) . "</td>
                                <td>" . htmlspecialchars($row['status']) . "</td>
                                <td>
                                <div class='d-flex'>";

                            if ($is_demo_account) {
                                echo "<button class='btn btn-secondary btn-sm me-2' disabled>Approve</button>";
                                echo "<button class='btn btn-sm btn-danger' disabled>Reject</button>";
                            } else {
                                echo "<a class='btn btn-secondary btn-sm me-2' href='?approve=" . htmlspecialchars($row['update_id']) . "'>Approve</a>";
                                echo "<button class='btn btn-sm btn-danger' onclick=\"" . $deleteConfirmFunction . "('" . htmlspecialchars($row['update_id']) . "', '" . htmlspecialchars($row['full_name'], ENT_QUOTES) . "')\">Reject</button>";
                            }
                            echo "</div>
                                </td>
                            </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='14' class='text-center'>No pending profile update requests.</td></tr>";
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