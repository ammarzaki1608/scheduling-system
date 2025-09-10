<?php
// ---------------------------
// Admin Management: Agents/Admins
// ---------------------------

// Display all PHP errors (development only)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- Includes ---
require_once "../includes/config.php";
require_once "../includes/db_connect.php";
require_once "../includes/auth_check.php";

// --- Role check ---
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php?unauthorized=1");
    exit;
}

// --- Initialize variables ---
$errors = [];
$success = "";

// --- Handle Add User Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_user') {
    $name = trim($_POST['user_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $team_id = intval($_POST['team_id'] ?? 0);
    $pod_id = intval($_POST['pod_id'] ?? 0);
    $role = ($_POST['role'] ?? 'agent') === 'admin' ? 'admin' : 'agent';

    // Validation
    if ($name === '') $errors[] = "Name is required.";
    if ($email === '') $errors[] = "Email is required.";
    if ($password === '') $errors[] = "Password is required.";
    if ($role === 'agent' && $pod_id <= 0) $errors[] = "Pod selection is required for agents.";

    // Check email uniqueness
    $stmt = $mysqli->prepare("SELECT User_ID FROM Users WHERE Email = ?");
    $stmt->bind_param("s", $email);
    if (!$stmt->execute()) {
        $errors[] = "Database error: " . $stmt->error;
    } else {
        $stmt->store_result();
        if ($stmt->num_rows > 0) $errors[] = "Email already exists.";
    }
    $stmt->close();

    // Insert into DB
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $pod_value = ($role === 'admin') ? NULL : $pod_id;

        $stmt = $mysqli->prepare("INSERT INTO Users (User_Name, Email, Role, Password, Created_At, Pod_ID) VALUES (?, ?, ?, ?, NOW(), ?)");
        $stmt->bind_param("ssssi", $name, $email, $role, $hashed_password, $pod_value);

        if ($stmt->execute()) {
            $success = "$role added successfully!";
        } else {
            $errors[] = "Database error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// --- Fetch Teams ---
$teams = [];
if ($result = $mysqli->query("SELECT Team_ID, Team_Name FROM Teams ORDER BY Team_Name ASC")) {
    while ($row = $result->fetch_assoc()) $teams[] = $row;
}

// --- Fetch Pods ---
$pods = [];
if ($result = $mysqli->query("SELECT Pod_ID, Pod_Name, Team_ID FROM Pods ORDER BY Pod_Name ASC")) {
    while ($row = $result->fetch_assoc()) $pods[] = $row;
}

// --- Fetch Users ---
$users = [];
$sql = "
    SELECT u.User_ID, u.User_Name, u.Email, u.Role, u.Created_At,
           p.Pod_Name, t.Team_Name
    FROM Users u
    LEFT JOIN Pods p ON u.Pod_ID = p.Pod_ID
    LEFT JOIN Teams t ON p.Team_ID = t.Team_ID
    ORDER BY u.Created_At DESC
";
if ($result = $mysqli->query($sql)) {
    while ($row = $result->fetch_assoc()) $users[] = $row;
}
?>
<?php include "../includes/header.php"; ?>

<h2>Manage Users (Agents/Admins)</h2>

<!-- Display success or error messages -->
<?php if ($success) echo "<p class='success'>{$success}</p>"; ?>
<?php foreach ($errors as $error) echo "<p class='error'>{$error}</p>"; ?>

<!-- Add User Form -->
<form method="post" id="addUserForm">
    <input type="hidden" name="action" value="add_user">

    <label>Name:</label>
    <input type="text" name="user_name" required>

    <label>Email:</label>
    <input type="email" name="email" required>

    <label>Password:</label>
    <input type="password" name="password" required>

    <label>Role:</label>
    <select name="role" required>
        <option value="agent">Agent</option>
        <option value="admin">Admin</option>
    </select>

    <label>Team:</label>
    <select name="team_id" id="teamSelect">
        <option value="">-- Select Team --</option>
        <?php foreach ($teams as $team): ?>
            <option value="<?= $team['Team_ID'] ?>"><?= htmlspecialchars($team['Team_Name']) ?></option>
        <?php endforeach; ?>
    </select>

    <label>Pod:</label>
    <select name="pod_id" id="podSelect">
        <option value="">-- Select Pod --</option>
        <?php foreach ($pods as $pod): ?>
            <option value="<?= $pod['Pod_ID'] ?>" data-team="<?= $pod['Team_ID'] ?>"><?= htmlspecialchars($pod['Pod_Name']) ?></option>
        <?php endforeach; ?>
    </select>

    <button type="submit">Add User</button>
</form>

<!-- Users Table -->
<h3>Existing Users</h3>
<table border="1" cellpadding="5">
    <thead>
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
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= htmlspecialchars($user['User_Name']) ?></td>
                <td><?= htmlspecialchars($user['Email']) ?></td>
                <td><?= htmlspecialchars($user['Role']) ?></td>
                <td><?= htmlspecialchars($user['Team_Name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($user['Pod_Name'] ?? '-') ?></td>
                <td><?= $user['Created_At'] ?></td>
                <td>
                    <a href="edit_user.php?id=<?= $user['User_ID'] ?>">Edit</a> |
                    <a href="delete_user.php?id=<?= $user['User_ID'] ?>" onclick="return confirm('Delete this user?');">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script>
// Dynamic Pod filtering
const teamSelect = document.getElementById('teamSelect');
const podSelect = document.getElementById('podSelect');
const allPods = Array.from(podSelect.options);

teamSelect.addEventListener('change', function() {
    const teamId = this.value;
    podSelect.innerHTML = "<option value=''>-- Select Pod --</option>";
    allPods.forEach(opt => {
        if (!opt.dataset.team) return;
        if (opt.dataset.team === teamId) podSelect.appendChild(opt);
    });
});
</script>

<?php include "../includes/footer.php"; ?>
