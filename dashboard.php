<?php 
session_start();
require 'config.php';


if(!isset($_SESSION['user_id'])){
    header("Location: index.php");
    exit;
}

if(isset($_GET['delete_user'])){
    $u_id = intval($_GET['delete_user']);
    if($u_id != $_SESSION['user_id']){
        $conn->query("DELETE FROM users WHERE user_id = $u_id");
    }
    header("Location: dashboard.php");
    exit;
}

$total_books = $conn->query("SELECT COUNT(*) AS count FROM books")->fetch_assoc()['count'] ?? 0;
$total_members = $conn->query("SELECT COUNT(*) AS count FROM members")->fetch_assoc()['count'] ?? 0;
$issued_books = $conn->query("SELECT COUNT(*) AS count FROM issue_books WHERE status='Issued'")->fetch_assoc()['count'] ?? 0;
$returned_books = $conn->query("SELECT COUNT(*) AS count FROM issue_books WHERE status='Returned'")->fetch_assoc()['count'] ?? 0;

$users_result = $conn->query("
    SELECT
        user_id,
        username,
        email,
        role,
        created_at
    FROM users
    ORDER BY user_id DESC
");?>

<?php include 'header.php'; ?>

<!-- Dashboard එකට අමතර CSS – Table එක පහළට ගෙනියන්න -->
<style>
   
</style>

<div class="container-fluid px-4">

    <!-- ========== STATISTICS CARDS ========== -->
    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon red-1"><i class="fas fa-book-open"></i></div>
            <div class="stat-label">Total Books</div>
            <div class="stat-number"><?= $total_books ?></div>
            <div class="stat-sub"><i class="fas fa-circle"></i> in library</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon red-2"><i class="fas fa-id-card"></i></div>
            <div class="stat-label">Total Members</div>
            <div class="stat-number"><?= $total_members ?></div>
            <div class="stat-sub"><i class="fas fa-circle"></i> active</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon red-3"><i class="fas fa-hand-holding"></i></div>
            <div class="stat-label">Issued Books</div>
            <div class="stat-number"><?= $issued_books ?></div>
            <div class="stat-sub"><i class="fas fa-circle" style="color:#f59e0b;"></i> currently out</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon red-4"><i class="fas fa-arrow-rotate-left"></i></div>
            <div class="stat-label">Returned Books</div>
            <div class="stat-number"><?= $returned_books ?></div>
            <div class="stat-sub"><i class="fas fa-circle" style="color:#22c55e;"></i> completed</div>
        </div>
    </div>

    <div class="users-section">
        <div class="section-header-custom">
            <div>
                <h5 class="mb-0 fw-bold"><i class="fas fa-user-shield me-2"></i>STAFF MANAGEMENT</h5>
            </div>
            <div class="header-stats">
                <span class="badge bg-white text-danger px-3 py-2 rounded-pill fw-bold">
                    <?= $users_result ? $users_result->num_rows : 0 ?> Registered Users
                </span>
            </div>
        </div>

        <div class="table-responsive">
            <?php if ($users_result && $users_result->num_rows > 0): ?>
                <table class="table custom-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>NO.</th>
                            <th>User Identity</th>
                            <th>Email Address</th>
                            <th>Access Level</th>
                            <th>Joined Date</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $counter = 1; ?>
                        <?php while ($user = $users_result->fetch_assoc()): 
                            $firstLetter = strtoupper(substr($user['username'], 0, 1));
                            $role = strtolower($user['role'] ?? 'member');
                        ?>
                            <tr>
                                <td class="ps-4"><span class="row-index"><?= sprintf("%02d", $counter++) ?></span></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar"><?= $firstLetter ?></div>
                                        <div>
                                            <div class="fw-bold text-dark" style="font-size: 0.95rem;"><?= htmlspecialchars($user['username']) ?></div>
                                            <div class="text-danger small" style="font-size: 0.75rem;">Verified User</div>
                                        </div>
                                    </div>
                                </td>
                                   <td>
    <span class="text-muted small">
        <?= htmlspecialchars($user['email']) ?>
    </span>
</td>            
                                                           <td>
                                    <span class="badge-role badge-<?= $role ?>">
                                        <?= ucfirst($role) ?>
                                    </span>
                                </td>
                                <td><span class="text-muted small"><?= date('M d, Y', strtotime($user['created_at'])) ?></span></td>
                                <td class="text-center">
                                    <?php if($user['user_id'] != $_SESSION['user_id']): ?>
                                        <a href="?delete_user=<?= $user['user_id'] ?>" class="btn-delete" onclick="return confirm('Delete this user?')">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted">You</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-users-slash fa-4x text-danger opacity-25 mb-3"></i>
                    <h4 class="text-secondary fw-bold">No Records</h4>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
