<?php
session_start();
include '../db.php';
include '../functions.php';

if (isset($_SESSION['index_number'])) {
    header("Location: student_dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            if ($user['is_approved']) {
                $_SESSION['index_number'] = $user['index_number'];
                header("Location: student_dashboard.php");
            } else {
                showToast('Your account is not approved yet. Please contact the administration.', 'warning');
            }
        } else {
            showToast('Invalid credentials. Please try again.', 'error');
        }
    } else {
        showToast('No user found with this email. Please register.', 'error');
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Student Login</title>
    <link rel="stylesheet" href="../styles/css/bootstrap.min.css">
    <link rel="stylesheet" href="../styles/css/custom.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oleo+Script:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body class="d-flex flex-column min-vh-100">
    <main class="container-lg " style="flex: 1;">
        <div class="mt-3 container alert alert-warning  ">
            <h5>Demo Account Credentials </h5>
            <p>Email: sahan.abeykoon69@example.com</p>
            <p>Password: student123</p>
        </div>

        <div class="row justify-content-center mb-4">
            <div class="col-md-6 col-sm-12">
                <div class="mt-5 card shadow mt-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Student Login</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3 input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" name="email" placeholder="Email Address"
                                    required>
                            </div>
                            <div class="mb-3 input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" name="password" placeholder="Password"
                                    required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-center ">
                        <p class="mb-0">Don't have an account? <a href="register.php">Register here</a></p>
                        <div class="alert alert-danger mt-3 text-center">
                            You cannot create new accounts in Live hosted website.
                            Please
                            setup your own local environment to access full features. Visit
                            [https://github.com/sumudu-k/School-Student-Management-System__php-mysql.git] for more
                            details."
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php include 'footer.php'; ?>
    <script src="../styles/js/bootstrap.bundle.min.js"></script>
</body>

</html>