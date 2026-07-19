<?php 
require_once 'database.php'; 
include 'sidebar.php'; 

// --- XAKAMAYN: Admin kaliya ayaa geli kara boggan ---
if ($_SESSION['role'] !== 'Admin') {
    echo "<script>alert('Gali maysid! Qaybtan Admin kaliya ayay u bannaan tahay.'); window.location.href='dashboard.php';</script>";
    exit();
}

// 1. Qaybta Daridda User Cusub
$add_user_error = '';
if (isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password']; 
    $gmail = $_POST['gmail'];
    $role = trim($_POST['role']);

    if (empty($username)) {
        $add_user_error = "Fadlan geli Username ka hor inta aadan Save gareyn.";
    } elseif (empty($password)) {
        $add_user_error = "Fadlan geli Password ka hor inta aadan Save gareyn.";
    } elseif (empty($role)) {
        $add_user_error = "Fadlan dooro Role ka hor inta aadan Save gareyn.";
    } else {
        // Hubi in username-kan horeyba loo isticmaalin
        $dupCheck = $conn->query("SELECT id FROM users WHERE username = '$username'")->fetch_assoc();

        if ($dupCheck) {
            $add_user_error = "Username-kan '$username' horeyba waa la isticmaalay, fadlan dooro mid kale.";
        } else {
            // Helitaanka Permissions-ka
            $perms = ['dashboard', 'customers', 'meters', 'readings', 'bills', 'payments', 'reports', 'users_page', 'settings'];
            $p_values = [];
            foreach($perms as $p) { $p_values[$p] = isset($_POST[$p]) ? 1 : 0; }

            $sql = "INSERT INTO users (username, password, gmail, role, status, dashboard, customers, meters, readings, bills, payments, reports, users_page, settings) 
                    VALUES ('$username', '$password', '$gmail', '$role', 'Active', {$p_values['dashboard']}, {$p_values['customers']}, {$p_values['meters']}, {$p_values['readings']}, {$p_values['bills']}, {$p_values['payments']}, {$p_values['reports']}, {$p_values['users_page']}, {$p_values['settings']})";

            if ($conn->query($sql)) {
                echo "<script>window.location.href='users.php';</script>";
                exit();
            } else {
                $add_user_error = "Khalad ayaa dhacay: " . $conn->error;
            }
        }
    }
}

// 2. Qaybta u-beddelka Status-ka (Active <-> Inactive)
if (isset($_POST['toggle_status'])) {
    $user_id = (int)$_POST['user_id'];
    $current_status = $_POST['current_status'];

    if ($user_id === (int)$_SESSION['user_id']) {
        echo "<script>alert('Ma iska dhigi kartid inactive akoonkaaga qudhiisa!'); window.location.href='users.php';</script>";
        exit();
    }

    $new_status = ($current_status === 'Active') ? 'Inactive' : 'Active';

    $conn->query("UPDATE users SET status = '$new_status' WHERE id = '$user_id'");

    echo "<script>window.location.href='users.php';</script>";
    exit();
}

// 3. Qaybta Delete-ka User-ka
if (isset($_POST['delete_user'])) {
    $user_id = (int)$_POST['user_id'];

    if ($user_id === (int)$_SESSION['user_id']) {
        echo "<script>alert('Ma iska tirtiri kartid akoonkaaga qudhiisa!'); window.location.href='users.php';</script>";
        exit();
    }

    $conn->query("DELETE FROM users WHERE id = '$user_id'");

    echo "<script>window.location.href='users.php';</script>";
    exit();
}
?>

