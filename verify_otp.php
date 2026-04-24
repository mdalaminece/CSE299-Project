<?php
session_start();

if(isset($_POST['otp'])){
    if(isset($_SESSION['otp']) && $_POST['otp'] == $_SESSION['otp']){
        unset($_SESSION['otp']);
        echo "<script>alert('OTP Verified Successfully!'); window.location.href='welcome.html';</script>";
        exit();
    } else {
        echo "<script>alert('Invalid OTP! Try Again.'); window.location.href='verify_otp.php';</script>";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Verify OTP</title>
</head>
<body>
    <h2>Verify OTP</h2>
    <form method="post">
        <label for="otp">Enter OTP:</label>
        <input type="text" name="otp" required>
        <button type="submit">Verify</button>
    </form>
</body>
</html>
