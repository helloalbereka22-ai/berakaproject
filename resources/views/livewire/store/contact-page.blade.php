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

                {{-- Social Media Section with Branded Colors and Real SVG Icons --}}
                @php
                    $socialLinks = [
                        ['name' => 'Facebook', 'url' => $facebookUrl, 'color' => '#1877F2', 'hoverColor' => '#165FE5', 
                         'icon' => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>'],
                        
                        ['name' => 'Telegram', 'url' => $telegramUrl, 'color' => '#0088cc', 'hoverColor' => '#006fa3', 
                         'icon' => '<svg viewBox="0 0 88 88" fill="currentColor"><path d="M43.6292 19.4778C73.6292 15.5478 76.5273 12.4649 80.4333 12.1743C80.2328 12.1596 80.0305 12.1513 79.8263 12.1513C75.6177 12.1513 72.4161 15.3473 72.4161 19.4768C72.4161 23.6064 75.6177 26.8051 79.8263 26.8051C80.0305 26.8051 80.2328 26.7969 80.4333 26.7821C76.5282 26.4915 73.6292 23.4068 73.6292 19.4768V19.4778ZM24.0277 7.46994V6.64955C22.7751 6.56126 21.4635 6.16762 20.3102 5.40885C21.3716 6.50147 22.6941 7.16643 24.0277 7.46994ZM12.6452 0V18.3401C12.6452 20.7433 10.9153 22.2884 8.81185 22.2884C8.11378 22.2884 7.45158 22.1256 6.87584 21.8249C7.60702 22.7565 8.76035 23.2918 10.0259 23.2918C12.1293 23.2918 13.8593 21.7467 13.8593 19.3416V1.00157H17.1933C17.1022 0.682429 17.0277 0.348572 16.9707 0H12.6462H12.6452ZM9.91 10.6623V9.75359C9.48877 9.68369 9.06753 9.6607 8.71896 9.6607C3.9732 9.6607 0 13.4628 0 18.1773C0 21.2721 1.50926 23.9181 3.80213 25.4844C2.20457 23.9016 1.21403 21.6869 1.21403 19.1788C1.21403 14.4717 5.17527 10.6724 9.91092 10.6623H9.91Z"/><path d="M82.2534 12.1524C82.0492 12.1524 81.8469 12.1606 81.6464 12.1753C85.5497 12.466 88.4477 15.5507 88.4477 19.4788C88.4477 23.4069 85.5497 26.4935 81.6464 26.7841C81.8469 26.7988 82.0492 26.8071 82.2534 26.8071C86.4602 26.8071 89.6617 23.6093 89.6617 19.4788C89.6617 15.3484 86.4602 12.1533 82.2534 12.1533V12.1524ZM20.3092 5.40991C19.2837 4.35683 18.5011 2.90644 18.1828 1.00171H17.1923C17.7579 3.06003 18.9223 4.49663 20.3092 5.40991ZM24.0267 10.9429C22.1008 10.9429 20.278 10.576 18.6721 9.49621C20.5419 11.3632 22.8109 11.9463 25.2408 11.9463V7.65218C24.8407 7.62551 24.4314 7.56572 24.0267 7.47099V10.9429ZM6.87486 21.8259C6.35889 21.1748 6.05078 20.3323 6.05078 19.3427C6.05078 16.567 8.22409 15.1 11.1221 15.4045V10.7553C10.7009 10.6854 10.2797 10.6624 9.92925 10.6624H9.90902V14.4001C7.01098 14.0976 4.83767 15.5627 4.83767 18.3402C4.83767 19.9644 5.6691 21.1959 6.87486 21.825V21.8259ZM17.4581 18.0385C17.4581 23.8953 12.9708 26.9984 8.74097 26.9984C6.9098 26.9984 5.20924 26.4466 3.80115 25.4855C5.38215 27.0518 7.55821 28 9.955 28C14.1848 28 18.6721 24.8969 18.6721 19.0401V9.49713C18.2509 9.21294 17.8462 8.88092 17.4581 8.49372V18.0385Z"/></svg>'],
                        
                        ['name' => 'TikTok', 'url' => $tiktokUrl, 'color' => '#000000', 'hoverColor' => '#333333', 
                         'icon' => '<svg viewBox="0 0 105 105" fill="currentColor"><path d="M63.745 13.7278H58.5559L54.6278 17.9622V7.8352H50.2573V26.5018H54.6278V23.0482L56.3578 21.2281L58.9992 26.4779H64.0945L59.5832 18.194L63.7459 13.726L63.745 13.7278ZM28.5233 11.9473H33.0585V26.5027H37.7095V11.9473H40.7648L42.3661 7.83612H28.5243V11.9473H28.5233ZM43.4182 26.5027H47.7887V13.7278H43.4182V26.5027ZM45.5915 7.60251C44.166 7.60251 43.1607 8.58293 43.1607 9.93583C43.1607 11.2887 44.166 12.2692 45.5915 12.2692C47.0171 12.2692 48.0224 11.2657 48.0224 9.93583C48.0224 8.60592 47.0171 7.60251 45.5915 7.60251ZM100.125 18.1958L104.287 13.7278H99.0982L95.17 17.9622V7.8352H90.7995V26.5018H95.17V23.0482L96.9 21.2281L99.5415 26.4779H104.637L100.125 18.194L100.125 18.1958ZM76.1539 7.83612H61.7326V11.9473H66.2669V26.5027H70.9179V11.9473H74.5517L76.1539 7.83612ZM81.6474 12.1753C81.4469 12.1606 81.2445 12.1524 81.0403 12.1524C80.8362 12.1524 80.6338 12.1606 80.4333 12.1753C76.5282 12.466 73.6292 15.5489 73.6292 19.4788C73.6292 23.4088 76.5273 26.4935 80.4333 26.7841C80.6338 26.7988 80.8362 26.8071 81.0403 26.8071C81.2445 26.8071 81.4469 26.7988 81.6474 26.7841C85.5507 26.4935 88.4487 23.4069 88.4487 19.4788C88.4487 15.5507 85.5507 12.466 81.6474 12.1753ZM81.6474 22.7631C81.4533 22.7981 81.251 22.8146 81.0403 22.8146C80.8297 22.8146 80.6274 22.7981 80.4333 22.7631C78.8974 22.4973 77.8839 21.218 77.8839 19.4779C77.8839 17.7378 78.8974 16.4594 80.4333 16.1927C80.6274 16.1577 80.8297 16.1412 81.0403 16.1412C81.251 16.1412 81.4533 16.1577 81.6474 16.1927C83.1815 16.4585 84.195 17.7378 84.195 19.4779C84.195 21.218 83.1815 22.4964 81.6474 22.7631ZM17.1933 1.00171H13.8593V19.3418C13.8593 21.7468 12.1293 23.292 10.0259 23.292C8.76037 23.292 7.60704 22.7558 6.87586 21.825C5.6701 21.1968 4.83868 19.9644 4.83868 18.3402C4.83868 15.5627 7.01198 14.0976 9.91002 14.4001V10.6624C5.17437 10.6725 1.21313 14.4728 1.21313 19.179C1.21313 21.687 2.20367 23.9017 3.80123 25.4846C5.20932 26.4447 6.90989 26.9975 8.74105 26.9975C12.9709 26.9975 17.4582 23.8944 17.4582 18.0376V8.4928C17.8463 8.88 18.251 9.21202 18.6722 9.49621C20.278 10.576 22.1009 10.9429 24.0268 10.9429V7.47099C22.6932 7.16841 21.3697 6.50253 20.3093 5.40991C18.9224 4.49663 17.758 3.06095 17.1924 1.00171H17.1933Z"/></svg>'],
                        
                        ['name' => 'YouTube', 'url' => $youtubeUrl, 'color' => '#FF0000', 'hoverColor' => '#cc0000', 
                         'icon' => '<svg viewBox="0 0 30 30" fill="currentColor"><path d="M14.4848 20C14.4848 20 23.5695 20 25.8229 19.4C27.0917 19.06 28.0459 18.08 28.3808 16.87C29 14.65 29 9.98 29 9.98C29 9.98 29 5.34 28.3808 3.14C28.0459 1.9 27.0917 0.94 25.8229 0.61C23.5695 0 14.4848 0 14.4848 0C14.4848 0 5.42037 0 3.17711 0.61C1.9286 0.94 0.954148 1.9 0.59888 3.14C0 5.34 0 9.98 0 9.98C0 9.98 0 14.65 0.59888 16.87C0.954148 18.08 1.9286 19.06 3.17711 19.4C5.42037 20 14.4848 20 14.4848 20ZM11.8251 14.555L11.8251 5.405L19.5443 9.98L11.8251 14.555Z"/></svg>'],
                        
                        ['name' => 'Instagram', 'url' => $instagramUrl, 'color' => '#E4405F', 'hoverColor' => '#d93175', 
                         'icon' => '<svg viewBox="0 0 24 24" fill="currentColor"><rect x="2.458" y="2.458" width="19.084" height="19.084" rx="4.458" fill="none" stroke="currentColor" stroke-width="1.458"/><circle cx="12" cy="12" r="3.375" fill="none" stroke="currentColor" stroke-width="1.458"/><circle cx="18.206" cy="5.793" r="1.458" fill="currentColor"/></svg>'],
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
                           style="display:inline-flex;align-items:center;justify-content:center;width:56px;height:56px;background:{{ $social['color'] }};border-radius:12px;text-decoration:none;transition:all 0.3s cubic-bezier(0.4, 0, 0.2, 1);box-shadow:0 2px 8px rgba(0,0,0,0.15);"
                           onmouseover="this.style.background='{{ $social['hoverColor'] }}';this.style.transform='translateY(-4px) scale(1.08)';this.style.boxShadow='0 8px 16px rgba(0,0,0,0.25)';"
                           onmouseout="this.style.background='{{ $social['color'] }}';this.style.transform='translateY(0) scale(1)';this.style.boxShadow='0 2px 8px rgba(0,0,0,0.15)';"
                           title="{{ $social['name'] }}">
                            <svg style="width:32px;height:32px;color:white;" viewBox="0 0 24 24" fill="white">
                                {!! $social['icon'] !!}
                            </svg>
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
