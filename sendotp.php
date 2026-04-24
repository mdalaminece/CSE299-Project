<?php
session_start();
include('smtp/PHPMailerAutoload.php');

if (isset($_POST['email'])) {
    $otp = rand(100000, 999999);
    $_SESSION['otp'] = $otp;
    $_SESSION['email'] = $_POST['email'];

    $receiverEmail = $_POST['email'];
    $subject = "Email Verification";
    $emailbody = "Your 6 Digit OTP Code: $otp";

    if (smtp_mailer($receiverEmail, $subject, $emailbody)) {
        echo "OTP has been sent to your email.";
        echo "<script>window.location.href='verify_otp.php';</script>";
        exit();
    } else {
        echo "Failed to send OTP. Try again.";
    }
}

function smtp_mailer($to, $subject, $msg)
{
    $mail = new PHPMailer();
    $mail->IsSMTP();
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = 'tls';
    $mail->Host = "smtp.gmail.com";
    $mail->Port = 587;
    $mail->IsHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Username = ".................";
    $mail->Password = ".............";
    $mail->SetFrom("...............");
    $mail->Subject = $subject;
    $mail->Body = $msg;
    $mail->AddAddress($to);
    $mail->SMTPOptions = array('ssl' => array(
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => false
    ));
    return $mail->Send();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification</title>
</head>

<body>
    <h2>Enter your email to receive OTP</h2>
    <form method="post">
        <input type="email" name="email" placeholder="Enter Email" required>
        <button type="submit">Send OTP</button>
    </form>
</body>

</html>