<?php 
// 1. Soo xir sidebar.php (Isaga ayaa daryeelaya session-ka iyo hubinta login-ka)
include 'sidebar.php'; 
require_once 'database.php';

// ==========================================
// SEARCH LOGIC
// ==========================================
$search_query = "";
if (isset($_POST['search_action']) && !empty($_POST['search_term'])) {
    $search_term = mysqli_real_escape_string($conn, $_POST['search_term']);
    $search_query = " WHERE customer_name LIKE '%$search_term%' OR phone LIKE '%$search_term%' ";
}

// ==========================================
// DATA FETCHING (QUERIES FOR CARDS)
// ==========================================
// 1. Total Customers
$total_cust = $conn->query("SELECT COUNT(*) as total FROM customers")->fetch_assoc()['total'] ?? 0;
// 2. Paid Bills & Unpaid Bills
$paid_bills = $conn->query("SELECT COUNT(*) as total FROM bills WHERE status='Paid'")->fetch_assoc()['total'] ?? 0;
$unpaid_bills = $conn->query("SELECT COUNT(*) as total FROM bills WHERE status='Unpaid'")->fetch_assoc()['total'] ?? 0;
// 3. Active & Deactive Customers
$active_cust = $conn->query("SELECT COUNT(*) as total FROM customers WHERE status='Active'")->fetch_assoc()['total'] ?? 0;
$deactive_cust = $conn->query("SELECT COUNT(*) as total FROM customers WHERE status='Inactive'")->fetch_assoc()['total'] ?? 0;
// 4. Total System Users
$total_users = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'] ?? 0;

// ==========================================
// NOTIFICATIONS LOGIC
// ==========================================
$notifications = [];
if ($unpaid_bills > 5) { $notifications[] = "Attention: There are ($unpaid_bills) pending unpaid invoices requiring collection."; }
if ($deactive_cust > 0) { $notifications[] = "Alert: ($deactive_cust) customer accounts are currently flagged as Inactive."; }
if (empty($notifications)) { $notifications[] = "System Status: All municipal water modules are running optimally."; }
?>

<!-- CHARTJS LIBRARY (waxaa lagu daray halkan si aan looga baahnayn in sidebar.php la taabto) -->
<script>
if (typeof Chart === 'undefined') {
    document.write('<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"><\/script>');
}
</script>

