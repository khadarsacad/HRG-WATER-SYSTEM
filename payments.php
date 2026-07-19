<?php 
include 'sidebar.php'; 
require_once 'database.php'; 

// --- Settings-ka loogu talagalay Print Receipt-ka ---
$settingsRow     = $conn->query("SELECT * FROM settings LIMIT 1")->fetch_assoc();
$currency        = $settingsRow['currency'] ?? '';
$company_name    = $settingsRow['company_name'] ?? '';
$company_phone   = $settingsRow['phone'] ?? '';
$company_address = $settingsRow['address'] ?? '';
$receipt_footer  = $settingsRow['receipt_footer'] ?? '';

// --- QAYBTA UPDATE-KA ---
$save_error = '';
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_payment'])) {
    $payment_id_edit = (int)($_POST['payment_id'] ?? 0);
    $amount  = (float)$_POST['amount_paid'];
    $method  = $_POST['payment_method'];
    $date    = $_POST['payment_date'];

    if ($payment_id_edit > 0) {
        // --- UPDATE MODE: waxaa la beddelayaa payment horey u jiray ---
        $bill_id = (int)($_POST['edit_bill_id_locked'] ?? 0);

        $existingPayment = $conn->query("SELECT id FROM payments WHERE id = '$payment_id_edit'")->fetch_assoc();

        if (!$existingPayment) {
            $save_error = "Payment-kan lama helin, waa laga yaabaa in horey loo tirtiray.";
        } elseif ($amount <= 0) {
            $save_error = "Lacagta la bixinayo waa in ay ka weyn tahay 0.";
        } else {
            $billCheck = $conn->query("SELECT id, total_amount FROM bills WHERE id = '$bill_id'")->fetch_assoc();

            if (!$billCheck) {
                $save_error = "Bill-kan lama helin.";
            } else {
                $total_amount = (float)$billCheck['total_amount'];

                // Wadarta lacagaha kale ee bill-kan (kani ma aha payment-ka la editingayo)
                $paidRow = $conn->query("SELECT COALESCE(SUM(amount_paid),0) as paid_sum FROM payments WHERE bill_id = '$bill_id' AND id != '$payment_id_edit'")->fetch_assoc();
                $already_paid_excl = (float)$paidRow['paid_sum'];
                $remaining = $total_amount - $already_paid_excl;

                if ($amount > $remaining + 0.01) {
                    $save_error = "Lacagta la bixinayo ($amount) way ka badan tahay lacagta hadhay ee bill-kan (" . $currency . " " . number_format($remaining, 2) . ").";
                } else {
                    $conn->query("UPDATE payments SET amount_paid = '$amount', payment_method = '$method', payment_date = '$date' WHERE id = '$payment_id_edit'");

                    // Status-ka bill-ka dib ayaa loo xisaabinayaa (hal jiho ama kale)
                    $new_paid_total = $already_paid_excl + $amount;
                    if ($new_paid_total >= $total_amount - 0.01) {
                        $conn->query("UPDATE bills SET status = 'Paid' WHERE id = '$bill_id'");
                    } else {
                        $conn->query("UPDATE bills SET status = 'Unpaid' WHERE id = '$bill_id'");
                    }

                    echo "<script>window.location.href='payments.php';</script>";
                    exit;
                }
            }
        }
    } else {
        // --- INSERT MODE: payment cusub ---
        $bill_id = (int)($_POST['bill_id'] ?? 0);
        $receipt_no = 'REC-' . rand(10000, 99999);

        if ($bill_id <= 0) {
            $save_error = "Fadlan dooro Bill-ka ka hor inta aadan Save gareyn.";
        } elseif ($amount <= 0) {
            $save_error = "Lacagta la bixinayo waa in ay ka weyn tahay 0.";
        } else {
            // Bill-ka si sax ah looga soo qaato (lama isku hallayn karo qiimo aan la hubin)
            $billCheck = $conn->query("SELECT id, total_amount FROM bills WHERE id = '$bill_id'")->fetch_assoc();

            if (!$billCheck) {
                $save_error = "Bill-kan lama helin, fadlan dooro bill sax ah.";
            } else {
                $total_amount = (float)$billCheck['total_amount'];

                // Wadarta lacagta hore loo bixiyay bill-kan
                $paidRow = $conn->query("SELECT COALESCE(SUM(amount_paid),0) as paid_sum FROM payments WHERE bill_id = '$bill_id'")->fetch_assoc();
                $already_paid = (float)$paidRow['paid_sum'];
                $remaining = $total_amount - $already_paid;

                if ($amount > $remaining + 0.01) {
                    $save_error = "Lacagta la bixinayo ($amount) way ka badan tahay lacagta hadhay ee bill-kan (" . $currency . " " . number_format($remaining, 2) . ").";
                } else {
                    $conn->query("INSERT INTO payments (bill_id, amount_paid, payment_method, payment_date, receipt_no) VALUES ('$bill_id', '$amount', '$method', '$date', '$receipt_no')");

                    // Bill-ka waxaa 'Paid' laga dhigayaa oo kaliya haddii lacagta la bixiyay ay bug-buuxin karto wadarta
                    $new_paid_total = $already_paid + $amount;
                    if ($new_paid_total >= $total_amount - 0.01) {
                        $conn->query("UPDATE bills SET status = 'Paid' WHERE id = '$bill_id'");
                    }

                    echo "<script>window.location.href='payments.php';</script>";
                    exit;
                }
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
            <span>🖊 Waxaad wax ka beddelaysaa Payment: <b id="editBannerText"></b></span>
            <button type="button" onclick="cancelEditPayment()" class="bg-slate-700 hover:bg-slate-600 text-white text-xs font-bold px-3 py-1.5 rounded-lg">Cancel Edit</button>
        </div>
        <form method="POST" id="paymentForm" onsubmit="return validatePaymentForm();" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            <input type="hidden" name="payment_id" id="payment_id_edit" value="">
            <input type="hidden" name="edit_bill_id_locked" id="edit_bill_id_locked" value="">
            <div>
                <label class="text-[10px] text-slate-400 font-bold uppercase">Select Bill</label>
                <select name="bill_id" id="bill_id" onchange="updateRemaining()" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-white text-sm" required>
                    <option value="">Select Bill</option>
                    <?php 
                    $res = $conn->query("SELECT b.id, b.bill_no, b.total_amount, c.customer_name,
                                            COALESCE((SELECT SUM(p.amount_paid) FROM payments p WHERE p.bill_id = b.id), 0) as paid_sum
                                         FROM bills b
                                         JOIN meters m ON b.meter_id = m.id
                                         JOIN customers c ON m.customer_id = c.id
                                         WHERE b.status = 'Unpaid' 
                                         ORDER BY b.id DESC");
                    while($row = $res->fetch_assoc()) {
                        $remaining = (float)$row['total_amount'] - (float)$row['paid_sum'];
                        echo "<option value='".$row['id']."' data-remaining='".$remaining."'>"
                            .$row['bill_no']." - ".$row['customer_name']." (Hadhay: ".$currency." ".number_format($remaining,2).")"
                            ."</option>";
                    }
                    ?>
                </select>
            </div>
            <div>
                <label class="text-[10px] text-slate-400 font-bold uppercase">Amount Paid (<?php echo $currency; ?>)</label>
                <input type="number" name="amount_paid" id="amount_paid" step="0.01" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-white text-sm" required>
                <p class="text-[10px] text-slate-500 mt-1">Prev Bal: <span id="remaining_display"><?php echo $currency; ?> 0.00</span></p>
            </div>
            <div>
                <label class="text-[10px] text-slate-400 font-bold uppercase">Method</label>
                <select name="payment_method" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-white text-sm">
                    <option value="Cash">Cash</option>
                    <option value="ZAAD">ZAAD</option>
                    <option value="eDahab">eDahab</option>
                </select>
            </div>
            <div>
                <label class="text-[10px] text-slate-400 font-bold uppercase">Date</label>
                <input type="date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-white text-sm">
            </div>
            <button type="submit" name="save_payment" id="saveBtn" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded-lg text-sm transition-all">
                <i class="fa-solid fa-check-circle mr-2"></i> Save Payment
            </button>
        </form>
    </div>

    <div class="bg-slate-800 rounded-xl border border-slate-700/30 overflow-hidden">
        <table class="w-full text-left text-sm text-slate-300">
            <thead class="bg-blue-600 text-white">
                <tr>
                    <th class="p-4">Receipt No</th>
                    <th class="p-4">Bill No</th>
                    <th class="p-4">Customer</th>
                    <th class="p-4">Amount</th>
                    <th class="p-4">Method</th>
                    <th class="p-4">Date</th>
                    <th class="p-4">Prev Balance</th>
                    <th class="p-4">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700">
                <?php 
                $sql = "SELECT p.*, b.bill_no, b.total_amount, c.customer_name,
                            (SELECT COALESCE(SUM(p3.amount_paid),0) FROM payments p3 WHERE p3.bill_id = p.bill_id AND p3.id <= p.id) as paid_upto_this
                        FROM payments p 
                        JOIN bills b ON p.bill_id = b.id 
                        JOIN meters m ON b.meter_id = m.id
                        JOIN customers c ON m.customer_id = c.id
                        ORDER BY p.id DESC";
                $res = $conn->query($sql);
                while($row = $res->fetch_assoc()):
                    $remaining_after = (float)$row['total_amount'] - (float)$row['paid_upto_this'];
                    if ($remaining_after < 0) { $remaining_after = 0; }
                    $receiptData = [
                        'receipt_no' => $row['receipt_no'],
                        'bill_no'    => $row['bill_no'],
                        'customer'   => $row['customer_name'],
                        'amount'     => number_format($row['amount_paid'], 2),
                        'method'     => $row['payment_method'],
                        'date'       => $row['payment_date'],
                        'remaining'  => number_format($remaining_after, 2),
                    ];
                    $editData = [
                        'id'       => $row['id'],
                        'bill_id'  => $row['bill_id'],
                        'bill_no'  => $row['bill_no'],
                        'customer' => $row['customer_name'],
                        'amount'   => $row['amount_paid'],
                        'method'   => $row['payment_method'],
                        'date'     => $row['payment_date'],
                    ];
                ?>
                <tr>
                    <td class="p-4 font-bold text-white"><?php echo $row['receipt_no']; ?></td>
                    <td class="p-4"><?php echo $row['bill_no']; ?></td>
                    <td class="p-4"><?php echo $row['customer_name']; ?></td>
                    <td class="p-4 text-blue-400 font-bold"><?php echo $currency; ?> <?php echo number_format($row['amount_paid'], 2); ?></td>
                    <td class="p-4"><?php echo $row['payment_method']; ?></td>
                    <td class="p-4"><?php echo $row['payment_date']; ?></td>
                    <td class="p-4 font-bold <?php echo $remaining_after > 0 ? 'text-red-400' : 'text-green-400'; ?>">
                        <?php echo $currency; ?> <?php echo number_format($remaining_after, 2); ?>
                    </td>
                    <td class="p-4">
                        <div class="flex gap-1.5">
                            <button type="button" title="Edit Payment" onclick='editPayment(<?php echo htmlspecialchars(json_encode($editData), ENT_QUOTES, "UTF-8"); ?>)' class="bg-amber-600 hover:bg-amber-700 text-white text-sm px-2.5 py-2 rounded-lg whitespace-nowrap leading-none">
                                ✏
                            </button>
                            <button type="button" title="Print Receipt" onclick='printReceipt(<?php echo htmlspecialchars(json_encode($receiptData), ENT_QUOTES, "UTF-8"); ?>)' class="bg-green-600 hover:bg-green-700 text-white text-sm px-2.5 py-2 rounded-lg whitespace-nowrap leading-none">
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
    const COMPANY = {
        name: <?php echo json_encode($company_name); ?>,
        phone: <?php echo json_encode($company_phone); ?>,
        address: <?php echo json_encode($company_address); ?>,
        currency: <?php echo json_encode($currency); ?>,
        footer: <?php echo json_encode($receipt_footer); ?>
    };

    function updateRemaining() {
        // Haddii edit mode socoto, remaining-ka auto ha isku badalin
        let editId = document.getElementById('payment_id_edit').value;
        if (editId) { return; }

        let sel = document.getElementById('bill_id');
        let opt = sel.options[sel.selectedIndex];
        let remaining = parseFloat(opt.getAttribute('data-remaining')) || 0;

        document.getElementById('remaining_display').innerText = COMPANY.currency + ' ' + remaining.toFixed(2);
        document.getElementById('amount_paid').value = remaining.toFixed(2);
        document.getElementById('amount_paid').setAttribute('max', remaining.toFixed(2));
    }

    function validatePaymentForm() {
        let editId = document.getElementById('payment_id_edit').value;

        // Edit mode: Select Bill-ka dropdown-ku disabled yahay, ma loo baahna hubinta bill_id
        if (editId) {
            let amount = parseFloat(document.getElementById('amount_paid').value) || 0;
            if (amount <= 0) {
                alert('Fadlan geli lacag sax ah.');
                return false;
            }
            return true;
        }

        let sel = document.getElementById('bill_id');
        if (!sel.value) {
            alert('Fadlan dooro Bill-ka ka hor inta aadan Save gareyn.');
            return false;
        }
        let amount = parseFloat(document.getElementById('amount_paid').value) || 0;
        let remaining = parseFloat(sel.options[sel.selectedIndex].getAttribute('data-remaining')) || 0;

        if (amount <= 0) {
            alert('Fadlan geli lacag sax ah.');
            return false;
        }
        if (amount > remaining + 0.01) {
            alert('Lacagta la bixinayo way ka badan tahay lacagta hadhay (' + COMPANY.currency + ' ' + remaining.toFixed(2) + ').');
            return false;
        }
        return true;
    }

    // --- Edit Mode: xogta payment-ka waxaa lagu soo celinayaa form-ka si loo update gareeyo ---
    function editPayment(payment) {
        document.getElementById('payment_id_edit').value = payment.id;
        document.getElementById('edit_bill_id_locked').value = payment.bill_id;

        // Select Bill-ka waxaa la disable gareynayaa (bill-ka ma badalayo)
        let sel = document.getElementById('bill_id');
        sel.disabled = true;
        sel.value = '';

        document.getElementById('amount_paid').value = parseFloat(payment.amount).toFixed(2);
        document.getElementById('remaining_display').innerText = '—';
        document.querySelector('select[name="payment_method"]').value = payment.method;
        document.querySelector('input[name="payment_date"]').value = payment.date;

        document.getElementById('editBanner').classList.remove('hidden');
        document.getElementById('editBannerText').innerText = payment.bill_no + ' - ' + payment.customer;
        document.getElementById('saveBtn').innerHTML = '<i class="fa-solid fa-check-circle mr-2"></i> Update Payment';

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function cancelEditPayment() {
        location.reload();
    }

    function printReceipt(r) {
        let win = window.open('', 'PrintReceipt', 'width=420,height=600');
        let html = `
        <html>
        <head>
            <title>Receipt - ${r.receipt_no}</title>
            <style>
                body{font-family: Arial, sans-serif; padding:20px; color:#111;}
                h2{margin:0; text-align:center;}
                p{margin:2px 0; text-align:center; font-size:12px; color:#444;}
                hr{border:none; border-top:1px dashed #999; margin:12px 0;}
                table{width:100%; border-collapse:collapse; font-size:13px;}
                td{padding:4px 0;}
                .label{color:#555;}
                .value{text-align:right; font-weight:bold;}
                .footer{margin-top:16px; text-align:center; font-size:11px; color:#666;}
            </style>
        </head>
        <body>
            <h2>${COMPANY.name}</h2>
            <p>${COMPANY.address}</p>
            <p>${COMPANY.phone}</p>
            <hr>
            <h3 style="text-align:center;">Payment Receipt</h3>
            <table>
                <tr><td class="label">Receipt No:</td><td class="value">${r.receipt_no}</td></tr>
                <tr><td class="label">Bill No:</td><td class="value">${r.bill_no}</td></tr>
                <tr><td class="label">Customer:</td><td class="value">${r.customer}</td></tr>
                <tr><td class="label">Date:</td><td class="value">${r.date}</td></tr>
                <tr><td class="label">Method:</td><td class="value">${r.method}</td></tr>
                <tr><td class="label" style="font-size:15px;">Amount Paid:</td><td class="value" style="font-size:15px;">${COMPANY.currency} ${r.amount}</td></tr>
                <tr><td class="label">Prev Balance:</td><td class="value" style="color:${parseFloat(r.remaining) > 0 ? '#c0392b' : '#27ae60'};">${COMPANY.currency} ${r.remaining}</td></tr>
            </table>
            <hr>
            <p class="footer">${COMPANY.footer}</p>
        </body>
        </html>`;
        win.document.write(html);
        win.document.close();
        win.focus();
        setTimeout(function(){ win.print(); }, 300);
    }
</script>