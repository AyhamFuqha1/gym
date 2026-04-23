@component('mail::message')

{{-- Header --}}
<div style="text-align:center; padding-bottom: 15px;">
    <h1 style="color:#27ae60; margin-bottom:5px;">
        💪 Gym News
    </h1>
    <p style="color:#7f8c8d; font-size:14px;">
        Stay strong. Stay updated.
    </p>
</div>

---

{{-- Title --}}
@component('mail::panel')
# 📰 {{ $title }}
@endcomponent

---

{{-- Content --}}
<div style="font-size:16px; line-height:1.8; color:#2c3e50;">
    {{ $content }}
</div>

---


---

{{-- Info --}}
@component('mail::panel')
📧 Sent to: **{{ $email }}**  
🕒 Stay tuned for more updates!
@endcomponent

---

{{-- Footer --}}
<div style="text-align:center; font-size:13px; color:#95a5a6;">
    🔥 Gym Management System  
    <br>
    💪 Train Hard. See Results.
</div>

@endcomponent