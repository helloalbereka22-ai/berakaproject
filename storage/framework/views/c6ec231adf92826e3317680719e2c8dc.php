<div class="space-y-6 max-w-3xl mx-auto">

    
    <div class="flex items-center gap-4">
        <a href="<?php echo e(route('admin.dashboard')); ?>" wire:navigate
           class="p-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white transition">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h2 class="text-2xl font-extrabold text-white tracking-tight">Page Content Settings</h2>
            <p class="text-sm text-slate-400 mt-0.5">Edit content shown on all public website pages</p>
        </div>
    </div>

    
    <div x-data="{ show: false }"
         x-on:saved.window="show = true; setTimeout(() => show = false, 3000)"
         x-show="show" x-transition
         class="flex items-center gap-2 px-4 py-2.5 bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 text-sm rounded-xl">
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        Content saved successfully.
    </div>

    
    <div class="bg-slate-900 border border-indigo-700/30 rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-800 flex items-center gap-2 bg-indigo-500/5">
            <span class="text-lg">🏠</span>
            <div>
                <h3 class="font-bold text-white text-sm">Home Page Hero</h3>
                <p class="text-xs text-slate-500">Displayed in the hero/banner section of the home page</p>
            </div>
            <a href="<?php echo e(route('home')); ?>" target="_blank"
               class="ml-auto text-xs text-indigo-400 hover:text-indigo-300 transition">Preview →</a>
        </div>
        <form wire:submit="saveHome" class="p-6 space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Hero Title</label>
                <input wire:model="homeHeroTitle" type="text"
                       placeholder="e.g. Welcome to Al-Bareka"
                       class="w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-xl text-sm text-white
                              placeholder-slate-500 focus:border-indigo-500 outline-none transition">
                <?php $__errorArgs = ['homeHeroTitle'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-xs text-red-400 mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Hero Subtitle</label>
                <textarea wire:model="homeHeroSubtitle" rows="3"
                          placeholder="e.g. Quality lubricating oils and books delivered to your door."
                          class="w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-xl text-sm text-white
                                 placeholder-slate-500 focus:border-indigo-500 outline-none transition resize-y leading-relaxed"></textarea>
                <?php $__errorArgs = ['homeHeroSubtitle'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-xs text-red-400 mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="flex justify-end">
                <button type="submit"
                        class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold rounded-xl transition">
                    <span wire:loading.remove wire:target="saveHome">Save Home Page</span>
                    <span wire:loading wire:target="saveHome">Saving…</span>
                </button>
            </div>
        </form>
    </div>

    
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-800 flex items-center gap-2">
            <span class="text-lg">📖</span>
            <div>
                <h3 class="font-bold text-white text-sm">About Us Content</h3>
                <p class="text-xs text-slate-500">Displayed on the About Us page</p>
            </div>
            <a href="<?php echo e(route('about')); ?>" target="_blank"
               class="ml-auto text-xs text-indigo-400 hover:text-indigo-300 transition">Preview →</a>
        </div>
        <form wire:submit="saveAbout" class="p-6 space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">
                    About Us Text
                    <span class="text-slate-600 font-normal normal-case ml-1">(first line becomes the heading)</span>
                </label>
                <textarea wire:model="aboutUsContent" rows="6"
                          placeholder="About Al-Baraka&#10;Al-Bareka Business Group is a diversified organization..."
                          class="w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-xl text-sm text-white
                                 placeholder-slate-500 focus:border-indigo-500 outline-none transition resize-y leading-relaxed"></textarea>
                <?php $__errorArgs = ['aboutUsContent'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-xs text-red-400 mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="flex justify-end">
                <button type="submit"
                        class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-bold rounded-xl transition">
                    <span wire:loading.remove wire:target="saveAbout">Save About Us</span>
                    <span wire:loading wire:target="saveAbout">Saving…</span>
                </button>
            </div>
        </form>
    </div>

    
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-800 flex items-center gap-2">
            <span class="text-lg">🎯</span>
            <div>
                <h3 class="font-bold text-white text-sm">Vision, Mission & Values</h3>
                <p class="text-xs text-slate-500">Shown in "Our Foundation" section on About page</p>
            </div>
            <a href="<?php echo e(route('about')); ?>" target="_blank"
               class="ml-auto text-xs text-violet-400 hover:text-violet-300 transition">Preview →</a>
        </div>
        <form wire:submit="saveFoundation" class="p-6 space-y-5">
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">🎯 Our Vision</label>
                <textarea wire:model="visionText" rows="3"
                          placeholder="To be a leading and trusted organization..."
                          class="w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-xl text-sm text-white
                                 placeholder-slate-500 focus:border-violet-500 outline-none transition resize-y leading-relaxed"></textarea>
                <?php $__errorArgs = ['visionText'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-xs text-red-400 mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">🚀 Our Mission</label>
                <textarea wire:model="missionText" rows="3"
                          placeholder="To manufacture and distribute premium quality lubricating oils..."
                          class="w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-xl text-sm text-white
                                 placeholder-slate-500 focus:border-violet-500 outline-none transition resize-y leading-relaxed"></textarea>
                <?php $__errorArgs = ['missionText'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-xs text-red-400 mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">💎 Our Values</label>
                <input wire:model="valuesText" type="text"
                       placeholder="Quality · Integrity · Service Excellence · Community Focus"
                       class="w-full px-4 py-3 bg-slate-800 border border-slate-700 rounded-xl text-sm text-white
                              placeholder-slate-500 focus:border-violet-500 outline-none transition">
                <?php $__errorArgs = ['valuesText'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-xs text-red-400 mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                <p class="text-xs text-slate-600 mt-1">Separate values with · (middle dot) or commas</p>
            </div>
            <div class="flex justify-end">
                <button type="submit"
                        class="px-5 py-2.5 bg-violet-600 hover:bg-violet-500 text-white text-sm font-bold rounded-xl transition">
                    <span wire:loading.remove wire:target="saveFoundation">Save Foundation</span>
                    <span wire:loading wire:target="saveFoundation">Saving…</span>
                </button>
            </div>
        </form>
    </div>

    
    <div class="bg-slate-900 border border-amber-700/30 rounded-2xl overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-800 flex items-center gap-2 bg-amber-500/5">
            <span class="text-lg">📞</span>
            <div>
                <h3 class="font-bold text-white text-sm">Contact Us Page</h3>
                <p class="text-xs text-slate-500">All content displayed on the Contact Us page</p>
            </div>
            <a href="<?php echo e(route('contact')); ?>" target="_blank"
               class="ml-auto text-xs text-amber-400 hover:text-amber-300 transition">Preview →</a>
        </div>
        <form wire:submit="saveContact" class="p-6 space-y-8">

            
            <div>
                <h4 class="text-xs font-extrabold text-amber-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-amber-500/20 text-amber-400 flex items-center justify-center text-[10px] font-black">1</span>
                    Header Section
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Section Title</label>
                        <input wire:model="contactSectionTitle" type="text" placeholder="Contact Us"
                               class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:border-amber-500 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Main Heading</label>
                        <input wire:model="contactMainHeading" type="text" placeholder="We'd Love to Hear From You"
                               class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:border-amber-500 outline-none transition">
                    </div>
                </div>
                <div class="mt-4">
                    <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Introduction</label>
                    <textarea wire:model="contactIntroduction" rows="3"
                              placeholder="Have a question, need more information, or want to learn more..."
                              class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:border-amber-500 outline-none transition resize-y"></textarea>
                    <?php $__errorArgs = ['contactIntroduction'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-xs text-red-400 mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
            </div>

            <div class="border-t border-slate-800"></div>

            
            <div>
                <h4 class="text-xs font-extrabold text-amber-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-amber-500/20 text-amber-400 flex items-center justify-center text-[10px] font-black">2</span>
                    Contact Information
                </h4>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Section Title</label>
                        <input wire:model="contactInfoTitle" type="text" placeholder="Contact Information"
                               class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:border-amber-500 outline-none transition">
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Phone Label</label>
                            <input wire:model="contactPhoneLabel" type="text" placeholder="Phone"
                                   class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:border-amber-500 outline-none transition">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Phone Number</label>
                            <input wire:model="contactPhone" type="text" placeholder="0910099936"
                                   class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:border-amber-500 outline-none transition">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Email Label</label>
                            <input wire:model="contactEmailLabel" type="text" placeholder="Email"
                                   class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:border-amber-500 outline-none transition">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Email Address</label>
                            <input wire:model="contactEmail" type="text" placeholder="info@albereka12.com"
                                   class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:border-amber-500 outline-none transition">
                            <?php $__errorArgs = ['contactEmail'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-xs text-red-400 mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Address Label</label>
                            <input wire:model="contactAddressLabel" type="text" placeholder="Address"
                                   class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:border-amber-500 outline-none transition">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Address</label>
                            <input wire:model="contactAddress" type="text" placeholder="Siham Mole, Ethiopia"
                                   class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:border-amber-500 outline-none transition">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">WhatsApp Number <span class="text-slate-600 font-normal normal-case">(optional)</span></label>
                            <input wire:model="contactWhatsapp" type="text" placeholder="+251910099936"
                                   class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:border-amber-500 outline-none transition">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Business Hours <span class="text-slate-600 font-normal normal-case">(optional)</span></label>
                            <input wire:model="contactHours" type="text" placeholder="Mon–Sat, 8am–6pm"
                                   class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:border-amber-500 outline-none transition">
                        </div>
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-800"></div>

            
            <div>
                <h4 class="text-xs font-extrabold text-amber-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-amber-500/20 text-amber-400 flex items-center justify-center text-[10px] font-black">3</span>
                    Get In Touch
                </h4>
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Get In Touch Title</label>
                            <input wire:model="contactGetInTouchTitle" type="text" placeholder="Get In Touch"
                                   class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:border-amber-500 outline-none transition">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Location Label</label>
                            <input wire:model="contactLocationLabel" type="text" placeholder="Our Location"
                                   class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:border-amber-500 outline-none transition">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Get In Touch Description</label>
                        <textarea wire:model="contactGetInTouchDesc" rows="3"
                                  placeholder="Whether you have a question about our products, services, orders..."
                                  class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:border-amber-500 outline-none transition resize-y"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Location Description</label>
                        <textarea wire:model="contactLocationDesc" rows="2"
                                  placeholder="Visit Albereka at Siham Mole for more information..."
                                  class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:border-amber-500 outline-none transition resize-y"></textarea>
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-800"></div>

            
            <div>
                <h4 class="text-xs font-extrabold text-amber-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-amber-500/20 text-amber-400 flex items-center justify-center text-[10px] font-black">4</span>
                    Social Media <span class="text-slate-600 font-normal normal-case ml-1">(optional URLs)</span>
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Facebook URL</label>
                        <input wire:model="contactFacebook" type="url" placeholder="https://facebook.com/albereka"
                               class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:border-amber-500 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Telegram URL</label>
                        <input wire:model="contactTelegram" type="url" placeholder="https://t.me/albereka"
                               class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:border-amber-500 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">TikTok URL</label>
                        <input wire:model="contactTiktok" type="url" placeholder="https://tiktok.com/@albereka"
                               class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:border-amber-500 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">YouTube URL</label>
                        <input wire:model="contactYoutube" type="url" placeholder="https://youtube.com/@albereka"
                               class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:border-amber-500 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Instagram URL</label>
                        <input wire:model="contactInstagram" type="url" placeholder="https://instagram.com/albereka"
                               class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:border-amber-500 outline-none transition">
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-800"></div>

            
            <div>
                <h4 class="text-xs font-extrabold text-amber-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                    <span class="w-5 h-5 rounded-full bg-amber-500/20 text-amber-400 flex items-center justify-center text-[10px] font-black">5</span>
                    Footer / Closing
                </h4>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Closing Tagline</label>
                        <input wire:model="contactTagline" type="text" placeholder="Albereka — Quality, Service & Trust"
                               class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:border-amber-500 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Footer Description</label>
                        <textarea wire:model="contactFooterDesc" rows="2"
                                  placeholder="Committed to providing quality products, reliable service..."
                                  class="w-full px-4 py-2.5 bg-slate-800 border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:border-amber-500 outline-none transition resize-y"></textarea>
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit"
                        class="px-6 py-2.5 bg-amber-600 hover:bg-amber-500 text-white text-sm font-bold rounded-xl transition">
                    <span wire:loading.remove wire:target="saveContact">💾 Save Contact Page</span>
                    <span wire:loading wire:target="saveContact">Saving…</span>
                </button>
            </div>
        </form>
    </div>

</div>
<?php /**PATH C:\xammpp82\htdocs\albarekaaaaaa\resources\views/livewire/admin/system/gm-page-settings.blade.php ENDPATH**/ ?>