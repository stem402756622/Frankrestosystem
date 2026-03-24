<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

echo "<h2>Creating No-Shows Table</h2>";

try {
    // Check if no_shows table exists
    $no_shows_check = db()->fetchOne("SHOW TABLES LIKE 'no_shows'");
    
    if (!$no_shows_check) {
        echo "<h3>Creating no_shows table...</h3>";
        db()->execute("
            CREATE TABLE no_shows (
                id INT PRIMARY KEY AUTO_INCREMENT,
                reservation_id INT NOT NULL,
                customer_name VARCHAR(100) NOT NULL,
                customer_email VARCHAR(100) NOT NULL,
                customer_phone VARCHAR(20) NOT NULL,
                party_size INT NOT NULL,
                reservation_date DATE NOT NULL,
                reservation_time TIME NOT NULL,
                reason ENUM('No Contact','Late Cancellation','Emergency','Forgot','Weather','Transportation','Other') NOT NULL,
                action_taken ENUM('No Action','Warning Sent','Fee Charged','Blacklisted','Follow-up Required') NOT NULL,
                follow_up VARCHAR(255),
                notes TEXT,
                reported_by INT NOT NULL,
                reported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id) ON DELETE CASCADE,
                FOREIGN KEY (reported_by) REFERENCES users(user_id) ON DELETE SET NULL
            )
        ");
        echo "<p>✓ no_shows table created</p>";
    } else {
        echo "<p>✓ no_shows table already exists</p>";
    }
    
    // Verify table exists
    echo "<h3>Verifying table...</h3>";
    $noshow_count = db()->fetchOne("SELECT COUNT(*) as cnt FROM no_shows")['cnt'];
    echo "<p>No-shows table has $noshow_count records</p>";
    
    echo "<h3>✅ Success! No-shows table has been created.</h3>";
    echo "<p><a href='no_shows.php'>Go to No-Shows page</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database connection and permissions.</p>";
}
?>
