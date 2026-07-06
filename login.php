<?php
session_start();
require_once 'database.php'; 

// Soo xiridda maktabadda PHPMailer ee aad folder-ka ku radday
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

$error = "";
$message = "";
$step = 1; 

// ==========================================
// SECTION 1: SYSTEM SIGN IN (LOGIN LOGIC)
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);

    $sql = "SELECT * FROM users WHERE username='$username' AND role='$role' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if ($user['status'] !== 'Active') {
            $error = "This account is Inactive! Please contact the Admin.";
        } elseif ($password === $user['password']) { 
            // Ku keydi ogolaanshiyaha boggagga (Permissions) gudaha Session-ka
            $_SESSION['user_id']        = $user['id'];
            $_SESSION['username']       = $user['username'];
            $_SESSION['role']           = $user['role'];
            
            $_SESSION['can_dashboard']  = $user['dashboard'];
            $_SESSION['can_customers']  = $user['customers'];
            $_SESSION['can_meters']     = $user['meters'];
            $_SESSION['can_readings']   = $user['readings'];
            $_SESSION['can_bills']      = $user['bills'];
            $_SESSION['can_payments']   = $user['payments'];
            $_SESSION['can_reports']    = $user['reports'];
            $_SESSION['can_users_page'] = $user['users_page'];
            $_SESSION['can_settings']   = $user['settings'];

            if ($user['dashboard'] == 1) { header("Location: index.php"); }
            elseif ($user['customers'] == 1) { header("Location: customers.php"); }
            else { header("Location: index.php"); }
            exit();
        } else { 
            $error = "Incorrect password! Please try again."; 
        }
    } else { 
        $error = "Username or Role not found!"; 
    }
}

// ==========================================
// SECTION 2: GMAIL OTP ACCOUNT RECOVERY
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_request_gmail'])) {
    $gmail_input = mysqli_real_escape_string($conn, $_POST['gmail']);
    $username_input = mysqli_real_escape_string($conn, $_POST['username']);
    
    // Nidaamku wuxuu hubinayaa in username-ka iyo gmail-ka isku rekoor yihiin, qofkuna yahay Active
    $sql = "SELECT * FROM users WHERE username='$username_input' AND gmail='$gmail_input' AND status='Active' LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $otp = rand(100000, 999999);
        
        // Ku kaydi koodhka OTP-ga ah database-ka
        $conn->query("UPDATE users SET otp_code='$otp' WHERE id='".$user['id']."'");
        
        $mail = new PHPMailer(true);
        
        try {
            // Server Configurations (Gmail SMTP)
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'maxamedsacad124@gmail.com'; // Iimaylkaaga halkan ku qor
            $mail->Password   = 'jrulkrvewphawblp';      // 16-kii xarfood halkan ku qor (Calaamadaha ha ka saarin)
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            
            // Macluumaadka Diraha iyo Qaataha
            $mail->setFrom('YOUR_GMAIL_ADDRESS@gmail.com', 'Water ERP Security');
            $mail->addAddress($gmail_input, $user['username']);
            
            // Naqshadda Iimaylka loo dirayo qofka (HTML Design)
            $mail->isHTML(true);
            $mail->Subject = 'Water ERP - Account Recovery OTP';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; padding: 20px; background-color: #f4f4f4;'>
                    <div style='max-width: 500px; margin: auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-top: 4px solid #0c8599;'>
                        <h2 style='color: #0c8599; text-align: center; margin-bottom: 20px;'>Water System</h2>
                        <p>Hello <strong>{$user['username']}</strong>,</p>
                        <p>We received a request to reset your password. Please use the secure 6-digit verification code below to proceed:</p>
                        <div style='text-align: center; margin: 30px 0;'>
                            <span style='background: #0f172a; color: #f59e0b; padding: 12px 30px; font-size: 26px; font-weight: bold; font-family: monospace; border-radius: 6px; letter-spacing: 5px; display: inline-block;'>$otp</span>
                        </div>
                        <p style='color: #ef4444; font-size: 13px;'><strong>Note:</strong> This code is temporary and confidential. Do not share it with anyone.</p>
                        <hr style='border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;'>
                        <p style='color: #64748b; font-size: 11px; text-align: center;'>If you did not request a password reset, please secure your account immediately.</p>
                    </div>
                </div>
            ";
            
            $mail->send();
            
            $_SESSION['reset_user_id'] = $user['id'];
            $step = 2; // U gudbi foomka labaad ee lagu qorayo koodhka iyo fure cusub
            $message = "A secure verification code has been dispatched to <span class='text-cyan-400 font-semibold'>$gmail_input</span>. Please check your inbox or spam folder.";
        } catch (Exception $e) {
            $message = "<span class='text-red-400 font-semibold'><i class='fa-solid fa-circle-xmark'></i> Email delivery failed! Mailer Error: {$mail->ErrorInfo}</span>";
        }
    } else {
        $message = "<span class='text-red-400 font-semibold'><i class='fa-solid fa-triangle-exclamation'></i> Authentication failed! The provided details do not match an Active account.</span>";
    }
}

