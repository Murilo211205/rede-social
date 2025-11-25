<?php
namespace Api\Controllers;

use Api\Models\Post;
use Api\Models\User;
use Api\Utils\JwtHelper;
use Api\Utils\StringUtils;
use Api\Utils\Validator;

class PostController extends Controller
{
    private $postModel;
    private $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->postModel = new Post();
        $this->userModel = new User();
    }

    /**
     * GET /api/posts
     * Lista posts com paginaÃ§Ã£o e filtro
     */
    public function list(): void
    {
        $page = (int)($this->getParam('page') ?? 1);
        $sort = $this->getParam('sort') ?? 'recent';
        $limit = 10;

        if ($page < 1) $page = 1;

        $posts = $this->postModel->listWithUser($page, $sort, $limit);
        $total = $this->postModel->countPosts();
        $pages = ceil($total / $limit);

        $this->success([
            'posts' => $posts,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => $pages
            ]
        ]);
    }

    /**
     * GET /api/posts/{id}
     * ObtÃ©m um post especÃ­fico
     */
    public function show(): void
    {
        $id = (int)($this->getParam('id') ?? 0);

        if ($id <= 0) {
            $this->error('ID invÃ¡lido', 'INVALID_ID', 400);
        }

        $post = $this->postModel->get($id);

        if (!$post) {
            $this->notFound('Post');
        }

        // Busca usuÃ¡rio e remove dados sensÃ­veis
        $user = $this->userModel->get($post['user_id']);
        $post['author'] = $this->sanitizeUser($user);

        $this->success($post);
    }

    /**
     * POST /api/posts
     * Cria novo post
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

        // ValidaÃ§Ã£o
        $validator = new Validator();
        $validator
            ->required($data['title'] ?? '', 'title')
            ->maxLength($data['title'] ?? '', 200, 'title')
            ->required($data['content'] ?? '', 'content');

        if ($validator->hasErrors()) {
            $this->error($validator->getFirstError(), 'VALIDATION_ERROR', 400);
        }

        // Gera slug base
        // O banco garante unicidade via UNIQUE constraint
        // Se houver duplicata, ajustamos adicionando sufixo numÃ©rico
        $slug = StringUtils::slugify($data['title']);
        $finalSlug = $slug;
        $attempt = 1;

        while (true) {
            try {
                $postId = $this->postModel->insert([
                    'user_id' => $decoded['id'],
                    'title' => $data['title'],
                    'slug' => $finalSlug,
                    'content' => $data['content']
                ]);

                if (!$postId) {
                    $this->error('Erro ao criar post', 'CREATION_ERROR', 500);
                }

                // Sucesso! Slug Ãºnico foi inserido
                break;
            } catch (\Exception $e) {
                // Se for erro de slug duplicado, tenta com sufixo numérico
                if (strpos($e->getMessage(), 'Duplicate entry') !== false && 
                    (strpos($e->getMessage(), 'posts.slug') !== false || strpos($e->getMessage(), "for key 'slug'") !== false)) {
                    $finalSlug = $slug . '-' . $attempt;
                    $attempt++;
                    
                    // Limite de tentativas para evitar loop infinito
                    if ($attempt > 100) {
                        $this->error('Não foi possível gerar slug único', 'SLUG_ERROR', 500);
                    }
                } else {
                    // Outros erros de banco
                    $this->error('Erro ao criar post', 'DATABASE_ERROR', 500);
                }
            }
        }

        $post = $this->postModel->get($postId);
        $user = $this->userModel->get($post['user_id']);
        $post['author'] = $this->sanitizeUser($user);

        $this->success($post, 'Post criado com sucesso', 201);
    }

    /**
     * PUT /api/posts/{id}
     * Atualiza post
     */
    public function update(): void
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
        $post = $this->postModel->get($id);

        if (!$post) {
            $this->notFound('Post');
        }

        if ($post['user_id'] != $decoded['id']) {
            $this->forbidden();
        }

        $data = $this->getJsonBody();

        $validator = new Validator();
        $validator
            ->required($data['title'] ?? '', 'title')
            ->maxLength($data['title'] ?? '', 200, 'title')
            ->required($data['content'] ?? '', 'content');

        if ($validator->hasErrors()) {
            $this->error($validator->getFirstError(), 'VALIDATION_ERROR', 400);
        }

        // Usa a assinatura correta do método abstrato update(int $id, array $data)
        try {
            $this->postModel->update($id, [
                'title' => $data['title'],
                'content' => $data['content']
            ]);
        } catch (\Exception $e) {
            $this->error('Erro ao atualizar post', 'DATABASE_ERROR', 500);
        }
        $updated = $this->postModel->get($id);

        $this->success($updated, 'Post atualizado com sucesso');
    }

    /**
     * DELETE /api/posts/{id}
     * Deleta post
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
        $post = $this->postModel->get($id);

        if (!$post) {
            $this->notFound('Post');
        }

        // Admin pode deletar qualquer post
        $user = $this->userModel->get($decoded['id']);
        if ($post['user_id'] != $decoded['id'] && !$user['is_admin']) {
            $this->forbidden();
        }

        try {
            $this->postModel->delete($id);
        } catch (\Exception $e) {
            $this->error('Erro ao deletar post', 'DATABASE_ERROR', 500);
        }

        $this->success(null, 'Post deletado com sucesso');
    }

    /**
     * GET /api/posts/search?q=query
     */
    public function search(): void
    {
        $query = $this->getParam('q') ?? '';

        if (strlen($query) < 2) {
            $this->error('Query deve ter no mÃ­nimo 2 caracteres', 'INVALID_QUERY', 400);
        }

        $posts = $this->postModel->search($query, 20);

        $this->success(['posts' => $posts]);
    }
}
