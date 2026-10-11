<?php
require_once 'includes/db.php';
function h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
$flash = '';
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id > 0) {
        $stmt = $conn->prepare('DELETE FROM toxic_plants WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        header('Location: admin_toxic.php?deleted=1');
        exit();
    }
}
if (isset($_GET['deleted'])) { $flash = 'تم حذف النبات السام بنجاح.'; }
$result = $conn->query('SELECT id, plant_name, latitude, longitude, symptoms FROM toxic_plants ORDER BY id DESC');
$rows = [];
if ($result) { while ($row = $result->fetch_assoc()) { $rows[] = $row; } }
include 'includes/admin_header.php';
include 'includes/admin_sidebar.php';
include 'includes/admin_navbar.php';
?>
<div class="content-wrapper admin-structured-page admin-crud-page admin-toxic-page">
    <div class="page-head"><div><h3><i class="bi bi-radioactive"></i> إدارة النباتات السامة</h3></div><a href="admin_reports.php" class="btn-soft btn-report"><i class="ri-file-chart-line"></i> التقارير الكاملة</a></div>
    <?php if ($flash): ?><div class="alert-soft"><?= h($flash) ?></div><?php endif; ?>
    <div class="toolbar"><input type="text" id="searchInput" class="search-box" placeholder="🔍 ابحث عن نبات أو أعراض أو إحداثيات..."><button type="button" class="btn-soft btn-export" data-export-table="toxic_plants.csv"><i class="ri-download-2-line"></i> تصدير الصفحة</button></div>
    <div class="table-card table-responsive"><table id="dataTable" class="table table-hover text-center sortable-table"><thead><tr><th>النبتة <span class="sort-icons">↕</span></th><th>الإحداثيات <span class="sort-icons">↕</span></th><th>الأعراض <span class="sort-icons">↕</span></th><th data-sortable="false">العمليات</th></tr></thead><tbody>
    <?php foreach ($rows as $row): ?>
        <tr><td class="fw-bold"><?= h($row['plant_name']) ?></td><td><span class="badge-geo"><?= number_format((float)$row['latitude'],4) ?></span><span class="badge-geo"><?= number_format((float)$row['longitude'],4) ?></span></td><td class="symptoms"><?= nl2br(h($row['symptoms'])) ?></td><td><button class="btn-edit open-edit" data-id="<?= (int)$row['id'] ?>" data-name="<?= h($row['plant_name']) ?>" data-lat="<?= h($row['latitude']) ?>" data-lng="<?= h($row['longitude']) ?>" data-symptoms="<?= h($row['symptoms']) ?>"><i class="bi bi-pencil"></i></button> <a href="admin_toxic.php?delete=<?= (int)$row['id'] ?>" class="btn-delete" data-confirm="هل تريد حذف هذا النبات؟"><i class="bi bi-trash"></i></a></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
</div>
<div id="modal" class="modal"><div class="modal-content-custom"><span class="close">&times;</span><h4 class="fw-bold mb-3">تعديل النبات السام</h4><form method="POST" action="update_toxic.php"><input type="hidden" name="id" id="id"><input type="text" name="plant_name" id="name" placeholder="اسم النبات" required><textarea name="symptoms" id="symptoms" rows="4" placeholder="الأعراض"></textarea><input type="text" name="latitude" id="lat" placeholder="خط العرض"><input type="text" name="longitude" id="lng" placeholder="خط الطول"><button type="submit" class="save-btn">💾 حفظ التعديل</button></form></div></div>
<script src="assets/js/admin-table-tools.js?v=20260426-final"></script>

</div>
</div>
</div>
</body>
</html>
