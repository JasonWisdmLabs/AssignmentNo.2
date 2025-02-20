<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include "databaseConnection.php";

$message = "";
$otpSent = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['send_code'])) {
        $email = $_POST['email'];
        
        // Checking if user exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id);
            $stmt->fetch();
            
            // Generate OTP
            $otp = rand(100000, 999999);
            
            // Store the OTP in Database
            $stmt = $conn->prepare("UPDATE users SET reset_code = ? WHERE email = ?");
            $stmt->bind_param("ss", $otp, $email);
            $stmt->execute();
            
            // Send Email
            $subject = "Password Reset Code";
            $message_body = "Your OTP code is: $otp";
            $headers = "From: dsajason2002@gmail.com";

            if (mail($email, $subject, $message_body, $headers)) {
                $_SESSION['otp_sent'] = true; 
                $_SESSION['email'] = $email; 
                header("Location: forgot_password.php");
                exit();
            } else {
                $message = "Failed to send OTP. Please try again.";
            }
        } else {
            $message = "Email not found!";
        }
    }

    if (isset($_POST['reset_password'])) {
        $email = trim($_POST['email']);
        $otp = trim($_POST['otp']);
        $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

        if (!$email || !$otp || !$new_password) {
            $message = "All fields are required!";
        } else {
            // Fetch OTP from the database
            $stmt = $conn->prepare("SELECT reset_code FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->bind_result($stored_otp);
            $stmt->fetch();
            $stmt->close();

            if ($stored_otp === null) {
                $message = "Email not found!";
            } elseif ($otp != $stored_otp) { 
                $message = "Invalid OTP!";
            } else {
                // Update Password
                $stmt = $conn->prepare("UPDATE users SET password = ?, reset_code = NULL WHERE email = ?");
                $stmt->bind_param("ss", $new_password, $email);
                if ($stmt->execute()) {
                    $message = "Password has been changed successfully!";
                    session_unset(); // Clear session after success
                } else {
                    $message = "Error updating password!";
                }
            }
        }
    }
}

// Check session flag to show OTP fields
if (isset($_SESSION['otp_sent']) && $_SESSION['otp_sent'] === true) {
    $otpSent = true;
    unset($_SESSION['otp_sent']); // Clear flag after showing UI update
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot/Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <section class="vh-100 d-flex justify-content-center align-items-center">
        <div class="container">
            <div class="card shadow-lg p-4 mx-auto login-card">
                <div class="card-body">
                    <h3 class="text-center mb-4">Forgot/Reset Password</h3>
                    <?php if (!empty($message)) echo "<p class='text-danger text-center'>$message</p>"; ?>

                    <form method="POST" action="forgot_password.php">
                        <div class="form-floating mb-3">
                            <input type="email" name="email" class="form-control" id="email" 
                                placeholder="Enter email" required value="<?= isset($_SESSION['email']) ? $_SESSION['email'] : '' ?>">
                            <label for="email">Email</label>
                        </div>

                        <!-- OTP Section (Visible if OTP was sent) -->
                        <div id="otp_section" style="<?= $otpSent ? 'display:block;' : 'display:none;' ?>">
                            <div class="form-floating mb-3">
                                <input type="text" name="otp" class="form-control" id="otp" placeholder="Enter OTP">
                                <label for="otp">OTP</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="password" name="new_password" class="form-control" id="new_password" placeholder="New Password">
                                <label for="new_password">New Password</label>
                            </div>

                            <button type="submit" name="reset_password" class="btn btn-danger w-100">Reset Password</button>
                        </div>

                        <!-- Send OTP Button (Hidden if OTP was sent) -->
                        <?php if (!$otpSent) : ?>
                            <button type="submit" name="send_code" id="send_code_btn" class="btn btn-primary w-100 mb-3">Send OTP</button>
                        <?php endif; ?>
                    </form>

                    <p class="text-center mt-3"><a href="signin.php" class="link-danger">Back to Login</a></p>
                </div>
            </div>
        </div>
    </section>
</body>

</html>
