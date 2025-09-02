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
  // On s’assure d'abord que l’utilisateur est connecté, puis on compare le rôle.
  return isLoggedIn() && ($_SESSION['utilisateur']['role'] === 'admin');
}

/* Appel de la fonction pour savoir si un utilisateur est connecté et stockage du résultat dans une variable réutilisable. */
$isLoggedIn = isLoggedIn();

// Connexion à la BDD
$conn = new mysqli("localhost", "root", "3BienN#EMuwDu!k", "recettes_collaboratif");

// Arrêt du script avec message d'erreur si la connexion échoue
if ($conn->connect_error) {
  die("Connexion échouée : " . $conn->connect_error);
}

// Traitement du formulaire uniquement en POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  // Récupération et nettoyage des inputs
  $email      = trim($_POST["email"] ?? "");
  $motdepasse = $_POST["motdepasse"] ?? "";
  // Variable pour stocker un éventuel message d'erreur
  $erreur     = "";

  // Validation rapide des champs
  if (empty($email) || empty($motdepasse)) {
    $erreur = "Veuillez remplir tous les champs.";
  } else {
    // Préparation de la requête pour éviter les injections SQL
    $sql  = "SELECT id, nom, mot_de_passe, role
             FROM utilisateurs
             WHERE email = ?";
    $stmt = $conn->prepare($sql);

    // Vérifier que la préparation s'est bien passée
    if ($stmt === false) {
      die("Erreur de préparation SQL : " . $conn->error);
    }

    // Liaison du paramètre et exécution
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    // Si on trouve exactement un utilisateur avec cet email
    if ($stmt->num_rows === 1) {
      // Récupération des colonnes
      $stmt->bind_result($id, $nom, $hash, $role);
      $stmt->fetch();

      // Vérification du mot de passe
      if (password_verify($motdepasse, $hash)) {
        // Création du tableau de session pour l’utilisateur
        $_SESSION['utilisateur'] = [
          'id'    => $id,
          'nom'   => $nom,
          'email' => $email,
          'role'  => $role
        ];

        // Redirection selon le rôle
        if ($role === 'admin') {
          header("Location:../views/admin.php");
        } else {
          header("Location:../views/compte.php");
        }
        exit;
      } else {
        $erreur = "Mot de passe incorrect.";
      }
    } else {
      $erreur = "Aucun compte ne correspond à cette adresse email.";
    }
    $stmt->close();
  }
  $conn->close();
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
  <!-- Déclaration de l’encodage et du responsive design -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <!-- Description de la page pour les moteurs de recherche -->
  <meta name="description" content="Accédez à votre espace personnel sur Mon site de recettes de cuisine collaboratif. Connectez-vous de manière sécurisée pour gérer vos recettes, partager vos idées culinaires et suivre vos favoris. Authentification rapide via email et mot de passe.">
  <title>Connexion</title>
  <!-- Import du logo pour l'onglet -->
  <link rel="icon" href="../assets/images/logo/favicon.ico" type="image/x-icon">
  <!-- Inclusion des feuilles de style externalisées -->
  <?php require_once __DIR__ . '/../includes/css.php'; ?>
</head>

<body class="d-flex flex-column min-vh-100 page-login">
  <!-- En-tête transparent avec le titre du site -->
  <header class="py-3 header-transparent" role="banner">
    <div class="container">
      <h1 class="text-center">Mon site de recettes collaboratif</h1>
    </div>
  </header>
  <!-- Inclusion de la nav externalisée -->
  <?php require_once __DIR__ . '/../includes/nav.php'; ?>

  <main class="flex-fill" role="main">
    <div class="container-sm mt-5 mb-5 mx-auto bloc-opaque p-4 rounded-4" style="max-width:420px;">
      <h2 class="mb-3">Connexion</h2>
      <!-- Message de succès après inscription -->
      <?php if (isset($_GET['inscription']) && $_GET['inscription'] === 'ok'): ?>
        <div class="alert alert-success">
          ✅ Inscription réussie ! Vous pouvez maintenant vous connecter.
        </div>
      <?php endif; ?>
      <!-- Affichage d’une alerte si une erreur de connexion est présente -->
      <?php if (!empty($erreur)): ?>
        <div class="alert alert-danger">
          <?= htmlspecialchars($erreur) ?>
        </div>
      <?php endif; ?>
      <!-- Formulaire de connexion -->
      <form method="POST" action="">
        <div class="mb-3">
          <label for="email" class="form-label">Adresse email</label>
          <input type="email" class="form-control" id="email"
            name="email" required
            value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
        </div>
        <div class="mb-3">
          <label for="motdepasse" class="form-label">Mot de passe</label>
          <input type="password" class="form-control" id="motdepasse"
            name="motdepasse" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Se connecter</button>
      </form>
    </div>
  </main>
  <!-- Inclusion du footer externalisé -->
  <?php require_once __DIR__ . '/../includes/footer.php'; ?>
  <!-- Inclusion du script Bootstrap et du script personnel externalisé -->
  <?php require_once __DIR__ . '/../includes/script.php'; ?>
</body>

</html>