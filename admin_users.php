<?php
include 'includes/db.php';

if(isset($_GET['delete'])){
    $id = (int)$_GET['delete'];

    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: admin_users.php");
    exit();
}

if(isset($_GET['promote'])){
    $id = (int)$_GET['promote'];
    $conn->query("UPDATE users SET role='admin' WHERE user_id=$id");

    header("Location: admin_users.php");
    exit();
}

if(isset($_GET['demote'])){
    $id = (int)$_GET['demote'];
    $conn->query("UPDATE users SET role='user' WHERE user_id=$id");

    header("Location: admin_users.php");
    exit();
}

$query = "SELECT * FROM users";
$result = $conn->query($query);

if(!$result){
    die("SQL Error: " . $conn->error);
}
?>
<?php include "includes/admin_header.php"; ?>
<?php include "includes/admin_sidebar.php"; ?>
<?php include "includes/admin_navbar.php"; ?>

<?php
$total_users = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$total_admins = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='admin'")->fetch_assoc()['c'];
$total_normal = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='user'")->fetch_assoc()['c'];
?>

<style>
    .users-title {
        color: #0f3b2f;
        font-weight: 900;
    }

    .users-subtitle {
        font-size: 14px;
        color: #6c757d;
        margin-top: -5px;
    }

    .users-card {
        border: none;
        border-radius: 18px;
        box-shadow: 0 8px 22px rgba(0,0,0,0.08);
        overflow: hidden;
        background: linear-gradient(135deg, #ffffff, #f4fff7);
    }

    .users-table thead {
        background: linear-gradient(135deg, #0f3b2f, #2e7d32);
        color: white;
        font-weight: bold;
    }

    .users-table th, .users-table td {
        text-align: center;
        vertical-align: middle;
        padding: 14px;
        font-size: 14px;
    }

    .users-table tbody tr {
        transition: 0.25s ease;
    }

    .users-table tbody tr:hover {
        background: rgba(46, 125, 50, 0.08);
        transform: scale(1.01);
    }

    .role-badge {
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        display: inline-block;
    }

    .role-admin {
        background: rgba(21, 101, 192, 0.15);
        color: #1565c0;
    }

    .role-user {
        background: rgba(46, 125, 50, 0.15);
        color: #2e7d32;
    }

    .role-manager {
        background: rgba(255, 152, 0, 0.15);
        color: #ff9800;
    }

    .btn-action {
        border: none;
        padding: 8px 14px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 13px;
        transition: 0.3s ease;
    }

    .btn-edit {
        background: linear-gradient(135deg, #ff9800, #ffc107);
        color: white;
        box-shadow: 0 6px 14px rgba(0,0,0,0.12);
    }

    .btn-edit:hover {
        transform: translateY(-3px);
        opacity: 0.95;
    }

    .btn-delete {
        background: linear-gradient(135deg, #d32f2f, #ef5350);
        color: white;
        box-shadow: 0 6px 14px rgba(0,0,0,0.12);
    }

    .btn-delete:hover {
        transform: translateY(-3px);
        opacity: 0.95;
    }

    .search-box {
        border-radius: 14px;
        padding: 10px 14px;
        border: 1px solid rgba(0,0,0,0.12);
        width: 260px;
        outline: none;
        transition: 0.3s ease;
    }

    .search-box:focus {
        border-color: #2e7d32;
        box-shadow: 0 0 0 4px rgba(46,125,50,0.15);
    }

    .btn-promote{
    background: linear-gradient(135deg, #1565c0, #42a5f5);
    color:white;
    box-shadow: 0 6px 14px rgba(0,0,0,0.12);
}

.btn-promote:hover{
    transform: translateY(-3px);
    opacity:0.95;
}

.btn-action i{
    margin-left:5px;
}
.btn-demote{
    background: linear-gradient(135deg, #6c757d, #9e9e9e);
    color:white;
    box-shadow: 0 6px 14px rgba(0,0,0,0.12);
}

.btn-demote:hover{
    transform: translateY(-3px);
    opacity:0.95;
}
.avatar{
    width:40px;
    height:40px;
    border-radius:50%;
    background: linear-gradient(135deg,#1e88e5,#42a5f5);
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:bold;
    font-size:16px;
    box-shadow:0 4px 10px rgba(0,0,0,0.2);
}
.stats-row{
    display:flex;
    gap:20px;
    margin-bottom:25px;
}

.stat-card{
    flex:1;
    background:rgba(255,255,255,0.7);
    backdrop-filter: blur(10px);
    border-radius:18px;
    padding:20px;
    text-align:center;
    box-shadow:0 10px 30px rgba(0,0,0,0.08);
    transition:0.3s;
}

.stat-card:hover{
    transform:translateY(-5px) scale(1.02);
}

.stat-card i{
    font-size:30px;
    margin-bottom:10px;
}

.stat-card h2{
    margin:5px 0;
    font-weight:900;
}

.stat-card p{
    font-size:13px;
    color:#777;
}

.blue{ border-top:4px solid #1e88e5; color:#1e88e5; }
.green{ border-top:4px solid #43a047; color:#43a047; }
.orange{ border-top:4px solid #fb8c00; color:#fb8c00; }

</style>

<link href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" rel="stylesheet">
<div class="container-fluid mt-4">


    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="users-title mb-1">إدارة المستخدمين</h3>
            
        </div>

        <div>
<input type="text" id="searchInput" class="search-box" placeholder="🔍 بحث عن مستخدم...">        </div>


    </div>

    <div class="stats-row">

    <div class="stat-card blue">
        <i class="ri-group-line"></i>
<h2 class="counter" data-target="<?= $total_users ?>">0</h2>        <p>إجمالي المستخدمين</p>

    </div>

    <div class="stat-card green">
        <i class="ri-shield-user-line"></i>
<h2 class="counter" data-target="<?= $total_admins ?>">0</h2>
        <p>عدد الأدمن</p>
    </div>

    <div class="stat-card orange">
        <i class="ri-user-3-line"></i>
<h2 class="counter" data-target="<?= $total_normal ?>">0</h2>
        <p>مستخدمين عاديين</p>
    </div>

</div>
    <div class="card users-card">
        <div class="card-body p-0">

            <table class="table users-table mb-0">
    <thead>
        <tr>
            <th>#</th>
            <th> الاسم</th>
            <th> البريد</th>
            <th> الدور</th>
            <th> الإجراءات</th>
        </tr>
    </thead>

<tbody id="usersTable">
        <?php while($user = $result->fetch_assoc()): ?>
    <tr>

        <td><?= $user['user_id'] ?></td>

        <td>
<div style="display:flex; align-items:center; gap:10px; justify-content:center;">

    <div class="avatar">
        <?= strtoupper(substr($user['full_name'],0,1)) ?>
    </div>

    <span><?= $user['full_name'] ?></span>

</div>
</td>

        <td><?= $user['email'] ?></td>

        <td>
            <?php
            $role = $user['role'];

            if($role == 'admin'){
                echo '<span class="role-badge role-admin"><i class="ri-shield-star-line"></i> Admin</span>';
            } else {
                echo '<span class="role-badge role-user"><i class="ri-user-line"></i> User</span>';
            }
            ?>
        </td>
<td>

<?php if($user['role'] == 'admin'): ?>
    
    <a href="?demote=<?= $user['user_id'] ?>" 
       class="btn-action btn-demote"
       onclick="return confirmDemote('<?= $user['full_name'] ?>')">
       <i class="ri-arrow-down-circle-line"></i> إلغاء الترقية
    </a>

<?php else: ?>

    <a href="?promote=<?= $user['user_id'] ?>" 
       class="btn-action btn-promote"
       onclick="return confirmPromote('<?= $user['full_name'] ?>')">
       <i class="ri-arrow-up-circle-line"></i> ترقية
    </a>

<?php endif; ?>

    <a href="edit_user.php?id=<?= $user['user_id'] ?>" 
   class="btn-action btn-edit">
   <i class="ri-edit-line"></i>
</a>

    <a href="?delete=<?= $user['user_id'] ?>" 
   class="btn-action btn-delete"
   onclick="return confirm('متأكدة من حذف المستخدم؟')">
   <i class="ri-delete-bin-6-line"></i>
</a>

</td>

    </tr>
    <?php endwhile; ?>
    </tbody>
</table>
        </div>
    </div>

</div>
<script>
function confirmPromote(name){
    return confirm("⚠️ هل أنت متأكدة من ترقية " + name + " إلى Admin؟");
}
</script>
<script>
document.getElementById("searchInput").addEventListener("keyup", function() {
    let value = this.value.toLowerCase();
    let rows = document.querySelectorAll("#usersTable tr");

    rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(value) ? "" : "none";
    });
});
</script>

<script>
function confirmPromote(name){
    return confirm("🚀 ترقية " + name + " إلى Admin؟");
}

function confirmDemote(name){
    return confirm("⚠️ إلغاء صلاحيات الأدمن عن " + name + "؟");
}
</script>

<script>
const counters = document.querySelectorAll('.counter');

counters.forEach(counter => {
    const update = () => {
        const target = +counter.getAttribute('data-target');
        const current = +counter.innerText;

        const increment = Math.ceil(target / 50);

        if(current < target){
            counter.innerText = current + increment;
            setTimeout(update, 30);
        } else {
            counter.innerText = target;
        }
    };

    update();
});
</script>