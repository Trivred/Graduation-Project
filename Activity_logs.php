<?php
session_start();

include 'includes/db.php';
include 'includes/admin_header.php';
include 'includes/admin_sidebar.php';
include 'includes/admin_navbar.php';

$sql = "
    SELECT activity_logs.*, users.username AS user_name
    FROM activity_logs
    LEFT JOIN users ON activity_logs.user_id = users.user_id
    ORDER BY activity_logs.created_at DESC
";

$result = mysqli_query($conn, $sql);

if(!$result){
    die(mysqli_error($conn));
}
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap');

    body {
        direction: rtl;
        text-align: right;
        background-color: #f4f7f6;
        font-family: 'Cairo', sans-serif; 
        margin: 20px;
        color: #333;
    }

    .page-header {
        background: linear-gradient(135deg, #198754, #14532d);
        color: white;
        padding: 30px;
        border-radius: 20px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(25, 135, 84, 0.15);
    }

    .page-header h2 { font-weight: 800; margin: 0; font-size: 28px; }

    .table-container {
        background: white;
        border-radius: 20px;
        padding: 25px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
    }

    .search-box {
        border-radius: 12px;
        padding: 10px 20px;
        border: 2px solid #e9ecef;
        outline: none;
        transition: 0.3s;
    }

    .search-box:focus { border-color: #198754; box-shadow: 0 0 0 4px rgba(25, 135, 84, 0.1); }

    .logs-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        border-radius: 15px;
        overflow: hidden;
        border: 1px solid #edf2f0;
    }

    .logs-table thead { background: #f8faf9; }

    .logs-table th {
        color: #0d4b3e;
        padding: 18px;
        font-weight: 700;
        text-align: right;
        border-bottom: 2px solid #e9ecef;
    }

    .head-flex { display: flex; align-items: center; gap: 8px; }

    .logs-table tbody td {
        padding: 16px;
        border-bottom: 1px solid #f1f5f3;
        font-size: 14px;
        text-align: right;
    }

    .btn-export {
        background-color: #ffffff;
        color: #198754;
        border: 2px solid #198754;
        font-weight: 700;
        border-radius: 10px;
        transition: 0.3s;
    }

    .btn-export:hover {
        background-color: #198754;
        color: white;
    }

    .badge {
        padding: 8px 12px;
        border-radius: 8px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    
    .date-cell { direction: ltr; font-family: monospace; font-size: 13px; }
</style>

<div class="container-fluid p-4">

    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <h2><i class="bi bi-clock-history"></i> سجل العمليات </h2>
            <button onclick="exportToExcel()" class="btn btn-export">
                <i class="bi bi-file-earmark-excel-fill"></i> تصدير إلى Excel
            </button>
        </div>
    </div>

    <div class="table-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0"><i class="bi bi-shield-check"></i> سجل نشاطات النظام</h4>
            <input type="text" id="searchInput" class="form-control search-box w-25" placeholder="ابحث في السجلات...">
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle logs-table" id="logsTable">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th><div class="head-flex"><i class="bi bi-person-circle"></i> المستخدم</div></th>
                        <th><div class="head-flex"><i class="bi bi-activity"></i> العملية</div></th>
                        <th><div class="head-flex"><i class="bi bi-info-circle"></i> التفاصيل</div></th>
                        <th><div class="head-flex"><i class="bi bi-calendar3"></i> التاريخ والوقت</div></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $counter = 1;
                    while($row = mysqli_fetch_assoc($result)):
                        $badgeClass = "bg-secondary";
                        $icon = "bi-activity";
                        $actionLabel = $row['action'];

                        if(stripos($row['action'], 'login') !== false) { $badgeClass = "bg-success"; $icon = "bi-box-arrow-in-right"; $actionLabel = "تسجيل دخول"; }
                        elseif(stripos($row['action'], 'logout') !== false) { $badgeClass = "bg-secondary"; $icon = "bi-box-arrow-right"; $actionLabel = "تسجيل خروج"; }
                        elseif(stripos($row['action'], 'admin') !== false) { $badgeClass = "bg-dark"; $icon = "bi-shield-lock"; $actionLabel = "صفحة إدارية"; }
                        elseif(stripos($row['action'], 'add') !== false) { $badgeClass = "bg-primary"; $icon = "bi-plus-circle"; $actionLabel = "إضافة"; }
                        elseif(stripos($row['action'], 'delete') !== false) { $badgeClass = "bg-danger"; $icon = "bi-trash"; $actionLabel = "حذف"; }

                        $details = $row['details'];
                        if(stripos($row['action'], 'login') !== false) $details = 'قام المستخدم بتسجيل الدخول إلى النظام';
                        elseif(stripos($row['action'], 'logout') !== false) $details = 'قام المستخدم بتسجيل الخروج من النظام';
                    ?>
                    <tr>
                        <td><?php echo $counter++; ?></td>
                        <td><strong><?php echo htmlspecialchars($row['user_name']); ?></strong></td>
                        <td>
                            <span class="badge <?php echo $badgeClass; ?>">
                                <i class="bi <?php echo $icon; ?>"></i> <?php echo $actionLabel; ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($details); ?></td>
                        <td class="date-cell"><?php echo $row['created_at']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.getElementById("searchInput").addEventListener("keyup", function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll("#logsTable tbody tr");
    rows.forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(filter) ? "" : "none";
    });
});

function exportToExcel() {
    var table = document.getElementById("logsTable");
    var wb = XLSX.utils.table_to_book(table, {sheet: "سجل العمليات"});
    XLSX.writeFile(wb, "Activity_Logs_" + new Date().toLocaleDateString() + ".xlsx");
}
</script>