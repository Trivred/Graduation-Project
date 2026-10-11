<?php
require_once 'includes/db.php';
function h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
$flash = '';
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id > 0) {
        $stmt = $conn->prepare('DELETE FROM rare_plants WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        header('Location: admin_rare.php?deleted=1');
        exit();
    }
}
if (isset($_GET['deleted'])) { $flash = 'تم حذف النبات النادر بنجاح.'; }
$query = "
SELECT rp.id, rp.plant_name, rp.rarity_level, rp.conservation_status, rp.latitude, rp.longitude, COALESCE(r.region_name_ar,'غير محدد') AS region_name_ar
FROM rare_plants rp
LEFT JOIN regions r ON rp.region_id = r.region_id
ORDER BY rp.id DESC
";
$result = $conn->query($query);
$rows = [];
if ($result) { while ($row = $result->fetch_assoc()) { $rows[] = $row; } }
include 'includes/admin_header.php';
include 'includes/admin_sidebar.php';
include 'includes/admin_navbar.php';
?>
<div class="content-wrapper admin-structured-page admin-crud-page admin-rare-page">
    <div class="page-head"><div><h3><i class="bi bi-star-fill"></i> إدارة النباتات النادرة</h3></div><a href="admin_reports.php" class="btn-soft btn-report"><i class="ri-file-chart-line"></i> التقارير الكاملة</a></div>
    <?php if ($flash): ?><div class="alert-soft"><?= h($flash) ?></div><?php endif; ?>
    <div class="toolbar"><input type="text" id="searchInput" class="search-box" placeholder="🔍 ابحث عن نبتة أو منطقة أو حالة..."><button type="button" class="btn-soft btn-export" data-export-table="rare_plants.csv"><i class="ri-download-2-line"></i> تصدير الصفحة</button></div>
    <div class="table-card table-responsive"><table id="dataTable" class="table table-hover text-center sortable-table"><thead><tr><th>المنطقة <span class="sort-icons">↕</span></th><th>النبتة <span class="sort-icons">↕</span></th><th>الندرة <span class="sort-icons">↕</span></th><th>الحالة <span class="sort-icons">↕</span></th><th>الإحداثيات <span class="sort-icons">↕</span></th><th data-sortable="false">العمليات</th></tr></thead><tbody>
    <?php foreach ($rows as $row): ?>
        <tr><td><div class="region"><i class="bi bi-geo-alt-fill text-primary"></i><?= h($row['region_name_ar']) ?></div></td><td class="fw-bold"><?= h($row['plant_name']) ?></td><td><span class="badge-rarity"><?= h($row['rarity_level']) ?></span></td><td><span class="badge-status"><?= h($row['conservation_status']) ?></span></td><td><span class="badge-geo"><?= number_format((float)$row['latitude'],4) ?></span><span class="badge-geo"><?= number_format((float)$row['longitude'],4) ?></span></td><td><button class="btn-edit open-edit" data-id="<?= (int)$row['id'] ?>" data-name="<?= h($row['plant_name']) ?>" data-rarity="<?= h($row['rarity_level']) ?>" data-status="<?= h($row['conservation_status']) ?>" data-lat="<?= h($row['latitude']) ?>" data-lng="<?= h($row['longitude']) ?>"><i class="bi bi-pencil"></i></button> <a href="admin_rare.php?delete=<?= (int)$row['id'] ?>" class="btn-delete" data-confirm="هل تريد حذف هذا النبات؟"><i class="bi bi-trash"></i></a></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
</div>
<div id="modal" class="modal"><div class="modal-content-custom"><span class="close">&times;</span><h4 class="fw-bold mb-3">تعديل النبات النادر</h4><form method="POST" action="update_rare.php"><input type="hidden" name="id" id="id"><input type="text" name="plant_name" id="name" placeholder="اسم النبتة" required><input type="text" name="rarity_level" id="rarity" placeholder="مستوى الندرة"><input type="text" name="conservation_status" id="status" placeholder="الحالة"><input type="text" name="latitude" id="lat" placeholder="خط العرض"><input type="text" name="longitude" id="lng" placeholder="خط الطول"><button type="submit" class="save-btn">💾 حفظ التعديل</button></form></div></div>
<script src="assets/js/admin-table-tools.js?v=20260426-final"></script>

</div>
</div>
</div>
</body>
</html>
