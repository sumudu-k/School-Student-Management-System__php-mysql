<?php
session_start();
include 'db.php';
include 'functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $teacherID = $_POST['teacherID'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $teaching_subject = $_POST['teaching_subject'];

    if ($password !== $confirm_password) {
        showToast('Passwords do not match', 'error');
    } else {
        // Check if teacherID or email already exists
        $query = "SELECT * FROM teachers WHERE teacherID = ? OR email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $teacherID, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            showToast('Teacher ID or Email already exists', 'error');
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert the admin registration request into the database
            $query = "INSERT INTO teachers (teacherID, first_name, last_name, full_name, email, password, position, teaching_subject, is_approved)
                      VALUES (?, ?, ?, ?, ?, ?, 'teacher', ?, FALSE)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssssssi", $teacherID, $first_name, $last_name, $full_name, $email, $hashed_password, $teaching_subject);
            if ($stmt->execute()) {
                showToast('Registration request submitted successfully. Please wait for approval.', 'success');
            } else {
                showToast('Error in registration. Please try again.', 'error');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Admin Registration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="styles/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles/css/custom.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oleo+Script:wght@400;700&display=swap" rel="stylesheet">
</head>

<body>
    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h2 class="text-center mb-0">Admin Registration</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="teacherID" class="form-label">Teacher ID</label>
                                <input type="text" class="form-control" id="teacherID" name="teacherID" required>
                            </div>
                            <div class="mb-3">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password"
                                    name="confirm_password" required>
                            </div>
                            <div class="mb-3">
                                <label for="position" class="form-label">Position</label>
                                <select class="form-select" id="position" name="position" required>
                                    <option value="teacher">Teacher</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="teaching_subject" class="form-label">Teaching Subject</label>
                                <select class="form-select" id="teaching_subject" name="teaching_subject" required>
                                    <option value="">Select Subject</option>
                                    <?php
                                    $query = "SELECT * FROM subjects";
                                    $result = $conn->query($query);
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<option value='" . $row['subject_id'] . "'>" . $row['subject_name'] . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Register</button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-center">
                        <p class="mb-0">Already have an account? <a href="login.php">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include 'user/footer.php'; ?>
    <script src="styles/js/bootstrap.bundle.min.js"></script>
</body>

</html>