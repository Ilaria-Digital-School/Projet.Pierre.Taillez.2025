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

/* Si l'utilisateur n'est pas connecté redirige vers la page de connexion */
if (! isLoggedIn()) {
  header('Location: connexion.php');
  exit;
}

/* Appel de la fonction pour savoir si un utilisateur est connecté et stockage du résultat dans une variable réutilisable. */
$isLoggedIn = isLoggedIn();
// ID de l'utilisateur en session
$userId     = $_SESSION['utilisateur']['id'];

// Connexion BDD
$conn = new mysqli("localhost", "root", "3BienN#EMuwDu!k", "recettes_collaboratif");
if ($conn->connect_error) {
  // Si la connexion échoue, on stoppe tout
  die("BDD : " . $conn->connect_error);
}

// Récupérer l’ID de la recette passé en paramètre GET (ou 0 si absent)
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Charger la recette selon le rôle de l'utilisateur
if (isAdmin()) {
  // Si admin : on sélectionne la recette sans vérification d'auteur
  $sql  = "
        SELECT titre, type_de_cuisine, description, ingredients, instructions,
               temps_preparation, image_url
        FROM recettes
        WHERE id = ?
    ";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $id);
} else {
  // Si non-admin : on s'assure que l'utilisateur est bien l'auteur
  $sql  = "
        SELECT titre, type_de_cuisine, description, ingredients, instructions,
               temps_preparation, image_url
        FROM recettes
        WHERE id = ? AND auteur_id = ?
    ";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ii", $id, $userId);
}

// Exécution de la requête et récupération du résultat
$stmt->execute();
$rec = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Si aucune recette trouvée ou accès non autorisé, on stoppe
if (! $rec) {
  die("Recette introuvable ou accès non autorisé.");
}

// Initialisation des messages d'état
$message = '';
$erreur  = '';

