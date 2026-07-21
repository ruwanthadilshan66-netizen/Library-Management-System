<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'config.php';

if(!isset($_SESSION['user_id'])){
    header("Location: index.php");
    exit;
}


if(isset($_GET['return'])){
    $issue_id = intval($_GET['return']);
    
    // Get book_id, member_id, due_date from issue
    $book_query = $conn->query("SELECT book_id, member_id, due_date FROM issue_books WHERE issue_id = $issue_id AND status = 'Issued'");
    if($book_query && $row = $book_query->fetch_assoc()){
        $book_id = $row['book_id'];
        $member_id = $row['member_id'];
        $due_date = $row['due_date'];
        $return_date = date('Y-m-d');
        
        // Calculate fine if overdue
        $fine_amount = 0;
        $due = new DateTime($due_date);
        $ret = new DateTime($return_date);
        if($ret > $due){
            $diff = $due->diff($ret)->days;
            $fine_amount = $diff * 50;
        }
        
        // Get member name for success message
        $member = $conn->query("SELECT full_name FROM members WHERE member_id = $member_id")->fetch_assoc();
        $member_name = $member['full_name'] ?? 'Member';
        
        // 1. Update issue status
        if($conn->query("UPDATE issue_books SET status='Returned' WHERE issue_id = $issue_id")){
            // 2. Increase available quantity
            $conn->query("UPDATE books SET available_quantity = available_quantity + 1 WHERE book_id = $book_id");
            
            // 3. Insert into return_books
            $stmt = $conn->prepare("INSERT INTO return_books (issue_id, return_date, fine_amount) VALUES (?, ?, ?)");
            $stmt->bind_param("isd", $issue_id, $return_date, $fine_amount);
            if($stmt->execute()){
                $msg = "Book returned successfully by " . htmlspecialchars($member_name) . "!";
                if($fine_amount > 0){
                    $msg .= " Fine: Rs. " . number_format($fine_amount, 2);
                }
                $_SESSION['message'] = $msg;
                $_SESSION['msg_type'] = "success";
            } else {
                $_SESSION['message'] = "Failed to record return. Please try again.";
                $_SESSION['msg_type'] = "danger";
            }
        } else {
            $_SESSION['message'] = "Failed to return book. Please try again.";
            $_SESSION['msg_type'] = "danger";
        }
    } else {
        $_SESSION['message'] = "Invalid issue record or book already returned.";
        $_SESSION['msg_type'] = "danger";
    }
    header("Location: issue_books.php");
    exit;
}

// =============================================
// ISSUE BOOK – with Success/Error Message
// =============================================
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['issue'])){
    $member_id = $_POST['member_id'];
    $book_id = $_POST['book_id'];
    $issue_date = date('Y-m-d');
    $due_date = date('Y-m-d', strtotime('+1 day')); // 1 day for testing
    
    // Get member and book names for message
    $member = $conn->query("SELECT full_name FROM members WHERE member_id = $member_id")->fetch_assoc();
    $book = $conn->query("SELECT title FROM books WHERE book_id = $book_id")->fetch_assoc();
    $member_name = $member['full_name'] ?? 'Member';
    $book_title = $book['title'] ?? 'Book';
    
    $stmt = $conn->prepare("INSERT INTO issue_books (member_id, book_id, issue_date, due_date, status) VALUES (?,?,?,?, 'Issued')");
    $stmt->bind_param("iiss", $member_id, $book_id, $issue_date, $due_date);
    if($stmt->execute()){
        // Decrease available quantity
        $conn->query("UPDATE books SET available_quantity = available_quantity - 1 WHERE book_id = $book_id AND available_quantity > 0");
        $_SESSION['message'] = "Book '" . htmlspecialchars($book_title) . "' issued to " . htmlspecialchars($member_name) . " successfully!";
        $_SESSION['msg_type'] = "success";
        header("Location: issue_books.php");
        exit;
    } else {
        $_SESSION['message'] = "Failed to issue book. Check availability or try again.";
        $_SESSION['msg_type'] = "danger";
        header("Location: issue_books.php");
        exit;
    }
}

