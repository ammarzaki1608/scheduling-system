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
$team_name = "";
$color_code = "#0d6efd"; // Default color

// --- Handle Form Submissions (Create/Update) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    $team_name = trim($_POST['team_name'] ?? '');
    $color_code = trim($_POST['color_code'] ?? '');
    $team_id = filter_input(INPUT_POST, 'team_id', FILTER_VALIDATE_INT);

    // --- Validation ---
    if (empty($team_name)) $errors[] = "Team Name is required.";
    if (empty($color_code) || !preg_match('/^#[a-f0-9]{6}$/i', $color_code)) {
        $errors[] = "A valid hex color code (e.g., #123456) is required.";
    }

    if (empty($errors)) {
        // --- UPDATE ---
        if ($_POST['action'] === 'edit_team' && $team_id) {
            $stmt = $mysqli->prepare("UPDATE Teams SET Team_Name = ?, Color_Code = ? WHERE Team_ID = ?");
            $stmt->bind_param("ssi", $team_name, $color_code, $team_id);
            if ($stmt->execute()) {
                $success = "Team updated successfully!";
            } else {
                $errors[] = "Failed to update team.";
            }
            $stmt->close();
        } 
        // --- CREATE ---
        elseif ($_POST['action'] === 'add_team') {
            $stmt = $mysqli->prepare("INSERT INTO Teams (Team_Name, Color_Code) VALUES (?, ?)");
            $stmt->bind_param("ss", $team_name, $color_code);
            if ($stmt->execute()) {
                $success = "Team added successfully!";
            } else {
                $errors[] = "Failed to add team.";
            }
            $stmt->close();
        }
    }
}

// --- Handle Delete Request ---
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $team_id_to_delete = (int)$_GET['id'];
    // Note: Due to database constraints (ON DELETE CASCADE), deleting a team will also delete its pods and users.
    $stmt = $mysqli->prepare("DELETE FROM Teams WHERE Team_ID = ?");
    $stmt->bind_param("i", $team_id_to_delete);
    if ($stmt->execute()) {
        $success = "Team and all associated pods/users deleted successfully.";
    } else {
        $errors[] = "Failed to delete team.";
    }
    $stmt->close();
    // Redirect to clean the URL
    header("Location: " . BASE_URL . "admin/teams.php?success=1");
    exit;
}
if (isset($_GET['success'])) {
    $success = "Team and all associated pods/users deleted successfully.";
}


// --- Fetch all teams for display ---
$teams = $mysqli->query("SELECT Team_ID, Team_Name, Color_Code FROM Teams ORDER BY Team_Name ASC")->fetch_all(MYSQLI_ASSOC);

// --- Page Setup ---
$pageTitle = "Manage Teams";
include __DIR__ . "/../includes/header.php"; // Include the template header
?>

<!-- Add/Edit Team Modal -->
<div class="modal fade" id="teamModal" tabindex="-1" aria-labelledby="teamModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-sm rounded-4">
            <form method="post">
                <div class="modal-header border-0">
                    <h5 class="modal-title" id="teamModalLabel">Add Team</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add_team">
                    <input type="hidden" name="team_id" id="teamId" value="">
                    
                    <div class="mb-3">
                        <label for="teamName" class="form-label">Team Name</label>
                        <input type="text" class="form-control" id="teamName" name="team_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="colorCode" class="form-label">Team Color</label>
                        <input type="color" class="form-control form-control-color" id="colorCode" name="color_code" value="#0d6efd" title="Choose your color">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Team</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Main Content Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Manage Teams</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#teamModal" id="addTeamBtn">
        <i class="bi bi-plus-lg"></i> Add New Team
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

<!-- Teams Table -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Team Name</th>
                        <th>Color Code</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($teams)): ?>
                        <tr>
                            <td colspan="3" class="text-center text-muted">No teams found. Add one to get started!</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($teams as $team): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($team['Team_Name']) ?></strong></td>
                                <td>
                                    <span class="badge p-2" style="background-color: <?= htmlspecialchars($team['Color_Code']) ?>;">
                                        <?= htmlspecialchars($team['Color_Code']) ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-secondary edit-btn" 
                                            data-bs-toggle="modal" data-bs-target="#teamModal"
                                            data-id="<?= $team['Team_ID'] ?>"
                                            data-name="<?= htmlspecialchars($team['Team_Name']) ?>"
                                            data-color="<?= htmlspecialchars($team['Color_Code']) ?>">
                                        <i class="bi bi-pencil-fill"></i> Edit
                                    </button>
                                    <a href="?action=delete&id=<?= $team['Team_ID'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this team? This will also delete all associated pods and users.');">
                                        <i class="bi bi-trash-fill"></i> Delete
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
    const teamModal = document.getElementById('teamModal');
    const modalTitle = document.getElementById('teamModalLabel');
    const formAction = document.getElementById('formAction');
    const teamIdInput = document.getElementById('teamId');
    const teamNameInput = document.getElementById('teamName');
    const colorCodeInput = document.getElementById('colorCode');
    const addTeamBtn = document.getElementById('addTeamBtn');

    // Handle Edit Button Clicks
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function () {
            const teamId = this.dataset.id;
            const teamName = this.dataset.name;
            const teamColor = this.dataset.color;

            modalTitle.textContent = 'Edit Team';
            formAction.value = 'edit_team';
            teamIdInput.value = teamId;
            teamNameInput.value = teamName;
            colorCodeInput.value = teamColor;
        });
    });

    // Reset modal for adding a new team
    addTeamBtn.addEventListener('click', function () {
        modalTitle.textContent = 'Add Team';
        formAction.value = 'add_team';
        teamIdInput.value = '';
        teamNameInput.value = '';
        colorCodeInput.value = '#0d6efd'; // Reset to default
    });
});
</script>

<?php
include __DIR__ . "/../includes/footer.php"; // Include the template footer
?>
