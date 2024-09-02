<?php

namespace App\Http\Controllers;

use App\Enums\StateEnum;
use App\Http\Requests\StoreClientRequest;
use App\Http\Resources\ClientCollection;
use App\Http\Resources\ClientResource;
use Illuminate\Support\Facades\Hash;
use App\Models\Client;
use App\Models\User;
use App\Traits\RestResponseTrait;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA; // Assurez-vous que cette ligne est présente en haut du fichier.

use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;


/**
 * @OA\Info(
 *     title="Client API",
 *     version="1.0.0",
 *     description="API pour la gestion des clients et des utilisateurs.",
 *     @OA\Contact(
 *         email="support@example.com"
 *     )
 * )
 */

/**
 * @OA\Tag(
 *     name="Clients",
 *     description="Opérations concernant les clients."
 * )
 */

/**
 * @OA\Tag(
 *     name="Users",
 *     description="Opérations concernant les utilisateurs."
 * )
 */

/**
 * @OA\Tag(
 *     name="Auth",
 *     description="Opérations liées à l'authentification."
 * )
 */
/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nom", type="string", example="John"),
 *     @OA\Property(property="prenom", type="string", example="Doe"),
 *     @OA\Property(property="login", type="string", example="johndoe"),
 *     @OA\Property(property="role_id", type="integer", example=1)
 * )
 */

class ClientController extends Controller
{
    use RestResponseTrait;
   /**
     * @OA\Get(
     *     path="/api/clients",
     *     summary="Liste des clients",
     *     description="Récupère la liste des clients, avec option pour inclure des relations.",
     *     tags={"Clients"},
     *     @OA\Parameter(
     *         name="include",
     *         in="query",
     *         description="Relations à inclure",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des clients récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/ClientResource")
     *             )
     *         )
     *     )
     * )
     */
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //  return Client::whereNotNull('user_id')->get();
        $include = $request->has('include') ?  [$request->input('include')] : [];

        $data = Client::with($include)->whereNotNull('user_id')->get();
        //return  response()->json(['data' => $data]);
        //  return  ClientResource::collection($data);
        // return new ClientCollection($data);
        $clients = QueryBuilder::for(Client::class)
            ->allowedFilters(['surname'])
            ->allowedIncludes(['user'])
            ->get();
        return new ClientCollection($clients);
    }
    /**
     * @OA\Post(
     *     path="/api/clients",
     *     summary="Créer un nouveau client",
     *     description="Stocke un nouveau client avec ou sans utilisateur associé.",
     *     tags={"Clients"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="surname", type="string", example="Doe"),
     *             @OA\Property(property="adresse", type="string", example="123 Main St"),
     *             @OA\Property(property="telephone", type="string", example="0123456789"),
     *             @OA\Property(property="photo", type="string", format="binary"),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="nom", type="string", example="John"),
     *                 @OA\Property(property="prenom", type="string", example="Doe"),
     *                 @OA\Property(property="login", type="string", example="johndoe"),
     *                 @OA\Property(property="password", type="string", format="password", example="password123"),
     *                 @OA\Property(property="role", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Client créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/ClientResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur lors de la création du client",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur interne du serveur")
     *         )
     *     )
     * )
     */

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClientRequest $request)
    {
        try {
            DB::beginTransaction();
            $clientRequest =  $request->only('surname', 'adresse', 'telephone');
            if ($request->hasFile('photo')) {
                $path = $request->file('photo')->store('client_photos', 'public');
                $clientRequest['photo'] = $path;
            }
            /*   dd($clientRequest); */
            $client = Client::create($clientRequest);
            if ($request->has('user')) {
                $user = User::create([
                    'nom' => $request->input('user.nom'),
                    'prenom' => $request->input('user.prenom'),
                    'login' => $request->input('user.login'),
                    'password' => $request->input('user.password'),
                    'role_id' => $request->input('user.role'),  // Correction du champ
                    'etat' => true  // Par exemple, ajouter un état par défaut
                ]);

            }
            $user->client()->save($client);
            DB::commit();
            return $this->sendResponse(new ClientResource($client), StateEnum::SUCCESS, 'ghzfjzf');
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendResponse(new ClientResource($e->getMessage()), StateEnum::ECHEC, 'error ', 500);
        }
    }


