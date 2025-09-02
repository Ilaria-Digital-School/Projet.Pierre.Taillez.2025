// Dark-mode
// Attendre que le DOM soit entièrement chargé avant d’exécuter le script
document.addEventListener('DOMContentLoaded', () => {
  // Récupère le bouton qui permet de basculer entre les modes
  const toggleButton = document.getElementById('toggle-mode');
  // Récupère l’élément <body> pour y appliquer la classe dark-mode
  const body = document.body;
  // Si le bouton n’existe pas sur la page, on arrête tout
  if (!toggleButton) return;

  // Lit le mode stocké dans le localStorage ('dark' ou 'light')
  const storedMode = localStorage.getItem('mode');
  // Détermine si on doit activer le mode sombre
  const isDark = storedMode === 'dark';

  // Applique ou retire la classe 'dark-mode' selon la valeur récupérée
  body.classList.toggle('dark-mode', isDark);

  // Met à jour l’attribut aria-checked pour l’accessibilité
  toggleButton.setAttribute('aria-checked', String(isDark));
  // Change le texte du bouton en fonction du mode actuel
  toggleButton.textContent = isDark ? 'Mode clair' : 'Mode sombre';

  // Écoute le clic sur le bouton pour inverser le mode
  toggleButton.addEventListener('click', () => {
    // Toggle et récupère l’état après bascule (true si sombre)
    const nowDark = body.classList.toggle('dark-mode');

    // Enregistre le nouveau mode dans le localStorage
    localStorage.setItem('mode', nowDark ? 'dark' : 'light');

    // Met à jour aria-checked pour refléter le nouvel état
    toggleButton.setAttribute('aria-checked', String(nowDark));
    // Met à jour le texte du bouton après la bascule
    toggleButton.textContent = nowDark ? 'Mode clair' : 'Mode sombre';
  });
});

// Carousel 
// Attendre que le DOM soit complètement chargé avant d’exécuter le script
document.addEventListener('DOMContentLoaded', () => {
  // Récupère l’élément principal du carousel par son ID
  const carousel = document.getElementById('carouselRecettes');
  // Si l’élément n’existe pas, on sort (pas de carousel à gérer)
  if (!carousel) return;

  // Récupère toutes les diapositives à l’intérieur du carousel
  const slides = carousel.querySelectorAll('.carousel-item');
  // Sélectionne le bouton « précédent »
  const prevBtn = carousel.querySelector('[data-slide="prev"]');
  // Sélectionne le bouton « suivant »
  const nextBtn = carousel.querySelector('[data-slide="next"]');

  // Durée entre deux changements automatiques (en millisecondes)
  const interval = 3000;
  // Index de la diapositive actuellement affichée
  let current = 0;
  // Variable pour stocker l’identifiant du timer
  let timer;

  /**
   * Affiche la diapositive d’index donné
   * @param {number} index — nouvel index à afficher
   */
  function show(index) {
    slides.forEach((slide, i) => {
      // Active la diapositive dont l’index correspond, désactive les autres
      slide.classList.toggle('active', i === index);
    });
  }

  /**
   * Passe à la diapositive suivante (et boucle si nécessaire)
   */
  function nextSlide() {
    current = (current + 1) % slides.length;
    show(current);
  }

  /**
   * Revient à la diapositive précédente (et boucle si nécessaire)
   */
  function prevSlide() {
    current = (current - 1 + slides.length) % slides.length;
    show(current);
  }

  /**
   * Réinitialise le timer d’avancement automatique
   * on l’arrête puis on le redémarre
   */
  function resetTimer() {
    clearInterval(timer);
    timer = setInterval(nextSlide, interval);
  }

  // Lorsqu’on clique sur « Suivant », on avance et on réarme le timer
  nextBtn.addEventListener('click', () => {
    nextSlide();
    resetTimer();
  });

  // Lorsqu’on clique sur « Précédent », on recule et on réarme le timer
  prevBtn.addEventListener('click', () => {
    prevSlide();
    resetTimer();
  });

  // Initialisation : afficher la première diapositive
  show(current);
  // Démarrage du défilement automatique
  timer = setInterval(nextSlide, interval);
});