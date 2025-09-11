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

// --- Data Fetching ---
$agentId = (int) $_SESSION['user_id'];

// We will fetch all appointments for this agent.
// In a real application with lots of data, you would add pagination here.
$appointments_sql = "
    SELECT Appointment_ID, Customer_Name, Case_Number, Subject, Start_At, End_At, Status
    FROM Appointments
    WHERE Agent_ID = ?
    ORDER BY Start_At DESC
";
$stmt = $mysqli->prepare($appointments_sql);
$stmt->bind_param("i", $agentId);
$stmt->execute();
$appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// --- Page Setup ---
$pageTitle = "My Appointments";
include __DIR__ . "/../includes/header.php";
?>

<!-- Main Content Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">My Appointments</h1>
    <a href="<?= BASE_URL ?>agent/appointment_form.php" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Add New Appointment
    </a>
</div>

<!-- Appointments Table -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body">
        <!-- Future Filter/Search Controls can go here -->
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Customer</th>
                        <th>Subject</th>
                        <th>Scheduled Date & Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($appointments)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted p-4">
                                <p class="mb-0">You have no appointments in your history.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($appointments as $appt): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($appt['Customer_Name']) ?></strong>
                                    <small class="d-block text-muted">Case: <?= htmlspecialchars($appt['Case_Number']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($appt['Subject']) ?></td>
                                <td><?= date('M j, Y, g:i A', strtotime($appt['Start_At'])) ?></td>
                                <td>
                                    <?php
                                        $status_class = 'bg-secondary';
                                        if ($appt['Status'] === 'Completed') $status_class = 'bg-success';
                                        if ($appt['Status'] === 'Missed') $status_class = 'bg-danger';
                                    ?>
                                    <span class="badge rounded-pill <?= $status_class ?>"><?= htmlspecialchars($appt['Status']) ?></span>
                                </td>
                                <td>
                                    <a href="appointment_form.php?id=<?= $appt['Appointment_ID'] ?>" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-pencil-fill"></i> Edit
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

<?php
include __DIR__ . "/../includes/footer.php";
?>
