<?php
session_start();
if(!isset($_SESSION['email']) || $_SESSION['role'] != 'student'){
    header("Location: index.php");
    exit();
}
include 'connect.php';

// Get student ID from session (you might need to adjust this based on your session setup)
$student_email = $_SESSION['email'];

// Query to get student details based on email
$student_sql = "SELECT * FROM users WHERE email = ?";
$student_stmt = $conn->prepare($student_sql);
$student_stmt->bind_param("s", $student_email);
$student_stmt->execute();
$student_result = $student_stmt->get_result();

if($student_result->num_rows > 0){
    $student = $student_result->fetch_assoc();
    $student_id = $student['student_id']; // Adjust this based on your database structure
    $student_name = $student['name']; // Adjust this based on your database structure
} else {
    // If student not found, redirect with error
    header("Location: index.php?error=student_not_found");
    exit();
}

// Get fee records for this student
$fees_sql = "SELECT * FROM fees WHERE student_id = ? ORDER BY sem";
$fees_stmt = $conn->prepare($fees_sql);
$fees_stmt->bind_param("s", $student_id);
$fees_stmt->execute();
$fees_result = $fees_stmt->get_result();

// Calculate totals
$total_fees = 0;
$total_paid = 0;
$total_balance = 0;

while($row = $fees_result->fetch_assoc()){
    $total_fees += $row['total_amount'];
    $total_paid += $row['amount_paid'];
    $total_balance += $row['balance'];
}

// Reset pointer for result set
$fees_result->data_seek(0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Dashboard - Fee Details</title>
<link rel="stylesheet" href="st_style.css">
<style>
    * {margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif;}
    body { background: linear-gradient(135deg, #006989, #004d66); min-height: 100vh; padding: 20px; color: #333; }
    .container { max-width: 1200px; margin: 0 auto;}
    header { display: flex; justify-content: space-between; align-items: center; background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); padding: 15px 25px; border-radius: 10px; margin-bottom: 25px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); color: white;}
    .header-info h1 { font-size: 1.8rem; margin-bottom: 5px;}
    .header-info p { opacity: 0.9; font-size: 0.9rem; }
    .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; font-weight: 500; transition: all 0.3s ease; display: inline-block; text-align: center;}
    .btn-logout { background: rgba(255, 255, 255, 0.2); color: white; border: 1px solid rgba(255, 255, 255, 0.3);}
    .btn-logout:hover { background: rgba(255, 255, 255, 0.3);}
    .stats-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;}
    .stat-card { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); text-align: center; transition: transform 0.3s ease;}
    .stat-card:hover { transform: translateY(-5px); }
    .stat-card h3 { font-size: 1rem; color: #666; margin-bottom: 10px;}
    .stat-card .amount { font-size: 2rem; font-weight: 700;}
    .total-fees .amount { color: #2196F3; }
    .total-paid .amount { color: #4CAF50; }
    .total-balance .amount { color: #f44336;}
    .fees-table-container { background: white; border-radius: 10px; padding: 25px;box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);overflow: hidden;}
    .section-title { font-size: 1.5rem;  margin-bottom: 20px;  color: #006989;  padding-bottom: 10px; border-bottom: 2px solid #f0f0f0; }
    table { width: 100%; border-collapse: collapse;}
    th, td { padding: 15px; text-align: left; border-bottom: 1px solid #f0f0f0; }
    th { background-color: #f8f9fa; font-weight: 600; color: #444;}
    tr:hover { background-color: #f8f9fa; }
    .status { padding: 6px 12px; border-radius: 20px; font-weight: 500; font-size: 0.85rem; }
    .status-paid { background-color: #e8f5e9; color: #4CAF50; }
    .status-partial {background-color: #fff3e0;color: #ff9800;}
    .status-unpaid {background-color: #ffebee;color: #f44336;}
    .no-records { text-align: center; padding: 30px; color: #666; font-style: italic; }
    .payment-date { color: #666; font-size: 0.9rem; }
    @media (max-width: 768px) {
        .stats-container {grid-template-columns: 1fr;}
        table {display: block;overflow-x: auto;}
        header { flex-direction: column; text-align: center; gap: 15px;}
        .header-info h1 {font-size: 1.5rem;}
        th, td { padding: 10px 8px; font-size: 0.9rem; }
    }
</style>
</head>
<body>
<div class="container">
    <header>
        <div class="header-info">
            <h1>💰 My Fee Details</h1>
            <p>Welcome, <?php echo $student_name; ?> (ID: <?php echo $student_id; ?>)</p>
        </div>
    </header>
    
    <div class="stats-container">
        <div class="stat-card total-fees">
            <h3>Total Fees</h3>
            <div class="amount">₹<?php echo number_format($total_fees, 2); ?></div>
        </div>
        
        <div class="stat-card total-paid">
            <h3>Total Paid</h3>
            <div class="amount">₹<?php echo number_format($total_paid, 2); ?></div>
        </div>
        
        <div class="stat-card total-balance">
            <h3>Balance Due</h3>
            <div class="amount">₹<?php echo number_format($total_balance, 2); ?></div>
        </div>
    </div>
    
    <div class="fees-table-container">
        <h2 class="section-title">📋 Semester-wise Fee Details</h2>
        
        <?php if($fees_result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Semester</th>
                    <th>Total Amount</th>
                    <th>Amount Paid</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th>Payment Date</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $fees_result->fetch_assoc()): 
                    $status_class = '';
                    if($row['status'] == 'Paid') {
                        $status_class = 'status-paid';
                    } elseif($row['status'] == 'Partially Paid') {
                        $status_class = 'status-partial';
                    } else {
                        $status_class = 'status-unpaid';
                    }
                    
                    // Format payment date
                    $payment_date = '-';
                    if($row['payment_date'] && $row['payment_date'] != '0000-00-00') {
                        $payment_date = date('d/m/Y', strtotime($row['payment_date']));
                    }
                ?>
                <tr>
                    <td>Semester <?php echo $row['sem']; ?></td>
                    <td>₹<?php echo number_format($row['total_amount'], 2); ?></td>
                    <td>₹<?php echo number_format($row['amount_paid'], 2); ?></td>
                    <td>₹<?php echo number_format($row['balance'], 2); ?></td>
                    <td><span class="status <?php echo $status_class; ?>"><?php echo $row['status']; ?></span></td>
                    <td class="payment-date"><?php echo $payment_date; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="no-records">
            <p>No fee records found for your account.</p>
            <p>Please contact the administration if you believe this is an error.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Close connections
$student_stmt->close();
$fees_stmt->close();
$conn->close();
?>
</body>
</html>