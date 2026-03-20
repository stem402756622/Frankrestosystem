<?php
function sendEmail($to, $subject, $message) {
    // Basic mail configuration
    // Ideally use PHPMailer here if available
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    // Change this to a valid sender address
    $headers .= 'From: Frank Restaurant <no-reply@frankrestaurant.com>' . "\r\n";
    
    // Wrap message in a nice HTML template
    $htmlContent = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
            .header { background-color: #f8f9fa; padding: 10px; text-align: center; border-bottom: 1px solid #ddd; }
            .content { padding: 20px; }
            .footer { background-color: #f8f9fa; padding: 10px; text-align: center; font-size: 12px; color: #777; border-top: 1px solid #ddd; }
            .btn { display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Frank Restaurant</h2>
            </div>
            <div class='content'>
                $message
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " Frank Restaurant. All rights reserved.</p>
                <p>123 Main Street, Cityville</p>
            </div>
        </div>
    </body>
    </html>";
    
    // Try to send email
    // Note: mail() requires a configured SMTP server in php.ini
    return @mail($to, $subject, $htmlContent, $headers);
}
?>
