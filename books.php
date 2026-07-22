<?php 
session_start();
require 'config.php';

if(!isset($_SESSION['user_id'])){
    header("Location: index.php");
    exit;
}


if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add'])){
    $title = $_POST['title'];
    $author = $_POST['author'];
    $category = $_POST['category'];
    $publisher = $_POST['publisher'];
    $publication_year = $_POST['publication_year'];
    $isbn = $_POST['isbn'];
    $quantity = $_POST['quantity'];
    $available_quantity = $quantity;
    $stmt = $conn->prepare("INSERT INTO books (title, author, category, publisher, publication_year, isbn, quantity, available_quantity) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->bind_param("ssssssii", $title, $author, $category, $publisher, $publication_year, $isbn, $quantity, $available_quantity);
    $stmt->execute();
    $_SESSION['success'] = "Book added successfully.";
    header("Location: books.php");
    exit;
}

// UPDATE BOOK
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit'])){
    $book_id = $_POST['book_id'];
    $title = $_POST['title'];
    $author = $_POST['author'];
    $category = $_POST['category'];
    $publisher = $_POST['publisher'];
    $publication_year = $_POST['publication_year'];
    $isbn = $_POST['isbn'];
    $quantity = $_POST['quantity'];
    $stmt = $conn->prepare("UPDATE books SET title=?, author=?, category=?, publisher=?, publication_year=?, isbn=?, quantity=? WHERE book_id=?");
    $stmt->bind_param("ssssssii", $title, $author, $category, $publisher, $publication_year, $isbn, $quantity, $book_id);
    $stmt->execute();
    $_SESSION['success'] = "Book updated successfully.";
    header("Location: books.php");
    exit;
}


if(isset($_GET['delete'])){
    $book_id = intval($_GET['delete']);
    
    // මෙම පොත Issue කර තිබේදැයි පරීක්ෂා කරන්න
    $check_query = $conn->query("SELECT COUNT(*) AS count FROM issue_books WHERE book_id = $book_id AND status = 'Issued'");
    $check = $check_query->fetch_assoc();
    
    if($check['count'] > 0){
        // Issue කර ඇති පොතක් නිසා Error Message එක Set කරන්න
        $_SESSION['error'] = "Cannot delete this book because it is currently issued to a member. Please return the book first.";
    } else {
        // Issue කර නැති නම් Delete කරන්න
        $conn->query("DELETE FROM books WHERE book_id = $book_id");
        $_SESSION['success'] = "Book deleted successfully.";
    }
    
    header("Location: books.php");
    exit;
}


$books = $conn->query("SELECT * FROM books ORDER BY book_id DESC");


$total_books = $conn->query("SELECT COUNT(*) AS count FROM books")->fetch_assoc()['count'] ?? 0;
$total_available = $conn->query("SELECT SUM(available_quantity) AS total FROM books")->fetch_assoc()['total'] ?? 0;
$total_quantity = $conn->query("SELECT SUM(quantity) AS total FROM books")->fetch_assoc()['total'] ?? 0;
$categories = $conn->query("SELECT COUNT(DISTINCT category) AS count FROM books")->fetch_assoc()['count'] ?? 0;
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

    /* Book Icon */
    .book-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #e8f0fe;
        color: #1a73e8;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1rem;
        margin-right: 12px;
        flex-shrink: 0;
    }
    /* Available quantity badges */
    .badge.bg-warning.text-dark {
        background-color: #f59e0b !important;
        color: #1a1a2e !important;
    }
</style>

