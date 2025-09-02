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

// Initialise les variables de nom et email à des chaînes vides par défaut
$nom   = '';
$email = '';

/* Si l'utilisateur est connecté, récupère son nom et son email de la session en les sécurisant pour éviter les failles XSS via htmlspecialchars */
if ($isLoggedIn) {
  $nom   = htmlspecialchars($_SESSION['utilisateur']['nom']);
  $email = htmlspecialchars($_SESSION['utilisateur']['email']);
}

// Connexion BDD
$conn = new mysqli("localhost", "root", "3BienN#EMuwDu!k", "recettes_collaboratif");
if ($conn->connect_error) {
  die("Erreur BDD : " . $conn->connect_error);
}

// Récupération des filtres avec valeurs par défaut
$type       = $_GET['type']       ?? 'all';
$ingredient = trim($_GET['ingredient'] ?? '');
$time       = $_GET['time']       ?? 'all';

// Construction de la clause WHERE
$where = ["1=1"];
// Filtre sur le type de cuisine si différent de 'all'
if ($type !== 'all') {
  $t = $conn->real_escape_string($type);
  $where[] = "r.type_de_cuisine = '$t'";
}
// Filtre sur le mot-clé dans titre, description ou instructions
if ($ingredient !== '') {
  $i = $conn->real_escape_string($ingredient);
  $where[] = "(r.titre LIKE '%$i%' OR r.description LIKE '%$i%' OR r.instructions LIKE '%$i%')";
}
// Filtre sur le temps de préparation en fonction de la valeur choisie
switch ($time) {
  case '15':
    $where[] = "r.temps_preparation <= 15";
    break;
  case '30':
    $where[] = "r.temps_preparation <= 30";
    break;
  case '45':
    $where[] = "r.temps_preparation <= 45";
    break;
  case '60':
    $where[] = "r.temps_preparation <= 60";
    break;
  case 'more':
    $where[] = "r.temps_preparation > 60";
    break;
}

// Assemblage et exécution de la requête SQL
$sql = "
  SELECT 
    r.id,
    r.titre,
    r.type_de_cuisine,
    r.description,
    r.ingredients,
    r.instructions,
    r.temps_preparation,
    r.image_url,
    r.date_creation,
    u.nom AS auteur
  FROM recettes r
  JOIN utilisateurs u ON r.auteur_id = u.id
  WHERE " . implode(" AND ", $where) . "
  ORDER BY r.date_creation DESC
";
// Exécution et récupération du jeu de résultats
$result = $conn->query($sql);

// Mapping pour l'affichage des types
$typeLabels = [
  'cuisine_traditionnelle' => 'Cuisine traditionnelle',
  'cuisine_du_monde'        => 'Cuisine du monde',
  'fast_food'               => 'Fast-food'
];
?>

<!DOCTYPE html>
<html lang="fr">

<head>
  <!-- Déclaration de l’encodage et du responsive design -->
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <!-- Description de la page pour les moteurs de recherche -->
  <meta name="description" content="Recherchez la recette idéale sur Mon site de recettes de cuisine collaboratif: filtrez par type de cuisine, ingrédients ou temps de préparation, et obtenez des résultats rapides et adaptés à vos envies culinaires." />
  <title>Recherche de recettes</title>
  <!-- Import du logo pour l'onglet -->
  <link rel="icon" href="../assets/images/logo/favicon.ico" type="image/x-icon">
  <!-- Inclusion des feuilles de style externalisées -->
  <?php require_once __DIR__ . '/../includes/css.php'; ?>
</head>

