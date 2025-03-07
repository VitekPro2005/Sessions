<?php
session_start();

$dbSource = __DIR__ . '/blogsWithCategories.db';
$db = new PDO("sqlite:$dbSource");

$messages = [
    'default' => '',
    'create' => 'Пост создан',
    'update' => 'Пост изменен',
    'delete' => 'Пост удален',
    'title' => 'Заголовок не может быть пустым',
    'content' => 'Содержание не может быть пустым',
    'category' => 'Категория не может быть пустой',
    'post' => 'Пост не существует'
];

$success = $_GET["success"] ?? false;
$action = $_GET["action"] ?? 'default';
$formActionText = 'create';
$formSubmitText = 'Создать';

$message = $messages[$_GET["message"] ?? 'default'] ?? '';
$error = $_GET["error"] ?? false;

$method = $_SERVER['REQUEST_METHOD'];

$errors = [];

// Инициализация таблиц

$db->query('CREATE TABLE IF NOT EXISTS `categories` (
    `id` INTEGER PRIMARY KEY,
    `title` VARCHAR NOT NULL
);');

$db->query('CREATE TABLE IF NOT EXISTS `posts` (
    `id` integer primary key,
    `title` VARCHAR NOT NULL,
    `content` TEXT NOT NULL,
    `category_id` INTEGER,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);');

$db->query('CREATE TABLE IF NOT EXISTS `users` (
    `id` integer primary key,
    `nickname` VARCHAR NOT NULL,
    `email` VARCHAR NOT NULL,
    `password` VARCHAR NOT NULL
);');
// Получение категорий
$categories = $db->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
if (empty($categories)) {
    $db->query("INSERT INTO categories (id, title) VALUES (1, 'Coding'), (2, 'Finance'), (3, 'Movies')");
}
// Регистрация
if ($action == 'register' && $method == 'POST') {
    $nickname = trim($_POST['nickname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$nickname) {
        $_SESSION['errors']['nickname'] = 'Введите никнейм для регистрации';
    }

    if (!$email) {
        $_SESSION['errors']['email'] = 'Введите E-mail для регистрации';
    }

    if (!$password) {
        $_SESSION['errors']['password'] = 'Введите пароль для регистрации';
    }

    if ($_SESSION['errors']) {
        $_SESSION['old']['nickname'] = $nickname;
        $_SESSION['old']['email'] = $email;

        header('Location: /form.php');
        exit;
    }

    $password = password_hash($password, PASSWORD_DEFAULT);

    $statement = $db->prepare("INSERT INTO users (nickname, email, password) values (?, ?, ?);");
    $statement->execute([$nickname, $email, $password]);

    $_SESSION['messages'][] = 'Вы успешно зарегистрировались';

    unset($_SESSION['old']);

    header('Location: /form.php');
    exit;
}

// Вход
if ($action == 'login' && $method == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email) {
        $_SESSION['errors']['email'] = 'Введите E-mail для входа';
    }

    if (!$password) {
        $_SESSION['errors']['password'] = 'Введите пароль для входа';
    }

    if ($_SESSION['errors']) {
        $_SESSION['old']['email'] = $email;

        header('Location: /form.php');
        exit;
    }

    $statement = $db->prepare("SELECT nickname, password FROM users WHERE email=?");
    $statement->execute([$email]);
    $user = $statement->fetch(PDO::FETCH_ASSOC);

    if (password_verify($password, $user['password'])) {
        $_SESSION['nickname'] = $user['nickname'];
        $_SESSION['messages'][] = 'Вы успешно вошли';
    } else {
        $_SESSION['errors'][] = 'Не удалось войти (неверный логин или пароль)';
    }

    unset($_SESSION['old']);

    header('Location: /form.php');
    exit;
}

// Выход
if ($action == 'logout' && $method == 'GET') {
    session_destroy();
    header('Location: /form.php');
    exit;
}

