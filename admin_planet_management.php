<?php
require_once 'includes/db.php';
function h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
$currentYear = (int)date('Y');
$result = $conn->query('SELECT id, region_name, plant_name, plant_count, year, created_at FROM vegetation_cover_admin ORDER BY year DESC, id DESC');
$rows = [];
if ($result) { while ($row = $result->fetch_assoc()) { $rows[] = $row; } }
include 'includes/admin_header.php';
include 'includes/admin_sidebar.php';
include 'includes/admin_navbar.php';
?>
<div class="content-wrapper admin-structured-page admin-crud-page admin-vegetation-page">
    <div class="page-head"><div><h3>🌿 إدارة الغطاء النباتي</h3></div><a href="admin_reports.php" class="btn-soft btn-report"><i class="ri-file-chart-line"></i> التقارير الكاملة</a></div>
    <div class="toolbar"><input type="text" id="searchInput" class="search-box" placeholder="🔍 بحث عن منطقة أو نبتة..."><select id="yearFilter" class="year-select"><option value="all">كل السنوات</option><?php for ($y=1990; $y <= $currentYear; $y++): ?><option value="<?= $y ?>"><?= $y ?><?= $y===$currentYear ? ' - السنة الحالية' : '' ?></option><?php endfor; ?></select><button type="button" class="btn-soft btn-export" data-export-table="vegetation_cover.csv"><i class="ri-download-2-line"></i> تصدير الصفحة</button><button type="button" class="btn-soft btn-reset" data-reset-filters><i class="ri-refresh-line"></i> إعادة ضبط</button></div>
    <div class="table-card table-responsive"><table id="dataTable" class="table table-hover text-center sortable-table"><thead><tr><th>المنطقة <span class="sort-icons">↕</span></th><th>النبتة <span class="sort-icons">↕</span></th><th>العدد <span class="sort-icons">↕</span></th><th>السنة <span class="sort-icons">↕</span></th><th>التاريخ <span class="sort-icons">↕</span></th><th data-sortable="false">العمليات</th></tr></thead><tbody>
        <?php foreach ($rows as $row): ?>
        <tr data-year="<?= h($row['year']) ?>"><td class="fw-bold"><i class="bi bi-geo-alt-fill text-success"></i> <?= h($row['region_name']) ?></td><td><?= h($row['plant_name']) ?></td><td><span class="count-badge"><?= number_format((float)$row['plant_count']) ?></span></td><td><span class="year-badge"><?= h($row['year']) ?></span></td><td><span class="date-badge"><?= h(substr((string)$row['created_at'],0,10)) ?></span></td><td><button class="btn-edit open-edit" data-id="<?= (int)$row['id'] ?>" data-name="<?= h($row['plant_name']) ?>" data-count="<?= h($row['plant_count']) ?>" data-region="<?= h($row['region_name']) ?>" data-year="<?= h($row['year']) ?>" data-date="<?= h(substr((string)$row['created_at'],0,10)) ?>"><i class="bi bi-pencil"></i></button> <a href="delete_vegetation.php?id=<?= (int)$row['id'] ?>" class="btn-delete" data-confirm="هل تريد حذف هذا السجل؟"><i class="bi bi-trash"></i></a></td></tr>
        <?php endforeach; ?>
    </tbody></table></div>
</div>
<div id="editModal" class="modal"><div class="modal-content-custom"><span class="close">&times;</span><h4 class="fw-bold mb-3">تعديل بيانات الغطاء النباتي</h4><form method="POST" action="update_vegetation.php"><input type="hidden" name="id" id="edit_id"><input type="text" name="plant_name" id="edit_name" placeholder="اسم النبتة"><input type="number" name="plant_count" id="edit_count" placeholder="العدد"><input type="text" name="region_name" id="edit_region" placeholder="المنطقة"><input type="number" name="year" id="edit_year" min="1990" max="<?= $currentYear ?>" placeholder="السنة"><input type="date" name="created_at" id="edit_date"><button type="submit" class="save-btn">💾 حفظ التعديل</button></form></div></div>
<script src="assets/js/admin-table-tools.js?v=20260426-final"></script>

</div>
</div>
</div>
</body>
</html>
