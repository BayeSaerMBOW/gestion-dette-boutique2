<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use App\Models\Article;
use App\Http\Requests\UpdateStockRequest; // Assurez-vous d'avoir créé cette requête de validation
use App\Http\Requests\StoreArticleRequest;
use Illuminate\Support\Facades\DB;
use App\Traits\RestResponseTrait;
use Illuminate\Support\Facades\Gate;
// Importez le trait
/**
 * @OA\Info(
 *     title="API de gestion des articles",
 *     version="1.0",
 *     description="Documentation de l'API pour la gestion des articles."
 * )
 */


class ArticleController extends Controller
{
    use RestResponseTrait; // Utilisez le trait


    /**
     * @OA\Post(
     *     path="/api/articles",
     *     summary="Stocke un nouvel article",
     *     description="Crée un nouvel article avec les données fournies.",
     *     tags={"Articles"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="libelle", type="string", example="Article Example"),
     *             @OA\Property(property="prix", type="number", format="float", example=99.99),
     *             @OA\Property(property="quantite_de_stock", type="integer", example=10)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Article créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="libelle", type="string", example="Article Example"),
     *             @OA\Property(property="prix", type="number", format="float", example=99.99),
     *             @OA\Property(property="quantite_de_stock", type="integer", example=10)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Requête invalide"
     *     )
     * )
     */
    public function store(StoreArticleRequest $request)
    {
        // Créer un nouvel article avec les données validées

        $article = Article::create([
            'libelle' => $request->input('libelle'),
            'prix' => $request->input('prix'),
            'quantite_de_stock' => $request->input('quantite_de_stock'),
        ]);

        // Utiliser le trait pour retourner une réponse JSON avec les détails de l'article créé
        return $this->sendResponse(
            $article,
            \App\Enums\StateEnum::SUCCESS,
            'Article créé avec succès !',
            201
        );
    }
    public function __construct()
    {
        $this->authorizeResource(Article::class, 'article');
    }


    /**
     * @OA\Delete(
     *     path="/api/articles/{id}",
     *     summary="Supprime un article",
     *     description="Supprime l'article correspondant à l'ID fourni.",
     *     tags={"Articles"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Article supprimé avec succès"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Article non trouvé"
     *     )
     * )
     */
    public function delete($id)
    {
        // Rechercher l'article par ID
        $article = Article::find($id);

        // Vérifier si l'article existe
        if (!$article) {
            return $this->sendResponse(
                null,
                \App\Enums\StateEnum::ECHEC,
                'Article non trouvé',
                404
            );
        }

        // Supprimer l'article
        $article->delete();

        // Retourner une réponse de succès
        return $this->sendResponse(
            null,
            \App\Enums\StateEnum::SUCCESS,
            'Article supprimé avec succès !',
            200
        );
    }


    /**
     * @OA\Patch(
     *     path="/api/articles/update-stock",
     *     summary="Met à jour le stock des articles",
     *     description="Met à jour les quantités de stock pour plusieurs articles.",
     *     tags={"Articles"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="updates",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="quantite", type="integer", example=5)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mises à jour de stock réussies",
     *         @OA\JsonContent(
     *             @OA\Property(property="successful_updates", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="old_quantity", type="integer"),
     *                 @OA\Property(property="new_quantity", type="integer"),
     *                 @OA\Property(property="added_quantity", type="integer")
     *             )),
     *             @OA\Property(property="failed_updates", type="array", @OA\Items(
     *                 @OA\Property(property="article", type="object"),
     *                 @OA\Property(property="error", type="string")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur globale lors de la mise à jour des stocks"
     *     )
     * )
     */
    public function updateStock(UpdateStockRequest $request)
    {
        if (! Gate::allows('update', Article::class)) {
            return $this->sendResponse(
                null,
                \App\Enums\StateEnum::ECHEC,
                "Vous n'avez pas le droit d'accès à ces fonctionnalités",
                403
            );
        }
        // Récupère les données validées à partir de la requête, spécifiquement les mises à jour de stock.
        $updates = $request->validated()['updates'];

        // Tableaux pour stocker les mises à jour réussies et échouées.
        $successfulUpdates = [];
        $failedUpdates = [];

        // Démarre une transaction de base de données pour s'assurer que toutes les opérations sont atomiques.
        DB::beginTransaction();

        try {
            // Parcourt chaque mise à jour de stock.
            foreach ($updates as $update) {
                try {
                    // Vérifie si la quantité saisie est inférieure à 0
                    if ($update['quantite'] < 0) {
                        throw new Exception('La quantité ne peut pas être inférieure à 0.');
                    }
                    // Tente de trouver l'article à mettre à jour par son ID.
                    $article = Article::findOrFail($update['id']);

                    // Sauvegarde l'ancienne quantité de stock pour référence.
                    $oldQuantity = $article->quantite_de_stock;

                    // Ajoute la quantité spécifiée au stock actuel.
                    $article->quantite_de_stock += $update['quantite'];

                    // Sauvegarde l'article mis à jour dans la base de données.
                    $article->save();

                    // Ajoute les détails de la mise à jour réussie au tableau des mises à jour réussies.
                    $successfulUpdates[] = [
                        'id' => $article->id,
                        'old_quantity' => $oldQuantity,
                        'new_quantity' => $article->quantite_de_stock,
                        'added_quantity' => $update['quantite']
                    ];
                } catch (Exception $e) {
                    // Si une mise à jour échoue, ajoute les détails de l'échec au tableau des mises à jour échouées.
                    $failedUpdates[] = [
                        'article' => $update,
                        'error' => $e->getMessage()
                    ];
                }
            }

            // Si toutes les mises à jour sont réussies, on valide la transaction.
            DB::commit();

            // Détermine le statut global en fonction des résultats des mises à jour.
            $status = empty($failedUpdates) ? \App\Enums\StateEnum::SUCCESS : \App\Enums\StateEnum::ECHEC;

            // Prépare le message de réponse en fonction du succès ou de l'échec des mises à jour.
            $message = empty($failedUpdates)
                ? 'Tous les stocks ont été mis à jour avec succès !'
                : 'Certaines mises à jour de stock ont échoué. Veuillez vérifier les détails.';

            // Retourne une réponse HTTP avec les détails des mises à jour réussies et échouées.
            return $this->sendResponse(
                [
                    'successful_updates' => $successfulUpdates,
                    'failed_updates' => $failedUpdates
                ],
                $status,
                $message,
                200
            );
        } catch (Exception $e) {
            // En cas d'erreur globale, annule toutes les modifications dans la transaction.
            DB::rollBack();

            // Retourne une réponse HTTP avec un message d'erreur.
            return $this->sendResponse(
                null,
                \App\Enums\StateEnum::ECHEC,
                'Erreur globale lors de la mise à jour des stocks : ' . $e->getMessage(),
                500
            );
        }
    }

    
    
