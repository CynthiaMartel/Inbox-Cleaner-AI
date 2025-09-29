<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use OpenAI\Laravel\Facades\OpenAI;

class Email extends Model
{
    protected $table = 'emails'; // Nota aprendizaje: Esta línea es para asegurar que el modelo Email está vinculado a la tabla de BD emails (ver migraciones)

    protected $fillable = [
        'message_id','from_email','subject','body_text',
        'received_at','metadata','ai_label','ai_deleted','embedding'
    ];

    protected $casts = [  
        'received_at' => 'datetime',
        'metadata'    => 'array',
        'embedding'   => 'array',
        'ai_deleted'  => 'boolean',
    ];

    use SoftDeletes;
    protected $dates = ['deleted_at'];
  /**
     * Boot method for events
     */
    protected static function boot()
    {
        parent::boot();

        // Display event after create a email
        static::created(function ($email) {
            $text = trim(($email->subject ?? '') . ' ' . ($email->body_text ?? ''));
            $embedding = self::getEmailEmbedding($text);

            if ($embedding) {
                $email->embedding = $embedding; // se guarda como array
                $email->save();
            }
        });
    }

    /**
     * Generate embbending with openAI
     */
    private static function getEmailEmbedding(string $text): ?array
    {
        if (empty($text)) {
            return null;
        }

        try {
            $response = OpenAI::embeddings()->create([
                'model' => 'text-embedding-3-small',
                'input' => $text,
            ]);

            return $response['data'][0]['embedding'] ?? null;
        } catch (\Exception $e) {
            \Log::error("Error al generar embedding de email: " . $e->getMessage());
            return null;
        }
    }
}
