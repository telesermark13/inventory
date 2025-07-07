<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/googleauthenticator.php';
session_start();

if (!isset($_SESSION['pending_2fa_user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['pending_2fa_user_id'];
$stmt = $conn->prepare("SELECT ga_secret FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'] ?? '';
    $ga = new PHPGangsta_GoogleAuthenticator();
    $checkResult = $ga->verifyCode($user['ga_secret'], $code, 2);
    if ($checkResult) {
        $_SESSION['user_id'] = $user_id;
        unset($_SESSION['pending_2fa_user_id']);
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid code, please try again.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>2FA Verification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container" style="max-width: 400px; margin-top: 80px;">
    <div class="card">
        <div class="card-header">Google Authenticator Code</div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="mb-3">
                    <label for="code" class="form-label">Enter 6-digit code</label>
                    <input type="text" class="form-control" name="code" id="code" pattern="\d{6}" maxlength="6" required autofocus>
                </div>
                <button type="submit" class="btn btn-primary">Verify</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