    public function get($id)
    {
        try {
            // Rechercher l'article par ID
            $article = Article::find($id);

            // Vérifier si l'article existe
            if (!$article) {
                return $this->sendResponse(
                    null,
                    \App\Enums\StateEnum::ECHEC,
                    'Article non trouvé',
                    404
                );
            }

            // Retourner l'article trouvé
            return $this->sendResponse(
                $article,
                \App\Enums\StateEnum::SUCCESS,
                'Article récupéré avec succès !',
                200
            );
        } catch (Exception $e) {
            // Retourner une réponse en cas d'erreur
            return $this->sendResponse(
                null,
                \App\Enums\StateEnum::ECHEC,
                'Erreur lors de la récupération de l\'article : ' . $e->getMessage(),
                500
            );
        }
    }
    /**
     * @OA\Patch(
     *     path="/api/articles/{id}/quantity",
     *     summary="Met à jour la quantité de stock d'un article",
     *     description="Met à jour la quantité de stock d'un article spécifique.",
     *     tags={"Articles"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="quantite", type="integer", example=20)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Quantité de stock mise à jour avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="old_quantity", type="integer"),
     *             @OA\Property(property="new_quantity", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Article non trouvé"
     *     )
     * )
     */
    /**
     * Met à jour la quantité de stock d'un article par son ID.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateQuantity(Request $request, $id)
    {

        if (! Gate::allows('update', Article::class)) {
            return $this->sendResponse(
                null,
                \App\Enums\StateEnum::ECHEC,
                "Vous n'avez pas le droit d'accès à ces fonctionnalités",
                403
            );
        }
        $request->validate([
            'quantite' => 'required|integer|min:0', // Valider la quantité pour s'assurer qu'elle est un entier positif
        ]);

        try {
            // Rechercher l'article par ID
            $article = Article::find($id);

            // Vérifier si l'article existe
            if (!$article) {
                return $this->sendResponse(
                    null,
                    \App\Enums\StateEnum::ECHEC,
                    'Article non trouvé',
                    404
                );
            }

            // Mettre à jour la quantité de stock de l'article
            $oldQuantity = $article->quantite_de_stock;
            $article->quantite_de_stock = $request->input('quantite');
            $article->save();

            // Retourner une réponse de succès
            return $this->sendResponse(
                [
                    'id' => $article->id,
                    'old_quantity' => $oldQuantity,
                    'new_quantity' => $article->quantite_de_stock,
                ],
                \App\Enums\StateEnum::SUCCESS,
                'Quantité de stock mise à jour avec succès !',
                200
            );
        } catch (Exception $e) {
            // Retourner une réponse en cas d'erreur
            return $this->sendResponse(
                null,
                \App\Enums\StateEnum::ECHEC,
                'Erreur lors de la mise à jour de la quantité de stock : ' . $e->getMessage(),
                500
            );
        }
    }
    /**
     * @OA\Get(
     *     path="/api/articles/available",
     *     summary="Récupère les articles en fonction de leur disponibilité",
     *     description="Retourne les articles qui sont disponibles ou non selon le paramètre 'disponible'.",
     *     tags={"Articles"},
     *     @OA\Parameter(
     *         name="disponible",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string", enum={"oui", "non"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Articles récupérés avec succès",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="libelle", type="string"),
     *                 @OA\Property(property="prix", type="number", format="float"),
     *                 @OA\Property(property="quantite_de_stock", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Paramètre invalide"
     *     )
     * )
     */

    /**
     * Récupère les articles en fonction de leur disponibilité.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailable(Request $request)
    {
        $this->authorize('viewAny', Article::class);
        $disponible = $request->query('disponible');

        if (!in_array($disponible, ['oui', 'non'])) {
            return $this->sendResponse(
                null,
                \App\Enums\StateEnum::ECHEC,
                'Le paramètre "disponible" doit être "oui" ou "non".',
                400
            );
        }

        try {
            $query = Article::query();

            if ($disponible === 'oui') {
                $query->where('quantite_de_stock', '>', 0);
            } else {
                $query->where('quantite_de_stock', '=', 0);
            }

            $articles = $query->get();

            return $this->sendResponse(
                $articles,
                \App\Enums\StateEnum::SUCCESS,
                'Articles récupérés avec succès.',
                200
            );
        } catch (Exception $e) {
            return $this->sendResponse(
                null,
                \App\Enums\StateEnum::ECHEC,
                'Erreur lors de la récupération des articles : ' . $e->getMessage(),
                500
            );
        }
    }
}
