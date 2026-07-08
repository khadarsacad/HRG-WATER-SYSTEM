<?php
session_start();
require_once "database.php";

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

/*=====================================================
=            SAVE / UPDATE READING LOGIC              =
=====================================================*/

$save_error = "";

if(isset($_POST['save_reading'])){

    $id              = mysqli_real_escape_string($conn,$_POST['id']);
    $meter_id        = mysqli_real_escape_string($conn,$_POST['meter_id']);
    $current_reading = mysqli_real_escape_string($conn,$_POST['current_reading']);
    $reading_date    = mysqli_real_escape_string($conn,$_POST['reading_date']);

    if(empty($id)){

        /* --- INSERT MODE: hubi in isla meter-kan uusan horeyba u haysan reading Unbilled ah --- */

        $dupCheck = mysqli_query($conn,"
            SELECT id FROM meter_readings
            WHERE meter_id='$meter_id' AND status='Unbilled'
            LIMIT 1
        ");

        if(mysqli_num_rows($dupCheck) > 0){

            $save_error = "Meter-kan horeyba wuxuu haystaa Akhris (Reading) Unbilled ah oo aan weli la bill gareyn. Fadlan isticmaal badhanka 'Edit' ee safka hoose si aad u hagaajiso akhriskii hore, halkii aad mid cusub u samayn lahayd.";

        } else {

            /* Previous Reading */

            $previous_reading="0.000";

            $last=mysqli_query($conn,"
               SELECT current_reading
        FROM meter_readings
        WHERE meter_id='$meter_id'
        ORDER BY reading_date DESC, id DESC
        LIMIT 1
            ");

            if(mysqli_num_rows($last)>0){

                $prev=mysqli_fetch_assoc($last);

                $previous_reading=$prev['current_reading'];

            }

            /* Units Used */

            $units_used=floatval($current_reading)-floatval($previous_reading);

            if($units_used<0){
                $units_used=0;
            }

            mysqli_query($conn,"
                INSERT INTO meter_readings
                (
                    meter_id,
                    previous_reading,
                    current_reading,
                    units_used,
                    reading_date,
                    status
                )
                VALUES
                (
                    '$meter_id',
                    '$previous_reading',
                    '$current_reading',
                    '$units_used',
                    '$reading_date',
                    'Unbilled'
                )
            ");

            header("Location: readings.php");
            exit();

        }

    }else{

        /* --- UPDATE MODE: reading jira ayaa la hagaajinayaa --- */

        /* Previous Reading waxaa mar kale la xisaabinayaa (meter-ka iskaga duwan yaraan karo) */

        $previous_reading="0.000";

        $last=mysqli_query($conn,"
           SELECT current_reading
    FROM meter_readings
    WHERE meter_id='$meter_id' AND id != '$id'
    ORDER BY reading_date DESC, id DESC
    LIMIT 1
        ");

        if(mysqli_num_rows($last)>0){

            $prev=mysqli_fetch_assoc($last);

            $previous_reading=$prev['current_reading'];

        }

        $units_used=floatval($current_reading)-floatval($previous_reading);

        if($units_used<0){
            $units_used=0;
        }

        mysqli_query($conn,"
            UPDATE meter_readings
            SET
                meter_id='$meter_id',
                previous_reading='$previous_reading',
                current_reading='$current_reading',
                units_used='$units_used',
                reading_date='$reading_date'
            WHERE id='$id'
        ");

        header("Location: readings.php");
        exit();

    }

}

/*=====================================================
=                    SEARCH                           =
=====================================================*/

$search="";

if(isset($_GET['search'])){

    $search=mysqli_real_escape_string($conn,$_GET['search']);

}

$where="";

if($search!=""){

    $where="
    WHERE

    m.meter_number LIKE '%$search%'

    OR

    c.customer_name LIKE '%$search%'
    ";

}

/*=====================================================
=              LOAD TABLE DATA                        =
=====================================================*/

$result=mysqli_query($conn,"
SELECT

mr.*,

m.meter_number,

m.customer_id,

c.customer_name

FROM meter_readings mr

INNER JOIN meters m
ON mr.meter_id=m.id

INNER JOIN customers c
ON m.customer_id=c.id

$where

ORDER BY mr.id DESC
");

/*=====================================================
=     LIISKA METERS-KA HAYSTA READING UNBILLED AH      =
=====================================================*/

$unbilledMeters = [];

$ubq = mysqli_query($conn, "SELECT DISTINCT meter_id FROM meter_readings WHERE status='Unbilled'");

while($u = mysqli_fetch_assoc($ubq)){
    $unbilledMeters[] = (int)$u['meter_id'];
}

include "sidebar.php";
?>
<div class="p-6">

    <!-- SEARCH -->

    <div class="bg-slate-800 p-4 rounded-xl border border-slate-700 mb-6">

        <form method="GET" class="flex gap-2">
<input type="hidden" id="meter_data" value='<?php
$map = [];

$q = mysqli_query($conn,"
SELECT meter_id, current_reading
FROM meter_readings
WHERE id IN (
    SELECT MAX(id)
    FROM meter_readings
    GROUP BY meter_id
)
");

while($r = mysqli_fetch_assoc($q)){
    $map[$r['meter_id']] = $r['current_reading'];
}

echo json_encode($map);
?>'>
<input type="hidden" id="unbilled_meters" value='<?php echo json_encode($unbilledMeters); ?>'>
            <input
                type="text"
                name="search"
                value="<?php echo htmlspecialchars($search); ?>"
                placeholder="Search Meter / Customer..."
                class="flex-1 bg-slate-900 border border-slate-700 rounded-lg p-3 text-white">

            <button
                type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 rounded-lg font-bold">

                Search

            </button>

            <?php if($search!=""){ ?>

            <button
                type="button"
                onclick="window.location='readings.php'"
                class="bg-red-600 hover:bg-red-700 text-white px-6 rounded-lg font-bold">

                Clear

            </button>

            <?php } ?>

        </form>

    </div>


    <!-- ENTRY FORM -->

    <div class="bg-slate-800 p-6 rounded-xl border border-slate-700 mb-6">

        <?php if(!empty($save_error)): ?>
        <div class="bg-red-900/40 border border-red-600 text-red-300 text-sm px-4 py-2 rounded-lg mb-4">
            ⚠ <?php echo htmlspecialchars($save_error); ?>
        </div>
        <?php endif; ?>

        <div id="dupWarning" class="hidden bg-amber-900/40 border border-amber-600 text-amber-300 text-sm px-4 py-2 rounded-lg mb-4">
            ⚠ Meter-kan horeyba wuxuu haystaa Akhris (Reading) Unbilled ah. Fadlan isticmaal badhanka 'Edit' ee safka hoose halkii aad mid cusub u samayn lahayd.
        </div>

        <form method="POST" id="readingForm" onsubmit="return validateReadingForm();">

            <input type="hidden" name="id" id="r_id">

           <div class="grid grid-cols-1 md:grid-cols-7 gap-4">

                <!-- Meter + Customer -->

                <div>

                    <label class="text-[9px] text-slate-400 uppercase font-semibold tracking-wider">

                        Meter / Customer

                    </label>

                    <select
                        name="meter_id"
                        id="meter_id"
                        required
                        onchange="calculatePreviousFromDB(this.value)"
                        class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-white">

                        <option value="">Select Meter / Customer</option>

                        <?php

                        $meters=mysqli_query($conn,"
                        SELECT
                        m.id,
                        m.meter_number,
                        c.customer_name
                        FROM meters m
                        INNER JOIN customers c
                        ON m.customer_id=c.id
                        WHERE m.status='Active'
                        ORDER BY m.id ASC
                        ");

                        while($m=mysqli_fetch_assoc($meters)){

                        ?>

                        <option value="<?php echo $m['id'];?>">

                            <?php
                            echo $m['meter_number']." - ".$m['customer_name'];
                            ?>

                        </option>

                        <?php } ?>

                    </select>

                </div>


                <!-- Previous Reading -->

                <div>

                    <label class="text-[9px] text-slate-400 uppercase font-semibold tracking-wider">

                        Previous Reading

                    </label>

                    <input
                        type="text"
                        id="previous_reading"
                        readonly
                        value="00000.000"
                        class="w-full bg-slate-700 border border-slate-600 rounded-lg p-2.5 text-white font-bold">

                </div>


                <!-- Current Reading -->

                <div>

                    <label class="text-[9px] text-slate-400 uppercase font-semibold tracking-wider">

                        Current Reading

                    </label>

                    <input
                        type="number"
                        step="0.001"
                        min="0"
                        name="current_reading"
                        id="current_reading"
                        onkeyup="calculateUnits()"
                        onchange="calculateUnits()"
                        required
                        class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-white">

                </div>


                <!-- Units -->

                <div>

                    <label class="text-[9px] text-slate-400 uppercase font-semibold tracking-wider">

                        Units Used

                    </label>

                    <input
                        type="text"
                        id="units_used"
                        readonly
                        value="0.000"
                        class="w-full bg-slate-700 border border-slate-600 rounded-lg p-2.5 text-emerald-400 font-bold">

                </div>


                <!-- Reading Date -->

                <div>

                    <label class="text-[9px] text-slate-400 uppercase font-semibold tracking-wider">

                        Reading Date

                    </label>

                    <input
                        type="date"
                        name="reading_date"
                        id="reading_date"
                        value="<?php echo date('Y-m-d');?>"
                        class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-white">

                </div>


                <!-- Buttons -->

               <div class="flex items-end gap-3">

 <button
type="submit"
name="save_reading"
id="saveBtn"
class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-2.5 rounded-lg font-bold">
Save
</button>

<button
type="button"
onclick="clearForm()"
class="bg-slate-700 hover:bg-slate-600 text-white px-8 py-2.5 rounded-lg font-bold">
Clear
</button>

                </div>

            </div>

        </form>

    </div>
        <!-- READINGS TABLE -->

    <div class="bg-slate-800 rounded-xl border border-slate-700 overflow-hidden">

        <div class="overflow-x-auto">

            <table class="w-full text-xs text-left">

                <thead class="bg-blue-600 text-white">

                    <tr>

                        <th class="p-3">Customer</th>

                        <th class="p-3">Meter</th>

                        <th class="p-3 text-right">Previous</th>

                        <th class="p-3 text-right">Current</th>

                        <th class="p-3 text-right">Units</th>

                        <th class="p-3">Reading Date</th>

                        <th class="p-3">Status</th>

                        <th class="p-3 text-center">Actions</th>

                    </tr>

                </thead>

                <tbody class="divide-y divide-slate-700">

<?php

while($row=mysqli_fetch_assoc($result)){

?>

<tr class="hover:bg-slate-700/30">

<td class="p-3 font-semibold text-white">

<?php echo $row['customer_name']; ?>

</td>

<td class="p-3 text-blue-400 font-bold">

<?php echo $row['meter_number']; ?>

</td>

<td class="p-3 text-right text-yellow-300">

<?php echo number_format($row['previous_reading'],3); ?>

</td>

<td class="p-3 text-right text-white font-bold">

<?php echo number_format($row['current_reading'],3); ?>

</td>

<td class="p-3 text-right text-emerald-400 font-bold">

<?php echo number_format($row['units_used'],3); ?>

</td>

<td class="p-3">

<?php echo $row['reading_date']; ?>

</td>

<td class="p-3">

<?php

if($row['status']=="Billed"){

?>

<span class="bg-emerald-600 text-white px-2 py-1 rounded text-xs">

Billed

</span>

<?php

}else{

?>

<span class="bg-amber-500 text-white px-2 py-1 rounded text-xs">

Unbilled

</span>

<?php

}

?>

</td>

<td class="p-3">

<div class="flex justify-center">

<button

onclick="editReading(

<?php echo $row['id'];?>,

<?php echo $row['meter_id'];?>,

'<?php echo $row['previous_reading'];?>',

'<?php echo $row['current_reading'];?>',

'<?php echo $row['reading_date'];?>'

)"

class="text-blue-400 hover:text-blue-300 font-bold">

Edit

</button>

</div>

</td>

</tr>

<?php

}

?>

                </tbody>

            </table>

        </div>

    </div>

</div>
<script>

function calculatePreviousFromDB(meter_id){

    if(meter_id == ""){
        document.getElementById("previous_reading").value = "00000.000";
        document.getElementById("dupWarning").classList.add("hidden");
        calculateUnits();
        return;
    }

    let data = JSON.parse(document.getElementById("meter_data").value || "{}");

    if(data[meter_id] !== undefined){

        document.getElementById("previous_reading").value = data[meter_id];

    }else{

        document.getElementById("previous_reading").value = "00000.000";
    }

    checkDuplicateUnbilled(meter_id);

    calculateUnits();
}

function checkDuplicateUnbilled(meter_id){
    // Haddii aan hadda edit-gareynayo (r_id leh qiimo), khaladka duplicate-ka ha la tusin
    let editingId = document.getElementById("r_id").value;
    if(editingId){
        document.getElementById("dupWarning").classList.add("hidden");
        return;
    }

    let unbilledList = JSON.parse(document.getElementById("unbilled_meters").value || "[]");

    if(unbilledList.includes(parseInt(meter_id))){
        document.getElementById("dupWarning").classList.remove("hidden");
    } else {
        document.getElementById("dupWarning").classList.add("hidden");
    }
}

function validateReadingForm(){
    let editingId = document.getElementById("r_id").value;

    if(editingId){
        // Edit mode - duplicate check lama baahna
        return true;
    }

    let meter_id = document.getElementById("meter_id").value;
    let unbilledList = JSON.parse(document.getElementById("unbilled_meters").value || "[]");

    if(unbilledList.includes(parseInt(meter_id))){
        alert("Meter-kan horeyba wuxuu haystaa Akhris (Reading) Unbilled ah. Fadlan isticmaal badhanka 'Edit' ee safka hoose halkii aad mid cusub u samayn lahayd.");
        return false;
    }

    return true;
}

function editReading(id, meter_id, previous_reading, current_reading, reading_date){
    document.getElementById("r_id").value = id;
    document.getElementById("meter_id").value = meter_id;
    document.getElementById("previous_reading").value = parseFloat(previous_reading).toFixed(3);
    document.getElementById("current_reading").value = current_reading;
    document.getElementById("reading_date").value = reading_date;

    document.getElementById("dupWarning").classList.add("hidden");

    calculateUnits();

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function calculateUnits(){
    let prev = parseFloat(document.getElementById("previous_reading").value) || 0;
    let curr = parseFloat(document.getElementById("current_reading").value) || 0;

    let units = curr - prev;
    if(units < 0){ units = 0; }

    document.getElementById("units_used").value = units.toFixed(3);
}

function clearForm(){
    document.getElementById("readingForm").reset();
    document.getElementById("r_id").value = "";
    document.getElementById("previous_reading").value = "00000.000";
    document.getElementById("units_used").value = "0.000";
    document.getElementById("dupWarning").classList.add("hidden");
}
</script>