// ==========================================
// SECTION 3: VERIFY OTP & RESET PASSWORD
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_verify_gmail_otp'])) {
    $user_id = $_SESSION['reset_user_id'];
    $otp_input = mysqli_real_escape_string($conn, $_POST['otp_code']);
    $new_password = mysqli_real_escape_string($conn, $_POST['new_password']);
    
    $sql = "SELECT * FROM users WHERE id='$user_id' AND otp_code='$otp_input' LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        // Cusboonaysii password-ka, tirtir koodhkii OTP-ga ahaa (ka dhig NULL)
        $conn->query("UPDATE users SET password='$new_password', otp_code=NULL WHERE id='$user_id'");
        unset($_SESSION['reset_user_id']);
        echo "<script>alert('Your password has been successfully updated!'); window.location.href='login.php';</script>";
        exit();
    } else {
        $step = 2;
        $message = "<span class='text-red-400 font-semibold'><i class='fa-solid fa-circle-xmark'></i> Invalid OTP Code! Please check your email again.</span>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Water- Sign In</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- THEME PERSISTENCE: isla habka sidebar.php isticmaalo, si login.php sidoo kale
         u xasuusto light/dark mode-kii hore loo doortay boggaga kale -->
    <style>
        body.light-mode { background-color: #f8fafc !important; color: #0f172a !important; }

        body.light-mode [class*="bg-slate-950"],
        body.light-mode [class*="bg-slate-900"] { background-color: #ffffff !important; }

        body.light-mode [class*="bg-slate-800"] { background-color: #f1f5f9 !important; }

        body.light-mode [class*="bg-slate-700"] { background-color: #e2e8f0 !important; }

        body.light-mode [class*="bg-slate-600"] { background-color: #cbd5e1 !important; }

        body.light-mode [class*="border-slate-700"],
        body.light-mode [class*="divide-slate-700"] { border-color: #cbd5e1 !important; }

        body.light-mode [class*="border-slate-600"] { border-color: #94a3b8 !important; }

        body.light-mode [class*="text-white"] { color: #0f172a !important; }

        /* login.php wuxuu isticmaalaa Tailwind "gray" palette-ka (ma aha "slate") halkan */
        body.light-mode [class*="text-gray-200"] { color: #1e293b !important; }
        body.light-mode [class*="text-gray-300"] { color: #334155 !important; }
        body.light-mode [class*="text-gray-400"] { color: #64748b !important; }

        body.light-mode [class*="bg-red-900"] { background-color: #fee2e2 !important; }
        body.light-mode [class*="bg-red-500"] { background-color: #fee2e2 !important; }
        body.light-mode [class*="border-red-500"] { border-color: #fecaca !important; }
        body.light-mode [class*="text-red-400"],
        body.light-mode [class*="text-red-300"] { color: #b91c1c !important; }

        body.light-mode ::placeholder { color: #94a3b8 !important; opacity: 1; }
    </style>
</head>
<body class="bg-slate-900 min-h-screen flex items-center justify-center p-4 relative overflow-hidden">
    <script>
        // Marka bogga si buuxda u soo furmo, class-ka light-mode waxaa lagu darayaa body-ga
        // haddii user-ku horey uga doortay boggaga kale (isla localStorage key-ga sidebar.php isticmaalo)
        if (localStorage.getItem('site_theme') === 'light') {
            document.body.classList.add('light-mode');
        }
    </script>

    <div class="absolute w-96 h-96 bg-cyan-500/10 rounded-full blur-3xl -top-20 -left-20"></div>
    <div class="absolute w-96 h-96 bg-blue-600/10 rounded-full blur-3xl -bottom-20 -right-20"></div>

    <div class="bg-slate-800/80 backdrop-blur-md w-full max-w-md p-8 rounded-2xl shadow-2xl border border-slate-700/50 relative z-10">
        
        <div id="loginSection" class="<?php echo (isset($_POST['action_request_gmail']) || isset($_POST['action_verify_gmail_otp'])) ? 'hidden' : ''; ?>">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-cyan-500 text-white flex items-center justify-center text-3xl rounded-2xl mx-auto shadow-lg shadow-cyan-500/20 mb-3">
                    <i class="fa-solid fa-droplet"></i>
                </div>
                <h1 class="text-2xl font-bold text-white tracking-wide">Water System</h1>
                <p class="text-gray-400 text-sm mt-1">Please sign in to access your portal</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="mb-4 p-3 bg-red-500/10 border border-red-500/20 text-red-400 rounded-lg text-sm flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="space-y-5">
                <input type="hidden" name="action_login" value="1">
                <div>
                    <label class="block text-gray-300 text-sm font-medium mb-1.5">User Role</label>
                    <select name="role" required class="w-full px-4 py-2.5 bg-slate-700/50 border border-slate-600 rounded-xl text-white focus:outline-none focus:border-cyan-500 transition">
                        <option value="" disabled selected>Select your role...</option>
                        <option value="Admin">Admin</option>
                        <option value="Manager">Manager</option>
                        <option value="Cashier">Cashier</option>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-300 text-sm font-medium mb-1.5">Username</label>
                    <input type="text" name="username" required placeholder="Enter username" class="w-full px-4 py-2.5 bg-slate-700/50 border border-slate-600 rounded-xl text-white focus:outline-none focus:border-cyan-500 transition">
                </div>
                <div>
                    <div class="flex justify-between items-center mb-1.5">
                        <label class="text-gray-300 text-sm font-medium">Password</label>
                        <button type="button" onclick="showReset()" class="text-xs text-cyan-400 hover:underline focus:outline-none">Forgot Password?</button>
                    </div>
                    <div class="relative">
                        <input type="password" id="passwordField" name="password" required placeholder="••••••••" class="w-full pl-4 pr-10 py-2.5 bg-slate-700/50 border border-slate-600 rounded-xl text-white focus:outline-none focus:border-cyan-500 transition">
                        <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-white">
                            <i class="fa-solid fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="w-full bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-600 hover:to-blue-700 text-white font-medium py-2.5 rounded-xl transition shadow-lg shadow-cyan-500/25">
                    Sign In <i class="fa-solid fa-arrow-right-to-bracket ml-1.5"></i>
                </button>
            </form>
        </div>

        <div id="resetSection" class="<?php echo (isset($_POST['action_request_gmail']) || isset($_POST['action_verify_gmail_otp'])) ? '' : 'hidden'; ?>">
            <div class="text-center mb-6">
                <h1 class="text-xl font-bold text-white tracking-wide"><i class="fa-regular fa-envelope text-cyan-400 mr-1.5"></i> Gmail OTP Account Recovery</h1>
                <p class="text-gray-400 text-xs mt-1">Verify your email details to retrieve account access.</p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="mb-4 p-4 bg-slate-700/80 border border-slate-600 text-gray-200 rounded-xl text-xs text-center leading-relaxed">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($step == 1): ?>
                <form action="" method="POST" class="space-y-4">
                    <input type="hidden" name="action_request_gmail" value="1">
                    <div>
                        <label class="block text-gray-300 text-xs font-medium mb-1.5">Username</label>
                        <input type="text" name="username" required placeholder="Your registered username" class="w-full px-4 py-2.5 bg-slate-700/50 border border-slate-600 rounded-xl text-white focus:outline-none focus:border-cyan-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-gray-300 text-xs font-medium mb-1.5">Registered Gmail</label>
                        <input type="email" name="gmail" required placeholder="e.g. name@gmail.com" class="w-full px-4 py-2.5 bg-slate-700/50 border border-slate-600 rounded-xl text-white focus:outline-none focus:border-cyan-500 text-sm">
                    </div>
                    <button type="submit" class="w-full bg-cyan-600 hover:bg-cyan-700 text-white font-medium py-2.5 rounded-xl transition text-sm">
                        Request Verification OTP <i class="fa-solid fa-paper-plane ml-1"></i>
                    </button>
                </form>
            <?php else: ?>
                <form action="" method="POST" class="space-y-4">
                    <input type="hidden" name="action_verify_gmail_otp" value="1">
                    <div>
                        <label class="block text-gray-300 text-xs font-medium mb-1.5">6-Digit OTP Code</label>
                        <input type="text" name="otp_code" required placeholder="xxxxxx" class="w-full px-4 py-2.5 bg-slate-700/50 border border-slate-600 rounded-xl text-white text-center font-mono tracking-widest focus:outline-none focus:border-cyan-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-gray-300 text-xs font-medium mb-1.5">New Password</label>
                        <input type="password" name="new_password" required placeholder="Create a new password" class="w-full px-4 py-2.5 bg-slate-700/50 border border-slate-600 rounded-xl text-white focus:outline-none focus:border-cyan-500 text-sm">
                    </div>
                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2.5 rounded-xl transition text-sm">
                        Reset Password <i class="fa-solid fa-circle-check ml-1"></i>
                    </button>
                </form>
            <?php endif; ?>

            <div class="text-center mt-5">
                <button type="button" onclick="showLogin()" class="text-xs text-gray-400 hover:text-white focus:outline-none"><i class="fa-solid fa-arrow-left mr-1"></i> Back to Sign In</button>
            </div>
        </div>

    </div>

    <script>
        function togglePassword() {
            const passwordField = document.getElementById('passwordField');
            const eyeIcon = document.getElementById('eyeIcon');
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                eyeIcon.className = 'fa-solid fa-eye-slash';
            } else {
                passwordField.type = 'password';
                eyeIcon.className = 'fa-solid fa-eye';
            }
        }
        function showReset() {
            document.getElementById('loginSection').classList.add('hidden');
            document.getElementById('resetSection').classList.remove('hidden');
        }
        function showLogin() {
            document.getElementById('resetSection').classList.add('hidden');
            document.getElementById('loginSection').classList.remove('hidden');
        }
    </script>
</body>
</html>