<!-- MAIN CONTAINER WITH FIXED HEIGHT, NO SCROLL & INCREASED GAP -->
<div class="h-full flex flex-col justify-between gap-5 overflow-hidden select-none">
    
    <!-- TOP BAR: SEARCH, NOTIFICATION & DATE -->
    <div class="flex flex-col md:flex-row justify-between items-center bg-slate-800 px-5 py-3 rounded-xl border border-slate-700/50 gap-4 shrink-0 shadow-lg">
        <!-- Search & Clear Form -->
        <form action="" method="POST" class="flex items-center gap-2 w-full md:w-auto">
            <div class="relative w-full md:w-72">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" name="search_term" value="<?php echo isset($_POST['search_term']) ? htmlspecialchars($_POST['search_term']) : ''; ?>" placeholder="Search customers..." class="w-full bg-slate-900/60 border border-slate-700 rounded-xl pl-9 pr-4 py-1.5 text-xs text-white placeholder-slate-400 focus:outline-none focus:border-cyan-500 transition-all">
            </div>
            <button type="submit" name="search_action" class="bg-cyan-500 hover:bg-cyan-600 text-white font-medium px-4 py-1.5 rounded-xl text-xs transition-all transform hover:-translate-y-[1px]">Search</button>
            <?php if(!empty($search_query)): ?>
                <a href="index.php" class="bg-slate-700 hover:bg-slate-600 text-slate-300 px-3 py-1.5 rounded-xl text-xs transition-all flex items-center gap-1"><i class="fa-solid fa-xmark"></i> Clear</a>
            <?php endif; ?>
        </form>

        <!-- Dynamic Notification Ticker -->
        <div class="flex items-center gap-2 flex-1 max-w-md bg-slate-900/40 border border-slate-700/30 px-3 py-1.5 rounded-xl overflow-hidden w-full">
            <i class="fa-solid fa-bell text-amber-400 animate-pulse text-xs shrink-0"></i>
            <marquee class="text-xs text-slate-300 font-medium" scrollamount="3"><?php echo implode(" | ", $notifications); ?></marquee>
        </div>

        <!-- System Date -->
        <div class="text-xs font-semibold text-slate-400 bg-slate-900/50 px-4 py-2 rounded-xl border border-slate-700/30 shrink-0">
            <i class="fa-regular fa-calendar mr-2 text-cyan-400"></i><?php echo date('F d, Y'); ?>
        </div>
    </div>

    <!-- 5 STATISTICAL CARDS GRID -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 shrink-0">
        <!-- 1. Total Customers -->
        <div class="bg-slate-800 p-3.5 rounded-xl border border-slate-700/50 shadow-md flex items-center justify-between transition-all duration-200 transform hover:-translate-y-[2px] hover:bg-slate-700/20 group">
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Customers</p>
                <h3 class="text-xl font-extrabold text-white mt-1"><?php echo $total_cust; ?></h3>
            </div>
            <div class="w-8 h-8 bg-blue-500/10 text-blue-400 flex items-center justify-center text-sm rounded-lg border border-blue-500/20 group-hover:bg-blue-500 group-hover:text-white transition-all">
                <i class="fa-solid fa-users"></i>
            </div>
        </div>
        <!-- 2. Paid Bills -->
        <div class="bg-slate-800 p-3.5 rounded-xl border border-slate-700/50 shadow-md flex items-center justify-between transition-all duration-200 transform hover:-translate-y-[2px] hover:bg-slate-700/20 group">
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Paid Bills</p>
                <h3 class="text-xl font-extrabold text-emerald-400 mt-1"><?php echo $paid_bills; ?></h3>
            </div>
            <div class="w-8 h-8 bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-sm rounded-lg border border-emerald-500/20 group-hover:bg-emerald-500 group-hover:text-white transition-all">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </div>
        <!-- 3. Unpaid Bills -->
        <div class="bg-slate-800 p-3.5 rounded-xl border border-slate-700/50 shadow-md flex items-center justify-between transition-all duration-200 transform hover:-translate-y-[2px] hover:bg-slate-700/20 group">
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Unpaid Bills</p>
                <h3 class="text-xl font-extrabold text-rose-400 mt-1"><?php echo $unpaid_bills; ?></h3>
            </div>
            <div class="w-8 h-8 bg-rose-500/10 text-rose-400 flex items-center justify-center text-sm rounded-lg border border-rose-500/20 group-hover:bg-rose-500 group-hover:text-white transition-all">
                <i class="fa-solid fa-circle-xmark"></i>
            </div>
        </div>
        <!-- 4. Active / Deactive Status -->
        <div class="bg-slate-800 p-3.5 rounded-xl border border-slate-700/50 shadow-md flex items-center justify-between transition-all duration-200 transform hover:-translate-y-[2px] hover:bg-slate-700/20 group">
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Active / Inactive</p>
                <h3 class="text-sm font-extrabold text-white mt-1">
                    <span class="text-cyan-400"><?php echo $active_cust; ?></span> <span class="text-slate-500">/</span> <span class="text-amber-500"><?php echo $deactive_cust; ?></span>
                </h3>
            </div>
            <div class="w-8 h-8 bg-cyan-500/10 text-cyan-400 flex items-center justify-center text-sm rounded-lg border border-cyan-500/20 group-hover:bg-cyan-500 group-hover:text-white transition-all">
                <i class="fa-solid fa-toggle-on"></i>
            </div>
        </div>
        <!-- 5. System Users -->
        <div class="bg-slate-800 p-3.5 rounded-xl border border-slate-700/50 shadow-md flex items-center justify-between transition-all duration-200 transform hover:-translate-y-[2px] hover:bg-slate-700/20 group">
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">System Users</p>
                <h3 class="text-xl font-extrabold text-purple-400 mt-1"><?php echo $total_users; ?></h3>
            </div>
            <div class="w-8 h-8 bg-purple-500/10 text-purple-400 flex items-center justify-center text-sm rounded-lg border border-purple-500/20 group-hover:bg-purple-500 group-hover:text-white transition-all">
                <i class="fa-solid fa-user-shield"></i>
            </div>
        </div>
    </div>

    <!-- MIDDLE SECTION: CHARTS BLOCK WITH ADJUSTED HEIGHT -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 h-[190px] shrink-0">
        <!-- Chart 1: Monthly Collection -->
        <div class="bg-slate-800 p-3 rounded-xl border border-slate-700/50 shadow-md flex flex-col h-full">
            <h4 class="text-xs font-bold text-slate-300 mb-1 flex items-center gap-1.5"><i class="fa-solid fa-chart-line text-cyan-400"></i> Monthly Collection Curve</h4>
            <div class="flex-1 relative min-h-0 w-full">
                <canvas id="monthlyCollectionChart"></canvas>
            </div>
        </div>
        <!-- Chart 2: Bill Status -->
        <div class="bg-slate-800 p-3 rounded-xl border border-slate-700/50 shadow-md flex flex-col h-full">
            <h4 class="text-xs font-bold text-slate-300 mb-1 flex items-center gap-1.5"><i class="fa-solid fa-chart-pie text-emerald-400"></i> Invoice Allocation Ratio</h4>
            <div class="flex-1 relative min-h-0 w-full">
                <canvas id="billStatusChart"></canvas>
            </div>
        </div>
    </div>

    <!-- BOTTOM SECTION: DATA GRID (LAST 10 CUSTOMERS) -->
    <div class="bg-slate-800 rounded-xl border border-slate-700/50 shadow-md flex flex-col flex-1 min-h-0 overflow-hidden">
        <div class="px-4 py-2.5 border-b border-slate-700/50 bg-slate-900/20 flex justify-between items-center shrink-0">
            <h4 class="text-xs font-bold text-slate-200 flex items-center gap-1.5"><i class="fa-solid fa-list text-cyan-400"></i> Core Roster - Last 10 Enrolled Customers</h4>
            <span class="text-[10px] bg-slate-700 text-slate-300 font-medium px-2 py-0.5 rounded-md">Live Stream</span>
        </div>
        
        <!-- Table Grid Container -->
        <div class="flex-1 overflow-hidden">
            <table class="w-full text-left text-xs border-collapse h-full">
                <thead class="bg-slate-900/40 text-slate-400 font-semibold sticky top-0 border-b border-slate-700/50">
                    <tr>
                        <th class="p-2.5 pl-4">ID</th>
                        <th class="p-2.5">Customer Name</th>
                        <th class="p-2.5">Phone Number</th>
                        <th class="p-2.5">District Address</th>
                        <th class="p-2.5 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700/30">
                    <?php 
                    $sql_grid = "SELECT * FROM customers $search_query ORDER BY id DESC LIMIT 10";
                    $grid_res = $conn->query($sql_grid);
                    
                    if($grid_res && $grid_res->num_rows > 0):
                        while($row = $grid_res->fetch_assoc()):
                            $status_color = ($row['status'] == 'Active') ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border-rose-500/20';
                    ?>
                        <tr class="hover:bg-slate-700/30 transition-colors">
                            <td class="p-2 pl-4 font-mono font-bold text-slate-400">#<?php echo $row['id']; ?></td>
                            <td class="p-2 font-medium text-white"><?php echo htmlspecialchars($row['customer_name'] ?? 'N/A'); ?></td>
                            <td class="p-2 text-slate-300"><?php echo htmlspecialchars($row['phone'] ?? 'N/A'); ?></td>
                            <td class="p-2 text-slate-400"><?php echo htmlspecialchars($row['address'] ?? 'N/A'); ?></td>
                            <td class="p-2 text-center">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border <?php echo $status_color; ?>"><?php echo $row['status'] ?? 'Active'; ?></span>
                            </td>
                        </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <tr>
                            <td colspan="5" class="text-center p-8 text-slate-500 font-medium">No customer records matching the requested query were found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- CHARTJS ENGINE INTEGRATION -->
