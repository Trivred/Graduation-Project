<?php
session_start();

$pageTitle = 'رفع بيانات النباتات';
$adminTitle = 'رفع بيانات النباتات';
$pageBodyClass = 'plant-import-page';

require_once 'includes/db.php';
require_once 'includes/log_activity.php';
require_once 'includes/plant_import_config.php';
require_once 'includes/plant_import_service.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$selectedCategory = plant_import_valid_category($_POST['category'] ?? ($_GET['category'] ?? 'beneficial'));
$categories = plant_import_categories();
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $selectedCategory = plant_import_valid_category($_POST['category'] ?? 'beneficial');

    $flash = handle_plant_import($conn, $_POST, $_FILES);


    if ($flash && $flash['type'] === 'success') {

        $categoryName = $categories[$selectedCategory]['label'] ?? 'نباتات';

        logActivity(
            $conn,
            $_SESSION['user_id'],
            'Add Plant',
            'تم رفع واستيراد بيانات فئة: ' . $categoryName
        );
    }
}

include 'includes/admin_header.php';
include 'includes/admin_sidebar.php';
include 'includes/admin_navbar.php';
?>

<link rel="stylesheet" href="assets/css/plant-import.css?v=20260505-clean-ui">

<div class="plant-import-shell">

    <div class="plant-import-head">

        <div>

            <h3>
                <i class="bi bi-cloud-upload-fill"></i>
                رفع بيانات النباتات
            </h3>

        </div>

    </div>

    <?php if ($flash): ?>

        <div class="import-alert <?= h($flash['type']) ?>">

            <h5><?= h($flash['title']) ?></h5>

            <p><?= h($flash['message']) ?></p>

            <?php if (!empty($flash['errors'])): ?>

                <ul>

                    <?php foreach ($flash['errors'] as $error): ?>

                        <li><?= h($error) ?></li>

                    <?php endforeach; ?>

                </ul>

            <?php endif; ?>

        </div>

    <?php endif; ?>

    <form class="import-card" method="post" enctype="multipart/form-data">

        <h4>

            <i class="bi bi-ui-checks-grid"></i>

            بيانات الرفع

        </h4>

        <div class="form-block">

            <label class="block-label">
                اختر الفئة
            </label>

            <div class="category-grid">

                <?php foreach ($categories as $key => $category): ?>

                    <label class="category-option">

                        <input
                            type="radio"
                            name="category"
                            value="<?= h($key) ?>"
                            <?= $selectedCategory === $key ? 'checked' : '' ?>
                        >

                        <div class="category-box">

                            <i 
                                class="<?= h($category['icon']) ?>" 
                                style="background: <?= h($category['color']) ?>">
                            </i>

                            <div class="category-text">

                                <strong>
                                    <?= h($category['label']) ?>
                                </strong>

                            </div>

                            <a
                                class="category-excel-btn"
                                href="download_plant_template.php?category=<?= h($key) ?>&format=xls"
                                title="تحميل قالب Excel"
                                data-download-template
                            >

                                <i class="bi bi-file-earmark-excel"></i>

                                قالب Excel

                            </a>

                        </div>

                    </label>

                <?php endforeach; ?>

            </div>

        </div>

        <div class="form-block">

            <label class="block-label">
                ملف البيانات
            </label>

            <div class="file-zone">

                <label for="fileInput" class="file-btn">

                    <i class="bi bi-upload"></i>

                    ارفع ملف Excel / CSV

                </label>

                <input
                    id="fileInput"
                    type="file"
                    name="excel"
                    accept=".csv,.txt,.xls,.xlsx"
                    required
                >

                <span id="fileName">
                    لم يتم اختيار ملف بعد
                </span>

            </div>

        </div>

        <label class="server-option" for="saveToServer">

            <input 
                id="saveToServer" 
                type="checkbox" 
                name="save_to_server" 
                value="1"
            >

            <span>

                <strong>

                    <i class="bi bi-hdd-network"></i>

                    حفظ نسخة الملف على السيرفر

                </strong>

            </span>

        </label>

        <button type="submit" class="submit-btn">

            <i class="bi bi-database-add"></i>

            رفع واستيراد البيانات

        </button>

    </form>

</div>

<script src="assets/js/plant-import.js?v=20260505-clean-ui" defer></script>

</body>
</html>