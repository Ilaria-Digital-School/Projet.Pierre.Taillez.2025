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

// Initialise le pseudo de l’utilisateur à une chaîne vide (sera rempli après authentification)
$nom = '';

// Initialise l’adresse email de l’utilisateur à une chaîne vide (sera rempli après authentification)
$email = '';

// Initialise l’ID de l’utilisateur à null (indique qu’aucun ID n’est encore défini)
$userId = null;

if ($isLoggedIn) {
  // Échappe le nom et l’email pour les afficher sans risque de XSS
  $nom    = htmlspecialchars($_SESSION['utilisateur']['nom']);
  $email  = htmlspecialchars($_SESSION['utilisateur']['email']);
  $userId = $_SESSION['utilisateur']['id'];
}

// Connexion à la BDD
$conn = new mysqli("localhost", "root", "3BienN#EMuwDu!k", "recettes_collaboratif");
// Arrêt du script avec message d'erreur si la connexion échoue
if ($conn->connect_error) {
  die("Erreur de connexion à la BDD : " . $conn->connect_error);
}

// Récupère les favoris si connecté
if ($isLoggedIn) {
  $userId = $_SESSION['utilisateur']['id'];
  $sql = "
    SELECT r.id, r.titre, r.type_de_cuisine, r.date_creation
    FROM recettes r
    INNER JOIN favoris f
       ON f.recette_id = r.id
    WHERE f.user_id = ?
    ORDER BY r.date_creation DESC
  ";
  $stmt        = $conn->prepare($sql);
  $stmt->bind_param("i", $userId);
  $stmt->execute();
  $mesFavoris = $stmt->get_result();
  $stmt->close();
} else {
  $mesFavoris = null;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">

<head>
  <!-- Déclaration de l’encodage et du responsive design -->
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <!-- Description de la page pour les moteurs de recherche -->
  <meta name="description" content="Retrouvez vos recettes favorites sur Mon site de recettes de cuisine collaboratif : organisez vos coups de cœur, partagez-les avec la communauté et inspirez-vous pour vos prochains plats." />
  <title>Mes recettes favorites</title>
  <!-- Import du logo pour l'onglet -->
  <link rel="icon" href="../assets/images/logo/favicon.ico" type="image/x-icon">
  <!-- Inclusion des feuilles de style externalisées -->
  <?php require_once __DIR__ . '/../includes/css.php'; ?>
</head>

<body class="d-flex flex-column min-vh-100 page-favoris">
  <!-- En-tête transparent avec titre centré -->
  <header class="py-3 header-transparent" role="banner">
    <div class="container">
      <h1 class="text-center">Mes recettes favorites</h1>
    </div>
  </header>
  <!-- Inclusion de la nav externalisée -->
  <?php require_once __DIR__ . '/../includes/nav.php'; ?>
  <!-- Conteneur principal centré avec marge en haut -->
  <main class="flex-fill" role="main">
    <div class="container mt-5" style="max-width:1000px;">
      <div class="bloc-opaque p-4 text-center rounded-4" style="max-width:600px; margin:0 auto;">
        <?php if ($isLoggedIn): // Si l’utilisateur est connecté?>
          <h2 class="mb-1">Bonjour <?= $nom ?> !</h2>
        <?php else: // Sinon, afficher l’invitation à se connecter ou à s'inscrire ?>
          <h2 class="mb-4">Vous n'êtes pas connecté</h2>
          <p class="mb-3">
            Pour voir vos favoris, veuillez vous connecter ou vous inscrire :
          </p>
          <!-- liens vers la page connexion ou inscription -->
          <div class="d-grid gap-2">
            <a href="connexion.php" class="btn btn-primary">Se connecter</a>
            <a href="inscription.php"
              class="btn btn-outline-primary">S'inscrire</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <?php if ($isLoggedIn): // Si connecté, afficher les recettes favorites?>
      <div class="container-fluid mt-5 bloc-opaque p-4 rounded-4" style="max-width:1000px;">
        <h3>Voici vos recettes favorites</h3>
        <!-- Vérifie qu’il existe au moins un favori -->
        <?php if ($mesFavoris && $mesFavoris->num_rows > 0): ?>
          <!-- Tableau responsive listant chaque favori -->
          <div class="table-responsive mt-4">
            <table class="table table-striped table-hover table-sm align-middle">
              <thead class="table-info text-dark">
                <tr>
                  <th scope="col">Titre</th>
                  <th scope="col">Type de cuisine</th>
                  <th scope="col">Date de publication</th>
                  <th scope="col">Actions</th>
                </tr>
              </thead>
              <tbody>
                <!-- Parcours de chaque enregistrement -->
                <?php while ($r = $mesFavoris->fetch_assoc()): ?>
                  <tr>
                    <!-- On affiche et on échappe les données pour éviter les attaques XSS -->
                    <td><?= htmlspecialchars($r['titre']) ?></td>
                    <td>
                      <?= htmlspecialchars(
                        ucfirst(str_replace('_', ' ', $r['type_de_cuisine']))
                      ) ?>
                    </td>
                    <td>
                      <?= (new DateTime($r['date_creation']))
                        ->format('d/m/Y H:i') ?>
                    </td>
                    <td>
                      <!-- Boutons pour voir le détail ou retirer des favoris -->
                      <a href="recette_detail.php?id=<?= $r['id'] ?>"
                        class="btn btn-sm btn-outline-dark me-2">
                        Voir
                      </a>
                      <a href="supprimer-favori.php?id=<?= $r['id'] ?>"
                        class="btn btn-sm btn-outline-danger"
                        onclick="return confirm('Retirer cette recette de vos favoris ?');">
                        Retirer
                      </a>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <!-- Sinon si aucun favori n’est trouvé -->
        <?php else: ?>
          <div class="alert alert-info mt-3">
            Vous n’avez aucune recette dans vos favoris.
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </main>
  <!-- Inclusion du footer externalisé -->
  <?php require_once __DIR__ . '/../includes/footer.php'; ?>
  <!-- Inclusion du script Bootstrap et du script personnel externalisé -->
  <?php require_once __DIR__ . '/../includes/script.php'; ?>
</body>

</html>