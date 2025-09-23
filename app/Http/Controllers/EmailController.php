<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmailRequest;
use App\Models\Email;
use App\Traits\AIEmailClassifier;

use OpenAI\Laravel\Facades\OpenAI;


class EmailController extends Controller
{
     /**
     * CRUD
     */

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
    public function deleted()
    {
        return response()->json(Email::where('ai_label', 'DELETE')->get());
    }

    
       /**
     *  OPENAI IMPLEMENTATION 
     */

    public function classify()
    { // Classify emails labels using OpenAI
    $emailLabels = $this->classifyWithAI();
    
    // Update emails labels in Data Base
    foreach ($emailLabels as $id => $label) {
        $email = Email::find($id);
        if ($email) {
            $email->update([
                'ai_label' => $label,
                'ai_deleted' => $label === 'DELETE',
            ]);
        }
    }
    return response()->json([
        'message' => 'Correos clasificados',
        'labels' => $emailLabels
    ]);             
    }


    /**
     *  Auxiliar method. app/Traits/AIEmailClassifier.php
     */
      use AIEmailClassifier;



     /**
     * TESTING OPENAI IN Postman
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



