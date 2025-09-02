<?php

namespace Services;

// Auth vérifie l’état de connexion et le rôle de l’utilisateur.
 class Auth
{
    /* isLoggedIn vérifie si un utilisateur est considéré comme connecté,en s’assurant que $_SESSION['utilisateur' ['id'] existe. Return bool true si l’ID utilisateur est en session, false sinon */
    public static function isLoggedIn(): bool
    {
        // isset évite une notice si $_SESSION n’a jamais été initialisé
        return isset($_SESSION['utilisateur']['id']);
    }

    /* isAdmin vérifie si l’utilisateur est connecté ET possède le rôle « admin ». Return bool true si connecté et rôle admin, false sinon */
    public static function isAdmin(): bool
    {
        /* Appel à isLoggedIn() pour s’assurer de la connexion, puis comparaison stricte du rôle, avec '' par défaut pour éviter une erreur si role n’est pas défini */
        return self::isLoggedIn()
            && ($_SESSION['utilisateur']['role'] ?? '') === 'admin';
    }
}