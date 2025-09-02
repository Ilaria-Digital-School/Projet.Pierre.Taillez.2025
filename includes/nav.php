<!-- Barre de navigation principale -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark" role="navigation" aria-label="Menu principal">
    <div class="container-fluid">
      <!-- Bouton pour basculer le menu sur mobile -->
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navRecettes" aria-label="Ouvrir le menu">
        <span class="navbar-toggler-icon"></span>
      </button>
      <!-- Liens de navigation masqués par défaut sur petit écran -->
      <div class="collapse navbar-collapse" id="navRecettes">
        <ul class="navbar-nav me-auto fs-4">
          <!-- Chaque item vérifie si la page courante correspond pour activer le style -->
          <li class="nav-item">
            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active text-white' : '' ?>" href="index.php">Accueil</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'recettes.php' ? 'active text-white' : '' ?>" href="recettes.php">Recettes</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'recherche.php' ? 'active text-white' : '' ?>" href="recherche.php">Recherche</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'ajouter-recette.php' ? 'active text-white' : '' ?>" href="ajouter-recette.php">Ajouter une recette</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'favoris.php' ? 'active text-white' : '' ?>" href="favoris.php">Mes recettes favorites</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'compte.php' ? 'active text-white' : '' ?>" href="compte.php">Mon compte</a>
          </li>
          <!-- Affichage du lien Admin si l’utilisateur est admin -->
          <?php if (isAdmin()): ?>
            <li class="nav-item">
              <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'admin.php' ? 'active text-white' : '' ?>"
                href="admin.php">
                Admin
              </a>
            </li>
          <?php endif; ?>
        </ul>
        <!-- Bouton de déconnexion ou liens vers connexion/inscription selon le statut -->
        <?php if ($isLoggedIn): ?>
          <a href="../controllers/logout.php" class="btn btn-outline-light">Déconnexion</a>
        <?php else: ?>
          <a href="connexion.php" class="btn btn-outline-light me-2">Connexion</a>
          <a href="inscription.php" class="btn btn-light">Inscription</a>
        <?php endif; ?>
        <!-- Toggle pour changer entre mode clair et mode sombre -->
        <button id="toggle-mode" class="btn btn-secondary ms-auto" role="switch" aria-label="Basculer entre le mode clair et le mode sombre" aria-checked="false">Mode sombre</button>
      </div>
    </div>
  </nav>