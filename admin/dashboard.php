<?php
// --- Includes & PHP Logic (No Changes) ---
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/db_connect.php";
require_once __DIR__ . "/../includes/auth_check.php";
// ... All PHP data fetching logic remains the same ...
$pageTitle = "Scheduling Dashboard";
$viewDateStr = $_GET['date'] ?? date('Y-m-d');
$viewDate = new DateTime($viewDateStr, new DateTimeZone(TIMEZONE));
$teams_result = $mysqli->query("SELECT Team_ID, Team_Name, Color_Code FROM Teams");
$teamsById = [];
if ($teams_result) { while ($team = $teams_result->fetch_assoc()) { $teamsById[$team['Team_ID']] = $team; } }
$appointments_sql = "SELECT a.Appointment_ID, a.Customer_Name, a.Subject, a.Start_At, a.End_At, u.User_Name as Agent_Name, p.Team_ID FROM Appointments a JOIN Users u ON a.Agent_ID = u.User_ID LEFT JOIN Pods p ON u.Pod_ID = p.Pod_ID WHERE DATE(a.Start_At) = ? ORDER BY a.Start_At ASC";
$stmt = $mysqli->prepare($appointments_sql); $stmt->bind_param("s", $viewDateStr); $stmt->execute();
$appointments_result = $stmt->get_result(); $all_appointments = $appointments_result->fetch_all(MYSQLI_ASSOC); $stmt->close();
$appointmentsByHour = []; $appointmentsByTeam = array_fill_keys(array_keys($teamsById), 0); $totalAppointments = count($all_appointments);
foreach ($all_appointments as $appt) {
    $hour = (int)date('G', strtotime($appt['Start_At']));
    $appointmentsByHour[$hour][] = $appt;
    if (isset($appt['Team_ID']) && isset($appointmentsByTeam[$appt['Team_ID']])) { $appointmentsByTeam[$appt['Team_ID']]++; }
}
ksort($appointmentsByHour);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) . ' - ' . APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <!-- UPDATED: Styles for the scrollable timeline -->
    <style>
        .schedule-card .card-body {
            /* Let the card body adjust its height */
            height: auto;
        }
        .schedule-timeline {
            /* Make the timeline itself the scrollable container */
            height: calc(100vh - 250px); /* Adjust height to fit screen minus header/padding */
            overflow-y: auto;
            padding-right: 10px; /* Add some padding for the scrollbar */
        }
    </style>