<script>
function renderDashboardCharts() {
    // Jaantuska 1: Monthly Collection (Live)
    const ctxCollection = document.getElementById('monthlyCollectionChart').getContext('2d');
    new Chart(ctxCollection, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Collection ($)',
                data: [
                    <?php 
                    // Koodhka PHP ee live-ka ah ee soo saaraya wadarta lacagta bishiiba
                    for ($m = 1; $m <= 12; $m++) {
                        $res = $conn->query("SELECT SUM(amount_paid) as total FROM payments WHERE MONTH(payment_date) = $m AND YEAR(payment_date) = " . date('Y'));
                        $row = $res->fetch_assoc();
                        echo ($row['total'] ?? 0) . ($m < 12 ? ',' : '');
                    }
                    ?>
                ],
                backgroundColor: 'rgba(6, 182, 212, 0.4)',
                borderColor: '#06b6d4',
                borderWidth: 1.5,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 9 } } },
                y: { grid: { color: 'rgba(148, 163, 184, 0.1)' }, ticks: { color: '#94a3b8', font: { size: 9 } } }
            }
        }
    });

    // Jaantuska 2: Paid vs Unpaid Bills (Live)
    const ctxStatus = document.getElementById('billStatusChart').getContext('2d');
    new Chart(ctxStatus, {
        type: 'bar',
        data: {
            labels: ['Paid', 'Unpaid'],
            datasets: [{
                data: [
                    <?php 
                    // Koodhka PHP ee live-ka ah ee tirinaya biilasha
                    $paid = $conn->query("SELECT COUNT(*) as t FROM bills WHERE status = 'Paid'")->fetch_assoc()['t'];
                    $unpaid = $conn->query("SELECT COUNT(*) as t FROM bills WHERE status = 'Unpaid'")->fetch_assoc()['t'];
                    echo (int)$paid . ',' . (int)$unpaid;
                    ?>
                ],
                backgroundColor: ['rgba(16, 185, 129, 0.4)', 'rgba(244, 63, 94, 0.4)'],
                borderColor: ['#10b981', '#f43f5e'],
                borderWidth: 1.5,
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { color: 'rgba(148, 163, 184, 0.1)' }, ticks: { color: '#94a3b8', font: { size: 9 } } },
                y: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 9 } } }
            }
        }
    });
}

// Haddii Chart.js horeyba loo geliyay (sidebar.php), isla markiiba ku shubo.
// Haddii kale, sug ilaa CDN-ka aan ku darnay uu si buuxda u soo dhamaado.
if (typeof Chart !== 'undefined') {
    renderDashboardCharts();
} else {
    window.addEventListener('load', function () {
        if (typeof Chart !== 'undefined') {
            renderDashboardCharts();
        } else {
            console.error('Chart.js lama helin - hubi in internet-ku shaqeynayo ama CDN-ka la awoodo.');
        }
    });
}
</script>

<?php 
echo "  </main>
      </body>
      </html>"; 
?>