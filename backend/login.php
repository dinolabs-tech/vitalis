<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// include_once('database/database_schema.php'); // Include database connection
include_once('database/db_connect.php'); // Include database connection

// Check if admin account exists, if not, create it
$admin_username = 'dinolabs';
$admin_password_plain = 'dinolabs';
$admin_role = 'admin';
$admin_email = 'admin@dinolabs.tech'; // fallback email

$stmt = $conn->prepare("SELECT id FROM login WHERE username = ? AND role = 'admin'");
$stmt->bind_param("s", $admin_username);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    // No admin account, create one
    $hashed_password = password_hash($admin_password_plain, PASSWORD_DEFAULT);
    $insert_stmt = $conn->prepare("INSERT INTO login (staffname, username, email, password, role) VALUES (?, ?, ?, ?, ?)");
    $staffname = 'Admin';
    $insert_stmt->bind_param("sssss", $staffname, $admin_username, $admin_email, $hashed_password, $admin_role);
    $insert_stmt->execute();
    $insert_stmt->close();
}

$stmt->close();

session_start();
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error_message = "Please enter both username/email and password.";
    } else {
        // Prepare a statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT id, staffname, username, password, role, email, profile_picture FROM login WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Password is correct, start a new session
                $_SESSION['loggedin'] = true;
                $_SESSION['id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['staffname'] = $user['staffname'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['profile_picture'] = $user['profile_picture'];

                // Redirect user to appropriate dashboard based on role
                if ($user['role'] === 'admin') {
                    header("Location: index.php");
                } elseif ($user['role'] === 'doctor') {
                    header("Location: index.php");
                } elseif ($user['role'] === 'receptionist') {
                    header("Location: index.php");
                } elseif ($user['role'] === 'pharmacist') {
                    header("Location: index.php");
                } elseif ($user['role'] === 'nurse') {
                    header("Location: index.php");
                } elseif ($user['role'] === 'lab_technician') {
                    header("Location: index.php");
                } else {
                    // For other roles, redirect to a generic dashboard or specific one
                    header("Location: ../index.php"); // Example: redirect to frontend for non-admins
                }
                exit;
            } else {
                $error_message = "Invalid username/email or password.";
            }
        } else {
            $error_message = "Invalid username/email or password.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<?php include('components/head.php'); ?>

<body>

    <div class="container">
        <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center">
            <a href="index.php" class="logo">
        <img
          src="assets/img/logo.png"
          alt="navbar brand"
          class="navbar-brand"
          height="37" />
      </a>
      <br>
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-4 col-md-6 col-sm-8 d-flex flex-column align-items-center justify-content-center">
                        <div class="card w-100">
                            <div class="card-header">
                                <h3 class="text-center">Login</h3>
                            </div>
                            <div class="card-body">
                                <form action="login.php" method="post">
                                    <div class="form-group">
                                        <input type="text" class="form-control" placeholder="Username or Email" name="username" autofocus="" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <input type="password" class="form-control" placeholder="Password" name="password">
                                    </div>
                                    <div class="form-group text-right">
                                        <a href="forgot-password.php">Forgot your password?</a>
                                    </div>
                                    <div class="form-group text-center">
                                        <button type="submit" class="btn btn-primary account-btn rounded text-center">Login</button>
                                    </div>
                                    <div class="text-center register-link">
                                        Don’t have an account? <a href="register.php">Register Now</a>
                                    </div>
                                    <div class="form-group text-center mt-3">
                                        <a href="../index.php">Back to Site</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</body>

</html>
