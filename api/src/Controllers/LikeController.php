<?php

namespace Api\Controllers;

use Api\Models\Like;
use Api\Models\Post;
use Api\Models\Comment;
use Api\Models\Notification;
use Api\Utils\JwtHelper;

class LikeController extends Controller
{
    private $likeModel;
    private $postModel;
    private $commentModel;
    private $notificationModel;

    public function __construct()
    {
        parent::__construct();
        $this->likeModel = new Like();
        $this->postModel = new Post();
        $this->commentModel = new Comment();
        $this->notificationModel = new Notification();
    }

    /**
     * POST /api/posts/{postId}/like
     * Curte um post
     */
    public function likePost(): void
    {
        $token = $this->getAuthToken();
        if (!$token) {
            $this->unauthorized();
        }

        $decoded = JwtHelper::verify($token);
        if (!$decoded) {
            $this->unauthorized();
        }

        $postId = (int)($this->getParam('post_id') ?? 0);
        $post = $this->postModel->get($postId);

        if (!$post) {
            $this->notFound('Post');
        }

        // O banco garante unicidade via UNIQUE KEY (user_id, post_id)
        // Se já curtiu, o banco lançará exception
        try {
            $this->likeModel->insert([
                'user_id' => $decoded['id'],
                'post_id' => $postId,
                'comment_id' => null
            ]);
            $this->postModel->incrementLikes($postId);

            // Notifica autor do post
            if ($post['user_id'] != $decoded['id']) {
                $this->notificationModel->insert([
                    'user_id' => $post['user_id'],
                    'from_user_id' => $decoded['id'],
                    'type' => 'like',
                    'post_id' => $postId,
                    'comment_id' => null
                ]);
            }

            $this->success(null, 'Post curtido com sucesso', 201);
        } catch (\Exception $e) {
            // Captura erro de UNIQUE constraint
            if (strpos($e->getMessage(), 'Duplicate entry') !== false && 
                strpos($e->getMessage(), 'unique_post_like') !== false) {
                $this->error('Você já curtiu este post', 'ALREADY_LIKED', 400);
            }
            // Outros erros de banco
            $this->error('Erro ao curtir post', 'DATABASE_ERROR', 500);
        }
    }

    /**
     * DELETE /api/posts/{postId}/like
     * Remove like de um post
     */
    public function unlikePost(): void
    {
        $token = $this->getAuthToken();
        if (!$token) {
            $this->unauthorized();
        }

        $decoded = JwtHelper::verify($token);
        if (!$decoded) {
            $this->unauthorized();
        }

        $postId = (int)($this->getParam('post_id') ?? 0);
        $post = $this->postModel->get($postId);

        if (!$post) {
            $this->notFound('Post');
        }

        if (!$this->likeModel->hasLikedPost($decoded['id'], $postId)) {
            $this->error('Você não curtiu este post', 'NOT_LIKED', 400);
        }

        try {
            $this->likeModel->unlikePost($decoded['id'], $postId);
            $this->postModel->decrementLikes($postId);
        } catch (\Exception $e) {
            $this->error('Erro ao remover like', 'DATABASE_ERROR', 500);
        }

        $this->success(null, 'Like removido com sucesso');
    }

    /**
     * POST /api/comments/{commentId}/like
     * Curte um comentário
     */
    public function likeComment(): void
    {
        $token = $this->getAuthToken();
        if (!$token) {
            $this->unauthorized();
        }

        $decoded = JwtHelper::verify($token);
        if (!$decoded) {
            $this->unauthorized();
        }

        $commentId = (int)($this->getParam('comment_id') ?? 0);
        $comment = $this->commentModel->get($commentId);

        if (!$comment) {
            $this->notFound('Comentário');
        }

        // O banco garante unicidade via UNIQUE KEY (user_id, comment_id)
        // Se já curtiu, o banco lançará exception
        try {
            $this->likeModel->insert([
                'user_id' => $decoded['id'],
                'post_id' => null,
                'comment_id' => $commentId
            ]);
            $this->commentModel->incrementLikes($commentId);

            // Notifica autor do comentário
            if ($comment['user_id'] != $decoded['id']) {
                $this->notificationModel->insert([
                    'user_id' => $comment['user_id'],
                    'from_user_id' => $decoded['id'],
                    'type' => 'like',
                    'post_id' => null,
                    'comment_id' => $commentId
                ]);
            }

            $this->success(null, 'Comentário curtido com sucesso', 201);
        } catch (\Exception $e) {
            // Captura erro de UNIQUE constraint
            if (strpos($e->getMessage(), 'Duplicate entry') !== false && 
                strpos($e->getMessage(), 'unique_comment_like') !== false) {
                $this->error('Você já curtiu este comentário', 'ALREADY_LIKED', 400);
            }
            // Outros erros de banco
            $this->error('Erro ao curtir comentário', 'DATABASE_ERROR', 500);
        }
    }

    /**
     * DELETE /api/comments/{commentId}/like
     * Remove like de um comentário
     */
    public function unlikeComment(): void
    {
        $token = $this->getAuthToken();
        if (!$token) {
            $this->unauthorized();
        }

        $decoded = JwtHelper::verify($token);
        if (!$decoded) {
            $this->unauthorized();
        }

        $commentId = (int)($this->getParam('comment_id') ?? 0);
        $comment = $this->commentModel->get($commentId);

        if (!$comment) {
            $this->notFound('Comentário');
        }

        if (!$this->likeModel->hasLikedComment($decoded['id'], $commentId)) {
            $this->error('Você não curtiu este comentário', 'NOT_LIKED', 400);
        }

        try {
            $this->likeModel->unlikeComment($decoded['id'], $commentId);
            $this->commentModel->decrementLikes($commentId);
        } catch (\Exception $e) {
            $this->error('Erro ao remover like', 'DATABASE_ERROR', 500);
        }

        $this->success(null, 'Like removido com sucesso');
    }
}
