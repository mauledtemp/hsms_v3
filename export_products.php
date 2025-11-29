<?php
require_once 'config.php';
require_once 'functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$filename = exportProductsToCSV();

if ($filename && file_exists($filename)) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
    header('Content-Length: ' . filesize($filename));
    header('Cache-Control: max-age=0');
    
    readfile($filename);
    
    // Delete the file after download
    unlink($filename);
    exit;
} else {
    echo "Error generating export file.";
}
?>