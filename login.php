<?php
session_start();
include 'db.php';
include 'functions.php';
$deleteConfirmFunction = createDeleteModal('teacher');

if (isset($_SESSION['teacherID'])) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = "SELECT * FROM teachers WHERE email = ? and is_approved = 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $teacher = $result->fetch_assoc();
        if (password_verify($password, $teacher['password'])) {
            $_SESSION['teacherID'] = $teacher['teacherID'];
            $_SESSION['position'] = $teacher['position'];
            header("Location: dashboard.php");
            exit;
        } else {
            showToast('Invalid email or password.', 'error');
        }
    } else {
        showToast('Invalid email or password.', 'error');
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Admin Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="styles/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles/css/custom.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oleo+Script:wght@400;700&display=swap" rel="stylesheet">
</head>

<body class="d-flex flex-column min-vh-100">
    <main class="container-lg" style="flex: 1;">
        <div class="mt-3 container alert alert-warning  ">
            <h5>Demo Admin Account Credentials </h5>
            <p>Email: nethmi.p07@example.com</p>
            <p>Password: teacher123</p>
        </div>
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white">
                            <h3 class="mb-0">Admin Login</h3>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email address</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="password" name="password"
                                            required>
                                    </div>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">Login <i
                                            class="fas fa-sign-in-alt ms-1"></i></button>
                                </div>
                            </form>
                            <p class="mt-3 text-center">Don't have an account? <a href="register_admin.php">Register
                                    here</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'user/footer.php'; ?>
    <script src="styles/js/bootstrap.bundle.min.js"></script>
</body>

</html>