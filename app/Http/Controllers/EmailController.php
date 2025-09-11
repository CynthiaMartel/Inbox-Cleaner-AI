<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmailRequest;
use App\Models\Email;


class EmailController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $emails = Email::all();
        return response()->json($emails);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $email = Email::findOrFail($id);
        return response()->json($email);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEmailRequest $request) // Reglas (rules ()) creadas a parte con StoreEmailRequest para que no esté dentro del método y se vea más limpio
    {
     return Email::create($request->validated()); // create () usa $fillable en Models para la inserción en BD
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Email $email)
    {
        $email->delete();
        return response()->noContent(); // 204 sin contenido
    }

}



