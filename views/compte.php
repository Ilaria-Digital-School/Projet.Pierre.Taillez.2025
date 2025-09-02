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
  $nom   = htmlspecialchars($_SESSION['utilisateur']['nom']);
  $email = htmlspecialchars($_SESSION['utilisateur']['email']);
}

// Connexion BDD
$conn = new mysqli("localhost", "root", "3BienN#EMuwDu!k", "recettes_collaboratif");

// Arrêt du script avec message d'erreur si la connexion échoue
if ($conn->connect_error) {
  die("Erreur BDD : " . $conn->connect_error);
}

if ($isLoggedIn) {
  // Récupère les recettes de l’utilisateur si il est connecté
  $userId = $_SESSION['utilisateur']['id'];
  $sql = "
    SELECT id, titre, type_de_cuisine, date_creation
    FROM recettes
    WHERE auteur_id = ?
    ORDER BY date_creation DESC
  ";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $userId);
  $stmt->execute();
  $mesRecettes = $stmt->get_result();
} 
// Sinon s'il n'a pas de recette
else {
  $mesRecettes = null;
}

// Modifier le nom/pseudo si l'utilisateur est connecté
if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nouveau_nom'])) {
  $nouveauNom = trim($_POST['nouveau_nom']);
  if (!empty($nouveauNom)) {
    // Prépare et exécute la mise à jour dans la table utilisateurs
    $stmt = $conn->prepare("UPDATE utilisateurs SET nom = ? WHERE id = ?");
    $stmt->bind_param("si", $nouveauNom, $userId);

    if ($stmt->execute()) {
      // Mise à jour de la variable de session puis redirection
      $_SESSION['utilisateur']['nom'] = $nouveauNom;
      header("Location: compte.php?modif_nom=ok");
      exit;
    }
    $stmt->close();
  }
}

