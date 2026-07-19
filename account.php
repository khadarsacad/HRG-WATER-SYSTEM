<?php 
require_once 'database.php'; 
include 'sidebar.php'; 
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
$user_id = $_SESSION['user_id'];
$msg = "";
if (isset($_POST['update_account'])) {
    // Xogta uu qofku geliyay si uu isku xaqiijiyo
    $confirm_role = $_POST['confirm_role'];
    $confirm_user = $_POST['confirm_username'];
    $confirm_pass = $_POST['confirm_password'];
    
    // Xogta cusub ee uu rabay inuu beddelo
    $new_username = $_POST['username'];
    $new_gmail = $_POST['gmail'];
    $new_password = $_POST['password'];
    // SQL-ka Xaqiijinta: Hubi inay saddexdu sax yihiin
    $check = $conn->query("SELECT id FROM users WHERE id = '$user_id' 
                           AND role = '$confirm_role' 
                           AND username = '$confirm_user' 
                           AND password = '$confirm_pass'");
    
    if ($check->num_rows > 0) {
        // Haddii ay sax yihiin, fuli UPDATE
        $sql = "UPDATE users SET username='$new_username', gmail='$new_gmail'";
        if (!empty($new_password)) { $sql .= ", password='$new_password'"; }
        $sql .= " WHERE id='$user_id'";
        
        $conn->query($sql);
        $msg = "<div class='bg-emerald-900 text-emerald-400 p-4 rounded-lg mb-4'>Account updated successfully!</div>";
    } else {
        $msg = "<div class='bg-rose-900 text-rose-400 p-4 rounded-lg mb-4'>Verification failed! Please check your Role, Username, and Password.</div>";
    }
}
$user = $conn->query("SELECT * FROM users WHERE id = '$user_id'")->fetch_assoc();
?>
<div class="p-6">
    <h2 class="text-white text-2xl font-bold mb-6">Account Settings</h2>
    <?php echo $msg; ?>
    
    <form method="POST" class="bg-slate-800 p-6 rounded-xl border border-slate-700 max-w-lg space-y-4">
        <div class="bg-slate-900 p-4 rounded-lg border border-rose-500/30">
            <h3 class="text-rose-400 font-bold mb-3 text-sm uppercase">Account Verification</h3>
            <input type="text" name="confirm_role" placeholder="Role (e.g. Admin)" required class="w-full bg-slate-800 border border-slate-700 rounded p-2 text-white mb-2">
            <input type="text" name="confirm_username" placeholder="Your Username" required class="w-full bg-slate-800 border border-slate-700 rounded p-2 text-white mb-2">
            <input type="password" name="confirm_password" placeholder="Your Password" required class="w-full bg-slate-800 border border-slate-700 rounded p-2 text-white">
        </div>
        
        <hr class="border-slate-700">
        
        <div>
            <label class="text-slate-400 text-[10px] font-bold uppercase">New Username</label>
            <input type="text" name="username" value="<?php echo $user['username']; ?>" class="w-full bg-slate-900 border border-slate-700 rounded p-2.5 text-white mt-1">
        </div>
        <div>
            <label class="text-slate-400 text-[10px] font-bold uppercase">New Gmail</label>
            <input type="email" name="gmail" value="<?php echo $user['gmail']; ?>" class="w-full bg-slate-900 border border-slate-700 rounded p-2.5 text-white mt-1">
        </div>
        <div>
            <label class="text-slate-400 text-[10px] font-bold uppercase">New Password</label>
            <input type="password" name="password" placeholder="Leave blank if not changing" class="w-full bg-slate-900 border border-slate-700 rounded p-2.5 text-white mt-1">
        </div>
        <button type="submit" name="update_account" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded-lg mt-4">
            Save Changes
        </button>
    </form>
</div>