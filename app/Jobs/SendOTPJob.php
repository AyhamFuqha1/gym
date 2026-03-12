<?php

namespace App\Jobs;

use App\Mail\OTPMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Mail;

class SendOTPJob implements ShouldQueue
{
    use Queueable;

    private $email;
    private $name;
    private $OTP;
    public function __construct($email,$name,$OTP)
    {
        $this->email = $email;
        $this->name = $name;
        $this->OTP = $OTP;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->email)->send(new OTPMail($this->email,$this->OTP,$this->name));
    }
}
