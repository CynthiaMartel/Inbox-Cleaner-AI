<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Email;
use Illuminate\Http\Request;

class SecurityTrashController extends Controller
{
    // Display a listing of emails in security trash space (deleted_at label)
    public function index(){
        return response()->json(Email::onlyTrashed()->get);
    }

    // Restore Emails in security trash space (deleted_at label)
    public function store($id) {
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

    // Delete Emails in security trash space definitly (deleted_at label)
    public function destroy($id){
        $email=Email::withTrashed()->findOrFail($id);
        $email->forceDelete();
        return response()->json(['message'=> 'Correo eliminado definitivamente']);
    }

}
