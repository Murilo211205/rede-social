# Rede Social — API

**Integrantes**
- Murilo Folkowski — murilof@unisantos.br  
- Leonardo Vitor Alves Fonseca — leonardovfonseca@unisantos.br

## Descrição
API REST em PHP (POO) para uma rede social simples. Recursos principais: autenticação (JWT), posts, comentários, likes, follow/unfollow. Banco: MySQL.

## Estrutura

```
api/
├── public/
│   ├── index.php        # Router principal (entrada da API)
│   └── uploads/         # Possibilidade de implementar conteúdo enviado pelo usuário (imagens em posts, foto de perfil, etc)
├── src/
│   ├── Controllers/     # Regras de negócio e endpoints
│   ├── Models/          # Acesso ao banco (CRUD)
│   ├── Utils/           # DB, JWT, Validator, helpers
│   └── Exceptions/
├── database/
│   └── schema.sql       # Script de criação do banco
└── composer.json
```

## Principais endpoints

### Auth
- `POST /auth/register` — Registrar novo usuário
- `POST /auth/login` — Fazer login (retorna JWT)
- `POST /auth/verify` — Verificar token

### Posts
- `GET /posts` — Listar posts (query: page, sort)
- `GET /posts/{id}` — Obter post
- `POST /posts` — Criar post
- `PUT /posts/{id}` — Atualizar post
- `DELETE /posts/{id}` — Deletar post
- `GET /posts/search?q={query}` — Buscar posts

### Comentários
- `GET /posts/{postId}/comments` — Listar comentários
- `POST /posts/{postId}/comments` — Criar comentário
- `DELETE /comments/{id}` — Deletar comentário

### Likes
- `POST /posts/{postId}/like` — Curtir post
- `DELETE /posts/{postId}/like` — Descurtir post
- `POST /comments/{commentId}/like` — Curtir comentário
- `DELETE /comments/{commentId}/like` — Descurtir comentário

### Usuários
- `GET /users/{username}` — Perfil do usuário
- `GET /users/{username}/posts` — Posts de um usuário
- `PUT /users/profile` — Atualizar perfil
- `GET /users/search?q={query}` — Buscar usuários

### Follow
- `POST /users/{userId}/follow` — Seguir usuário
- `DELETE /users/{userId}/follow` — Deixar de seguir
- `GET /users/{userId}/followers` — Listar seguidores
- `GET /users/{userId}/following` — Listar seguindo

### Notificações
- `GET /notifications` — Listar notificações do usuário autenticado
- `GET /notifications/unread` — Contar não lidas
- `PUT /notifications/{id}/read` — Marcar como lida

---

## Pré-requisitos
- XAMPP (Apache + MySQL) 8.2.12 instalado  
- PHP 8.0+ (já incluído no XAMPP)  
- Composer

---

## Instalação do XAMPP

