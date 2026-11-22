<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}

verify_csrf();

$action = $_POST['action'] ?? '';
$userId = (int) $_SESSION['user_id'];

if ($action === 'create' || $action === 'update') {
    $title = trim((string) ($_POST['title'] ?? ''));
    $content = trim((string) ($_POST['content'] ?? ''));

    if ($title === '' || strlen($title) > 160 || $content === '') {
        http_response_code(422);
        exit('A title (up to 160 bytes) and post content are required.');
    }

    if ($action === 'create') {
        $statement = $db->prepare('INSERT INTO `post` (user_id, title, content) VALUES (?, ?, ?)');
        $statement->bind_param('iss', $userId, $title, $content);
        $statement->execute();
    } else {
        $postId = filter_var($_POST['post_id'] ?? null, FILTER_VALIDATE_INT);
        if ($postId === false || $postId === null || $postId < 1) {
            http_response_code(400);
            exit('Invalid post ID.');
        }

        $statement = $db->prepare('UPDATE `post` SET title = ?, content = ? WHERE id = ? AND user_id = ?');
        $statement->bind_param('ssii', $title, $content, $postId, $userId);
        $statement->execute();
    }

    header('Location: dashboard.php?saved=1');
    exit;
}

if ($action === 'delete') {
    $postId = filter_var($_POST['post_id'] ?? null, FILTER_VALIDATE_INT);
    if ($postId === false || $postId === null || $postId < 1) {
        http_response_code(400);
        exit('Invalid post ID.');
    }

    $statement = $db->prepare('DELETE FROM `post` WHERE id = ? AND user_id = ?');
    $statement->bind_param('ii', $postId, $userId);
    $statement->execute();

    header('Location: dashboard.php?deleted=1');
    exit;
}

http_response_code(400);
exit('Invalid post action.');
