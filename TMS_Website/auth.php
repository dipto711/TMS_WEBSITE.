<?php session_start(); ?>
<!DOCTYPE html>
<html>
<head>
    <title>TMS - Login/Register</title>
    <link rel="stylesheet" href="css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <div class="forms-container">
            <div class="signin-signup">
                <!-- Login Form -->
                <form action="login_process.php" method="post" class="sign-in-form">
                    <h2>Sign In</h2>
                    <div class="input-field">
                        <i class="fas fa-user"></i>
                        <input type="email" name="email" placeholder="Email" required>
                    </div>
                    <div class="input-field">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="Password" required>
                    </div>
                    <input type="submit" value="Login" class="btn solid">
                </form>

                <!-- Register Form -->
                <form action="register_process.php" method="post" class="sign-up-form">
                    <h2>Sign Up</h2>
                    <div class="input-field">
                        <i class="fas fa-user"></i>
                        <input type="text" name="name" placeholder="Full Name" required>
                    </div>
                    <div class="input-field">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="Email" required>
                    </div>
                    <div class="input-field">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="Password" required>
                    </div>
                    <div class="input-field">
                        <i class="fas fa-user-tag"></i>
                        <select name="role" id="role" onchange="showFields()">
                            <option value="client">Client</option>
                            <option value="driver">Driver</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div id="driverFields">
                        <div class="input-field">
                            <i class="fas fa-id-card"></i>
                            <input type="text" name="license" placeholder="License Number" required>
                        </div>
                        <div class="input-field">
                            <i class="fas fa-truck"></i>
                            <input type="text" name="vehicleType" placeholder="Vehicle Type" required>
                        </div>
                        <div class="input-field">
                            <i class="fas fa-car"></i>
                            <input type="text" name="vehicle_number" placeholder="Vehicle Number" required>
                        </div>
                        <div class="input-field">
                            <i class="fas fa-weight"></i>
                            <input type="number" name="capacity" placeholder="Capacity" min="1" required>
                        </div>
                    </div>
                    <input type="submit" class="btn" value="Sign up">
                    <?php
                        if(isset($_SESSION['errors'])){
                            echo "<ul class='error'>";
                            foreach($_SESSION['errors'] as $error){
                                echo "<li>$error</li>";
                            }
                            echo "</ul>";
                            unset($_SESSION['errors']);
                        }
                        if(isset($_SESSION['success'])){
                            echo "<p class='success'>".$_SESSION['success']."</p>";
                            unset($_SESSION['success']);
                        }
                    ?>
                </form>
            </div>
        </div>

        <div class="panels-container">
            <div class="panel left-panel">
                <div class="content">
                    <h3>New here?</h3>
                    <p>Join our platform and revolutionize your transportation management experience!</p>
                    <button class="btn transparent" id="sign-up-btn">Sign up</button>
                </div>
                <img src="images/log.svg" class="image" alt="" />
            </div>
            <div class="panel right-panel">
                <div class="content">
                    <h3>One of us?</h3>
                    <p>Sign in and manage your transportation operations efficiently!</p>
                    <button class="btn transparent" id="sign-in-btn">Sign in</button>
                </div>
                <img src="images/register.svg" class="image" alt="" />
            </div>
        </div>
    </div>
    <script src="js/auth.js"></script>
</body>
</html>
