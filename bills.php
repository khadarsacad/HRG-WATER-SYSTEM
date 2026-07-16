<?php 
require_once 'database.php'; 
include 'sidebar.php'; 

// --- Ka soo akhri Settings-ka (Unit Price, Company Info, Due Days) ---
$settingsRow = $conn->query("SELECT * FROM settings LIMIT 1")->fetch_assoc();
$default_unit_price = $settingsRow['unit_price'] ?? 0;
$currency           = $settingsRow['currency'] ?? '';
$company_name       = $settingsRow['company_name'] ?? '';
$company_phone      = $settingsRow['phone'] ?? '';
$company_address    = $settingsRow['address'] ?? '';
$due_days           = (int)($settingsRow['due_days'] ?? 0);
$receipt_footer     = $settingsRow['receipt_footer'] ?? '';

// --- QAYBTA PROCESS-KA (Hadda wuu dhex jiraa bogga) ---
$save_error = '';
if(isset($_POST['save_bill'])) {
    $bill_id_edit = (int)($_POST['bill_id'] ?? 0);
    $bill_no = $_POST['bill_no'];

    // Halkan ayaan ku saxaynaa qaladaadka (Type Casting)
    $amount = (float)$_POST['amount'];
    $prev_bal = (float)$_POST['prev_bal'];
    $total = $amount + $prev_bal; // Xisaabinta saxan

    $bill_date = $_POST['bill_date'];
    $status = $_POST['status'];

    if ($bill_id_edit > 0) {
        // --- UPDATE MODE: waxaa la beddelayaa bill horey u jiray ---
        $existingBill = $conn->query("SELECT id FROM bills WHERE id = '$bill_id_edit'")->fetch_assoc();

        if (!$existingBill) {
            $save_error = "Bill-kan lama helin, waa laga yaabaa in horey loo tirtiray.";
        } else {
            $conn->query("UPDATE bills SET bill_no = '$bill_no', amount = '$amount', previous_balance = '$prev_bal', total_amount = '$total', bill_date = '$bill_date', status = '$status' WHERE id = '$bill_id_edit'");

            echo "<script>window.location.href='bills.php';</script>";
            exit;
        }
    } else {
        // --- INSERT MODE: bill cusub ---
        $reading_id = (int)($_POST['reading_id'] ?? 0);

        if ($reading_id <= 0) {
            // Isticmaaluhu ma dooranin reading - waa in aan la kaydin bill
            $save_error = "Fadlan dooro Akhriska (Reading) ka hor inta aadan Save gareyn.";
        } else {
            // meter_id-ga si sax ah looga soo qaato reading_id-ga (lama isku hallayn karo qiimaha JS-ku dhiibo)
            $meterCheck = $conn->query("SELECT meter_id FROM meter_readings WHERE id = '$reading_id'")->fetch_assoc();

            if (!$meterCheck) {
                $save_error = "Reading-kan lama helin, fadlan dooro reading sax ah.";
            } else {
                $meter_id = (int)$meterCheck['meter_id'];

                $conn->query("INSERT INTO bills (bill_no, meter_id, reading_id, amount, previous_balance, total_amount, bill_date, status) 
                              VALUES ('$bill_no', '$meter_id', '$reading_id', '$amount', '$prev_bal', '$total', '$bill_date', '$status')");

                // U beddel status-ka reading-ga Billed
                $conn->query("UPDATE meter_readings SET status = 'Billed' WHERE id = '$reading_id'");

                echo "<script>window.location.href='bills.php';</script>";
                exit;
            }
        }
    }
}
?>

<div class="p-6">
    <div class="bg-slate-800 p-6 rounded-xl border border-slate-700/30 mb-6">
        <?php if (!empty($save_error)): ?>
        <div class="bg-red-900/40 border border-red-600 text-red-300 text-sm px-4 py-2 rounded-lg mb-4">
            ⚠ <?php echo htmlspecialchars($save_error); ?>
        </div>
        <?php endif; ?>
        <div id="editBanner" class="hidden bg-amber-900/40 border border-amber-600 text-amber-300 text-sm px-4 py-2 rounded-lg mb-4 flex justify-between items-center">
            <span>🖊 Waxaad wax ka beddelaysaa Bill: <b id="editBannerText"></b></span>
            <button type="button" onclick="cancelEdit()" class="bg-slate-700 hover:bg-slate-600 text-white text-xs font-bold px-3 py-1.5 rounded-lg">Cancel Edit</button>
        </div>
        <form method="POST" id="billForm" onsubmit="return validateBillForm();" class="grid grid-cols-1 md:grid-cols-8 gap-4 items-end">
            <input type="hidden" name="bill_id" id="edit_bill_id" value="">
            <input type="hidden" name="meter_id" id="meter_id_val">
            <div>
                <label class="text-[10px] text-slate-400 font-bold uppercase">Bill No</label>
                <input type="text" name="bill_no" value="BILL-<?php echo rand(100000, 999999); ?>" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-white text-sm">
            </div>
            <div class="col-span-2">
                <label class="text-[10px] text-slate-400 font-bold uppercase">Select Unbilled Reading</label>
                <select id="reading_id" name="reading_id" onchange="updateInfo()" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-white text-sm">
                    <option value="0" data-units="0" data-meter="0" data-prevbal="0">Select Readings</option>
                    <?php 
                    $res = $conn->query("SELECT r.id, r.units_used, r.reading_date, m.id as m_id, m.meter_number, c.id as customer_id, c.customer_name,
                                            COALESCE((SELECT SUM(b2.total_amount - COALESCE((SELECT SUM(p2.amount_paid) FROM payments p2 WHERE p2.bill_id = b2.id), 0)) 
                                                      FROM bills b2 JOIN meters m2 ON b2.meter_id = m2.id 
                                                      WHERE m2.customer_id = c.id AND b2.status = 'Unpaid'), 0) as prev_balance
                                         FROM meter_readings r 
                                         JOIN meters m ON r.meter_id = m.id 
                                         JOIN customers c ON m.customer_id = c.id 
                                         WHERE r.status = 'Unbilled'
                                         ORDER BY r.reading_date DESC, r.id DESC");
                    while($row = $res->fetch_assoc()) {
                        echo "<option value='".$row['id']."' data-units='".$row['units_used']."' data-meter='".$row['m_id']."' data-prevbal='".$row['prev_balance']."'>"
                            .$row['meter_number']." - ".$row['customer_name']." (Units: ".number_format($row['units_used'],2)." | ".$row['reading_date'].")"
                            ."</option>";
                    }
                    ?>
                </select>
            </div>
            <div>
                <label class="text-[10px] text-slate-400 font-bold uppercase">Units</label>
                <input type="text" id="units_used" readonly class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2.5 text-slate-400 text-sm">
            </div>
            <div>
                <label class="text-[10px] text-slate-400 font-bold uppercase">Unit Price (<?php echo $currency; ?>)</label>
                <input type="text" name="unit_price" id="unit_price" value="<?php echo $default_unit_price; ?>" oninput="calculateAmount()" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-white text-sm">
            </div>
            <div>
                <label class="text-[10px] text-slate-400 font-bold uppercase">Amount</label>
                <input type="text" name="amount" id="amount" readonly class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2.5 text-slate-400 text-sm">
            </div>
            <div>
                <label class="text-[10px] text-slate-400 font-bold uppercase">Prev Bal</label>
                <input type="text" name="prev_bal" id="prev_bal" readonly class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2.5 text-slate-400 text-sm">
            </div>
            <div>
                <label class="text-[10px] text-slate-400 font-bold uppercase">Total Amount (<?php echo $currency; ?>)</label>
                <input type="text" id="total_amount" readonly class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2.5 text-blue-400 font-bold text-sm">
            </div>

            <div class="col-span-2">
                <label class="text-[10px] text-slate-400 font-bold uppercase">Bill Date</label>
                <input type="date" name="bill_date" value="<?php echo date('Y-m-d'); ?>" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-white text-sm">
            </div>
            <div class="col-span-2">
                <label class="text-[10px] text-slate-400 font-bold uppercase">Status</label>
                <select name="status" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-white text-sm">
                    <option value="Unpaid">Unpaid</option>
                    <option value="Paid">Paid</option>
                </select>
            </div>
            <button type="submit" name="save_bill" id="saveBtn" class="col-span-2 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded-lg text-sm">Save & Generate Bill</button>
            <button type="button" onclick="location.reload()" class="col-span-2 bg-slate-700 hover:bg-slate-600 text-white font-bold py-2.5 rounded-lg text-sm">Clear</button>
        </form>
    </div>

    <div class="bg-slate-800 rounded-xl border border-slate-700/30 overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-300">
            <thead class="bg-blue-600 text-white">
                <tr>
                    <th class="p-4">Bill No</th>
                    <th class="p-4">Customer Name</th>
                    <th class="p-4">Meter No</th>
                    <th class="p-4">Units</th>
                    <th class="p-4">Rate</th>
                    <th class="p-4">Amount</th>
                    <th class="p-4">Prev Bal</th>
                    <th class="p-4">Total Due</th>
                    <th class="p-4">Status</th>
                    <th class="p-4">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700">
                <?php 
                $result = $conn->query("SELECT b.*, c.customer_name, m.meter_number, r.units_used 
                                         FROM bills b 
                                         JOIN meters m ON b.meter_id = m.id 
                                         JOIN customers c ON m.customer_id = c.id 
                                         LEFT JOIN meter_readings r ON b.reading_id = r.id
                                         ORDER BY b.id DESC");
                while($row = $result->fetch_assoc()):
                    $units = (float)$row['units_used'];
                    $rate = $units > 0 ? ($row['amount'] / $units) : $default_unit_price;
                    $status_class = ($row['status'] === 'Paid') ? 'bg-green-600' : 'bg-amber-600';
                    $due_date = date('Y-m-d', strtotime($row['bill_date'] . " +{$due_days} days"));

                    $billData = [
                        'id'        => $row['id'],
                        'bill_no'   => $row['bill_no'],
                        'customer'  => $row['customer_name'],
                        'meter_no'  => $row['meter_number'],
                        'units'     => number_format($units, 2),
                        'rate'      => number_format($rate, 2),
                        'amount'    => number_format($row['amount'], 2),
                        'prev_bal'  => number_format($row['previous_balance'], 2),
                        'total'     => number_format($row['total_amount'], 2),
                        'status'    => $row['status'],
                        'bill_date' => $row['bill_date'],
                        'due_date'  => $due_date,
                    ];
                ?>
                <tr>
                    <td class="p-4 font-bold text-white"><?php echo $row['bill_no']; ?></td>
                    <td class="p-4"><?php echo $row['customer_name']; ?></td>
                    <td class="p-4"><?php echo $row['meter_number']; ?></td>
                    <td class="p-4"><?php echo number_format($units, 2); ?></td>
                    <td class="p-4"><?php echo $currency; ?> <?php echo number_format($rate, 2); ?></td>
                    <td class="p-4"><?php echo $currency; ?> <?php echo number_format($row['amount'], 2); ?></td>
                    <td class="p-4 text-red-400"><?php echo $currency; ?> <?php echo number_format($row['previous_balance'], 2); ?></td>
                    <td class="p-4 font-bold text-blue-400"><?php echo $currency; ?> <?php echo number_format($row['total_amount'], 2); ?></td>
                    <td class="p-4"><span class="<?php echo $status_class; ?> text-white text-xs font-bold px-3 py-1 rounded-full"><?php echo $row['status']; ?></span></td>
                    <td class="p-4">
                        <div class="flex gap-1.5">
                            <button type="button" title="Edit Bill" onclick='editBill(<?php echo htmlspecialchars(json_encode($billData), ENT_QUOTES, "UTF-8"); ?>)' class="bg-amber-600 hover:bg-amber-700 text-white text-sm px-2.5 py-2 rounded-lg whitespace-nowrap leading-none">
                                ✏
                            </button>
                            <button type="button" title="Print Receipt" onclick='printBill(<?php echo htmlspecialchars(json_encode($billData), ENT_QUOTES, "UTF-8"); ?>)' class="bg-green-600 hover:bg-green-700 text-white text-sm px-2.5 py-2 rounded-lg whitespace-nowrap leading-none">
                                🖨
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Macluumaadka shirkadda ee loo baahan yahay print-ka (waxa laga soo akhriyay settings.php)
    const COMPANY = {
        name: <?php echo json_encode($company_name); ?>,
        phone: <?php echo json_encode($company_phone); ?>,
        address: <?php echo json_encode($company_address); ?>,
        currency: <?php echo json_encode($currency); ?>,
        footer: <?php echo json_encode($receipt_footer); ?>
    };

    function validateBillForm() {
        // Haddii edit mode socoto, ma loo baahna in reading la doorto
        let editId = document.getElementById('edit_bill_id').value;
        if (editId && editId !== '0' && editId !== '') {
            return true;
        }

        let sel = document.getElementById('reading_id');
        if (!sel.value || sel.value === '0') {
            alert('Fadlan dooro Akhriska (Reading) ka hor inta aadan Save gareyn.');
            return false;
        }
        return true;
    }

    // --- Edit Mode: xogta bill-ka waxaa lagu soo celinayaa form-ka si loo update gareeyo ---
    function editBill(bill) {
        document.getElementById('edit_bill_id').value = bill.id;

        // Reading select-ka waxaa la disable gareynayaa (meter/reading-ga ma badalayo)
        let sel = document.getElementById('reading_id');
        sel.disabled = true;
        sel.value = '0';

        document.querySelector('input[name="bill_no"]').value = bill.bill_no;
        document.getElementById('units_used').value = bill.units;
        document.getElementById('unit_price').value = bill.rate;
        document.getElementById('amount').value = bill.amount;

        let prevBalField = document.getElementById('prev_bal');
        prevBalField.readOnly = false;
        prevBalField.value = bill.prev_bal;
        prevBalField.classList.remove('bg-slate-950', 'text-slate-400');
        prevBalField.classList.add('bg-slate-900', 'text-white');

        document.getElementById('total_amount').value = bill.total;
        document.querySelector('input[name="bill_date"]').value = bill.bill_date;
        document.querySelector('select[name="status"]').value = bill.status;

        // Marka prev_bal la beddelo gacanta, total_amount ha isla xisaabsamo
        prevBalField.oninput = calculateAmount;

        document.getElementById('editBanner').classList.remove('hidden');
        document.getElementById('editBannerText').innerText = bill.bill_no + ' - ' + bill.customer;
        document.getElementById('saveBtn').innerText = 'Update Bill';

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function cancelEdit() {
        location.reload();
    }

    function updateInfo() {
        let sel = document.getElementById('reading_id');
        let opt = sel.options[sel.selectedIndex];
        let units = opt.getAttribute('data-units');
        let m_id = opt.getAttribute('data-meter');
        let prevBal = opt.getAttribute('data-prevbal');

        document.getElementById('units_used').value = units;
        document.getElementById('meter_id_val').value = m_id;
        document.getElementById('prev_bal').value = parseFloat(prevBal || 0).toFixed(2);

        calculateAmount();
    }

    function calculateAmount() {
        let u = parseFloat(document.getElementById('units_used').value) || 0;
        let p = parseFloat(document.getElementById('unit_price').value) || 0;
        let prevBal = parseFloat(document.getElementById('prev_bal').value) || 0;

        let amount = u * p;
        document.getElementById('amount').value = amount.toFixed(2);
        document.getElementById('total_amount').value = (amount + prevBal).toFixed(2);
    }

    // --- Print Receipt (gudaha isla file-kan, ma jiro file kale) ---
    function printBill(bill) {
        let win = window.open('', 'PrintReceipt', 'width=420,height=650');
        let html = `
        <html>
        <head>
            <title>Receipt - ${bill.bill_no}</title>
            <style>
                body{font-family: Arial, sans-serif; padding:20px; color:#111;}
                h2{margin:0; text-align:center;}
                p{margin:2px 0; text-align:center; font-size:12px; color:#444;}
                hr{border:none; border-top:1px dashed #999; margin:12px 0;}
                table{width:100%; border-collapse:collapse; font-size:13px;}
                td{padding:4px 0;}
                .label{color:#555;}
                .value{text-align:right; font-weight:bold;}
                .total{font-size:16px; border-top:2px solid #111; padding-top:8px;}
                .status{text-align:center; margin-top:10px; font-weight:bold;}
                .footer{margin-top:16px; text-align:center; font-size:11px; color:#666;}
            </style>
        </head>
        <body>
            <h2>${COMPANY.name}</h2>
            <p>${COMPANY.address}</p>
            <p>${COMPANY.phone}</p>
            <hr>
            <h3 style="text-align:center;">Bill Receipt</h3>
            <table>
                <tr><td class="label">Bill No:</td><td class="value">${bill.bill_no}</td></tr>
                <tr><td class="label">Customer:</td><td class="value">${bill.customer}</td></tr>
                <tr><td class="label">Meter No:</td><td class="value">${bill.meter_no}</td></tr>
                <tr><td class="label">Bill Date:</td><td class="value">${bill.bill_date}</td></tr>
                <tr><td class="label">Due Date:</td><td class="value">${bill.due_date}</td></tr>
            </table>
            <hr>
            <table>
                <tr><td class="label">Units Used:</td><td class="value">${bill.units}</td></tr>
                <tr><td class="label">Rate (${COMPANY.currency}):</td><td class="value">${bill.rate}</td></tr>
                <tr><td class="label">Amount:</td><td class="value">${COMPANY.currency} ${bill.amount}</td></tr>
                <tr><td class="label">Previous Balance:</td><td class="value">${COMPANY.currency} ${bill.prev_bal}</td></tr>
                <tr class="total"><td class="label">Total Due:</td><td class="value">${COMPANY.currency} ${bill.total}</td></tr>
            </table>
            <p class="status">Status: ${bill.status}</p>
            <hr>
            <p class="footer">${COMPANY.footer}</p>
        </body>
        </html>`;
        win.document.write(html);
        win.document.close();
        win.focus();
        win.onload = function() {
            win.print();
        };
        setTimeout(function(){ win.print(); }, 300);
    }
</script>