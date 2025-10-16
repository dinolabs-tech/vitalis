<!DOCTYPE html>
<html lang="en">


<!-- register24:03-->
<?php include('components/head.php'); ?>

<body>
    <div class="container">
        <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
            <div class="account-logo">
                <a href="index-2.php"><img src="assets/img/logo-dark.png" alt=""></a>
            </div>
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">
                        <div class="card w-100">
                            <div class="card-header">
                                <h3 class="text-center">Register</h3>
                            </div>
                            <form action="#" class="form-signin">

                                <div class="form-group">
                                    <input type="text" placeholder="Username" class="form-control">
                                </div>
                                <div class="form-group">
                                    <input type="email" placeholder="Email Address" class="form-control">
                                </div>
                                <div class="form-group">
                                    <input type="password" placeholder="Password" class="form-control">
                                </div>
                                <div class="form-group">
                                    <input type="text" placeholder="Mobile Number" class="form-control">
                                </div>
                                <div class="form-group checkbox">
                                    <label>
                                        <input type="checkbox"> I have read and agree the Terms & Conditions
                                    </label>
                                </div>
                                <div class="form-group text-center">
                                    <button class="btn btn-primary account-btn rounded" type="submit">Signup</button>
                                </div>
                                <div class="text-center login-link">
                                    Already have an account? <a href="login.php">Login</a>
                                </div>
                                <div class="text-center login-link mb-3">
                                    <a href="index.php">Back to Site</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <?php include('components/script.php'); ?>
</body>


<!-- register24:03-->

</html>