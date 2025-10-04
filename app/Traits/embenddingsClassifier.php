<?php
/**
 * Suggested implementation for the future (this is not used in this project):
 * 
 * smartClassifier(Email $email): string
 *
 * “Smart” classification flow for a single email:
 *
 * 1️. Generate or reuse embedding:
 *    - If the email does not have an embedding yet, it is generated from the subject + body_text using OpenAI embeddings.
 *    - The embedding is saved in the database for future use.
 *
 * 2️.  Search for similar already-classified emails:
 *    - Embeddings are compared with other emails that already have labels.
 *    - Similarity is calculated and the most common label among neighbors is determined.
 *
 * 3️. Use similarity-based label:
 *    - If the average similarity with neighbors is high (>= 0.80) and a clear label exists, that label is used.
 *
 * 4️. Fall back to GPT:
 *    - If there are not enough neighbors or similarity is low, classifyWithAI() is called to classify in batches using OpenAI.
 *
 * 5️. Final result:
 *    - Returns 'KEEP', 'DELETE', or 'REVIEW' for the email.
 *
 * This flow allows emails to be classified internally whenever possible
 * (learning from database embeddings), and only uses OpenAI when necessary.
 */


namespace App\Traits;

use App\Models\Email;
use App\Traits\AIEmailClassifier;

trait embenddingsClassifier
{
    private function smartClassifier($emails): array{
        $labels = [];

        $toGPT = [];

        foreach ($emails as $email) {
            $embedding = $email->embedding; 
            $neighbors = $this->findSimilarEmails($embedding, 5);

            $label = null;

            if ($neighbors->isNotEmpty()) {
                $avgSim = $neighbors->avg('similarity');
                $topLabel = $neighbors->pluck('ai_label')->countBy()->sortDesc()->keys()->first();

                if ($avgSim >= 0.80 && $topLabel) {
                    $label = $topLabel;
                }
            }

            if (!$label) {
                $toGPT[] = $email;
            } else {
                $labels[$email->id] = $label;
            }
        }

        // If embendding is not enougth similar, clasiffy with openAI
        if (!empty($toGPT)) {
            $gptLabels = $this->classifyWithAI($toGPT); 
            $labels = array_merge($labels, $gptLabels);
        }

        return $labels;
    }

}