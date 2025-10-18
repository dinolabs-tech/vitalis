<?php
session_start();
include('includes/config.php');
include('includes/checklogin.php');
check_login();

// Include database connection
require_once('database/db_connect.php');

$user_id = $_SESSION['id'];
$msg = "";
$error = "";

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM login WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_object();
$stmt->close();

if (isset($_POST['update_profile'])) {
    $staffname = $_POST['staffname'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $specialization = $_POST['specialization'];
    $license_number = $_POST['license_number'];
    $address = $_POST['address'];
    $mobile = $_POST['mobile'];
    $country = $_POST['country'];
    $state = $_POST['state'];

    $profile_picture = $user->profile_picture; // Default to existing picture

    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $target_dir = "assets/img/profile/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $target_file = $target_dir . basename($_FILES["profile_picture"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $uploadOk = 1;

        // Check if image file is a actual image or fake image
        $check = getimagesize($_FILES["profile_picture"]["tmp_name"]);
        if ($check !== false) {
            // Allow certain file formats
            if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
                $error = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
                $uploadOk = 0;
            }
        } else {
            $error = "File is not an image.";
            $uploadOk = 0;
        }

        // Check file size
        if ($_FILES["profile_picture"]["size"] > 500000) { // 500KB
            $error = "Sorry, your file is too large.";
            $uploadOk = 0;
        }

        // If all checks pass, try to upload file
        if ($uploadOk == 1) {
            // Generate a unique file name to prevent overwrites
            $new_file_name = uniqid() . "." . $imageFileType;
            $target_file = $target_dir . $new_file_name;

            if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) {
                $profile_picture = $new_file_name;
            } else {
                $error = "Sorry, there was an error uploading your file.";
            }
        }
    }

    if (empty($error)) {
        $stmt = $conn->prepare("UPDATE login SET staffname = ?, username = ?, email = ?, specialization = ?, license_number = ?, address = ?, mobile = ?, country = ?, state = ?, profile_picture = ? WHERE id = ?");
        $stmt->bind_param("ssssssssssi", $staffname, $username, $email, $specialization, $license_number, $address, $mobile, $country, $state, $profile_picture, $user_id);

        if ($stmt->execute()) {
            $msg = "Profile updated successfully!";
            // Update session variables if needed
            $_SESSION['staffname'] = $staffname;
            $_SESSION['username'] = $username;
            $_SESSION['profile_picture'] = $profile_picture;
            // Re-fetch user data to display updated info immediately
            $stmt_re_fetch = $conn->prepare("SELECT * FROM login WHERE id = ?");
            $stmt_re_fetch->bind_param("i", $user_id);
            $stmt_re_fetch->execute();
            $result_re_fetch = $stmt_re_fetch->get_result();
            $user = $result_re_fetch->fetch_object();
            $stmt_re_fetch->close();
        } else {
            $error = "Error updating profile: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (!password_verify($current_password, $user->password)) {
        $error = "Current password is incorrect.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New password and confirm password do not match.";
    } else {
        $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE login SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_new_password, $user_id);

        if ($stmt->execute()) {
            $msg = "Password updated successfully!";
        } else {
            $error = "Error updating password: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include('components/head.php'); ?>

<body>
    <div class="wrapper">
        <?php include('components/sidebar.php'); ?>

        <div class="main-panel">
            <?php include('components/navbar.php'); ?>

            <div class="container">
                <div class="page-inner">

                    <div class="page-header">
                        <h4 class="page-title">User Profile</h4>
                        <ul class="breadcrumbs">
                            <li class="nav-home">
                                <a href="dashboard.php">
                                    <i class="flaticon-home"></i>
                                </a>
                            </li>
                            <li class="separator">
                                <i class="flaticon-right-arrow"></i>
                            </li>
                            <li class="nav-item">
                                <a href="#">Profile</a>
                            </li>
                        </ul>
                    </div>

                    <div class="row">

                        <?php if ($msg) { ?>
                            <div class="alert alert-success"><?php echo $msg; ?></div>
                        <?php } ?>
                        <?php if ($error) { ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php } ?>

                        <!-- edit profile card -->
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title">Edit Profile</div>
                                </div>
                                <div class="card-body">
                                    <form method="POST" enctype="multipart/form-data" class="row p-3">
                                        <div class="form-group">
                                            <label for="profile_picture">Profile Picture</label>
                                            <input type="file" class="form-control-file form-control" id="profile_picture" name="profile_picture">
                                            <?php if ($user->profile_picture) { ?>
                                                <div class="avatar avatar-xxl">
                                                    <img src="assets/img/profile/<?php echo htmlentities($user->profile_picture); ?>" class="avatar-img rounded-circle mt-3" alt="Profile Picture" width="100" class="mt-2">
                                                </div>
                                            <?php } ?>
                                        </div>
                                        <div class="col-md-4 form-group">
                                            <label for="staffname">Staff Name</label>
                                            <input type="text" class="form-control" id="staffname" name="staffname" value="<?php echo htmlentities($user->staffname); ?>" required>
                                        </div>
                                        <div class="col-md-4 form-group">
                                            <label for="username">Username</label>
                                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlentities($user->username); ?>" required>
                                        </div>
                                        <div class="col-md-4 form-group">
                                            <label for="email">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlentities($user->email); ?>" required>
                                        </div>
                                        <div class="col-md-4 form-group">
                                            <label for="specialization">Specialization</label>
                                            <input type="text" class="form-control" id="specialization" name="specialization" value="<?php echo htmlentities($user->specialization); ?>">
                                        </div>
                                        <div class="col-md-4 form-group">
                                            <label for="license_number">License Number</label>
                                            <input type="text" class="form-control" id="license_number" name="license_number" value="<?php echo htmlentities($user->license_number); ?>">
                                        </div>
                                        <div class="col-md-4 form-group">
                                            <label for="address">Address</label>
                                            <textarea class="form-control" id="address" name="address"><?php echo htmlentities($user->address); ?></textarea>
                                        </div>
                                        <div class="col-md-4 form-group">
                                            <label for="mobile">Mobile</label>
                                            <input type="text" class="form-control" id="mobile" name="mobile" value="<?php echo htmlentities($user->mobile); ?>">
                                        </div>
                                        <div class="col-md-4 form-group">
                                            <label for="country">Country</label>
                                            <input type="text" class="form-control" id="country" name="country" value="<?php echo htmlentities($user->country); ?>">
                                        </div>
                                        <div class="col-md-4 form-group">
                                            <label for="state">State</label>
                                            <input type="text" class="form-control" id="state" name="state" value="<?php echo htmlentities($user->state); ?>">
                                        </div>
                                        <div class="col-md-12 text-center card-action">
                                            <button type="submit" name="update_profile" class="btn btn-success">Update Profile</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <!-- edit profile car ends here -->

                        <!-- change password card -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title">Change Password</div>
                                </div>
                                <div class="card-body">
                                    <!-- <h4 class="mt-4">Change Password</h4> -->
                                    <form method="POST">
                                        <div class="form-group">
                                            <label for="current_password">Current Password</label>
                                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="new_password">New Password</label>
                                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="confirm_password">Confirm New Password</label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        </div>
                                        <div class="col-md-12 text-center card-action">
                                            <button type="submit" name="change_password" class="btn btn-warning">Change Password</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <!-- change password card ends here -->
                    </div>



                </div>
            </div>
            <?php include('components/footer.php'); ?>
        </div>
    </div>
    <?php include('components/script.php'); ?>
</body>

</html>