<?php

// Démarre la session ou reprend celle déjà existante.
session_start();

/* Vérifie si l’utilisateur est connecté. Return bool true si la clé ['utilisateur']['id'] existe en session, sinon false */
function isLoggedIn(): bool
{
  // isset retourne true seulement si la variable est définie et non nulle
  return isset($_SESSION['utilisateur']['id']);
}

/* Vérifie si l’utilisateur connecté possède le rôle « admin ». Return bool true si connecté ET rôle égal à 'admin', sinon false */
function isAdmin(): bool
{
  return isLoggedIn() && $_SESSION['utilisateur']['role'] === 'admin';
}

/* Appel de la fonction pour savoir si un utilisateur est connecté et stockage du résultat dans une variable réutilisable. */
$isLoggedIn = isLoggedIn();


if ($isLoggedIn) {
  // Échappe le nom et l’email pour les afficher sans risque de XSS
  $nom   = htmlspecialchars($_SESSION['nom']);
  $email = htmlspecialchars($_SESSION['email']);
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
  <!-- Déclaration de l’encodage et du responsive design -->
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <!-- Description de la page pour les moteurs de recherche -->
  <meta name="description" content="Inscrivez-vous gratuitement sur Mon site de recettes de cuisine collaboratif : partagez vos recettes, sauvegardez vos favoris et échangez avec une communauté de passionnés." />
  <title>Inscription</title>
  <!-- Import du logo pour l'onglet -->
  <link rel="icon" href="../assets/images/logo/favicon.ico" type="image/x-icon">
  <!-- Inclusion des feuilles de style externalisées -->
  <?php require_once __DIR__ . '/../includes/css.php'; ?>
</head>

<body class="d-flex flex-column min-vh-100 page-inscription">
  <!-- En-tête transparent avec titre centré -->
  <header class="py-3 header-transparent" role="banner">
    <div class="container">
      <h1 class="text-center">Inscription</h1>
    </div>
  </header>
  <!-- Inclusion de la nav externalisée -->
  <?php require_once __DIR__ . '/../includes/nav.php'; ?>
  <!-- Le conteneur principal qui occupe tout l’espace disponible dans un flex parent,
       et signale aux lecteurs d’écran qu’il s’agit du contenu principal -->
  <main class="flex-fill" role="main">
    <div class="container-sm mt-5 mb-5 mx-auto bloc-opaque p-4 rounded-4" style="max-width:420px;">
      <!-- Titre du formulaire, avec marge inférieure pour espacer -->
      <h2 class="mb-3">Créer un compte</h2>
      <!-- Formulaire d'inscription avec méthode POST pour envoyer les données -->
      <form method="POST" action="/fichierphp/recettes_collaboratif/controllers/register.php">
        <!-- Le label associé à l’input pour l’accessibilité -->
        <div class="mb-3">
          <label for="nom" class="form-label">Pseudo</label>
          <input type="text" class="form-control" id="nom" name="nom" required />
        </div>
        <!-- type="email" vérifie automatiquement le format de l’adresse -->
        <div class="mb-3">
          <label for="email" class="form-label">Adresse email</label>
          <input type="email" class="form-control" id="email" name="email" required />
        </div>
        <!-- Les champs password masquent la saisie pour plus de confidentialité -->
        <div class="mb-3">
          <label for="motdepasse" class="form-label">Mot de passe</label>
          <input type="password" class="form-control" id="motdepasse" name="mot_de_passe" required />
        </div>
        <div class="mb-3">
          <label for="mot_de_passe_confirme" class="form-label">Confirmer le mot de passe</label>
          <input type="password" class="form-control" id="mot_de_passe_confirme" name="mot_de_passe_confirme"
            required>
        </div>
        <button type="submit" class="btn btn-primary">S'inscrire</button>
      </form>
    </div>
  </main>
  <!-- Inclusion du footer externalisé -->
  <?php require_once __DIR__ . '/../includes/footer.php'; ?>
  <!-- Inclusion du script Bootstrap et du script personnel externalisé -->
  <?php require_once __DIR__ . '/../includes/script.php'; ?>
</body>

</html>