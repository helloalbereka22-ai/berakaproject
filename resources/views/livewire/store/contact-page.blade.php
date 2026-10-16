<div id="contactFormContainer">

{{-- SUCCESS MODAL --}}
<div id="successModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:white;border-radius:1rem;padding:2rem;max-width:400px;text-align:center;box-shadow:0 10px 25px rgba(0,0,0,0.2);">
        <div style="font-size:3rem;margin-bottom:1rem;">✅</div>
        <h3 style="font-size:1.5rem;font-weight:bold;margin-bottom:0.5rem;color:#000;">Success!</h3>
        <p style="color:#666;margin-bottom:1.5rem;">Your message has been sent successfully.</p>
        <button onclick="document.getElementById('successModal').style.display='none'" style="background:#4f46e5;color:white;border:none;padding:0.75rem 1.5rem;border-radius:0.5rem;cursor:pointer;font-weight:bold;">Close</button>
    </div>
</div>

@php
    $s = app(\App\Services\SettingsService::class)->all() ?? [];
    $ctMainHeading  = $s['contact_main_heading'] ?? "We'd Love to Hear From You";
    $ctIntro        = $s['contact_introduction'] ?? '';
    $ctInfoTitle    = $s['contact_info_title'] ?? 'Contact Information';
    $ctPhoneLabel   = $s['contact_phone_label'] ?? 'Phone';
    $ctPhone        = $s['contact_phone'] ?? '';
    $ctEmailLabel   = $s['contact_email_label'] ?? 'Email';
    $ctEmail        = $s['contact_email'] ?? '';
    $ctAddressLabel = $s['contact_address_label'] ?? 'Address';
    $ctAddress      = $s['contact_address'] ?? '';
    $ctGitTitle     = $s['contact_get_in_touch_title'] ?? 'Get In Touch';
    $ctGitDesc      = $s['contact_get_in_touch_desc'] ?? '';
    $ctLocLabel     = $s['contact_location_label'] ?? 'Our Location';
    $ctLocDesc      = $s['contact_location_desc'] ?? '';
    $ctTagline      = $s['contact_tagline'] ?? '';
    $ctFooterDesc   = $s['contact_footer_desc'] ?? '';
@endphp

{{-- HERO --}}
<div style="background:linear-gradient(135deg, #1e1b4b, #312e81);color:white;padding:6rem 1rem;text-align:center;">
    <h1 style="font-size:3rem;font-weight:900;margin-bottom:1rem;">{{ $ctMainHeading }}</h1>
    <p style="font-size:1.1rem;max-width:600px;margin:0 auto;opacity:0.9;">{{ $ctIntro }}</p>
</div>

