<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Aquí podríamos chequear permisos de usuario en el futuro ****
        // De momento devolvemos true para permitir todo
        return true;
    }

    public function rules(): array
    {
        return [
            'message_id' => 'required|unique:emails',
            'from_email' => 'required|email',
            'subject'    => 'nullable|string',
            'body_text'  => 'nullable|string',
            'received_at'=> 'nullable|date',
        ];
    }
}

