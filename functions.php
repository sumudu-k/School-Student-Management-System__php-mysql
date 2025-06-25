<?php

function showToast($message, $status = 'success')
{
    if ($status === 'success') {
        $bgColor = 'bg-success';
        $icon = 'fas fa-check-circle';
    } else {
        $bgColor = 'bg-danger';
        $icon = 'fas fa-exclamation-circle';
    }

    $toastId = 'toast_' . rand(1000, 9999);

    echo '<div class="position-fixed bottom-0 start-0 p-3 " style="z-index: 11; ">
            <div id="' . $toastId . '" class="toast align-items-center text-white ' . $bgColor . ' border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                <i class="' . $icon . ' me-2"></i> ' . $message . '
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            </div>
        </div>
        <script>
            window.addEventListener("DOMContentLoaded", function() {
                var toast = new bootstrap.Toast(document.getElementById("' . $toastId . '"));
                toast.show();
            });
        </script>';
}


function createDeleteModal($itemType = 'item')
{
    $modalId = 'deleteModal_' . rand(1000, 9999);

    echo '<div class="modal fade" id="' . $modalId . '" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" ></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this ' . $itemType . '?</p>
                    <p class="fw-bold" id="' . $modalId . '_itemName"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" class="btn btn-danger" id="' . $modalId . '_deleteLink">Delete</a>
                </div>
            </div>
        </div>
    </div>
    <script>
        function confirmDelete' . $modalId . '(id, name, url = "") {
            document.getElementById("' . $modalId . '_itemName").textContent = name;
            const deleteUrl = url || "?delete=" + id;
            document.getElementById("' . $modalId . '_deleteLink").href = deleteUrl;
            
            const modal = new bootstrap.Modal(document.getElementById("' . $modalId . '"));
            modal.show();
        }
    </script>';

    return 'confirmDelete' . $modalId;
}