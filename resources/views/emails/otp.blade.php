<x-mail::message>

# 💪 {{ config('app.name') }}

Hello **{{ $name }}**,  

We received a request to reset your password for your **Gym account**.

Please use the verification code below to continue.

<x-mail::panel>
<div style="text-align:center">

<h2 style="margin-bottom:10px; color:#555;">
Your Verification Code
</h2>

<div style="
font-size:36px;
font-weight:bold;
letter-spacing:8px;
color:#111;
background:#f4f4f4;
padding:15px;
border-radius:8px;
display:inline-block;
">
{{ $OTP }}
</div>

</div>
</x-mail::panel>

⚠️ **Security Notice**

- This code will expire in **10 minutes**.
- Do **not share this code** with anyone.

If you did not request a password reset, please ignore this email.

Thanks,<br>
**{{ config('app.name') }} Team**

</x-mail::message>