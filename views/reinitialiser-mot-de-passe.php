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

// Connexion BDD
$conn = new mysqli("localhost", "root", "3BienN#EMuwDu!k", "recettes_collaboratif");
if ($conn->connect_error) {
  // Arrête le script en cas d’erreur de connexion
  die("Erreur BDD : " . $conn->connect_error);
}

// Récupération du token de réinitialisation 
$token    = $_GET['token'] ?? '';
$error    = ''; // Message d’erreur à afficher
$success  = ''; // Message de succès à afficher

// Vérification de la validité du token
if (empty($token)) {
  // Aucun token fourni
  $error = "Token manquant.";
} else {
  // Prépare et exécute la requête pour lire email et date d’expiration
  $stmt = $conn->prepare("
        SELECT email, expiration
        FROM password_resets
        WHERE token = ?
    ");
  $stmt->bind_param("s", $token);
  $stmt->execute();
  $stmt->bind_result($email, $expiration);
  if ($stmt->fetch()) {
    // Contrôle de l'expiration
    if (new DateTime() > new DateTime($expiration)) {
      $error = "Le lien a expiré.";
    }
  } else {
    // Aucun enregistrement ne correspond au token
    $error = "Lien invalide.";
  }
  $stmt->close();
}

// Traitement du formulaire de réinitialisation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
  // Récupère les mots de passe saisis
  $newPwd     = $_POST['nouveau_mdp']  ?? '';
  $confirmPwd = $_POST['conf_mdp']     ?? '';

  // Validation avec longueur minimale
  if (strlen($newPwd) < 8) {
    $error = "Le mot de passe doit contenir au moins 8 caractères.";
  }
  // Validation : correspondance des deux champs
  elseif ($newPwd !== $confirmPwd) {
    $error = "La confirmation ne correspond pas.";
  }

  // Si aucune erreur, mise à jour dans la table utilisateurs
  if (empty($error)) {
    // Hachage sécurisé du nouveau mot de passe
    $newHash = password_hash($newPwd, PASSWORD_DEFAULT);

    $upd = $conn->prepare("
          UPDATE utilisateurs
          SET mot_de_passe = ?
          WHERE email = ?
        ");
    $upd->bind_param("ss", $newHash, $email);
    // Si mise à jour effectuée
    if ($upd->execute()) {
      // Suppression du token pour empêcher toute réutilisation
      $del = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
      $del->bind_param("s", $token);
      $del->execute();
      $del->close();
      // Message de succès pour l’utilisateur
      $success = "Votre mot de passe a été réinitialisé avec succès.";
    } else {
      // Sinon message d'erreur
      $error = "Erreur de mise à jour : " . $upd->error;
    }
    $upd->close();
  }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">

<head>
  <!-- Déclaration de l’encodage et du responsive design -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <!-- Description de la page pour les moteurs de recherche -->
  <meta name="description" content="Réinitialisez rapidement votre mot de passe pour retrouver l’accès à votre compte. Recevez un lien sécurisé par email et créez un nouveau mot de passe." />
  <title>Réinitialiser mot de passe</title>
  <!-- Import du logo pour l'onglet -->
  <link rel="icon" href="../assets/images/logo/favicon.ico" type="image/x-icon">
  <!-- Inclusion des feuilles de style externalisées -->
  <?php require_once __DIR__ . '/../includes/css.php'; ?>
</head>

<body class="d-flex flex-column min-vh-100 page-reinitialiser-mot-de-passe">
  <!-- En-tête transparent avec titre centré -->
  <header class="py-3 header-transparent" role="banner">
    <div class="container">
      <h1 class="text-center">Réinitialiser votre mot de passe</h1>
    </div>
  </header>
  <!-- Inclusion de la nav externalisée -->
  <?php require_once __DIR__ . '/../includes/nav.php'; ?>
  <!-- Conteneur principal occupant l’espace disponible -->
  <main class="flex-fill" role="main">
    <div class="container-sm mt-5 mb-5 mx-auto bloc-opaque p-4 rounded-4" style="max-width:420px;">
      <!-- Titre de la section -->
      <h4 class="mb-3">Réinitialiser le mot de passe</h4>
      <!-- Si une erreur est définie, affiche une alerte danger-->
      <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <!-- Si la réinitialisation a réussi, affiche une alerte succès -->
      <?php elseif ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <p class="text-center mt-3">
          <!-- Lien pour revenir à la page de connexion -->
          <a href="connexion.php" class="btn btn-primary">Se connecter</a>
        </p>
      <?php endif; ?>
      <!-- Si on n'a ni message d'erreur ni de succès, on affiche le formulaire -->
      <?php if (empty($success) && empty($error)): ?>
        <!-- Formulaire pour enregistrer le nouveau mot de passe -->
        <form method="POST">
          <div class="mb-3">
            <!-- Champ mot de passe -->
            <label for="nouveau_mdp" class="form-label">Nouveau mot de passe *</label>
            <input type="password" id="nouveau_mdp" name="nouveau_mdp"
              class="form-control" minlength="8" required>
          </div>
          <!-- Champ confirmer le mot de passe -->
          <div class="mb-3">
            <label for="conf_mdp" class="form-label">Confirmer le mot de passe *</label>
            <input type="password" id="conf_mdp" name="conf_mdp"
              class="form-control" required>
          </div>
          <!-- Bouton vert pleine largeur pour valider le formulaire -->
          <button type="submit" class="btn btn-success w-100">Enregistrer</button>
        </form>
      <?php endif; ?>
    </div>
  </main>
  <!-- Inclusion du footer externalisé -->
  <?php require_once __DIR__ . '/../includes/footer.php'; ?>
  <!-- Inclusion du script Bootstrap et du script personnel externalisé -->
  <?php require_once __DIR__ . '/../includes/script.php'; ?>
</body>

</html>