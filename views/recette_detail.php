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

// Récupère l’ID de l’utilisateur (ou null si non connecté)
$userId        = $_SESSION['utilisateur']['id'] ?? null;

// Récupère l’ID de la recette 
$recetteId     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Récupère l’ID du commentaire à éditer
$editCommentId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;

// Connexion à la BDD
$conn = new mysqli("localhost", "root", "3BienN#EMuwDu!k", "recettes_collaboratif");
if ($conn->connect_error) {
  die("BDD error");
}

// Récupération des détails de la recette et de son auteur
$stmt = $conn->prepare("
  SELECT r.*, u.nom AS auteur
  FROM recettes r
  JOIN utilisateurs u ON r.auteur_id = u.id
  WHERE r.id = ?
");
$stmt->bind_param("i", $recetteId);
$stmt->execute();
$recette = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$recette) {
  die("Recette introuvable");
}

// Vérification si la recette est déjà dans les favoris de l’utilisateur
$isFavori = false;
if ($isLoggedIn) {
  $check = $conn->prepare("
      SELECT 1 FROM favoris
      WHERE user_id = ? AND recette_id = ?
      LIMIT 1
    ");
  $check->bind_param("ii", $userId, $recetteId);
  $check->execute();
  $check->store_result();
  $isFavori = $check->num_rows > 0;
  $check->close();
}

// Traitement de la modification inline d’un commentaire existant
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier_commentaire'])) {
  if ($isLoggedIn) {
    $commentId = (int)$_POST['commentaire_id'];
    $nouveau   = trim($_POST['nouveau_contenu'] ?? '');
    if ($nouveau !== '') {
      $upd = $conn->prepare("
              UPDATE commentaires
              SET contenu = ?, date_publication = NOW()
              WHERE id = ? AND user_id = ?
            ");
      // L'utilisateur ne peut modifier que ses propres commentaires 
      $upd->bind_param("sii", $nouveau, $commentId, $userId);
      $upd->execute();
      $upd->close();
    }
  }
  // Redirection pour éviter le repost du formulaire
  header("Location: recette_detail.php?id={$recetteId}");
  exit;
}

// Traitement de l’ajout de nouveau commentaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['commentaire']) && !isset($_POST['modifier_commentaire'])) {
  // Redirige vers la page connexion si non connecté
  if (!$isLoggedIn) {
    header("Location: connexion.php");
    exit;
  }
  $contenu = trim($_POST['commentaire']);
  if ($contenu !== '') {
    $ins = $conn->prepare("
          INSERT INTO commentaires
            (recette_id, user_id, contenu, date_publication)
          VALUES (?, ?, ?, NOW())
        ");
    $ins->bind_param("iis", $recetteId, $userId, $contenu);
    $ins->execute();
    $ins->close();
  }
  header("Location: recette_detail.php?id=$recetteId");
  exit;
}

// Traitement de la suppression d’un commentaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supprimer_commentaire'])) {
  $commentId = (int)$_POST['commentaire_id'];
  if ($isLoggedIn && $commentId > 0) {
    $del = $conn->prepare("
          DELETE FROM commentaires
          WHERE id = ? AND user_id = ?
        ");
    // L'utilisateur ne peut supprimer que ses propres commentaires
    $del->bind_param("ii", $commentId, $userId);
    $del->execute();
    $del->close();
  }
  header("Location: recette_detail.php?id=$recetteId");
  exit;
}

// Récupération de tous les commentaires pour affichage
$stm = $conn->prepare("
  SELECT c.id, c.contenu, c.date_publication, c.user_id, u.nom
  FROM commentaires c
  JOIN utilisateurs u ON c.user_id = u.id
  WHERE c.recette_id = ?
  ORDER BY c.date_publication DESC
");
$stm->bind_param("i", $recetteId);
$stm->execute();
$comments = $stm->get_result()->fetch_all(MYSQLI_ASSOC);
$stm->close();

// Traitement de l’ajout aux favoris
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_favori'])) {
  // Si connecté et pas déjà favori, on insère
  if ($isLoggedIn && !$isFavori) {
    $add = $conn->prepare("
          INSERT INTO favoris (user_id, recette_id)
          VALUES (?, ?)
        ");
    $add->bind_param("ii", $userId, $recetteId);
    $add->execute();
    $add->close();
  }
  header("Location: recette_detail.php?id=$recetteId");
  exit;
}
// Ferme la connexion à la BDD
$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">

<head>
  <!-- Déclaration de l’encodage et du responsive design -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <!-- Description de la page pour les moteurs de recherche -->
  <?php
  $metaDescription = sprintf(
    "Découvrez la recette de %s : %s Préparez ce plat avec nos instructions détaillées, les ingrédients nécessaires et les conseils du chef.",
    htmlspecialchars($recette['titre']),
    mb_substr(strip_tags($recette['description']), 0, 150) . '...'
  );
  ?>
  <meta name="description" content="<?= $metaDescription ?>">
  <title>Détails de la recette</title>
  <!-- Import du logo pour l'onglet -->
  <link rel="icon" href="../assets/images/logo/favicon.ico" type="image/x-icon">
  <!-- Inclusion des feuilles de style externalisées -->
  <?php require_once __DIR__ . '/../includes/css.php'; ?>
</head>

<body class="d-flex flex-column min-vh-100 page-recette-detail">
  <!-- Inclusion de la nav externalisée -->
  <?php require_once __DIR__ . '/../includes/nav.php'; ?>

  <main class="container py-5" role="main">
    <!-- Titre de la recette -->
    <h1><?= htmlspecialchars($recette['titre']) ?></h1>
    <p>
      <!-- Auteur et date de création formatée -->
      <strong>Auteur :</strong> <?= htmlspecialchars($recette['auteur']) ?>
      &bullet;
      <strong>Créée le :</strong>
      <?= (new DateTime($recette['date_creation']))->format('d/m/Y H:i') ?>
    </p>
    <div class="row mb-4">
      <!-- Si une image existe, on l’affiche dans une colonne -->
      <?php if ($recette['image_url']): ?>
        <div class="col-md-4">
          <img src="../<?= htmlspecialchars($recette['image_url']) ?>"
            class="img-fluid rounded"
            style="max-height:300px;object-fit:cover"
            alt="<?= htmlspecialchars($recette['titre']) ?>">
        </div>
        <!-- Et le contenu dans la colonne adjacente -->
        <div class="col-md-8">
        <?php else: ?>
          <!-- Sinon, on utilise toute la largeur pour le contenu textuel -->
          <div class="col-12">
          <?php endif; ?>
          <!-- Description de la recette -->
          <h2>Description</h2>
          <p><?= nl2br(htmlspecialchars($recette['description'])) ?></p>
          <!-- Liste des ingrédients -->
          <h2>Ingrédients</h2>
          <p><?= nl2br(htmlspecialchars($recette['ingredients'])) ?></p>
          <!-- Instructions -->
          <h2>Instructions</h2>
          <p><?= nl2br(htmlspecialchars($recette['instructions'])) ?></p>
          <?php if ($isLoggedIn): ?>
            <!-- Formulaire d’ajout / indication de favori pour utilisateur connecté -->
            <form method="post" class="mb-4">
              <?php if ($isFavori): ?>
                <!-- Bouton désactivé si déjà favori -->
                <button type="button" class="btn btn-outline-success" disabled>
                  ★ Déjà dans vos favoris
                </button>
              <?php else: ?>
                <!-- Bouton pour ajouter aux favoris -->
                <button type="submit" name="ajouter_favori"
                  class="btn btn-success">
                  ☆ Ajouter aux favoris
                </button>
              <?php endif; ?>
            </form>
          <?php endif; ?>
          </div>
        </div>
        <hr>
        <!-- En-tête de la section commentaires avec comptage -->
        <section id="comments" class="mb-5">
          <h3>Commentaires (<?= count($comments) ?>)</h3>
          <!-- Message si aucun commentaire -->
          <?php if (empty($comments)): ?>
            <p class="text-muted">Pas encore de commentaires.</p>
          <?php else: ?>
            <!-- Boucle sur chaque commentaire -->
            <?php foreach ($comments as $c): ?>
              <div class="mb-3 border-bottom pb-2">
                <small>
                  <!-- Nom de l’auteur du commentaire et date de publication -->
                  <strong><?= htmlspecialchars($c['nom']) ?></strong>
                  le <?= (new DateTime($c['date_publication']))
                        ->format('d/m/Y H:i') ?>
                </small>
                <?php if (
                  $isLoggedIn
                  && $c['user_id'] === $userId
                  && $editCommentId === $c['id']
                ): ?>
                  <!-- Formulaire inline de modification (lorsque l’auteur édite) -->
                  <form method="post" class="mt-2">
                    <input type="hidden" name="commentaire_id" value="<?= $c['id'] ?>">
                    <textarea name="nouveau_contenu"
                      class="form-control mb-2"
                      rows="3" required><?= htmlspecialchars($c['contenu']) ?></textarea>
                    <button name="modifier_commentaire"
                      class="btn btn-sm btn-success me-2">
                      Enregistrer
                    </button>
                    <a href="recette_detail.php?id=<?= $recetteId ?>"
                      class="btn btn-sm btn-secondary">
                      Annuler
                    </a>
                  </form>
                <?php else: ?>
                  <!-- Affichage du contenu du commentaire -->
                  <p><?= nl2br(htmlspecialchars($c['contenu'])) ?></p>
                  <?php if ($isLoggedIn && $c['user_id'] === $userId): ?>
                    <!-- Liens pour modifier ou supprimer (uniquement pour l’auteur) -->
                    <a href="recette_detail.php?id=<?= $recetteId ?>&edit=<?= $c['id'] ?>"
                      class="btn btn-sm btn-outline-primary">
                      Modifier
                    </a>
                    <form method="post" class="d-inline"
                      onsubmit="return confirm('Confirmer la suppression ?');">
                      <input type="hidden" name="commentaire_id" value="<?= $c['id'] ?>">
                      <button name="supprimer_commentaire"
                        class="btn btn-sm btn-outline-danger">
                        Supprimer
                      </button>
                    </form>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </section>
        <?php if ($isLoggedIn): ?>
          <!-- Formulaire d’ajout de nouveau commentaire, si utilisateur connecté -->
          <section id="comment-form">
            <h4>Laisser un commentaire</h4>
            <form method="post">
              <div class="mb-3">
                <label for="commentaire" class="form-label">
                  Votre message
                </label>
                <textarea name="commentaire" id="commentaire"
                  rows="4" class="form-control" required>
            </textarea>
              </div>
              <button type="submit" class="btn btn-primary">
                Envoyer
              </button>
            </form>
          </section>
        <?php else: ?>
          <!-- Invitation à se connecter ou s’inscrire pour laisser un commentaire -->
          <p>
            <a href="connexion.php">Connectez-vous</a> ou
            <a href="inscription.php">inscrivez-vous</a>
            pour laisser un commentaire.
          </p>
        <?php endif; ?>
  </main>
  <!-- Inclusion du footer externalisé -->
  <?php require_once __DIR__ . '/../includes/footer.php'; ?>
  <!-- Inclusion du script Bootstrap et du script personnel externalisé -->
  <?php require_once __DIR__ . '/../includes/script.php'; ?>
</body>

</html>