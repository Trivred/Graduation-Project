<?php
include 'includes/log_activity.php';
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/includes/db.php';

$loginError = '';
$signupError = '';
$signupSuccess = '';
$activeTab = 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $activeTab = 'login';
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'user';

if ($username === '' || $password === '' || !in_array($role, ['user', 'admin'], true)) {            $loginError = tr('required_fields');
        } else {
            $stmt = $conn->prepare('SELECT user_id, username, password_hash, role FROM users WHERE username = ? AND role = ? LIMIT 1');
            $stmt->bind_param('ss', $username, $role);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                header('Location: ' . ($user['role'] === 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php'));
                exit;
            }
            $loginError = tr('invalid_login');
        }
    }

    if ($action === 'signup') {
        $activeTab = 'signup';
        $fullName = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $role = 'user';

if ($fullName === '' || $username === '' || $email === '' || $password === '' || $confirmPassword === '') {            $signupError = tr('required_fields');
        } elseif ($password !== $confirmPassword) {
            $signupError = 'ÙÙÙØ© Ø§ÙÙØ±ÙØ± ØºÙØ± ÙØªØ·Ø§Ø¨ÙØ©.';
        } else {
            $stmt = $conn->prepare('SELECT user_id FROM users WHERE username = ? OR email = ? LIMIT 1');
            $stmt->bind_param('ss', $username, $email);
            $stmt->execute();
            $exists = $stmt->get_result()->num_rows > 0;
            $stmt->close();

            if ($exists) {
                $signupError = 'Ø§Ø³Ù Ø§ÙÙØ³ØªØ®Ø¯Ù Ø£Ù Ø§ÙØ¨Ø±ÙØ¯ Ø§ÙØ¥ÙÙØªØ±ÙÙÙ ÙØ³Ø¬Ù ÙØ³Ø¨ÙØ§Ù.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare('INSERT INTO users (full_name, username, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
                $stmt->bind_param('sssss', $fullName, $username, $email, $hashedPassword, $role);
                if ($stmt->execute()) {
                    $signupSuccess = tr('signup_success');
                    $activeTab = 'login';
                } else {
                    $signupError = 'Database error: ' . $conn->error;
                }
                $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= e($currentLang) ?>" dir="<?= e($dir) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(tr('login')) ?> | <?= e(tr('site_name')) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/auth.css?v=20260510_password_fix">
    <link rel="stylesheet" href="assets/css/footer.css">
</head>
<body class="<?= $currentLang === 'ar' ? 'lang-ar' : '' ?>" data-active-tab="<?= e($activeTab) ?>">
    <main class="auth-page">
    <section class="auth-hero">
        <div class="auth-hero-content">
            <img class="auth-logo" src="assets/img/logo2.png" alt="<?= e(tr('site_name')) ?>" onerror="this.style.display='none'">
            <h1><?= e(tr('site_name')) ?></h1><p><?= e(tr('hero_desc')) ?></p>   
     </div>
    </section>

    <section class="auth-panel">
        <div class="auth-card">
            <div class="auth-top">
                <h2><?= e(tr('welcome_back')) ?></h2>
                <div class="lang-switch">
                    <a class="<?= $currentLang === 'ar' ? 'active' : '' ?>" href="?lang=ar">AR</a>
                    <a class="<?= $currentLang === 'en' ? 'active' : '' ?>" href="?lang=en">EN</a>
                </div>
            </div>

            <div class="tabs" role="tablist">
                <button class="tab" type="button" data-auth-tab="login"><?= e(tr('login')) ?></button>
                <button class="tab" type="button" data-auth-tab="signup"><?= e(tr('signup')) ?></button>
            </div>

            <?php if ($loginError): ?><div class="alert alert-danger"><?= e($loginError) ?></div><?php endif; ?>
            <?php if ($signupError): ?><div class="alert alert-danger"><?= e($signupError) ?></div><?php endif; ?>
            <?php if ($signupSuccess): ?><div class="alert alert-success"><?= e($signupSuccess) ?></div><?php endif; ?>

            <form class="form" data-auth-form="login" method="POST" autocomplete="on">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
<label for="loginUsername"><?= e(tr('username')) ?></label>             
       <div class="input-wrap"><i class="bi bi-person"></i><input id="loginUsername" name="username" type="text" required></div>

                </div>
                <div class="form-group">
                    <label for="loginPassword"><?= e(tr('password')) ?></label>
                    <div class="input-wrap">
                        <i class="bi bi-lock-fill"></i>
                        <input id="loginPassword" name="password" type="password" required>
                        <button class="password-toggle" type="button" data-toggle-password="loginPassword" aria-label="Show or hide password"><i class="bi bi-eye"></i></button>
                    </div>
                </div>
                <div class="role-switch">
                    <label><input type="radio" name="role" value="user" checked> <?= e(tr('user')) ?></label>
                    <label><input type="radio" name="role" value="admin"> <?= e(tr('admin')) ?></label>
                </div>
                <button class="auth-btn" type="submit"><i class="bi bi-box-arrow-in-right"></i><?= e(tr('login')) ?></button>
                <div class="forgot-password">
    <a href="forgot_password.php">
        <i class="bi bi-question-circle"></i>
        <?= e(tr('forgot_password')) ?>
    </a>
</div>
            </form>

            <form class="form" data-auth-form="signup" method="POST" autocomplete="on">
                <input type="hidden" name="action" value="signup">
                <div class="form-group">
                    <label for="fullName"><?= e(tr('full_name')) ?></label>
                    <div class="input-wrap"><i class="bi bi-person"></i><input id="fullName" name="full_name" type="text" required></div>
                </div>
                <div class="form-group">
<label for="username"><?= e(tr('username')) ?></label>
                    <div class="input-wrap"><i class="bi bi-at"></i><input id="username" name="username" type="text" required></div>
                </div>
                <div class="form-group">
                    <label for="signupEmail"><?= e(tr('email')) ?></label>
                    <div class="input-wrap"><i class="bi bi-envelope"></i><input id="signupEmail" name="email" type="email" required></div>
                </div>
                <div class="form-group">
                    <label for="signupPassword"><?= e(tr('password')) ?></label>
                    <div class="input-wrap">
                        <i class="bi bi-lock-fill"></i>
                        <input id="signupPassword" name="password" type="password" required minlength="6">
                        <button class="password-toggle" type="button" data-toggle-password="signupPassword" aria-label="Show or hide password"><i class="bi bi-eye"></i></button></div>
                </div>
                <div class="form-group">
<label for="confirmPassword"><?= e(tr('confirm_password')) ?></label>
                    <div class="input-wrap"><i class="bi bi-lock-fill"></i><input id="confirmPassword" name="confirm_password" type="password" required minlength="6"></div>
                </div>
                <button class="auth-btn" type="submit"><i class="bi bi-person-plus"></i><?= e(tr('create_account')) ?></button>
                
            </form>

            <div class="form-links"><a href="index.php"><i class="bi bi-arrow-right"></i> <?= e(tr('home')) ?></a></div>
        </div>
    </section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
<script src="assets/js/auth.js?v=20260510_password_fix"></script>
</body>
</html>