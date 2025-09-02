<?php

/* Vérifie si un utilisateur est connecté. Return bool true si la session contient un identifiant utilisateur, false sinon */
function isLoggedIn(): bool
{
  return isset($_SESSION['utilisateur']['id']);
}

/*Vérifie si l’utilisateur connecté possède le rôle "admin". Return bool true si l’utilisateur est connecté ET son rôle est 'admin' , false sinon */
function isAdmin(): bool
{
  return isLoggedIn() && $_SESSION['utilisateur']['role'] === 'admin';
}

/* Appel de la fonction pour savoir si un utilisateur est connecté et stockage du résultat dans une variable réutilisable. */
$isLoggedIn = isLoggedIn();

$erreur = $message = "";

// Ne lance la logique d’inscription que lors d’un POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  // Connexion à la base de données
  $conn = new mysqli("localhost", "root", "3BienN#EMuwDu!k", "recettes_collaboratif");
  // Arrêt du script avec message d'erreur si la connexion échoue
  if ($conn->connect_error) {
    die("Connexion échouée : " . $conn->connect_error);
  }

  // Récupération et nettoyage des données saisies
  $nom     = strip_tags(trim($_POST["nom"] ?? ""));
  $email   = trim($_POST["email"] ?? "");
  $mdp     = $_POST["mot_de_passe"] ?? "";
  $mdp2    = $_POST["mot_de_passe_confirme"] ?? "";

  // Validation des champs
  if (empty($nom) || empty($email) || empty($mdp) || empty($mdp2)) {
    $erreur = "Tous les champs sont obligatoires.";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $erreur = "Adresse email invalide.";
  } elseif (strlen($mdp) < 8) {
    $erreur = "Le mot de passe doit contenir au moins 8 caractères.";
  } elseif ($mdp !== $mdp2) {
    $erreur = "Les mots de passe ne correspondent pas.";
  } else {
    // Vérifie si l'email existe déjà
    $verif = $conn->prepare("SELECT id FROM utilisateurs WHERE email = ?");
    $verif->bind_param("s", $email);
    $verif->execute();
    $verif->store_result();

    if ($verif->num_rows > 0) {
      $erreur = "Un compte avec cette adresse email existe déjà.";
    } else {
      // Vérifier si l'email est banni
      $stmt = $conn->prepare("SELECT 1 FROM bannis WHERE email = ?");
      $stmt->bind_param("s", $email);
      $stmt->execute();
      $stmt->store_result();
      if ($stmt->num_rows > 0) {
        $erreur = "Cet email a été banni et ne peut pas s’inscrire.";
      }
      $stmt->close();

      // Si pas d’erreur, procède à l’insertion dans utilisateurs
      if (empty($erreur)) {
        // Hachage et insertion
        $mdp_hache = password_hash($mdp, PASSWORD_DEFAULT);
        // Préparation de la requête d’insertion
        $stmt = $conn->prepare("INSERT INTO utilisateurs (nom, email, mot_de_passe) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nom, $email, $mdp_hache);

        if ($stmt->execute()) {
          // Redirige vers la page de connexion avec un flag succès
          header("Location: /fichierphp/recettes_collaboratif/views/connexion.php?inscription=ok");
          exit;
        } else {
          $erreur = "Erreur lors de l'inscription : " . $stmt->error;
        }
        $stmt->close();
      }
    }
    $verif->close();
  }
  $conn->close();
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
  <!-- Déclaration de l’encodage et du responsive design -->
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <!-- Description de la page pour les moteurs de recherche -->
  <meta name="description" content="Rejoignez Mon site de recettes de cuisine collaboratif : créez un compte gratuit pour partager vos recettes, sauvegarder vos favoris et échanger avec une communauté de passionnés de cuisine." />
  <title>Inscription</title>
  <!-- Import du logo pour l'onglet -->
  <link rel="icon" href="../assets/images/logo/favicon.ico" type="image/x-icon">
  <!-- Inclusion des feuilles de style externalisées -->
  <?php require_once __DIR__ . '/../includes/css.php'; ?>
</head>

<body class="d-flex flex-column min-vh-100 page-register">
  <!-- En-tête transparent avec titre centré -->
  <header class="py-3 header-transparent" role="banner">
    <div class="container">
      <h1 class="text-center">Inscription</h1>
    </div>
  </header>
  <!-- Inclusion de la nav externalisée -->
  <?php require_once __DIR__ . '/../includes/nav.php'; ?>

  <main class="flex-fill" role="main">
    <div class="container-sm mt-5 mb-5 mx-auto bloc-opaque p-4 rounded-4" style="max-width:420px;">
      <h2 class="mb-4">Créer un compte</h2>
      <!-- Affichage d’un message d’erreur ou de succès -->
      <?php if (!empty($erreur)) : ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
      <?php elseif (!empty($message)) : ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>
      <!-- Formulaire d’inscription -->
      <form method="POST" action="/fichierphp/recettes_collaboratif/controllers/inscription.php">
        <!-- Champ Nom -->
        <div class="mb-3">
          <label for="nom" class="form-label">Pseudo</label>
          <input type="text" class="form-control" id="nom" name="nom" required>
        </div>
        <!-- Champ Email -->
        <div class="mb-3">
          <label for="email" class="form-label">Adresse email</label>
          <input type="email" class="form-control" id="email" name="email" required>
        </div>
        <!-- Champ Mot de passe -->
        <div class="mb-3">
          <label for="mot_de_passe" class="form-label">Mot de passe</label>
          <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe" required>
        </div>
        <!-- Champ Confirmation du mot de passe -->
        <div class="mb-3">
          <label for="mot_de_passe_confirme" class="form-label">Confirmer le mot de passe</label>
          <input type="password" class="form-control" id="mot_de_passe_confirme" name="mot_de_passe_confirme" required>
        </div>
        <!-- Bouton de soumission -->
        <button type="submit" class="btn btn-primary w-100">S’inscrire</button>
      </form>
    </div>
  </main>
  <!-- Inclusion du footer externalisé -->
  <?php require_once __DIR__ . '/../includes/footer.php'; ?>
  <!-- Inclusion du script Bootstrap et du script personnel externalisé -->
  <?php require_once __DIR__ . '/../includes/script.php'; ?>
</body>

</html>