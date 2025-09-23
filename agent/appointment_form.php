<?php
// --- Includes & Role Check ---
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/db_connect.php";
require_once __DIR__ . "/../includes/auth_check.php";
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'agent') {
    header("Location: " . BASE_URL . "auth/login.php?error=unauthorized");
    exit;
}

// --- Initialize variables ---
$errors = [];
$pageTitle = "Add Appointment";
$form_action = "add_appointment";
$agentId = (int) $_SESSION['user_id'];
$appointment = [
    'Appointment_ID' => '', 'Customer_Name' => '', 'Case_Number' => '', 'Subject' => '',
    'Start_At' => '', 'End_At' => '', 'Status' => 'Pending', 'Notes' => ''
];
$appointment_id_from_url = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($appointment_id_from_url) {
    $pageTitle = "Edit Appointment";
    $form_action = "edit_appointment";
    $stmt = $mysqli->prepare("SELECT * FROM Appointments WHERE Appointment_ID = ? AND Agent_ID = ?");
    $stmt->bind_param("ii", $appointment_id_from_url, $agentId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) { $appointment = $result->fetch_assoc(); } else {
        header("Location: " . BASE_URL . "agent/appointments.php?error=notfound"); exit;
    }
    $stmt->close();
}

// --- User-Friendly Timezone List ---
$common_timezones = [
    'Pacific/Midway'    => '(UTC-11:00) Midway Island, Samoa',
    'Pacific/Honolulu'  => '(UTC-10:00) Hawaii',
    'America/Los_Angeles' => '(UTC-08:00) Pacific Time (US & Canada)',
    'America/Denver'    => '(UTC-07:00) Mountain Time (US & Canada)',
    'America/Chicago'   => '(UTC-06:00) Central Time (US & Canada)',
    'America/New_York'  => '(UTC-05:00) Eastern Time (US & Canada)',
    'America/Sao_Paulo' => '(UTC-03:00) Brasilia, Buenos Aires',
    'Europe/London'     => '(UTC+00:00) London, Dublin, Lisbon (GMT)',
    'Europe/Paris'      => '(UTC+01:00) Amsterdam, Berlin, Paris, Rome',
    'Europe/Istanbul'   => '(UTC+03:00) Istanbul, Moscow, Nairobi',
    'Asia/Dubai'        => '(UTC+04:00) Abu Dhabi, Muscat',
    'Asia/Kolkata'      => '(UTC+05:30) New Delhi, Mumbai, Kolkata',
    'Asia/Dhaka'        => '(UTC+06:00) Almaty, Dhaka, Colombo',
    'Asia/Bangkok'      => '(UTC+07:00) Bangkok, Hanoi, Jakarta',
    'Asia/Kuala_Lumpur' => '(UTC+08:00) Kuala Lumpur, Singapore, Perth',
    'Asia/Tokyo'        => '(UTC+09:00) Tokyo, Seoul, Osaka',
    'Australia/Sydney'  => '(UTC+10:00) Sydney, Melbourne, Guam',
    'Pacific/Auckland'  => '(UTC+12:00) Auckland, Wellington, Fiji'
];