/**
 * @OA\Get(
 *     path="/api/users",
 *     summary="Liste des utilisateurs",
 *     description="Récupère la liste de tous les utilisateurs.",
 *     tags={"Users"},
 *     @OA\Response(
 *         response=200,
 *         description="Liste des utilisateurs",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/User")
 *         )
 *     )
 * )
 */
public function users()
{
    return User::all();
}

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }
     /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="Authentifie un utilisateur",
     *     description="Authentifie un utilisateur avec ses informations d'identification et retourne un token.",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="login", type="string", example="user@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="access_token", type="string", example="access_token_here"),
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(property="user", ref="#/components/schemas/ClientResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Échec de la connexion",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Identifiants invalides")
     *         )
     *     )
     * )
     */
    /**
     * Authentifie un utilisateur et génère un token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('login', $request->login)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->sendResponse(
                null,
                \App\Enums\StateEnum::ECHEC,
                'Identifiants invalides',
                401
            );
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        $client = $user->client;

        return $this->sendResponse(
            [
                'user' => new ClientResource($client),
                'access_token' => $token,
                'token_type' => 'Bearer',
            ],
            \App\Enums\StateEnum::SUCCESS,
            'Connexion réussie',
            200
        );
    }
     /**
     * @OA\Get(
     *     path="/api/users-by-etat",
     *     summary="Récupère les utilisateurs par état",
     *     description="Récupère les utilisateurs en fonction de l'état de leur compte.",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         name="etat",
     *         in="query",
     *         description="État du compte (true ou false)",
     *         required=true,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Utilisateurs récupérés avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/User")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Paramètre d'état invalide",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Paramètre 'etat' invalide")
     *         )
     *     )
     * )
     */
    /**
 * Récupère les utilisateurs en fonction de l'état de leur compte.
 *
 * @param  Request  $request
 * @return JsonResponse
 */
public function getUsersByEtat(Request $request): JsonResponse
{
    // Valider le paramètre 'etat' dans la requête
    $request->validate([
        'etat' => 'required|boolean',  // S'assure que 'etat' est un booléen
    ]);

    // Récupérer l'état à partir des paramètres du corps de la requête
    $etat = $request->input('etat');

    // Rechercher les utilisateurs dont l'état correspond à celui spécifié
    $users = User::where('etat', $etat)->get();

    // Retourner la réponse JSON avec les utilisateurs trouvés
    return $this->sendResponse(
        $users,
        StateEnum::SUCCESS,
        'Utilisateurs récupérés avec succès',
        200
    );
}
 /**
     * @OA\Post(
     *     path="/api/clients/filter-by-telephone",
     *     summary="Filtre les clients par numéro de téléphone",
     *     description="Récupère un client en fonction de son numéro de téléphone.",
     *     tags={"Clients"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="telephone", type="string", example="0123456789")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Client récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/ClientResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Aucun client trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Aucun client trouvé avec ce numéro de téléphone")
     *         )
     *     )
     * )
     */
// public function filterByTelephone(Request $request)
// {
//     $request->validate([
//         'telephone' => 'required|string|size:9',
//     ]);

//     $telephone = $request->input('telephone');

//     $client = Client::where('telephone', $telephone)->first();

//     dd($client); // Ajoutez cette ligne pour déboguer

//     if ($client) {
//         return $this->sendResponse(
//             new ClientResource($client),
//             StateEnum::SUCCESS,
//             'Client récupéré avec succès',
//             200
//         );
//     } else {
//         return $this->sendResponse(
//             null,
//             StateEnum::ECHEC,
//             'Aucun client trouvé avec ce numéro de téléphone',
//             404
//         );
//     }
// }



}
