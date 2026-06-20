<?php
/**
 * doodle - Helper functions
 *
 * @package doodle
 * @author  Death Legion Team
 */

// Build an internal URL
function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    if ($path === '') return BASE_URL . '/';
    return BASE_URL . '/index.php?r=' . $path;
}

// Build an asset URL
function asset(string $path): string
{
    return BASE_URL . '/public/' . ltrim($path, '/');
}

// Build an upload URL
function uploadUrl(string $path): string
{
    return BASE_URL . '/public/uploads/' . ltrim($path, '/');
}

// Shortcut to render a view (used by Router errors)
function view(string $view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    $file = APP_DIR . '/views/' . $view . '.php';
    if (!file_exists($file)) {
        echo '<h1>View missing</h1><p>' . htmlspecialchars($view) . '</p>';
        return;
    }
    require $file;
}

// Redirect shortcut
function redirect(string $path = ''): void
{
    header('Location: ' . url($path));
    exit;
}

// Escape output
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Truncate text
function truncate(string $text, int $length = 120, string $suffix = '...'): string
{
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . $suffix;
}

// Format date
function formatDate($date, string $format = 'M j, Y'): string
{
    if (empty($date) || $date === '0000-00-00 00:00:00') return '';
    $ts = is_numeric($date) ? (int) $date : strtotime($date);
    return date($format, $ts);
}

function formatDateTime($date): string
{
    return formatDate($date, 'M j, Y · g:i A');
}

function timeAgo($date): string
{
    if (empty($date)) return '';
    $ts = is_numeric($date) ? (int) $date : strtotime($date);
    $diff = time() - $ts;
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hr ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return formatDate($date);
}

// Initials from a name (for avatar)
function initials(string $name): string
{
    $parts = explode(' ', trim($name));
    if (count($parts) === 1) return strtoupper(mb_substr($parts[0], 0, 1));
    return strtoupper(mb_substr($parts[0], 0, 1) . mb_substr(end($parts), 0, 1));
}

// Avatar color from a string
function avatarColor(string $name): string
{
    $colors = ['#4F46E5', '#059669', '#D97706', '#DC2626', '#7C3AED', '#0891B2', '#DB2777', '#65A30D'];
    $hash = 0;
    for ($i = 0; $i < strlen($name); $i++) $hash += ord($name[$i]);
    return $colors[$hash % count($colors)];
}

// Human-readable file size
function humanFileSize(int $bytes): string
{
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
    if ($bytes < 1073741824) return round($bytes / 1048576, 1) . ' MB';
    return round($bytes / 1073741824, 1) . ' GB';
}

// File extension icon (Material Icons name)
function fileIcon(string $ext): string
{
    $icons = [
        'pdf'  => 'picture_as_pdf',
        'doc'  => 'description', 'docx' => 'description',
        'ppt'  => 'slideshow', 'pptx' => 'slideshow',
        'xls'  => 'table_chart', 'xlsx' => 'table_chart',
        'txt'  => 'article',
        'jpg'  => 'image', 'jpeg' => 'image', 'png' => 'image', 'gif' => 'image', 'webp' => 'image',
        'mp4'  => 'movie', 'webm' => 'movie',
        'mp3'  => 'music_note', 'wav' => 'music_note',
    ];
    return $icons[strtolower($ext)] ?? 'insert_drive_file';
}

// Generate pagination HTML
function paginate(int $total, int $perPage, int $currentPage, string $baseUrl): string
{
    $pages = (int) ceil($total / $perPage);
    if ($pages <= 1) return '';
    $html = '<nav class="pagination">';
    if ($currentPage > 1) {
        $html .= '<a href="' . e($baseUrl . '&p=' . ($currentPage - 1)) . '" class="page-btn">&laquo; Prev</a>';
    }
    for ($i = max(1, $currentPage - 2); $i <= min($pages, $currentPage + 2); $i++) {
        if ($i === $currentPage) {
            $html .= '<span class="page-btn active">' . $i . '</span>';
        } else {
            $html .= '<a href="' . e($baseUrl . '&p=' . $i) . '" class="page-btn">' . $i . '</a>';
        }
    }
    if ($currentPage < $pages) {
        $html .= '<a href="' . e($baseUrl . '&p=' . ($currentPage + 1)) . '" class="page-btn">Next &raquo;</a>';
    }
    $html .= '</nav>';
    return $html;
}

// Generate a slug from text
function slugify(string $text): string
{
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return $text ?: 'n-a';
}

// Mask a string (e.g., for hidden email)
function maskEmail(string $email): string
{
    [$u, $d] = explode('@', $email);
    $len = strlen($u);
    if ($len <= 2) return $u[0] . '*@' . $d;
    return $u[0] . str_repeat('*', $len - 2) . $u[$len - 1] . '@' . $d;
}

// Validate email
function isValidEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Get uploaded file extension
function fileExtension(string $filename): string
{
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

// Flash message rendering
function renderFlash(array $flashes): string
{
    $html = '';
    foreach ($flashes as $f) {
        $html .= '<div class="alert alert-' . e($f['type']) . '">' . e($f['message']) . '</div>';
    }
    return $html;
}
