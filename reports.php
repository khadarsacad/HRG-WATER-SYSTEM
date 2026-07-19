<?php 
require_once 'database.php'; 
include 'sidebar.php'; 

// 1. Filter Logic
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'today';
$date_condition = "";

switch($filter) {
    case 'yesterday': $date_condition = "DATE(bill_date) = CURDATE() - INTERVAL 1 DAY"; break;
    case 'week':      $date_condition = "YEARWEEK(bill_date, 1) = YEARWEEK(CURDATE(), 1)"; break;
    case 'month':     $date_condition = "MONTH(bill_date) = MONTH(CURDATE()) AND YEAR(bill_date) = YEAR(CURDATE())"; break;
    case 'year':      $date_condition = "YEAR(bill_date) = YEAR(CURDATE())"; break;
    default:          $date_condition = "DATE(bill_date) = CURDATE()"; break;
}

// 2. Fetching Data for 5 Cards
$stats = $conn->query("SELECT 
    COUNT(id) as total_bills, 
    SUM(total_amount) as total_revenue,
    SUM(previous_balance) as total_prev_bal,
    SUM(amount) as current_charges,
    SUM(CASE WHEN status = 'Paid' THEN total_amount ELSE 0 END) as collected
    FROM bills WHERE $date_condition")->fetch_assoc();
?>

<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-white text-2xl font-bold">Financial Reports</h2>
        <button onclick="window.print()" class="bg-emerald-600 hover:bg-emerald-500 text-white px-6 py-2 rounded-lg font-bold shadow-lg">
            <i class="fa-solid fa-print mr-2"></i> Print Report
        </button>
    </div>

    <div class="flex flex-wrap gap-2 mb-8">
        <?php foreach(['today'=>'Day', 'yesterday'=>'Yesterday', 'week'=>'Week', 'month'=>'Month', 'year'=>'Year'] as $val => $label): ?>
            <a href="?filter=<?php echo $val; ?>" class="px-4 py-2 rounded-lg text-sm font-bold <?php echo ($filter==$val) ? 'bg-blue-600 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600'; ?>">
                <?php echo $label; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
        <?php 
        $cards = [
            ['Total Bills', $stats['total_bills'] ?? 0, 'text-white'],
            ['Collected', '$'.number_format($stats['collected'] ?? 0, 2), 'text-emerald-400'],
            ['Pending', '$'.number_format(($stats['total_revenue'] ?? 0) - ($stats['collected'] ?? 0), 2), 'text-rose-400'],
            ['Charges', '$'.number_format($stats['current_charges'] ?? 0, 2), 'text-blue-400'],
            ['Total Revenue', '$'.number_format($stats['total_revenue'] ?? 0, 2), 'text-white']
        ];
        foreach($cards as $c): ?>
            <div class="bg-slate-800 p-4 rounded-xl border border-slate-700 shadow-sm">
                <h3 class="text-slate-400 text-[10px] uppercase font-bold"><?php echo $c[0]; ?></h3>
                <p class="text-xl font-bold mt-1 <?php echo $c[2]; ?>"><?php echo $c[1]; ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="bg-slate-800 rounded-xl border border-slate-700 overflow-hidden">
        <table class="w-full text-left text-sm text-slate-300">
            <thead class="bg-blue-600 text-white">
                <tr><th class="p-4">Bill No</th><th class="p-4">Date</th><th class="p-4">Status</th><th class="p-4">Amount</th></tr>
            </thead>
            <tbody>
                <?php 
                $res = $conn->query("SELECT * FROM bills WHERE $date_condition ORDER BY bill_date DESC");
                while($row = $res->fetch_assoc()): ?>
                <tr class="border-t border-slate-700 hover:bg-slate-700/30">
                    <td class="p-4"><?php echo $row['bill_no']; ?></td>
                    <td class="p-4"><?php echo $row['bill_date']; ?></td>
                    <td class="p-4"><?php echo $row['status']; ?></td>
                    <td class="p-4 font-bold">$<?php echo number_format($row['total_amount'], 2); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
@media print {
    .sidebar, .filter-section, button, a { display: none !important; }
    body { background: white !important; }
    .bg-slate-800 { background: white !important; border: 1px solid #ddd !important; }
    .text-white { color: black !important; }
    .text-slate-300, .text-slate-400 { color: #555 !important; }
    table { border: 1px solid #ddd; }
}
</style>