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

// Si l’utilisateur n’est pas admin, on le redirige vers la page d’accueil
if (! isAdmin()) {
  header('Location: ../views/index.php');
  exit;
}

// Connexion à la BDD
$conn = new mysqli('localhost', 'root', '3BienN#EMuwDu!k', 'recettes_collaboratif');

// Arrêt du script avec message d'erreur si la connexion échoue
if ($conn->connect_error) {
  die('Erreur de connexion : ' . $conn->connect_error);
}

/* Bannir un utilisateur : Récupère l’ID depuis le formulaire. Charge son email pour l’ajouter à la table "bannis". Supprime ensuite l’utilisateur de la table "utilisateurs" */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ban_user_id'])) {
  $userId = (int) $_POST['ban_user_id'];

  // Récupère l'email avant suppression
  $stmt = $conn->prepare("SELECT email FROM utilisateurs WHERE id = ?");
  $stmt->bind_param("i", $userId);
  $stmt->execute();
  $stmt->bind_result($email);
  if ($stmt->fetch()) {
    $stmt->close();

    // Insère l’email dans la table 'bannis' (IGNORE évite les doublons)
    $insert = $conn->prepare("INSERT IGNORE INTO bannis (email) VALUES (?)");
    $insert->bind_param("s", $email);
    $insert->execute();
    $insert->close();

    // Supprime l'utilisateur de la table 'utilisateurs'
    $del = $conn->prepare("DELETE FROM utilisateurs WHERE id = ?");
    $del->bind_param("i", $userId);
    $del->execute();
    $del->close();
  } else {
    // Aucun utilisateur trouvé, on ferme simplement le statement
    $stmt->close();
  }

  // Redirection pour éviter le repost
  header("Location: admin.php");
  exit;
}

/* Débannir un email : Récupère l’email à débannir depuis le formulaire et supprime l’enregistrement correspondant dans la table 'bannis' */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unban_email'])) {
  $emailToUnban = $_POST['unban_email'];

  $del = $conn->prepare("DELETE FROM bannis WHERE email = ?");
  $del->bind_param("s", $emailToUnban);
  $del->execute();
  $del->close();

  // Redirection pour éviter le repost
  header("Location: admin.php");
  exit;
}

// Supprimer une recette : Récupère l’ID de la recette et supprime la recette de la table 'recettes' 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_recipe_id'])) {
  $recipeId = (int) $_POST['delete_recipe_id'];

  $stmt = $conn->prepare("DELETE FROM recettes WHERE id = ?");
  $stmt->bind_param("i", $recipeId);
  $stmt->execute();
  $stmt->close();

  header("Location: admin.php");
  exit;
}

