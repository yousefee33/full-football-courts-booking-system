<?php
// Get all HTML files in the current directory
$html_files = glob("*.php");

foreach ($html_files as $html_file) {
    // Get the filename without extension
    $filename = pathinfo($html_file, PATHINFO_FILENAME);
    
    // Create new PHP filename
    $php_file = $filename . ".php";
    
    // Rename file
    if (rename($html_file, $php_file)) {
        echo "Renamed $html_file to $php_file successfully\n";
    } else {
        echo "Error renaming $html_file\n";
    }
}

echo "File renaming complete!\n";
?> 