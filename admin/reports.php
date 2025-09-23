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

// --- Fetch Data for Reports ---
$totalAppointments = $mysqli->query("SELECT COUNT(*) FROM Appointments")->fetch_row()[0] ?? 0;
$totalAgents = $mysqli->query("SELECT COUNT(*) FROM Users WHERE Role = 'agent'")->fetch_row()[0] ?? 0;
$totalTeams = $mysqli->query("SELECT COUNT(*) FROM Teams")->fetch_row()[0] ?? 0;
$completedAppointments = $mysqli->query("SELECT COUNT(*) FROM Appointments WHERE Status = 'Completed'")->fetch_row()[0] ?? 0;

// --- Page Setup ---
$pageTitle = "System Reports";
include __DIR__ . "/../includes/header.php";
?>

<!-- Main Content Header -->
<div class="mb-4">
    <h1 class="h3 mb-0">System Reports</h1>
    <p class="text-muted">A high-level overview of all system activity.</p>
</div>

<!-- Stat Cards -->
<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center">
                <div class="flex-grow-1">
                    <div class="text-muted text-uppercase small">Total Appointments</div>
                    <div class="h3 fw-bold mb-0"><?= $totalAppointments ?></div>
                </div>
                <div class="fs-2 text-primary opacity-75"><i class="bi bi-calendar-check"></i></div>
            </div>
        </div>
    </div>
     <div class="col-xl-3 col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center">
                <div class="flex-grow-1">
                    <div class="text-muted text-uppercase small">Completed</div>
                    <div class="h3 fw-bold mb-0"><?= $completedAppointments ?></div>
                </div>
                <div class="fs-2 text-success opacity-75"><i class="bi bi-check2-circle"></i></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center">
                <div class="flex-grow-1">
                    <div class="text-muted text-uppercase small">Total Agents</div>
                    <div class="h3 fw-bold mb-0"><?= $totalAgents ?></div>
                </div>
                <div class="fs-2 text-info opacity-75"><i class="bi bi-people"></i></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center">
                <div class="flex-grow-1">
                    <div class="text-muted text-uppercase small">Total Teams</div>
                    <div class="h3 fw-bold mb-0"><?= $totalTeams ?></div>
                </div>
                <div class="fs-2 text-warning opacity-75"><i class="bi bi-diagram-3"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Future Advanced Reports Section -->
<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body text-center p-5">
                <h5 class="card-title">Agent Performance</h5>
                <p class="text-muted">A future report will display a chart of completed vs. missed appointments for each agent.</p>
                <i class="bi bi-bar-chart-steps fs-1 text-primary opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body text-center p-5">
                <h5 class="card-title">Peak Hour Analysis</h5>
                <p class="text-muted">A future report will show which hours of the day are busiest to help with resource planning.</p>
                <i class="bi bi-graph-up fs-1 text-success opacity-50"></i>
            </div>
        </div>
    </div>
</div>


<?php
include __DIR__ . "/../includes/footer.php";
?>

