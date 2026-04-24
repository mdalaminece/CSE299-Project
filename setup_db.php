<?php
include 'db_connect.php';
mysqli_report(MYSQLI_REPORT_OFF);

// Import SQL dump file for the gym management system
$sql_file = __DIR__ . '/Database/gym_management.sql';

if (file_exists($sql_file)) {
    $sql_content = file_get_contents($sql_file);
    
    // Execute the queries
    if ($conn->multi_query($sql_content)) {
        do {
            // Free results to allow next query
            if ($res = $conn->store_result()) {
                $res->free();
            }
        } while ($conn->more_results() && $conn->next_result());
        
        if ($conn->errno) {
            echo "Error during import: " . $conn->error . "<br>";
        } else {
            echo "Database imported successfully from gym_management.sql.<br>";
        }
    } else {
        echo "Error importing database: " . $conn->error . "<br>";
    }
} else {
    echo "SQL dump file not found.<br>";
}

$conn->close();
echo "Database setup complete.";
?>