<div class="container-fluid px-4 pt-4 pb-5">

    <!-- Display Success/Error Messages -->
    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?= $_SESSION['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?= $_SESSION['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <!-- ========================================== -->
    <!-- Books Management Section                   -->
    <!-- ========================================== -->
    <div class="users-section">
        <div class="section-header-custom">
            <div>
                <h5 class="mb-0 fw-bold"><i class="fas fa-book-open me-2"></i>BOOKS MANAGEMENT</h5>
            </div>
            <div class="header-stats">
                <button class="btn btn-primary btn-sm rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="fas fa-plus me-1"></i> Add Book
                </button>
                <span class="badge bg-white text-danger px-3 py-2 rounded-pill fw-bold ms-2">
                    <?= $total_books ?> Records
                </span>
            </div>
        </div>

        <div class="table-responsive pb-4">
            <?php if ($books && $books->num_rows > 0): ?>
                <table class="table custom-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Category</th>
                            <th>Publisher</th>
                            <th>Year</th>
                            <th>ISBN</th>
                            <th class="text-center">Qty</th>
                            <th class="text-center">Available</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $counter = 1; ?>
                        <?php while ($row = $books->fetch_assoc()): 
                            $firstLetter = strtoupper(substr($row['title'], 0, 1));
                            $low_stock = ($row['available_quantity'] <= 2 && $row['available_quantity'] > 0);
                            $out_of_stock = ($row['available_quantity'] == 0);
                        ?>
                            <tr>
                                <td class="ps-4"><span class="row-index"><?= sprintf("%02d", $counter++) ?></span></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="book-icon"><?= $firstLetter ?></div>
                                        <div>
                                            <div class="fw-bold text-dark" style="font-size: 0.95rem;"><?= htmlspecialchars($row['title']) ?></div>
                                            <div class="text-muted small" style="font-size: 0.75rem;">ID: <?= $row['book_id'] ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="fw-medium"><?= htmlspecialchars($row['author']) ?></span></td>
                                <td><span class="badge bg-info bg-opacity-10 text-info rounded-pill px-3 py-2"><?= htmlspecialchars($row['category'] ?: '—') ?></span></td>
                                <td><span class="text-muted small"><?= htmlspecialchars($row['publisher'] ?: '—') ?></span></td>
                                <td><span class="text-muted small"><?= htmlspecialchars($row['publication_year'] ?: '—') ?></span></td>
                                <td><span class="text-muted small"><?= htmlspecialchars($row['isbn'] ?: '—') ?></span></td>
                                <td class="text-center"><span class="fw-bold"><?= $row['quantity'] ?></span></td>
                                <td class="text-center">
                                    <?php if ($out_of_stock): ?>
                                        <span class="badge bg-danger rounded-pill px-3 py-2">0</span>
                                    <?php elseif ($low_stock): ?>
                                        <span class="badge bg-warning text-dark rounded-pill px-3 py-2"><?= $row['available_quantity'] ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-success rounded-pill px-3 py-2"><?= $row['available_quantity'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <!-- Edit Button – Yellow -->
                                    <button class="btn-action btn-edit" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editModal<?= $row['book_id'] ?>" 
                                            title="Edit Book">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <!-- Delete Button – Red -->
                                    <a href="?delete=<?= $row['book_id'] ?>" 
                                       class="btn-action btn-delete-action" 
                                       onclick="return confirm('Delete this book?')" 
                                       title="Delete Book">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal<?= $row['book_id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow-lg">
                                        <div class="modal-header bg-danger text-white">
                                            <h5 class="modal-title fw-bold"><i class="fas fa-book me-2"></i>Edit Book</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="post">
                                            <div class="modal-body">
                                                <input type="hidden" name="book_id" value="<?= $row['book_id'] ?>">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Title</label>
                                                    <input type="text" name="title" value="<?= htmlspecialchars($row['title']) ?>" class="form-control" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Author</label>
                                                    <input type="text" name="author" value="<?= htmlspecialchars($row['author']) ?>" class="form-control" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Category</label>
                                                    <input type="text" name="category" value="<?= htmlspecialchars($row['category']) ?>" class="form-control">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Publisher</label>
                                                    <input type="text" name="publisher" value="<?= htmlspecialchars($row['publisher']) ?>" class="form-control">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Publication Year</label>
                                                    <input type="number" name="publication_year" value="<?= htmlspecialchars($row['publication_year']) ?>" class="form-control">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">ISBN</label>
                                                    <input type="text" name="isbn" value="<?= htmlspecialchars($row['isbn']) ?>" class="form-control">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Quantity</label>
                                                    <input type="number" name="quantity" value="<?= htmlspecialchars($row['quantity']) ?>" class="form-control" required>
                                                    <div class="text-muted small mt-1">Note: Available quantity will be updated automatically.</div>
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
                    <i class="fas fa-book fa-4x text-danger opacity-25 mb-3"></i>
                    <h4 class="text-secondary fw-bold">No Books Found</h4>
                    <p class="text-muted">Click "Add Book" to add your first book to the library.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- Add Book Modal                             -->
<!-- ========================================== -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle me-2"></i>Add New Book</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Title</label>
                        <input type="text" name="title" placeholder="Enter book title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Author</label>
                        <input type="text" name="author" placeholder="Enter author name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Category</label>
                        <input type="text" name="category" placeholder="e.g. Fiction, Science, History" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Publisher</label>
                        <input type="text" name="publisher" placeholder="Enter publisher name" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Publication Year</label>
                        <input type="number" name="publication_year" placeholder="e.g. 2024" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ISBN</label>
                        <input type="text" name="isbn" placeholder="Enter ISBN number" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Quantity</label>
                        <input type="number" name="quantity" placeholder="Number of copies" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add" class="btn btn-danger fw-bold"><i class="fas fa-plus me-1"></i>Add Book</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
