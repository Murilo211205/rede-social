<?php
namespace Api\Controllers;

use Api\Models\Comment;
use Api\Models\Post;
use Api\Models\User;
use Api\Models\Notification;
use Api\Utils\JwtHelper;
use Api\Utils\Validator;

class CommentController extends Controller
{
    private $commentModel;
    private $postModel;
    private $userModel;
    private $notificationModel;

    public function __construct()
    {
        parent::__construct();
        $this->commentModel = new Comment();
        $this->postModel = new Post();
        $this->userModel = new User();
        $this->notificationModel = new Notification();
    }

    /**
     * GET /api/posts/{postId}/comments
     * Lista comentários de um post
     */
    public function listByPost(): void
    {
        $postId = (int)($this->getParam('post_id') ?? 0);
        $sort = $this->getParam('sort') ?? 'recent';

        if ($postId <= 0) {
            $this->error('ID do post inválido', 'INVALID_ID', 400);
        }

        $post = $this->postModel->get($postId);
        if (!$post) {
            $this->notFound('Post');
        }

        $comments = $this->commentModel->listByPost($postId, $sort);

        $this->success(['comments' => $comments]);
    }

    /**
     * POST /api/posts/{postId}/comments
     * Cria novo comentário
     */
    public function create(): void
    {
        $token = $this->getAuthToken();
        if (!$token) {
            $this->unauthorized();
        }

        $decoded = JwtHelper::verify($token);
        if (!$decoded) {
            $this->unauthorized();
        }

        $data = $this->getJsonBody();
        $postId = (int)($this->getParam('post_id') ?? 0);

        $validator = new Validator();
        $validator
            ->required($data['content'] ?? '', 'content')
            ->maxLength($data['content'] ?? '', 5000, 'content');

        if ($validator->hasErrors()) {
            $this->error($validator->getFirstError(), 'VALIDATION_ERROR', 400);
        }

        $post = $this->postModel->get($postId);
        if (!$post) {
            $this->notFound('Post');
        }

        $parentCommentId = $data['parent_comment_id'] ?? null;

        try {
            $commentId = $this->commentModel->insert([
                'post_id' => $postId,
                'user_id' => $decoded['id'],
                'content' => $data['content'],
                'parent_comment_id' => $parentCommentId
            ]);

            if (!$commentId) {
                $this->error('Erro ao criar comentário', 'CREATION_ERROR', 500);
            }

            // Notifica autor do post
            if ($post['user_id'] != $decoded['id']) {
                $this->notificationModel->insert([
                    'user_id' => $post['user_id'],
                    'from_user_id' => $decoded['id'],
                    'type' => 'comment',
                    'post_id' => $postId,
                    'comment_id' => $commentId
                ]);
            }
        } catch (\Exception $e) {
            // Erros de banco de dados
            $this->error('Erro ao criar comentário', 'DATABASE_ERROR', 500);
        }

        $comment = $this->commentModel->get($commentId);
        $author = $this->userModel->get($comment['user_id']);
        $comment['author'] = $this->sanitizeUser($author);

        $this->success($comment, 'Comentário criado com sucesso', 201);
    }

    /**
     * DELETE /api/comments/{id}
     * Deleta comentário
     */
    public function delete(): void
    {
        $token = $this->getAuthToken();
        if (!$token) {
            $this->unauthorized();
        }

        $decoded = JwtHelper::verify($token);
        if (!$decoded) {
            $this->unauthorized();
        }

        $id = (int)($this->getParam('id') ?? 0);
        $comment = $this->commentModel->get($id);

        if (!$comment) {
            $this->notFound('Comentário');
        }

        $user = $this->userModel->get($decoded['id']);
        if ($comment['user_id'] != $decoded['id'] && !$user['is_admin']) {
            $this->forbidden();
        }

        try {
            $this->commentModel->delete($id);
        } catch (\Exception $e) {
            $this->error('Erro ao deletar comentário', 'DATABASE_ERROR', 500);
        }

        $this->success(null, 'Comentário deletado com sucesso');
    }

    /**
     * GET /api/comments/{id}
     * Obtém um comentário específico
     */
    public function show(): void
    {
        $id = (int)($this->getParam('id') ?? 0);

        if ($id <= 0) {
            $this->error('ID inválido', 'INVALID_ID', 400);
        }

        $comment = $this->commentModel->get($id);

        if (!$comment) {
            $this->notFound('Comentário');
        }

        $author = $this->userModel->get($comment['user_id']);
        $comment['author'] = $this->sanitizeUser($author);

        $this->success($comment);
    }
}