// CRUD -> Update
if ($action == 'update') {
    $id = (int)$_GET["id"] ?? 0;
    $statement = $db->prepare("SELECT * from posts where id = ?");
    $statement->execute([$id]);
    $post = $statement->fetch(PDO::FETCH_ASSOC);
    if (!$post) {
        $_SESSION['errors'][] = $messages['post'];
    } else {
        $formActionText = 'save';
        $formSubmitText = 'Изменить';
        if (isset($_SESSION['old'])) {
            $post = array_merge($post, $_SESSION['old']);
            unset($_SESSION['old']);
        }
    }
}

// CRUD -> Save (Update)
if ($action == 'save' && $method == 'POST') {
    $id = (int)$_POST["id"] ?? 0;
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $category_id = (int)$_POST['category_id'] ?? 0;

    if (empty($title)) {
        $_SESSION['errors'][] = $messages['title'];
    }
    if (empty($content)) {
        $_SESSION['errors'][] = $messages['content'];
    }
    if (empty($category_id)) {
        $_SESSION['errors'][] = $messages['category'];
    }

    if (empty($_SESSION['errors'])) {
        $statement = $db->prepare("UPDATE posts SET title = ?, content = ?, category_id = ? where id = ?");
        $statement->execute([$title, $content, $category_id, $id]);
        $_SESSION['messages'][] = $messages['update'];
        header('Location: /form.php?success=true&message=update');
        exit();
    } else {
        $_SESSION['old'] = [
            'id' => $id,
            'title' => $title,
            'content' => $content,
            'category_id' => $category_id,
        ];
        header('Location: /form.php?action=update&id=' . $id);
        exit();
    }
}

// CRUD -> Delete
if ($action == 'delete') {
    $id = (int)$_GET["id"] ?? 0;

    $statement = $db->prepare("SELECT * from posts where id = ?");
    $statement->execute([$id]);
    $post = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        $_SESSION['errors'][] = $messages['post'];
    } else {
        $statement = $db->prepare("DELETE FROM posts WHERE id = ?");
        $statement->execute([$id]);
        $_SESSION['messages'][] = $messages['delete'];
        header('Location: /form.php?success=true&message=delete');
        exit();
    }
}

// CRUD -> Create
if ($action == 'create' && $method == 'POST') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $category_id = (int)$_POST['category_id'] ?? 0;

    if (empty($title)) {
        $_SESSION['errors'][] = $messages['title'];
    }
    if (empty($content)) {
        $_SESSION['errors'][] = $messages['content'];
    }
    if (empty($category_id)) {
        $_SESSION['errors'][] = $messages['category'];
    }

    if (empty($_SESSION['errors'])) {
        $statement = $db->prepare("INSERT INTO posts (title, content, category_id) values (?, ?, ?)");
        $statement->execute([$title, $content, $category_id]);
        $_SESSION['messages'][] = $messages['create'];
        header('Location: /form.php?success=true&message=create');
        exit();
    }
}

