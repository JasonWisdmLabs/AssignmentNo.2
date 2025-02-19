<?php
include "databaseConnection.php"; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    //Checking if the email ID already exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        $error = "Email already exists!";
    } else {
        // Inserting a new user
        $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $password);

        if ($stmt->execute()) {
            header("Location: signin.php");
            exit();
        } else {
            $error = "Unable to register!";
        }
        $stmt->close();
    }
    $checkStmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
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
            <h3 class="text-center mb-4">Sign Up</h3>
            <?php if (!empty($error)) echo "<p class='text-danger text-center'>$error</p>"; ?>
            <form method="POST" action="signup.php">
              <div class="form-floating mb-4">
                <input type="text" name="name" class="form-control" id="name" placeholder="Full Name" required>
                <label for="name">Full Name</label>
              </div>
              <div class="form-floating mb-4">
                <input type="email" name="email" class="form-control" id="email" placeholder="Enter email" required>
                <label for="email">Email</label>
              </div>
              <div class="form-floating mb-3">
                <input type="password" name="password" class="form-control" id="password" placeholder="Enter password" required>
                <label for="password">Password</label>
              </div>
              <button type="submit" class="btn btn-danger btn-lg w-100">Sign Up</button>
              <p class="text-center mt-3">Already have an account? <a href="signin.php" class="link-danger">Login</a></p>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
</body>
</html>
