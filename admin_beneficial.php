<?php
require_once 'includes/db.php';

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$flash = '';

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    if ($id > 0) {
        $stmt = $conn->prepare('DELETE FROM plant_benefits WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        header('Location: admin_beneficial.php?deleted=1');
        exit();
    }
}

if (isset($_GET['deleted'])) {
    $flash = 'تم حذف السجل بنجاح.';
}

$query = "
    SELECT
        pb.id,
        pb.plant_id,

        COALESCE(
            name_fix.display_name,
            NULLIF(pt.local_name_ar, ''),
            'نبات نافع غير مسمى'
        ) AS local_name_ar,

        pb.latitude,
        pb.longitude,
        pb.benefit_type,
        pb.description

    FROM plant_benefits pb

    LEFT JOIN plant_types pt
        ON pb.plant_id = pt.plant_id

    LEFT JOIN (
        SELECT 1 AS benefit_id, 'السلم' AS display_name
        UNION ALL SELECT 39, 'الأراك'
        UNION ALL SELECT 40, 'الشيح'
        UNION ALL SELECT 41, 'الرمث'
        UNION ALL SELECT 42, 'الغاف'
        UNION ALL SELECT 43, 'العوسج'
        UNION ALL SELECT 44, 'السمر'
        UNION ALL SELECT 45, 'الأثل'
        UNION ALL SELECT 46, 'السدر'
        UNION ALL SELECT 47, 'الطلح'
        UNION ALL SELECT 48, 'نخيل التمر'
        UNION ALL SELECT 49, 'القرم'
        UNION ALL SELECT 50, 'الحنظل'
        UNION ALL SELECT 51, 'العشر'
        UNION ALL SELECT 52, 'الدفلى'
        UNION ALL SELECT 53, 'الحرمل'
        UNION ALL SELECT 54, 'العرعر'
        UNION ALL SELECT 55, 'الهذب'
        UNION ALL SELECT 56, 'الدوم'
        UNION ALL SELECT 57, 'الخزامى البرية'
    ) name_fix
        ON name_fix.benefit_id = pb.id

    ORDER BY pb.id DESC
";

$result = $conn->query($query);

$rows = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
}

include 'includes/admin_header.php';
include 'includes/admin_sidebar.php';
include 'includes/admin_navbar.php';
?>

<div class="content-wrapper admin-structured-page admin-crud-page admin-beneficial-page">

    <div class="page-head">
        <div>
            <h3>
                <i class="bi bi-heart-fill"></i>
                إدارة النباتات النافعة
            </h3>
        </div>

        <a href="admin_reports.php" class="btn-soft btn-report">
            <i class="ri-file-chart-line"></i>
            التقارير الكاملة
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="alert-soft">
            <?= h($flash) ?>
        </div>
    <?php endif; ?>

    <div class="toolbar">
        <input
            type="text"
            id="searchInput"
            class="search-box"
            placeholder="🔍 ابحث عن نبتة، فائدة، وصف، أو إحداثيات..."
        >

        <button
            type="button"
            class="btn-soft btn-export"
            data-export-table="beneficial_plants.csv"
        >
            <i class="ri-download-2-line"></i>
            تصدير الصفحة
        </button>
    </div>

    <div class="table-card table-responsive">
        <table class="table table-hover text-center sortable-table" id="dataTable">
            <thead>
                <tr>
                    <th>النبتة <span class="sort-icons">↕</span></th>
                    <th>الإحداثيات <span class="sort-icons">↕</span></th>
                    <th>الأهمية <span class="sort-icons">↕</span></th>
                    <th>الوصف <span class="sort-icons">↕</span></th>
                    <th data-sortable="false">العمليات</th>
                </tr>
            </thead>

            <tbody>
                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="5" style="padding: 30px; font-weight: 800; color: #64748b;">
                            لا توجد بيانات للنباتات النافعة حالياً.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td class="fw-bold">
                            <?= h($row['local_name_ar']) ?>
                        </td>

                        <td>
                            <span class="badge-geo">
                                <?= number_format((float)$row['latitude'], 4) ?>
                            </span>

                            <span class="badge-geo">
                                <?= number_format((float)$row['longitude'], 4) ?>
                            </span>
                        </td>

                        <td>
                            <span class="badge-benefit">
                                <?= h($row['benefit_type']) ?>
                            </span>
                        </td>

                        <td style="max-width:340px; white-space:normal; line-height:1.7;">
                            <?= h($row['description']) ?>
                        </td>

                        <td>
                            <button
                                class="btn-edit open-edit"
                                type="button"
                                title="تعديل"
                                data-id="<?= (int)$row['id'] ?>"
                                data-name="<?= h($row['local_name_ar']) ?>"
                                data-benefit="<?= h($row['benefit_type']) ?>"
                                data-desc="<?= h($row['description']) ?>"
                                data-lat="<?= h($row['latitude']) ?>"
                                data-lng="<?= h($row['longitude']) ?>"
                            >
                                <i class="bi bi-pencil"></i>
                            </button>

                            <a
                                href="admin_beneficial.php?delete=<?= (int)$row['id'] ?>"
                                class="btn-delete"
                                data-confirm="هل تريد حذف هذا السجل؟"
                            >
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>

        </table>
    </div>

</div>

<div id="modal" class="modal">
    <div class="modal-content-custom">
        <span class="close">&times;</span>

        <h4 class="fw-bold mb-3">
            تعديل النبات النافع
        </h4>

        <form method="POST" action="update_beneficial.php">
            <input type="hidden" name="id" id="id">

            <input
                type="text"
                id="name"
                disabled
                placeholder="اسم النبتة"
            >

            <input
                type="text"
                name="benefit_type"
                id="benefit"
                placeholder="نوع الفائدة"
                required
            >

            <textarea
                name="description"
                id="desc"
                rows="4"
                placeholder="وصف الفائدة"
            ></textarea>

            <input
                type="text"
                name="latitude"
                id="lat"
                placeholder="خط العرض"
            >

            <input
                type="text"
                name="longitude"
                id="lng"
                placeholder="خط الطول"
            >

            <button type="submit" class="save-btn">
                💾 حفظ التعديل
            </button>
        </form>
    </div>
</div>

<script src="assets/js/admin-table-tools.js?v=20260426-final"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modal');
    const closeBtn = document.querySelector('#modal .close');

    const idInput = document.getElementById('id');
    const nameInput = document.getElementById('name');
    const benefitInput = document.getElementById('benefit');
    const descInput = document.getElementById('desc');
    const latInput = document.getElementById('lat');
    const lngInput = document.getElementById('lng');

    document.querySelectorAll('.open-edit').forEach(function (button) {
        button.addEventListener('click', function () {
            idInput.value = button.dataset.id || '';
            nameInput.value = button.dataset.name || '';
            benefitInput.value = button.dataset.benefit || '';
            descInput.value = button.dataset.desc || '';
            latInput.value = button.dataset.lat || '';
            lngInput.value = button.dataset.lng || '';

            modal.style.display = 'flex';
        });
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            modal.style.display = 'none';
        });
    }

    window.addEventListener('click', function (event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
});
</script>

</body>
</html>