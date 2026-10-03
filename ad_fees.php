<?php
session_start();
if(!isset($_SESSION['email']) || $_SESSION['role'] != 'admin'){
    header("Location: index.php");
    exit();
}
include 'connect.php';

// Handle Insert or Update Fees
if(isset($_POST['add_fees'])){
    $student_name = $_POST['student_name'];
    $student_id = $_POST['student_id'];
    $sem = $_POST['sem'];
    $total_amount = $_POST['total_amount'];
    $amount_paid = $_POST['amount_paid'];
    $balance = $total_amount - $amount_paid;
    $status = ($balance == 0) ? 'Paid' : (($amount_paid > 0) ? 'Partially Paid' : 'Unpaid');
    $payment_date = ($amount_paid > 0) ? $_POST['payment_date'] : NULL;

    // Check if record already exists
    $check_sql = "SELECT id FROM fees WHERE student_id = ? AND sem = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ss", $student_id, $sem);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if($check_result->num_rows > 0){
        // Update existing record
        $row = $check_result->fetch_assoc();
        $update_sql = "UPDATE fees SET student_name=?, total_amount=?, amount_paid=?, balance=?, status=?, payment_date=? WHERE id=?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("sdddssi", $student_name, $total_amount, $amount_paid, $balance, $status, $payment_date, $row['id']);
        $update_stmt->execute();
        $update_stmt->close();
    } else {
        // Insert new record
        $insert_sql = "INSERT INTO fees (student_name, student_id, sem, total_amount, amount_paid, balance, status, payment_date) VALUES (?,?,?,?,?,?,?,?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("sssdddss", $student_name, $student_id, $sem, $total_amount, $amount_paid, $balance, $status, $payment_date);
        $stmt->execute();
        $stmt->close();
    }
    $check_stmt->close();
}

// Update record manually
if(isset($_POST['update_fees'])){
    $id = $_POST['fees_id'];
    $total_amount = $_POST['total_amount'];
    $amount_paid = $_POST['amount_paid'];
    $balance = $total_amount - $amount_paid;
    $status = ($balance == 0) ? 'Paid' : (($amount_paid > 0) ? 'Partially Paid' : 'Unpaid');
    $payment_date = ($amount_paid > 0) ? $_POST['payment_date'] : NULL;

    $sql = "UPDATE fees SET total_amount=?, amount_paid=?, balance=?, status=?, payment_date=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("dddssi", $total_amount, $amount_paid, $balance, $status, $payment_date, $id);
    $stmt->execute();
}

