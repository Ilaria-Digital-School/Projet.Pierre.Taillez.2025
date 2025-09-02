<?php

// Démarre la session ou reprend celle déjà existante.
session_start();

// Si l’utilisateur n’est pas connecté (pas d’ID en session), on le redirige
if (empty($_SESSION['utilisateur']['id'])) {
  header('Location: connexion.php');
  exit;
}
// Récupération de l’ID de l’utilisateur connecté
$userId = $_SESSION['utilisateur']['id'];

// Récupération l’ID de la recette à supprimer
$recetteId = isset($_GET['id']) ? intval($_GET['id']) : 0;
// Si l’ID n’est pas fourni ou invalide, on retourne à la liste des favoris
if ($recetteId <= 0) {
  header('Location: favoris.php');
  exit;
}

// Connexion à la BDD
$conn = new mysqli("localhost", "root", "3BienN#EMuwDu!k", "recettes_collaboratif");
if ($conn->connect_error) {
  // Arrêt du script en cas d’erreur de connexion
  die("Erreur BDD : " . $conn->connect_error);
}

// Préparation de la requête DELETE pour supprimer le favori
$stmt = $conn->prepare("
  DELETE FROM favoris
  WHERE user_id = ? AND recette_id = ?
");
$stmt->bind_param("ii", $userId, $recetteId);
// Exécution de la requête
$stmt->execute();
// Fermeture du statement 
$stmt->close();
// Déconnexion de la BDD
$conn->close();
// Redirection vers la page favoris
header('Location: favoris.php');
exit;
?>