1. Baixe o instalador oficial do XAMPP 8.2.12:  
   👉 **[https://www.apachefriends.org/pt_br/download.html](https://www.apachefriends.org/pt_br/download.html)**

2. Execute o instalador e deixe as opções padrão (Apache, MySQL e PHP ativados).

3. Após a instalação, abra o **XAMPP Control Panel**.

4. Inicie os serviços:
   - **Apache**
   - **MySQL**

5. Verifique no navegador:  
   ```
   http://localhost
   ```
   Se abrir a página inicial do XAMPP, está funcionando.

---

## Instalação do Composer

1. Baixe o instalador oficial do Composer:  
   👉 **[https://getcomposer.org/download/](https://getcomposer.org/download/)**

2. No Windows:
   - Execute o arquivo **Composer-Setup.exe**
   - Deixe as opções padrão
   - O instalador detecta automaticamente o PHP do XAMPP
   - Finalize a instalação

3. No macOS ou Linux:
   Execute no terminal conforme instruções do site oficial.

4. Após instalar, verifique:
   ```bash
   composer -V
   ```
   Deve exibir a versão instalada.

---

## Colocar o projeto no Apache
Copie a pasta `api/` para o diretório público do XAMPP, por exemplo:

**Windows**
```
C:\xampp\htdocs\rede-social\api
```

**macOS (XAMPP)**
```
/Applications/XAMPP/htdocs/rede-social/api
```

---

## Dependências
No terminal, dentro da pasta `api`:
```bash
composer install
```

---

## Criar o banco de dados
1. Abra o XAMPP e inicie **Apache** e **MySQL**.  
2. Acesse `http://localhost/phpmyadmin`.  
3. Crie o banco `rede_social` com collation `utf8mb4_unicode_ci`.  
4. Importe `api/database/schema.sql` (aba Import).

Ou via terminal:
```bash
mysql -u root -p
CREATE DATABASE rede_social CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit
mysql -u root -p rede_social < api/database/schema.sql
```

---

## Configurar `config.ini`
No diretório `api/` há `config.ini.example`. Crie `config.ini` com os mesmos valores (ajuste se necessário):

```
[database]
host = localhost
user = root
password =
database = rede_social

[jwt]
secret = sua_chave_secreta_aqui_mude_em_producao
```

---

## VirtualHost
Vamos configurar o Apache para servir a API diretamente em `http://localhost/` apontando para `api/public`.

1. Abra (como administrador) o arquivo de VirtualHosts do Apache:
```
C:\xampp\apache\conf\extra\httpd-vhosts.conf   # Windows
/Applications/XAMPP/xamppfiles/etc/extra/httpd-vhosts.conf  # macOS (XAMPP)
```

2. Adicione o bloco (ajuste o caminho do DocumentRoot conforme sua instalação):

```apache
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot "C:/xampp/htdocs/rede-social/api/public"
    <Directory "C:/xampp/htdocs/rede-social/api/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

3. Verifique que o `mod_rewrite` está habilitado em `httpd.conf` (a linha `LoadModule rewrite_module modules/mod_rewrite.so` **não** deve estar comentada).

4. Reinicie o Apache pelo XAMPP Control Panel.

5. A API ficará disponível em:
```
http://localhost/
```

---

## Usando o Postman com o Desktop Agent para testes

Para testar os endpoints localmente (especialmente `http://localhost`), é necessário utilizar o **Postman Desktop Agent**, pois o Postman Web sozinho não permite enviar requisições para localhost.

### 1. Instalar o Postman
Baixe o Postman (versão Desktop) em:  
👉 **https://www.postman.com/downloads/**

### 2. Instalar o Postman Desktop Agent
O agente é responsável por enviar requisições para URLs locais.  
Baixe em:  
👉 **https://www.postman.com/downloads/postman-agent/**

### 3. Abrir o Desktop Agent
Após instalar, abra o aplicativo:

**Windows:** ele aparece próximo ao relógio (ícone laranja do Postman).  
**macOS:** aparece na barra superior.  

Certifique-se de que está mostrando **“Connected”** no canto inferior do Postman.

### 4. Importar a coleção da API
No Postman:
1. Clique em **Import**
2. Selecione o arquivo:
   ```
   Rede_Social_API.postman_collection.json
   ```
3. A coleção com os endpoints será carregada automaticamente.

### 5. Enviar requisições
Agora você pode testar normalmente:
- Registrar: `POST http://localhost/auth/register`
- Login: `POST http://localhost/auth/login`
- etc.

**Ah, e não esqueça de alterar a variável `TOKEN` para realizar requisições com conta autenticada.**  
Tanto o endpoint **/auth/register** quanto **/auth/login** retornam um token JWT.  
Copie o valor retornado em `token` e coloque na variável global `TOKEN` do Postman  
(*Em Postman → Environments → Globals*).

Se aparecer o aviso *“Please install the Postman Desktop Agent”*, basta verificar:
- Se o agente está aberto
- Se o Postman está em modo **Desktop**, não Web
- Se não há bloqueio de firewall

Com isso, tudo deve funcionar sem problemas.
