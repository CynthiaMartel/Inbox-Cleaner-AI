<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmailRequest;
use App\Models\Email;

use OpenAI\Laravel\Facades\OpenAI;


class EmailController extends Controller
{
    /**
     * Display a listing of emails.
     */
    public function index()
    {
        $emails = Email::all();
        return response()->json($emails);
    }

    /**
     * Display the specified email's id.
     */
    public function show($id)
    {
        $email = Email::findOrFail($id);
        return response()->json($email);
    }

    /**
     * Store a newly created email into BD.
     */
    public function store(StoreEmailRequest $request) // Rules (rules ()) created in app/Http/Request/StoreEmailRequest.php
    {
     return Email::create($request->validated()); // create () uses $fillable in Models for the insertion into BD 
    }

    /**
     * Remove an email.
     */
    public function destroy(Email $email)
    {
        $email->delete();
        return response()->noContent(); // 204 without content
    }

     /**
     * Display emails in safe zone.
     */
    public function keep(){
        return response()->json(Email::where('ai_label', 'KEEP')->get());
    }

    /**
     * Display deleteds emails.
     */
    public function deleted(){
        return response()->json(Email::where('ai_label', 'DELETE')->get());
    }


    public function classify($id){
        $email = Email::findorFail($id);

        // ****** Call AI later *****
        $label = $this->classifyWithAI($email);

        $email->update([
            'ai_label' => $label,
            'ai_deleted' => $label === 'DELETE',
        ]);


        return response()->json($email);
    }
      // ****** In this moment we only have REVIEW, but in the further steps we will improve this section for AI working*****
    public function classifyWithAI(Email $email):  string {
        return 'REVIEW';
    }

     /**
     * Testing AI in Postman
     */
    public function testAI()
{
    try {
        $result = OpenAI::chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => 'Eres un clasificador de correos.'],
                ['role' => 'user', 'content' => 'Correo de prueba sobre facturas y pagos.'],
            ],
        ]);

        return response()->json($result);
    } catch (\OpenAI\Exceptions\RateLimitException $e) {
        return response()->json([
            'error' => 'Se alcanzó el límite de peticiones a OpenAI. Intenta de nuevo en unos segundos.',
            'details' => $e->getMessage(),
        ], 429);
    }
}

}



