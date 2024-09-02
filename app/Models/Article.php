<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    // Définir la table associée au modèle
    protected $table = 'articles';

    // Définir les attributs qui peuvent être remplis en masse
    protected $fillable = [
        'libelle',
        'prix',
        'quantite_de_stock',
    ];
    // Définir les attributs qui devraient être masqués lors de la serialization
    protected $hidden = [
        //  'password',
        'created_at',
        'updated_at',
    ];

    // Définir les attributs qui ne devraient pas être assignés en masse
    protected $guarded = [];
}