{{-- MAIN CONTENT --}}
<section style="padding:4rem 1rem;background:#f9fafb;">
    <div style="max-width:1200px;margin:0 auto;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:3rem;">
            
            {{-- LEFT SIDEBAR --}}
            <div>
                {{-- Contact Information --}}
                <div style="background:linear-gradient(135deg, #312e81, #1e3a8a);color:white;border-radius:1rem;padding:2rem;margin-bottom:2rem;">
                    <h3 style="font-weight:bold;margin-bottom:1.5rem;">{{ $ctInfoTitle }}</h3>
                    <div style="space:1rem;">
                        @if($ctPhone)
                        <p style="margin-bottom:1rem;"><strong>{{ $ctPhoneLabel }}:</strong><br><a href="tel:{{ $ctPhone }}" style="color:#fff;text-decoration:none;">{{ $ctPhone }}</a></p>
                        @endif
                        @if($ctEmail)
                        <p style="margin-bottom:1rem;"><strong>{{ $ctEmailLabel }}:</strong><br><a href="mailto:{{ $ctEmail }}" style="color:#fff;text-decoration:none;">{{ $ctEmail }}</a></p>
                        @endif
                        @if($ctAddress)
                        <p><strong>{{ $ctAddressLabel }}:</strong><br>{{ $ctAddress }}</p>
                        @endif
                    </div>
                </div>

                {{-- Social Media Section --}}
                @php
                    $socialLinks = [
                        ['name' => 'Facebook', 'url' => $facebookUrl, 'icon' => '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>'],
                        ['name' => 'Telegram', 'url' => $telegramUrl, 'icon' => '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a11.955 11.955 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.96 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.365-1.373.275-.567-.099-1.614-.321-2.406-.484-.963-.202-1.723-.2-1.773-.855-.025-.427.1-.878.32-1.335C2.122 9.88 5.859 5.054 9.64 5.284c.514.043 1.052.057 1.57.0 1.323-.1 2.692.4 3.692 1.178.527.483 1.15 1.07 1.654 1.915.155.264.599.941.959 1.514.133.199.276.397.422.59z"/></svg>'],
                        ['name' => 'TikTok', 'url' => $tiktokUrl, 'icon' => '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M19.321 5.562a5.122 5.122 0 0 1-.868-.084c.584-1.746.603-3.513.056-5.397h-3.103v14.132c0 2.695-2.163 4.858-4.858 4.858-2.695 0-4.858-2.163-4.858-4.858 0-2.695 2.163-4.858 4.858-4.858.211 0 .419.013.624.04v3.237a8.125 8.125 0 0 0-.624-.025c-4.381 0-7.942 3.56-7.942 7.942 0 4.381 3.56 7.942 7.942 7.942 4.381 0 7.942-3.56 7.942-7.942V8.697a6.237 6.237 0 0 0 3.887 1.293v-3.124c-.788 0-1.528-.161-2.258-.476z"/></svg>'],
                        ['name' => 'YouTube', 'url' => $youtubeUrl, 'icon' => '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>'],
                        ['name' => 'Instagram', 'url' => $instagramUrl, 'icon' => '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><rect x="2.458" y="2.458" width="19.084" height="19.084" rx="4.458" fill="none" stroke="currentColor" stroke-width="2.458"/><circle cx="12" cy="12" r="3.375" fill="none" stroke="currentColor" stroke-width="2.458"/><circle cx="18.206" cy="5.793" r="1.458" fill="currentColor"/></svg>'],
                    ];
                    $activeSocial = array_filter($socialLinks, fn($link) => !empty(trim($link['url'])));
                @endphp

                @if(!empty($activeSocial))
                <div style="background:white;border:1px solid #e5e7eb;border-radius:1rem;padding:2rem;margin-bottom:2rem;">
                    <div style="text-align:center;margin-bottom:1.5rem;">
                        <h3 style="font-weight:700;font-size:1.25rem;margin:0 0 0.5rem 0;color:#111827;">Connect With Us</h3>
                        <p style="font-size:0.85rem;color:#6b7280;margin:0;">Follow us on social media for updates and news</p>
                    </div>
                    <div style="display:flex;justify-content:center;gap:1rem;flex-wrap:wrap;">
                        @foreach($activeSocial as $social)
                        <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer"
                           style="display:inline-flex;align-items:center;justify-content:center;width:48px;height:48px;background:white;color:#4f46e5;border-radius:10px;text-decoration:none;transition:all 0.3s cubic-bezier(0.4, 0, 0.2, 1);box-shadow:0 1px 3px rgba(0,0,0,0.1),0 1px 2px rgba(0,0,0,0.06);border:1.5px solid #e5e7eb;"
                           onmouseover="this.style.background='#4f46e5';this.style.color='white';this.style.transform='translateY(-3px)';this.style.boxShadow='0 10px 25px rgba(79,70,229,0.2)';"
                           onmouseout="this.style.background='white';this.style.color='#4f46e5';this.style.transform='translateY(0)';this.style.boxShadow='0 1px 3px rgba(0,0,0,0.1),0 1px 2px rgba(0,0,0,0.06)';"
                           title="{{ $social['name'] }}">
                            {!! $social['icon'] !!}
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif

                <div style="background:white;border:1px solid #e5e7eb;border-radius:1rem;padding:1.5rem;margin-bottom:2rem;">
                    <h3 style="font-weight:bold;margin-bottom:0.5rem;color:#000;">{{ $ctGitTitle }}</h3>
                    <p style="color:#666;font-size:0.95rem;">{{ $ctGitDesc }}</p>
                </div>
            </div>

            {{-- RIGHT COLUMN: FORM --}}
            <div>
                <div style="background:white;border:1px solid #e5e7eb;border-radius:1rem;padding:2rem;">
                    <form id="contactForm" style="display:flex;flex-direction:column;gap:1.5rem;">
                        @csrf
                        
                        <div>
                            <label style="display:block;font-weight:bold;margin-bottom:0.5rem;color:#000;">Name <span style="color:red;">*</span></label>
                            <input type="text" name="name" required style="width:100%;padding:0.75rem;border:1px solid #d1d5db;border-radius:0.5rem;font-size:1rem;" placeholder="Your name">
                            <small style="color:red;display:none;" class="error-name"></small>
                        </div>

                        <div>
                            <label style="display:block;font-weight:bold;margin-bottom:0.5rem;color:#000;">Email</label>
                            <input type="email" name="email" style="width:100%;padding:0.75rem;border:1px solid #d1d5db;border-radius:0.5rem;font-size:1rem;" placeholder="your@email.com">
                            <small style="color:red;display:none;" class="error-email"></small>
                        </div>

                        <div>
                            <label style="display:block;font-weight:bold;margin-bottom:0.5rem;color:#000;">Phone <span style="color:red;">*</span></label>
                            <input type="tel" name="phone" required style="width:100%;padding:0.75rem;border:1px solid #d1d5db;border-radius:0.5rem;font-size:1rem;" placeholder="0910089936">
                            <small style="color:#666;">Numbers only, 10-13 digits</small>
                            <small style="color:red;display:none;" class="error-phone"></small>
                        </div>

                        <div>
                            <label style="display:block;font-weight:bold;margin-bottom:0.5rem;color:#000;">Message <span style="color:red;">*</span></label>
                            <textarea name="message" required rows="5" style="width:100%;padding:0.75rem;border:1px solid #d1d5db;border-radius:0.5rem;font-size:1rem;resize:none;" placeholder="Your message..."></textarea>
                            <small style="color:red;display:none;" class="error-message"></small>
                        </div>

                        <button type="submit" id="submitBtn" style="background:#4f46e5;color:white;padding:0.75rem 1.5rem;border:none;border-radius:0.5rem;cursor:pointer;font-weight:bold;font-size:1rem;transition:all 0.3s ease;">
                            <span id="btnText">Send Message</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- FOOTER --}}
