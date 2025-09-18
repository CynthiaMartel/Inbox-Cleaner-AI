<?php

namespace App\Traits;

use OpenAI\Laravel\Facades\OpenAI;
use App\Models\Email;

trait AIEmailClassifier
{
    private function classifyWithAI(Email $email): string
    {
        
        $subject = $email->subject ?? '';
        $from = $email->from_email ?? '';
        $snippet = mb_substr($email->body_text ?? '', 0, 500); 

        
        $system = "Eres un clasificador de correos. RESPONDE SÓLO CON UNA PALABRA: KEEP, DELETE o REVIEW. No añadas explicaciones ni puntuación.";
        $user = "Reglas: KEEP si el correo es importante, transaccional o personal. DELETE si es spam, publicidad masiva o irrelevante. REVIEW si no estás segura.\nFrom: {$from}\nSubject: {$subject}\nSnippet: {$snippet}";

        try {
            $resp = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',      
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user',   'content' => $user],
                ],
                'temperature' => 0.0,   
                'max_tokens'  => 6,     
            ]);
        } catch (\OpenAI\Exceptions\RateLimitException $e) {
            
            return 'REVIEW';
        } catch (\Exception $e) {
            
            return 'REVIEW';
        }

        
        $raw = $resp->choices[0]->message->content ?? '';
        $txt = strtoupper(trim($raw));

        
        $label = preg_replace('/[^A-Z]/', '', $txt);

       
        if (in_array($label, ['KEEP','DELETE','REVIEW'])) {
            return $label;
        }

        
        if (strpos($txt, 'KEEP') !== false) return 'KEEP';
        if (strpos($txt, 'DELETE') !== false) return 'DELETE';

        // Fallback
        return 'REVIEW';
    }

}
