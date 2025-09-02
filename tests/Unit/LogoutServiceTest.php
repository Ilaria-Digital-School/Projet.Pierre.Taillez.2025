<?php
// Import de la classe de test de PHPUnit
use PHPUnit\Framework\TestCase;

// Chargement de la classe AuthService à tester
require_once __DIR__ . '/../../services/LogoutService.php';

class LogoutServiceTest extends TestCase
{
    /* Prépare l’environnement avant chaque test. Si une session est active, on la détruit pour repartir d’un état propre et on réinitialise le tableau $_SESSION */
    protected function setUp(): void
    {
        // Si une session PHP est déjà démarrée, on la termine
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        // On vide explicitement le superglobal pour éviter toute donnée résiduelle
        $_SESSION = [];
    }

    // Teste que la méthode logout vide complètement $_SESSION et retourne la bonne URL de redirection
    public function testLogoutClearsSessionAndReturnsRedirect()
    {
        // Démarre une nouvelle session pour simuler un utilisateur connecté
        session_start();
        // Ajoute une donnée fictive dans la session
        $_SESSION['user_id'] = 123;

        // Instanciation du service et appel de la méthode à tester
        $auth = new LogoutService();
        $redirect = $auth->logout();

        // Assert 1 : la session doit être totalement vide après logout()
        $this->assertEmpty(
            $_SESSION,
            'La session doit être vide après logout.'
        );

        // Assert 2 : la redirection renvoyée doit correspondre à la page de connexion
        $this->assertEquals(
            '../views/connexion.php',
            $redirect,
            'Le chemin de redirection après déconnexion est incorrect.'
        );
    }
}
// Pour exécuter ce test, utilisez la commande suivante dans le terminal :
// vendor/bin/phpunit tests/unit/LogoutServiceTest.php