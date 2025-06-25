<?php
session_start();
include 'db.php';
include 'functions.php';
$deleteConfirmFunction = createDeleteModal('profile change request');

if (!isset($_SESSION['teacherID'])) {
    header("Location: login.php");
    exit;
}

$is_super_admin = ($_SESSION['position'] === 'classroom_teacher');
if (!$is_super_admin) {
    showToast('You do not have permission to access this page.', 'error');
    header("Location: login.php");
    exit;
}

// Approve Update
if (isset($_GET['approve'])) {
    $update_id = $_GET['approve'];

    // Fetch the update details before deleting it
    $query = "SELECT * FROM profile_updates WHERE update_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $update_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $update_data = $result->fetch_assoc();

    if (!$result->num_rows == 0) {

        showToast('No update found with the provided ID.', 'error');
    }

    if ($update_data) {
        // Apply the changes to the users table
        $query = "UPDATE users 
                  SET first_name = ?, last_name = ?, full_name = ?, email = ?, class = ?, birthday = ?, address = ?, 
                      mother_name = ?, mother_contact = ?, father_name = ?, father_contact = ?
                  WHERE index_number = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param(
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
        $stmt->execute();

        // Delete the update from the profile_updates table
        $query = "DELETE FROM profile_updates WHERE update_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $update_id);
        $stmt->execute();

        showToast('Profile update approved successfully.', 'success');
    }
}

// Reject Update
if (isset($_GET['delete'])) {
    $update_id = $_GET['delete'];

    $query = "DELETE FROM profile_updates WHERE update_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $update_id);
    $stmt->execute();
    showToast('Profile update rejected.', 'danger');
}
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
                      pu.status,
                      u.first_name AS u_first_name, 
                      u.last_name AS u_last_name, 
                      u.full_name AS u_full_name,
                      u.email AS u_email, 
                      u.class AS u_class, 
                      u.birthday AS u_birthday, 
                      u.address AS u_address, 
                      u.mother_name AS u_mother_name, 
                      u.mother_contact AS u_mother_contact, 
                      u.father_name AS u_father_name, 
                      u.father_contact AS u_father_contact
                  FROM profile_updates pu
                  LEFT JOIN users u ON pu.index_number = u.index_number";
                    $result = $conn->query($query);


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
                    <div class='d-flex'>
                    <a class='btn btn-secondary btn-sm me-2' href='?approve=" . $row['update_id'] . "'>Approve</a>

                   <button class='btn btn-sm btn-danger' onclick=\"" . $deleteConfirmFunction . "('" . $row['update_id'] . "', '" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name'], ENT_QUOTES) . "')\">Reject</button>
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
    <?php
    include 'user/footer.php';
    ?>
</body>

</html>