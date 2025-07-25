<?php
$host = 'localhost';
$db   = 'database_name';
$user = 'database_user';
$pass = 'database_password';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    $stmt = $pdo->query("SELECT COUNT(*) AS total_links FROM traces");
    $totalLinks = $stmt->fetch()['total_links'] ?? 0;

    $stmt = $pdo->query("SELECT COUNT(*) AS today_links FROM traces WHERE DATE(created_at) = CURDATE()");
    $todayLinks = $stmt->fetch()['today_links'] ?? 0;

    $stmt = $pdo->query("SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(shortened_url, '/', 3), '/', -1) AS domain, COUNT(*) AS cnt FROM traces GROUP BY domain ORDER BY cnt DESC LIMIT 1");
    $popularDomain = $stmt->fetch()['domain'] ?? 'No data';

} catch (\PDOException $e) {
    $totalLinks = 0;
    $todayLinks = 0;
    $popularDomain = 'No data';
}
?>