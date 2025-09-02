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

// Connexion à la BDD
$conn = new mysqli("localhost", "root", "3BienN#EMuwDu!k", "recettes_collaboratif");
if ($conn->connect_error) {
  die("Connexion BDD échouée : " . $conn->connect_error);
}

// Requête pour récupérer les recettes et le nom de l'auteur
$sql = "
  SELECT 
    r.id,
    r.titre,
    r.type_de_cuisine,
    r.description,
    r.temps_preparation,
    r.instructions,
    r.image_url,
    r.date_creation,
    u.nom AS auteur
  FROM recettes r
  JOIN utilisateurs u ON r.auteur_id = u.id
  ORDER BY r.date_creation DESC
";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
  <!-- Déclaration de l’encodage et du responsive design -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <!-- Description de la page pour les moteurs de recherche -->
  <meta name="description" content="Explorez notre sélection de recettes : entrées, plats et desserts, pas à pas, ingrédients détaillés, astuces et avis pour inspirer votre cuisine." />
  <title>Liste des recettes</title>
  <!-- Import du logo pour l'onglet -->
  <link rel="icon" href="../assets/images/logo/favicon.ico" type="image/x-icon">
  <!-- Inclusion des feuilles de style externalisées -->
  <?php require_once __DIR__ . '/../includes/css.php'; ?>
</head>

<body class="d-flex flex-column min-vh-100 page-recettes">
  <!-- En-tête transparent avec titre centré -->
  <header class="py-3 header-transparent" role="banner">
    <div class="container">
      <h1 class="text-center">Toutes les recettes</h1>
    </div>
  </header>
  <!-- Inclusion de la nav externalisée -->
  <?php require_once __DIR__ . '/../includes/nav.php'; ?>
  <!-- Conteneur principal occupant l’espace disponible -->
  <main class="flex-fill" role="main">
    <div class="container-fluid mt-5">
      <!-- Si la requête a retourné des lignes -->
      <?php if ($result && $result->num_rows > 0): ?>
        <!-- Conteneur responsive avec coins arrondis pour le tableau -->
        <div class="table-responsive rounded-4">
          <table class="table table-striped table-hover align-middle">
            <!-- En-tête du tableau avec style sombre et texte centré -->
            <thead class="table-dark text-center">
              <tr>
                <th>Image</th>
                <th>Titre</th>
                <th>Type de cuisine</th>
                <th>Description</th>
                <th>Préparation (min)</th>
                <th>Auteur</th>
                <th>Crée le</th>
                <th>Lien</th>
              </tr>
            </thead>
            <!-- Corps du tableau -->
            <tbody class="text-center">
              <!-- Parcours de chaque recette -->
              <?php while ($row = $result->fetch_assoc()):
                // Tableau de correspondance pour l'affichage du type
                $labels = [
                  'cuisine_traditionnelle' => 'Cuisine traditionnelle',
                  'cuisine_du_monde' => 'Cuisine du monde',
                  'fast_food' => 'Fast-food',
                ]; ?>
                <tr>
                  <td>
                    <?php if ($row['image_url']): ?>
                      <!-- Affiche la miniature si une image est définie -->
                      <img
                        src="../<?= htmlspecialchars($row['image_url']) ?>"
                        alt="Image <?= htmlspecialchars($row['titre']) ?>"
                        class="img-thumbnail"
                        style="max-width: 200px;">
                    <?php else: ?>
                      <!-- Tiret de remplacement si pas d'image -->
                      —
                    <?php endif; ?>
                  </td>
                  <!-- Titre de la recette -->
                  <td><?= htmlspecialchars($row['titre']) ?></td>
                  <?php
                  // Récupère le libellé du type de cuisine
                  $key     = $row['type_de_cuisine'];
                  $display = $labels[$key] ?? ucfirst(str_replace('_', ' ', $key));
                  ?>
                  <td><?= htmlspecialchars($display) ?></td>
                  <td>
                    <!-- Description limitée à 80 caractères -->
                    <?= nl2br(htmlspecialchars(
                      mb_strlen($row['description']) > 80
                        ? mb_substr($row['description'], 0, 80) . '…'
                        : $row['description']
                    )) ?>
                  </td>
                  <!-- Temps de préparation en minutes -->
                  <td><?= htmlspecialchars($row['temps_preparation']) ?></td>
                  <!-- Nom de l'auteur de la recette -->
                  <td><?= htmlspecialchars($row['auteur']) ?></td>
                  <!-- Date de création formatée -->
                  <td><?= (new DateTime($row['date_creation']))->format('d/m/Y H:i') ?></td>
                  <td>
                    <!-- Lien vers la page de détails de la recette -->
                    <a href="recette_detail.php?id=<?= $row['id'] ?>"
                      class="btn btn-sm btn-outline-primary">
                      Voir la recette
                    </a>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <!-- Message d'alerte si aucune recette n'est trouvée -->
        <div class="alert alert-info text-center">
          Aucune recette trouvée pour le moment.
        </div>
      <?php endif; ?>
      <?php
      // Libère les ressources de la requête et ferme la connexion à la BDD
      if ($result) $result->free();
      $conn->close();
      ?>
    </div>
  </main>
  <!-- Inclusion du footer externalisé -->
  <?php require_once __DIR__ . '/../includes/footer.php'; ?>
  <!-- Inclusion du script Bootstrap et du script personnel externalisé -->
  <?php require_once __DIR__ . '/../includes/script.php'; ?>
</body>

</html>