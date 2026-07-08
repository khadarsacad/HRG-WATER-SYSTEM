<?php
session_start();
require_once "database.php";

// Hubi haddii user-ku uu login yahay
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

// ============================
// SAVE & UPDATE CUSTOMER
// ============================
if(isset($_POST['save_cust'])){

    $id    = mysqli_real_escape_string($conn,$_POST['id']);
    $name  = mysqli_real_escape_string($conn,$_POST['name']);
    $phone = mysqli_real_escape_string($conn,$_POST['phone']);
    $loc   = mysqli_real_escape_string($conn,$_POST['location']);

    if($id!=""){

        mysqli_query($conn,"
            UPDATE customers
            SET
            customer_name='$name',
            phone='$phone',
            location='$loc'
            WHERE id='$id'
        ");

    }else{

        mysqli_query($conn,"
            INSERT INTO customers
            (customer_name,phone,location,status)
            VALUES
            ('$name','$phone','$loc','Active')
        ");

    }

    header("Location: customers.php");
    exit();

}


// ============================
// ACTIVE CUSTOMER
// ============================
if(isset($_GET['active'])){

    $id=(int)$_GET['active'];

    mysqli_query($conn,"
        UPDATE customers
        SET status='Active'
        WHERE id='$id'
    ");

    header("Location: customers.php");
    exit();

}


// ============================
// INACTIVE CUSTOMER
// ============================
if(isset($_GET['inactive'])){

    $id=(int)$_GET['inactive'];

    mysqli_query($conn,"
        UPDATE customers
        SET status='Inactive'
        WHERE id='$id'
    ");

    header("Location: customers.php");
    exit();

}


// ============================
// SEARCH
// ============================

$search = isset($_GET['search'])
? mysqli_real_escape_string($conn,$_GET['search'])
: '';

$where = $search!=""
?
"WHERE
customer_name LIKE '%$search%'
OR phone LIKE '%$search%'
OR location LIKE '%$search%'"
:
"";

$result = mysqli_query($conn,"
SELECT *
FROM customers
$where
ORDER BY id DESC
");

include "sidebar.php";
?>
<div class="p-6">

    <div class="bg-slate-800 p-4 rounded-xl border border-slate-700 mb-6">
        <form method="POST" id="custForm" class="flex items-end gap-3">

            <input type="hidden" name="id" id="cust_id">

            <div class="flex-1">
                <label class="text-xs text-slate-400">Name</label>
                <input type="text" name="name" id="cust_name" required
                    class="w-full bg-slate-900 border border-slate-700 rounded p-2 text-white">
            </div>

            <div class="flex-1">
                <label class="text-xs text-slate-400">Phone</label>
                <input type="text" name="phone" id="cust_phone" required
                    class="w-full bg-slate-900 border border-slate-700 rounded p-2 text-white">
            </div>

            <div class="flex-1">
                <label class="text-xs text-slate-400">Location</label>
                <input type="text" name="location" id="cust_loc" required
                    class="w-full bg-slate-900 border border-slate-700 rounded p-2 text-white">
            </div>

            <button type="submit"
                name="save_cust"
                id="saveBtn"
                class="bg-blue-600 text-white px-6 py-2 rounded font-bold">
                Save Customer
            </button>

            <button type="button"
                onclick="clearForm()"
                class="bg-slate-700 text-white px-4 py-2 rounded font-bold">
                Clear
            </button>

        </form>
    </div>


    <div class="bg-slate-800 rounded-xl border border-slate-700 overflow-hidden">

        <div class="p-4 border-b border-slate-700 flex gap-2">

            <form method="GET" class="flex gap-2">

                <input
                    type="text"
                    name="search"
                    placeholder="Search..."
                    value="<?php echo htmlspecialchars($search); ?>"
                    class="bg-slate-900 border border-slate-700 rounded p-2 text-white w-64">

                <button
                    type="submit"
                    class="<?php echo $search!='' ? 'bg-blue-600' : 'bg-slate-600'; ?> text-white px-4 py-2 rounded">
                    Search
                </button>

                <?php if($search!=''): ?>

                <button
                    type="button"
                    onclick="window.location='customers.php'"
                    class="bg-red-600 text-white px-4 py-2 rounded">
                    Clear Search
                </button>

                <?php endif; ?>

            </form>

        </div>


        <table class="w-full text-left text-sm text-slate-300">

            <thead class="bg-slate-700 text-white">

                <tr>

                    <th class="p-3">Name</th>

                    <th class="p-3">Phone</th>

                    <th class="p-3">Location</th>

                    <th class="p-3">Status</th>

                    <th class="p-3">Actions</th>

                </tr>

            </thead>
            <tbody>

<?php while($row = mysqli_fetch_assoc($result)): ?>

<tr class="border-b border-slate-700 hover:bg-slate-700/40">

    <td class="p-3">
        <?php echo $row['customer_name']; ?>
    </td>

    <td class="p-3">
        <?php echo $row['phone']; ?>
    </td>

    <td class="p-3">
        <?php echo $row['location']; ?>
    </td>

    <!-- STATUS -->
    <td class="p-3">

        <?php if($row['status']=="Active"){ ?>

            <span class="bg-green-600 text-white px-3 py-1 rounded-full text-xs font-bold">
                Active
            </span>

        <?php }else{ ?>

            <span class="bg-red-600 text-white px-3 py-1 rounded-full text-xs font-bold">
                Inactive
            </span>

        <?php } ?>

    </td>

    <!-- ACTIONS -->
    <td class="p-3">

        <button
            onclick='edit(
                <?php echo $row["id"]; ?>,
                <?php echo json_encode($row["customer_name"]); ?>,
                <?php echo json_encode($row["phone"]); ?>,
                <?php echo json_encode($row["location"]); ?>
            )'
            class="text-blue-400 font-bold hover:text-blue-300">
            Edit
        </button>

        <?php if($row['status']=="Active"){ ?>

            <a href="customers.php?inactive=<?php echo $row['id']; ?>"
               class="text-red-400 font-bold ml-3 hover:text-red-300">
                Inactive
            </a>

        <?php }else{ ?>

            <a href="customers.php?active=<?php echo $row['id']; ?>"
               class="text-green-400 font-bold ml-3 hover:text-green-300">
                Active
            </a>

        <?php } ?>

    </td>

</tr>

<?php endwhile; ?>

</tbody>
    </div>

</div>

<script>

function edit(id, name, phone, loc){

    document.getElementById("cust_id").value = id;
    document.getElementById("cust_name").value = name;
    document.getElementById("cust_phone").value = phone;
    document.getElementById("cust_loc").value = loc;

    document.getElementById("saveBtn").innerHTML = "Update Customer";

}

function clearForm(){

    document.getElementById("cust_id").value = "";

    document.getElementById("cust_name").value = "";

    document.getElementById("cust_phone").value = "";

    document.getElementById("cust_loc").value = "";

    document.getElementById("saveBtn").innerHTML = "Save Customer";

    document.getElementById("cust_name").focus();

}

</script>