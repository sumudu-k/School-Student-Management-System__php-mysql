<?php
?>
<nav class="navbar bg-primary mb-4">
    <div class="navbar-brand text-light ms-3 ms-md-5  fs-3 p-0 m-0" style="font-family: 'Oleo Script', cursive;">
        <a href="./student_dashboard.php" class="nav-link">Damso</a>
    </div>
    <ul class="navbar-nav ms-auto me-4">
        <li class="navbar-item">
            <a href="profile.php" class="nav-link text-light link-secondary  " data-bs-toggle="tooltip"
                data-bs-title="profile" data-bs-placement="bottom"><i class="fa-solid fa-user fa-lg"></i></a>
        </li>
    </ul>
    <ul class="navbar-nav me-3 me-md-5">
        <li class="navbar-item">
            <a href="#" class="nav-link text-light" data-bs-toggle="modal" data-bs-target="#logoutModal">
                <button class="btn btn-sm btn-danger">Logout</button>
            </a>

            <div class="modal fade" id="logoutModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            Are you sure you want to logout?
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <a href="logout.php" class="btn btn-danger">Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </li>
    </ul>
</nav>