// Traitement du formulaire envoyé en POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Collecte et nettoyage des données du formulaire
  $titre        = trim($_POST['titre'] ?? '');
  $type         = $_POST['type_de_cuisine'] ?? '';
  $description  = trim($_POST['description'] ?? '');
  $ingredients = trim($_POST['ingredients'] ?? '');
  $instructions = trim($_POST['instructions'] ?? '');
  $temps        = intval($_POST['temps_preparation'] ?? 0);

  // Redimensionne et compresse une image en respectant une hauteur max.
  function resizeAndCompressImage(string $srcPath, string $dstPath, int $maxHeight = 250, int $quality = 75): bool
  {
    // Récupère les dimensions et le type de l'image
    [$origW, $origH, $type] = getimagesize($srcPath);
    if ($origH <= $maxHeight) {
      // Si déjà plus petit, on se contente de la compression
      $ratio = $origW / $origH;
      $newW  = (int) round($maxHeight * $ratio);
      $newH  = $origH;
    } else {
      // Redimensionnement proportionnel
      $newH  = $maxHeight;
      $newW  = (int) round($origW * ($maxHeight / $origH));
    }

    // Création de la ressource image source selon le type
    switch ($type) {
      case IMAGETYPE_JPEG:
        $srcImg = imagecreatefromjpeg($srcPath);
        break;
      case IMAGETYPE_PNG:
        $srcImg = imagecreatefrompng($srcPath);
        break;
      case IMAGETYPE_WEBP:
        $srcImg = imagecreatefromwebp($srcPath);
        break;
      default:
        return false;
    }
    if (! $srcImg) return false;

    // Canvas pour l’image redimensionnée
    $dstImg = imagecreatetruecolor($newW, $newH);
    // Gestion de la transparence pour PNG et WebP
    if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_WEBP])) {
      imagealphablending($dstImg, false);
      imagesavealpha($dstImg, true);
    }

    // Redimensionnement
    imagecopyresampled(
      $dstImg, $srcImg, 0, 0, 0, 0, $newW, $newH, $origW, $origH
    );

    // Sauvegarde de la nouvelle image
    $saved = false;
    switch ($type) {
      case IMAGETYPE_JPEG:
        $saved = imagejpeg($dstImg, $dstPath, $quality);
        break;
      case IMAGETYPE_PNG:
        $pngLevel = (int) round((100 - $quality) / 10);
        $saved    = imagepng($dstImg, $dstPath, $pngLevel);
        break;
      case IMAGETYPE_WEBP:
        $saved = imagewebp($dstImg, $dstPath, $quality);
        break;
    }

    // Nettoyage
    imagedestroy($srcImg);
    imagedestroy($dstImg);
    return (bool) $saved;
  }

  // On conserve l'URL actuelle de l'image
  $image_url = $rec['image_url'];

  // Si un nouveau fichier a été uploadé, on le traite
  if (!empty($_FILES['image']['name'])) {
    if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
      $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
      $allowed = ['jpg', 'jpeg', 'png', 'webp'];

      if (in_array($ext, $allowed, true)) {
        // Génération d'un nom unique et répertoire de destination
        $newName   = uniqid('R_', true) . ".$ext";
        $uploadDir = __DIR__ . '/../assets/images/uploads/';
        if (!is_dir($uploadDir)) {
          mkdir($uploadDir, 0755, true);
        }
        $destPath = $uploadDir . $newName;

        // Appel à la fonction de redimensionnement et de compression
        if (resizeAndCompressImage(
          $_FILES['image']['tmp_name'],
          $destPath,
          250,
          75
        )) {
          // On met à jour l'URL pour la base de données
          $image_url = "assets/images/uploads/$newName";
        } else {
          $erreur = "Échec du redimensionnement ou de la sauvegarde de l'image.";
        }
      } else {
        $erreur = "Format d'image non autorisé.";
      }
    } else {
      $erreur = "Erreur upload ({$_FILES['image']['error']}).";
    }
  }

  // Validation minimale des champs obligatoires
  if (! $erreur && (empty($titre) || empty($instructions) || $temps <= 0)) {
    $erreur = "Merci de remplir tous les champs obligatoires.";
  }

  // Préparation de la requête UPDATE selon le rôle
  if (isAdmin()) {
    $sql = "
      UPDATE recettes
      SET titre             = ?,
          type_de_cuisine   = ?,
          description       = ?,
          ingredients       = ?,
          instructions      = ?,
          temps_preparation = ?,
          image_url         = ?
      WHERE id = ?
    ";
    $upd = $conn->prepare($sql);
    $upd->bind_param(
      "sssssisi",
      $titre,
      $type,
      $description,
      $ingredients,
      $instructions,
      $temps,
      $image_url,
      $id
    );
  } else {
    $sql = "
      UPDATE recettes
      SET titre             = ?,
          type_de_cuisine   = ?,
          description       = ?,
          ingredients       = ?,
          instructions      = ?,
          temps_preparation = ?,
          image_url         = ?
      WHERE id = ? AND auteur_id = ?
    ";
    $upd = $conn->prepare($sql);
    $upd->bind_param(
      "sssssisii",
      $titre,
      $type,
      $description,
      $ingredients,
      $instructions,
      $temps,
      $image_url,
      $id,
      $userId
    );
  }

  // Exécution de la mise à jour en base de données
  if ($upd->execute()) {
    // Redirection vers la même page avec flag de succès
    header("Location: modifier-recette.php?id={$id}&updated=1");
    exit;
  } else {
    // En cas d'erreur SQL, on l'affiche
    $erreur = "Erreur mise à jour : " . $upd->error;
  }
  $upd->close();
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
  <meta name="description" content="Mettez à jour vos recettes sur Mon site de recettes de cuisine collaboratif : ajustez ingrédients, étapes et astuces en toute simplicité, modifiez votre photo, et partagez vos améliorations avec la communauté." />
  <title>Modifier une recette</title>
  <!-- Import du logo pour l'onglet -->
  <link rel="icon" href="../assets/images/logo/favicon.ico" type="image/x-icon">
  <!-- Inclusion des feuilles de style externalisées -->
  <?php require_once __DIR__ . '/../includes/css.php'; ?>
</head>