// Changer le mot de passe
if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['changer_mdp'])) {
  // Récupère les champs du formulaire
  $current = $_POST['mdp_actuel']   ?? '';
  $new     = $_POST['nouveau_mdp']  ?? '';
  $confirm = $_POST['conf_mdp']     ?? '';

  // Récupère le hash actuel
  $stmt = $conn->prepare("SELECT mot_de_passe FROM utilisateurs WHERE id = ?");
  $stmt->bind_param("i", $userId);
  $stmt->execute();
  $stmt->bind_result($hash);
  $stmt->fetch();
  $stmt->close();

  // Vérification du mot de passe actuel
  if (!password_verify($current, $hash)) {
    $pwdError = "Le mot de passe actuel est incorrect.";
  }
  // Vérification de la longueur minimale
  elseif (strlen($new) < 8) {
    $pwdError = "Le nouveau mot de passe doit faire au moins 8 caractères.";
  }
  // Vérification de la confirmation
  elseif ($new !== $confirm) {
    $pwdError = "La confirmation ne correspond pas au nouveau mot de passe.";
  }

  // Mise à jour si OK
  if (empty($pwdError)) {
    $newHash = password_hash($new, PASSWORD_DEFAULT);
    $upd = $conn->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?");
    $upd->bind_param("si", $newHash, $userId);
    if ($upd->execute()) {
      $pwdSuccess = "✅ Votre mot de passe a bien été modifié.";
      header("Location: compte.php?modif_mdp=ok");
      exit;
    } else {
      $pwdError = "Erreur lors de la mise à jour : " . $upd->error;
    }
    $upd->close();
  }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
  <!-- Déclaration de l’encodage et du responsive design -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <!-- Description de la page pour les moteurs de recherche -->
  <meta name="description" content="Gérez votre espace personnel sur Mon site de recettes de cuisine collaboratif : modifiez vos informations, consultez vos recettes sauvegardées, et apportez des modifications à vos recettes déposées." />
  <title>Mon Compte</title>
  <!-- Import du logo pour l'onglet -->
  <link rel="icon" href="../assets/images/logo/favicon.ico" type="image/x-icon">
  <!-- Inclusion des feuilles de style externalisées -->
  <?php require_once __DIR__ . '/../includes/css.php'; ?>
</head>

<body class="d-flex flex-column min-vh-100 page-compte">
  <!-- En-tête transparent avec titre centré -->
  <header class="py-3 header-transparent" role="banner">
    <div class="container">
      <h1 class="text-center">Mon compte</h1>
    </div>
  </header>
  <!-- Inclusion de la nav externalisée -->
  <?php require_once __DIR__ . '/../includes/nav.php'; ?>
  <!-- Contenu principal de la page, rôle ARIA pour l'accessibilité -->
  <main class="flex-fill" role="main">
    <!-- Conteneur centré avec marge supérieure et largeur limitée -->
    <div class="container mt-5" style="max-width:1000px;">
      <!-- Bloc semi-opaque centré, padding et bords arrondis -->
      <div class="bloc-opaque p-4 text-center rounded-4" style="max-width:600px; margin:0 auto;">
        <!-- Si l’utilisateur est connecté, on affiche son espace personnel -->
        <?php if ($isLoggedIn): ?>
          <!-- Message de bienvenue, $nom est déjà échappé en PHP -->
          <h2 class="mb-1">Bonjour <?= $nom ?> !</h2>
          <hr><!-- Ligne de séparation -->
          <?php if (isset($_GET['modif_nom'])): ?>
            <!-- Affiche un message de succès après modification du pseudo -->
            <div class="alert alert-success">✅ Nom modifié avec succès !</div>
          <?php endif; ?>
          <!-- Formulaire de modification du pseudo -->
          <form method="POST" class="mt-3">
            <fieldset class="mb-3">
              <h3>Modifier mon pseudo</h3>
              <div class="mb-3">
                <label for="nouveau_nom" class="form-label">
                  Nouveau pseudo :
                </label>
                <input type="text" name="nouveau_nom" id="nouveau_nom" class="form-control text-center" value="<?= $nom ?>" required>
                <!-- 'required' empêche l’envoi sans valeur -->
              </div>
              <button type="submit" class="btn btn-primary">
                Enregistrer le nouveau pseudo
              </button>
            </fieldset>
          </form>
          <?php if (isset($_GET['modif_mdp'])): ?>
            <hr>
            <!-- Message de succès après changement de mot de passe -->
            <div class="alert alert-success">✅ Mot de passe modifié avec succès !</div>
          <?php elseif (!empty($pwdError)): ?>
            <!-- Affiche l’erreur de validation du mot de passe s’il y en a une -->
            <div class="alert alert-danger"><?= htmlspecialchars($pwdError) ?></div>
          <?php endif; ?>
          <hr>
          <h3>Modifier mon mot de passe</h3>
          <!-- Formulaire de changement de mot de passe -->
          <form method="POST" class="mt-4">
            <!-- Champ caché pour identifier ce formulaire côté backend -->
            <input type="hidden" name="changer_mdp" value="1">
            <div class="mb-3">
              <label for="mdp_actuel" class="form-label">Mot de passe actuel :</label>
              <input type="password" id="mdp_actuel" name="mdp_actuel" class="form-control" required>
            </div>
            <div class="mb-3">
              <label for="nouveau_mdp" class="form-label">Nouveau mot de passe :</label>
              <input type="password" id="nouveau_mdp" name="nouveau_mdp" class="form-control" minlength="8" required>
              <!-- minlength="8" impose une longueur minimale côté client -->
            </div>
            <div class="mb-3">
              <label for="conf_mdp" class="form-label">Confirmer le nouveau mot de passe :</label>
              <input type="password" id="conf_mdp" name="conf_mdp" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Modifier mon mot de passe</button>
          </form>
          <!-- Sinon, si l’utilisateur n’est pas connecté, on lui propose de se connecter ou de s’inscrire -->
        <?php else: ?>
          <h3 class="mb-4">Vous n'êtes pas connecté</h3>
          <p class="mb-3">Pour accéder à votre compte, veuillez vous connecter ou vous inscrire :</p>
          <div class="d-grid gap-2">
            <a href="connexion.php" class="btn btn-primary">Se connecter</a>
            <a href="inscription.php" class="btn btn-outline-primary">S'inscrire</a>
          </div>
        <?php endif; ?>
      </div>
      <!-- Affiche la liste des recettes seulement si connecté -->
      <?php if ($isLoggedIn): ?>
        <div class="container-fluid mt-5 bloc-opaque p-4 rounded-4">
          <h3>Voici vos recettes publiées</h3>
          <!-- Si l’utilisateur a des recettes, on les affiche dans un tableau -->
          <?php if ($mesRecettes && $mesRecettes->num_rows > 0): ?>
            <div class="table-responsive mt-4">
              <table class="table table-striped table-hover table-sm align-middle">
                <thead class="table-primary text-white">
                  <tr>
                    <th scope="col">Titre</th>
                    <th scope="col">Type de cuisine</th>
                    <th scope="col">Date de publication</th>
                    <th scope="col">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($recette = $mesRecettes->fetch_assoc()): ?>
                    <tr>
                      <!-- Échappement pour éviter tout XSS -->
                      <td><?= htmlspecialchars($recette['titre']) ?></td>
                      <!-- Transformation du type en label lisible -->
                      <td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $recette['type_de_cuisine']))) ?>
                      </td>
                      <td>
                        <!-- Formatage de la date de création -->
                        <?= (new DateTime($recette['date_creation']))->format('d/m/Y H:i') ?>
                      </td>
                      <td>
                        <!-- Liens d’action pour chaque recette -->
                        <a href="recette_detail.php?id=<?= $recette['id'] ?>" class="btn btn-sm btn-outline-dark me-2">Voir</a>
                        <a href="modifier-recette.php?id=<?= $recette['id'] ?>" class="btn btn-sm btn-outline-primary">Modifier</a>
                        <a href="supprimer-recette.php?id=<?= $recette['id'] ?>"
                          class="btn btn-sm btn-outline-danger"
                          onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette recette ?');">
                          Supprimer
                        </a>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
            <!-- Sinon message informatif si aucune recette n’existe -->
          <?php else: ?>
            <div class="alert alert-info mt-3">
              Vous n’avez encore publié aucune recette.
            </div>
          <?php endif; ?>
        </div>
    </div>
  <?php endif; ?>
  </div>
  </main>
  <!-- Inclusion du footer externalisé -->
  <?php require_once __DIR__ . '/../includes/footer.php'; ?>
  <!-- Inclusion du script Bootstrap et du script personnel externalisé -->
  <?php require_once __DIR__ . '/../includes/script.php'; ?>
</body>

</html>