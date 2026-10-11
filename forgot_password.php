<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/includes/db.php';

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
    $_SESSION['selected_lang'] = $_GET['lang'];
}

$currentLang = $_SESSION['selected_lang'] ?? $_SESSION['lang'] ?? 'ar';
$currentLang = in_array($currentLang, ['ar', 'en'], true) ? $currentLang : 'ar';
$dir = $currentLang === 'ar' ? 'rtl' : 'ltr';

function reset_text(string $key): string
{
    global $currentLang;

    $messages = [
        'ar' => [
            'invalid_email' => 'يرجى إدخال بريد إلكتروني صحيح.',
            'email_not_found' => 'هذا البريد الإلكتروني غير موجود في النظام.',
            'pass_not_match' => 'كلمة المرور وتأكيدها غير متطابقين.',
            'pass_requirements' => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل وتحتوي على حروف وأرقام.',
            'password_updated' => 'تم تحديث كلمة المرور بنجاح. سيتم تحويلك إلى تسجيل الدخول.',
            'error_occurred' => 'حدث خطأ أثناء تحديث كلمة المرور. حاول مرة أخرى.',
        ],
        'en' => [
            'invalid_email' => 'Please enter a valid email address.',
            'email_not_found' => 'This email address does not exist in the system.',
            'pass_not_match' => 'Password and confirmation do not match.',
            'pass_requirements' => 'Password must be at least 6 characters and include letters and numbers.',
            'password_updated' => 'Password updated successfully. Redirecting to login.',
            'error_occurred' => 'An error occurred while updating the password. Please try again.',
        ],
    ];

    return $messages[$currentLang][$key] ?? tr($key);
}

