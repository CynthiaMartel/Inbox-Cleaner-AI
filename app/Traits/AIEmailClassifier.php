<?php
/**
 * classifyWithAI(array|Collection $emails): array
 *
 * Batch classification flow using OpenAI:
 *
 * 1️. Email input:
 *    - Receives an array or collection of Email objects that need classification.
 *    - Each object contains all database data (id, subject, body_text, embedding, etc.).
 *
 * 2️. Safety check:
 *    - If $emails is empty, returns an empty array immediately.
 *
 * 3️. Chunking:
 *    - Emails are divided into batches of 10 to reduce tokens and optimize GPT calls.
 *
 * 4️. Prompt preparation:
 *    - For each batch, a prompt is created including From, Subject, and a snippet of body_text for each email.
 *    - GPT is instructed to return ONLY one word per email: KEEP, DELETE, or REVIEW.
 *
 * 5️. Call to OpenAI API:
 *    - Each batch is sent to gpt-4o-mini with temperature 0 and limited max_tokens.
 *    - The response is processed and cleaned to extract only valid labels.
 *
 * 6️. Result:
 *    - Returns an associative array [email_id => label] for each email in the batch.
 *    - On error, 'REVIEW' is assigned by default.
 *
 * Suggested implementation in the future: This flow allows classifying **only emails that could not be classified using embeddings**,
 * maintaining efficiency by processing 10 emails per request.
 */

namespace App\Traits;

use OpenAI\Laravel\Facades\OpenAI;
use App\Models\Email;

trait AIEmailClassifier
{
private function classifyWithAI(): array
{
    // Take 10 emails max for classification
    $emails = Email::whereNull('ai_label')->limit(10)->get();

    if ($emails->isEmpty()) {
        return [];
    }

    $system = "Eres un clasificador de correos. RESPONDE SÓLO CON UNA palabra por cada correo: KEEP, DELETE o REVIEW. Mantén el orden. No añadas explicaciones ni puntuación.";

    // 
    $labels = $emails->chunk(10)->flatMap(function ($chunk) use ($system) 
    {
        // Propmt to send openAI
        $userPrompt = $chunk->map(fn($email, $i) => 
            "Correo {$i}:\nFrom: {$email->from_email}\nSubject: {$email->subject}\nSnippet: " . 
            mb_substr($email->body_text ?? '', 0, 500)
        )->implode("\n\n");

        try {
            $resp = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'temperature' => 0.0,
                'max_tokens' => 60,
            ]);

            // 
            $lines = preg_split('/[\r\n]+/', strtoupper(trim($resp->choices[0]->message->content ?? '')));

            $blockLabels = collect($lines)
                ->map(fn($line) => preg_replace('/[^A-Z]/', '', $line))
                ->map(fn($label) => in_array($label, ['KEEP', 'DELETE', 'REVIEW']) ? $label : null)
                ->filter()
                ->values();

        } catch (\Exception $e) {
            // if we have arror in the response -> default as REVIEW
            $blockLabels = $chunk->map(fn() => 'REVIEW');
        }

        // Ensure each email has a classification 
        while ($blockLabels->count() < $chunk->count()) {
            $blockLabels->push('REVIEW');
        }

        return $blockLabels;
    })->toArray();

    // Associate email ID's with their labels
    return $emails->mapWithKeys(function ($email, $index) use ($labels) {
        return [$email->id => $labels[$index] ?? 'REVIEW'];
    })->toArray();
}

}