<div class="p-6">
    <h2 class="text-white text-2xl font-bold mb-6">User Management & Permissions</h2>

    <?php if (!empty($add_user_error)): ?>
    <div class="bg-red-900/40 border border-red-600 text-red-300 text-sm px-4 py-2 rounded-lg mb-4">
        ⚠ <?php echo htmlspecialchars($add_user_error); ?>
    </div>
    <?php endif; ?>

    <form method="POST" class="bg-slate-800 p-6 rounded-xl border border-slate-700 mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
        <input type="text" name="username" placeholder="Username" required class="bg-slate-900 border border-slate-700 p-2 text-white rounded">
        <input type="password" name="password" placeholder="Password" required class="bg-slate-900 border border-slate-700 p-2 text-white rounded">
        <input type="email" name="gmail" placeholder="Gmail" class="bg-slate-900 border border-slate-700 p-2 text-white rounded">
        <select name="role" required class="bg-slate-900 border border-slate-700 p-2 text-white rounded">
            <option value="" disabled selected>Select Role</option>
            <option value="Admin">Admin</option>
            <option value="Manager">Manager</option>
            <option value="Cashier">Cashier</option>
            <option value="Staff">Staff</option>
        </select>

        <div class="col-span-4 grid grid-cols-3 md:grid-cols-5 gap-2 mt-4 text-xs text-slate-300">
            <?php 
            $perms = ['dashboard', 'customers', 'meters', 'readings', 'bills', 'payments', 'reports', 'users_page', 'settings'];
            foreach($perms as $p) echo "<label><input type='checkbox' name='$p' checked> ".ucfirst($p)."</label>";
            ?>
        </div>
        <button type="submit" name="add_user" class="bg-blue-600 text-white py-2 rounded font-bold col-span-4">Add User & Assign Privileges</button>
    </form>

    <div class="bg-slate-800 rounded-xl border border-slate-700 overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-300 min-w-[900px]">
            <thead class="bg-blue-600 text-white">
                <tr>
                    <th class="p-4">Username</th>
                    <th class="p-4">Password</th>
                    <th class="p-4">Role</th>
                    <th class="p-4">Status</th>
                    <th class="p-4">Permissions</th>
                    <th class="p-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $res = $conn->query("SELECT * FROM users");
                while($row = $res->fetch_assoc()): 
                    $status_class = ($row['status'] === 'Active') ? 'bg-emerald-600' : 'bg-rose-600';
                    $pwd_id = 'pwd_' . $row['id'];
                ?>
                <tr class="border-t border-slate-700">
                    <td class="p-4"><?php echo $row['username']; ?></td>
                    <td class="p-4">
                        <div class="flex items-center gap-2">
                            <span id="<?php echo $pwd_id; ?>_masked">••••••••</span>
                            <span id="<?php echo $pwd_id; ?>_real" class="hidden"><?php echo htmlspecialchars($row['password']); ?></span>
                            <button type="button"
                                    onclick="togglePassword('<?php echo $pwd_id; ?>', this)"
                                    class="bg-slate-700 hover:bg-slate-600 text-white text-xs font-bold px-2 py-1 rounded">
                                View
                            </button>
                        </div>
                    </td>
                    <td class="p-4"><?php echo $row['role']; ?></td>
                    <td class="p-4">
                        <span class="<?php echo $status_class; ?> text-white text-xs font-bold px-2 py-1 rounded"><?php echo $row['status']; ?></span>
                    </td>
                    <td class="p-4 text-[10px]">
                        <?php 
                        // Tani waxay soo bandhigaysaa xubnaha uu user-ku xaq u leeyahay
                        foreach($perms as $p) if($row[$p] == 1) echo "<span class='bg-slate-700 px-1 rounded mr-1'>$p</span>"; 
                        ?>
                    </td>
                    <td class="p-4">
                        <div class="flex gap-2">
                            <form method="POST" onsubmit="return confirm('Ma hubtaa inaad <?php echo ($row['status'] === 'Active') ? 'Inactive ka dhigto' : 'Active ka dhigto'; ?> user-kan?');">
                                <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="current_status" value="<?php echo $row['status']; ?>">
                                <button type="submit" name="toggle_status" class="<?php echo ($row['status'] === 'Active') ? 'bg-amber-600 hover:bg-amber-700' : 'bg-emerald-600 hover:bg-emerald-700'; ?> text-white text-xs font-bold px-3 py-1.5 rounded-lg whitespace-nowrap">
                                    <?php echo ($row['status'] === 'Active') ? 'Inactive' : 'Active'; ?>
                                </button>
                            </form>
                            <form method="POST" onsubmit="return confirm('Ma hubtaa inaad tirtirto user-kan? Tallaabadan lama soo celin karo!');">
                                <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" name="delete_user" class="bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-3 py-1.5 rounded-lg whitespace-nowrap">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function togglePassword(id, btn) {
    var masked = document.getElementById(id + '_masked');
    var real = document.getElementById(id + '_real');
    var isHidden = real.classList.contains('hidden');

    if (isHidden) {
        real.classList.remove('hidden');
        masked.classList.add('hidden');
        btn.textContent = 'Hide';
    } else {
        real.classList.add('hidden');
        masked.classList.remove('hidden');
        btn.textContent = 'View';
    }
}
</script>