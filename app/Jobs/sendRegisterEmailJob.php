<?php

namespace App\Jobs;

use App\Mail\RegisterEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Mail;

class sendRegisterEmailJob implements ShouldQueue
{
    use Queueable;

    
    private $email;
    private $name;
    private $password;
    public function __construct($email,$name,$password)
    {
        $this->email = $email;
        $this->name = $name;
        $this->password = $password;
    }


    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->email)->send(new RegisterEmail($this->email,$this->password,$this->name));
    }
}
