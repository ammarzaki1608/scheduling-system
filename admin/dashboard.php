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

// --- Date Handling ---
// Determine the day to display. Default is today.
// The timezone is set in config.php
$viewDateStr = $_GET['date'] ?? date('Y-m-d');
$viewDate = new DateTime($viewDateStr, new DateTimeZone(TIMEZONE));
$today = new DateTime('now', new DateTimeZone(TIMEZONE));

// --- Data Fetching ---

// 1. Fetch all agents to create the timeline rows
$agents_sql = "SELECT User_ID, User_Name FROM Users WHERE Role = 'agent' ORDER BY User_Name ASC";
$agents = $mysqli->query($agents_sql)->fetch_all(MYSQLI_ASSOC);

// 2. Fetch appointments for the selected day
$appointments_sql = "
    SELECT 
        a.Appointment_ID, a.Agent_ID, a.Customer_Name, a.Case_Number, 
        a.Subject, a.Start_At, a.End_At, a.Status,
        u.User_Name as Agent_Name
    FROM Appointments a
    JOIN Users u ON a.Agent_ID = u.User_ID
    WHERE DATE(a.Start_At) = ?
    ORDER BY a.Start_At ASC
";
$stmt = $mysqli->prepare($appointments_sql);
$stmt->bind_param("s", $viewDateStr);
$stmt->execute();
$result = $stmt->get_result();
$appointments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Group appointments by Agent_ID for easy lookup
$agentAppointments = [];
foreach ($appointments as $appt) {
    $agentAppointments[$appt['Agent_ID']][] = $appt;
}

// --- Page Title ---
$pageTitle = "Admin Dashboard";

// --- Include Header ---
include __DIR__ . "/../includes/header.php";

// --- Helper function to calculate timeline position ---
function get_timeline_styles($start_at, $end_at) {
    $start = new DateTime($start_at);
    $end = new DateTime($end_at);

    // Timeline runs from 8 AM (0%) to 6 PM (100%), which is a 10-hour span (600 minutes)
    $timeline_start_hour = 8;
    $timeline_total_minutes = (18 - $timeline_start_hour) * 60;

    // Calculate start position
    $start_minutes = (($start->format('H') - $timeline_start_hour) * 60) + $start->format('i');
    $start_percentage = ($start_minutes / $timeline_total_minutes) * 100;
    
    // Calculate duration
    $duration_minutes = ($end->getTimestamp() - $start->getTimestamp()) / 60;
    $width_percentage = ($duration_minutes / $timeline_total_minutes) * 100;

    // Ensure blocks don't go past the timeline boundaries
    $start_percentage = max(0, min(100, $start_percentage));
    $width_percentage = min($width_percentage, 100 - $start_percentage);

    return "left: {$start_percentage}%; width: {$width_percentage}%;";
}
?>

<!-- Main Content Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Team Dashboard</h1>
        <p class="text-muted mb-0">
            Schedule for: <strong><?= $viewDate->format('l, F j, Y') ?></strong>
        </p>
    </div>
    <div class="btn-group" role="group">
        <a href="?date=<?= $viewDate->modify('-1 day')->format('Y-m-d') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-chevron-left"></i> Previous Day
        </a>
        <a href="?date=<?= $today->format('Y-m-d') ?>" class="btn btn-outline-secondary">Today</a>
        <a href="?date=<?= $viewDate->modify('+2 days')->format('Y-m-d') ?>" class="btn btn-outline-secondary">
            Next Day <i class="bi bi-chevron-right"></i>
        </a>
    </div>
</div>

