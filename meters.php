<?php
session_start();
require_once "database.php";

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

/*=========================================
=            SAVE / UPDATE METER          =
=========================================*/

if(isset($_POST['save_meter'])){

    $id          = mysqli_real_escape_string($conn,$_POST['id']);
    $customer_id = mysqli_real_escape_string($conn,$_POST['customer_id']);
    $meter       = mysqli_real_escape_string($conn,$_POST['meter_number']);

    if($id==""){

        mysqli_query($conn,"
            INSERT INTO meters
            (customer_id,meter_number,status)
            VALUES
            ('$customer_id','$meter','Active')
        ");

    }else{

        mysqli_query($conn,"
            UPDATE meters
            SET
                customer_id='$customer_id',
                meter_number='$meter'
            WHERE id='$id'
        ");

    }

    header("Location: meters.php");
    exit();

}

/*=========================================
=         AUTO METER NUMBER               =
=========================================*/

$next_num="MT-1001";

$get=mysqli_query($conn,"
SELECT meter_number
FROM meters
ORDER BY id DESC
LIMIT 1
");

if(mysqli_num_rows($get)>0){

    $last=mysqli_fetch_assoc($get);

    $num=(int)str_replace("MT-","",$last['meter_number']);

    $next_num="MT-".($num+1);

}

/*=========================================
=        ACTIVE / INACTIVE                =
=========================================*/

if(isset($_GET['active'])){

    $id=(int)$_GET['active'];

    mysqli_query($conn,"
        UPDATE meters
        SET status='Active'
        WHERE id='$id'
    ");

    header("Location: meters.php");
    exit();

}

if(isset($_GET['inactive'])){

    $id=(int)$_GET['inactive'];

    mysqli_query($conn,"
        UPDATE meters
        SET status='Inactive'
        WHERE id='$id'
    ");

    header("Location: meters.php");
    exit();

}

/*=========================================
=               SEARCH                    =
=========================================*/

$search="";

if(isset($_GET['search'])){

    $search=mysqli_real_escape_string($conn,$_GET['search']);

}

$where="";

if($search!=""){

$where="
WHERE
customers.customer_name LIKE '%$search%'
OR meters.meter_number LIKE '%$search%'
OR meters.status LIKE '%$search%'
";

}

$result=mysqli_query($conn,"
SELECT
meters.*,
customers.customer_name
FROM meters
INNER JOIN customers
ON customers.id=meters.customer_id
$where
ORDER BY meters.id DESC
");

include "sidebar.php";
?>
<div class="h-full flex flex-col gap-4 p-6 overflow-hidden">

    <!-- FORM -->
    <div class="bg-slate-800 p-4 rounded-xl border border-slate-700/50 shrink-0">

        <form id="meterForm" method="POST" class="flex items-end gap-3">

            <input type="hidden" name="id" id="m_id">

            <div class="flex-1">

                <label class="text-[10px] text-slate-400 uppercase font-bold">
                    Customer
                </label>

                <select
                    name="customer_id"
                    id="m_cust_id"
                    required
                    class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2 text-white text-xs">

                    <option value="">Select Customer</option>

                    <?php

                    $customers=mysqli_query($conn,"
                    SELECT id,customer_name
                    FROM customers
                    ORDER BY customer_name ASC
                    ");

                    while($c=mysqli_fetch_assoc($customers)){

                    ?>

                    <option value="<?php echo $c['id']; ?>">

                        <?php echo htmlspecialchars($c['customer_name']); ?>

                    </option>

                    <?php } ?>

                </select>

            </div>

            <div class="flex-1">

                <label class="text-[10px] text-slate-400 uppercase font-bold">
                    Meter Number
                </label>

                <input
                    type="text"
                    name="meter_number"
                    id="m_num"
                    value="<?php echo $next_num; ?>"
                    readonly
                    class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2 text-white text-xs">

            </div>

            <button
                type="submit"
                name="save_meter"
                id="saveBtn"
                class="bg-blue-600 text-white px-6 py-2 rounded-lg text-xs font-bold hover:bg-blue-700">

                Assign Meter

            </button>

            <button
                type="button"
                onclick="resetMeterForm()"
                class="bg-slate-700 text-white px-4 py-2 rounded-lg text-xs font-bold">

                Clear

            </button>

        </form>

    </div>


    <!-- TABLE -->

    <div class="flex-1 bg-slate-800 rounded-xl border border-slate-700/50 overflow-hidden flex flex-col">

        <div class="p-3 border-b border-slate-700/50">

            <form method="GET" class="flex gap-2">

                <input
                    type="text"
                    name="search"
                    value="<?php echo htmlspecialchars($search); ?>"
                    placeholder="Search..."
                    class="bg-slate-900 border border-slate-700 rounded-lg p-2 text-xs text-white w-64">

                <button
                    type="submit"
                    class="<?php echo ($search!='')?'bg-blue-600':'bg-slate-600'; ?> text-white px-4 py-2 rounded-lg text-xs font-bold">

                    Search

                </button>

                <?php if($search!=""){ ?>

                    <button
                        type="button"
                        onclick="window.location='meters.php'"
                        class="bg-red-600 text-white px-4 py-2 rounded-lg text-xs font-bold">

                        Clear Search

                    </button>

                <?php } ?>

            </form>

        </div>

        <div class="overflow-y-auto flex-1">

            <table class="w-full text-left text-xs">

                <thead class="bg-blue-600 text-white sticky top-0">

                    <tr>

                        <th class="p-3">ID</th>

                        <th class="p-3">Customer</th>

                        <th class="p-3">Meter No</th>

                        <th class="p-3">Status</th>

                        <th class="p-3">Actions</th>

                    </tr>

                </thead>
                <tbody class="divide-y divide-slate-700/30">

<?php while($row = mysqli_fetch_assoc($result)): ?>

<tr class="hover:bg-slate-700/20">

    <td class="p-3 text-slate-400">
        #<?php echo $row['id']; ?>
    </td>

    <td class="p-3 text-white">
        <?php echo htmlspecialchars($row['customer_name']); ?>
    </td>

    <td class="p-3 text-blue-400 font-bold">
        <?php echo htmlspecialchars($row['meter_number']); ?>
    </td>

    <!-- STATUS -->
    <td class="p-3">

        <?php if($row['status']=="Active"){ ?>

            <span class="bg-emerald-500/20 text-emerald-400 px-2 py-1 rounded text-[10px] font-bold">
                Active
            </span>

        <?php }else{ ?>

            <span class="bg-rose-500/20 text-rose-400 px-2 py-1 rounded text-[10px] font-bold">
                Inactive
            </span>

        <?php } ?>

    </td>

    <!-- ACTIONS -->
    <td class="p-3 flex gap-4">

        <button
            type="button"
            onclick='editMeter(
                <?php echo $row["id"]; ?>,
                <?php echo $row["customer_id"]; ?>,
                <?php echo json_encode($row["meter_number"]); ?>
            )'
            class="text-blue-400 font-bold hover:underline">

            Edit

        </button>

        <?php if($row['status']=="Active"){ ?>

            <a
                href="meters.php?inactive=<?php echo $row['id']; ?>"
                class="text-red-400 font-bold hover:underline">

                Inactive

            </a>

        <?php }else{ ?>

            <a
                href="meters.php?active=<?php echo $row['id']; ?>"
                class="text-green-400 font-bold hover:underline">

                Active

            </a>

        <?php } ?>

    </td>

</tr>

<?php endwhile; ?>

</tbody>
            </table>

        </div>

    </div>

</div>

<script>

function editMeter(id, customer_id, meter){

    document.getElementById("m_id").value=id;

    document.getElementById("m_cust_id").value=customer_id;

    document.getElementById("m_num").value=meter;

    let btn=document.getElementById("saveBtn");

    btn.innerHTML="Update Meter";

    btn.className="bg-amber-500 text-white px-6 py-2 rounded-lg text-xs font-bold hover:bg-amber-600";

}

function resetMeterForm(){

    document.getElementById("meterForm").reset();

    document.getElementById("m_id").value="";

    document.getElementById("m_num").value="<?php echo $next_num; ?>";

    let btn=document.getElementById("saveBtn");

    btn.innerHTML="Assign Meter";

    btn.className="bg-blue-600 text-white px-6 py-2 rounded-lg text-xs font-bold hover:bg-blue-700";

}

</script>