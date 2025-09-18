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

// --- All the existing PHP logic for handling forms and fetching data remains here ---
$errors = []; $success = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $agent_id = filter_input(INPUT_POST, 'agent_id', FILTER_VALIDATE_INT);
    $customer_name = trim($_POST['customer_name'] ?? '');
    $case_number = trim($_POST['case_number'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $start_at = trim($_POST['start_at'] ?? '');
    $end_at = trim($_POST['end_at'] ?? '');
    $status = trim($_POST['status'] ?? 'Pending');
    $appointment_id = filter_input(INPUT_POST, 'appointment_id', FILTER_VALIDATE_INT);
    if (empty($agent_id)) $errors[] = "An agent must be selected.";
    if (empty($customer_name)) $errors[] = "Customer Name is required.";
    if (empty($subject)) $errors[] = "Subject is required.";
    if (empty($start_at) || empty($end_at)) $errors[] = "Both start and end times are required.";
    if (strtotime($end_at) <= strtotime($start_at)) $errors[] = "End time must be after the start time.";
    if (empty($errors)) {
        if ($_POST['action'] === 'edit_appointment' && $appointment_id) {
            $stmt = $mysqli->prepare("UPDATE Appointments SET Agent_ID=?, Customer_Name=?, Case_Number=?, Subject=?, Start_At=?, End_At=?, Status=? WHERE Appointment_ID=?");
            $stmt->bind_param("issssssi", $agent_id, $customer_name, $case_number, $subject, $start_at, $end_at, $status, $appointment_id);
            if ($stmt->execute()) { $success = "Appointment updated successfully!"; } else { $errors[] = "Failed to update appointment."; }
            $stmt->close();
        } elseif ($_POST['action'] === 'add_appointment') {
            $stmt = $mysqli->prepare("INSERT INTO Appointments (Agent_ID, Customer_Name, Case_Number, Subject, Start_At, End_At, Status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssss", $agent_id, $customer_name, $case_number, $subject, $start_at, $end_at, $status);
            if ($stmt->execute()) { $success = "Appointment created successfully!"; } else { $errors[] = "Failed to create appointment."; }
            $stmt->close();
        }
    }
}
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $appointment_id_to_delete = (int)$_GET['id'];
    $stmt = $mysqli->prepare("DELETE FROM Appointments WHERE Appointment_ID = ?");
    $stmt->bind_param("i", $appointment_id_to_delete);
    $stmt->execute(); $stmt->close();
    header("Location: " . BASE_URL . "admin/appointments.php?deleted=1"); exit;
}
if(isset($_GET['deleted'])) $success = "Appointment deleted successfully.";
$agents = $mysqli->query("SELECT User_ID, User_Name FROM Users WHERE Role = 'agent' ORDER BY User_Name ASC")->fetch_all(MYSQLI_ASSOC);
$appointments_sql = "SELECT a.*, u.User_Name as Agent_Name FROM Appointments a JOIN Users u ON a.Agent_ID = u.User_ID ORDER BY a.Start_At DESC";
$appointments = $mysqli->query($appointments_sql)->fetch_all(MYSQLI_ASSOC);

// --- Page Setup ---
$pageTitle = "Manage Appointments";
include __DIR__ . "/../includes/header.php";
?>

<!-- Add/Edit Appointment Modal -->
<div class="modal fade" id="appointmentModal" tabindex="-1" aria-labelledby="appointmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="appointmentModalLabel">Add New Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add_appointment">
                    <input type="hidden" name="appointment_id" id="appointmentId" value="">
                    <div class="row g-3">
                        <div class="col-md-6"><label for="agentSelect" class="form-label">Assign to Agent</label><select class="form-select" id="agentSelect" name="agent_id" required><option value="">-- Select an Agent --</option><?php foreach ($agents as $agent): ?><option value="<?= $agent['User_ID'] ?>"><?= htmlspecialchars($agent['User_Name']) ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-6"><label for="customerName" class="form-label">Customer Name</label><input type="text" class="form-control" id="customerName" name="customer_name" required></div>
                        <div class="col-md-6"><label for="caseNumber" class="form-label">Case Number (Optional)</label><input type="text" class="form-control" id="caseNumber" name="case_number"></div>
                        <div class="col-md-6"><label for="statusSelect" class="form-label">Status</label><select class="form-select" id="statusSelect" name="status" required><option value="Pending">Pending</option><option value="Completed">Completed</option><option value="Missed">Missed</option></select></div>
                        <div class="col-12"><label for="subject" class="form-label">Subject</label><input type="text" class="form-control" id="subject" name="subject" required></div>
                        <div class="col-md-6"><label for="startAt" class="form-label">Start Time</label><input type="datetime-local" class="form-control" id="startAt" name="start_at" required></div>
                        <div class="col-md-6"><label for="endAt" class="form-label">End Time</label><input type="datetime-local" class="form-control" id="endAt" name="end_at" required></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Appointment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Main Content Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Manage Appointments</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#appointmentModal" id="addAppointmentBtn">
        <i class="bi bi-plus-lg"></i> Add New Appointment
    </button>
