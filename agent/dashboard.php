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
$today = date('Y-m-d');

// 1. Fetch summary statistics using prepared statements for security
$stmt_upcoming = $mysqli->prepare("SELECT COUNT(*) FROM Appointments WHERE Agent_ID = ? AND Start_At >= NOW()");
$stmt_upcoming->bind_param("i", $agentId);
$stmt_upcoming->execute();
$upcoming = $stmt_upcoming->get_result()->fetch_row()[0] ?? 0;
$stmt_upcoming->close();

$stmt_completed = $mysqli->prepare("SELECT COUNT(*) FROM Appointments WHERE Agent_ID = ? AND Status = 'Completed'");
$stmt_completed->bind_param("i", $agentId);
$stmt_completed->execute();
$completed = $stmt_completed->get_result()->fetch_row()[0] ?? 0;
$stmt_completed->close();

$stmt_missed = $mysqli->prepare("SELECT COUNT(*) FROM Appointments WHERE Agent_ID = ? AND Status = 'Missed'");
$stmt_missed->bind_param("i", $agentId);
$stmt_missed->execute();
$missed = $stmt_missed->get_result()->fetch_row()[0] ?? 0;
$stmt_missed->close();

// 2. Fetch today's appointments for the agent
$todays_appts_sql = "
    SELECT Appointment_ID, Customer_Name, Case_Number, Subject, Start_At, End_At, Status
    FROM Appointments
    WHERE Agent_ID = ? AND DATE(Start_At) = ?
    ORDER BY Start_At ASC
";
$stmt_today = $mysqli->prepare($todays_appts_sql);
$stmt_today->bind_param("is", $agentId, $today);
$stmt_today->execute();
$todays_appointments = $stmt_today->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_today->close();


// --- Page Title ---
$pageTitle = "Agent Dashboard";

// --- Include Header ---
include __DIR__ . "/../includes/header.php";
?>

<!-- Main Content Header -->
<div class="mb-4">
    <h1 class="h3 mb-0">Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?>!</h1>
    <p class="text-muted">Here's your summary and today's schedule.</p>
</div>

<!-- Summary Stat Cards -->
<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body text-center">
                <i class="bi bi-calendar-event fs-1 text-primary"></i>
                <h5 class="card-title mt-3">Upcoming Appointments</h5>
                <p class="card-text fs-2 fw-bold"><?= $upcoming ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body text-center">
                <i class="bi bi-check2-circle fs-1 text-success"></i>
                <h5 class="card-title mt-3">Completed</h5>
                <p class="card-text fs-2 fw-bold"><?= $completed ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body text-center">
                <i class="bi bi-calendar-x fs-1 text-danger"></i>
                <h5 class="card-title mt-3">Missed</h5>
                <p class="card-text fs-2 fw-bold"><?= $missed ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Today's Schedule -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-0 pt-3">
        <h5 class="mb-0"><i class="bi bi-list-task me-2"></i>Today's Schedule - <?= date('l, F j, Y') ?></h5>
    </div>
    <div class="card-body">
        <?php if (empty($todays_appointments)): ?>
            <div class="text-center text-muted p-4">
                <p class="mb-0">You have no appointments scheduled for today.</p>
            </div>
        <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($todays_appointments as $appt): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong class="d-block"><?= date('g:i A', strtotime($appt['Start_At'])) ?> - <?= date('g:i A', strtotime($appt['End_At'])) ?></strong>
                            <span class="text-muted"><?= htmlspecialchars($appt['Subject']) ?></span>
                            <small class="d-block text-muted">Customer: <?= htmlspecialchars($appt['Customer_Name']) ?> | Case: <?= htmlspecialchars($appt['Case_Number']) ?></small>
                        </div>
                        <div>
                            <?php
                                $status_class = 'bg-secondary';
                                if ($appt['Status'] === 'Completed') $status_class = 'bg-success';
                                if ($appt['Status'] === 'Missed') $status_class = 'bg-danger';
                            ?>
                            <span class="badge <?= $status_class ?>"><?= htmlspecialchars($appt['Status']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>
