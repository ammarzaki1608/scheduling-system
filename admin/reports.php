<?php
// --- Includes ---
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/db_connect.php";
require_once __DIR__ . "/../includes/auth_check.php";
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') { header("Location: " . BASE_URL . "auth/login.php?error=unauthorized"); exit; }

// --- Date Range Filtering ---
$today = date('Y-m-d');
$filter_start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-29 days'));
$filter_end_date = $_GET['end_date'] ?? $today;

// --- Data Fetching for All Reports ---

// 1. Fetch Agent Performance Stats
$agent_stats_sql = "
    SELECT
        u.User_Name,
        COUNT(a.Appointment_ID) AS TotalAppointments,
        SUM(CASE WHEN a.Status = 'Completed' THEN 1 ELSE 0 END) AS Completed,
        SUM(CASE WHEN a.Status = 'Missed' THEN 1 ELSE 0 END) AS Missed
    FROM Users u
    LEFT JOIN Appointments a ON u.User_ID = a.Agent_ID AND DATE(a.Start_At) BETWEEN ? AND ?
    WHERE u.Role = 'agent'
    GROUP BY u.User_ID
    ORDER BY TotalAppointments DESC, u.User_Name ASC
";
$stmt_agents = $mysqli->prepare($agent_stats_sql);
$stmt_agents->bind_param("ss", $filter_start_date, $filter_end_date);
$stmt_agents->execute();
$agent_stats = $stmt_agents->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_agents->close();

// 2. Fetch Team Performance Stats
$team_stats_sql = "
    SELECT t.Team_Name, COUNT(a.Appointment_ID) AS TotalAppointments,
           SUM(CASE WHEN a.Status = 'Completed' THEN 1 ELSE 0 END) AS Completed
    FROM Teams t
    LEFT JOIN Pods p ON t.Team_ID = p.Team_ID
    LEFT JOIN Users u ON p.Pod_ID = u.Pod_ID
    LEFT JOIN Appointments a ON u.User_ID = a.Agent_ID AND DATE(a.Start_At) BETWEEN ? AND ?
    GROUP BY t.Team_ID ORDER BY t.Team_Name ASC";
$stmt_teams = $mysqli->prepare($team_stats_sql);
$stmt_teams->bind_param("ss", $filter_start_date, $filter_end_date);
$stmt_teams->execute();
$team_stats = $stmt_teams->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_teams->close();

// 3. Fetch Busiest Hours
$hourly_stats_sql = "
    SELECT HOUR(Start_At) AS Hour, COUNT(Appointment_ID) AS AppointmentCount
    FROM Appointments WHERE DATE(Start_At) BETWEEN ? AND ?
    GROUP BY Hour ORDER BY Hour ASC";
$stmt_hours = $mysqli->prepare($hourly_stats_sql);
$stmt_hours->bind_param("ss", $filter_start_date, $filter_end_date);
$stmt_hours->execute();
$hourly_stats_result = $stmt_hours->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_hours->close();

$hourly_stats = array_fill(8, 10, 0);
$max_appointments_in_hour = 0;
foreach($hourly_stats_result as $row) {
    $hour = (int)$row['Hour'];
    if ($hour >= 8 && $hour < 18) {
        $hourly_stats[$hour] = $row['AppointmentCount'];
        if ($row['AppointmentCount'] > $max_appointments_in_hour) {
            $max_appointments_in_hour = $row['AppointmentCount'];
        }
    }
}

// 4. Fetch Top Appointment Subjects
$subject_stats_sql = "
    SELECT Subject, COUNT(Appointment_ID) as AppointmentCount
    FROM Appointments
    WHERE DATE(Start_At) BETWEEN ? AND ? AND Subject != ''
    GROUP BY Subject
    ORDER BY AppointmentCount DESC
    LIMIT 5
";
$stmt_subjects = $mysqli->prepare($subject_stats_sql);
$stmt_subjects->bind_param("ss", $filter_start_date, $filter_end_date);
$stmt_subjects->execute();
$subject_stats = $stmt_subjects->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_subjects->close();


$pageTitle = "System Reports";
include __DIR__ . "/../includes/header.php";
?>

<!-- Main Content Header -->
<div class="mb-4">
    <h1 class="h3 mb-0">System Reports</h1>
    <p class="text-muted">Analyze system activity within a specific date range.</p>
</div>

