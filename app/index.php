<?php
require_once __DIR__ . '/db.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    if ($name !== '') {
        $stmt = $pdo->prepare("INSERT INTO users (name, created_at) VALUES (:name, NOW())");
        $stmt->execute(['name' => $name]);
        $message = "✅ Saved: " . htmlspecialchars($name);
    } else {
        $message = "❌ Please enter a name.";
    }
}

$stmt = $pdo->query("SELECT id, name, created_at FROM users ORDER BY id DESC LIMIT 10");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
  <head><title>PHP Docker App</title></head>
  <body>
    <h2>PHP + MySQL on AWS RDS</h2>
    <form method="post">
      <input name="name" placeholder="Enter name" required>
      <button type="submit">Save</button>
    </form>
    <p><?= $message ?></p>
    <ul>
      <?php foreach ($users as $u): ?>
        <li><?= htmlspecialchars($u['name']) ?> — <?= $u['created_at'] ?></li>
      <?php endforeach; ?>
    </ul>
  </body>
</html>

