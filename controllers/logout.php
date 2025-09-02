<?php
// Inclut le fichier qui contient la classe LogoutService.
require_once __DIR__ . '/../services/LogoutService.php';

// Instanciation du service de déconnexion.
$logoutService = new LogoutService();

/* Appel de la méthode logout() du service. Cette méthode supprime la session de l’utilisateur et renvoie l’URL de redirection. */
$redirectUrl = $logoutService->logout();

// Envoi de l’en-tête HTTP "Location" pour rediriger le navigateur
header("Location: $redirectUrl");

// Arrêt immédiat de l’exécution du script
exit;