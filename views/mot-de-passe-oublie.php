<?php

// Démarre la session ou reprend celle déjà existante.
session_start();

// Charge l'autoload de Composer pour Dotenv et Symfony Mailer
require __DIR__ . '/../vendor/autoload.php';

// Import des classes nécessaires
use Dotenv\Dotenv;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

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

// Chargement des variables d’environnement depuis le fichier .env à la racine
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Enregistre un message flash en session
function setFlash(string $type, string $msg): void
{
  $_SESSION['flash'][$type] = $msg;
}

// Récupère et supprime un message flash de la session
function getFlash(string $type): ?string
{
  if (!isset($_SESSION['flash'][$type])) {
    return null;
  }
  $msg = $_SESSION['flash'][$type];
  unset($_SESSION['flash'][$type]);
  return $msg;
}

// Connexion à la BDD
$conn = new mysqli("localhost", "root", "3BienN#EMuwDu!k", "recettes_collaboratif");
if ($conn->connect_error) {
  // Termine le script en cas d'erreur de connexion
  die("Erreur de connexion : " . $conn->connect_error);
}

// Traitement du formulaire envoyé en POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Récupère et nettoie l'email saisi
  $email = trim($_POST['email'] ?? '');

  // Validation minimale : champ non vide
  if ($email === '') {
    setFlash('error', "Veuillez saisir votre adresse email.");
    header('Location: mot-de-passe-oublie.php');
    exit;
  }

  // Vérifie l'existence de l'utilisateur dans la table 'utilisateurs'
  $stmt = $conn->prepare("SELECT nom FROM utilisateurs WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $stmt->store_result();

  if ($stmt->num_rows !== 1) {
    // Aucun compte trouvé => message d'erreur et retour au formulaire
    setFlash('error', "Aucun compte ne correspond à cette adresse.");
    $stmt->close();
    header('Location: mot-de-passe-oublie.php');
    exit;
  }

  // Récupère le nom de l'utilisateur pour le personnaliser
  $stmt->bind_result($userName);
  $stmt->fetch();
  $stmt->close();

  // Sécurise le nom pour l'injection dans le mail
  $greeting  = htmlspecialchars($userName, ENT_QUOTES, 'UTF-8');
  // Génère un token aléatoire et une date d'expiration (1 heure)
  $token     = bin2hex(random_bytes(32));
  $expire    = date("Y-m-d H:i:s", strtotime("+1 hour"));

  // Enregistre les données dans la table 'password_resets'
  $ins = $conn->prepare("
        INSERT INTO password_resets (email, token, expiration)
        VALUES (?, ?, ?)
    ");
  $ins->bind_param("sss", $email, $token, $expire);
  $ins->execute();
  $ins->close();

  // Lien de réinitialisation
  $resetLink = sprintf(
    "http://localhost/fichierphp/recettes_collaboratif/views/reinitialiser-mot-de-passe.php?token=%s",
    $token
  );

  // Récupère le DSN SMTP depuis les variables d’environnement
  $dsn = $_ENV['MAILER_DSN'] ?? null;
  if (!$dsn) {
    setFlash('error', "Configuration SMTP manquante.");
    header('Location: mot-de-passe-oublie.php');
    exit;
  }

  // Envoi de l'email via Symfony Mailer
  try {
    // Initialise le transport et le mailer
    $transport = Transport::fromDsn($dsn);
    $mailer    = new Mailer($transport);

    $year = date('Y');
    // Corps HTML du message
    $html = ''
      . '<!DOCTYPE html>'
      . '<html lang="fr"><head><meta charset="UTF-8"><title>Réinitialisation</title></head><body '
      . 'style="margin:0;padding:0;background:#f5f5f5;font-family:Arial,sans-serif;">'
      . '<div style="max-width:600px;margin:40px auto;background:#fff;border-radius:8px;overflow:hidden">'
      . '<div style="background:#333;padding:20px;text-align:center">'
      . '<h1 style="color:#fff;font-size:24px;margin:0">Mon site de recettes collaboratif</h1>'
      . '</div><div style="padding:30px;color:#333;line-height:1.5">'
      . "<p>Bonjour {$greeting},</p>"
      . '<p>Vous avez demandé la réinitialisation de votre mot de passe. Ce lien expire dans 1 heure.</p>'
      . '<p style="text-align:center;margin:30px 0">'
      . "<a href=\"{$resetLink}\" style=\"display:inline-block;padding:12px 24px;background:#28a745;"
      . "color:#fff;text-decoration:none;border-radius:4px;font-weight:bold;\">"
      . 'Réinitialiser mon mot de passe</a></p>'
      . '<p style="font-size:14px;color:#777">Si vous n’avez pas fait cette demande, ignorez cet e-mail.</p>'
      . '</div><div style="background:#f0f0f0;padding:15px;text-align:center;font-size:12px;color:#999">'
      . "© {$year} Mon site de recettes collaboratif"
      . '</div></div></body></html>';

    // Prépare l'objet Email
    $emailMessage = (new Email())
      ->from(new Address('noreply@monsite.com', 'Recettes Collaboratif'))
      ->to($email)
      ->subject('Réinitialisation de votre mot de passe')
      ->text(
        "Bonjour $greeting,\n\n"
          . "Vous avez demandé la réinitialisation de votre mot de passe.\n"
          . "Lien : $resetLink\n\n"
          . "Ce lien est valable 1 heure.\n\n"
          . "© {$year} Mon site de recettes collaboratif"
      )
      ->html($html);

    // Envoie le mail
    $mailer->send($emailMessage);
    // Message de succès
    setFlash('success', "Un lien de réinitialisation a été envoyé à {$email}.");
  } catch (\Exception $e) {
    // Capture et affiche toute erreur d'envoi
    setFlash('error', "Échec de l'envoi : " . $e->getMessage());
  }

  // Fin du POST, redirection 
  header('Location: mot-de-passe-oublie.php');
  exit;
}

// GET : on récupère les flashs pour affichage côté vue
$erreur  = getFlash('error');
$message = getFlash('success');

// Ferme la connexion à la BDD
$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">

<head>
  <!-- Déclaration de l’encodage et du responsive design -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Description de la page pour les moteurs de recherche -->
  <meta name="description" content="Réinitialisez votre mot de passe sur Mon site de recettes de cuisine collaboratif : recevez un lien par email pour sécuriser et retrouver l’accès à votre compte." />
  <title>Mot de passe oublié</title>
  <!-- Import du logo pour l'onglet -->
  <link rel="icon" href="../assets/images/logo/favicon.ico" type="image/x-icon">
  <!-- Inclusion des feuilles de style externalisées -->
  <?php require_once __DIR__ . '/../includes/css.php'; ?>
</head>

<body class="d-flex flex-column min-vh-100 page-mot-de-passe-oublié">
  <!-- En-tête transparent avec titre centré -->
  <header class="py-3 header-transparent" role="banner">
    <div class="container">
      <h1 class="text-center">Mot de passe oublié</h1>
    </div>
  </header>
  <!-- Inclusion de la nav externalisée -->
  <?php require_once __DIR__ . '/../includes/nav.php'; ?>
  <!-- Conteneur principal -->
  <main class="flex-fill" role="main">
    <div class="container-sm mt-5 mb-5 mx-auto bloc-opaque p-4 rounded-4" style="max-width:420px;">
      <!-- Titre de la page -->
      <h2 class="mb-3 text-center">Mot de passe oublié</h2>

      <!-- Affichage du message d'erreur, si défini -->
      <?php if ($erreur): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>

        <!-- Sinon, affichage du message de succès, si défini -->
      <?php elseif ($message): ?>
        <div class="alert alert-success text-center"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>

      <!-- Afficher le formulaire uniquement s'il n'y a pas de message de succès -->
      <?php if (empty($message)): ?>
        <form method="POST" class="mt-4">
          <!-- Champ email avec validation HTML5 et pré‐remplissage sécurisé -->
          <div class="mb-3">
            <label for="email" class="form-label">Adresse email :</label>
            <input
              type="email"
              id="email"
              name="email"
              class="form-control"
              required
              value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>
          <!-- Bouton de soumission -->
          <button class="btn btn-primary w-100">
            Recevoir le lien de réinitialisation
          </button>
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