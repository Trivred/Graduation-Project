<?php

session_start();

include 'includes/db.php';
include 'includes/log_activity.php';

if(isset($_POST['update'])){

    $id = (int)$_GET['id'];

    $name = $_POST['full_name'];

    $email = $_POST['email'];

    $stmt = $conn->prepare("UPDATE users SET full_name=?, email=? WHERE user_id=?");

    $stmt->bind_param("ssi", $name, $email, $id);

    $stmt->execute();

    logActivity(
        $conn,
        $_SESSION['user_id'],
        'Edit User',
        'تم تعديل بيانات المستخدم '
        . $name
    );

    header("Location: admin_users.php");

    exit();
}

if(!isset($_GET['id'])){

    header("Location: admin_users.php");

    exit();
}

$id = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT * FROM users WHERE user_id=?");

$stmt->bind_param("i", $id);

$stmt->execute();

$result = $stmt->get_result();

$user = $result->fetch_assoc();

?>

<?php include "includes/admin_header.php"; ?>
<?php include "includes/admin_sidebar.php"; ?>
<?php include "includes/admin_navbar.php"; ?>

<div class="form-container">

<h3>✏️ تعديل المستخدم</h3>

<form method="POST">

    <input 
        type="text" 
        name="full_name" 
        value="<?= $user['full_name'] ?>" 
        class="form-control" 
        required
    >

    <input 
        type="email" 
        name="email" 
        value="<?= $user['email'] ?>" 
        class="form-control" 
        required
    >

    <button type="submit" name="update" class="btn-save">

        💾 حفظ التعديل

    </button>

</form>

</div>

<script src="assets/js/admin-table-tools.js?v=20260426-final"></script>

</div>
</div>
</div>
</body>
</html>