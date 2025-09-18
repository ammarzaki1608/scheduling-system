<ul class="nav flex-column flex-grow-1">
    <li class="nav-item">
        <a class="nav-link <?= is_nav_active('dashboard.php') ?>" href="<?= BASE_URL ?>admin/dashboard.php">
            <i class="bi bi-columns-gap"></i><span>Dashboard</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= is_nav_active('users.php') ?>" href="<?= BASE_URL ?>admin/users.php">
            <i class="bi bi-people-fill"></i><span>Manage Users</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= is_nav_active('appointments.php') ?>" href="<?= BASE_URL ?>admin/appointments.php">
            <i class="bi bi-calendar-check-fill"></i><span>Appointments</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= is_nav_active('teams.php') ?>" href="<?= BASE_URL ?>admin/teams.php">
            <i class="bi bi-diagram-3-fill"></i><span>Manage Teams</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= is_nav_active('pods.php') ?>" href="<?= BASE_URL ?>admin/pods.php">
            <i class="bi bi-hdd-stack-fill"></i><span>Manage Pods</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= is_nav_active('reports.php') ?>" href="<?= BASE_URL ?>admin/reports.php">
            <i class="bi bi-file-earmark-bar-graph-fill"></i><span>Reports</span>
        </a>
    </li>
</ul>

