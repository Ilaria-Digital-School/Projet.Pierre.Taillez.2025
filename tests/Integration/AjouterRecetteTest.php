<?php
// Active le mode strict pour une meilleure gestion des types
declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

final class AjouterRecetteTest extends TestCase
{
    private string $viewFile;

    // Prépare l'environnement de test avant chaque méthode de test 
     protected function setUp(): void
    {
        // Recherche le chemin absolu vers la vue ajouter-recette.php
        $this->viewFile = realpath(__DIR__ . '/../../views/ajouter-recette.php');
        if ($this->viewFile === false) {
            // Arrête le test si la vue est introuvable
            $this->fail('Le fichier views/ajouter-recette.php est introuvable');
        }

        // Initialise les superglobales pour simuler une requête GET sans session ni données
        $_SESSION = [];
        $_POST    = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    /* Vérifie que la page affiche un message d’invitation à se connecter et cache le formulaire si l’utilisateur n’est pas connecté. */
    public function testAccesSansSessionAfficheMessageConnexion(): void
    {
        // Lance la session si elle n'est pas déjà démarrée
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Simule un visiteur anonyme (aucune session active)
        $_SESSION = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_POST = [];

        // Charge la vue dans un buffer pour capturer la sortie HTML
        ob_start();
        require $this->viewFile;
        $html = (string) ob_get_clean();

        // Vérifie que le message de connexion s'affiche
        $this->assertStringContainsString(
            "Vous n'êtes pas connecté",
            $html,
            'Le message d’invitation à se connecter doit s’afficher'
        );

        // Vérifie que le formulaire n'est pas affiché
        $this->assertStringNotContainsString(
            '<form',
            $html,
            'Le formulaire ne doit pas apparaître pour un visiteur non connecté'
        );
    }

    /* Vérifie que le formulaire affiche un message d’erreur générique lorsque les champs requis sont vides dans une soumission POST. */
    public function testPostSansChampsRequisAfficheMessageGenerique(): void
    {
        // Simule un utilisateur connecté avec des données fictives
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['utilisateur'] = [
            'id'    => 1,
            'nom'   => 'Alice',
            'email' => 'alice@test.com',
            'role'  => 'utilisateur',
        ];

        // Simule une soumission POST invalide avec champs requis vides
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'titre'           => '',
            'type_de_cuisine' => '',
        ];

        // Charge la vue et capture le contenu HTML
        ob_start();
        require $this->viewFile;
        $html = (string) ob_get_clean();

        // Vérifie que le message d’erreur générique est affiché
        $this->assertStringContainsString(
            'Merci de remplir tous les champs obligatoires.',
            $html,
            'La vue doit afficher le message d’erreur générique en cas de POST invalide'
        );

        // Vérifie que le formulaire reste visible pour permettre une correction
        $this->assertStringContainsString(
            '<form method="POST"',
            $html,
            'Le formulaire doit rester visible pour corriger les erreurs'
        );
    }
}
// Pour exécuter ce test, utilisez la commande : vendor/bin/phpunit --testsuite Integration