<?php

namespace Services;

/* Validation des données d’une recette provenant d’un formulaire (POST + upload d’image). Retourne une liste d’erreurs si des champs obligatoires sont manquants ou si l’image n’a pas une extension autorisée. */

class RecipeValidator
{
    // Valide les champs reçus et l’éventuel fichier image.
    public function validate(array $post, array $files): array
    {
        // Initialisation du tableau d’erreurs
        $errors = [];

        // Vérification du titre : doit être non vide après trim()
        $titre = trim($post['titre'] ?? '');
        if (empty($titre)) {
            $errors[] = 'Le titre est obligatoire.';
        }

        // Vérification du temps de préparation : doit être un entier > 0
        $temps = intval($post['temps_preparation'] ?? 0);
        if ($temps <= 0) {
            $errors[] = 'Le temps doit être supérieur à zéro.';
        }

        // Si une image est fournie, vérifie l’extension
        if (!empty($files['image']['name'])) {
            // Extensions autorisées
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            // Extraction de l’extension (en minuscules)
            $ext = strtolower(
                pathinfo($files['image']['name'], PATHINFO_EXTENSION)
            );

            // Contrôle de l’extension dans la liste autorisée
            if (!in_array($ext, $allowed, true)) {
                $errors[] = "Format d'image non autorisé.";
            }
        }

        // Retourne la liste des erreurs : vide si toutes les validations sont OK
        return $errors;
    }
}
