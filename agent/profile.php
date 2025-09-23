<?php
// --- Includes ---
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/db_connect.php";
require_once __DIR__ . "/../includes/auth_check.php";

// --- Role check ---
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'agent') {
    header("Location: " . BASE_URL . "auth/login.php?error=unauthorized");
    exit;
}

// --- Initialize variables ---
$errors = [];
$success = "";
$agentId = (int) $_SESSION['user_id'];

// Fetch current user data to display in the form
$stmt = $mysqli->prepare("SELECT User_Name, Email, Password FROM Users WHERE User_ID = ?");
$stmt->bind_param("i", $agentId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// --- Handle Form Submissions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- PROFILE INFO UPDATE ---
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $name = trim($_POST['user_name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (empty($name) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please provide a valid name and email address.";
        } else {
            // Check if email is already used by ANOTHER user
            $stmt_check = $mysqli->prepare("SELECT User_ID FROM Users WHERE Email = ? AND User_ID != ?");
            $stmt_check->bind_param("si", $email, $agentId);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) {
                $errors[] = "This email address is already in use by another account.";
            } else {
                $stmt_update = $mysqli->prepare("UPDATE Users SET User_Name = ?, Email = ? WHERE User_ID = ?");
                $stmt_update->bind_param("ssi", $name, $email, $agentId);
                if ($stmt_update->execute()) {
                    // Update the session variable so the name change is reflected immediately
                    $_SESSION['user_name'] = $name;
                    $success = "Profile updated successfully!";
                    // Re-fetch user data to display the updated info on the page
                    $user['User_Name'] = $name;
                    $user['Email'] = $email;
                } else {
                    $errors[] = "Failed to update profile.";
                }
                $stmt_update->close();
            }
            $stmt_check->close();
        }
    }

    // --- PASSWORD CHANGE ---
    if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (!password_verify($current_password, $user['Password'])) {
            $errors[] = "Your current password is incorrect.";
        } elseif (empty($new_password) || strlen($new_password) < 8) {
            $errors[] = "Your new password must be at least 8 characters long.";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "The new passwords do not match.";
        } else {
            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt_pass = $mysqli->prepare("UPDATE Users SET Password = ? WHERE User_ID = ?");
            $stmt_pass->bind_param("si", $hashedPassword, $agentId);
            if ($stmt_pass->execute()) {
                $success = "Password changed successfully!";
            } else {
                $errors[] = "Failed to change password.";
            }
            $stmt_pass->close();
        }
    }
}

// --- Page Setup ---
$pageTitle = "My Profile";
include __DIR__ . "/../includes/header.php";
?>

<!-- Main Content Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">My Profile</h1>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success" role="alert"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger" role="alert">
        <ul class="mb-0">
            <?php foreach ($errors as $error) echo "<li>" . htmlspecialchars($error) . "</li>"; ?>
        </ul>
    </div>
<?php endif; ?>


<div class="row g-4">
    <!-- Profile Details Column -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body p-4">
                <h5 class="card-title fw-bold mb-4">Profile Information</h5>
                <form method="post">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="mb-3">
                        <label for="userName" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="userName" name="user_name" value="<?= htmlspecialchars($user['User_Name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['Email']) ?>" required>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Change Password Column -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body p-4">
                <h5 class="card-title fw-bold mb-4">Change Password</h5>
                <form method="post">
                    <input type="hidden" name="action" value="change_password">
                    <div class="mb-3">
                        <label for="currentPassword" class="form-label">Current Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="currentPassword" name="current_password" required>
                            <span class="input-group-text" id="toggleCurrentPassword" style="cursor: pointer;">
                                <i class="bi bi-eye-slash-fill"></i>
                            </span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="newPassword" class="form-label">New Password</label>
                         <div class="input-group">
                            <input type="password" class="form-control" id="newPassword" name="new_password" required>
                            <span class="input-group-text" id="toggleNewPassword" style="cursor: pointer;">
                                <i class="bi bi-eye-slash-fill"></i>
                            </span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="confirmPassword" class="form-label">Confirm New Password</label>
                         <div class="input-group">
                            <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required>
                            <span class="input-group-text" id="toggleConfirmPassword" style="cursor: pointer;">
                                <i class="bi bi-eye-slash-fill"></i>
                            </span>
                        </div>
                    </div>
                     <div class="text-end">
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function togglePasswordVisibility(inputId, toggleId) {
        const passwordInput = document.getElementById(inputId);
        const toggleIcon = document.getElementById(toggleId).querySelector('i');

        if (passwordInput && toggleIcon) {
            document.getElementById(toggleId).addEventListener('click', function() {
                // Toggle the type attribute
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // Toggle the icon
                toggleIcon.classList.toggle('bi-eye-slash-fill');
                toggleIcon.classList.toggle('bi-eye-fill');
            });
        }
    }

    togglePasswordVisibility('currentPassword', 'toggleCurrentPassword');
    togglePasswordVisibility('newPassword', 'toggleNewPassword');
    togglePasswordVisibility('confirmPassword', 'toggleConfirmPassword');
});
</script>

<?php
include __DIR__ . "/../includes/footer.php";
?>

