<?php
require_once 'db_connect.php';

$errors = [];
$success = false;

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $rating = (int)($_POST['rating'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    // Валидация
    if (empty($name)) $errors['name'] = 'Введите ваше имя';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Введите корректный email';
    if ($rating < 1 || $rating > 5) $errors['rating'] = 'Выберите оценку от 1 до 5';
    if (empty($message)) $errors['message'] = 'Напишите ваш отзыв';

    // Если нет ошибок - сохраняем
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO feedback (name, email, rating, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $rating, $message]);
            $success = true;
            
            // Очищаем поля после успешной отправки
            $_POST = [];
        } catch (PDOException $e) {
            $errors['database'] = 'Ошибка сохранения: ' . $e->getMessage();
        }
    }
}

// В начале файла после подключения к БД
header('Content-Type: text/html; charset=utf-8');

// После успешного сохранения отзыва
if (empty($errors)) {
    try {
        $stmt = $pdo->prepare("INSERT INTO reviews (name, email, rating, message, is_approved) VALUES (?, ?, ?, ?, ?)");
        // Автоматически одобряем отзывы для демонстрации (в реальном проекте нужно модерация)
        $stmt->execute([$name, $email, $rating, $message, true]);
        $success = true;
    } catch (PDOException $e) {
        $errors['database'] = 'Ошибка при сохранении отзыва: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Обратная связь | Отчаянные домохозяйки</title>
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="feedback-page">
        <div class="container">
            <h1>Оставьте ваш отзыв</h1>
            
            <?php if ($success): ?>
                <div class="alert success">Спасибо! Ваш отзыв отправлен на модерацию.</div>
            <?php endif; ?>
            
            <div class="feedback-container">
                <!-- Форма для нового отзыва -->
                <section class="feedback-form">
                    <h2>Написать отзыв</h2>
                    <form method="POST">
                        <div class="form-group <?= isset($errors['name']) ? 'error' : '' ?>">
                            <label for="name">Ваше имя:</label>
                            <input type="text" id="name" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                            <?php if (isset($errors['name'])): ?>
                                <span class="error-message"><?= $errors['name'] ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group <?= isset($errors['email']) ? 'error' : '' ?>">
                            <label for="email">Ваш email:</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                            <?php if (isset($errors['email'])): ?>
                                <span class="error-message"><?= $errors['email'] ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group <?= isset($errors['rating']) ? 'error' : '' ?>">
                            <label>Ваша оценка:</label>
                            <div class="rating">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" <?= ($_POST['rating'] ?? 0) == $i ? 'checked' : '' ?>>
                                    <label for="star<?= $i ?>">★</label>
                                <?php endfor; ?>
                            </div>
                            <?php if (isset($errors['rating'])): ?>
                                <span class="error-message"><?= $errors['rating'] ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group <?= isset($errors['message']) ? 'error' : '' ?>">
                            <label for="message">Ваш отзыв:</label>
                            <textarea id="message" name="message" rows="5" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                            <?php if (isset($errors['message'])): ?>
                                <span class="error-message"><?= $errors['message'] ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <button type="submit" class="btn">Отправить отзыв</button>
                    </form>
                </section>
                
                <!-- Список предыдущих отзывов -->
                <section class="reviews-list">
                    <h2>Последние отзывы</h2>
                    
                    <?php if (!empty($errors['reviews'])): ?>
                        <div class="alert error"><?= $errors['reviews'] ?></div>
                    <?php elseif (empty($reviews)): ?>
                        <p>Пока нет отзывов. Будьте первым!</p>
                    <?php else: ?>
                        <div class="reviews">
                            <?php foreach ($reviews as $review): ?>
                                <article class="review">
                                    <div class="review-header">
                                        <h3><?= htmlspecialchars($review['name']) ?></h3>
                                        <div class="review-rating">
                                            <?= str_repeat('★', $review['rating']) ?>
                                        </div>
                                        <time datetime="<?= $review['created_at'] ?>">
                                            <?= date('d.m.Y', strtotime($review['created_at'])) ?>
                                        </time>
                                    </div>
                                    <div class="review-message">
                                        <?= nl2br(htmlspecialchars($review['message'])) ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>