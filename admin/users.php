<?php
/**
 * Admin - Registered users.
 *
 * Read-only directory of every account (name, username, email, role and
 * join date). Admin accounts are highlighted with a blue role badge.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_layout.php';
// All users, newest registrations first.
$users = admin_query($conn, 'SELECT user_id, full_name, username, email, role, created_at FROM users ORDER BY created_at DESC');
admin_header('Users', 'users');
?>

<!-- Registered users table -->
<section class="panel"><div class="panel-heading">
    <h2>Registered users</h2>
    <span class="muted"><?= $users ? $users->num_rows : 0 ?> accounts</span></div>
<?php 
if ($users && $users->num_rows > 0): ?>
<div class="table-wrap">
    <table class="data-table">
        <thead><tr>
            <th>Name</th>
            <th>Username</th>
            <th>Email</th>
            <th>Role</th>
            <th>Joined</th>
        </tr></thead>
        <tbody>
            <?php 
            // Render one row per user account.
            while ($user = $users->fetch_assoc()): ?>
            <tr><td>
                <strong><?= htmlspecialchars($user['full_name']) ?></strong>
            </td>
            <td>
                <?= htmlspecialchars($user['username']) ?>
            </td>
            <td><?= htmlspecialchars($user['email']) ?></td>
            <td>
                <span class="badge <?= $user['role'] === 'ADMIN' ? 'badge-blue' : 'badge-green' ?>"><?= htmlspecialchars($user['role']) ?>
            </span>
        </td><td>
            <?php /* Join date formatted as e.g. "09 Oct 2026". */ ?>
            <?= htmlspecialchars(date('d M Y', strtotime($user['created_at']))) ?>
        </td></tr>
        <?php endwhile; ?>
    </tbody></table>
</div>
<?php else: ?>
    <div class="empty-state">No users have registered yet.</div>
    <?php endif; ?>
</section>
<?php admin_footer(); ?>