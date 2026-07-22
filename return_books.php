<?php 
session_start();
require 'config.php';

if(!isset($_SESSION['user_id'])){
    header("Location: index.php");
    exit;
}

// ---------- RETURN BOOK ----------
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['return'])){
    $issue_id = intval($_POST['issue_id']);
    $return_date = date('Y-m-d');
    $fine_amount = 0;
    
    $issue_query = $conn->query("SELECT * FROM issue_books WHERE issue_id = $issue_id AND status = 'Issued'");
    
    if($issue_query && $issue = $issue_query->fetch_assoc()){
        $due = new DateTime($issue['due_date']);
        $ret = new DateTime($return_date);
        
        if($ret > $due){
            $diff = $due->diff($ret)->days;
            $fine_amount = $diff * 50;
        }
        
        // ===== DEBUGGING: Display values =====
        echo "<div style='background: #f8d7da; padding: 15px; margin: 10px; border: 1px solid #f5c6cb;'>";
        echo "<h4>🔍 Debug Information</h4>";
        echo "Issue ID: " . $issue_id . "<br>";
        echo "Due Date: " . $issue['due_date'] . "<br>";
        echo "Return Date: " . $return_date . "<br>";
        echo "Fine Amount: " . $fine_amount . "<br>";
        echo "Book ID: " . $issue['book_id'] . "<br>";
        echo "</div>";
        // =====================================
        
        // 1. Update issue status
        $conn->query("UPDATE issue_books SET status = 'Returned' WHERE issue_id = $issue_id");
        
        // 2. Update available quantity
        $conn->query("UPDATE books SET available_quantity = available_quantity + 1 WHERE book_id = " . intval($issue['book_id']));
        
        // 3. Insert into return_books
        $stmt = $conn->prepare("INSERT INTO return_books (issue_id, return_date, fine_amount) VALUES (?, ?, ?)");
        $stmt->bind_param("isd", $issue_id, $return_date, $fine_amount);
        
       
    } else {
        $error = "Invalid issue record or book already returned.";
       
    }
}

// ---------- DATA QUERIES ----------
$returns = $conn->query("
    SELECT 
        r.return_id,
        r.return_date,
        r.fine_amount,
        i.issue_date,
        i.due_date,
        m.full_name,
        b.title
    FROM return_books r
    JOIN issue_books i ON r.issue_id = i.issue_id
    JOIN members m ON i.member_id = m.member_id
    JOIN books b ON i.book_id = b.book_id
    ORDER BY r.return_id DESC
");

$issued_list = $conn->query("
    SELECT 
        i.issue_id,
        m.full_name,
        b.title
    FROM issue_books i
    JOIN members m ON i.member_id = m.member_id
    JOIN books b ON i.book_id = b.book_id
    WHERE i.status = 'Issued'
");

// Statistics
$total_returns = $conn->query("SELECT COUNT(*) AS count FROM return_books")->fetch_assoc()['count'] ?? 0;
$total_fines = $conn->query("SELECT SUM(fine_amount) AS total FROM return_books")->fetch_assoc()['total'] ?? 0;
$overdue_returns = $conn->query("SELECT COUNT(*) AS count FROM return_books WHERE fine_amount > 0")->fetch_assoc()['count'] ?? 0;
$issued_count = $conn->query("SELECT COUNT(*) AS count FROM issue_books WHERE status = 'Issued'")->fetch_assoc()['count'] ?? 0;
?>

<?php include 'header.php'; ?>

<div class="container-fluid px-4 pt-4">
    <!-- Statistics Cards -->
    
    <!-- Return Management Section -->
    <div class="users-section">
        <div class="section-header-custom">
            <div>
                <h5 class="mb-0 fw-bold"><i class="fas fa-undo-alt me-2"></i>RETURN MANAGEMENT</h5>
            </div>
            <div class="header-stats">
                <button class="btn btn-success btn-sm rounded-pill px-4 fw-bold" 
                        data-bs-toggle="modal" 
                        data-bs-target="#returnModal" 
                        <?= ($issued_count == 0) ? 'disabled' : '' ?>>
                    <i class="fas fa-undo-alt me-1"></i> Return Book
                </button>
                <span class="badge bg-white text-danger px-3 py-2 rounded-pill fw-bold ms-2">
                    <?= $total_returns ?> Returns
                </span>
            </div>
        </div>

        <div class="table-responsive">
            <?php if ($returns && $returns->num_rows > 0): ?>
                <table class="table custom-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Member</th>
                            <th>Book Title</th>
                            <th>Issue Date</th>
                            <th>Due Date</th>
                            <th>Return Date</th>
                            <th>Fine (Rs.)</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $counter = 1; ?>
                        <?php while ($row = $returns->fetch_assoc()): 
                            $firstLetter = strtoupper(substr($row['full_name'], 0, 1));
                            $has_fine = ($row['fine_amount'] > 0);
                            $on_time = (strtotime($row['return_date']) <= strtotime($row['due_date']));
                        ?>
                            <tr>
                                <td class="ps-4"><span class="row-index"><?= sprintf("%02d", $counter++) ?></span></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar"><?= $firstLetter ?></div>
                                        <div>
                                            <div class="fw-bold text-dark" style="font-size: 0.95rem;"><?= htmlspecialchars($row['full_name']) ?></div>
                                            <div class="text-muted small" style="font-size: 0.75rem;">Return #<?= $row['return_id'] ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="fw-medium"><?= htmlspecialchars($row['title']) ?></span></td>
                                <td><span class="text-muted small"><?= date('M d, Y', strtotime($row['issue_date'])) ?></span></td>
                                <td><span class="text-muted small"><?= date('M d, Y', strtotime($row['due_date'])) ?></span></td>
                                <td><span class="text-muted small"><?= date('M d, Y', strtotime($row['return_date'])) ?></span></td>
                                <td>
                                    <?php if ($has_fine): ?>
                                        <span class="fw-bold text-danger">Rs. <?= number_format($row['fine_amount'], 2) ?></span>
                                    <?php else: ?>
                                        <span class="text-success">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $on_time ? 'bg-success' : 'bg-warning text-dark' ?> rounded-pill px-3 py-2">
                                        <?= $on_time ? 'On Time' : 'Overdue' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-undo-alt fa-4x text-danger opacity-25 mb-3"></i>
                    <h4 class="text-secondary fw-bold">No Return Records</h4>
                    <p class="text-muted">Return a book to start the history.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Return Book Modal -->
<div class="modal fade" id="returnModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-undo-alt me-2"></i>Return a Book</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <?php if(isset($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <?php if($issued_list->num_rows == 0): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-1"></i> No books are currently issued.
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Issued Book</label>
                        <select name="issue_id" class="form-select" required <?= ($issued_list->num_rows == 0) ? 'disabled' : '' ?>>
                            <option value="">Choose an issued book...</option>
                            <?php while($row = $issued_list->fetch_assoc()): ?>
                                <option value="<?= $row['issue_id'] ?>">
                                    <?= htmlspecialchars($row['full_name']) ?> - <?= htmlspecialchars($row['title']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="alert alert-info small">
                        <i class="fas fa-info-circle me-1"></i> 
                        <strong>Fine Policy:</strong> Rs. 50 per day for overdue books.
                        Fine will be calculated automatically.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="return" class="btn btn-success fw-bold" <?= ($issued_list->num_rows == 0) ? 'disabled' : '' ?>>
                        <i class="fas fa-check me-1"></i> Confirm Return
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
