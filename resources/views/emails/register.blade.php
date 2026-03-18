<x-mail::message>

# 💪 {{ config('app.name') }}

Hello **{{ $name }}**,  

Welcome to our **Gym System** 🎉

An account has been created for you by the **Admin**.

You can now log in using the credentials below:

<x-mail::panel>
<div style="text-align:center">

<h2 style="margin-bottom:10px; color:#555;">
Your Login Details
</h2>

<div style="
font-size:18px;
color:#333;
margin-bottom:10px;
">
<strong>Email:</strong> {{ $email }}
</div>

<div style="
font-size:28px;
font-weight:bold;
letter-spacing:3px;
color:#111;
background:#f4f4f4;
padding:15px;
border-radius:8px;
display:inline-block;
">
{{ $password }}
</div>

</div>
</x-mail::panel>

⚠️ **Security Notice**

- Please **change your password after your first login**.
- Do **not share your login details** with anyone.

<x-mail::button :url="$loginUrl">
Login Now
</x-mail::button>

If you have any issues, feel free to contact us.

Thanks,<br>
**{{ config('app.name') }} Team**

</x-mail::message>