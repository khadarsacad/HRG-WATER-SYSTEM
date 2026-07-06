<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Theme (Light/Dark) waxaa lagu kaydiyaa localStorage; qiimihiisu waxaa lagu dajiyaa body-ga si toos ah marka bogga la furayo -->

    <!-- LIGHT MODE OVERRIDES: hal block CSS oo overwrite garaya midabada Tailwind boggo oo dhan, iyada oo aan la taaban file-yada kale
         MUHIIM: waxaan isticmaalnaa [class*="..."] (substring match) halkii [class~="..."] (exact match),
         si loo daboolo dhammaan opacity variants-ka Tailwind (tusaale bg-slate-800/50, bg-slate-900/70, iwm)
         oo aanay noqonin token gaar ah oo aan la wadaagin liiska hore -->
    <style>
        body.light-mode { background-color: #f8fafc !important; color: #0f172a !important; }

        /* Backgrounds - slate 950/900 (dhammaan opacity variants) */
        body.light-mode [class*="bg-slate-950"],
        body.light-mode [class*="bg-slate-900"] { background-color: #ffffff !important; }

        /* Backgrounds - slate 800 (dhammaan opacity variants) */
        body.light-mode [class*="bg-slate-800"] { background-color: #f1f5f9 !important; }

        /* Backgrounds - slate 700 (dhammaan opacity variants, hover:bg-slate-700 wuu ku jiraa) */
        body.light-mode [class*="bg-slate-700"] { background-color: #e2e8f0 !important; }

        /* Backgrounds - slate 600 (haddii jira meel kale) */
        body.light-mode [class*="bg-slate-600"] { background-color: #cbd5e1 !important; }

        /* Borders - slate 700/600 (dhammaan opacity variants) iyo divide (border u dhaxeeya rows) */
        body.light-mode [class*="border-slate-700"],
        body.light-mode [class*="divide-slate-700"] { border-color: #cbd5e1 !important; }

        body.light-mode [class*="border-slate-600"] { border-color: #94a3b8 !important; }

        /* Text colors - slate/white text */
        body.light-mode [class*="text-white"] { color: #0f172a !important; }
        body.light-mode [class*="text-slate-100"] { color: #1e293b !important; }
        body.light-mode [class*="text-slate-200"] { color: #334155 !important; }
        body.light-mode [class*="text-slate-300"] { color: #475569 !important; }
        body.light-mode [class*="text-slate-400"] { color: #64748b !important; }
        body.light-mode [class*="text-slate-500"] { color: #94a3b8 !important; }

        /* Status/alert badge backgrounds (dark-tinted -> light-tinted) */
        body.light-mode [class*="bg-red-900"] { background-color: #fee2e2 !important; }
        body.light-mode [class*="bg-amber-900"] { background-color: #fef3c7 !important; }
        body.light-mode [class*="bg-emerald-900"] { background-color: #d1fae5 !important; }
        body.light-mode [class*="bg-rose-900"] { background-color: #ffe4e6 !important; }

        /* Status/alert badge text */
        body.light-mode [class*="text-red-300"] { color: #b91c1c !important; }
        body.light-mode [class*="text-amber-300"] { color: #b45309 !important; }
        body.light-mode [class*="text-emerald-400"],
        body.light-mode [class*="text-emerald-300"] { color: #047857 !important; }
        body.light-mode [class*="text-rose-400"],
        body.light-mode [class*="text-rose-300"] { color: #be123c !important; }

        body.light-mode ::placeholder { color: #94a3b8 !important; opacity: 1; }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 flex h-screen overflow-hidden">
    <script>
        // Marka bogga si buuxda u soo furmo, class-ka light-mode waxaa lagu darayaa body-ga (haddii horeyba loo doortay)
        if (localStorage.getItem('site_theme') === 'light') {
            document.body.classList.add('light-mode');
        }

        function updateThemeIcon() {
            let icon = document.getElementById('themeIcon');
            if (!icon) return;
            if (document.body.classList.contains('light-mode')) {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
            } else {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            }
        }

        function toggleTheme() {
            document.body.classList.toggle('light-mode');

            if (document.body.classList.contains('light-mode')) {
                localStorage.setItem('site_theme', 'light');
            } else {
                localStorage.setItem('site_theme', 'dark');
            }

            updateThemeIcon();
        }

        document.addEventListener('DOMContentLoaded', updateThemeIcon);
    </script>


    <!-- SIDEBAR CONTAINER (PERFECTLY SIZED & NO SCROLL) -->
    <aside class="w-64 bg-slate-800 border-r border-slate-700/50 flex flex-col justify-between h-screen shrink-0 select-none overflow-hidden">
        
        <div class="flex-1 min-h-0 flex flex-col">
            <!-- SIDEBAR HEADER / BRAND -->
            <div class="px-5 py-4 shrink-0 flex items-center justify-between">
                <h1 class="text-sm font-extrabold text-white tracking-wide">Water <span class="text-cyan-400">System</span></h1>
                <button onclick="toggleTheme()" id="themeToggleBtn" title="Beddel Light/Dark Mode" class="w-8 h-8 shrink-0 flex items-center justify-center rounded-lg bg-slate-700/40 hover:bg-slate-700 text-amber-300 transition-all">
                    <i class="fa-solid fa-sun" id="themeIcon"></i>
                </button>
            </div>

            <!-- Navigation Links (With Smooth Hover & Lift Effect) -->
            <nav class="pt-2 px-4 pb-4 space-y-1 flex-1 min-h-0 overflow-y-auto">
                
                <!-- 1. Dashboard -->
                <?php if (isset($_SESSION['can_dashboard']) && $_SESSION['can_dashboard'] == 1): ?>
                    <a href="index.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 transform <?php echo ($current_page == 'index.php') ? 'bg-cyan-500 text-white shadow-lg shadow-cyan-500/30 font-semibold' : 'text-slate-400 hover:bg-slate-700/30 hover:text-cyan-400 hover:-translate-y-[2px]'; ?>">
                        <i class="fa-solid fa-chart-pie w-5 text-center text-base"></i>
                        <span>Dashboard</span>
                    </a>
                <?php endif; ?>

                <!-- 2. Customers -->
                <?php if (isset($_SESSION['can_customers']) && $_SESSION['can_customers'] == 1): ?>
                    <a href="customers.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 transform <?php echo ($current_page == 'customers.php') ? 'bg-cyan-500 text-white shadow-lg shadow-cyan-500/30 font-semibold' : 'text-slate-400 hover:bg-slate-700/30 hover:text-cyan-400 hover:-translate-y-[2px]'; ?>">
                        <i class="fa-solid fa-users w-5 text-center text-base"></i>
                        <span>Customers</span>
                    </a>
                <?php endif; ?>

                <!-- 3. Meters -->
                <?php if (isset($_SESSION['can_meters']) && $_SESSION['can_meters'] == 1): ?>
                    <a href="meters.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 transform <?php echo ($current_page == 'meters.php') ? 'bg-cyan-500 text-white shadow-lg shadow-cyan-500/30 font-semibold' : 'text-slate-400 hover:bg-slate-700/30 hover:text-cyan-400 hover:-translate-y-[2px]'; ?>">
                        <i class="fa-solid fa-faucet-drip w-5 text-center text-base"></i>
                       
                        <span>Meters</span>
                    </a>
                <?php endif; ?>

                <!-- 3. Readings -->
                <?php if (isset($_SESSION['can_readings']) && $_SESSION['can_readings'] == 1): ?>
                    <a href="readings.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 transform <?php echo ($current_page == 'readings.php') ? 'bg-cyan-500 text-white shadow-lg shadow-cyan-500/30 font-semibold' : 'text-slate-400 hover:bg-slate-700/30 hover:text-cyan-400 hover:-translate-y-[2px]'; ?>">
                        <i class="fa-solid fa-gauge w-5 text-center text-base"></i>
                        <span>Readings</span>
                    </a>
                <?php endif; ?>
                <!-- 4. Bills -->
                <?php if (isset($_SESSION['can_bills']) && $_SESSION['can_bills'] == 1): ?>
                    <a href="bills.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 transform <?php echo ($current_page == 'bills.php') ? 'bg-cyan-500 text-white shadow-lg shadow-cyan-500/30 font-semibold' : 'text-slate-400 hover:bg-slate-700/30 hover:text-cyan-400 hover:-translate-y-[2px]'; ?>">
                        <i class="fa-solid fa-file-invoice-dollar w-5 text-center text-base"></i>
                        <span>Bills</span>
                    </a>
                <?php endif; ?>

                <!-- 5. Payments -->
                <?php if (isset($_SESSION['can_payments']) && $_SESSION['can_payments'] == 1): ?>
                    <a href="payments.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 transform <?php echo ($current_page == 'payments.php') ? 'bg-cyan-500 text-white shadow-lg shadow-cyan-500/30 font-semibold' : 'text-slate-400 hover:bg-slate-700/30 hover:text-cyan-400 hover:-translate-y-[2px]'; ?>">
                        <i class="fa-solid fa-credit-card w-5 text-center text-base"></i>
                        <span>Payments</span>
                    </a>
                <?php endif; ?>

                <!-- 6. Reports -->
                <?php if (isset($_SESSION['can_reports']) && $_SESSION['can_reports'] == 1): ?>
                    <a href="reports.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 transform <?php echo ($current_page == 'reports.php') ? 'bg-cyan-500 text-white shadow-lg shadow-cyan-500/30 font-semibold' : 'text-slate-400 hover:bg-slate-700/30 hover:text-cyan-400 hover:-translate-y-[2px]'; ?>">
                        <i class="fa-solid fa-file-lines w-5 text-center text-base"></i>
                        <span>Reports</span>
                    </a>
                <?php endif; ?>

                <!-- 7. Users -->
                <?php if (isset($_SESSION['can_users_page']) && $_SESSION['can_users_page'] == 1): ?>
                    <a href="users.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 transform <?php echo ($current_page == 'users.php') ? 'bg-cyan-500 text-white shadow-lg shadow-cyan-500/30 font-semibold' : 'text-slate-400 hover:bg-slate-700/30 hover:text-cyan-400 hover:-translate-y-[2px]'; ?>">
                        <i class="fa-solid fa-user-gear w-5 text-center text-base"></i>
                        <span>Users</span>
                    </a>
                <?php endif; ?>

                <!-- 8. Account -->
                <a href="account.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 transform <?php echo ($current_page == 'account.php') ? 'bg-cyan-500 text-white shadow-lg shadow-cyan-500/30 font-semibold' : 'text-slate-400 hover:bg-slate-700/30 hover:text-cyan-400 hover:-translate-y-[2px]'; ?>">
                    <i class="fa-solid fa-user-lock w-5 text-center text-base"></i>
                    <span>Account</span>
                </a>

                <!-- 9. Settings -->
                <?php if (isset($_SESSION['can_settings']) && $_SESSION['can_settings'] == 1): ?>
                    <a href="settings.php" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 transform <?php echo ($current_page == 'settings.php') ? 'bg-cyan-500 text-white shadow-lg shadow-cyan-500/30 font-semibold' : 'text-slate-400 hover:bg-slate-700/30 hover:text-cyan-400 hover:-translate-y-[2px]'; ?>">
                        <i class="fa-solid fa-sliders w-5 text-center text-base"></i>
                        <span>Settings</span>
                    </a>
                <?php endif; ?>

            </nav>
        </div>

        <!-- SIDEBAR FOOTER (LOGOUT) -->
        <div class="p-4 shrink-0">
            <a href="logout.php" onclick="return confirm('Are you sure you want to sign out?')" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium text-red-400 hover:bg-red-500/10 hover:text-red-300 transition-all duration-200 transform hover:-translate-y-[2px]">
                <i class="fa-solid fa-arrow-right-from-bracket w-5 text-center text-base"></i>
                <span>Logout</span>
            </a>
        </div>

    </aside>

    <!-- CONTENT DISPLAY AREA -->
    <main class="flex-1 p-8 bg-slate-900 h-screen overflow-y-auto">