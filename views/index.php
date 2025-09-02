<?php
// Mode strict_types est activé pour une meilleure gestion des types
declare(strict_types=1);

// Démarrage de la session si elle n'est pas déjà active
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

//Cette fonction n'est redéfinie que si elle n'existe pas encore.
if (! function_exists('isLoggedIn')) {
  /* Vérifie si l’utilisateur est connecté. Return bool true si la clé ['utilisateur']['id'] existe en session, sinon false */
  function isLoggedIn(): bool
  {
    return isset($_SESSION['utilisateur']['id']);
  }
}

//Cette fonction n'est redéfinie que si elle n'existe pas encore.
if (! function_exists('isAdmin')) {
  /* Vérifie si l’utilisateur connecté possède le rôle « admin ». Return bool true si connecté ET rôle égal à 'admin', sinon false */
  function isAdmin(): bool
  {
    return isLoggedIn() && ($_SESSION['utilisateur']['role'] === 'admin');
  }
}

/* Appel de la fonction pour savoir si un utilisateur est connecté et stockage du résultat dans une variable réutilisable. */
$isLoggedIn = isLoggedIn();

// Initialise les variables de nom et email à des chaînes vides par défaut
$nom   = '';
$email = '';

/* Si l'utilisateur est connecté, récupère son nom et son email de la session en les sécurisant pour éviter les failles XSS via htmlspecialchars */
if ($isLoggedIn) {
  $nom   = htmlspecialchars($_SESSION['utilisateur']['nom']);
  $email = htmlspecialchars($_SESSION['utilisateur']['email']);
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
  <!-- Déclaration de l’encodage et du responsive design -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Description de la page pour les moteurs de recherche -->
  <meta name="description" content="Découvrez Mon site de recettes de cuisine collaboratif : une plateforme où les passionnés de cuisine partagent entrées, plats et desserts, astuces et menus. Explorez, testez et échangez pour sublimer vos préparations.">
  <title>Site de recettes de cuisine collaboratif</title>
  <!-- Import du logo pour l'onglet -->
  <link rel="icon" href="../assets/images/logo/favicon.ico" type="image/x-icon">
  <!-- Inclusion des feuilles de style externalisées -->
  <?php require_once __DIR__ . '/../includes/css.php'; ?>
</head>

<body class="d-flex flex-column min-vh-100 page-accueil">
  <!-- En-tête transparent avec titre centré -->
  <header class="py-3 header-transparent" role="banner">
    <div class="container">
      <h1 class="text-center">Mon site de recettes de cuisine collaboratif</h1>
    </div>
  </header>
  <!-- Inclusion de la nav externalisée -->
  <?php require_once __DIR__ . '/../includes/nav.php'; ?>
  <!-- Zone principale de la page, occupe l’espace restant grâce à flex-fill -->
  <main class="flex-fill" role="main">
    <!-- Conteneur Bootstrap centré horizontalement avec une marge supérieure -->
    <div class="container mt-3" style="max-width:1000px;">
      <!-- Bloc semi-opaque pour améliorer la lisibilité du texte -->
      <div class="bloc-opaque p-4 rounded-4">
        <!-- Titre de niveau 2, centré et avec une petite marge en bas -->
        <h2 class="mb-1 text-center">
          Bienvenue sur mon site de recettes collaboratif ! Ici vous pouvez partager vos recettes préférées, découvrir de nouvelles idées culinaires et interagir avec d'autres passionnés de cuisine. Que vous soyez un chef expérimenté ou un novice en cuisine, vous trouverez ici une communauté accueillante et inspirante. N'hésitez pas à explorer les différentes sections du site pour commencer votre aventure culinaire !
        </h2>
      </div>
    </div>
    <!-- Conteneur plus étroit pour le carrousel d’images -->
    <div class="container" style="max-width: 400px;">
      <!-- Carrousel Bootstrap :
           id utilisé pour les contrôles, mt-4 pour la marge haute,
           role et aria-label pour l’accessibilité -->
      <div id="carouselRecettes" class="carousel mt-4" role="region"
        aria-label="Carrousel d'ustensiles de cuisine">
        <!-- Conteneur des slides avec coins arrondis et ombre portée -->
        <div class="carousel-inner rounded shadow">
          <!-- Première diapositive affichée au chargement -->
          <div class="carousel-item active">
            <!-- Image respon­sive (d-block w-100) et lazy-loading pour la performance -->
            <img src="../assets/images/ustensile1.webp" loading="lazy" class="d-block w-100" alt="ustensile 1">
          </div>
          <!-- Diapositives secondaires -->
          <div class="carousel-item">
            <img src="../assets/images/ustensile2.webp" loading="lazy" class="d-block w-100" alt="ustensile 2">
          </div>
          <div class="carousel-item">
            <img src="../assets/images/ustensile3.webp" loading="lazy" class="d-block w-100" alt="ustensile 3">
          </div>
          <div class="carousel-item">
            <img src="../assets/images/ustensile4.webp" loading="lazy" class="d-block w-100" alt="ustensile 4">
          </div>
        </div>
        <!-- Bouton pour revenir à la diapositive précédente -->
        <button class="carousel-control-prev" type="button" data-target="#carouselRecettes" data-slide="prev">
          <span class="carousel-control-prev-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Précédent</span>
        </button>
        <!-- Bouton pour passer à la diapositive suivante -->
        <button class="carousel-control-next" type="button" data-target="#carouselRecettes" data-slide="next">
          <span class="carousel-control-next-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Suivant</span>
        </button>
      </div>
    </div>
  </main>
  <!-- Inclusion du footer externalisé -->
  <?php require_once __DIR__ . '/../includes/footer.php'; ?>
  <!-- Inclusion du script Bootstrap et du script personnel externalisé -->
  <?php require_once __DIR__ . '/../includes/script.php'; ?>
</body>

</html>