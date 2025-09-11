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

// --- Initialize variables ---
$errors = [];
$success = "";
$pageTitle = "Add Appointment";
$form_action = "add_appointment";
$agentId = (int) $_SESSION['user_id'];

// Appointment data placeholders
$appointment = [
    'Appointment_ID' => '',
    'Customer_Name' => '',
    'Case_Number' => '',
    'Subject' => '',
    'Start_At' => '',
    'End_At' => '',
    'Status' => 'Pending'
];

// --- Check if we are in EDIT mode ---
$appointment_id_from_url = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($appointment_id_from_url) {
    $pageTitle = "Edit Appointment";
    $form_action = "edit_appointment";
    
    // Fetch existing appointment data, ensuring it belongs to the current agent
    $stmt = $mysqli->prepare("SELECT * FROM Appointments WHERE Appointment_ID = ? AND Agent_ID = ?");
    $stmt->bind_param("ii", $appointment_id_from_url, $agentId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $appointment = $result->fetch_assoc();
    } else {
        // Redirect if appointment not found or doesn't belong to this agent
        header("Location: " . BASE_URL . "agent/appointments.php?error=notfound");
        exit;
    }
    $stmt->close();
}

// --- Handle Form Submission (Create/Update) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Overwrite placeholder data with submitted data
    $appointment['Customer_Name'] = trim($_POST['customer_name'] ?? '');
    $appointment['Case_Number'] = trim($_POST['case_number'] ?? '');
    $appointment['Subject'] = trim($_POST['subject'] ?? '');
    $appointment['Start_At'] = trim($_POST['start_at'] ?? '');
    $appointment['End_At'] = trim($_POST['end_at'] ?? '');
    $appointment['Status'] = trim($_POST['status'] ?? 'Pending');
    $appointment['Appointment_ID'] = filter_input(INPUT_POST, 'appointment_id', FILTER_VALIDATE_INT);
    $form_action = $_POST['action'] ?? 'add_appointment';

    // --- Validation ---
    if (empty($appointment['Customer_Name'])) $errors[] = "Customer Name is required.";
    if (empty($appointment['Subject'])) $errors[] = "Subject is required.";
    if (empty($appointment['Start_At']) || empty($appointment['End_At'])) $errors[] = "Both start and end times are required.";
    if (strtotime($appointment['End_At']) <= strtotime($appointment['Start_At'])) $errors[] = "End time must be after the start time.";

    if (empty($errors)) {
        // --- UPDATE ---
        if ($form_action === 'edit_appointment' && $appointment['Appointment_ID']) {
            $stmt = $mysqli->prepare("UPDATE Appointments SET Customer_Name=?, Case_Number=?, Subject=?, Start_At=?, End_At=?, Status=? WHERE Appointment_ID=? AND Agent_ID=?");
            $stmt->bind_param("ssssssii", $appointment['Customer_Name'], $appointment['Case_Number'], $appointment['Subject'], $appointment['Start_At'], $appointment['End_At'], $appointment['Status'], $appointment['Appointment_ID'], $agentId);
            if ($stmt->execute()) {
                header("Location: " . BASE_URL . "agent/appointments.php?success=updated");
                exit;
            } else {
                $errors[] = "Failed to update appointment.";
            }
        // --- CREATE ---
        } elseif ($form_action === 'add_appointment') {
            $stmt = $mysqli->prepare("INSERT INTO Appointments (Agent_ID, Customer_Name, Case_Number, Subject, Start_At, End_At, Status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssss", $agentId, $appointment['Customer_Name'], $appointment['Case_Number'], $appointment['Subject'], $appointment['Start_At'], $appointment['End_At'], $appointment['Status']);
            if ($stmt->execute()) {
                 header("Location: " . BASE_URL . "agent/appointments.php?success=created");
                exit;
            } else {
                $errors[] = "Failed to create appointment.";
            }
        }
        $stmt->close();
    }
}

include __DIR__ . "/../includes/header.php";
?>

<!-- Main Content Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><?= htmlspecialchars($pageTitle) ?></h1>
    <a href="<?= BASE_URL ?>agent/appointments.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back to Appointments
    </a>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    <?php foreach ($errors as $error) echo "<li>" . htmlspecialchars($error) . "</li>"; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="action" value="<?= htmlspecialchars($form_action) ?>">
            <input type="hidden" name="appointment_id" value="<?= htmlspecialchars($appointment['Appointment_ID']) ?>">
            
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="customerName" class="form-label">Customer Name</label>
                    <input type="text" class="form-control" id="customerName" name="customer_name" value="<?= htmlspecialchars($appointment['Customer_Name']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="caseNumber" class="form-label">Case Number (Optional)</label>
                    <input type="text" class="form-control" id="caseNumber" name="case_number" value="<?= htmlspecialchars($appointment['Case_Number']) ?>">
                </div>
                <div class="col-12">
                    <label for="subject" class="form-label">Subject</label>
                    <input type="text" class="form-control" id="subject" name="subject" value="<?= htmlspecialchars($appointment['Subject']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="startAt" class="form-label">Start Time</label>
                    <input type="datetime-local" class="form-control" id="startAt" name="start_at" value="<?= !empty($appointment['Start_At']) ? date('Y-m-d\TH:i', strtotime($appointment['Start_At'])) : '' ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="endAt" class="form-label">End Time</label>
                    <input type="datetime-local" class="form-control" id="endAt" name="end_at" value="<?= !empty($appointment['End_At']) ? date('Y-m-d\TH:i', strtotime($appointment['End_At'])) : '' ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="statusSelect" class="form-label">Status</label>
                    <select class="form-select" id="statusSelect" name="status" required>
                        <option value="Pending" <?= $appointment['Status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Completed" <?= $appointment['Status'] == 'Completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="Missed" <?= $appointment['Status'] == 'Missed' ? 'selected' : '' ?>>Missed</option>
                    </select>
                </div>
            </div>
            <div class="mt-4 text-end">
                <button type="submit" class="btn btn-primary">Save Appointment</button>
            </div>
        </form>
    </div>
</div>

<?php
include __DIR__ . "/../includes/footer.php";
?>
