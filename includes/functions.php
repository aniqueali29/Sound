<?php
/**
 * Helper functions for SOUND music platform
 */

/**
 * Format duration from seconds to MM:SS format
 * 
 * @param int $seconds Duration in seconds
 * @return string Formatted duration string (MM:SS)
 */
function formatDuration($seconds) {
    $minutes = floor($seconds / 60);
    $remainingSeconds = $seconds % 60;
    return sprintf("%d:%02d", $minutes, $remainingSeconds);
}

/**
 * Generate a human-readable file size
 * 
 * @param int $bytes Size in bytes
 * @param int $precision Number of decimal places
 * @return string Formatted file size with unit
 */
function formatFileSize($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= (1 << (10 * $pow));
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Truncate text to specified length
 * 
 * @param string $text Input text
 * @param int $length Maximum length
 * @param string $append Text to append if truncated
 * @return string Truncated text
 */
function truncateText($text, $length = 100, $append = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    $text = substr($text, 0, $length);
    $text = substr($text, 0, strrpos($text, ' '));
    
    return $text . $append;
}

/**
 * Generate a slug from a string
 * 
 * @param string $text Input text
 * @return string URL-friendly slug
 */
function generateSlug($text) {
    // Replace non-alphanumeric characters with dashes
    $text = preg_replace('/[^A-Za-z0-9-]+/', '-', $text);
    // Remove duplicate dashes
    $text = preg_replace('/-+/', '-', $text);
    // Trim dashes from beginning and end
    $text = trim($text, '-');
    // Convert to lowercase
    return strtolower($text);
}

/**
 * Check if user has favorited an album
 * 
 * @param int $userId User ID
 * @param int $albumId Album ID
 * @param mysqli $conn Database connection
 * @return bool True if favorited, false otherwise
 */
function isAlbumFavorited($userId, $albumId, $conn) {
    $query = "SELECT 1 FROM user_favorites WHERE user_id = ? AND album_id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $userId, $albumId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $count = mysqli_stmt_num_rows($stmt);
    mysqli_stmt_close($stmt);
    
    return $count > 0;
}

/**
 * Get time elapsed string (e.g., "2 hours ago")
 * 
 * @param string $datetime Date/time string
 * @return string Time elapsed string
 */
function timeElapsed($datetime) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$string) {
        return 'just now';
    }
    
    $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

/**
 * Sanitize input data
 * 
 * @param string $data Input data
 * @param mysqli $conn Database connection for escaping
 * @return string Sanitized data
 */
function sanitizeInput($data, $conn) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = mysqli_real_escape_string($conn, $data);
    return $data;
}

/**
 * Check if user is logged in
 * 
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get user's permission level
 * 
 * @param int $userId User ID
 * @param mysqli $conn Database connection
 * @return int Permission level (0=user, 1=moderator, 2=admin)
 */
function getUserPermission($userId, $conn) {
    $query = "SELECT permission_level FROM users WHERE id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $permissionLevel);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    
    return $permissionLevel ?? 0;
}

/**
 * Generate pagination links
 * 
 * @param int $currentPage Current page number
 * @param int $totalPages Total number of pages
 * @param string $urlPattern URL pattern with %d placeholder for page number
 * @return string HTML for pagination links
 */
function generatePagination($currentPage, $totalPages, $urlPattern) {
    if ($totalPages <= 1) {
        return '';
    }
    
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous button
    if ($currentPage > 1) {
        $html .= sprintf('<li class="page-item"><a class="page-link" href="' . $urlPattern . '">&laquo;</a></li>', $currentPage - 1);
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">&laquo;</span></li>';
    }
    
    // Page numbers
    $startPage = max(1, $currentPage - 2);
    $endPage = min($totalPages, $currentPage + 2);
    
    if ($startPage > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . sprintf($urlPattern, 1) . '">1</a></li>';
        if ($startPage > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    for ($i = $startPage; $i <= $endPage; $i++) {
        if ($i == $currentPage) {
            $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
        } else {
            $html .= '<li class="page-item"><a class="page-link" href="' . sprintf($urlPattern, $i) . '">' . $i . '</a></li>';
        }
    }
    
    if ($endPage < $totalPages) {
        if ($endPage < $totalPages - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="' . sprintf($urlPattern, $totalPages) . '">' . $totalPages . '</a></li>';
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $html .= sprintf('<li class="page-item"><a class="page-link" href="' . $urlPattern . '">&raquo;</a></li>', $currentPage + 1);
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">&raquo;</span></li>';
    }
    
    $html .= '</ul></nav>';
    
    return $html;
}

/**
 * Format a date in a human-readable format
 * 
 * @param string $dateString Date string
 * @param string $format Date format
 * @return string Formatted date
 */
function formatDate($dateString, $format = 'M d, Y') {
    $date = new DateTime($dateString);
    return $date->format($format);
}

/**
 * Generate a random string
 * 
 * @param int $length Length of the random string
 * @return string Random string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}