// Delete record
if(isset($_GET['delete'])){
    $id = $_GET['delete'];
    $sql = "DELETE FROM fees WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

// Search
$search_query = "";
$search_condition = "";
if(isset($_GET['search']) && !empty($_GET['search'])){
    $search_query = $_GET['search'];
    $search_condition = " WHERE student_id LIKE '%" . $conn->real_escape_string($search_query) . "%'";
}

$result = $conn->query("SELECT * FROM fees" . $search_condition . " ORDER BY student_name, sem");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard - Fees</title>
<style>
    body { font-family: Poppins, sans-serif; background: #006989; margin:0; padding:0; }
    .container { max-width: 1500px; margin: auto; padding: 20px; color: white; }
    .card { background: #dbeafe; color: black; padding: 20px; border-radius: 8px; margin: 20px 0; }
    table { width: 100%; border-collapse: collapse; background: white; }
    th, td { padding: 12px; border: 1px solid #ddd; }
    th { background: #2196F3; color: white; }
    .btn { padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; color: white; }
    .btn-primary { background: #2196F3; }
    .btn-warning { background: #ff9800; }
    .btn-danger { background: #f44336; }
    .btn-success { background: #4CAF50; }
    input, select { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 5px; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; }
    .stat-card { background: #dbeafe; color: black; text-align: center; padding: 15px; border-radius: 8px; flex:1; }
    .stats { display: flex; gap: 20px; margin: 20px 0; }
</style>
</head>
<body>
<div class="container">
    <h1>💰 Admin Dashboard - Student Fees</h1>
    <p>Welcome, <?php echo $_SESSION['email']; ?></p>
    <!-- Stats -->
    <div class="stats">
        <div class="stat-card">
            <h3>Total Students</h3>
            <p style="font-size:22px;">
                <?php
                $total = $conn->query("SELECT COUNT(DISTINCT student_id) AS total FROM fees")->fetch_assoc()['total'] ?? 0;
                echo $total;
                ?>
            </p>
        </div>
        <div class="stat-card">
            <h3>Total Collected</h3>
            <p style="font-size:22px;">
                ₹<?php
                $collected = $conn->query("SELECT SUM(amount_paid) AS paid FROM fees")->fetch_assoc()['paid'] ?? 0;
                echo number_format($collected,2);
                ?>
            </p>
        </div>
        <div class="stat-card">
            <h3>Pending Balance</h3>
            <p style="font-size:22px;">
                ₹<?php
                $pending = $conn->query("SELECT SUM(balance) AS bal FROM fees")->fetch_assoc()['bal'] ?? 0;
                echo number_format($pending,2);
                ?>
            </p>
        </div>
    </div>

    <!-- Add / Update Form -->
    <div class="card">
        <h2>➕ Add / Update Student Fees</h2>
        <form method="POST">
            <div class="grid-3">
                <div>
                    <label>Student Name:</label>
                    <input type="text" name="student_name" required>
                </div>
                <div>
                    <label>Student ID:</label>
                    <input type="text" name="student_id" required>
                </div>
                <div>
                    <label>Semester:</label>
                    <select name="sem" required>
                        <option value="">Select</option>
                        <option value="I">Semester I</option>
                        <option value="II">Semester II</option>
                        <option value="III">Semester III</option>
                        <option value="IV">Semester IV</option>
                        <option value="V">Semester V</option>
                        <option value="VI">Semester VI</option>
                    </select>
                </div>
                <div>
                    <label>Total Amount:</label>
                    <input type="number" step="0.01" name="total_amount" required>
                </div>
                <div>
                    <label>Amount Paid:</label>
                    <input type="number" step="0.01" name="amount_paid" required>
                </div>
                <div>
                    <label>Payment Date:</label>
                    <input type="date" name="payment_date">
                </div>
            </div>
            <br>
            <div style="text-align:center;">
                <button type="submit" name="add_fees" class="btn btn-primary" style="padding:10px 25px;">💾 Save Fees Details</button>
            </div>
        </form>
    </div>

    <!-- Search -->
    <div class="card">
        <h2>📋 Fees Records</h2>
        <form method="GET" style="display:flex; gap:10px;">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="🔍 Search by Student ID...">
            <button class="btn btn-primary">Search</button>
            <?php if(!empty($search_query)): ?>
                <a href="admin_fees.php" class="btn btn-warning">Clear</a>
            <?php endif; ?>
        </form>
        <br>

        <table>
            <tr>
                <th>ID</th>
                <th>Student Name</th>
                <th>Student ID</th>
                <th>Semester</th>
                <th>Total Amount</th>
                <th>Amount Paid</th>
                <th>Balance</th>
                <th>Status</th>
                <th>Payment Date</th>
                <th>Actions</th>
            </tr>
            <?php
            if($result->num_rows > 0){
                while($row = $result->fetch_assoc()){
                    $color = ($row['status'] == 'Paid') ? '#4CAF50' : (($row['status']=='Partially Paid') ? '#ff9800' : '#f44336');
                    $payment_date = $row['payment_date'] ? date('d/m/Y', strtotime($row['payment_date'])) : '-';
                    
                    echo "<tr>
                        <td>{$row['id']}</td>
                        <td>{$row['student_name']}</td>
                        <td>{$row['student_id']}</td>
                        <td>{$row['sem']}</td>
                        <td>₹{$row['total_amount']}</td>
                        <td>₹{$row['amount_paid']}</td>
                        <td>₹{$row['balance']}</td>
                        <td style='color:$color;font-weight:bold;'>{$row['status']}</td>
                        <td>{$payment_date}</td>
                        <td>
                            <form method='POST' style='display:inline;'>
                                <input type='hidden' name='fees_id' value='{$row['id']}'>
                                <input type='number' step='0.01' name='total_amount' value='{$row['total_amount']}' style='width:80px;'>
                                <input type='number' step='0.01' name='amount_paid' value='{$row['amount_paid']}' style='width:80px;'>
                                <input type='date' name='payment_date' value='{$row['payment_date']}' style='width:120px;'>
                                <button type='submit' name='update_fees' class='btn btn-warning'>✏️ Update</button>
                            </form>
                            <a href='?delete={$row['id']}' class='btn btn-danger' onclick='return confirm(\"Delete this record?\")'>🗑️ Delete</a>
                        </td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='10' style='text-align:center;color:#666;'>No fee records found.</td></tr>";
            }
            ?>
        </table>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>