<?php 
session_start();
require 'config.php';

// log wela nattan login page ekatm yanwa 
if(!isset($_SESSION['user_id'])){
    header("Location: index.php");
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add'])){
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $registration_date = date('Y-m-d');
    $stmt = $conn->prepare("INSERT INTO members (full_name, email, phone, address, registration_date) VALUES (?,?,?,?,?)");
    $stmt->bind_param("sssss", $full_name, $email, $phone, $address, $registration_date);
    if($stmt->execute()){
        $_SESSION['message'] = "Member added successfully!";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Failed to add member. Please try again.";
        $_SESSION['msg_type'] = "danger";
    }
    header("Location: members.php");
    exit;
}

 
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit'])){
    $member_id = $_POST['member_id'];
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    // update query eka 
    $stmt = $conn->prepare("UPDATE members SET full_name=?, email=?, phone=?, address=? WHERE member_id=?");
    $stmt->bind_param("ssssi", $full_name, $email, $phone, $address, $member_id);
    if($stmt->execute()){
        $_SESSION['message'] = "Member updated successfully!";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['message'] = "Failed to update member. Please try again.";
        $_SESSION['msg_type'] = "danger";
    }
    header("Location: members.php");
    exit;
}


if(isset($_GET['delete'])){
    $member_id = intval($_GET['delete']);
    
    // Check if member has issued books (not returned yet)
    $check = $conn->query("SELECT COUNT(*) AS count FROM issue_books WHERE member_id = $member_id AND status = 'Issued'");
    $has_issued = $check->fetch_assoc()['count'] ?? 0;
    
    if($has_issued > 0){
        $_SESSION['message'] = "Cannot delete member. They have issued books that are not returned yet.";
        $_SESSION['msg_type'] = "danger";
    } else {
        if($conn->query("DELETE FROM members WHERE member_id = $member_id")){
            $_SESSION['message'] = "Member deleted successfully!";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['message'] = "Failed to delete member. Please try again.";
            $_SESSION['msg_type'] = "danger";
        }
    }
    header("Location: members.php");
    exit;
}

$members = $conn->query("SELECT * FROM members ORDER BY member_id DESC");

// Statistics
$total_members = $conn->query("SELECT COUNT(*) AS count FROM members")->fetch_assoc()['count'] ?? 0;
$current_month = date('Y-m');
$new_this_month = $conn->query("SELECT COUNT(*) AS count FROM members WHERE DATE_FORMAT(registration_date, '%Y-%m') = '$current_month'")->fetch_assoc()['count'] ?? 0;
// blnn on 
$active_members = $conn->query("SELECT COUNT(*) AS count FROM members WHERE registration_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch_assoc()['count'] ?? 0;
$with_addr = $conn->query("SELECT COUNT(*) AS count FROM members WHERE address IS NOT NULL AND address != ''")->fetch_assoc()['count'] ?? 0;

// =============================================
// GET SESSION MESSAGE & CLEAR
// =============================================
$message = $_SESSION['message'] ?? '';
$msg_type = $_SESSION['msg_type'] ?? 'info';
unset($_SESSION['message'], $_SESSION['msg_type']);
?>

<?php include 'header.php'; ?>

<style>
    /* Action buttons styling */
    .btn-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        border: none;
        background: transparent;
        color: #fff;
        font-size: 1rem;
        transition: all 0.25s ease-in-out;
        text-decoration: none;
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    }
    .btn-action i {
        font-size: 1rem;
        line-height: 1;
    }
    
    /* Edit button – YELLOW THEME */
    .btn-edit {
        background: #f59e0b;
        color: #fff;
    }
    .btn-edit:hover {
        background: #d97706;
        transform: scale(1.12);
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.5);
        color: #fff;
    }
    
    /* Delete button – Red */
    .btn-delete-action {
        background: #dc3545;
        color: #fff;
    }
    .btn-delete-action:hover {
        background: #bb2d3b;
        transform: scale(1.12);
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
        color: #fff;
    }
    
    .btn-action + .btn-action {
        margin-left: 6px;
    }
    .custom-table td .btn-action {
        margin: 2px 0;
    }

    .section-header-custom .header-stats {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }

    /* Statistics Cards - අවශ්‍ය නම් */
    .stat-card {
        background: #fafafa;
        border: 1px solid #f0f0f0;
        border-radius: 16px;
        padding: 20px;
        text-align: center;
        transition: all 0.2s ease;
    }
    .stat-card:hover {
        border-color: #dc2626;
        background: #ffffff;
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.05);
    }
    .stat-card i {
        color: #dc2626;
        font-size: 2.2rem;
    }
    .stat-card h6 {
        color: #64748b;
        margin-top: 10px;
        font-weight: 600;
    }
    .stat-card p {
        color: #0f172a;
        font-weight: 700;
        font-size: 1.8rem;
        margin-bottom: 0;
    }
