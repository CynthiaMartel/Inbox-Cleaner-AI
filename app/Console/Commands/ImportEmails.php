<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Email;
use Illuminate\Support\Facades\File;

class ImportEmails extends Command
{
    protected $signature = 'import:emails';
    protected $description = 'Import Emails from Json to Emails table DB';

    public function handle()
    {
        $path = database_path('data/Emails.json');
        if (!File::exists($path)) {
            $this->error('File emails.json not found');
            return;
        }

        $json = File::get($path);
        $emails = json_decode($json, true);

        foreach ($emails as $e) {
            Email::create([
                'message_id' => $e['message_id'],
                'from_email' => $e['from_email'],
                'subject' => $e['subject'],
                'body_text' => $e['body_text'],
                'received_at' => $e['received_at'],
                'ai_label' => $e['ai_label'],
                'ai_deleted' => $e['ai_deleted'] ?? false
            ]);
        }

        $this->info('Correos importados correctamente: ' . count($emails));
    }
}
