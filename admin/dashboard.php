<?php
// --- Includes ---
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/db_connect.php";
require_once __DIR__ . "/../includes/auth_check.php";

// --- Role check ---
// Although auth_check.php handles this, an explicit check is good practice.
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: " . BASE_URL . "auth/login.php?error=unauthorized");
    exit;
}

// --- Date Handling ---
$viewDateStr = $_GET['date'] ?? date('Y-m-d');
$viewDate = new DateTime($viewDateStr, new DateTimeZone(TIMEZONE));

// --- Data Fetching ---

// 1. Fetch all Teams and store them in an associative array for easy lookup
$teams_result = $mysqli->query("SELECT Team_ID, Team_Name, Color_Code FROM Teams");
$teamsById = [];
if ($teams_result) {
    while ($team = $teams_result->fetch_assoc()) {
        $teamsById[$team['Team_ID']] = $team;
    }
}


// 2. Fetch all appointments for the selected day, joining with user and pod data
$appointments_sql = "
    SELECT 
        a.Appointment_ID, a.Customer_Name, a.Subject, a.Start_At, a.End_At,
        u.User_Name as Agent_Name,
        p.Team_ID
    FROM Appointments a
    JOIN Users u ON a.Agent_ID = u.User_ID
    LEFT JOIN Pods p ON u.Pod_ID = p.Pod_ID
    WHERE DATE(a.Start_At) = ?
    ORDER BY a.Start_At ASC
";
$stmt = $mysqli->prepare($appointments_sql);
$stmt->bind_param("s", $viewDateStr);
$stmt->execute();
$appointments_result = $stmt->get_result();
$all_appointments = $appointments_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 3. Process appointments into a structure grouped by hour and team for easy display
$appointmentsByHour = [];
$appointmentsByTeam = array_fill_keys(array_keys($teamsById), 0);
$totalAppointments = count($all_appointments);

foreach ($all_appointments as $appt) {
    $hour = (int)date('G', strtotime($appt['Start_At']));
    $appointmentsByHour[$hour][] = $appt;
    if (isset($appt['Team_ID']) && isset($appointmentsByTeam[$appt['Team_ID']])) {
        $appointmentsByTeam[$appt['Team_ID']]++;
    }
}
ksort($appointmentsByHour); // Sort by hour

