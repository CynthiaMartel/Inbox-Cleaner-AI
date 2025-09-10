<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
