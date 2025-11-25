<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Api\Controllers\AuthController;
use Api\Controllers\PostController;
use Api\Controllers\CommentController;
use Api\Controllers\UserController;
use Api\Controllers\LikeController;
use Api\Controllers\FollowController;
use Api\Controllers\NotificationController;

// ========== HEADERS ==========
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Responde preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ========== FUNÇÃO HELPER ==========
/**
 * Acessa array de forma segura
 */
function p($index) {
    global $parts;
    return $parts[$index] ?? null;
}

// ========== ROTEAMENTO ==========
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove prefixo da URL
$path = str_replace('/api', '', $path);
$path = preg_replace('#^/+#', '/', $path);

// Separa rota e parâmetros
$parts = array_filter(explode('/', $path));
$parts = array_values($parts); // Re-indexa

try {
    // Se não houver rota, retorna erro
    if (empty($parts) || p(0) === null) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Rota não encontrada',
            'code' => 'NOT_FOUND'
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    // GET /api/posts
    if (p(0) === 'posts' && $method === 'GET' && count($parts) === 1) {
        $_GET['page'] = $_GET['page'] ?? 1;
        $_GET['sort'] = $_GET['sort'] ?? 'recent';
        (new PostController())->list();
    }

    // POST /api/posts
    else if (p(0) === 'posts' && $method === 'POST' && count($parts) === 1) {
        (new PostController())->create();
    }

    // GET /api/posts/{id}
    else if (p(0) === 'posts' && $method === 'GET' && is_numeric(p(1))) {
        $_GET['id'] = p(1);
        (new PostController())->show();
    }

    // PUT /api/posts/{id}
    else if (p(0) === 'posts' && $method === 'PUT' && is_numeric(p(1))) {
        $_GET['id'] = p(1);
        (new PostController())->update();
    }

    // DELETE /api/posts/{id}
    else if (p(0) === 'posts' && $method === 'DELETE' && is_numeric(p(1))) {
        $_GET['id'] = p(1);
        (new PostController())->delete();
    }

    // GET /api/posts/search?q=query
    else if (p(0) === 'posts' && p(1) === 'search' && $method === 'GET') {
        (new PostController())->search();
    }

    // GET /api/posts/{postId}/comments
    else if (p(0) === 'posts' && p(2) === 'comments' && $method === 'GET') {
        $_GET['post_id'] = p(1);
        $_GET['sort'] = $_GET['sort'] ?? 'recent';
        (new CommentController())->listByPost();
    }

    // POST /api/posts/{postId}/comments
    else if (p(0) === 'posts' && p(2) === 'comments' && $method === 'POST') {
        $_GET['post_id'] = p(1);
        (new CommentController())->create();
    }

    // GET /api/comments/{id}
    else if (p(0) === 'comments' && $method === 'GET' && is_numeric(p(1))) {
        $_GET['id'] = p(1);
        (new CommentController())->show();
    }

    // DELETE /api/comments/{id}
    else if (p(0) === 'comments' && $method === 'DELETE' && is_numeric(p(1))) {
        $_GET['id'] = p(1);
        (new CommentController())->delete();
    }

    // POST /api/posts/{postId}/like
    else if (p(0) === 'posts' && p(2) === 'like' && $method === 'POST') {
        $_GET['post_id'] = p(1);
        (new LikeController())->likePost();
    }

    // DELETE /api/posts/{postId}/like
    else if (p(0) === 'posts' && p(2) === 'like' && $method === 'DELETE') {
        $_GET['post_id'] = p(1);
        (new LikeController())->unlikePost();
    }

    // POST /api/comments/{commentId}/like
    else if (p(0) === 'comments' && p(2) === 'like' && $method === 'POST') {
        $_GET['comment_id'] = p(1);
        (new LikeController())->likeComment();
    }

    // DELETE /api/comments/{commentId}/like
    else if (p(0) === 'comments' && p(2) === 'like' && $method === 'DELETE') {
        $_GET['comment_id'] = p(1);
        (new LikeController())->unlikeComment();
    }

    // POST /api/auth/register
    else if (p(0) === 'auth' && p(1) === 'register' && $method === 'POST') {
        (new AuthController())->register();
    }

    // POST /api/auth/login
    else if (p(0) === 'auth' && p(1) === 'login' && $method === 'POST') {
        (new AuthController())->login();
    }

    // POST /api/auth/verify
    else if (p(0) === 'auth' && p(1) === 'verify' && $method === 'POST') {
        (new AuthController())->verify();
    }

    // GET /api/users/{username}/posts (DEVE VIR ANTES DA ROTA GENÉRICA)
    else if (p(0) === 'users' && p(2) === 'posts' && $method === 'GET') {
        $_GET['username'] = p(1);
        $_GET['page'] = $_GET['page'] ?? 1;
        (new UserController())->posts();
    }

    // GET /api/users/{username}
    else if (p(0) === 'users' && $method === 'GET' && !is_numeric(p(1)) && p(1) !== 'search' && p(2) !== 'posts') {
        $_GET['username'] = p(1);
        (new UserController())->profile();
    }

    // GET /api/users/search?q=query
    else if (p(0) === 'users' && p(1) === 'search' && $method === 'GET') {
        (new UserController())->search();
    }

    // PUT /api/users/profile
    else if (p(0) === 'users' && p(1) === 'profile' && $method === 'PUT') {
        (new UserController())->updateProfile();
    }

    // DELETE /api/users/{id} (admin)
    else if (p(0) === 'users' && $method === 'DELETE' && is_numeric(p(1))) {
        $_GET['id'] = p(1);
        (new UserController())->deleteUser();
    }

    // POST /api/users/{id}/ban (admin)
    else if (p(0) === 'users' && p(2) === 'ban' && $method === 'POST') {
        $_GET['id'] = p(1);
        (new UserController())->ban();
    }

    // POST /api/users/{userId}/follow
    else if (p(0) === 'users' && p(2) === 'follow' && $method === 'POST') {
        $_GET['user_id'] = p(1);
        (new FollowController())->follow();
    }

    // DELETE /api/users/{userId}/follow
    else if (p(0) === 'users' && p(2) === 'follow' && $method === 'DELETE') {
        $_GET['user_id'] = p(1);
        (new FollowController())->unfollow();
    }

    // GET /api/users/{userId}/followers
    else if (p(0) === 'users' && p(2) === 'followers' && $method === 'GET') {
        $_GET['user_id'] = p(1);
        (new FollowController())->followers();
    }

    // GET /api/users/{userId}/following
    else if (p(0) === 'users' && p(2) === 'following' && $method === 'GET') {
        $_GET['user_id'] = p(1);
        (new FollowController())->following();
    }

    // GET /api/users/{userId}/is-following
    else if (p(0) === 'users' && p(2) === 'is-following' && $method === 'GET') {
        $_GET['user_id'] = p(1);
        (new FollowController())->isFollowing();
    }

    // GET /api/notifications
    else if (p(0) === 'notifications' && $method === 'GET' && count($parts) === 1) {
        (new NotificationController())->list();
    }

    // GET /api/notifications/unread
    else if (p(0) === 'notifications' && p(1) === 'unread' && $method === 'GET') {
        (new NotificationController())->unreadCount();
    }

    // PUT /api/notifications/{id}/read
    else if (p(0) === 'notifications' && p(2) === 'read' && $method === 'PUT') {
        $_GET['id'] = p(1);
        (new NotificationController())->markAsRead();
    }

    // PUT /api/notifications/read-all
    else if (p(0) === 'notifications' && p(1) === 'read-all' && $method === 'PUT') {
        (new NotificationController())->markAllAsRead();
    }

    // Rota não encontrada
    else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Rota não encontrada',
            'code' => 'NOT_FOUND'
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => 'SERVER_ERROR'
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
