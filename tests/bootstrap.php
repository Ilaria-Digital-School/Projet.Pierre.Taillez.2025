<?php
// Mode strict_types est activé pour une meilleure gestion des types
declare(strict_types=1);

// Charge automatiquement toutes les classes et dépendances via Composer
require __DIR__ . '/../vendor/autoload.php';

// Charge les variables d'environnement 
if (file_exists(__DIR__ . '/../.env.test')) {
    Dotenv\Dotenv::createImmutable(__DIR__ . '/..', '.env.test')->load();
}

// Récupère les variables d’environnement utilisées par PHPUnit pour configurer la base de données
// Si une variable est absente, une valeur par défaut est utilisée
$host   = getenv('DB_HOST') ?: '127.0.0.1';
$port   = getenv('DB_PORT') ?: '3306';
$user   = getenv('DB_USER') ?: 'root';
$pass   = getenv('DB_PASS') ?: '';
$dbname = getenv('DB_NAME') ?: 'ma_base_test';

// Configure MySQLi pour lancer des exceptions en cas d'erreur (plutôt que des warnings silencieux)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Tente de se connecter à la base de données MySQL en utilisant les paramètres récupérés
try {
    $mysqli = new mysqli($host, $user, $pass, $dbname, (int)$port);

    // Rend la connexion disponible globalement pour les scripts exécutés après ce bootstrap
    $GLOBALS['mysqli'] = $mysqli;
} catch (mysqli_sql_exception $e) {
    // En cas d’erreur de connexion, affiche un message sur STDERR et quitte le processus avec un code d’erreur
    fwrite(STDERR, "Erreur de connexion BDD en bootstrap : " . $e->getMessage() . "\n");
    exit(1);
}

// Chargement optionnel du schéma de test à partir du fichier fixtures/schema.sql
// Utile pour réinitialiser la base à une structure connue avant les tests
$schema = file_get_contents(__DIR__ . '/fixtures/schema.sql');
if ($schema !== false) {
    $mysqli->multi_query($schema);

    // Parcourt les requêtes multiples jusqu'à la dernière pour s'assurer qu'elles sont toutes exécutées
    while ($mysqli->more_results() && $mysqli->next_result()) { }
}