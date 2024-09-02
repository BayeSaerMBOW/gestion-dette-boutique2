<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ArticlePolicy
{
    /**
     * Détermine si l'utilisateur peut voir tous les modèles.
     */
    public function viewAny(User $user): bool
    {
        return $user->role_id === 1 || $user->role_id === 2; // BOUTIQUIER ou ADMIN
    }

    /**
     * Détermine si l'utilisateur peut voir le modèle.
     */
    public function view(User $user, Article $article): bool
    {
        return $user->role_id === 1 || $user->role_id === 2; // BOUTIQUIER ou ADMIN
    }

    /**
     * Détermine si l'utilisateur peut créer des modèles.
     */
    public function create(User $user): bool
    {
        return $user->role_id === 1 || $user->role_id === 2; // BOUTIQUIER ou ADMIN
    }

    /**
     * Détermine si l'utilisateur peut mettre à jour le modèle.
     */
    public function update(User $user, Article $article = null): bool
    {
        return $user->role_id === 1 || $user->role_id === 2; // BOUTIQUIER ou ADMIN
    }

    /**
     * Détermine si l'utilisateur peut supprimer le modèle.
     */
    public function delete(User $user, Article $article): bool
    {
        return $user->role_id === 1 || $user->role_id === 2; // BOUTIQUIER ou ADMIN
    }

    /**
     * Détermine si l'utilisateur peut restaurer le modèle.
     */
    public function restore(User $user, Article $article): bool
    {
        return $user->role === 2; // ADMIN seulement
    }

    /**
     * Détermine si l'utilisateur peut supprimer définitivement le modèle.
     */
    public function forceDelete(User $user, Article $article): bool
    {
        return $user->role === 2; // ADMIN seulement
    }
}
