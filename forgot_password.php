<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include "databaseConnection.php";

$message = "";

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
            
            // Generate Code
            $otp = rand(100000, 999999);
            
            // Storing the code in Database
            $stmt = $conn->prepare("UPDATE users SET reset_code = ? WHERE email = ?");
            $stmt->bind_param("ss", $otp, $email);
            $stmt->execute();
            
            // Send Email using PHP mail()
            $subject = "Password Reset Code";
            $message_body = "Your OTP code is: $otp";
            $headers = "From: jasoncsfy2011@gmail.com";
            
            if (mail($email, $subject, $message_body, $headers)) {
                $message = "OTP has been sent to your email.";
            } else {
                $message = "Failed to send OTP. Please try again.";
            }
        } else {
            $message = "Email not found!";
        }
    }

    if (isset($_POST['reset_password'])) {
      // Debugging: Print $_POST values to check if they exist
      echo "<pre>";
      print_r($_POST);
      echo "</pre>";
  
      // Ensure fields exist and are not empty
      $email = !empty($_POST['email']) ? trim($_POST['email']) : null;
      $otp = !empty($_POST['otp']) ? trim($_POST['otp']) : null;
      $new_password = !empty($_POST['new_password']) ? password_hash($_POST['new_password'], PASSWORD_DEFAULT) : null;
  
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
  
          // Debugging: Show OTPs for comparison
          echo "Entered OTP: " . htmlspecialchars($otp) . "<br>";
          echo "Stored OTP: " . htmlspecialchars($stored_otp) . "<br>";
  
          if ($stored_otp === null) {
              $message = "Email not found!";
          } elseif ($otp != $stored_otp) { // Loose comparison to handle different data types
              $message = "Invalid OTP!";
          } else {
              // Update Password
              $stmt = $conn->prepare("UPDATE users SET password = ?, reset_code = NULL WHERE email = ?");
              $stmt->bind_param("ss", $new_password, $email);
              if ($stmt->execute()) {
                  $message = "Password has been changed successfully!";
              } else {
                  $message = "Error updating password!";
              }
          }
      }
  }  
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
                        <div class="form-floating mb-4">
                            <input type="email" name="email" class="form-control" id="email" placeholder="Enter email"
                                required>
                            <label for="email">Email</label>
                        </div>

                        <button type="submit" name="send_code" class="btn btn-primary w-100 mb-3">Send OTP</button>
                    </form>

                    <form method="POST" action="forgot_password.php">
                        <div class="form-floating mb-3">
                            <input type="email" name="email" class="form-control" id="email" placeholder="Enter email"
                                required>
                            <label for="email">Email</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" name="otp" class="form-control" id="otp" placeholder="Enter OTP"
                                required>
                            <label for="otp">OTP</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="password" name="new_password" class="form-control" id="new_password"
                                placeholder="New Password" required>
                            <label for="new_password">New Password</label>
                        </div>

                        <button type="submit" name="reset_password" class="btn btn-danger w-100">Reset Password</button>
                    </form>


                    <p class="text-center mt-3"><a href="signin.php" class="link-danger">Back to Login</a></p>
                </div>
            </div>
        </div>
    </section>
</body>

</html>