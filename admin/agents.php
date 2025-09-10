<?php
// ---------------------------
// Admin Management: Agents/Admins
// ---------------------------

// --- Includes ---
// The paths are adjusted to be relative to the /admin/ directory
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/db_connect.php";
require_once __DIR__ . "/../includes/auth_check.php";

// --- Role check ---
// Ensure the user is an admin, otherwise redirect to the login page
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: " . BASE_URL . "auth/login.php?error=unauthorized");
    exit;
}

// --- Initialize variables ---
$errors = [];
$success = "";

// --- Handle Add User Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    // Sanitize and retrieve form data
    $name = trim($_POST['user_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? ''); // Plaintext password
    $pod_id = filter_input(INPUT_POST, 'pod_id', FILTER_VALIDATE_INT);
    $role = ($_POST['role'] ?? 'agent');

    // --- Validation ---
    if (empty($name)) $errors[] = "User Name is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid Email is required.";
    if (empty($password)) $errors[] = "Password is required.";
    if ($role === 'agent' && empty($pod_id)) $errors[] = "Pod selection is required for agents.";

    // Check for email uniqueness if validation passes so far
    if (empty($errors)) {
        $stmt = $mysqli->prepare("SELECT User_ID FROM Users WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "An account with this email address already exists.";
        }
        $stmt->close();
    }

    // --- Insert into Database ---
    if (empty($errors)) {
        // ⚠️ SECURITY WARNING: Storing plaintext password as per user request.
        $pod_value = ($role === 'admin') ? NULL : $pod_id;

        $stmt = $mysqli->prepare("INSERT INTO Users (User_Name, Email, Role, Password, Created_At, Pod_ID) VALUES (?, ?, ?, ?, NOW(), ?)");
        // The password is bound as a string 's'
        $stmt->bind_param("ssssi", $name, $email, $role, $password, $pod_value);

        if ($stmt->execute()) {
            $success = "User '" . htmlspecialchars($name) . "' was added successfully!";
        } else {
            $errors[] = "Failed to add the user due to a database error.";
        }
        $stmt->close();
    }
}

// --- Fetch Data for Page Display ---
$teams = $mysqli->query("SELECT Team_ID, Team_Name FROM Teams ORDER BY Team_Name ASC")->fetch_all(MYSQLI_ASSOC);
$pods = $mysqli->query("SELECT Pod_ID, Pod_Name, Team_ID FROM Pods ORDER BY Pod_Name ASC")->fetch_all(MYSQLI_ASSOC);
$users_sql = "
    SELECT u.User_ID, u.User_Name, u.Email, u.Role, u.Created_At,
           p.Pod_Name, t.Team_Name
    FROM Users u
    LEFT JOIN Pods p ON u.Pod_ID = p.Pod_ID
    LEFT JOIN Teams t ON p.Team_ID = t.Team_ID
    ORDER BY u.User_Name ASC
";
$users = $mysqli->query($users_sql)->fetch_all(MYSQLI_ASSOC);

// --- Page Title ---
$pageTitle = "Manage Users";

// --- Include Header ---
include __DIR__ . "/../includes/header.php";
?>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-sm rounded-4">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" id="addUserForm" action="agents.php">
                    <input type="hidden" name="action" value="add_user">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="userName" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="userName" name="user_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="col-md-6">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="col-md-6">
                            <label for="roleSelect" class="form-label">Role</label>
                            <select class="form-select" id="roleSelect" name="role" required>
                                <option value="agent" selected>Agent</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-md-6 agent-fields">
                            <label for="teamSelect" class="form-label">Team</label>
                            <select class="form-select" name="team_id" id="teamSelect">
                                <option value="">-- Select Team --</option>
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?= $team['Team_ID'] ?>"><?= htmlspecialchars($team['Team_Name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 agent-fields">
                            <label for="podSelect" class="form-label">Pod</label>
                            <select class="form-select" name="pod_id" id="podSelect">
                                <option value="">-- Select Team First --</option>
                                <?php foreach ($pods as $pod): ?>
                                    <option class="d-none" value="<?= $pod['Pod_ID'] ?>" data-team="<?= $pod['Team_ID'] ?>"><?= htmlspecialchars($pod['Pod_Name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="addUserForm" class="btn btn-primary">Add User</button>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Manage Users</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="bi bi-plus-lg"></i> Add New User</button>
</div>

<!-- Display success or error messages -->
<?php if (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $success ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            <?php foreach ($errors as $error) echo "<li>{$error}</li>"; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Users Table -->
<div class="card border-0 shadow-sm rounded-4">
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
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No users found. Start by adding one.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($user['User_Name']) ?></strong></td>
                                <td><?= htmlspecialchars($user['Email']) ?></td>
                                <td>
                                    <?php if ($user['Role'] === 'admin'): ?>
                                        <span class="badge bg-primary">Admin</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Agent</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($user['Team_Name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($user['Pod_Name'] ?? 'N/A') ?></td>
                                <td><?= date("M j, Y", strtotime($user['Created_At'])) ?></td>
                                <td>
                                    <a href="#" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="bi bi-pencil-fill"></i></a>
                                    <a href="#" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this user? This cannot be undone.');"><i class="bi bi-trash-fill"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const roleSelect = document.getElementById('roleSelect');
    const agentFields = document.querySelectorAll('.agent-fields');
    const teamSelect = document.getElementById('teamSelect');
    const podSelect = document.getElementById('podSelect');
    const podOptions = podSelect.querySelectorAll('option[data-team]');

    function toggleAgentFields() {
        const isAgent = roleSelect.value === 'agent';
        agentFields.forEach(field => field.style.display = isAgent ? 'block' : 'none');
        teamSelect.required = isAgent;
        podSelect.required = isAgent;
        if (!isAgent) {
            teamSelect.value = '';
            podSelect.value = '';
        }
    }

    roleSelect.addEventListener('change', toggleAgentFields);

    teamSelect.addEventListener('change', function() {
        const selectedTeamId = this.value;
        podSelect.innerHTML = '<option value="">-- Select Pod --</option>'; // Reset options
        podOptions.forEach(option => {
            if (option.dataset.team === selectedTeamId) {
                podSelect.appendChild(option.cloneNode(true));
            }
        });
        podSelect.value = '';
    });
    
    // Initial setup on page load
    toggleAgentFields();
});

// If there were errors on POST, open the modal automatically
<?php if (!empty($errors) && $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
    var addUserModal = new bootstrap.Modal(document.getElementById('addUserModal'));
    addUserModal.show();
<?php endif; ?>
</script>

<?php include __DIR__ . "/../includes/footer.php"; ?>