</head>
<body>
<div class="container-fluid p-4">
    <!-- Header (No Changes) -->
    <header class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom">
        <div>
            <h1 class="h4 fw-bold mb-1">Scheduling Dashboard</h1>
            <p class="text-muted small mb-0"><i class="bi bi-calendar-event me-2"></i><?= $viewDate->format('l, F j, Y') ?></p>
        </div>
        <div>
            <a href="<?= BASE_URL ?>admin/users.php" class="btn btn-light"><i class="bi bi-grid-3x3-gap-fill me-2"></i> Back to Main Panel</a>
        </div>
    </header>

    <main>
        <div class="row g-4">
            <!-- Left Column: Overview (No Changes) -->
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <h5 class="card-title fw-bold mb-4 d-flex align-items-center">
                            <i class="bi bi-people me-2"></i>Today's Overview
                        </h5>
                        <div class="text-center my-4 py-3">
                            <h1 class="display-4 fw-bold mb-0"><?= $totalAppointments ?></h1>
                            <p class="text-muted">Total Appointments</p>
                        </div>
                        <h6 class="fw-bold text-uppercase small text-muted mb-3">DEPARTMENTS</h6>
                        <ul class="list-group list-group-flush department-list">
                           <?php foreach ($teamsById as $id => $team): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="fw-500"><?= htmlspecialchars($team['Team_Name']) ?></span>
                                <span class="badge" style="background-color: <?= htmlspecialchars($team['Color_Code']) ?>20; color: <?= htmlspecialchars($team['Color_Code']) ?>;">
                                    <?= $appointmentsByTeam[$id] ?>
                                </span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                         <div class="mt-4 pt-4 border-top">
                            <div class="live-indicator">
                                <span class="dot"></span> Live Data Active
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Schedule Timeline -->
            <div class="col-lg-8">
                <div class="card">
                     <div class="card-body p-4">
                         <h5 class="card-title fw-bold mb-3">Today's Schedule</h5>
                         <!-- UPDATED: ID is now on the timeline container -->
                         <div class="schedule-timeline" id="schedule-scroll-container">
                            <?php for ($hour = 8; $hour < 18; $hour++): ?>
                                <div class="time-slot py-3 border-top">
                                    <h6 class="fw-bold mb-3 text-muted small text-uppercase"><?= date('g A', strtotime("$hour:00")) ?></h6>
                                    <?php if (empty($appointmentsByHour[$hour])): ?>
                                        <p class="text-muted small fst-italic">No appointments scheduled.</p>
                                    <?php else: ?>
                                        <div class="d-flex flex-column gap-3">
                                            <?php foreach ($appointmentsByHour[$hour] as $appointment): 
                                                $team_color = $teamsById[$appointment['Team_ID']]['Color_Code'] ?? '#6c757d';
                                            ?>
                                            <div class="d-flex align-items-start" style="border-left: 3px solid <?= htmlspecialchars($team_color) ?>; padding-left: 1rem;">
                                                <div class="flex-grow-1">
                                                    <p class="fw-bold mb-1"><?= htmlspecialchars($appointment['Subject']) ?></p>
                                                    <p class="text-muted small mb-1"><i class="bi bi-person me-2"></i><?= htmlspecialchars($appointment['Agent_Name']) ?></p>
                                                    <p class="text-muted small mb-0"><i class="bi bi-building me-2"></i><?= htmlspecialchars($appointment['Customer_Name']) ?></p>
                                                </div>
                                                <div class="text-end text-muted small fw-500">
                                                    <?= date('g:i A', strtotime($appointment['Start_At'])) ?>
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
        </div>
    </main>
</div>

<!-- JavaScript Section (UPDATED) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const scrollContainer = document.getElementById('schedule-scroll-container');
    if (!scrollContainer) return;

    // --- Configuration ---
    const scrollSpeed = 1; // Pixels per interval. Lower is slower.
    const resumeDelay = 2000; // 2 seconds delay before resuming.

    // --- State Variables ---
    let scrollInterval;
    let resumeTimeout;

    // This function handles the scrolling animation
    function autoScroll() {
        scrollContainer.scrollTop += scrollSpeed;
        // If we've reached the bottom, loop back to the top
        if (scrollContainer.scrollTop + scrollContainer.clientHeight >= scrollContainer.scrollHeight) {
            scrollContainer.scrollTop = 0;
        }
    }

    // This function starts the continuous scroll
    function startScrolling() {
        // Clear any pending timers to avoid conflicts
        clearInterval(scrollInterval);
        clearTimeout(resumeTimeout);

        // Only start if the content is actually taller than the container
        if (scrollContainer.scrollHeight > scrollContainer.clientHeight) {
            scrollInterval = setInterval(autoScroll, 20); // Adjust interval for smoothness
        }
    }

    // This function stops the scroll and sets a timer to resume
    function stopAndResumeScrolling() {
        clearInterval(scrollInterval); // Stop the current scroll
        clearTimeout(resumeTimeout);   // Clear any previous resume timer
        
        // Set a new timer to restart the scrolling after the delay
        resumeTimeout = setTimeout(() => {
            // When resuming, we reset to the top for a fresh loop
            scrollContainer.scrollTop = 0;
            startScrolling();
        }, resumeDelay);
    }

    // --- Event Listeners to stop auto-scroll on user interaction ---
    scrollContainer.addEventListener('wheel', stopAndResumeScrolling);
    scrollContainer.addEventListener('mousedown', stopAndResumeScrolling);
    scrollContainer.addEventListener('touchstart', stopAndResumeScrolling);

    // Start the auto-scroll when the page loads
    startScrolling();
});
</script>
</body>
</html>