// Supprimer un commentaire : Récupère l’ID du commentaire et le supprime de la table 'commentaires' 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment_id'])) {
  $commentId = (int) $_POST['delete_comment_id'];

  $stmt = $conn->prepare("DELETE FROM commentaires WHERE id = ?");
  $stmt->bind_param("i", $commentId);
  $stmt->execute();
  $stmt->close();

  // Redirection pour éviter le repost
  header("Location: admin.php");
  exit;
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
  <!-- Déclaration de l’encodage et du responsive design -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Description de la page pour les moteurs de recherche -->
  <meta name="description" content="Accédez au tableau de bord admin de Mon site de recettes de cuisine collaboratif : gérez recettes et utilisateurs, modérez le contenu, consultez les statistiques et assurez la qualité de la communauté." />
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
      <h1 class="text-center">Admin</h1>
    </div>
  </header>
  <!-- Inclusion de la nav externalisée -->
  <?php require_once __DIR__ . '/../includes/nav.php'; ?>

  <main class="container my-5" role="main">
    <!-- Section : Utilisateurs enregistrés (hors admins) -->
    <h2 class="mb-4 bloc-opaque p-4 text-center rounded-4">Utilisateurs enregistrés</h2>
    <?php
    // Récupère tous les utilisateurs dont le rôle n'est pas 'admin'
    $users = $conn->query("SELECT id, nom, email, role, date_inscription FROM utilisateurs WHERE role <> 'admin'");
    // Vérifie que la requête a renvoyé au moins une ligne
    if ($users && $users->num_rows > 0):
    ?>
      <div class="table-responsive mb-5">
        <table class="table table-bordered table-hover align-middle">
          <thead class="table-dark text-white">
            <tr>
              <th>Nom</th>
              <th>Email</th>
              <th>Date d'inscription</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($u = $users->fetch_assoc()): ?>
              <tr>
                <!-- Protection contre XSS avec htmlspecialchars -->
                <td><?= htmlspecialchars($u['nom']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <!-- Mise en forme de la date d'inscription -->
                <td><?= (new DateTime($u['date_inscription']))->format('d/m/Y H:i') ?></td>
                <td>
                  <!-- Formulaire de bannissement -->
                  <form method="POST" style="display:inline"
                    onsubmit="return confirm('Confirmer le bannissement de <?= htmlspecialchars($u['email']) ?> ?');">
                    <!-- Champ caché pour transmettre l'ID de l'utilisateur -->
                    <input type="hidden" name="ban_user_id" value="<?= $u['id'] ?>">
                    <button class="btn btn-sm btn-danger">Bannir</button>
                  </form>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <!-- Aucun utilisateur trouvé -->
      <h4 class="mb-4 text-center bloc-opaque">Aucun utilisateur enregistré.</h4>
    <?php endif; ?>
    <!-- Section : Recettes publiées -->
    <h2 class="mb-4 bloc-opaque p-4 text-center rounded-4">Recettes publiées</h2>
    <?php
    // Récupère les recettes avec le nom de leur auteur
    $recettes = $conn->query("
  SELECT r.id, r.titre, r.type_de_cuisine, r.date_creation, u.nom AS auteur
  FROM recettes r
  JOIN utilisateurs u ON r.auteur_id = u.id
");
    if ($recettes && $recettes->num_rows > 0):
    ?>
      <div class="table-responsive mb-5">
        <table class="table table-bordered table-hover align-middle">
          <thead class="table-success text-dark">
            <tr>
              <th>Titre</th>
              <th>Cuisine</th>
              <th>Auteur</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($r = $recettes->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($r['titre']) ?></td>
                <!-- Remplace les underscores et met la première lettre en majuscule -->
                <td><?= ucfirst(str_replace('_', ' ', $r['type_de_cuisine'])) ?></td>
                <td><?= htmlspecialchars($r['auteur']) ?></td>
                <td><?= (new DateTime($r['date_creation']))->format('d/m/Y H:i') ?></td>
                <td>
                  <!-- Lien vers la page de modification -->
                  <a href="modifier-recette.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-primary me-1">
                    Modifier
                  </a>
                  <!-- Formulaire de suppression de recette -->
                  <form
                    method="POST"
                    style="display:inline"
                    onsubmit="return confirm('Confirmer la suppression de “<?= htmlspecialchars($r['titre']) ?>” ?');">
                    <input type="hidden"
                      name="delete_recipe_id"
                      value="<?= $r['id'] ?>">
                    <button class="btn btn-sm btn-danger">Supprimer</button>
                  </form>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <h4 class="mb-4 text-center bloc-opaque">Aucune recette publiée.</h4>
    <?php endif; ?>
    <!-- Section : Commentaires -->
    <h2 class="mb-4 bloc-opaque p-4 text-center rounded-4">Commentaires</h2>
    <?php
    // Récupère les commentaires, l'auteur et la recette associée
    $commentaires = $conn->query("
  SELECT c.id, r.titre AS recette, u.nom AS auteur, c.contenu, c.date_publication AS date_com
  FROM commentaires c
  JOIN utilisateurs u ON c.user_id = u.id
  JOIN recettes r ON c.recette_id = r.id
  ORDER BY c.date_publication DESC
");
    if ($commentaires && $commentaires->num_rows > 0):
    ?>
      <div class="table-responsive mb-5">
        <table class="table table-bordered table-hover align-middle">
          <thead class="table-info text-dark">
            <tr>
              <th>Recette</th>
              <th>Commentaire</th>
              <th>Auteur</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($c = $commentaires->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($c['recette']) ?></td>
                <td><?= htmlspecialchars($c['contenu']) ?></td>
                <td><?= htmlspecialchars($c['auteur']) ?></td>
                <td><?= (new DateTime($c['date_com']))->format('d/m/Y H:i') ?></td>
                <td>
                  <!-- Formulaire de suppression de commentaire -->
                  <form method="POST" onsubmit="return confirm('Supprimer ce commentaire ?');" style="display:inline;">
                    <input type="hidden" name="delete_comment_id" value="<?= $c['id'] ?>">
                    <button class="btn btn-sm btn-danger">Supprimer</button>
                  </form>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <h4 class="mb-4 text-center bloc-opaque">Aucun commentaire.</h4>
    <?php endif; ?>
    <!-- Section : Emails bannis -->
    <h2 class="mb-4 bloc-opaque p-4 text-center rounded-4">Emails bannis</h2>
    <?php
    // Récupère la liste des emails bannis avec la date de bannissement
    $bannis = $conn->query("SELECT email, date_banissement FROM bannis ORDER BY date_banissement DESC");
    if ($bannis && $bannis->num_rows > 0): ?>
      <div class="table-responsive mb-5">
        <table class="table table-bordered table-hover align-middle">
          <thead class="table-danger text-white">
            <tr>
              <th>Email</th>
              <th>Date de bannissement</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($b = $bannis->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($b['email']) ?></td>
                <td><?= (new DateTime($b['date_banissement']))->format('d/m/Y H:i') ?></td>
                <td>
                  <!-- Formulaire de débannissement -->
                  <form
                    method="POST"
                    style="display:inline"
                    onsubmit="return confirm('Débannir <?= htmlspecialchars($b['email']) ?> ?');">
                    <input
                      type="hidden"
                      name="unban_email"
                      value="<?= htmlspecialchars($b['email']) ?>">
                    <button class="btn btn-sm btn-success">
                      Débannir
                    </button>
                  </form>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <h4 class="mb-4 text-center bloc-opaque">Aucun email banni.</h4>
    <?php endif; ?>
  </main>
  <!-- Inclusion du footer externalisé -->
  <?php require_once __DIR__ . '/../includes/footer.php'; ?>
  <!-- Inclusion du script Bootstrap et du script personnel externalisé -->
  <?php require_once __DIR__ . '/../includes/script.php'; ?>
</body>

</html>