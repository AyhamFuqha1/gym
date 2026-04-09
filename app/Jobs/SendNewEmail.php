<?php

namespace App\Jobs;

use App\Mail\NewEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendNewEmail implements ShouldQueue
{
    use Queueable;
    protected $email;
    protected $title;
    protected $content;
 
    public function __construct($email)
    {
        $this->email = $email;
    }

    public function handle(): void
    {
        Mail::to($this->email)->send(new NewEmail($this->email,$this->title,$this->content));
    }
}
