<?php
// This script should be run by a cron job once a day or manually.
// It sends reminder emails for reservations scheduled for tomorrow.

require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/mailer.php';

echo "Running reservation reminders...\n";

try {
    // Find reservations for tomorrow that haven't been reminded yet
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    
    $reservations = db()->fetchAll(
        "SELECT r.*, u.email, u.full_name 
         FROM reservations r 
         JOIN users u ON r.user_id = u.user_id 
         WHERE r.reservation_date = ? 
         AND r.status IN ('confirmed', 'pending') 
         AND (r.is_reminded = 0 OR r.is_reminded IS NULL)",
        [$tomorrow]
    );

    echo "Found " . count($reservations) . " reservations for tomorrow ($tomorrow).\n";

    foreach ($reservations as $r) {
        $subject = "Reservation Reminder - Frank Restaurant";
        $message = "Dear " . htmlspecialchars($r['full_name']) . ",<br><br>";
        $message .= "This is a reminder for your upcoming reservation at Frank Restaurant.<br>";
        $message .= "<strong>Date:</strong> " . date('F j, Y', strtotime($r['reservation_date'])) . "<br>";
        $message .= "<strong>Time:</strong> " . date('g:i A', strtotime($r['reservation_time'])) . "<br>";
        $message .= "<strong>Party Size:</strong> " . $r['party_size'] . " people<br>";
        $message .= "<strong>Table:</strong> " . ($r['table_id'] ? "Reserved" : "To be assigned") . "<br><br>";
        $message .= "We look forward to seeing you tomorrow!";

        if (sendEmail($r['email'], $subject, $message)) {
            // Mark as reminded
            db()->execute("UPDATE reservations SET is_reminded = 1 WHERE reservation_id = ?", [$r['reservation_id']]);
            echo "Sent reminder to " . $r['email'] . "\n";
        } else {
            echo "Failed to send reminder to " . $r['email'] . "\n";
        }
    }

    echo "Done.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
