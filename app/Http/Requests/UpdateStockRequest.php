<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStockRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Ajustez selon vos besoins d'autorisation
    }

    public function rules()
    {
        return [
            'updates' => 'required|array',
          
        ];
    }
}