// =============================================
// DATA QUERIES
// =============================================
$issued = $conn->query("
    SELECT i.*, m.full_name, b.title 
    FROM issue_books i 
    JOIN members m ON i.member_id = m.member_id 
    JOIN books b ON i.book_id = b.book_id 
    WHERE i.status = 'Issued' 
    ORDER BY i.issue_id DESC
");

// For issuing modal
$members = $conn->query("SELECT member_id, full_name FROM members");
$books = $conn->query("SELECT book_id, title FROM books WHERE available_quantity > 0");

// Statistics
$total_issued = $conn->query("SELECT COUNT(*) AS count FROM issue_books WHERE status='Issued'")->fetch_assoc()['count'] ?? 0;
$overdue = $conn->query("SELECT COUNT(*) AS count FROM issue_books WHERE status='Issued' AND due_date < CURDATE()")->fetch_assoc()['count'] ?? 0;
$returned = $conn->query("SELECT COUNT(*) AS count FROM issue_books WHERE status='Returned'")->fetch_assoc()['count'] ?? 0;
$total_books = $conn->query("SELECT COUNT(*) AS count FROM books")->fetch_assoc()['count'] ?? 0;

// =============================================
// GET SESSION MESSAGE & CLEAR
// =============================================
$message = $_SESSION['message'] ?? '';
$msg_type = $_SESSION['msg_type'] ?? 'info';
unset($_SESSION['message'], $_SESSION['msg_type']);
?>

<?php include 'header.php'; ?>

<div class="container-fluid px-4 pt-4 pb-5">

    <!-- ========================================== -->
    <!-- ALERT MESSAGES (Success/Error)            -->
    <!-- ========================================== -->
    <?php if($message): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas <?= $msg_type == 'success' ? 'fa-check-circle' : ($msg_type == 'danger' ? 'fa-exclamation-circle' : 'fa-info-circle') ?> me-2"></i>
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ========================================== -->
    <!-- Issue Books Management Section              -->
    <!-- ========================================== -->
    <div class="users-section mt-5">
        <div class="section-header-custom">
            <div>
                <h5 class="mb-0 fw-bold"><i class="fas fa-book-open me-2"></i>ISSUE BOOKS MANAGEMENT</h5>
            </div>
            <div class="header-stats">
                <button class="btn btn-primary btn-sm rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#issueModal">
                    <i class="fas fa-plus me-1"></i> Issue Book
                </button>
                <span class="badge bg-white text-danger px-3 py-2 rounded-pill fw-bold ms-2">
                    <?= $total_issued ?> Issued
                </span>
                <?php if($overdue > 0): ?>
                    <span class="badge bg-danger px-3 py-2 rounded-pill fw-bold ms-1">
                        <?= $overdue ?> Overdue
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <div class="table-responsive pb-4">
            <?php if ($issued && $issued->num_rows > 0): ?>
                <table class="table custom-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Member</th>
                            <th>Book Title</th>
                            <th>Issue Date</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $counter = 1; ?>
                        <?php while ($row = $issued->fetch_assoc()): 
                            $firstLetter = strtoupper(substr($row['full_name'], 0, 1));
                            $is_overdue = (strtotime($row['due_date']) < time());
                            $status_class = $is_overdue ? 'text-danger' : 'text-success';
                        ?>
                            <tr>
                                <td class="ps-4"><span class="row-index"><?= sprintf("%02d", $counter++) ?></span></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar"><?= $firstLetter ?></div>
                                        <div>
                                            <div class="fw-bold text-dark" style="font-size: 0.95rem;"><?= htmlspecialchars($row['full_name']) ?></div>
                                            <div class="text-muted small" style="font-size: 0.75rem;">ID: <?= $row['member_id'] ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="fw-medium"><?= htmlspecialchars($row['title']) ?></span></td>
                                <td><span class="text-muted small"><?= date('M d, Y', strtotime($row['issue_date'])) ?></span></td>
                                <td>
                                    <span class="small <?= $status_class ?>">
                                        <?= date('M d, Y', strtotime($row['due_date'])) ?>
                                        <?php if ($is_overdue): ?>
                                            <i class="fas fa-exclamation-circle ms-1" title="Overdue"></i>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= $is_overdue ? 'bg-danger' : 'bg-success' ?> rounded-pill px-3 py-2">
                                        <?= $is_overdue ? 'Overdue' : 'Issued' ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <!-- Return Button -->
                                    <a href="?return=<?= $row['issue_id'] ?>" 
                                       class="btn-return" 
                                       onclick="return confirm('Return this book?')" 
                                       title="Return Book">
                                        <i class="fas fa-undo-alt"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-book-open fa-4x text-danger opacity-25 mb-3"></i>
                    <h4 class="text-secondary fw-bold">No Issued Books</h4>
                    <p class="text-muted">Click "Issue Book" to lend a book to a member.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- Issue Book Modal                           -->
<!-- ========================================== -->
<div class="modal fade" id="issueModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-hand-holding me-2"></i>Issue a Book</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <?php if(isset($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Member</label>
                        <select name="member_id" class="form-select" required>
                            <option value="">Choose a member...</option>
                            <?php while($m = $members->fetch_assoc()): ?>
                                <option value="<?= $m['member_id'] ?>"><?= htmlspecialchars($m['full_name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Book</label>
                        <select name="book_id" class="form-select" required>
                            <option value="">Choose a book...</option>
                            <?php while($b = $books->fetch_assoc()): ?>
                                <option value="<?= $b['book_id'] ?>"><?= htmlspecialchars($b['title']) ?></option>
                            <?php endwhile; ?>
                        </select>
                        <?php if($books->num_rows == 0): ?>
                            <div class="text-warning small mt-1">No books available at the moment.</div>
                        <?php endif; ?>
                    </div>
                    <div class="alert alert-info small">
                        <i class="fas fa-info-circle me-1"></i> Due date will be set to <strong>1 day</strong> from today.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="issue" class="btn btn-danger fw-bold" <?= ($books->num_rows == 0) ? 'disabled' : '' ?>>
                        <i class="fas fa-check me-1"></i> Issue Book
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- CSS – Return Button Styles                -->
<!-- ========================================== -->
<style>
    /* Return Button – Green Gradient with Animation */
    .btn-return {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: #fff;
        font-size: 1.1rem;
        text-decoration: none;
        transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        box-shadow: 0 4px 10px rgba(34, 197, 94, 0.3);
        position: relative;
        animation: pulse-return 2s infinite;
    }
    .btn-return:hover {
        transform: scale(1.25) rotate(10deg);
        box-shadow: 0 8px 25px rgba(34, 197, 94, 0.6);
        color: #fff;
        background: linear-gradient(135deg, #16a34a, #15803d);
    }
    .btn-return:active {
        transform: scale(0.95);
    }
    .btn-return i {
        transition: transform 0.3s ease;
    }
    .btn-return:hover i {
        transform: rotate(-30deg);
    }
    @keyframes pulse-return {
        0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(34, 197, 94, 0); }
        100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
    }
    .btn-return.disabled {
        opacity: 0.5;
        pointer-events: none;
        animation: none;
    }
    .custom-table tbody tr:hover .btn-return {
        animation-play-state: running;
    }
</style>

<?php include 'footer.php'; ?>