<!-- Timeline Dashboard -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
        <div class="timeline-container">
            <!-- Timeline Header (Hours) -->
            <div class="timeline-header">
                <?php for ($hour = 8; $hour < 18; $hour++): ?>
                    <div class="timeline-hour"><?= date('g A', strtotime("$hour:00")) ?></div>
                <?php endfor; ?>
            </div>

            <!-- Live Time Indicator -->
            <div class="timeline-now" id="timeline-now-indicator"></div>

            <!-- Agent Rows -->
            <div class="timeline-body">
                <?php if (empty($agents)): ?>
                    <div class="text-center text-muted p-5">No agents have been added to the system yet. <a href="agents.php">Add an agent</a>.</div>
                <?php else: ?>
                    <?php foreach ($agents as $agent): ?>
                        <div class="agent-row">
                            <div class="agent-name">
                                <strong><?= htmlspecialchars($agent['User_Name']) ?></strong>
                            </div>
                            <div class="agent-timeline">
                                <?php // Draw vertical grid lines for each hour ?>
                                <?php for ($i = 0; $i < 10; $i++): ?>
                                    <div class="timeline-grid-line"></div>
                                <?php endfor; ?>
                                
                                <?php // Display appointments for this agent ?>
                                <?php if (isset($agentAppointments[$agent['User_ID']])): ?>
                                    <?php foreach ($agentAppointments[$agent['User_ID']] as $appointment): ?>
                                        <div class="appointment-block bg-primary text-white" 
                                             style="<?= get_timeline_styles($appointment['Start_At'], $appointment['End_At']) ?>"
                                             data-bs-toggle="tooltip"
                                             data-bs-placement="top"
                                             title="<strong>Customer:</strong> <?= htmlspecialchars($appointment['Customer_Name']) ?><br><strong>Case:</strong> <?= htmlspecialchars($appointment['Case_Number']) ?><br><strong>Time:</strong> <?= date('g:i A', strtotime($appointment['Start_At'])) ?> - <?= date('g:i A', strtotime($appointment['End_At'])) ?>">
                                             <div class="appointment-subject"><?= htmlspecialchars($appointment['Subject']) ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    .timeline-container { overflow-x: auto; }
    .timeline-header {
        display: flex;
        padding-left: 150px; /* Agent name width */
        border-bottom: 2px solid #e9ecef;
    }
    .timeline-hour {
        flex: 1 0 10%; /* 10 hours = 10% width each */
        text-align: center;
        padding: 8px 0;
        font-size: 0.8rem;
        color: #6c757d;
        border-left: 1px solid #e9ecef;
    }
    .timeline-body { position: relative; }
    .agent-row { display: flex; align-items: stretch; border-bottom: 1px solid #e9ecef; }
    .agent-name {
        flex: 0 0 150px;
        padding: 16px;
        font-weight: 500;
        background-color: #f8f9fa;
        border-right: 2px solid #e9ecef;
    }
    .agent-timeline { flex-grow: 1; position: relative; display: flex; }
    .timeline-grid-line {
        flex: 1 0 10%;
        border-left: 1px dotted #dee2e6;
    }
    .appointment-block {
        position: absolute;
        top: 10px;
        bottom: 10px;
        border-radius: 8px;
        padding: 4px 8px;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
        font-size: 0.8rem;
        cursor: pointer;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .appointment-block:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        z-index: 10;
    }
    .timeline-now {
        position: absolute;
        top: 0;
        bottom: 0;
        width: 2px;
        background-color: #dc3545;
        z-index: 20;
        opacity: 0.8;
        display: none; /* Hidden by default */
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap Tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, { html: true });
    });

    // --- Live Time Indicator Logic ---
    function updateTimeIndicator() {
        const indicator = document.getElementById('timeline-now-indicator');
        const viewDate = '<?= $viewDate->format('Y-m-d') ?>';
        const today = '<?= $today->format('Y-m-d') ?>';
        
        // Only show the indicator if we are viewing today's schedule
        if (viewDate !== today) {
            indicator.style.display = 'none';
            return;
        }

        const now = new Date();
        const timelineStartHour = 8;
        const timelineTotalMinutes = (18 - timelineStartHour) * 60;
        
        const currentMinutes = ((now.getHours() - timelineStartHour) * 60) + now.getMinutes();
        
        // Calculate position as a percentage
        let leftPercentage = (currentMinutes / timelineTotalMinutes) * 100;

        // Show indicator only during timeline hours (8am - 6pm)
        if (leftPercentage >= 0 && leftPercentage <= 100) {
            indicator.style.display = 'block';
            // Position relative to the timeline body, accounting for the agent name column
            indicator.style.left = `calc(150px + ${leftPercentage}%)`;
        } else {
            indicator.style.display = 'none';
        }
    }
    
    // Update the time indicator every minute
    updateTimeIndicator();
    setInterval(updateTimeIndicator, 60000); 
});
</script>

<?php include __DIR__ . "/../includes/footer.php"; ?>