<!-- Date Range Filter Card -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-center">
            <div class="col-md-5">
                <label for="startDate" class="form-label">Start Date</label>
                <input type="text" class="form-control" id="startDate" name="start_date" value="<?= htmlspecialchars($filter_start_date) ?>">
            </div>
            <div class="col-md-5">
                <label for="endDate" class="form-label">End Date</label>
                <input type="text" class="form-control" id="endDate" name="end_date" value="<?= htmlspecialchars($filter_end_date) ?>">
            </div>
            <div class="col-md-2 d-flex align-self-end">
                <button type="submit" class="btn btn-primary w-100">Apply Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-4">
    <!-- Agent Performance Table -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title fw-bold">Agent Performance</h5>
                <p class="text-muted small">Report for <?= date('M j, Y', strtotime($filter_start_date)) ?> to <?= date('M j, Y', strtotime($filter_end_date)) ?></p>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr><th>Agent Name</th><th>Completed</th><th>Missed</th><th>Total</th><th>Completion Rate</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($agent_stats as $agent): 
                                $completion_rate = ($agent['TotalAppointments'] > 0) ? round(($agent['Completed'] / $agent['TotalAppointments']) * 100) : 0;
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($agent['User_Name']) ?></strong></td>
                                <td><?= (int)$agent['Completed'] ?></td>
                                <td><?= (int)$agent['Missed'] ?></td>
                                <td><?= (int)$agent['TotalAppointments'] ?></td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar" role="progressbar" style="width: <?= $completion_rate ?>%;" aria-valuenow="<?= $completion_rate ?>" aria-valuemin="0" aria-valuemax="100"><?= $completion_rate ?>%</div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Team Performance Table -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title fw-bold">Team Performance</h5>
                <p class="text-muted small">Aggregated team statistics for the selected date range.</p>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr><th>Team Name</th><th>Completed</th><th>Total</th><th>Completion Rate</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($team_stats as $team): 
                                $completion_rate = ($team['TotalAppointments'] > 0) ? round(($team['Completed'] / $team['TotalAppointments']) * 100) : 0;
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($team['Team_Name']) ?></strong></td>
                                <td><?= (int)$team['Completed'] ?></td>
                                <td><?= (int)$team['TotalAppointments'] ?></td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= $completion_rate ?>%;" aria-valuenow="<?= $completion_rate ?>" aria-valuemin="0" aria-valuemax="100"><?= $completion_rate ?>%</div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <!-- Busiest Hours Chart -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-body">
                 <h5 class="card-title fw-bold">Busiest Hours</h5>
                 <p class="text-muted small">Analysis of appointment start times within the selected date range.</p>
                 <div class="mt-4">
                    <?php foreach ($hourly_stats as $hour => $count): 
                        $percentage = ($max_appointments_in_hour > 0) ? ($count / $max_appointments_in_hour) * 100 : 0;
                    ?>
                    <div class="row g-2 align-items-center mb-2">
                        <div class="col-2 text-muted small text-end"><?= date('g A', strtotime("$hour:00")) ?></div>
                        <div class="col-10">
                            <div class="progress" style="height: 25px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $percentage ?>%;" aria-valuenow="<?= $count ?>" aria-valuemin="0" aria-valuemax="<?= $max_appointments_in_hour ?>">
                                    <strong class="ms-2"><?= $count ?> Appts</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                 </div>
            </div>
        </div>
    </div>

    <!-- Top Appointment Subjects -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title fw-bold">Top Subjects</h5>
                <p class="text-muted small">Most frequent appointment subjects in the selected date range.</p>
                <?php if (empty($subject_stats)): ?>
                    <p class="text-muted text-center mt-4">No data available for this period.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach($subject_stats as $subject): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-truncate" title="<?= htmlspecialchars($subject['Subject']) ?>">
                                    <?= htmlspecialchars($subject['Subject']) ?>
                                </span>
                                <span class="badge bg-primary rounded-pill"><?= $subject['AppointmentCount'] ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    flatpickr("#startDate", { altInput: true, altFormat: "M j, Y", dateFormat: "Y-m-d", defaultDate: "<?= htmlspecialchars($filter_start_date) ?>" });
    flatpickr("#endDate", { altInput: true, altFormat: "M j, Y", dateFormat: "Y-m-d", defaultDate: "<?= htmlspecialchars($filter_end_date) ?>" });
});
</script>

<?php
include __DIR__ . "/../includes/footer.php";
?>

