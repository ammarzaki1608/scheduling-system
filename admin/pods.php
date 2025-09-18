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

// --- Initialize variables ---
$errors = [];
$success = "";

// --- Handle Form Submissions (Create/Update) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    $pod_name = trim($_POST['pod_name'] ?? '');
    $team_id = filter_input(INPUT_POST, 'team_id', FILTER_VALIDATE_INT);
    $pod_id = filter_input(INPUT_POST, 'pod_id', FILTER_VALIDATE_INT);

    // --- Validation ---
    if (empty($pod_name)) $errors[] = "Pod Name is required.";
    if (empty($team_id)) $errors[] = "A Team must be selected.";

    if (empty($errors)) {
        // --- UPDATE ---
        if ($_POST['action'] === 'edit_pod' && $pod_id) {
            $stmt = $mysqli->prepare("UPDATE Pods SET Pod_Name = ?, Team_ID = ? WHERE Pod_ID = ?");
            $stmt->bind_param("sii", $pod_name, $team_id, $pod_id);
            if ($stmt->execute()) {
                $success = "Pod updated successfully!";
            } else {
                $errors[] = "Failed to update pod.";
            }
            $stmt->close();
        } 
        // --- CREATE ---
        elseif ($_POST['action'] === 'add_pod') {
            $stmt = $mysqli->prepare("INSERT INTO Pods (Pod_Name, Team_ID) VALUES (?, ?)");
            $stmt->bind_param("si", $pod_name, $team_id);
            if ($stmt->execute()) {
                $success = "Pod added successfully!";
            } else {
                $errors[] = "Failed to add pod.";
            }
            $stmt->close();
        }
    }
}

// --- Handle Delete Request ---
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $pod_id_to_delete = (int)$_GET['id'];
    // Note: Due to database constraints (ON DELETE CASCADE), this will also delete associated users.
    $stmt = $mysqli->prepare("DELETE FROM Pods WHERE Pod_ID = ?");
    $stmt->bind_param("i", $pod_id_to_delete);
    $stmt->execute();
    $stmt->close();
    header("Location: " . BASE_URL . "admin/pods.php?success=1");
    exit;
}
if (isset($_GET['success'])) {
    $success = "Pod and all associated users deleted successfully.";
}

// --- Fetch Data for Display ---
// Fetch all teams for the dropdown menu
$teams = $mysqli->query("SELECT Team_ID, Team_Name FROM Teams ORDER BY Team_Name ASC")->fetch_all(MYSQLI_ASSOC);

// Fetch all pods with their team name for the main table
$pods_sql = "
    SELECT p.Pod_ID, p.Pod_Name, t.Team_ID, t.Team_Name 
    FROM Pods p
    JOIN Teams t ON p.Team_ID = t.Team_ID
    ORDER BY t.Team_Name, p.Pod_Name ASC
";
$pods = $mysqli->query($pods_sql)->fetch_all(MYSQLI_ASSOC);

// --- Page Setup ---
$pageTitle = "Manage Pods";
include __DIR__ . "/../includes/header.php";
?>

<!-- Add/Edit Pod Modal -->
<div class="modal fade" id="podModal" tabindex="-1" aria-labelledby="podModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="podModalLabel">Add Pod</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add_pod">
                    <input type="hidden" name="pod_id" id="podId" value="">
                    
                    <div class="mb-3">
                        <label for="podName" class="form-label">Pod Name</label>
                        <input type="text" class="form-control" id="podName" name="pod_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="teamSelect" class="form-label">Parent Team</label>
                        <select class="form-select" id="teamSelect" name="team_id" required>
                            <option value="">-- Select a Team --</option>
                            <?php foreach ($teams as $team): ?>
                                <option value="<?= $team['Team_ID'] ?>"><?= htmlspecialchars($team['Team_Name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Pod</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Main Content Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Manage Pods</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#podModal" id="addPodBtn">
        <i class="bi bi-plus-lg"></i> Add New Pod
    </button>
</div>

<!-- Display success or error messages -->
<?php if (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger" role="alert">
        <ul class="mb-0">
            <?php foreach ($errors as $error) echo "<li>" . htmlspecialchars($error) . "</li>"; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Pods Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Pod Name</th>
                        <th>Parent Team</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pods)): ?>
                        <tr>
                            <td colspan="3" class="text-center text-muted">No pods found. Add one to get started!</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pods as $pod): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($pod['Pod_Name']) ?></strong></td>
                                <td><?= htmlspecialchars($pod['Team_Name']) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-secondary edit-btn" 
                                            data-bs-toggle="modal" data-bs-target="#podModal"
                                            data-id="<?= $pod['Pod_ID'] ?>"
                                            data-name="<?= htmlspecialchars($pod['Pod_Name']) ?>"
                                            data-teamid="<?= $pod['Team_ID'] ?>">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <a href="?action=delete&id=<?= $pod['Pod_ID'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this pod? This will also delete all associated users.');">
                                        <i class="bi bi-trash-fill"></i>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const podModal = document.getElementById('podModal');
    const modalTitle = document.getElementById('podModalLabel');
    const formAction = document.getElementById('formAction');
    const podIdInput = document.getElementById('podId');
    const podNameInput = document.getElementById('podName');
    const teamSelect = document.getElementById('teamSelect');
    const addPodBtn = document.getElementById('addPodBtn');

    // Handle Edit Button Clicks
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function () {
            const podId = this.dataset.id;
            const podName = this.dataset.name;
            const teamId = this.dataset.teamid;

            modalTitle.textContent = 'Edit Pod';
            formAction.value = 'edit_pod';
            podIdInput.value = podId;
            podNameInput.value = podName;
            teamSelect.value = teamId; // Set the selected team in the dropdown
        });
    });

    // Reset modal for adding a new pod
    addPodBtn.addEventListener('click', function () {
        modalTitle.textContent = 'Add Pod';
        formAction.value = 'add_pod';
        podIdInput.value = '';
        podNameInput.value = '';
        teamSelect.value = ''; // Reset the dropdown
    });
});
</script>

<?php
include __DIR__ . "/../includes/footer.php";
?>