<body class="d-flex flex-column min-vh-100 page-modifier-recette">
  <!-- En-tête transparent avec titre centré -->
  <header class="py-3 header-transparent" role="banner">
    <div class="container">
      <h1 class="text-center">Modifier une recette</h1>
    </div>
  </header>
  <!-- Inclusion de la nav externalisée -->
  <?php require_once __DIR__ . '/../includes/nav.php'; ?>
  <!-- Conteneur principal centré verticalement et horizontalement -->
  <main class="flex-fill" role="main">
    <div class="container mt-5" style="max-width:600px;">
      <!-- Bloc opaque avec padding et coins arrondis -->
      <div class="bloc-opaque p-4 rounded-4">
        <h2 class="mb-4 text-center">Modifier une recette</h2>
        <!-- Affiche un message de succès si la mise à jour a réussi -->
        <?php if (isset($_GET['updated'])): ?>
          <div class="alert alert-success">Recette mise à jour avec succès ✅</div>
          <!-- Sinon, si une erreur est définie, on l’affiche -->
        <?php elseif ($erreur): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>
        <!-- Formulaire d’édition de recette (multipart pour upload d’image) -->
        <form method="POST" enctype="multipart/form-data">
          <!-- Champ Titre (obligatoire) -->
          <div class="mb-3">
            <label for="titre" class="form-label">Titre *</label>
            <input type="text" id="titre" name="titre"
              class="form-control"
              value="<?= htmlspecialchars($rec['titre']) ?>"
              required>
          </div>
          <!-- Sélecteur Type de cuisine (obligatoire) -->
          <div class="mb-3">
            <label for="type_de_cuisine" class="form-label">Type de cuisine *</label>
            <select id="type_de_cuisine" name="type_de_cuisine"
              class="form-select" required>
              <option value="">-- Sélectionner --</option>
              <?php
              // Tableau des options de type de cuisine
              $types = [
                'cuisine_traditionnelle' => 'Cuisine traditionnelle',
                'cuisine_du_monde'        => 'Cuisine du monde',
                'fast_food'               => 'Fast-food'
              ];
              foreach ($types as $val => $label): ?>
                <option value="<?= $val ?>"
                  <?= $val === $rec['type_de_cuisine'] ? 'selected' : '' ?>>
                  <?= $label ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <!-- Zone de texte Description (facultative) -->
          <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea id="description" name="description"
              class="form-control" rows="2"><?= htmlspecialchars($rec['description']) ?></textarea>
          </div>
          <!-- Zone de texte Ingrédients (obligatoire) -->
          <div class="mb-3">
            <label for="ingredients" class="form-label">Ingrédients *</label>
            <textarea id="ingredients" name="ingredients"
              class="form-control" rows="4" required><?= htmlspecialchars($rec['ingredients']) ?></textarea>
          </div>
          <!-- Zone de texte Instructions (obligatoire) -->
          <div class="mb-3">
            <label for="instructions" class="form-label">Instructions *</label>
            <textarea id="instructions" name="instructions"
              class="form-control" rows="4" required><?= htmlspecialchars($rec['instructions']) ?></textarea>
          </div>
          <!-- Champ nombre Temps de préparation (obligatoire) -->
          <div class="mb-3">
            <label for="temps_preparation" class="form-label">Temps (minutes) *</label>
            <input type="number" id="temps_preparation" name="temps_preparation"
              class="form-control" min="1"
              value="<?= htmlspecialchars($_POST['temps_preparation'] ?? $rec['temps_preparation'] ?? '') ?>"
              required>
          </div>
          <!-- Upload d’une nouvelle image (facultatif) -->
          <div class="mb-3">
            <label for="image" class="form-label">Image</label>
            <?php if (!empty($rec['image_url'])): ?>
              <div class="mb-2">
                <img src="../<?= htmlspecialchars($rec['image_url']) ?>"
                  alt="Aperçu" class="img-fluid rounded" style="max-height:150px;">
              </div>
            <?php endif; ?>
            <!-- Input fichier (JPEG, PNG autorisés) -->
            <input class="form-control" type="file" id="image" name="image"
              accept="image/jpeg,image/png,image/webp">
            <small class="text-muted">Laisser vide pour conserver l’image actuelle.</small>
          </div>
          <!-- Bouton de soumission -->
          <button type="submit" class="btn btn-success w-100">
            Enregistrer les modifications
          </button>
          <!-- Lien pour annuler et revenir à la page compte -->
          <a href="compte.php" class="btn btn-secondary w-100 mt-2">
            Annuler
          </a>
        </form>
      </div>
    </div>
  </main>
  <!-- Inclusion du footer externalisé -->
  <?php require_once __DIR__ . '/../includes/footer.php'; ?>
  <!-- Inclusion du script Bootstrap et du script personnel externalisé -->
  <?php require_once __DIR__ . '/../includes/script.php'; ?>
</body>

</html>