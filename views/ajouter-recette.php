<?php

// Démarrage de la session si elle n'est pas déjà active
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

//Cette fonction n'est redéfinie que si elle n'existe pas encore.
if (!function_exists('isLoggedIn')) {
  /* Vérifie si l’utilisateur est connecté. Return bool true si la clé ['utilisateur']['id'] existe en session, sinon false */
  function isLoggedIn(): bool
  {
    return isset($_SESSION['utilisateur']['id']);
  }
}

//Cette fonction n'est redéfinie que si elle n'existe pas encore.
if (!function_exists('isAdmin')) {
  /* Vérifie si l’utilisateur connecté possède le rôle « admin ». Return bool true si connecté ET rôle égal à 'admin', sinon false */
  function isAdmin(): bool
  {
    return isLoggedIn() && $_SESSION['utilisateur']['role'] === 'admin';
  }
}

/* Appel de la fonction pour savoir si un utilisateur est connecté et stockage du résultat dans une variable réutilisable. */
$isLoggedIn = isLoggedIn();

// Initialisation des messages (succès ou erreur)
$message = $erreur = "";

// Traitement du formulaire uniquement si l’utilisateur est connecté et que la requête est en POST 
if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST') {
  // Connexion à la BDD
  $conn = new mysqli("localhost", "root", "3BienN#EMuwDu!k", $_ENV['DB_NAME'] ?? 'recettes_collaboratif');
  if ($conn->connect_error) die("BDD : " . $conn->connect_error);

  // Redimensionne et compresse une image en respectant une hauteur max.
  function resizeAndCompressImage(string $srcPath, string $dstPath, int $maxHeight = 250, int $quality = 75): bool
  {
    // Récupère les dimensions et le type
    [$origW, $origH, $type] = getimagesize($srcPath);
    if ($origH <= $maxHeight) {
      // Si déjà plus petit, on se contente de la compression
      $ratio = $origW / $origH;
      $newW  = (int) round($maxHeight * $ratio);
      $newH  = $origH;
    } else {
      // Sinon, redimensionnement proportionnel
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
        return false; // Type non supporté
    }
    if (! $srcImg) return false; // Échec de chargement

    // Canvas pour l’image redimensionnée
    $dstImg = imagecreatetruecolor($newW, $newH);
    // Conserver la transparence pour PNG/WebP
    if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_WEBP])) {
      imagealphablending($dstImg, false);
      imagesavealpha($dstImg, true);
    }

    // Redimensionnement
    imagecopyresampled(
      $dstImg, $srcImg, 0, 0, 0, 0, $newW, $newH, $origW, $origH
    );

    // Sauvegarde
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

  // Récupération et nettoyage des champs du formulaire
  $titre        = trim($_POST['titre'] ?? '');
  $type_de_cuisine = $_POST['type_de_cuisine'] ?? '';
  $ingredients = trim($_POST['ingredients'] ?? '');
  $description  = trim($_POST['description'] ?? '');
  $instructions = trim($_POST['instructions'] ?? '');
  $temps        = intval($_POST['temps_preparation'] ?? 0);
  $auteur_id = $_SESSION['utilisateur']['id'] ?? null;

  // Upload et redimensionnement
  $image_url = '';
  if (isset($_FILES['image']) && !empty($_FILES['image']['name'])) {
    $errorCode = $_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($errorCode === UPLOAD_ERR_OK) {
      $tmp      = $_FILES['image']['tmp_name'];
      $ext      = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
      $allowed  = ['jpg', 'jpeg', 'png', 'webp'];

      if (!in_array($ext, $allowed)) {
        $erreur = "Format d'image non autorisé.";
      } else {
        // Génération d’un nom unique pour l’image
        $newName   = uniqid('R_', true) . ".$ext";
        $uploadDir = __DIR__ . '/../assets/images/uploads/';
        if (!is_dir($uploadDir)) {
          mkdir($uploadDir, 0755, true);
        }
        $destPath  = $uploadDir . $newName;

        // Appel à la fonction de redimensionnement et de compression
        if (resizeAndCompressImage($tmp, $destPath, 250, 75)) {
          $image_url = "assets/images/uploads/$newName";
        } else {
          $erreur = "Échec de la compression/upload de l'image.";
        }
      }
    } else {
      $erreur = "Erreur lors de l'upload (code $errorCode).";
    }
  }

  // Validation minimale des champs obligatoires
  if (!$erreur && (empty($titre) || empty($instructions) || $temps <= 0)) {
    $erreur = "Merci de remplir tous les champs obligatoires.";
  }

  // Insertion dans la DBB
  if (!$erreur) {
    $stmt = $conn->prepare("
  INSERT INTO recettes
    (titre, type_de_cuisine, description, ingredients, instructions, temps_preparation, auteur_id, image_url)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
    if ($stmt === false) {
      die("Erreur dans prepare() : " . $conn->error);
    }

    $stmt->bind_param(
      "sssssiss",
      $titre,
      $type_de_cuisine,
      $description,
      $ingredients,
      $instructions,
      $temps,
      $auteur_id,
      $image_url
    );
    if ($stmt->execute()) {
      $message = "✅ Recette ajoutée avec succès !";
    } else {
      $erreur = "Erreur ajout : " . $stmt->error;
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
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <!-- Description de la page pour les moteurs de recherche -->
  <meta name="description" content="Ajoutez votre recette sur Mon site de recettes de cuisine collaboratif : décrivez ingrédients, étapes et astuces en quelques clics, partagez votre savoir-faire culinaire et inspirez toute la communauté." />
  <title>Ajouter une recette</title>
  <!-- Import du logo pour l'onglet -->
  <link rel="icon" href="../assets/images/logo/favicon.ico" type="image/x-icon">
  <!-- Inclusion des feuilles de style externalisées -->
  <?php require_once __DIR__ . '/../includes/css.php'; ?>
</head>

<body class="d-flex flex-column min-vh-100 page-ajouter-recette">
  <!-- En-tête transparent avec titre centré -->
  <header class="py-3 header-transparent" role="banner">
    <div class="container">
      <h1 class="text-center">Ajouter une recette</h1>
    </div>
  </header>
  <!-- Inclusion de la nav externalisée -->
  <?php require_once __DIR__ . '/../includes/nav.php'; ?>
  <!-- Zone principale de la page, prend l’espace restant (flex-fill) -->
  <main class="flex-fill" role="main">
    <!-- Conteneur Bootstrap centré, marge haute et largeur max de 600px -->
    <div class="container mt-5" style="max-width:600px;">
      <!-- Si l’utilisateur est connecté, on affiche le formulaire d’ajout -->
      <?php if ($isLoggedIn): ?>
        <!-- Bloc semi-transparent (personnalisé), padding et coins arrondis -->
        <div class="bloc-opaque p-4 rounded-4">
          <!-- Titre centré avec marge basse -->
          <h2 class="mb-4 text-center">Nouvelle recette</h2>
          <!-- Affiche le message de succès, échappé pour prévenir les XSS -->
          <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
            <!-- Affiche l’erreur, échappée pour prévenir les XSS -->
          <?php elseif ($erreur): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
          <?php endif; ?>
          <!-- Formulaire POST avec support d'upload de fichier -->
          <form method="POST" enctype="multipart/form-data">
            <!-- Champ texte pour le titre, requis -->
            <div class="mb-3">
              <label for="titre" class="form-label">Titre *</label>
              <input type="text" id="titre" name="titre" class="form-control" required>
            </div>
            <!-- Liste déroulante pour le type de cuisine, requis -->
            <div class="mb-3">
              <label for="type_de_cuisine" class="form-label">Type de cuisine *</label>
              <select class="form-select" id="type_de_cuisine" name="type_de_cuisine" required>
                <option value="">-- Sélectionner --</option>
                <option value="cuisine_traditionnelle">Cuisine traditionnelle</option>
                <option value="cuisine_du_monde">Cuisine du monde</option>
                <option value="fast_food">Fast-food</option>
              </select>
            </div>
            <!-- Zone de texte pour la description -->
            <div class="mb-3">
              <label for="description" class="form-label">Description</label>
              <textarea id="description" name="description" class="form-control" rows="2"></textarea>
            </div>
            <!-- Zone de texte pour la liste des ingrédients -->
            <div class="mb-3">
              <label for="ingredients" class="form-label">Ingredients</label>
              <textarea id="ingredients" name="ingredients" class="form-control" rows="4"></textarea>
            </div>
            <!-- Zone de texte pour les instructions, requis -->
            <div class="mb-3">
              <label for="instructions" class="form-label">Instructions *</label>
              <textarea id="instructions" name="instructions" class="form-control" rows="4" required></textarea>
            </div>
            <!-- Champ numérique pour le temps de préparation, valeur minimale = 1 -->
            <div class="mb-3">
              <label for="temps_preparation" class="form-label">Temps (min) *</label>
              <input type="number" id="temps_preparation" name="temps_preparation"
                class="form-control" min="1" required>
            </div>
            <!-- Sélecteur de fichier limité aux formats autorisés -->
            <div class="mb-3">
              <label for="image" class="form-label">Image (JPEG, JPG, PNG, WEBP)</label>
              <input class="form-control" type="file" id="image" name="image"
                accept="image/jpeg,image/jpg,image/png,image/webp">
            </div>
            <!-- Bouton vert pleine largeur pour soumettre le formulaire -->
            <button type="submit" class="btn btn-success w-100">Enregistrer</button>
          </form>
        </div>
        <!-- Sinon si l’utilisateur n’est pas connecté, on l’invite à se connecter -->
      <?php else: ?>
        <div class="bloc-opaque p-4 text-center rounded-4">
          <h2 class="mb-4">Vous n'êtes pas connecté</h2>
          <p class="mb-3">
            Pour ajouter une recette, veuillez vous connecter ou vous inscrire :
          </p>
          <div class="d-grid gap-2">
            <a href="connexion.php" class="btn btn-primary">Se connecter</a>
            <a href="inscription.php" class="btn btn-outline-primary">S'inscrire</a>
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