// CRUD -> Read
$statement = $db->query('SELECT posts.*, categories.title as category_title FROM posts
LEFT JOIN categories ON posts.category_id = categories.id ORDER BY posts.id DESC');
$posts = $statement->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Post</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h1>Create Post</h1>

    <?php if ($_SESSION['messages'] ?? false): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php foreach ($_SESSION['messages'] as $message): ?>
                <?= $message ?><br>
            <?php endforeach; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($_SESSION['errors'] ?? false): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php foreach ($_SESSION['errors'] as $error): ?>
                <?= $error ?><br>
            <?php endforeach; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!isset($_SESSION['nickname'])): ?>
        <hr>
        <h2>Регистрация</h2>
        <form method="POST" action="/form.php?action=register">
            <div class="mb-3">
                <label for="nickname" class="form-label">Nickname</label>
                <input type="text" class="form-control <?= $_SESSION['errors']['nickname'] ?? false ? 'is-invalid' : '' ?>" id="nickname" name="nickname" value="<?= $_SESSION['old']['nickname'] ?? '' ?>">
                <?php if ($_SESSION['errors']['nickname'] ?? false): ?>
                    <div class="invalid-feedback">Введите никнейм</div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control <?= $_SESSION['errors']['email'] ?? false ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= $_SESSION['old']['email'] ?? '' ?>">
                <?php if ($_SESSION['errors']['email'] ?? false): ?>
                    <div class="invalid-feedback">Введите email</div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control <?= $_SESSION['errors']['password'] ?? false ? 'is-invalid' : '' ?>" id="password" name="password">
                <?php if ($_SESSION['errors']['password'] ?? false): ?>
                    <div class="invalid-feedback">Введите пароль</div>
                <?php endif; ?>
            </div>
            <button type="submit" class="btn btn-primary">Зарегистрироваться</button>
        </form>

        <hr>
        <h2>Вход</h2>
        <form method="POST" action="/form.php?action=login">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control <?= $_SESSION['errors']['email'] ?? false ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= $_SESSION['old']['email'] ?? '' ?>">
                <?php if ($_SESSION['errors']['email'] ?? false): ?>
                    <div class="invalid-feedback">Введите email</div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control <?= $_SESSION['errors']['password'] ?? false ? 'is-invalid' : '' ?>" id="password" name="password">
                <?php if ($_SESSION['errors']['password'] ?? false): ?>
                    <div class="invalid-feedback">Введите пароль</div>
                <?php endif; ?>
            </div>
            <button type="submit" class="btn btn-primary">Войти</button>
        </form>
        <hr>
    <?php else: ?>
        <h2>Вы вошли как: <?= $_SESSION['nickname'] ?></h2>
        <a style="width: 150px" href="/form.php?action=logout" class="btn btn-danger">Выйти</a>
        <hr>
    <?php endif; ?>

    <form method="POST" action="/form.php?action=<?= $formActionText ?>">
        <input type="text" name="id" value="<?= $post['id'] ?? 0 ?>" hidden>

        <div class="mb-3">
            <label for="title" class="form-label">Название</label>
            <input type="text" class="form-control <?= in_array($messages['title'], $_SESSION['errors'] ?? []) ? 'is-invalid' : '' ?>" id="title"
                   name="title" value="<?= htmlspecialchars($post['title'] ?? '') ?>">
            <?php if (in_array($messages['title'], $_SESSION['errors'] ?? [])): ?>
                <div class="invalid-feedback"><?= $messages['title'] ?></div>
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label for="content" class="form-label">Содержание</label>
            <textarea class="form-control <?= in_array($messages['content'], $_SESSION['errors'] ?? []) ? 'is-invalid' : '' ?>" id="content"
                      name="content" rows="5"><?= htmlspecialchars($post['content'] ?? '') ?></textarea>
            <?php if (in_array($messages['content'], $_SESSION['errors'] ?? [])): ?>
                <div class="invalid-feedback"><?= $messages['content'] ?></div>
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label for="category_id" class="form-label">Категория</label>
            <select class="form-control <?= in_array($messages['category'], $_SESSION['errors'] ?? []) ? 'is-invalid' : '' ?>" id="category_id" name="category_id">
                <option value="">Выберите категорию</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= $category['id'] ?>" <?= ($post['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($category['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (in_array($messages['category'], $_SESSION['errors'] ?? [])): ?>
                <div class="invalid-feedback"><?= $messages['category'] ?></div>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary"><?= $formSubmitText ?></button>
    </form>
    <br>
    <?php foreach ($posts as $post): ?>
        <div class="card">
            <div class="card-header"><?= htmlspecialchars($post['title']) ?>
                <span class="badge bg-secondary"><?= htmlspecialchars($post['category_title']) ?></span>
                <a style="width: 150px" href="/form.php?action=update&id=<?= $post['id'] ?>" class="btn btn-warning">изменить</a>
                <a style="width: 150px" href="/form.php?action=delete&id=<?= $post['id'] ?>" class="btn btn-danger">удалить</a>
            </div>
            <div class="card-body"><?= htmlspecialchars($post['content']) ?></div>
        </div><br>
    <?php endforeach; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
unset($_SESSION['messages']);
unset($_SESSION['errors']);
unset($_SESSION['old']);
?>