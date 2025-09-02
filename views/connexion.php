<?php 
// Affiche une alerte d’erreur si la variable $erreur contient un message
if (!empty($erreur)) : ?>
  <!-- htmlspecialchars pour protéger contre les injections HTML/JS (XSS) -->
  <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
<?php endif; ?>

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
  <meta name="description" content="Connexion à Mon site de recettes de cuisine collaboratif : accédez en toute sécurité à votre espace personnel pour gérer vos recettes, commenter, partager et découvrir de nouvelles idées culinaires." />
  <title>Connexion</title>
  <!-- Import du logo pour l'onglet -->
  <link rel="icon" href="../assets/images/logo/favicon.ico" type="image/x-icon">
  <!-- Inclusion des feuilles de style externalisées -->
  <?php require_once __DIR__ . '/../includes/css.php'; ?>
</head>

<body class="d-flex flex-column min-vh-100 page-connexion">
  <!-- En-tête transparent avec titre centré -->
  <header class="py-3 header-transparent" role="banner">
    <div class="container">
      <h1 class="text-center">Connexion</h1>
    </div>
  </header>
  <!-- Inclusion de la nav externalisée -->
  <?php require_once __DIR__ . '/../includes/nav.php'; ?>
  <!-- Contenu principal de la page, rôle ARIA pour l'accessibilité -->
  <main class="flex-fill" role="main">
    <div class="container-sm mt-4 mb-5 mx-auto bloc-opaque p-4 rounded-4" style="max-width:420px;">
      <!-- Titre de la page de connexion -->
      <h2 class="mb-3">Connexion</h2>
      <!-- Si l’utilisateur vient d’une inscription réussie (paramètre URL inscription=ok) -->
      <?php if (isset($_GET['inscription']) && $_GET['inscription'] === 'ok') : ?>
        <!-- Affiche une alerte verte de succès -->
        <div class="alert alert-success">✅ Inscription réussie ! Vous pouvez maintenant vous connecter.</div>
      <?php endif; ?>
      <!-- Formulaire d’authentification : method POST pour ne pas exposer les données dans l’URL, action vers le contrôleur PHP qui traitera la connexion -->
      <form method="POST" action="/fichierphp/recettes_collaboratif/controllers/login.php">
        <!-- Étiquette liée à l’input email pour accessibilité -->
        <div class="mb-3">
          <label for="email" class="form-label">Adresse email :</label>
          <!-- type=email : validation côté client pour format email, required : champ obligatoire -->
          <input type="email" class="form-control" id="email" name="email" required />
        </div>
        <!-- Étiquette pour le mot de passe -->
        <div class="mb-3">
          <label for="motdepasse" class="form-label">Mot de passe :</label>
          <!-- type=password : masque les caractères, required : champ obligatoire -->
          <input type="password" class="form-control" id="motdepasse" name="motdepasse" required />
        </div>
        <!-- Bouton plein largeur (w-100) avec style Bootstrap btn-primary -->
        <button type="submit" class="btn btn-primary w-100">Se connecter</button>
      </form>
      <!-- Lien vers la page de récupération de mot de passe -->
      <p class="mt-3 text-center">
        <a href="mot-de-passe-oublie.php">Mot de passe oublié ?</a>
      </p>
      <!-- Lien vers la page d’inscription -->
      <p class="mt-3 text-center">Pas encore de compte ?<a href="inscription.php">
          Cliquez ici pour vous inscrire</a>
      </p>
    </div>
  </main>
  <!-- Inclusion du footer externalisé -->
  <?php require_once __DIR__ . '/../includes/footer.php'; ?>
  <!-- Inclusion du script Bootstrap et du script personnel externalisé -->
  <?php require_once __DIR__ . '/../includes/script.php'; ?>
</body>

</html>