</style>

<div class="container-fluid pt-4 pb-5">

    <!-- ========================================== -->
    <!-- ALERT MESSAGES (Success/Error/Update/Delete) -->
    <!-- ========================================== -->
    <?php if($message): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas <?= $msg_type == 'success' ? 'fa-check-circle' : ($msg_type == 'danger' ? 'fa-exclamation-circle' : 'fa-info-circle') ?> me-2"></i>
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ========================================== -->
    <!-- MEMBERS MANAGEMENT SECTION                -->
    <!-- ========================================== -->
    <div class="users-section">
        <div class="section-header-custom">
            <div>
                <h5 class="mb-0 fw-bold"><i class="fas fa-id-card me-2"></i>MEMBER MANAGEMENT</h5>
            </div>
            <div class="header-stats">
                <button class="btn btn-primary btn-sm rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="fas fa-plus me-1"></i> Add Member
                </button>
                <span class="badge bg-white text-danger px-3 py-2 rounded-pill fw-bold ms-2">
                    <?= $total_members ?> Records
                </span>
            </div>
        </div>

        <div class="table-responsive pb-4">
            <?php if ($members && $members->num_rows > 0): ?>
                <table class="table custom-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Member</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Registered</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $counter = 1; ?>
                        <?php while ($row = $members->fetch_assoc()): 
                            $firstLetter = strtoupper(substr($row['full_name'], 0, 1));
                        ?>
                            <tr>
                                <td class="ps-4"><span class="row-index"><?= sprintf("%02d", $counter++) ?></span></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar"><?= $firstLetter ?></div>
                                        <div>
                                            <div class="fw-bold text-dark" style="font-size: 0.95rem;"><?= htmlspecialchars($row['full_name']) ?></div>
                                            <div class="text-success small" style="font-size: 0.75rem;"><i class="fas fa-check-circle"></i> active</div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="text-muted fw-medium"><?= htmlspecialchars($row['email']) ?></span></td>
                                <td><span class="text-dark"><?= htmlspecialchars($row['phone']) ?></span></td>
                                <td><span class="text-muted small"><?= htmlspecialchars($row['address'] ?: '—') ?></span></td>
                                <td><span class="text-muted small"><?= date('M d, Y', strtotime($row['registration_date'])) ?></span></td>
                                <td class="text-center">
                                    <!-- Edit Button – Yellow -->
                                    <button class="btn-action btn-edit" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editModal<?= $row['member_id'] ?>" 
                                            title="Edit Member">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <!-- Delete Button – Red -->
                                    <a href="?delete=<?= $row['member_id'] ?>" 
                                       class="btn-action btn-delete-action" 
                                       onclick="return confirm('Delete this member?')" 
                                       title="Delete Member">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal<?= $row['member_id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow-lg">
                                        <div class="modal-header bg-danger text-white">
                                            <h5 class="modal-title fw-bold"><i class="fas fa-user-edit me-2"></i>Edit Member</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="post">
                                            <div class="modal-body">
                                                <input type="hidden" name="member_id" value="<?= $row['member_id'] ?>">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Full Name</label>
                                                    <input type="text" name="full_name" value="<?= htmlspecialchars($row['full_name']) ?>" class="form-control" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Email</label>
                                                    <input type="email" name="email" value="<?= htmlspecialchars($row['email']) ?>" class="form-control">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Phone</label>
                                                    <input type="text" name="phone" value="<?= htmlspecialchars($row['phone']) ?>" class="form-control">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Address</label>
                                                    <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($row['address']) ?></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" name="edit" class="btn btn-warning fw-bold"><i class="fas fa-save me-1"></i>Update</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-users-slash fa-4x text-danger opacity-25 mb-3"></i>
                    <h4 class="text-secondary fw-bold">No Members Found</h4>
                    <p class="text-muted">Click "Add Member" to create your first record.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- ADD MEMBER MODAL                          -->
<!-- ========================================== -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-plus me-2"></i>Add New Member</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Full Name</label>
                        <input type="text" name="full_name" placeholder="Enter full name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email</label>
                        <input type="email" name="email" placeholder="Enter email address" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Phone</label>
                        <input type="text" name="phone" placeholder="Enter phone number" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Address</label>
                        <textarea name="address" placeholder="Enter address" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add" class="btn btn-danger fw-bold"><i class="fas fa-plus me-1"></i>Add Member</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
