<?php
// --- Includes ---
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/db_connect.php";
require_once __DIR__ . "/../includes/auth_check.php";

// --- Role check ---
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: " . BASE_URL . "auth/login.php?error=unauthorized");
    exit;
}

// --- THIS IS THE MISSING PHP LOGIC ---
$errors = [];
$success = "";

// Handle Form Submissions (Create/Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $name = trim($_POST['user_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = trim($_POST['role'] ?? 'agent');
    $pod_id = filter_input(INPUT_POST, 'pod_id', FILTER_VALIDATE_INT);
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    if (empty($name)) $errors[] = "User Name is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid Email is required.";
    if ($role === 'agent' && empty($pod_id)) $errors[] = "A Pod must be selected for agents.";
    if ($_POST['action'] === 'add_user' && empty($password)) { $errors[] = "Password is required for new users."; }
    if (!empty($password) && strlen($password) < 8) { $errors[] = "Password must be at least 8 characters long.";}
    if (empty($errors)) {
        $stmt_check_email = $mysqli->prepare("SELECT User_ID FROM Users WHERE Email = ? AND User_ID != ?");
        $check_id = $user_id ?? 0;
        $stmt_check_email->bind_param("si", $email, $check_id);
        $stmt_check_email->execute();
        $stmt_check_email->store_result();
        if ($stmt_check_email->num_rows > 0) { $errors[] = "This email address is already in use by another account."; }
        $stmt_check_email->close();
    }
    if (empty($errors)) {
        $pod_value = ($role === 'admin') ? NULL : $pod_id;
        if ($_POST['action'] === 'edit_user' && $user_id) {
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $mysqli->prepare("UPDATE Users SET User_Name=?, Email=?, Role=?, Password=?, Pod_ID=? WHERE User_ID=?");
                $stmt->bind_param("ssssii", $name, $email, $role, $hashed_password, $pod_value, $user_id);
            } else {
                $stmt = $mysqli->prepare("UPDATE Users SET User_Name=?, Email=?, Role=?, Pod_ID=? WHERE User_ID=?");
                $stmt->bind_param("sssii", $name, $email, $role, $pod_value, $user_id);
            }
            if ($stmt->execute()) { $success = "User updated successfully!"; } else { $errors[] = "Failed to update user."; }
            $stmt->close();
        } elseif ($_POST['action'] === 'add_user') {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare("INSERT INTO Users (User_Name, Email, Role, Password, Pod_ID, Created_At) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssssi", $name, $email, $role, $hashed_password, $pod_value);
            if ($stmt->execute()) { $success = "User added successfully!"; } else { $errors[] = "Failed to add user."; }
            $stmt->close();
        }
    }
}

// Handle Delete Request
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $user_id_to_delete = (int)$_GET['id'];
    $stmt = $mysqli->prepare("DELETE FROM Users WHERE User_ID = ?");
    $stmt->bind_param("i", $user_id_to_delete);
    $stmt->execute();
    $stmt->close();
    header("Location: " . BASE_URL . "admin/users.php?deleted=1");
    exit;
}
if(isset($_GET['deleted'])) $success = "User deleted successfully.";

// Fetch Data for Modals and Table
$teams = $mysqli->query("SELECT Team_ID, Team_Name FROM Teams ORDER BY Team_Name ASC")->fetch_all(MYSQLI_ASSOC);
$pods = $mysqli->query("SELECT Pod_ID, Pod_Name, Team_ID FROM Pods ORDER BY Pod_Name ASC")->fetch_all(MYSQLI_ASSOC);
$users_sql = "
    SELECT u.User_ID, u.User_Name, u.Email, u.Role, u.Pod_ID, p.Pod_Name, t.Team_Name
    FROM Users u
    LEFT JOIN Pods p ON u.Pod_ID = p.Pod_ID
    LEFT JOIN Teams t ON p.Team_ID = t.Team_ID
    ORDER BY u.User_Name ASC
";
$users = $mysqli->query($users_sql)->fetch_all(MYSQLI_ASSOC);
// --- END OF MISSING PHP LOGIC ---

$pageTitle = "Manage Users";
include __DIR__ . "/../includes/header.php";
?>

