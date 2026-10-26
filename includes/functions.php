<?php

declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';

function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function url(string $path): string
{
    return config()['base_url'] . '/' . ltrim($path, '/');
}
function redirect(string $path): never
{
    header('Location: ' . url($path), true, 303);
    exit;
}
function query(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}
function rows(string $sql, array $params = []): array
{
    return query($sql, $params)->fetchAll();
}
function one(string $sql, array $params = []): ?array
{
    return query($sql, $params)->fetch() ?: null;
}
function scalar(string $sql, array $params = [])
{
    return query($sql, $params)->fetchColumn();
}
function transaction(callable $callback)
{
    db()->beginTransaction();
    try {
        $result = $callback();
        db()->commit();
        return $result;
    } catch (Throwable $error) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        throw $error;
    }
}
function input(string $name, string $default = ''): string
{
    $value = $_POST[$name] ?? $_GET[$name] ?? $default;
    return is_string($value) ? trim($value) : $default;
}
function required(string $name, int $max = 200): string
{
    $value = input($name);
    if ($value === '' || strlen($value) > $max) {
        field_error($name, 'Enter a valid ' . str_replace('_', ' ', $name) . ' (up to ' . $max . ' characters).');
    }
    return $value;
}
function positive_id(string $name = 'id'): int
{
    $value = filter_var(input($name), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($value === false) {
        field_error($name, 'Select a valid record.');
    }
    return $value;
}
function choice(string $name, array $allowed): string
{
    $value = input($name);
    if (!in_array($value, $allowed, true)) {
        field_error($name, 'Select a valid ' . str_replace('_', ' ', $name) . '.');
    }
    return $value;
}
function decimal_input(string $name, bool $zero = true): string
{
    $value = required($name, 12);
    if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/D', $value) || (!$zero && (float) $value <= 0)) {
        field_error($name, 'Enter a valid ' . str_replace('_', ' ', $name) . ' with at most two decimal places.');
    }
    return number_format((float) $value, 2, '.', '');
}
function csrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">';
}
function check_csrf(): void
{
    if (!isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], input('csrf'))) {
        throw new DomainException('Your form has expired. Reload this page and try again.');
    }
}
function handle_post(callable $callback, string $destination): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    try {
        check_csrf();
        $message = $callback();
        $_SESSION['flash'] = ['success', $message ?: 'Changes saved.'];
        redirect($destination);
    } catch (DomainException $error) {
        $GLOBALS['form_error'] = $error->getMessage();
    } catch (PDOException $error) {
        error_log('SSIS database operation failed: ' . $error->getCode());
        $GLOBALS['form_error'] = $error->getCode() === '23000'
            ? 'A unique identifier already exists or a related record is invalid. Check your entries.'
            : 'The change could not be saved. Reload and try again; contact your administrator if it continues.';
    }
}
function abort_page(int $code, string $message): never
{
    http_response_code($code);
    echo '<!doctype html>
<html lang="en">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width">
<title>CuyoTech</title>
<body>
<main>
<h1>' . e($code) . '</h1>
<p>' . e($message) . '</p>
<a href="' . e(url('index.php')) . '">Return to portal</a>
</main>
</body>
</html>';
    exit;
}
function money($value): string
{
    return '₱' . number_format((float) $value, 2);
}
function date_label(?string $value): string
{
    return $value ? (new DateTimeImmutable($value, new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Manila'))->format('M j, Y · g:i a') : '—';
}
function badge(string $value): string
{
    return '<span class="badge ' . e(strtolower($value)) . '">' . e(ui_label($value)) . '</span>';
}
function options(array $records, string $key, string $label, $selected = ''): void
{
    foreach ($records as $record) {
        echo '<option value="' . e($record[$key]) . '"' . ((string) $selected === (string) $record[$key] ? ' selected' : '') . '>' . e($record[$label]) . '</option>';
    }
}
function terms(): array
{
    return rows("SELECT term_id, CONCAT(academic_year, ' / ', term_code) AS label FROM academic_terms ORDER BY starts_on DESC");
}
function page_limit(): string
{
    $page = max(1, min(100000, (int) input('page', '1')));
    return ' LIMIT 21 OFFSET ' . (($page - 1) * 20);
}
function pager(array &$records): void
{
    $GLOBALS['has_next'] = count($records) > 20;
    if ($GLOBALS['has_next']) {
        array_pop($records);
    }
}
function pagination(): void
{
    $page = max(1, (int) input('page', '1'));
    echo '<nav class="pagination" aria-label="Pagination">';
    foreach ([$page - 1 => 'Previous', $page + 1 => 'Next'] as $target => $label) {
        if ($target < 1 || ($target > $page && empty($GLOBALS['has_next']))) {
            continue;
        }
        $params = $_GET;
        $params['page'] = $target;
        echo '<a class="button secondary" href="?' . e(http_build_query($params)) . '">' . e($label) . '</a>';
    }
    echo '<span>Page ' . e($page) . '</span>
</nav>';
}

function icon(string $name): string
{
    $aliases = ['accounts' => 'users', 'students' => 'student', 'departments' => 'building', 'courses' => 'graduation', 'subjects' => 'book', 'enrollments' => 'enrollment', 'terms' => 'calendar', 'grades' => 'results', 'document-requests' => 'file', 'service-catalog' => 'catalog', 'receipts' => 'receipt', 'payments' => 'payment', 'clearance' => 'shield', 'clearances' => 'shield', 'clearance-status' => 'shield', 'activity-log' => 'activity'];
    $paths = [
        'enrollment' => 'M8 4H5v17h14V4h-3 M8 2h8v5H8z M8 13l3 3 5-6',
        'student' => 'M5 21v-2a7 5 0 0 1 14 0v2 M8 8v3a4 4 0 0 0 8 0V8 M3 5l9-4 9 4-9 4z M21 5v7',
        'building' => 'M4 21V3h16v18 M2 21h20 M8 7h2 M14 7h2 M8 11h2 M14 11h2 M10 21v-6h4v6',
        'graduation' => 'M2 8l10-5 10 5-10 5z M6 11v6q6 5 12 0v-6 M22 8v10',
        'results' => 'M4 3v18h17 M8 17v-4 M13 17V9 M18 17V5',
        'receipt' => 'M5 2l3 2 4-2 4 2 3-2v20l-3-2-4 2-4-2-3 2z M8 8h8 M8 12h8 M8 16h5',
        'shield' => 'M12 2l8 3v6c0 5-4 9-8 11-4-2-8-6-8-11V5z m-4 9 3 3 5-6',
        'catalog' => 'M4 3h16v18H4z M8 7h2 M13 7h3 M8 12h2 M13 12h3 M8 17h2 M13 17h3',
        'back' => 'M20 12H4 m7-7-7 7 7 7',
        'chevron' => 'm6 9 6 6 6-6',

        'dashboard' => 'M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z',
        'users' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8 M17 4a4 4 0 0 1 0 8 M22 21v-2a4 4 0 0 0-3-4',
        'book' => 'M3 4h7l2 2 2-2h7v16h-7l-2 1-2-1H3z M12 6v15',
        'calendar' => 'M4 5h16v16H4z M8 2v6 M16 2v6 M4 11h16',
        'file' => 'M5 3h10l4 4v14H5z M14 3v5h5 M8 12h8 M8 16h6',
        'check' => 'm4 12 5 5L20 6',
        'close' => 'm6 6 12 12 M18 6 6 18',
        'payment' => 'M3 5h18v14H3z M3 9h18 M6 15h4',
        'activity' => 'M3 12h4l3-8 4 16 3-8h4',
        'profile' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8 M4 21v-2a8 5 0 0 1 16 0v2',
        'search' => 'M10 17a7 7 0 1 0 0-14 7 7 0 0 0 0 14 m5-2 6 6',
        'add' => 'M12 4v16 M4 12h16',
        'edit' => 'm4 15 12-12 5 5L9 20H4z M14 5l5 5',
        'eye' => 'M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12 M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6',
        'logout' => 'M9 4H3v16h6 M9 12h12 m-5-5 5 5-5 5',
        'print' => 'M6 9V3h12v6 M6 17H3V9h18v8h-3 M6 14h12v7H6z',
        'menu' => 'M3 6h18 M3 12h18 M3 18h18',
        'delete' => 'M3 6h18 M9 6V3h6v3 M5 6l1 15h12l1-15 M10 10v7 M14 10v7',
    ];
    $name = $aliases[$name] ?? $name;
    $path = $paths[$name] ?? $paths['file'];
    return '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="' . $path . '"/></svg>';
}

function ui_label(string $value): string
{
    $labels = ['ADMIN' => 'Administrator', 'DEPARTMENT' => 'Department Staff', 'UNIVERSITY' => 'University-wide'];
    return $labels[$value] ?? ucwords(strtolower(str_replace('_', ' ', $value)));
}

function field_error(string $name, string $message): never
{
    $GLOBALS['field_errors'][$name] = $message;
    throw new DomainException($message);
}

// Preserve submitted controls and attach field errors without duplicating markup in every portal.
function render_forms(string $html): string
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') return $html;
    return preg_replace_callback('/<form\b[^>]*method="post"[^>]*>.*?<\/form>/s', function ($match) {
        $form = $match[0];
        if (
            preg_match('/name="action" value="([^"]*)"/', $form, $action)
            && ($action[1] !== ($_POST['action'] ?? ''))
        ) return $form;
        if (
            preg_match('/name="enrollment_subject_id" value="([^"]*)"/', $form, $record)
            && $record[1] !== ($_POST['enrollment_subject_id'] ?? '')
        ) return $form;
        return preg_replace_callback('/<input\b[^>]*>|<select\b[^>]*>.*?<\/select>|<textarea\b[^>]*>.*?<\/textarea>/s', function ($control) {
            $tag = $control[0];
            if (!preg_match('/^<(input|select|textarea)\b[^>]*name="([a-z_]+)"/', $tag, $control)) return $tag;
            $name = $control[2];
            if (str_contains($tag, 'type="hidden"')) return $tag;
            $value = str_contains($tag, 'type="password"') ? null : ($_POST[$name] ?? null);
            if (is_string($value)) {
                if ($control[1] === 'select') {
                    $tag = preg_replace('/ selected(?:="[^"]*")?/', '', $tag);
                    $tag = preg_replace_callback('/<option([^>]*)>(.*?)<\/option>/s', function ($option) use ($value) {
                        preg_match('/value="([^"]*)"/', $option[1], $v);
                        $optionValue = html_entity_decode($v[1] ?? trim(strip_tags($option[2])), ENT_QUOTES, 'UTF-8');
                        return '<option' . $option[1] . ($optionValue === $value ? ' selected' : '') . '>' . $option[2] . '</option>';
                    }, $tag);
                } elseif ($control[1] === 'textarea') {
                    $tag = substr($tag, 0, strpos($tag, '>') + 1) . e($value) . '</textarea>';
                } elseif (!str_contains($tag, 'type="checkbox"')) {
                    $tag = preg_replace('/ value="[^"]*"/', '', $tag);
                    $tag = substr($tag, 0, -1) . ' value="' . e($value) . '">';
                }
            }
            if (str_contains($tag, 'type="checkbox"')) {
                $tag = preg_replace('/ checked(?:="[^"]*")?/', '', $tag);
                if (isset($_POST[$name])) $tag = substr($tag, 0, -1) . ' checked>';
            }
            if (isset($GLOBALS['field_errors'][$name])) {
                $errorId = 'error-' . $name;
                $tag = preg_replace('/>/', ' aria-invalid="true" aria-describedby="' . $errorId . '">', $tag, 1);
                $tag .= '<span class="field-error" id="' . $errorId . '">' . e($GLOBALS['field_errors'][$name]) . '</span>';
            }
            return $tag;
        }, $form);
    }, $html);
}

function validation_script(): void
{
?>
    <script>
        document.querySelectorAll('form[method="post"]').forEach(form => {
            form.addEventListener('invalid', event => {
                const field = event.target;
                showToast('Check the highlighted fields before continuing.', 'error', 'form-validation');
                field.setAttribute('aria-invalid', 'true');
                if (!field.id) field.id = 'field-' + field.name;
                let message = document.getElementById(field.id + '-error');
                if (!message) {
                    message = document.createElement('span');
                    message.id = field.id + '-error';
                    message.className = 'field-error';
                    field.insertAdjacentElement('afterend', message);
                }
                field.setAttribute('aria-describedby', message.id);
                message.textContent = field.validationMessage;
            }, true);
            form.addEventListener('input', event => {
                dismissToast('server-error');
                const field = event.target;
                if (field.validity?.valid) {
                    const message = document.getElementById(field.getAttribute('aria-describedby'));
                    if (message?.classList.contains('field-error')) message.remove();
                    field.removeAttribute('aria-invalid');
                    field.removeAttribute('aria-describedby');
                    if (!form.querySelector('[aria-invalid="true"]')) dismissToast('form-validation');
                }
            });
        });
        const invalidField = document.querySelector('[aria-invalid="true"]');
        if (invalidField) {
            const collapsed = invalidField.closest('details');
            if (collapsed) collapsed.open = true;
            invalidField.focus();
        }
    </script>
<?php
}

// Retain only supported list filters, never an arbitrary return URL.
function list_query(array $changes = []): string
{
    $params = [];
    foreach (['q', 'page', 'term_id', 'status'] as $key) {
        if (isset($_GET[$key]) && is_string($_GET[$key]) && $_GET[$key] !== '') {
            $params[$key] = $_GET[$key];
        }
    }
    foreach ($changes as $key => $value) {
        if ($value === null) unset($params[$key]);
        else $params[$key] = $value;
    }
    return $params ? '?' . http_build_query($params) : '';
}
