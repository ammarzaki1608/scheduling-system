<?php
// This partial contains the navigation links specific to the Agent role.
// It is included by header.php when an agent is logged in.
?>
<ul class="nav flex-column flex-grow-1">
    <li class="nav-item">
        <a class="nav-link <?= is_nav_active('dashboard.php') ?>" href="<?= BASE_URL ?>agent/dashboard.php">
            <i class="bi bi-person-workspace"></i>Dashboard
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= is_nav_active('appointments.php') ?>" href="<?= BASE_URL ?>agent/appointments.php">
            <i class="bi bi-calendar-week-fill"></i>My Appointments
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= is_nav_active('appointment_form.php') ?>" href="<?= BASE_URL ?>agent/appointment_form.php">
            <i class="bi bi-calendar-plus-fill"></i>Add Appointment
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= is_nav_active('profile.php') ?>" href="<?= BASE_URL ?>agent/profile.php">
            <i class="bi bi-person-circle"></i>My Profile
        </a>
    </li>
</ul>