<?php

// Démarre la session ou reprend celle déjà existante.
session_start();

// Si non connecté, redirige vers la page de connexion.
if (empty($_SESSION['utilisateur']['id'])) {
  header('Location: connexion.php');
  exit;
}
// Récupère l’ID de l’utilisateur connecté.
$userId = $_SESSION['utilisateur']['id'];

// Récupérer l’ID de la recette
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
  // Si l’ID est invalide ou absent, redirige vers la page de compte.
  header('Location: compte.php');
  exit;
}

// Connexion à la BDD
$conn = new mysqli("localhost", "root", "3BienN#EMuwDu!k", "recettes_collaboratif");
if ($conn->connect_error) {
  die("Erreur BDD : " . $conn->connect_error);
}

// Vérifie que la recette existe et que l’utilisateur connecté en est l’auteur.
// On récupère l’URL de l’image pour potentiellement la supprimer.
$stmt = $conn->prepare("SELECT image_url FROM recettes WHERE id = ? AND auteur_id = ?");
$stmt->bind_param("ii", $id, $userId);
$stmt->execute();
$recette = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Si la recette n’existe pas ou n’appartient pas à l’utilisateur, on stoppe.
if (!$recette) {
  die("Recette introuvable ou accès non autorisé.");
}

// Supprime le fichier image associé à la recette, si un chemin est présent.
if (!empty($recette['image_url'])) {
  $imagePath = __DIR__ . '/../' . $recette['image_url'];
  if (file_exists($imagePath)) {
    unlink($imagePath);
  }
}

// Prépare et exécute la requête de suppression de la recette 
$del = $conn->prepare("DELETE FROM recettes WHERE id = ? AND auteur_id = ?");
$del->bind_param("ii", $id, $userId);
$del->execute();
$del->close();
$conn->close();

// Redirection vers la page compte
header("Location: compte.php");
exit;
