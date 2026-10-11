<?php
require_once 'includes/db.php';
function h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
$flash = '';
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id > 0) {
        $stmt = $conn->prepare('DELETE FROM plant_diseases WHERE disease_id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        header('Location: admin_plant_diseases.php?deleted=1');
        exit();
    }
}
if (isset($_POST['update'])) {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $severity = trim($_POST['severity'] ?? '');
    $crops = trim($_POST['crops'] ?? '');
    $transmission = trim($_POST['transmission'] ?? '');
    $region = trim($_POST['region'] ?? '');
    if ($id > 0 && $name !== '') {
        $stmt = $conn->prepare('UPDATE plant_diseases SET name=?, disease_type=?, severity=?, affected_crops=?, transmission_mode=?, preferred_region=? WHERE disease_id=?');
        $stmt->bind_param('ssssssi', $name, $type, $severity, $crops, $transmission, $region, $id);
        $stmt->execute();
        $stmt->close();
        header('Location: admin_plant_diseases.php?updated=1');
        exit();
    }
}
if (isset($_GET['deleted'])) $flash = 'تم حذف المرض النباتي بنجاح.';
if (isset($_GET['updated'])) $flash = 'تم تحديث المرض النباتي بنجاح.';
$result = $conn->query('SELECT disease_id, name, disease_type, severity, affected_crops, transmission_mode, preferred_region FROM plant_diseases ORDER BY disease_id DESC');
$rows = [];
if ($result) { while ($row = $result->fetch_assoc()) { $rows[] = $row; } }
include 'includes/admin_header.php';
include 'includes/admin_sidebar.php';
include 'includes/admin_navbar.php';
?>
<div class="content-wrapper admin-structured-page admin-crud-page admin-disease-page">
    <div class="page-head"><div><h3><i class="bi bi-virus2"></i> إدارة الأوبئة النباتية</h3></div><a href="admin_reports.php" class="btn-soft btn-report"><i class="ri-file-chart-line"></i> التقارير الكاملة</a></div>
    <?php if ($flash): ?><div class="alert-soft"><?= h($flash) ?></div><?php endif; ?>
    <div class="toolbar"><input type="text" id="searchInput" class="search-box" placeholder="🔍 ابحث عن مرض أو نوع أو محصول أو منطقة..."><button type="button" class="btn-soft btn-export" data-export-table="plant_diseases.csv"><i class="ri-download-2-line"></i> تصدير الصفحة</button></div>
    <div class="table-card table-responsive"><table id="dataTable" class="table table-hover align-middle text-center sortable-table"><thead><tr><th>الأمراض والافات  <span class="sort-icons">↕</span></th><th>النوع <span class="sort-icons">↕</span></th><th>الخطورة <span class="sort-icons">↕</span></th><th>المحاصيل المصابة <span class="sort-icons">↕</span></th><th>طريقة الانتقال <span class="sort-icons">↕</span></th><th>المنطقة <span class="sort-icons">↕</span></th><th data-sortable="false">العمليات</th></tr></thead><tbody>
    <?php foreach ($rows as $row): $sev = strtolower((string)$row['severity']); $sevClass = ($sev==='high'||$sev==='مرتفع') ? 'sev-high' : (($sev==='medium'||$sev==='متوسط') ? 'sev-medium' : 'sev-low'); ?>
        <tr><td><strong><?= h($row['name']) ?></strong></td><td><?= h($row['disease_type']) ?></td><td><span class="badge-soft <?= $sevClass ?>"><?= h($row['severity']) ?></span></td><td><span class="badge-soft badge-green"><?= h($row['affected_crops']) ?></span></td><td><?= h($row['transmission_mode']) ?></td><td><span class="badge-soft badge-gray"><?= h($row['preferred_region']) ?></span></td><td><button type="button" class="btn-edit open-edit-row" data-row='<?= h(json_encode($row, JSON_UNESCAPED_UNICODE|JSON_HEX_APOS|JSON_HEX_QUOT)) ?>'><i class="bi bi-pencil"></i></button> <a href="?delete=<?= (int)$row['disease_id'] ?>" data-confirm="هل أنت متأكد من الحذف؟" class="btn-delete"><i class="bi bi-trash"></i></a></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
</div>
<div id="editModal" class="modal-box"><div class="modal-card"><h5 class="fw-bold mb-3">تعديل المرض النباتي</h5><form method="POST"><input type="hidden" name="id" id="edit_id"><input type="text" name="name" id="edit_name" placeholder="اسم المرض" required><input type="text" name="type" id="edit_type" placeholder="النوع"><select name="severity" id="edit_severity"><option value="low">low</option><option value="medium">medium</option><option value="high">high</option><option value="منخفض">منخفض</option><option value="متوسط">متوسط</option><option value="مرتفع">مرتفع</option></select><input type="text" name="crops" id="edit_crops" placeholder="المحاصيل"><input type="text" name="transmission" id="edit_transmission" placeholder="طريقة الانتقال"><input type="text" name="region" id="edit_region" placeholder="المنطقة"><button type="submit" name="update" class="btn btn-success mt-2 w-100">حفظ</button><button type="button" data-close-modal class="btn btn-secondary mt-2 w-100">إلغاء</button></form></div></div>
<script src="assets/js/admin-table-tools.js?v=20260426-final"></script>

</div>
</div>
</div>
</body>
</html>
