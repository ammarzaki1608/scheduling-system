<?php
// This partial contains the navigation links specific to the Admin role.
// It is included by header.php when an admin is logged in.
?>
<ul class="nav flex-column flex-grow-1">
    <li class="nav-item">
        <a class="nav-link <?= is_nav_active('dashboard.php') ?>" href="<?= BASE_URL ?>admin/dashboard.php">
            <i class="bi bi-columns-gap"></i>Dashboard
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= is_nav_active('users.php') ?>" href="<?= BASE_URL ?>admin/users.php">
            <i class="bi bi-people-fill"></i>Manage Users
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= is_nav_active('appointments.php') ?>" href="<?= BASE_URL ?>admin/appointments.php">
            <i class="bi bi-calendar-check-fill"></i>Appointments
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= is_nav_active('teams.php') ?>" href="<?= BASE_URL ?>admin/teams.php">
            <i class="bi bi-diagram-3-fill"></i>Manage Teams
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= is_nav_active('reports.php') ?>" href="<?= BASE_URL ?>admin/reports.php">
            <i class="bi bi-file-earmark-bar-graph-fill"></i>Reports
        </a>
    </li>
</ul>