// --- Handle Form Submission (Create/Update) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment['Customer_Name'] = trim($_POST['customer_name'] ?? '');
    $appointment['Case_Number'] = trim($_POST['case_number'] ?? '');
    $appointment['Subject'] = trim($_POST['subject'] ?? '');
    $appointment['Start_At'] = trim($_POST['start_at'] ?? '');
    $appointment['End_At'] = trim($_POST['end_at'] ?? '');
    $appointment['Status'] = trim($_POST['status'] ?? 'Pending');
    $appointment['Notes'] = trim($_POST['notes'] ?? '');
    $appointment['Appointment_ID'] = filter_input(INPUT_POST, 'appointment_id', FILTER_VALIDATE_INT);
    $form_action = $_POST['action'] ?? 'add_appointment';
    $customer_timezone = trim($_POST['timezone'] ?? TIMEZONE);

    // Validation
    if (empty($appointment['Customer_Name'])) $errors[] = "Customer Name is required.";
    if (empty($appointment['Start_At']) || empty($appointment['End_At'])) $errors[] = "Both start and end times are required.";
    if (strtotime($appointment['End_At']) <= strtotime($appointment['Start_At'])) $errors[] = "End time must be after the start time.";
    if (!array_key_exists($customer_timezone, $common_timezones)) { $errors[] = "Invalid timezone selected."; }

    if (empty($errors)) {
        // --- Timezone Conversion Logic ---
        try {
            $malaysia_tz = new DateTimeZone(TIMEZONE);
            $customer_tz = new DateTimeZone($customer_timezone);
            
            $start_dt = new DateTime($appointment['Start_At'], $customer_tz);
            $start_dt->setTimezone($malaysia_tz);
            $db_start_at = $start_dt->format('Y-m-d H:i:s');

            $end_dt = new DateTime($appointment['End_At'], $customer_tz);
            $end_dt->setTimezone($malaysia_tz);
            $db_end_at = $end_dt->format('Y-m-d H:i:s');

        } catch (Exception $e) {
            $errors[] = "Invalid date/time format. Please use the picker.";
        }
        
        if (empty($errors)) {
            if ($form_action === 'edit_appointment' && $appointment['Appointment_ID']) {
                $stmt = $mysqli->prepare("UPDATE Appointments SET Customer_Name=?, Case_Number=?, Subject=?, Start_At=?, End_At=?, Status=?, Notes=? WHERE Appointment_ID=? AND Agent_ID=?");
                $stmt->bind_param("sssssssii", $appointment['Customer_Name'], $appointment['Case_Number'], $appointment['Subject'], $db_start_at, $db_end_at, $appointment['Status'], $appointment['Notes'], $appointment['Appointment_ID'], $agentId);
                if ($stmt->execute()) { header("Location: " . BASE_URL . "agent/appointments.php?success=updated"); exit; } else { $errors[] = "Failed to update appointment."; }
            } elseif ($form_action === 'add_appointment') {
                $stmt = $mysqli->prepare("INSERT INTO Appointments (Agent_ID, Customer_Name, Case_Number, Subject, Start_At, End_At, Status, Notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssssss", $agentId, $appointment['Customer_Name'], $appointment['Case_Number'], $appointment['Subject'], $db_start_at, $db_end_at, $appointment['Status'], $appointment['Notes']);
                if ($stmt->execute()) { header("Location: " . BASE_URL . "agent/appointments.php?success=created"); exit; } else { $errors[] = "Failed to create appointment."; }
            }
            $stmt->close();
        }
    }
}

include __DIR__ . "/../includes/header.php";
?>

<!-- Main Content Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><?= htmlspecialchars($pageTitle) ?></h1>
    <a href="<?= BASE_URL ?>agent/appointments.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to Appointments</a>
</div>

