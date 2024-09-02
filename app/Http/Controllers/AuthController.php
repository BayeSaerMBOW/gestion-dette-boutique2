<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Laravel\Passport\TokenRepository;

class AuthController extends Controller
{
     /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="Authentifie un utilisateur",
     *     description="Connecte un utilisateur avec ses informations d'identification et retourne un token d'accès et un refresh token.",
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
     *         description="Authentification réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="token", type="string", example="access_token_here"),
     *             @OA\Property(property="refresh_token", type="string", example="refresh_token_here"),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="user@example.com")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Échec de l'authentification",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to authenticate.")
     *         )
     *     )
     * )
     */
    public function login(Request $request)
    {
        // Récupère les informations d'identification (login et password) du requête
        $credentials = $request->only('login', 'password');
    
        // Essaye de connecter l'utilisateur avec les informations d'identification fournies
        if (Auth::attempt($credentials)) {
            // Si l'authentification réussit, récupère l'utilisateur actuellement authentifié
            $user = User::find(Auth::user()->id);
    
            // Crée un token d'accès pour l'utilisateur
            $token = $user->createToken('appToken')->accessToken;
    
            // Génère un refresh token pour l'utilisateur
            $refreshToken = $this->createRefreshToken($user);
    
            // Retourne une réponse JSON indiquant le succès de l'authentification
            // Inclut le token d'accès, le refresh token et les informations de l'utilisateur dans la réponse
            return response()->json([
                'success' => true,
                'token' => $token, // Token d'accès
                'refresh_token' => $refreshToken, // Refresh token
                'user' => $user, // Informations sur l'utilisateur
            ], 200);
        } else {
            // Si l'authentification échoue, retourne une réponse JSON indiquant l'échec
            return response()->json([
                'success' => false,
                'message' => 'Failed to authenticate.', // Message d'erreur
            ], 401);
        }
    }
    /**
     * @OA\Post(
     *     path="/api/refresh-token",
     *     summary="Crée un refresh token",
     *     description="Génère un refresh token pour l'utilisateur.",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="login", type="string", example="user@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Refresh token créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="refresh_token", type="string", example="new_refresh_token_here")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Échec de la création du refresh token",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to create refresh token.")
     *         )
     *     )
     * )
     */
    protected function createRefreshToken($user)
    {
        // Obtient le TokenRepository à partir du service container
        $tokenRepository = app(TokenRepository::class);
    
        // Crée un token de rafraîchissement pour l'utilisateur
        $token = $user->createToken('refreshToken')->accessToken;
    
        // Sauvegarder le refresh token si nécessaire (commenté ici car non utilisé)
        // $tokenRepository->save($token);
    
        // Retourne le refresh token
        return $token;
    }
    
    
}
