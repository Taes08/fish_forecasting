
<header
    class="h-20 bg-white border-b border-[#E4EBEF]
           px-5 lg:px-8
           flex items-center justify-between
           sticky top-0 z-20">

    <!-- LEFT SECTION -->
    <div class="flex items-center gap-4 min-w-0">

        <!-- MOBILE SIDEBAR BUTTON -->
        <button
            type="button"
            onclick="toggleSidebar()"
            aria-label="Toggle navigation"
            class="lg:hidden w-10 h-10 rounded-xl
                   flex items-center justify-center
                   text-[#0B3954]
                   hover:bg-[#E8F7F6]
                   transition-colors">

            <i data-lucide="menu" class="w-5 h-5"></i>
        </button>

        <!-- HEADER INFORMATION -->
        <div class="flex items-center gap-3">

            <div class="hidden sm:flex w-10 h-10
                        items-center justify-center
                        rounded-xl bg-[#E8F7F6]">

                <i data-lucide="waves"
                   class="w-5 h-5 text-[#087E8B]"></i>
            </div>

            <div class="min-w-0">

                <h2 class="text-sm font-bold text-[#0B3954]">
                    FishCast Management
                </h2>

                <p class="hidden sm:block
                          text-xs text-slate-400 mt-1">
                    Fish Price Intelligence Platform
                </p>

            </div>

        </div>

    </div>

    <!-- RIGHT SECTION -->
    <div class="flex items-center gap-3">

        <!-- PLATFORM LABEL -->
        <div class="hidden sm:flex items-center gap-2
                    px-3 py-2 rounded-lg
                    bg-[#E8F7F6]">

            <span class="w-2 h-2 rounded-full bg-[#2EC4B6]"></span>

            <span class="text-xs font-semibold text-[#087E8B]">
                FishCast
            </span>

        </div>

        <!-- HOMEPAGE -->
        <a
            href="../index.php"
            title="Go to Homepage"
            aria-label="Go to Homepage"
            class="w-10 h-10 rounded-xl
                   border border-[#E4EBEF]
                   flex items-center justify-center
                   text-slate-500
                   hover:text-[#087E8B]
                   hover:bg-[#E8F7F6]
                   transition-colors">

            <i data-lucide="house" class="w-5 h-5"></i>

        </a>

    </div>

</header>
