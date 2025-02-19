<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include "databaseConnection.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $hashed_password);
        $stmt->fetch();
        if (password_verify($password, $hashed_password)) {
            $_SESSION["user_id"] = $id;
            header("Location: index.php");
            exit();
        } else {
            $error = "Invalid email or password!";
        }
    } else {
        $error = "Invalid email or password!";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <section class="vh-100 d-flex justify-content-center align-items-center">
        <div class="container">
            <div class="card shadow-lg p-4 mx-auto login-card">
                <div class="row g-0">
                    <div class="col-md-6 d-flex align-items-center">
                        <img src="images/LeftImage.webp" class="img-fluid rounded" alt="Sample image">
                    </div>
                    <div class="col-md-6">
                        <div class="card-body">
                            <h3 class="text-center mb-4">Login</h3>
                            <?php if (!empty($error)) echo "<p class='text-danger text-center'>$error</p>"; ?>
                            <form method="POST" action="signin.php">
                                <div class="form-floating mb-4">
                                    <input type="email" name="email" class="form-control" id="email"
                                        placeholder="Enter email" required>
                                    <label for="email">Email</label>
                                </div>
                                <div class="form-floating mb-3">
                                    <input type="password" name="password" class="form-control" id="password"
                                        placeholder="Enter password" required>
                                    <label for="password">Password</label>
                                </div>
                                <button type="submit" class="btn btn-danger btn-lg w-100">Login</button>
                                <p class="text-center mt-3">Don't have an account? <a href="signup.php"
                                        class="link-danger">Sign Up</a></p>
                                <p class="text-center mt-2"><a href="forgot_password.php" class="link-danger">Forgot
                                        Password / Change Password?</a></p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</body>

</html>