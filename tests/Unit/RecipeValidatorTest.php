<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
// On importe le service RecipeValidator à tester
use Services\RecipeValidator;

class RecipeValidatorTest extends TestCase
{
    /* Instance partagée de RecipeValidator pour chaque test. Permet de réutiliser le même validateur tout en garantissant un état propre grâce à setUp(). */
    private RecipeValidator $validator;

    // setUp() est appelé avant chaque méthode de test. Ici, on instancie un nouveau RecipeValidator.
     
    protected function setUp(): void
    {
        $this->validator = new RecipeValidator();
    }

    // 1-testMissingTitre(). Vérifie que la validation signale une erreur quand le titre est vide.
     
    public function testMissingTitre(): void
    {
        // Simule un formulaire POST sans titre valide
        $post = [
            'titre' => '',
            'temps_preparation' => 10
        ];
        // Simule l’absence de fichier uploadé
        $files = [
            'image' => [
                'name'  => '',
                'error' => UPLOAD_ERR_NO_FILE
            ]
        ];

        // On exécute la validation
        $errors = $this->validator->validate($post, $files);

        // On s’attend à trouver l’erreur sur le titre manquant
        $this->assertContains(
            'Le titre est obligatoire.',
            $errors,
            'La validation doit renvoyer une erreur lorsque le titre est vide'
        );
    }

    /* 2-testInvalidImageExtension(). Vérifie que la validation signale une erreur quand l’extension de l’image n’est pas autorisée. */
    public function testInvalidImageExtension(): void
    {
        // Simule un POST avec un titre et un temps valides
        $post = [
            'titre' => 'OK',
            'temps_preparation' => 5
        ];
        // Simule un fichier avec extension non autorisée (bmp)
        $files = [
            'image' => [
                'name'  => 'foo.bmp',
                'error' => UPLOAD_ERR_OK
            ]
        ];

        // Exécute la validation
        $errors = $this->validator->validate($post, $files);

        // Vérifie que l’erreur de format d’image apparaît
        $this->assertContains(
            "Format d'image non autorisé.",
            $errors,
            'La validation doit rejeter les extensions non listées'
        );
    }
}
// Pour exécuter ce test, utilisez la commande suivante dans le terminal :
// vendor/bin/phpunit tests/Unit/RecipeValidatorTest.php