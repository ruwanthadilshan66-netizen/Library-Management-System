<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Current Page එක හඳුනා ගැනීම
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library MS | Red & White</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <link rel="stylesheet" href="css/header.css" />
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<div class="sidebar" id="mainSidebar">
    
    <div class="sidebar-brand">
        <div class="icon-wrapper">
            <i class="fas fa-book-open"></i>
        </div>
    </div>

    <nav class="nav flex-column">
        
        <div class="nav-header"></div>
        <!-- Dashboard -->
        <a class="nav-link <?= ($current_page == 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php" data-tooltip="Dashboard">
            <i class="fas fa-compass"></i>
        </a>
      
        <div class="nav-header">-------------</div>
        <!-- Members -->
        <a class="nav-link <?= ($current_page == 'members.php') ? 'active' : ''; ?>" href="members.php" data-tooltip="Members">
            <i class="fas fa-id-card"></i>
        </a>

        <!-- Issue Books -->
        <a class="nav-link <?= ($current_page == 'issue_books.php') ? 'active' : ''; ?>" href="issue_books.php" data-tooltip="Issue Books">
            <i class="fas fa-hand-holding"></i>
        </a>

        <!-- Books -->
        <a class="nav-link <?= ($current_page == 'books.php') ? 'active' : ''; ?>" href="books.php" data-tooltip="Books">
            <i class="fas fa-book-open"></i>
        </a>

        <!-- Return Books -->
        <a class="nav-link <?= ($current_page == 'return_books.php') ? 'active' : ''; ?>" href="return_books.php" data-tooltip="Return Books">
            <i class="fas fa-arrow-rotate-left"></i>
        </a>
      
        <!-- Logout -->
        <a class="nav-link logout-link <?= ($current_page == 'logout.php') ? 'active' : ''; ?>" href="logout.php" data-tooltip="Logout">
            <i class="fas fa-sign-out-alt"></i>
        </a>

    </nav>
</div>

<div class="main-content">
