<?php

namespace App\Http\Controllers;

use App\Models\Email;

use App\Http\Requests\StoreEmailRequest;
use App\Traits\AIEmailClassifier;

use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Http\Request;



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
     *  OPENAI IMPLEMENTATION 
     */
    public function classify()
    
    { // Classify emails labels using OpenAI
    $emailLabels = $this->classifyWithAI();

    $emails=[];
    
    // Update emails labels in Data Base
    foreach ($emailLabels as $id => $label) {
        $email = Email::find($id);
        if ($email) {
            $email->update([
                'ai_label' => $label,
                'ai_deleted' => $label === 'DELETE',
            ]);
            if ($label === 'DELETE' && !$email->trashed()) {
                // Mark timestamp in deleted_at (soft delete)
                $email->delete(); 
            }
        
        $email->refresh();
        $emails[] = $email->toArray();
        }
    }
    return response()->json($emails);             
    }

    /**
     *  Auxiliar method. app/Traits/AIEmailClassifier.php
     */
      use AIEmailClassifier;

    /**
     *  Show emails with REVIEW classification results
     */
    public function showReview(){
        $email = Email::where('ai_label','REVIEW')->get();
        return response()->json($email);
    }
    /**
     *  Show emails with KEEP classification results
     */
    public function showKeep(){
        $email = Email::where('ai_label','KEEP')->get();
        return response()->json($email);
    }
    public function showDelete(){
        $email = Email::withTrashed()->where('ai_label','DELETE')->get();
        return response()->json($email);
    }
    
    /**
     *  Update classification manually (by user)(Options in fronted)
     */
    public function updateLabelManually(Request $request, $id){
        $request->validate([
            'ai_label' => 'required|in:KEEP,REVIEW,FORCE_DELETE'
        ]);

        $email = Email::withTrashed()->findOrFail($id);

        $newLabel = $request->ai_label;

        if ($newLabel === 'FORCE_DELETE') {
            $email->forceDelete();
            return response()->json(['message' => 'Correo eliminado definitivamente']);
        }

        $email->update([
            'ai_label' => $newLabel,
            'ai_deleted' => false,
            'deleted_at' => null,
        ]);

        return response()->json($email);
    }

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



