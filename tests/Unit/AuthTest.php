<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
// On importe la classe Auth que l’on va tester
use Services\Auth;

class AuthTest extends TestCase
{
    /* tearDown() est exécuté après chaque test. On nettoie la superglobale $_SESSION pour garantir l’indépendance des tests. */
    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    /* 1-Teste isLoggedIn() quand la session n’a pas été démarrée ou qu’aucun utilisateur n’est identifié. On s’attend à false. */
    public function testIsLoggedInFalseWhenNoSession(): void
    {
        // On s’assure qu’il n’y a pas de données de session
        $_SESSION = [];
        // L’utilisateur n’est pas connecté
        $this->assertFalse(Auth::isLoggedIn());
    }

    // 2-Teste isLoggedIn() quand un identifiant d’utilisateur est présent dans la session. On s’attend à true. 
    public function testIsLoggedInTrueWhenSessionIdSet(): void
    {
        // On simule une session avec un utilisateur connecté
        $_SESSION['utilisateur']['id'] = 42;
        // Auth::isLoggedIn() doit renvoyer true
        $this->assertTrue(Auth::isLoggedIn());
    }

    // 3-Teste isAdmin() quand l’utilisateur est connecté mais n’a pas le rôle « admin ». Résultat attendu : false. 
    public function testIsAdminFalseForNonAdmin(): void
    {
        // Session avec rôle « user »
        $_SESSION['utilisateur'] = [
            'id'   => 1,
            'role' => 'user'
        ];
        // L’utilisateur n’est pas admin
        $this->assertFalse(Auth::isAdmin());
    }

    // 4-Teste isAdmin() quand l’utilisateur a le rôle « admin ». isAdmin() doit renvoyer true.
     
    public function testIsAdminTrueWhenRoleAdmin(): void
    {
        // Session avec rôle « admin »
        $_SESSION['utilisateur'] = [
            'id'   => 1,
            'role' => 'admin'
        ];
        // L’utilisateur est bien admin
        $this->assertTrue(Auth::isAdmin());
    }
}
// Pour exécuter ce test, utilisez la commande suivante dans le terminal :
// vendor/bin/phpunit tests/unit/AuthTest.php