<div class="card">
    <div class="card-body p-4">
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" role="alert"><ul class="mb-0"><?php foreach ($errors as $error) echo "<li>" . htmlspecialchars($error) . "</li>"; ?></ul></div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="action" value="<?= htmlspecialchars($form_action) ?>">
            <input type="hidden" name="appointment_id" value="<?= htmlspecialchars($appointment['Appointment_ID']) ?>">
            
            <div class="row g-3">
                <div class="col-md-6"><label for="customerName" class="form-label">Customer Name</label><input type="text" class="form-control" id="customerName" name="customer_name" value="<?= htmlspecialchars($appointment['Customer_Name']) ?>" required></div>
                <div class="col-md-6"><label for="caseNumber" class="form-label">Case Number (Optional)</label><input type="text" class="form-control" id="caseNumber" name="case_number" value="<?= htmlspecialchars($appointment['Case_Number']) ?>"></div>
                <div class="col-12"><label for="subject" class="form-label">Subject</label><input type="text" class="form-control" id="subject" name="subject" value="<?= htmlspecialchars($appointment['Subject']) ?>" required></div>
                
                <div class="col-md-6">
                    <label for="timezone" class="form-label">Customer Timezone</label>
                    <select class="form-select" id="timezone" name="timezone">
                        <?php foreach($common_timezones as $identifier => $display_text): ?>
                            <option value="<?= htmlspecialchars($identifier) ?>" <?= ($identifier == TIMEZONE) ? 'selected' : '' ?>><?= htmlspecialchars($display_text) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                 <div class="col-md-6"><label for="statusSelect" class="form-label">Status</label><select class="form-select" id="statusSelect" name="status" required><option value="Pending" <?= $appointment['Status'] == 'Pending' ? 'selected' : '' ?>>Pending</option><option value="Completed" <?= $appointment['Status'] == 'Completed' ? 'selected' : '' ?>>Completed</option><option value="Missed" <?= $appointment['Status'] == 'Missed' ? 'selected' : '' ?>>Missed</option></select></div>

                <div class="col-md-6">
                    <label for="startAt" class="form-label">Start Time</label>
                    <input type="text" class="form-control" id="startAt" name="start_at" required placeholder="Select date and time...">
                    <small class="form-text text-success fw-bold" id="startAtConverted"></small>
                </div>
                <div class="col-md-6">
                    <label for="endAt" class="form-label">End Time</label>
                    <input type="text" class="form-control" id="endAt" name="end_at" required placeholder="Select date and time...">
                    <small class="form-text text-success fw-bold" id="endAtConverted"></small>
                </div>

                <div class="col-12"><label for="notes" class="form-label">Appointment Notes</label><textarea class="form-control" id="notes" name="notes" rows="4"><?= htmlspecialchars($appointment['Notes'] ?? '') ?></textarea></div>
            </div>
            <div class="mt-4 text-end">
                <button type="submit" class="btn btn-primary">Save Appointment</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const timezoneSelect = document.getElementById('timezone');
    const startAtInput = document.getElementById('startAt');
    const endAtInput = document.getElementById('endAt');
    const startAtConverted = document.getElementById('startAtConverted');
    const endAtConverted = document.getElementById('endAtConverted');
    const malaysiaTzIdentifier = '<?= TIMEZONE ?>';

    let startPicker, endPicker;

    function initializePickers() {
        const config = {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            time_24hr: true,
            altInput: true,
            altFormat: "M j, Y at h:i K",
            onChange: function(selectedDates, dateStr, instance) {
                updateConvertedTime(instance.element.id, dateStr);
            }
        };
        startPicker = flatpickr(startAtInput, config);
        endPicker = flatpickr(endAtInput, config);
    }

    function updateConvertedTime(elementId, dateString) {
        const customerTz = timezoneSelect.value;
        const displayElement = elementId === 'startAt' ? startAtConverted : endAtConverted;

        if (!dateString || customerTz === malaysiaTzIdentifier) {
            displayElement.textContent = '';
            return;
        }

        displayElement.textContent = 'Converting...';
        displayElement.className = 'form-text text-muted';

        // Use fetch to get the accurately converted time from the server
        fetch(`<?= BASE_URL ?>agent/ajax_convert_time.php?date=${encodeURIComponent(dateString)}&tz=${encodeURIComponent(customerTz)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayElement.textContent = `(Equals ${data.convertedTime} MYT)`;
                    displayElement.className = 'form-text text-success fw-bold';
                } else {
                    displayElement.textContent = `(Error: ${data.error || 'Invalid date'})`;
                    displayElement.className = 'form-text text-danger fw-bold';
                }
            })
            .catch(error => {
                console.error('Error fetching converted time:', error);
                displayElement.textContent = '(Conversion failed)';
                displayElement.className = 'form-text text-danger fw-bold';
            });
    }

    timezoneSelect.addEventListener('change', function() {
        updateConvertedTime('startAt', startPicker.input.value);
        updateConvertedTime('endAt', endPicker.input.value);
    });
    
    initializePickers();

    <?php if ($appointment_id_from_url && !empty($appointment['Start_At'])): ?>
        startPicker.setDate('<?= $appointment['Start_At'] ?>');
        endPicker.setDate('<?= $appointment['End_At'] ?>');
        timezoneSelect.value = malaysiaTzIdentifier;
    <?php endif; ?>
});
</script>

<?php
include __DIR__ . "/../includes/footer.php";
?>

