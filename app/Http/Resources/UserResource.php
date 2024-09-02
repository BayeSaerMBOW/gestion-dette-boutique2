<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
           
            'nom' => $this->user["nom"],
            "prenom" => $this->user["prenom"],
            "login" => $this->user["login"],
            "password" => $this->user["password"],
            "role" => $this->user["role"],
           
        ];
    }
}
