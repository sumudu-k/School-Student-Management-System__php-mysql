<?php
session_start();
include '../db.php';
include '../functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $index_number = $_POST['index_number'];
    $full_name = $_POST['full_name'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $grade = $_POST['grade'];
    $class = $_POST['class'];
    $birthday = $_POST['birthday'];
    $address = $_POST['address'];
    $subjects = isset($_POST['subjects']) ? $_POST['subjects'] : [];

    // Validate input
    if ($password !== $confirm_password) {
        showToast('Passwords do not match', 'error');
    } elseif (empty($subjects) || count($subjects) !== 4) {
        showToast('Please select only 4 subjects', 'error');
    } else {
        // Check if index number or email already exists
        $query = "SELECT * FROM users WHERE index_number = ? OR email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $index_number, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            showToast('Index number or Email already exists', 'error');
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert the student into the database
            $query = "INSERT INTO users (index_number, full_name, first_name, last_name, email, password, grade, class, birthday, address, is_approved)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, FALSE)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssssssisss", $index_number, $full_name, $first_name, $last_name, $email, $hashed_password, $grade, $class, $birthday, $address);
            if ($stmt->execute()) {
                // Link selected subjects
                foreach ($subjects as $subject_id) {
                    $query = "INSERT INTO user_subjects (index_number, subject_id) VALUES (?, ?)";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("si", $index_number, $subject_id);
                    $stmt->execute();
                }
                showToast('Registration request submitted successfully. Awaiting Super Admin approval.', 'success');
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
    <title>Student Registration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

    <link rel="stylesheet" href="../styles/css/bootstrap.min.css">
    <link rel="stylesheet" href="../styles/css/custom.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oleo+Script:wght@400;700&display=swap" rel="stylesheet">
    <script>
    function validateSubjects() {
        const checkboxes = document.querySelectorAll('input[name="subjects[]"]:checked');
        const requiredSubjects = 4;

        if (checkboxes.length > requiredSubjects) {
            showToast('You can only select exactly 4 subjects.', 'error');
            checkboxes[checkboxes.length - 1].checked = false;
        }

    }
    </script>
</head>

<body class="d-flex flex-column min-vh-100">
    <?php
    if ($_ENV['ALLOW_REGISTER'] === 'false') : ?>
    <div class="alert alert-danger text-center mt-3 mx-1 mx-md-3" role="alert"> You cannot create new accounts in Live
        hosted
        website. Please setup your own local environment to access full features. Visit
        [https://github.com/sumudu-k/School-Student-Management-System__php-mysql.git] for more details.
    </div>
    <?php endif;
    ?>
    <main class="container-lg" style="flex: 1;">
        <div class="row justify-content-center mt-4 mb-5">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Student Registration</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row mb-3">
                                <div class="col-12">
                                    <label for="index_number" class="form-label">Index Number:</label>
                                    <input type="text" class="form-control" id="index_number" name="index_number"
                                        required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-12">
                                    <label for="full_name" class="form-label">Full Name:</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-12  mb-3 mb-md-0">
                                    <label for="first_name" class="form-label">First Name:</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required>
                                </div>
                                <div class="col-12 ">
                                    <label for="last_name" class="form-label">Last Name:</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-12">
                                    <label for="email" class="form-label">Email:</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-12  mb-3 mb-md-0">
                                    <label for="password" class="form-label">Password:</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <div class="col-12">
                                    <label for="confirm_password" class="form-label">Confirm Password:</label>
                                    <input type="password" class="form-control" id="confirm_password"
                                        name="confirm_password" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-6">
                                    <label for="grade" class="form-label">Grade:</label>
                                    <select class="form-select" name="grade" id="grade">
                                        <option value="12">12</option>
                                        <option value="13">13</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label for="class" class="form-label">Class:</label>
                                    <select class="form-select" name="class" id="class">
                                        <option value="a">A</option>
                                        <option value="b">B</option>
                                        <option value="c">C</option>
                                        <option value="d">D</option>
                                        <option value="e">E</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-12">
                                    <label for="birthday" class="form-label">Birthday:</label>
                                    <input type="date" class="form-control" id="birthday" name="birthday" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-12">
                                    <label for="address" class="form-label">Address:</label>
                                    <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-12">
                                    <label class="form-label">Subjects (Exactly 4):</label>
                                    <div class="row">
                                        <?php
                                        $query = "SELECT * FROM subjects";
                                        $result = $conn->query($query);
                                        while ($row = $result->fetch_assoc()) {
                                            echo '<div class="col-6  mb-2">';
                                            echo '<div class="form-check">';
                                            echo '<input class="form-check-input" type="checkbox" name="subjects[]" id="subject-' . $row['subject_id'] . '" value="' . $row['subject_id'] . '" onchange="validateSubjects()">';
                                            echo '<label class="form-check-label" for="subject-' . $row['subject_id'] . '">' . $row['subject_name'] . '</label>';
                                            echo '</div>';
                                            echo '</div>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>

                            <?php
                            if ($_ENV['ALLOW_REGISTER'] === 'false') : ?>
                            <div class="d-grid gap-2">
                                <button type="reset" disabled class="btn btn-primary">
                                    <i class="fas fa-user-plus "></i> Register
                                </button>
                            </div>
                            <?php else : ?>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-user-plus"></i> Register
                                </button>
                            </div>
                            <?php endif; ?>
                        </form>
                    </div>
                    <div class="card-footer  text-center">
                        <p class="mb-0">Already have an account? <a href="login.php">Login here</a></p>
                    </div>
                    </form>
                </div>
            </div>
        </div>
        </div>
    </main>
    <?php
    include 'footer.php';
    ?>
    <script src="../styles/js/bootstrap.bundle.min.js"></script>
</body>

</html>