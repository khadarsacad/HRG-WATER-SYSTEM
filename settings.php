<?php
// 1. Session & Database
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'database.php'; 

// 2. Update Logic
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $unit_price = mysqli_real_escape_string($conn, $_POST['unit_price']);
    $due_days = mysqli_real_escape_string($conn, $_POST['due_days']);
    $company_name = mysqli_real_escape_string($conn, $_POST['company_name']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    
    $update = "UPDATE settings SET unit_price='$unit_price', due_days='$due_days', company_name='$company_name', address='$address', phone='$phone' WHERE id=3";
    mysqli_query($conn, $update);
    $message = "<div class='bg-green-500/20 text-green-400 p-3 rounded-lg mb-4'>Settings updated successfully!</div>";
}

// 3. Fetch Data
$query = "SELECT * FROM settings WHERE id=3";
$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-900 text-slate-100 flex h-screen overflow-hidden">

    <?php include 'sidebar.php'; ?>

    <main class="flex-1 p-8 overflow-y-auto">
        <h1 class="text-2xl font-bold mb-6">System Settings</h1>
        <?php if (isset($message)) echo $message; ?>

        <form method="POST" class="bg-slate-800 p-6 rounded-xl border border-slate-700 shadow-xl max-w-4xl">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm text-slate-400 mb-2">Company Name</label>
                    <input type="text" name="company_name" value="<?php echo htmlspecialchars($data['company_name'] ?? ''); ?>" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-3 text-white">
                </div>
                <div>
                    <label class="block text-sm text-slate-400 mb-2">Phone</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($data['phone'] ?? ''); ?>" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-3 text-white">
                </div>
                <div>
                    <label class="block text-sm text-slate-400 mb-2">Unit Price</label>
                    <input type="number" step="0.01" name="unit_price" value="<?php echo htmlspecialchars($data['unit_price'] ?? ''); ?>" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-3 text-white">
                </div>
                <div>
                    <label class="block text-sm text-slate-400 mb-2">Due Days</label>
                    <input type="number" name="due_days" value="<?php echo htmlspecialchars($data['due_days'] ?? ''); ?>" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-3 text-white">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm text-slate-400 mb-2">Address</label>
                    <input type="text" name="address" value="<?php echo htmlspecialchars($data['address'] ?? ''); ?>" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-3 text-white">
                </div>
            </div>
            <button type="submit" class="mt-6 bg-blue-500 hover:bg-cyan-600 text-white px-8 py-3 rounded-xl font-bold transition-all">
                Update Settings
            </button>
        </form>
    </main>

</body>
</html>