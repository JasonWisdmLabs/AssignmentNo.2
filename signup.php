<?php
include "databaseConnection.php"; 
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);  // FIX: Added missing password retrieval

    // Server-side validation
    if (empty($name) || empty($email) || empty($password)) {
        $error = "All fields are required!";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Checking if the email ID already exists
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $error = "Email already exists!";
        } else {
            // Inserting a new user
            $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $email, $hashedPassword);

            if ($stmt->execute()) {
                // FIX: Remove exit() and store a flag for JavaScript execution
                $success = true;
            } else {
                $error = "Unable to register!";
            }
            $stmt->close();
        }
        $checkStmt->close();
    }
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            <?php 
            if (!empty($error)) { 
                echo "<script>Swal.fire('Error', '$error', 'error');</script>"; 
            }
            ?>
            <form method="POST" action="signup.php" onsubmit="return validateForm()">
              <div class="form-floating mb-4">
                <input type="text" name="name" class="form-control" id="name" placeholder="Full Name">
                <label for="name">Full Name</label>
              </div>
              <div class="form-floating mb-4">
                <input type="email" name="email" class="form-control" id="email" placeholder="Enter email">
                <label for="email">Email</label>
              </div>
              <div class="form-floating mb-3">
                <input type="password" name="password" class="form-control" id="password" placeholder="Enter password">
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

<script>
function validateForm() {
    let name = document.getElementById("name").value.trim();
    let email = document.getElementById("email").value.trim();
    let password = document.getElementById("password").value.trim();

    if (name === "" || email === "" || password === "") {
        Swal.fire('Error', 'All fields are required!', 'error');
        return false;
    }
    return true;
}

// Success Alert Handling
<?php if (isset($success) && $success === true) : ?>
setTimeout(function() {
    Swal.fire({
        title: 'Account Created!',
        text: 'Your account has been successfully created.',
        icon: 'success',
        confirmButtonText: 'OK'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'signin.php';
        }
    });
}, 100);
<?php endif; ?>
</script>

</body>
</html>
