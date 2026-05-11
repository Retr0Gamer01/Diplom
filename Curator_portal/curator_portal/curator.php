<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'curator') {
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

$allowedSort = ['original_name', 'uploaded_at', 'full_name', 'group_name'];
$sort = in_array($_GET['sort'] ?? '', $allowedSort) ? $_GET['sort'] : 'uploaded_at';
$dir = ($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

// ==== SQL ====

// Получаем все уникальные группы
$groupsStmt = $pdo->query("SELECT DISTINCT group_name FROM users ORDER BY group_name");
$allGroups = $groupsStmt->fetchAll(PDO::FETCH_COLUMN);

// Получаем всех студентов
$studentsStmt = $pdo->query("SELECT id, full_name FROM users WHERE role = 'student' ORDER BY full_name");
$allStudents = $studentsStmt->fetchAll();

$sql = "SELECT d.*, u.full_name, u.group_name 
        FROM documents d
        JOIN users u ON d.user_id = u.id
        WHERE 1=1";
$params = [];

if ($startSQL && $endSQL) {
    $sql .= " AND d.uploaded_at BETWEEN ? AND ?";
    $params[] = $startSQL;
    $params[] = $endSQL;
}

// Фильтр по группе
$groupFilter = $_GET['group'] ?? null;
if ($groupFilter) {
    $sql .= " AND u.group_name = ?";
    $params[] = $groupFilter;
}

// Фильтр по студенту
$studentFilter = $_GET['student'] ?? null;
if ($studentFilter) {
    $sql .= " AND u.id = ?";
    $params[] = $studentFilter;
}

// Для сортировки по полям users — меняем alias
$sortColumn = in_array($sort, ['full_name', 'group_name']) ? "u.$sort" : "d.$sort";
$sql .= " ORDER BY $sort $dir";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

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
    <title>Кабинет куратора</title>
    <link rel="stylesheet" href="assets/css/curator.css">
    <!-- Подключение flatpickr для фильтра -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="shortcut icon" href="assets/icon//free-icon-graduation-hat-8276809.png" type="image/png">
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
                        <div>
                            <label for="dateRange">Период:</label>
                            <div class="date-picker-wrapper">
                                <input type="text" id="dateRange" name="period" placeholder="Выберите диапазон" value="<?= htmlspecialchars($_GET['period'] ?? '') ?>">
                                <img src="assets/icon/data.png" class="calendar-icon" alt="calendar">
                            </div>
                        </div>
                        <!-- Фильтр по студенту -->
                        <div>
                            <label id="label_student" for="student">Студент:</label>
                            <select name="student" id="student" class="select_filter">
                                <option value="">Все</option>
                                <?php foreach ($allStudents as $student): ?>
                                    <option value="<?= $student['id'] ?>" <?= ($student['id'] == ($_GET['student'] ?? '')) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($student['full_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- Фильтр по группе -->
                        <div>
                            <label id="label_group" for="group">Группа:</label>
                            <select name="group" id="group" class="select_filter">
                                <option value="">Все</option>
                                <?php foreach ($allGroups as $groupOption): ?>
                                    <option value="<?= htmlspecialchars($groupOption) ?>" <?= ($groupOption === ($_GET['group'] ?? '')) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($groupOption) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
                <!-- Кнопка открытия модального окна -->
                <div class="upload-block">
                    <button type="button" class="btn" id="openModal">Добавить документ</button>
                </div>
            </div>

            <!-- Перечень документов -->
            <main class="content">
                <div class="table-wrapper">
                    <table class="documents-table">
                        <thead>
                            <tr>
                                <th><?= sort_link('Наименование файла', 'original_name') ?></th>
                                <th><?= sort_link('Дата создания', 'uploaded_at') ?></th>
                                <th><?= sort_link('ФИО студента', 'full_name') ?></th>
                                <th><?= sort_link('Группа', 'group_name') ?></th>
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
                                        <td><?= htmlspecialchars($doc['full_name'] ?? '—') ?></td>
                                        <td style="position: relative;">
                                            <?= htmlspecialchars($doc['group_name'] ?? '—') ?>
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
        <!-- Модальное окно -->
        <div id="uploadModal" class="modal">
            <div class="modal-content">
                <form id="modalUploadForm" action="src/actions/upload.php" method="post" enctype="multipart/form-data">
                    <h3>Добавить документ</h3>

                    <div class="form-group">
                        <label for="modalStudent">Студент:</label>
                        <select name="student_id" id="modalStudent" required>
                            <option value="">Выберите студента</option>
                            <?php foreach ($allStudents as $student): ?>
                                <option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="modalGroup">Группа:</label>
                        <select name="group_name" id="modalGroup" required>
                            <option value="">Выберите группу</option>
                            <?php foreach ($allGroups as $group): ?>
                                <option value="<?= htmlspecialchars($group) ?>"><?= htmlspecialchars($group) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="modalFile">Файл:</label>
                        <input type="file" name="document" id="modalFile" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.txt" style="display: none;">

                        <div class="custom-file-display" id="customFileDisplay">
                            <img src="assets/icon/file.png" alt="иконка" class="file-icon">
                            <span class="file-name">Файл не выбран</span>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="submit" class="btn btn-primary">Добавить документ</button>
                        <button type="button" id="closeModal" class="btn btn-secondary">Закрыть</button>
                    </div>
                </form>
            </div>
        </div>

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
    <script src="assets/JS/curator.js"></script>
</body>

</html>