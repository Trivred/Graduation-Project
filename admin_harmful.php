<?php
require_once 'includes/db.php';
function h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id > 0) {
        $stmt = $conn->prepare('DELETE FROM plant_hazards WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        header('Location: admin_harmful.php?deleted=1');
        exit();
    }
}
$query = "
SELECT 
    ph.id,
    ph.plant_id,
    COALESCE(pt.local_name_ar, ph.plant_id) AS local_name_ar,
    ph.hazard_type,
    ph.severity_level,
    ph.description,
    COALESCE(tp.latitude, rp.latitude, pb.latitude, 0) AS latitude,
    COALESCE(tp.longitude, rp.longitude, pb.longitude, 0) AS longitude,
    COALESCE(r.region_name_ar, 'غير محدد') AS region_name_ar
FROM plant_hazards ph
LEFT JOIN plant_types pt ON ph.plant_id = pt.plant_id
LEFT JOIN regions r ON pt.region_id = r.region_id
LEFT JOIN toxic_plants tp ON pt.plant_id = tp.plant_id
LEFT JOIN rare_plants rp ON pt.plant_id = rp.plant_id
LEFT JOIN plant_benefits pb ON pt.plant_id = pb.plant_id
ORDER BY ph.id DESC
";
$result = $conn->query($query);
$rows = [];
if ($result) { while ($row = $result->fetch_assoc()) { $rows[] = $row; } }
include 'includes/admin_header.php';
include 'includes/admin_sidebar.php';
include 'includes/admin_navbar.php';
?>
<div class="content-wrapper admin-structured-page admin-crud-page admin-harmful-page">
    <div class="page-head"><div><h3><i class="bi bi-exclamation-octagon-fill"></i> إدارة النباتات الضارة</h3><p>بحث، فرز من الهيدر، وتصدير السجلات الحالية.</p></div><a href="admin_reports.php" class="btn-soft btn-report"><i class="ri-file-chart-line"></i> التقارير الكاملة</a></div>
    <?php if (isset($_GET['deleted'])): ?><div class="alert-soft">تم حذف السجل بنجاح.</div><?php endif; ?>
    <div class="toolbar"><input type="text" id="searchInput" class="search-box" placeholder="🔍 ابحث عن منطقة، نبتة، نوع ضرر، مستوى خطورة..."><button type="button" class="btn-soft btn-export" data-export-table="harmful_plants.csv"><i class="ri-download-2-line"></i> تصدير الصفحة</button></div>
    <div class="table-card table-responsive"><table id="dataTable" class="table table-hover text-center sortable-table"><thead><tr><th>المنطقة <span class="sort-icons">↕</span></th><th>النبتة <span class="sort-icons">↕</span></th><th>نوع الضرر <span class="sort-icons">↕</span></th><th>مستوى الخطورة <span class="sort-icons">↕</span></th><th>الإحداثيات <span class="sort-icons">↕</span></th><th>توضيح <span class="sort-icons">↕</span></th><th data-sortable="false">العمليات</th></tr></thead><tbody>
        <?php foreach ($rows as $row): ?>
        <tr><td><i class="bi bi-geo-alt-fill text-danger"></i> <?= h($row['region_name_ar']) ?></td><td class="fw-bold"><?= h($row['local_name_ar']) ?></td><td><span class="hazard-badge"><?= h($row['hazard_type']) ?></span></td><td><span class="level"><?= h($row['severity_level']) ?></span></td><td><span class="badge-geo"><?= number_format((float)$row['latitude'],4) ?></span><span class="badge-geo"><?= number_format((float)$row['longitude'],4) ?></span></td><td class="desc"><?= nl2br(h($row['description'])) ?></td><td><a href="admin_plant_edit.php?id=<?= (int)$row['plant_id'] ?>" class="btn-edit"><i class="bi bi-pencil-square"></i> تعديل</a><a href="admin_harmful.php?delete=<?= (int)$row['id'] ?>" class="btn-delete" data-confirm="متأكد من الحذف؟"><i class="bi bi-trash3"></i> حذف</a></td></tr>
        <?php endforeach; ?>
    </tbody></table></div>
</div>
<script src="assets/js/admin-table-tools.js?v=20260426-final"></script>

</div>
</div>
</div>
</body>
</html>
