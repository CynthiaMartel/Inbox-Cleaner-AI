<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Email;
use Illuminate\Http\Request;

class SecurityTrashController extends Controller
{
    // Display a listing of emails in security trash space (deleted_at label)
    public function showDelete_at(){
        $deletedEmails = Email::onlyTrashed()->get();

    if ($deletedEmails->isEmpty()) {
        return response()->json(['message' => 'No hay correos en la papelera de seguridad o con la etiqueta delete_at not null.'], 200);
    }

    return response()->json($deletedEmails, 200);
    }

    // Restore Emails from security trash space (deleted_at label)
    public function restore($id) {
        $email=Email::withTrashed()->findOrFail($id);

        if ($email->trashed()){
            $email->restore();

            if ($email->ai_label == 'DELETE'){
                $email->update(['ai_label'=> 'REVIEW']);

                return response()->json(
                    ['message'=> 'Correo restaurado y marcado como REVIEW','email'=> $email]);

            }
        }
        
        return response()->json(['message'=> 'Este correo no estaba en la papelera']);
    }

    // Delete Emails from security trash space definitely (deleted_at label)
    public function destroyDefinitely($id){
        $email=Email::withTrashed()->findOrFail($id);
        $email->forceDelete();
        return response()->json(['message'=> 'Correo eliminado definitivamente']);
    }

}