<body class="d-flex flex-column min-vh-100 page-recherche">
  <!-- En-tête transparent avec titre centré -->
  <header class="py-3 header-transparent" role="banner">
    <div class="container">
      <h1 class="text-center">Recherche</h1>
    </div>
  </header>
  <!-- Inclusion de la nav externalisée -->
  <?php require_once __DIR__ . '/../includes/nav.php'; ?>
  <!-- Conteneur principal occupant l’espace disponible -->
  <main class="flex-fill" role="main">
    <div class="container mt-3 mb-4" style="max-width:800px;">
      <!-- Bloc semi-transparent pour le formulaire -->
      <div class="bloc-opaque p-4 mb-4 rounded-4">
        <h2>Recherchez une recette par :</h2>
        <!-- Formulaire structuré en grille -->
        <form class="row g-3" method="get">
          <!-- Filtre « Type de cuisine » -->
          <div class="col-md-4">
            <label for="type" class="form-label">Type de cuisine</label>
            <select id="type" name="type" class="form-select">
              <option value="all">Toutes</option>
              <option value="cuisine_traditionnelle"
                <?= $type === 'cuisine_traditionnelle' ? 'selected' : '' ?>>
                Cuisine traditionnelle
              </option>
              <option value="cuisine_du_monde"
                <?= $type === 'cuisine_du_monde' ? 'selected' : '' ?>>
                Cuisine du monde
              </option>
              <option value="fast_food"
                <?= $type === 'fast_food' ? 'selected' : '' ?>>
                Fast-food
              </option>
            </select>
          </div>
          <!-- Filtre « Ingrédient » : champ texte avec préremplissage -->
          <div class="col-md-4">
            <label for="ingredient" class="form-label">Ingrédient</label>
            <input type="text" id="ingredient" name="ingredient"
              class="form-control"
              value="<?= htmlspecialchars($ingredient) ?>"
              placeholder="exemple : jambon, tomate…">
          </div>
          <!-- Filtre « Temps max » -->
          <div class="col-md-4">
            <label for="time" class="form-label">Temps max</label>
            <select id="time" name="time" class="form-select">
              <option value="all" <?= $time === 'all' ? 'selected' : '' ?>>Toutes</option>
              <option value="15" <?= $time === '15' ? 'selected' : '' ?>>≤ 15 min</option>
              <option value="30" <?= $time === '30' ? 'selected' : '' ?>>≤ 30 min</option>
              <option value="45" <?= $time === '45' ? 'selected' : '' ?>>≤ 45 min</option>
              <option value="60" <?= $time === '60' ? 'selected' : '' ?>>≤ 60 min</option>
              <option value="more" <?= $time === 'more' ? 'selected' : '' ?>>> 60 min</option>
            </select>
          </div>
          <!-- Bouton de soumission aligné à droite -->
          <div class="col-12 text-end">
            <button type="submit" class="btn btn-primary">Rechercher</button>
          </div>
        </form>
      </div>
    </div>
    <div class="container-fluid px-4">
      <!-- Si des recettes correspondent aux critères -->
      <?php if ($result && $result->num_rows > 0): ?>
        <!-- Affichage dans un tableau -->
        <div class="table-responsive rounded-4">
          <table class="table table-striped table-hover align-middle">
            <thead class="table-dark text-center">
              <tr>
                <th>Image</th>
                <th>Titre</th>
                <th>Type de cuisine</th>
                <th>Description</th>
                <th>Temps (min)</th>
                <th>Auteur</th>
                <th>Créée le</th>
                <th>Lien</th>
              </tr>
            </thead>
            <tbody class="text-center">
              <?php while ($row = $result->fetch_assoc()):
                // Détermine le libellé humain du type
                $key       = $row['type_de_cuisine'];
                $typeLabel = $typeLabels[$key]
                  ?? ucfirst(str_replace('_', ' ', $key));
                // Nettoie et tronque la description à 80 caractères
                $desc = strip_tags($row['description']);
                if (mb_strlen($desc) > 80) {
                  $desc = mb_substr($desc, 0, 80) . '…';
                }
              ?>
                <tr>
                  <!-- Colonne image : uniquement si une URL est fournie -->
                  <td>
                    <?php if ($row['image_url']): ?>
                      <img
                        src="../<?= htmlspecialchars($row['image_url']) ?>"
                        alt="<?= htmlspecialchars($row['titre']) ?>"
                        class="img-thumbnail"
                        width="300"
                        height="225"
                        loading="lazy"
                        decoding="async">
                    <?php else: ?>
                    <?php endif; ?>
                  </td>
                  <!-- Colonnes texte, sécurisées avec htmlspecialchars -->
                  <td><?= htmlspecialchars($row['titre']) ?></td>
                  <td><?= htmlspecialchars($typeLabel) ?></td>
                  <td><?= nl2br(htmlspecialchars($desc)) ?></td>
                  <td><?= (int)$row['temps_preparation'] ?></td>
                  <td><?= htmlspecialchars($row['auteur']) ?></td>
                  <!-- Date format francophone jour/mois/année heure:minute -->
                  <td><?= (new DateTime($row['date_creation']))->format('d/m/Y H:i') ?></td>
                  <td>
                    <!-- Lien vers le détail de la recette -->
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
        <!-- Message d’avertissement si aucun résultat -->
      <?php else: ?>
        <div class="alert alert-warning text-center">
          Aucune recette ne correspond à ces critères.
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