@if($ctTagline || $ctFooterDesc)
<div style="background:#1f2937;color:white;padding:2rem;text-align:center;">
    @if($ctTagline)
        <h4 style="font-weight:bold;font-size:1.25rem;margin-bottom:0.5rem;">{{ $ctTagline }}</h4>
    @endif
    @if($ctFooterDesc)
        <p style="font-size:0.95rem;">{{ $ctFooterDesc }}</p>
    @endif
</div>
@endif

</div>

<script>
document.getElementById('contactForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    submitBtn.disabled = true;
    btnText.textContent = 'Sending...';
    
    // Clear all error messages
    document.querySelectorAll('.error-name, .error-email, .error-phone, .error-message').forEach(el => {
        el.style.display = 'none';
        el.textContent = '';
    });
    
    const formData = new FormData(this);
    
    try {
        const response = await fetch('{{ route("contact.submit") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const data = await response.json();
        
        if (response.ok) {
            // Show success modal
            document.getElementById('successModal').style.display = 'flex';
            
            // Clear form
            document.getElementById('contactForm').reset();
            
            // Reset button immediately
            btnText.textContent = 'Send Message';
            submitBtn.disabled = false;
            
            // Auto-close modal after 5 seconds
            setTimeout(() => {
                document.getElementById('successModal').style.display = 'none';
            }, 5000);
        } else {
            // Show validation errors
            if (data.errors) {
                Object.entries(data.errors).forEach(([field, messages]) => {
                    const errorEl = document.querySelector('.error-' + field);
                    if (errorEl) {
                        errorEl.textContent = messages[0];
                        errorEl.style.display = 'block';
                    }
                });
            }
            // Reset button on error
            btnText.textContent = 'Send Message';
            submitBtn.disabled = false;
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred while sending your message.');
        // Reset button on error
        btnText.textContent = 'Send Message';
        submitBtn.disabled = false;
    }
});
</script>