</div>

<!-- Display success or error messages -->
<?php if (!empty($success)): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?= htmlspecialchars($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
<?php if (!empty($errors)): ?><div class="alert alert-danger" role="alert"><ul class="mb-0"><?php foreach ($errors as $error) echo "<li>" . htmlspecialchars($error) . "</li>"; ?></ul></div><?php endif; ?>

<!-- Appointments Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Customer & Case</th>
                        <th>Appointment Details</th>
                        <th>Scheduled Date</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($appointments)): ?>
                        <tr><td colspan="5" class="text-center text-muted p-4">No appointments found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($appointments as $appt): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($appt['Customer_Name']) ?></strong>
                                    <small class="d-block text-muted">Case: <?= htmlspecialchars($appt['Case_Number'] ?: 'N/A') ?></small>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($appt['Subject']) ?></strong>
                                    <small class="d-block text-muted">Agent: <?= htmlspecialchars($appt['Agent_Name']) ?></small>
                                </td>
                                <td>
                                    <strong><?= date('M j, Y', strtotime($appt['Start_At'])) ?></strong>
                                    <small class="d-block text-muted"><?= date('g:i A', strtotime($appt['Start_At'])) ?> - <?= date('g:i A', strtotime($appt['End_At'])) ?></small>
                                </td>
                                <td>
                                    <?php 
                                        $status = htmlspecialchars($appt['Status']);
                                        $status_class = 'bg-secondary';
                                        if ($status === 'Completed') $status_class = 'bg-success';
                                        if ($status === 'Missed') $status_class = 'bg-danger';
                                    ?>
                                    <span class="badge rounded-pill <?= $status_class ?>"><?= $status ?></span>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-light border edit-btn" data-bs-toggle="modal" data-bs-target="#appointmentModal"
                                        data-appointment='<?= htmlspecialchars(json_encode($appt), ENT_QUOTES, 'UTF-8') ?>'>
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <a href="?action=delete&id=<?= $appt['Appointment_ID'] ?>" class="btn btn-sm btn-light border" onclick="return confirm('Are you sure you want to delete this appointment?');">
                                        <i class="bi bi-trash3-fill"></i>
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
    const appointmentModal = document.getElementById('appointmentModal');
    const modalTitle = document.getElementById('appointmentModalLabel');
    const formAction = document.getElementById('formAction');
    const appointmentIdInput = document.getElementById('appointmentId');
    const agentSelect = document.getElementById('agentSelect');
    const customerNameInput = document.getElementById('customerName');
    const caseNumberInput = document.getElementById('caseNumber');
    const subjectInput = document.getElementById('subject');
    const startAtInput = document.getElementById('startAt');
    const endAtInput = document.getElementById('endAt');
    const statusSelect = document.getElementById('statusSelect');

    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function () {
            const apptData = JSON.parse(this.dataset.appointment);
            modalTitle.textContent = 'Edit Appointment';
            formAction.value = 'edit_appointment';
            appointmentIdInput.value = apptData.Appointment_ID;
            agentSelect.value = apptData.Agent_ID;
            customerNameInput.value = apptData.Customer_Name;
            caseNumberInput.value = apptData.Case_Number;
            subjectInput.value = apptData.Subject;
            startAtInput.value = apptData.Start_At.slice(0, 16);
            endAtInput.value = apptData.End_At.slice(0, 16);
            statusSelect.value = apptData.Status;
        });
    });

    document.getElementById('addAppointmentBtn').addEventListener('click', function () {
        modalTitle.textContent = 'Add New Appointment';
        formAction.value = 'add_appointment';
        document.querySelector('#appointmentModal form').reset();
        appointmentIdInput.value = '';
    });
});
</script>

<?php
include __DIR__ . "/../includes/footer.php";
?>