$msg = '';
$error = '';
$emailValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirm  = (string)($_POST['confirm'] ?? '');
    $emailValue = $email;

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = reset_text('invalid_email');
    } else {
        $check = $conn->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');

        if (!$check) {
            $error = reset_text('error_occurred');
        } else {
            $check->bind_param('s', $email);
            $check->execute();
            $result = $check->get_result();
            $user = $result ? $result->fetch_assoc() : null;
            $check->close();

            if (!$user) {
                $error = reset_text('email_not_found');
            } elseif ($password !== $confirm) {
                $error = reset_text('pass_not_match');
            } elseif (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,}$/', $password)) {
                $error = reset_text('pass_requirements');
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare('UPDATE users SET password_hash = ? WHERE user_id = ? LIMIT 1');

                if (!$stmt) {
                    $error = reset_text('error_occurred');
                } else {
                    $userId = (int)$user['user_id'];
                    $stmt->bind_param('si', $hashed, $userId);
                    $stmt->execute();

                    if ($stmt->affected_rows >= 0 && $stmt->errno === 0) {
                        $msg = reset_text('password_updated');
                        $emailValue = '';
                    } else {
                        $error = reset_text('error_occurred');
                    }

                    $stmt->close();
                }
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
<title><?= e(tr('reset_password')) ?></title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="assets/css/public-home.css">
<link rel="stylesheet" href="assets/css/header.css">
<link rel="stylesheet" href="assets/css/footer.css">

<style>
.page-wrapper{
    min-height:80vh;
    display:flex;
    justify-content:center;
    align-items:center;
    padding:40px 20px;
}

.box{
    background:white;
    padding:35px;
    border-radius:15px;
    width:100%;
    max-width:400px;
    box-shadow:0 15px 40px rgba(0,0,0,0.1);
    animation:fadeIn 0.5s ease;
}

h2{
    text-align:center;
    color:#1B5E20;
    margin-bottom:12px;
}

.help-text{
    text-align:center;
    color:#60756a;
    line-height:1.8;
    margin:0 0 18px;
    font-size:14px;
}

.input-group{
    position:relative;
    margin:15px 0;
}

.input-group input{
    width:100%;
    padding:12px 40px 12px 12px;
    border-radius:8px;
    border:1px solid #ccc;
    transition:0.3s;
}

.input-group input:focus{
    border-color:#1B5E20;
    box-shadow:0 0 6px rgba(27,94,32,0.3);
    outline:none;
}

.input-group i{
    position:absolute;
    right:12px;
    top:50%;
    transform:translateY(-50%);
    color:#1B5E20;
    cursor:pointer;
}

button{
    width:100%;
    padding:12px;
    background:#1B5E20;
    color:white;
    border:none;
    border-radius:8px;
    cursor:pointer;
    transition:0.3s;
    font-weight:700;
}

button:hover{
    background:#4CAF50;
}

.error,
.success{
    text-align:center;
    margin-bottom:12px;
    padding:10px 12px;
    border-radius:10px;
    font-weight:700;
    line-height:1.7;
}

.error{
    color:#b71c1c;
    background:#ffebee;
    border:1px solid #ffcdd2;
}

.success{
    color:#1b5e20;
    background:#e8f5e9;
    border:1px solid #c8e6c9;
}

.back{
    text-align:center;
    margin-top:12px;
}

.back a{
    color:#1B5E20;
    text-decoration:none;
    font-weight:700;
}

.back a:hover{
    text-decoration:underline;
}

@keyframes fadeIn{
    from{opacity:0;transform:translateY(20px);}
    to{opacity:1;transform:translateY(0);}
}
</style>

</head>
<body>

<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>

<header class="public-header">
    <a class="brand" href="index.php">
        <img src="assets/img/logo2.png" alt="<?= e(tr('site_name')) ?>" onerror="this.style.display='none'">
        <span><?= e(tr('site_name')) ?></span>
    </a>

    <button class="menu-toggle" onclick="toggleMenu()">
        <i class="bi bi-list"></i>
    </button>

    <nav class="public-nav" id="mainNav" aria-label="main navigation">
        <a class="<?= $currentPage == 'index.php' ? 'active' : '' ?>" href="index.php">
            <i class="bi bi-house"></i>
            <?= e(tr('home')) ?>
        </a>

        <a class="<?= $currentPage == 'about.php' ? 'active' : '' ?>" href="about.php">
            <i class="bi bi-info-circle"></i>
            <?= e(tr('about')) ?>
        </a>

        <a class="<?= $currentPage == 'login_register.php' ? 'active' : '' ?>" href="login_register.php">
            <i class="bi bi-box-arrow-in-right"></i>
            <?= e(tr('login')) ?>
        </a>

        <div class="lang-switch">
            <a class="<?= $currentLang === 'ar' ? 'active' : '' ?>" href="?lang=ar">AR</a>
            <a class="<?= $currentLang === 'en' ? 'active' : '' ?>" href="?lang=en">EN</a>
        </div>
    </nav>
</header>

<div class="page-wrapper">
    <div class="box">
        <h2><?= e(tr('reset_password')) ?></h2>

        <?php if($error): ?>
            <p class="error"><?= e($error) ?></p>
        <?php endif; ?>

        <?php if($msg): ?>
            <p class="success"><?= e($msg) ?></p>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="input-group">
                <input type="email" name="email" value="<?= e($emailValue) ?>" placeholder="<?= e(tr('email')) ?>" required>
                <i class="bi bi-envelope-fill"></i>
            </div>

            <div class="input-group">
                <input id="pass" type="password" name="password" placeholder="<?= e(tr('new_password')) ?>" required>
                <i class="bi bi-eye-slash" onclick="togglePassword('pass', this)"></i>
            </div>

            <div class="input-group">
                <input id="confirm" type="password" name="confirm" placeholder="<?= e(tr('confirm_password')) ?>" required>
                <i class="bi bi-eye-slash" onclick="togglePassword('confirm', this)"></i>
            </div>

            <button type="submit"><?= e(tr('update_password')) ?></button>
        </form>

        <div class="back">
            <a href="login_register.php"><?= e(tr('back_login')) ?></a>
        </div>
    </div>
</div>

<script>
function togglePassword(id, icon){
    const input = document.getElementById(id);

    if(input.type === 'password'){
        input.type = 'text';
        icon.classList.replace('bi-eye-slash','bi-eye');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye','bi-eye-slash');
    }
}

<?php if($msg): ?>
setTimeout(() => {
    window.location.href = 'login_register.php';
}, 2000);
<?php endif; ?>
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
