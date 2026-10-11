<?php
require_once __DIR__ . '/lang.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login_register.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>عرض النباتات</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/user-shared.css">
</head>
<body>
<?php include __DIR__ . '/includes/user_sidebar.php'; ?>
<main class="main">
    <section class="page-header">
        <div>
            <h1 class="page-title"><i class="bi bi-tree"></i> عرض الغطاء النباتي </h1>
        </div>
        <div class="actions">
            <button class="export-btn" type="button" data-export-plants><i class="bi bi-file-earmark-excel"></i> تصدير Excel</button>
        </div>
    </section>

    <section class="toolbar" aria-label="أدوات البحث والتصفية">
        <div class="control-card">
            <label for="search">بحث باسم المنطقة أو النبتة</label>
            <input id="search" type="search" placeholder="اكتب كلمة البحث هنا...">
        </div>
        <div class="control-card">
            <label for="yearSelect">السنة</label>
            <select id="yearSelect">
                <option value="all">كل السنوات</option>
            </select>
        </div>
        <div class="actions">
            <button class="btn btn-soft" type="button" data-reset-plants><i class="bi bi-arrow-counterclockwise"></i> إعادة ضبط</button>
        </div>
    </section>

    <section class="table-box">
        <table id="plantsTable" data-sortable-table>
            <thead>
                <tr>
                    <th data-index="0" data-type="number"><span class="sort-icons">▲▼</span>السنة</th>
                    <th data-index="1"><span class="sort-icons">▲▼</span>المنطقة</th>
                    <th data-index="2"><span class="sort-icons">▲▼</span>النبتة</th>
                    <th data-index="3" data-type="number"><span class="sort-icons">▲▼</span>العدد</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <tr class="empty-row"><td colspan="4">جاري تحميل البيانات...</td></tr>
            </tbody>
        </table>
        <div class="note">يتم تحميل البيانات من ملف fetch_user_plants.php مع دعم البحث والسنة والتصدير.</div>
    </section>
</main>
<script src="assets/js/app-core.js"></script>
<script src="assets/js/sidebar.js"></script>
<script src="assets/js/user-plants.js"></script>
</body>
</html>
