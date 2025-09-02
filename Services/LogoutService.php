<?php
class LogoutService
{
    /* Termine la session utilisateur et renvoie le chemin vers la page de connexion. Return string Chemin relatif pour la redirection */
    public function logout(): string
    {
        // Si aucune session n’est active, on en démarre une
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Vide toutes les données stockées en session
        $_SESSION = [];

        // Détruit la session côté serveur et supprime le cookie de session
        session_destroy();

        // Retourne la route vers laquelle on redirigera l’utilisateur
        return '../views/connexion.php';
    }
}