// --- Page Title ---
$pageTitle = "Scheduling Dashboard";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) . ' - ' . APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --bs-body-bg: #f7f8fc;
            --bs-body-font-family: 'Inter', sans-serif;
            --border-color: #e9ecef;
            --card-bg: #ffffff;
            --text-muted: #6c757d;
        }
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-size: 14px; }
        .dashboard-header { padding: 1.5rem 0; border-bottom: 1px solid var(--border-color); }
        .overview-card, .schedule-card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
        }
        .department-list .badge { font-size: 0.75rem; padding: 0.4em 0.8em; border-radius: 0.5rem; font-weight: 500;}
        .live-indicator { display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; }
        .live-indicator .dot { width: 8px; height: 8px; background-color: #28a745; border-radius: 50%; animation: pulse 1.5s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        .schedule-timeline .time-slot { border-top: 1px solid var(--border-color); }
        .schedule-timeline .time-slot:first-child { border-top: 0; }
        .appointment-card { background-color: #f8f9fa; border-left: 4px solid; border-radius: 0.5rem; padding: 1rem; transition: transform 0.2s ease, box-shadow 0.2s ease; cursor: pointer; }
        .appointment-card:hover { transform: translateY(-3px); box-shadow: 0 8px 15px rgba(0,0,0,0.05); }
        .priority-tag { font-size: 0.7rem; font-weight: 600; padding: 0.2em 0.6em; border-radius: 0.3rem; }
        .priority-tag.high { background-color: #ffe8e6; color: #dc3545; }
        .priority-tag.medium { background-color: #fff4e6; color: #fd7e14; }
        .priority-tag.low { background-color: #e6f7ff; color: #0d6efd; }
    </style>
</head>
<body>
<div class="container-fluid px-4">
    <header class="dashboard-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h4 fw-bold mb-1">Scheduling Dashboard</h1>
            <span class="text-muted"><i class="bi bi-calendar-event me-2"></i><?= $viewDate->format('l, F j, Y') ?></span>
            <span class="text-muted ms-3"><i class="bi bi-arrow-clockwise me-2"></i>Last updated: <span id="last-updated">00:00:00</span></span>
        </div>
        <div>
            <a href="<?= BASE_URL ?>admin/users.php" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back to Admin Panel</a>
        </div>
    </header>

    <main class="py-4">
        <div class="row g-4">
            <!-- Left Column: Overview -->
            <div class="col-lg-4">
                <div class="overview-card">
                    <h5 class="fw-bold d-flex align-items-center"><i class="bi bi-pie-chart-fill me-2"></i>Today's Overview</h5>
                    <div class="text-center my-4">
                        <h1 class="display-4 fw-bold mb-0"><?= $totalAppointments ?></h1>
                        <p class="text-muted">Total Appointments</p>
                    </div>
                    <h6 class="fw-bold text-uppercase small text-muted">DEPARTMENTS</h6>
                    <ul class="list-group list-group-flush department-list">
                        <?php foreach ($teamsById as $id => $team): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <?= htmlspecialchars($team['Team_Name']) ?>
                            <span class="badge" style="background-color: <?= htmlspecialchars($team['Color_Code']) ?>20; color: <?= htmlspecialchars($team['Color_Code']) ?>;">
                                <?= $appointmentsByTeam[$id] ?>
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="mt-4 pt-3 border-top">
                        <div class="live-indicator">
                            <span class="dot"></span> Live Data Active
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Schedule Timeline -->
            <div class="col-lg-8">
                <div class="schedule-card">
                     <h5 class="fw-bold d-flex align-items-center mb-3"><i class="bi bi-clock-fill me-2"></i>Today's Schedule</h5>
                     <div class="schedule-timeline">
                        <?php for ($hour = 8; $hour < 18; $hour++): ?>
                            <div class="time-slot py-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="fw-bold mb-0"><?= date('g:00 A', strtotime("$hour:00")) ?></h6>
                                    <small class="text-muted"><?= count($appointmentsByHour[$hour] ?? []) ?> appointments</small>
                                </div>
                                <?php if (empty($appointmentsByHour[$hour])): ?>
                                    <p class="text-muted small fst-italic">No appointments scheduled for this time slot.</p>
                                <?php else: ?>
                                    <div class="row g-3">
                                        <?php foreach ($appointmentsByHour[$hour] as $appointment): 
                                            $team_color = $teamsById[$appointment['Team_ID']]['Color_Code'] ?? '#6c757d';
                                            $team_name = $teamsById[$appointment['Team_ID']]['Team_Name'] ?? 'Unassigned';
                                            $duration = (strtotime($appointment['End_At']) - strtotime($appointment['Start_At'])) / 60;
                                            // Mock priority for demonstration as the DB column doesn't exist yet.
                                            $priorities = ['High', 'Medium', 'Low'];
                                            $priority = $priorities[array_rand($priorities)];
                                        ?>
                                        <div class="col-md-6">
                                            <div class="appointment-card" style="border-left-color: <?= htmlspecialchars($team_color) ?>;">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <strong class="d-block"><i class="bi bi-clock me-2"></i><?= date('g:i A', strtotime($appointment['Start_At'])) ?></strong>
                                                        <span class="badge mt-1" style="background-color: <?= htmlspecialchars($team_color) ?>20; color: <?= htmlspecialchars($team_color) ?>;"><?= htmlspecialchars($team_name) ?></span>
                                                    </div>
                                                    <span class="priority-tag <?= strtolower($priority) ?>"><?= $priority ?></span>
                                                </div>
                                                <div class="mt-3">
                                                    <p class="fw-bold mb-1"><i class="bi bi-person me-2"></i><?= htmlspecialchars($appointment['Agent_Name']) ?></p>
                                                    <p class="text-muted small mb-1"><i class="bi bi-building me-2"></i><?= htmlspecialchars($appointment['Customer_Name']) ?></p>
                                                    <p class="small mb-2"><strong><?= htmlspecialchars($appointment['Subject']) ?></strong></p>
                                                    <small class="text-muted"><?= $duration ?> minutes</small>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endfor; ?>
                     </div>
                </div>
            </div>
        </div>
    </main>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const lastUpdatedEl = document.getElementById('last-updated');
    let seconds = 0;
    setInterval(() => {
        seconds++;
        const h = String(Math.floor(seconds / 3600)).padStart(2, '0');
        const m = String(Math.floor((seconds % 3600) / 60)).padStart(2, '0');
        const s = String(seconds % 60).padStart(2, '0');
        lastUpdatedEl.textContent = `${h}:${m}:${s}`;
    }, 1000);
});
</script>
</body>
</html>

