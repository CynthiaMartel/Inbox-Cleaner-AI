<?php

namespace App\Traits;

use OpenAI\Laravel\Facades\OpenAI;
use App\Models\Email;

trait AIEmailClassifier
{
private function classifyWithAI(): array
{
    // Take 10 emails maximum without labels
    $emails = Email::whereNull('ai_label')->limit(10)->get();

    if ($emails->isEmpty()) {
        return [];
    }

    $system = "Eres un clasificador de correos. RESPONDE SÓLO CON UNA palabra por cada correo: KEEP, DELETE o REVIEW. Mantén el orden. No añadas explicaciones ni puntuación.";

    $labels = $emails->chunk(10)->flatMap(function ($chunk) use ($system) 
    {
        // Prompt
        $userPrompt = $chunk->map(fn($email, $i) => 
            "Correo {$i}:\nFrom: {$email->from_email}\nSubject: {$email->subject}\nSnippet: " . mb_substr($email->body_text ?? '', 0, 500)
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

            $lines = preg_split('/[\r\n]+/', strtoupper(trim($resp->choices[0]->message->content ?? '')));

            $blockLabels = collect($lines)
                ->map(fn($line) => preg_replace('/[^A-Z]/', '', $line))
                ->map(fn($label) => in_array($label, ['KEEP', 'DELETE', 'REVIEW']) ? $label : null)
                ->filter()
                ->values();

        } catch (\Exception $e) {
            $blockLabels = $chunk->map(fn() => 'REVIEW');
        }

        while ($blockLabels->count() < $chunk->count()) {
            $blockLabels->push('REVIEW');
        }

        return $blockLabels;
    })->toArray();

    // Return emails with labels
    return $emails->mapWithKeys(function($email, $index) use ($labels) {
        return [$email->id => $labels[$index] ?? 'REVIEW'];
    })->toArray();
}

}
