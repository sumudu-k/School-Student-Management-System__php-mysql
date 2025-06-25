<?php
session_start();
include '../db.php';
include 'navbar.php';
include '../functions.php';

// Redirect to login page if not logged in
if (!isset($_SESSION['index_number'])) {
    header("Location: login.php");
    exit;
}

$index_number = $_SESSION['index_number'];

// Fetch user details
$query = "SELECT * FROM users WHERE index_number = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $index_number);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle Profile Update Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $full_name = $first_name . ' ' . $last_name;
    $email = $_POST['email'];
    $class_year = $_POST['class_year'];
    $class_section = $_POST['class_section'];
    $class = $class_year . $class_section;
    $birthday = $_POST['birthday'];
    $address = $_POST['address'];
    $mother_name = $_POST['mother_name'];
    $mother_contact = $_POST['mother_contact'];
    $father_name = $_POST['father_name'];
    $father_contact = $_POST['father_contact'];

    // Validate inputs
    if (!is_numeric($mother_contact) || !is_numeric($father_contact)) {
        echo "<script>alert('Contact numbers must contain only numbers.');</script>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Invalid email address.');</script>";
    } else {
        // Save the update request to the profile_updates table
        $query = "INSERT INTO profile_updates (index_number, first_name, last_name, full_name, email, class, birthday, address, mother_name, mother_contact, father_name, father_contact)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                  ON DUPLICATE KEY UPDATE
                  first_name = VALUES(first_name), last_name = VALUES(last_name), full_name = VALUES(full_name),
                  email = VALUES(email), class = VALUES(class), birthday = VALUES(birthday), address = VALUES(address),
                  mother_name = VALUES(mother_name), mother_contact = VALUES(mother_contact),
                  father_name = VALUES(father_name), father_contact = VALUES(father_contact), status = 'pending'";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssssssss", $index_number, $first_name, $last_name, $full_name, $email, $class, $birthday, $address, $mother_name, $mother_contact, $father_name, $father_contact);
        $stmt->execute();

        // Check if update was successful
        if ($stmt->affected_rows > 0) {
            showToast('Profile update request sent for review.', 'success');
        } else {
            showToast('Error updating profile. Please try again.', 'danger');
        }
    }
}

// check users data in user updates table
$query = "SELECT * FROM profile_updates WHERE index_number = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $index_number);
$stmt->execute();
$result = $stmt->get_result();
$user_update = $result->fetch_assoc();


?>
<!DOCTYPE html>
<html>

<head>
    <title> Profile</title>
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

    <div class="container">
        <h2 class="text-primary text-center mb-5">My Profile</h2>
        <p class="text-muted mb-4"><i class="fa-solid fa-circle-info"></i> Edit personal Information</p>
        <form method="POST" class="form">

            <div class="form-floating mb-3 col-lg-10 ">
                <input type="text" class="form-control" name="index_number"
                    value="<?php echo htmlspecialchars($user['index_number']); ?>" placeholder="" required disabled>
                <label for="" class="form-label">Index Number</label>
            </div>


            <div class="form-floating mb-3  col-lg-10">
                <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>"
                    class="form-control" required>
                <label for="" class="form-label">First Name</label>

            </div>

            <div class="form-floating mb-3 col-lg-10">
                <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>"
                    class="form-control" required>
                <label for="" class="form-label">Last Name</label>
            </div>

            <div class="form-floating mb-3 col-lg-10">
                <input type="text" name="full_name"
                    value="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>"
                    class="form-control" required>
                <label for="" class="form-label">Full Name</label>
            </div>

            <div class="form-floating mb-3 col-lg-10">
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>"
                    class="form-control" required>
                <label for="" class="form-label">Email</label>
            </div>


            <label for="" class="form-label text-muted">Class</label>

            <div class="form-floating d-flex col-md-6 mb-3">
                <select name="class_year" class="form-select me-4" required>
                    <?php
                    $class_years = ['12', '13'];
                    foreach ($class_years as $year) {
                        $selected = ($year == substr($user['class'], 0, 2)) ? 'selected' : '';
                        echo "<option value='$year' $selected>$year</option>";
                    }
                    ?>
                </select>
                <select name="class_section" class="form-select" required>
                    <?php
                    $class_sections = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'];
                    foreach ($class_sections as $section) {
                        $selected = ($section == substr($user['class'], -1)) ? 'selected' : '';
                        echo "<option value='$section' $selected>$section</option>";
                    }
                    ?>
                </select>
            </div>

            <label for="" class="form-label text-muted">Birthday</label>
            <div class="form-floating mb-3 col-md-6">
                <input type="date" name="birthday" class="form-control"
                    value="<?php echo htmlspecialchars($user['birthday']); ?>">
            </div>

            <div class=" form form-floating mb-3 col-lg-10">
                <input type="text" name="address" class="form-control"
                    value="<?php echo htmlspecialchars($user['address']); ?>">
                <label for="" class="form-label">Address</label>
            </div>

            <div class="form-floating mb-3 col-lg-10">
                <input type="text" name="mother_name" class="form-control"
                    value="<?php echo htmlspecialchars($user['mother_name'] ?? ''); ?>">
                <label for="" class="form-label">Mother's Name</label>
            </div>
            <div class="form-floating mb-3 col-lg-10">
                <input type="text" name="mother_contact" class="form-control"
                    value="<?php echo htmlspecialchars($user['mother_contact'] ?? ''); ?>">
                <label for="" class="form-label">Mother's Contact Number</label>
            </div>

            <div class="form-floating mb-3 col-lg-10">
                <input type="text" name="father_name" class="form-control"
                    value="<?php echo htmlspecialchars($user['father_name'] ?? ''); ?>">
                <label for="" class="form-label">Father's Name</label>
            </div>
            <div class="form-floating mb-3 col-lg-10">
                <input type="text" name="father_contact" class="form-control"
                    value="<?php echo htmlspecialchars($user['father_contact'] ?? ''); ?>">
                <label for="" class="form-label">Father's Contact Number</label>
            </div>

            <?php
            // Check if there is a pending update request
            if ($user_update && $user_update['status'] == 'pending') : ?>
            <button type="submit" name="update_profile" class="btn btn-primary mb-2" disabled>Update Profile</button>
            <p class="mb-5 text-danger">You have already requested profile update. Wait for admin process </p>
            <?php else : ?>
            <button type="submit" name="update_profile" class="btn btn-primary mb-5">Update Profile</button>
            <?php endif; ?>


        </form>

    </div>
    <script src="../styles/js/bootstrap.bundle.min.js"></script>
    <?php
    include 'footer.php' ?>
    <script src="../styles/js/bootstrap.bundle.min.js"></script>
</body>

</html>