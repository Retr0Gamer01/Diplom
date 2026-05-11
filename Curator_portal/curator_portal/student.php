<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: index.php');
    exit;
}

require_once 'includes/db.php';

$userId = $_SESSION['user_id'];
$fullName = $_SESSION['full_name'];
$group = $_SESSION['group_name'];

// Фильтрация по дате
$startStr = null;
$endStr = null;
$startSQL = null;
$endSQL = null;

if (isset($_GET['period']) && strpos($_GET['period'], ' - ') !== false) {
    [$startStr, $endStr] = explode(' - ', $_GET['period']);
    $startSQL = DateTime::createFromFormat('d.m.Y', $startStr)?->format('Y-m-d 00:00:00');
    $endSQL = DateTime::createFromFormat('d.m.Y', $endStr)?->format('Y-m-d 23:59:59');
}

// ==== ФИЛЬТР ПО ДАТЕ ====

$startSQL = null;
$endSQL = null;
$startStr = null;
$endStr = null;

if (!empty($_GET['period'])) {
    $period = trim($_GET['period']);

    if (strpos($period, ' - ') !== false) {
        // диапазон
        [$startStr, $endStr] = explode(' - ', $period);
    } else {
        // одиночная дата
        $startStr = $endStr = $period;
    }

    $startSQL = DateTime::createFromFormat('d.m.Y', $startStr)?->format('Y-m-d 00:00:00');
    $endSQL = DateTime::createFromFormat('d.m.Y', $endStr)?->format('Y-m-d 23:59:59');
}

// ==== СОРТИРОВКА ====

$allowedSort = ['original_name', 'uploaded_at'];
$sort = in_array($_GET['sort'] ?? '', $allowedSort) ? $_GET['sort'] : 'uploaded_at';
$dir = ($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

// ==== SQL ====

$sql = "SELECT * FROM documents WHERE user_id = ?";
$params = [$userId];

if ($startSQL && $endSQL) {
    $sql .= " AND uploaded_at BETWEEN ? AND ?";
    $params[] = $startSQL;
    $params[] = $endSQL;
}

$sql .= " ORDER BY $sort $dir";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$documents = $stmt->fetchAll();

// Функция генерации заголовков с сортировкой
function sort_link($label, $column)
{
    $currentSort = $_GET['sort'] ?? '';
    $currentDir = $_GET['dir'] ?? 'asc';

    $nextDir = ($currentSort === $column && $currentDir === 'asc') ? 'desc' : 'asc';

    $arrow = '<span style="color:#ccc;">↓</span>'; // по умолчанию

    if ($currentSort === $column) {
        $arrow = $currentDir === 'asc'
            ? '<span style="color:#000;">↑</span>'
            : '<span style="color:#000;">↓</span>';
    }

    $params = $_GET;
    $params['sort'] = $column;
    $params['dir'] = $nextDir;
    $query = http_build_query($params);

    return "<a href=\"?{$query}\" style=\"text-decoration:none; color:inherit\">{$label} {$arrow}</a>";
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Мои документы</title>
    <link rel="stylesheet" href="assets/css/student.css">
    <!-- Подключение flatpickr для фильтра -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="shortcut icon" href="/assets/icon//free-icon-graduation-hat-8276809.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
</head>

<body>
    <div class="student-page">
        <!-- Контейнер -->
        <div class="container_student">
            <h2 class="page-title">Документы</h2>
            <div class="top-bar">
                <!-- Датапикер -->
                <div class="filter-block">
                    <form method="get" id="filterForm" class="filter-block" style="display: flex; gap: 10px; align-items: flex-end;">
                        <label for="dateRange">Период:</label>
                        <div class="date-picker-wrapper">
                            <input type="text" id="dateRange" name="period" placeholder="Выберите диапазон" value="<?= htmlspecialchars($_GET['period'] ?? '') ?>">
                            <img src="assets/icon/data.png" class="calendar-icon" alt="calendar">
                        </div>
                    </form>
                </div>
                <!-- Кнопка загрузки документа -->
                <div class="upload-block">
                    <form id="uploadForm" action="src/actions/upload.php" method="post" enctype="multipart/form-data">
                        <input type="file" name="document" id="documentInput" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.txt" style="display: none;">
                        <button type="button" class="btn" id="triggerUpload">Добавить документ</button>
                    </form>
                </div>
            </div>

            <!-- Перечень документов -->
            <main class="content">
                <div class="table-wrapper">
                    <table class="documents-table">
                        <thead>
                            <tr>
                                <th><?= sort_link('Наименование файла', 'original_name') ?></th>
                                <th><?= sort_link('Дата загрузки', 'uploaded_at') ?></th>
                                <th>ФИО студента</th>
                                <th>Группа</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($documents) === 0): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 20px; color: #888;">
                                        За данный период нет загруженных документов
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($documents as $doc): ?>
                                    <tr class="doc-row" data-id="<?= $doc['id'] ?>" data-file="<?= $doc['filename'] ?>">
                                        <td><?= htmlspecialchars($doc['original_name']) ?></td>
                                        <td><?= date('d.m.Y', strtotime($doc['uploaded_at'])) ?></td>
                                        <td><?= htmlspecialchars($fullName) ?></td>
                                        <td style="position: relative;">
                                            <?= htmlspecialchars($group) ?>
                                            <div class="row-actions">
                                                <?php
                                                // Собираем текущие GET-параметры, кроме id
                                                $queryParams = $_GET;
                                                $queryString = http_build_query($queryParams);
                                                ?>

                                                <a href="src/actions/download.php?id=<?= $doc['id'] ?>&<?= $queryString ?>" title="Скачать">
                                                    <img src="assets/icon/download.png" alt="скачать">
                                                </a>

                                                <a href="src/actions/delete.php?id=<?= $doc['id'] ?>&<?= $queryString ?>" title="Удалить" onclick="return confirm('Удалить документ?')">
                                                    <img src="assets/icon/del.png" alt="удалить">
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </main>
        </div>
        <aside class="sidebar-fixed">
            <div class="avatar-section">
                <img src="assets/icon/icon_profile.png" alt="Аватар" class="avatar-fixed">
            </div>

            <div class="menu-section">
                <div class="menu-icon active">
                    <img src="assets/icon/add_screen.png" alt="Документы">
                </div>
            </div>

            <div class="logout-section">
                <a href="src/actions/logout.php" class="logout-icon" title="Выход">
                    <img src="assets/icon/exit.png" alt="Выход">
                </a>
            </div>
        </aside>
    </div>

    <!-- flatpickr -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        flatpickr("#dateRange", {
            mode: "range",
            dateFormat: "d.m.Y",
            locale: {
                rangeSeparator: " - "
            },
            allowInput: true,
            defaultDate: <?php
                            if (!empty($_GET['period'])) {
                                $dates = explode(' - ', $_GET['period']);
                                echo json_encode($dates);
                            } else {
                                echo '[]';
                            }
                            ?>,
            onClose: function(selectedDates) {
                if (selectedDates.length >= 1) {
                    document.getElementById('filterForm').submit();
                }
            }
        });
    </script>
    <script src="assets/JS/student.js"></script>
</body>

</html>