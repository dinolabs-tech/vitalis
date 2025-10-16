<?php
// Import PHPMailer classes into the global namespace.
// These must be at the top of your script, not inside a function.
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Required files for PHPMailer.
// These files contain the core classes and functionalities for sending emails.
require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

// Start a new session or resume the existing session.
// This is used to store messages (success or error) that can be displayed on other pages.
session_start();

// Check if the 'message' field is set in the POST request.
// This ensures the script only runs when a form has been submitted.
if (isset($_POST["message"])) {
    // Retrieve user input from the POST request.
    $user_email = $_POST["email"];   // User's email address.
    $user_name = $_POST["name"];     // User's name.
    $user_subject = $_POST["subject"]; // User's message subject.
    $user_message = $_POST["message"]; // User's message content.

    try {
        // ========== First: Send the user's message to the administrator ==========
        // Create a new PHPMailer instance. The 'true' argument enables exceptions.
        $mailToAdmin = new PHPMailer(true);

        // Configure PHPMailer to use SMTP.
        $mailToAdmin->isSMTP();
        // Set the SMTP server host. This should be your mail server.
        $mailToAdmin->Host       = 'mail.dinolabstech.com'; // <-- change this to your actual SMTP host
        // Enable SMTP authentication.
        $mailToAdmin->SMTPAuth   = true;
        // SMTP username (your email address for sending).
        $mailToAdmin->Username   = 'enquiries@dinolabstech.com'; // <-- change this to your actual email
        // SMTP password for the above username.
        $mailToAdmin->Password   = 'Dinolabs@11';     // <-- change this to your actual email password
        // Enable TLS encryption. Use `PHPMailer::ENCRYPTION_SMTPS` for SSL on port 465.
        $mailToAdmin->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        // Set the SMTP port. 587 for STARTTLS, 465 for SMTPS.
        $mailToAdmin->Port       = 587;

        // Set the sender's email address and name for the email sent to the admin.
        $mailToAdmin->setFrom('enquiries@dinolabstech.com', 'Edupack Contact Form');
        // Add the recipient (your company's email address).
        $mailToAdmin->addAddress('dinolabs.tech@gmail.com'); // Company email address, either Gmail or webmail.
        // Set the reply-to address to the user's email, so you can reply directly to them.
        $mailToAdmin->addReplyTo($user_email, $user_name);

        // Set email format to HTML.
        $mailToAdmin->isHTML(true);
        // Set the subject line for the admin's email.
        $mailToAdmin->Subject = "New Contact Form Submission: {$user_subject} from $user_name";
        // Set the body of the email to the admin, including user's name, email, subject, and message.
        $mailToAdmin->Body = "
            <p><strong>Name:</strong> {$user_name}</p>
            <p><strong>Email:</strong> {$user_email}</p>
            <p><strong>Subject:</strong> {$user_subject}</p>
            <p><strong>Message:</strong><br>{$user_message}</p>
        ";
        // Send the email to the admin.
        $mailToAdmin->send();

        // ========== Second: Send a confirmation email to the user ==========
        // Create another PHPMailer instance for the user's confirmation email.
        $mailToUser = new PHPMailer(true);

        // Configure PHPMailer to use SMTP for the user's email.
        $mailToUser->isSMTP();
        // Set the SMTP server host.
        $mailToUser->Host       = 'mail.dinolabstech.com'; // <-- change this to your actual SMTP host
        // Enable SMTP authentication.
        $mailToUser->SMTPAuth   = true;
        // SMTP username (your email address for sending).
        $mailToUser->Username   = 'enquiries@dinolabstech.com'; // <-- change this to your actual email
        // SMTP password for the above username.
        $mailToUser->Password   = 'Dinolabs@11';     // <-- change this to your actual email password
        // Enable TLS encryption.
        $mailToUser->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        // Set the SMTP port.
        $mailToUser->Port       = 587;

        // Set the sender's email address and name for the email sent to the user.
        $mailToUser->setFrom('enquiries@dinolabstech.com', 'Dinolabs Tech Services');
        // Add the user's email address as the recipient.
        $mailToUser->addAddress($user_email);
        // Set the reply-to address to your company's email.
        $mailToUser->addReplyTo('enquiries@dinolabstech.com', 'Dinolabs Tech Services');

        // Set email format to HTML.
        $mailToUser->isHTML(true);
        // Set the subject line for the user's confirmation email.
        $mailToUser->Subject = 'Thank You for Reaching Out!';
        // Set the body of the email to the user, confirming receipt of their message.
        $mailToUser->Body = "
            <p>Dear $user_name,</p>
            <p>Thank You for Reaching Out to Dinolabs Tech Services! We appreciate the time you took to share your feedback and inquiries with us.</p>
            <p>Our team is reviewing your message and will respond shortly.</p>
            <p>Best regards,<br>Dinolabs Team</p>
        ";
        // Send the confirmation email to the user.
        $mailToUser->send();

        // ========== Success Handling ==========
        // If both emails are sent successfully, set a success message in the session.
        $_SESSION['success_message'] = "Message was sent successfully. Thank you for contacting us!";
        // Redirect the user back to the contact page.
        header("Location: contact.php");
        // Terminate script execution after redirection.
        exit();

    } catch (Exception $e) {
        // ========== Error Handling ==========
        // If an exception occurs during email sending, set an error message in the session.
        $_SESSION['error_message'] = "Message could not be sent. Mailer Error: {$mailToAdmin->ErrorInfo}";
        // Redirect the user back to the contact page.
        header("Location: contact.php");
        // Terminate script execution after redirection.
        exit();
    }
}
?>
