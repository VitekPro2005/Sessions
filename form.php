<?php

$messages = [
    'default' => '',
    'create' => 'Пост создан',
    'update' => 'Пост изменен',
    'delete' => 'Пост удален',
    'title' => 'Заголовок не может быть пустой',
];

$success = $_GET["success"] ?? false;
$action = $_GET["action"] ?? 'default';
$formActionText = 'create';
$formSubmitText = 'Создать';

$message = $messages[$_GET["message"] ?? 'default'] ?? '';

$error = $_GET["error"] ?? false;

$db = new PDO("sqlite:database.db");

$statement = $db->query('CREATE TABLE IF NOT EXISTS `posts` (
	`id` integer primary key,
	`title` VARCHAR NOT NULL,
	`content` TEXT NOT NULL
);');

$statement = $db->query('CREATE TABLE IF NOT EXISTS `users` (
	`id` integer primary key,
	`nickname` VARCHAR NOT NULL,
	`email` VARCHAR NOT NULL,
	`password` VARCHAR NOT NULL
);');

$method = $_SERVER['REQUEST_METHOD'];

session_start();

// Register
if ($action == 'register' && $method == 'POST') {
    $nickname = trim($_POST['nickname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (! $nickname) {
        $_SESSION['errors']['nickname'] = 'Введите никнейм для регистрации';
    }

    if (! $email) {
        $_SESSION['errors']['email'] = 'Введите E-mail для регистрации';
    }

    if (! $password) {
        $_SESSION['errors']['password'] = 'Введите пароль для регистрации';
    }

    if ($_SESSION['errors']) {
        $_SESSION['old']['nickname'] = $nickname;
        $_SESSION['old']['email'] = $email;

        header('Location: /');
        exit;
    }

    $password = password_hash($password, PASSWORD_DEFAULT);

    $statement = $db->prepare("INSERT INTO users (nickname, email, password) values (?, ?, ?);");
    $statement->execute([$nickname, $email, $password]);

    $_SESSION['messages'][] = 'Вы успешно зарегистрировались';

    unset($_SESSION['old']);

    header('Location: /');
    exit;
}

// Login
if ($action == 'login' && $method == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (! $email) {
        $_SESSION['errors']['email'] = 'Введите E-mail для входа';
    }

    if (! $password) {
        $_SESSION['errors']['password'] = 'Введите пароль для входа';
    }

    if ($_SESSION['errors']) {
        $_SESSION['old']['email'] = $email;

        header('Location: /');
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

    header('Location: /');
    exit;
}

// Logout
if ($action == 'logout' && $method == 'GET') {
    session_destroy();
    header('Location: /');
    exit;
}

//CRUD -> Update
if ($action == 'update') {
    $id = (int)$_GET["id"] ?? 0;
    $statement = $db->prepare("SELECT * from posts where id = ?");
    $statement->execute([$id]);
    $post = $statement->fetch(PDO::FETCH_ASSOC);
    $formActionText = 'save';
    $formSubmitText = 'Изменить';
}
if ($action == 'save') {
    $id = (int)$_POST["id"] ?? 0;
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';


    $statement = $db->prepare("UPDATE posts SET title = ?, content = ? where id = ?");
    $statement->execute([$title, $content, $id]);
    header('Location: /?success=true&message=update');
    exit();
}

//CRUD -> Delete
if ($action == 'delete') {
    $id = (int)$_GET["id"] ?? 0;

    //??валидация!!

    $statement = $db->prepare("DELETE FROM posts WHERE id = :id");
    $statement->execute([$id]);

    $success = true;
    header('Location: /?success=true&message=delete');
    exit();
}

//CRUD -> Create
if ($action == 'create' && $method == 'POST') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';

    $errors = [];
    //валидация
    if ($title == '') {
        header('Location: /?error=true&message=title');
        exit();
    }

    $statement = $db->prepare("INSERT INTO posts (title, content) values (?, ?)");
    $statement->execute([$title, $content]);
    //переоткрыть страницу методом GET redirect

    $success = true;
    header('Location: /?success=true&message=create');
    exit();
}

//CRUD -> Read
$statement = $db->query('SELECT * from posts ORDER BY id DESC');
$posts = $statement->fetchAll(PDO::FETCH_ASSOC);


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Post</title>
    <!-- Подключаем Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <?php if ($action == 'update'): ?>
            <h1>Edit Post</h1>
        <?php else: ?>
            <h1>Create Post</h1>
        <?php endif; ?>

        <?php if ($_SESSION['messages']): ?>
            <?php foreach ($_SESSION['messages'] as $message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <p><?= $message ?></p>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($_SESSION['errors']): ?>
            <?php foreach ($_SESSION['errors'] as $error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <p><?= $error ?></p>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (! isset($_SESSION['nickname'])): ?>
            <hr>

            <h2>Регистрация</h2>
            <form method="POST" action="/?action=register">
                <!-- Поле Nickname -->
                <div class="mb-3">
                    <label for="nickname" class="form-label">Nickname</label>
                    <input type="text" class="form-control <?php if ($_SESSION['errors']['nickname']): ?>is-invalid<?php endif; ?>" id="nickname" name="nickname" value="<?= $_SESSION['old']['nickname'] ?? '' ?>">

                    <?php if ($_SESSION['errors']['nickname']): ?>
                        <div class="invalid-feedback">
                            Введите никнейм
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Поле Email -->
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control <?php if ($_SESSION['errors']['email']): ?>is-invalid<?php endif; ?>" id="email" name="email" value="<?= $_SESSION['old']['email'] ?? '' ?>" autocomplete="off">

                    <?php if ($_SESSION['errors']['email']): ?>
                        <div class="invalid-feedback">
                            Введите email
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Поле Password -->
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control <?php if ($_SESSION['errors']['password']): ?>is-invalid<?php endif; ?>" id="password" name="password">

                    <?php if ($_SESSION['errors']['password']): ?>
                        <div class="invalid-feedback">
                            Введите пароль
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Кнопка отправки формы -->
                <button type="submit" class="btn btn-primary">Зарегистрироваться</button>
            </form>

            <hr>

            <h2>Вход</h2>
            <form method="POST" action="/?action=login">
                <!-- Поле Email -->
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control <?php if ($_SESSION['errors']['email']): ?>is-invalid<?php endif; ?>" id="email" name="email" value="<?= $_SESSION['old']['email'] ?? '' ?>">

                    <?php if ($_SESSION['errors']['email']): ?>
                        <div class="invalid-feedback">
                            Введите email
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Поле Password -->
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control <?php if ($_SESSION['errors']['password']): ?>is-invalid<?php endif; ?>" id="password" name="password">

                    <?php if ($_SESSION['errors']['password']): ?>
                        <div class="invalid-feedback">
                            Введите пароль
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Кнопка отправки формы -->
                <button type="submit" class="btn btn-primary">Войти</button>
            </form>

            <hr>
        <?php else: ?>
            <h2>Вы вошли как: <?= $_SESSION['nickname'] ?></h2>
            <a style="width: 150px" href="/?action=logout" class="btn btn-danger">Выйти</a>
            <hr>
        <?php endif; ?>

        <form method="POST" action="/?action=<?= $formActionText ?>">
            <input type="text" name="id" value="<?= $post['id'] ?? 0 ?>" hidden>

            <!-- Поле Title -->
            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input type="text" class="form-control  <?php if ($error): ?>is-invalid<?php endif; ?>" id="title"
                    name="title"
                    value="<?= $post['title'] ?? '' ?>">

                <?php if ($error): ?>
                    <div class="invalid-feedback">
                        <?= $message ?>
                    </div>
                <?php endif; ?>

            </div>

            <!-- Поле Content -->
            <div class="mb-3">
                <label for="content" class="form-label">Content</label>
                <textarea class="form-control <?php if ($error): ?>is-invalid<?php endif; ?>" id="content" name="content"
                    rows="5"><?= $post['content'] ?? '' ?></textarea>

                <?php if ($error): ?>
                    <div class="invalid-feedback">
                        ggg
                    </div>
                <?php endif; ?>


            </div>

            <!-- Кнопка отправки формы -->
            <button type="submit" class="btn btn-primary"><?= $formSubmitText ?></button>
            <?php if ($action == 'update'): ?>
                <a style="width: 150px" href="/" class="btn btn-danger">Отменить</a>
            <?php endif; ?>
        </form>

        <br>

        <?php foreach ($posts as $post): ?>
            <div class="card">
                <div class="card-header"><?= htmlspecialchars($post['title']) ?></div>
                <div class="card-body">
                    <p><?= htmlspecialchars($post['content']) ?></p>
                    <a style="width: 150px" href="/?action=update&id=<?= $post['id'] ?>" class="btn btn-warning">Изменить</a>
                    <a style="width: 150px" href="/?action=delete&id=<?= $post['id'] ?>" class="btn btn-danger">Удалить</a>
                </div>
            </div><br>
        <?php endforeach; ?>
    </div>
    <?php
    unset($_SESSION['messages']);
    unset($_SESSION['errors']);
    ?>
    <!-- Подключаем Bootstrap JS (необязательно, если не используете JS-компоненты) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>