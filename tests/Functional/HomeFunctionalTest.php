<?php
// Mode strict_types est activé pour une meilleure gestion des types
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class HomeFunctionalTest extends TestCase
{
    /* Chemin absolu vers le fichier de la vue à tester */
    private string $view;

    protected function setUp(): void
    {
    
        // Détermine le chemin vers la vue index.php
        $this->view = realpath(__DIR__ . '/../../views/index.php');
        if ($this->view === false) {
            // Échec immédiat si le fichier est introuvable
            $this->fail("Fichier views/index.php introuvable");
        }
    }

    /* Vérifie que, lorsqu’aucun utilisateur n’est en session les liens Connexion et Inscription apparaissent et le lien Déconnexion n’apparaît pas */
    
    public function testAccueilNonConnecteAfficheConnexionEtInscription(): void
    {
        // Simule un visiteur sans session
        $_SESSION = [];

        // Capture le rendu HTML de la vue
        ob_start();
        include $this->view;
        $html = ob_get_clean();

        // Assertions : liens connexion et inscription présents
        $this->assertStringContainsString('<a href="connexion.php"', $html);
        $this->assertStringContainsString('<a href="inscription.php"', $html);

        // Assertion : pas de lien Déconnexion
        $this->assertStringNotContainsString('Déconnexion', $html);
    }

    // Vérifie que, lorsqu’un utilisateur est connecté le bouton Déconnexion est affiché  
     
    public function testAccueilConnecteAfficheDeconnexion(): void
    {
        // Simule un utilisateur connecté en injectant $_SESSION['utilisateur']
        $_SESSION['utilisateur'] = [
            'id'    => 1,
            'nom'   => 'Bob',
            'email' => 'bob@test.fr',
            'role'  => 'utilisateur',
        ];

        // Capture du rendu HTML
        ob_start();
        include $this->view;
        $html = ob_get_clean();

        // Assertion : présence du lien Déconnexion avec sa classe CSS
        $this->assertStringContainsString(
            '<a href="../controllers/logout.php" class="btn btn-outline-light">Déconnexion</a>',
            $html
        );
    }
}

// Pour exécuter ce test, utilisez la commande suivante dans le terminal à la racine du projet : 
// vendor/bin/phpunit --colors --testsuite Functional