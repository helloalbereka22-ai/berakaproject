<div id="contactFormContainer">


<div id="successModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:white;border-radius:1rem;padding:2rem;max-width:400px;text-align:center;box-shadow:0 10px 25px rgba(0,0,0,0.2);">
        <div style="font-size:3rem;margin-bottom:1rem;">✅</div>
        <h3 style="font-size:1.5rem;font-weight:bold;margin-bottom:0.5rem;color:#000;">Success!</h3>
        <p style="color:#666;margin-bottom:1.5rem;">Your message has been sent successfully.</p>
        <button onclick="document.getElementById('successModal').style.display='none'" style="background:#4f46e5;color:white;border:none;padding:0.75rem 1.5rem;border-radius:0.5rem;cursor:pointer;font-weight:bold;">Close</button>
    </div>
</div>

<?php
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
?>


<div style="background:linear-gradient(135deg, #1e1b4b, #312e81);color:white;padding:6rem 1rem;text-align:center;">
    <h1 style="font-size:3rem;font-weight:900;margin-bottom:1rem;"><?php echo e($ctMainHeading); ?></h1>
    <p style="font-size:1.1rem;max-width:600px;margin:0 auto;opacity:0.9;"><?php echo e($ctIntro); ?></p>
</div>


<section style="padding:4rem 1rem;background:#f9fafb;">
    <div style="max-width:1200px;margin:0 auto;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:3rem;">
            
            
            <div>
                
                <div style="background:linear-gradient(135deg, #312e81, #1e3a8a);color:white;border-radius:1rem;padding:2rem;margin-bottom:2rem;">
                    <h3 style="font-weight:bold;margin-bottom:1.5rem;"><?php echo e($ctInfoTitle); ?></h3>
                    <div style="space:1rem;">
                        <?php if($ctPhone): ?>
                        <p style="margin-bottom:1rem;"><strong><?php echo e($ctPhoneLabel); ?>:</strong><br><a href="tel:<?php echo e($ctPhone); ?>" style="color:#fff;text-decoration:none;"><?php echo e($ctPhone); ?></a></p>
                        <?php endif; ?>
                        <?php if($ctEmail): ?>
                        <p style="margin-bottom:1rem;"><strong><?php echo e($ctEmailLabel); ?>:</strong><br><a href="mailto:<?php echo e($ctEmail); ?>" style="color:#fff;text-decoration:none;"><?php echo e($ctEmail); ?></a></p>
                        <?php endif; ?>
                        <?php if($ctAddress): ?>
                        <p><strong><?php echo e($ctAddressLabel); ?>:</strong><br><?php echo e($ctAddress); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                
                <?php
                    $socialLinks = [
                        ['name' => 'Facebook', 'url' => $facebookUrl, 'color' => '#1877F2', 'hoverColor' => '#165FE5', 'icon' => 'fab fa-facebook'],
                        ['name' => 'Telegram', 'url' => $telegramUrl, 'color' => '#0088cc', 'hoverColor' => '#006fa3', 'icon' => 'fab fa-telegram'],
                        ['name' => 'TikTok', 'url' => $tiktokUrl, 'color' => '#000000', 'hoverColor' => '#333333', 'icon' => 'fab fa-tiktok'],
                        ['name' => 'YouTube', 'url' => $youtubeUrl, 'color' => '#FF0000', 'hoverColor' => '#cc0000', 'icon' => 'fab fa-youtube'],
                        ['name' => 'Instagram', 'url' => $instagramUrl, 'color' => '#E4405F', 'hoverColor' => '#d93175', 'icon' => 'fab fa-instagram'],
                    ];
                    $activeSocial = array_filter($socialLinks, fn($link) => !empty(trim($link['url'])));
                ?>

                <?php if(!empty($activeSocial)): ?>
                <div style="background:white;border:1px solid #e5e7eb;border-radius:1rem;padding:2rem;margin-bottom:2rem;">
                    <div style="text-align:center;margin-bottom:1.5rem;">
                        <h3 style="font-weight:700;font-size:1.25rem;margin:0 0 0.5rem 0;color:#111827;">Connect With Us</h3>
                        <p style="font-size:0.85rem;color:#6b7280;margin:0;">Follow us on social media for updates and news</p>
                    </div>
                    <div style="display:flex;justify-content:center;gap:1rem;flex-wrap:wrap;">
                        <?php $__currentLoopData = $activeSocial; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $social): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e($social['url']); ?>" target="_blank" rel="noopener noreferrer"
                           style="display:inline-flex;align-items:center;justify-content:center;width:52px;height:52px;background:<?php echo e($social['color']); ?>;border-radius:12px;text-decoration:none;transition:all 0.3s cubic-bezier(0.4, 0, 0.2, 1);box-shadow:0 2px 8px rgba(0,0,0,0.15);font-size:24px;"
                           onmouseover="this.style.background='<?php echo e($social['hoverColor']); ?>';this.style.transform='translateY(-4px) scale(1.08)';this.style.boxShadow='0 8px 16px rgba(0,0,0,0.25)';"
                           onmouseout="this.style.background='<?php echo e($social['color']); ?>';this.style.transform='translateY(0) scale(1)';this.style.boxShadow='0 2px 8px rgba(0,0,0,0.15)';"
                           title="<?php echo e($social['name']); ?>">
                            <i class="<?php echo e($social['icon']); ?>" style="color:white;"></i>
                        </a>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
                <?php endif; ?>

                <div style="background:white;border:1px solid #e5e7eb;border-radius:1rem;padding:1.5rem;margin-bottom:2rem;">
                    <h3 style="font-weight:bold;margin-bottom:0.5rem;color:#000;"><?php echo e($ctGitTitle); ?></h3>
                    <p style="color:#666;font-size:0.95rem;"><?php echo e($ctGitDesc); ?></p>
                </div>
            </div>

            
            <div>
                <div style="background:white;border:1px solid #e5e7eb;border-radius:1rem;padding:2rem;">
                    <form id="contactForm" style="display:flex;flex-direction:column;gap:1.5rem;">
                        <?php echo csrf_field(); ?>
                        
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


<?php if($ctTagline || $ctFooterDesc): ?>
<div style="background:#1f2937;color:white;padding:2rem;text-align:center;">
    <?php if($ctTagline): ?>
        <h4 style="font-weight:bold;font-size:1.25rem;margin-bottom:0.5rem;"><?php echo e($ctTagline); ?></h4>
    <?php endif; ?>
    <?php if($ctFooterDesc): ?>
        <p style="font-size:0.95rem;"><?php echo e($ctFooterDesc); ?></p>
    <?php endif; ?>
</div>
<?php endif; ?>

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
        const response = await fetch('<?php echo e(route("contact.submit")); ?>', {
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
<?php /**PATH C:\xammpp82\htdocs\albarekaaaaaa\resources\views/livewire/store/contact-page.blade.php ENDPATH**/ ?>