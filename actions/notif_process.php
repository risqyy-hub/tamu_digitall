<?php
/**
 * actions/notif_process.php
 * Penandaan notifikasi sudah dibaca.
 */
require_once __DIR__ . '/../includes/functions.php';

require_login();
require_post();
verify_csrf();

$action = req_str('action');

if ($action === 'mark_read') {
    $id = req_int('id');
    if ($id > 0) {
        $stmt = db()->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND (user_id = ? OR user_id IS NULL)');
        $stmt->execute([$id, current_user_id()]);
    }
} elseif ($action === 'mark_all') {
    $stmt = db()->prepare('UPDATE notifications SET is_read = 1 WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0');
    $stmt->execute([current_user_id()]);
}

redirect('index.php');