<!-- Add/Edit User Modal (No Changes) -->
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" id="userForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalLabel">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add_user">
                    <input type="hidden" name="user_id" id="userId" value="">
                    <div class="row g-3">
                        <div class="col-md-6"><label for="userName" class="form-label">Full Name</label><input type="text" class="form-control" id="userName" name="user_name" required></div>
                        <div class="col-md-6"><label for="email" class="form-label">Email Address</label><input type="email" class="form-control" id="email" name="email" required></div>
                        <div class="col-md-6"><label for="password" class="form-label">Password</label><input type="password" class="form-control" id="password" name="password"><small class="form-text text-muted" id="passwordHelp">Leave blank to keep current password.</small></div>
                        <div class="col-md-6"><label for="roleSelect" class="form-label">Role</label><select class="form-select" id="roleSelect" name="role" required><option value="agent" selected>Agent</option><option value="admin">Admin</option></select></div>
                        <div class="col-md-6 agent-fields"><label for="teamSelect" class="form-label">Team</label><select class="form-select" id="teamSelect"><option value="">-- Select Team --</option><?php foreach ($teams as $team): ?><option value="<?= $team['Team_ID'] ?>"><?= htmlspecialchars($team['Team_Name']) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-6 agent-fields"><label for="podSelect" class="form-label">Pod</label><select class="form-select" id="podSelect" name="pod_id"><option value="">-- Select Team First --</option><?php foreach ($pods as $pod): ?><option class="d-none" value="<?= $pod['Pod_ID'] ?>" data-team="<?= $pod['Team_ID'] ?>"><?= htmlspecialchars($pod['Pod_Name']) ?></option><?php endforeach; ?></select></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveUserBtn">Save User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Main Content Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Manage Users</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal" id="addUserBtn">
        <i class="bi bi-plus-lg"></i> Add New User
    </button>
</div>

<!-- Display success or error messages (No Changes) -->
<?php if (!empty($success)): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?= htmlspecialchars($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
<?php if (!empty($errors)): ?><div class="alert alert-danger" role="alert"><ul class="mb-0"><?php foreach ($errors as $error) echo "<li>" . htmlspecialchars($error) . "</li>"; ?></ul></div><?php endif; ?>

<!-- Users Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Team</th>
                        <th>Pod</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="6" class="text-center text-muted">No users found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($user['User_Name']) ?></strong></td>
                                <td><?= htmlspecialchars($user['Email']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $user['Role'] === 'admin' ? 'primary' : 'secondary' ?>">
                                        <?= ucfirst($user['Role']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($user['Team_Name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($user['Pod_Name'] ?? 'N/A') ?></td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-light border edit-btn" data-bs-toggle="modal" data-bs-target="#userModal"
                                        data-user='<?= htmlspecialchars(json_encode($user), ENT_QUOTES, 'UTF-8') ?>'>
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <a href="?action=delete&id=<?= $user['User_ID'] ?>" class="btn btn-sm btn-light border" onclick="return confirm('Are you sure you want to delete this user?');">
                                        <i class="bi bi-trash3-fill"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- JavaScript (No Changes) -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const userModal = document.getElementById('userModal'); const modalTitle = document.getElementById('userModalLabel'); const formAction = document.getElementById('formAction'); const userIdInput = document.getElementById('userId'); const userNameInput = document.getElementById('userName'); const emailInput = document.getElementById('email'); const passwordInput = document.getElementById('password'); const passwordHelp = document.getElementById('passwordHelp'); const roleSelect = document.getElementById('roleSelect'); const teamSelect = document.getElementById('teamSelect'); const podSelect = document.getElementById('podSelect'); const podOptions = podSelect.querySelectorAll('option[data-team]');
    function toggleAgentFields() {
        const agentFields = document.querySelectorAll('.agent-fields');
        if (roleSelect.value === 'admin') { agentFields.forEach(field => field.style.display = 'none'); podSelect.required = false; } else { agentFields.forEach(field => field.style.display = 'block'); podSelect.required = true; }
    }
    roleSelect.addEventListener('change', toggleAgentFields);
    teamSelect.addEventListener('change', function() {
        const selectedTeamId = this.value; podSelect.value = ''; let firstOption = podSelect.querySelector('option[value=""]'); podSelect.innerHTML = ''; if(firstOption) podSelect.appendChild(firstOption);
        podOptions.forEach(option => { if (option.dataset.team === selectedTeamId) { podSelect.appendChild(option); } });
    });
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function () {
            const userData = JSON.parse(this.dataset.user);
            modalTitle.textContent = 'Edit User'; formAction.value = 'edit_user'; passwordHelp.style.display = 'block'; passwordInput.required = false; userIdInput.value = userData.User_ID; userNameInput.value = userData.User_Name; emailInput.value = userData.Email; roleSelect.value = userData.Role;
            toggleAgentFields();
            if(userData.Role === 'agent') {
                let teamId = '';
                podOptions.forEach(opt => { if (opt.value == userData.Pod_ID) { teamId = opt.dataset.team; } });
                teamSelect.value = teamId; teamSelect.dispatchEvent(new Event('change')); podSelect.value = userData.Pod_ID;
            }
        });
    });
    document.getElementById('addUserBtn').addEventListener('click', function () {
        modalTitle.textContent = 'Add New User'; formAction.value = 'add_user'; passwordHelp.style.display = 'none'; passwordInput.required = true; userIdInput.value = ''; document.querySelector('#userModal form').reset(); toggleAgentFields();
    });
});
</script>

<?php
include __DIR__ . "/../includes/footer.php";
?>

