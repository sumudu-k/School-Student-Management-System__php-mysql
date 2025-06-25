<?php
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Damso</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="styles/css/custom.css">
    <link rel="stylesheet" href="styles/css/bootstrap.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oleo+Script:wght@400;700&display=swap" rel="stylesheet">
</head>

<body>
    <style>
    .navbar-brand {
        font-family: 'Oleo Script', cursive;
    }
    </style>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm mb-4">
        <div class="container-fluid ms-5 me-5">
            <a class="navbar-brand fs-4 text-light" href="dashboard.php">Admin Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <i class="fa-solid fa-bars fa-sm"></i>
            </button>

            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item ">
                        <span class="fw-500 text-secondary me-3 nav-link">
                            <div class="d-flex align-items-center text-white fs-6">
                                <i class="fa-solid fa-user text-white me-2"></i>Welcome Teacher

                            </div>
                        </span>
                    </li>

                    <li class="nav-item ">
                        <a href="#" class="btn btn-outline-danger nav-link btn-sm" data-bs-toggle="modal"
                            data-bs-target="#logoutModal">
                            <i class="fas fa-sign-out-alt me-1"></i> Logout
                        </a>
                        <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel"
                            aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header bg-danger text-white">
                                        <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                            aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        Are you sure you want to logout?
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                            data-bs-dismiss="modal">Cancel</button>
                                        <a href="logout.php" class="btn btn-danger">Logout</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <script src="styles/js/bootstrap.bundle.min.js"></script>
</body>


</html>