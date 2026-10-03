<?php
session_start();
if(!isset($_SESSION['email']) || $_SESSION['role'] != 'student'){
    header("Location: index.php");
    exit();
}
include 'connect.php';

// Define subjects with keys that work in form names
$subjects = [
    'DBMS' => 'Database Management System',
    'Computer_Network' => 'Computer Network', 
    'R_Data_Mining' => 'R & Data Mining',
    'Cybersecurity' => 'Cybersecurity',
    'NME' => 'Non-Major Elective'
];

// Get current student's data
$student_email = $_SESSION['email'];
$student_id = ''; // You might need to adjust this based on your student identification

// If you have student ID in session or can get it from email
// This depends on your user management system
// For now, I'll assume we can get student ID from email or session

// Fetch attendance records for the current student
// You need to determine how to identify the current student
// This could be by email, student_id in session, etc.

// Example: If student_id is stored in session
if(isset($_SESSION['student_id'])) {
    $student_id = $_SESSION['student_id'];
} else {
    // Alternative: Get student_id from students table using email
    $student_sql = "SELECT student_id FROM users WHERE email = ?";
    $student_stmt = $conn->prepare($student_sql);
    $student_stmt->bind_param("s", $student_email);
    $student_stmt->execute();
    $student_result = $student_stmt->get_result();
    
    if($student_result->num_rows > 0) {
        $student_data = $student_result->fetch_assoc();
        $student_id = $student_data['student_id'];
    }
    $student_stmt->close();
}

// Fetch attendance records for this student
$attendance_result = null;
$student_name = '';
$overall_stats = [];

if($student_id) {
    $sql = "SELECT * FROM attendance WHERE student_id = ? ORDER BY subject";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $attendance_result = $stmt->get_result();
    
    // Get student name from first record
    if($attendance_result->num_rows > 0) {
        $first_row = $attendance_result->fetch_assoc();
        $student_name = $first_row['student_name'];
        // Reset pointer
        $attendance_result->data_seek(0);
    }
    
    // Calculate overall statistics
    $stats_sql = "SELECT 
                    AVG(percentage) as avg_attendance,
                    MIN(percentage) as min_attendance,
                    MAX(percentage) as max_attendance,
                    COUNT(*) as total_subjects
                  FROM attendance 
                  WHERE student_id = ?";
    $stats_stmt = $conn->prepare($stats_sql);
    $stats_stmt->bind_param("s", $student_id);
    $stats_stmt->execute();
    $stats_result = $stats_stmt->get_result();
    $overall_stats = $stats_result->fetch_assoc();
    $stats_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Attendance</title>
     <link rel="stylesheet" href="st_att.css">
</head>
<body>
    <div class="container">
        <h1 style="color:white;">🎓 Student Dashboard - My Attendance</h1>
        <p style="color:white;">Welcome, <?php echo $_SESSION['email']; ?></p>
     <!-- Student Information -->
        <div class="student-info-box">
            <h3>👤 Student Information</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <strong>Name:</strong> <?php echo htmlspecialchars($student_name ?: 'Not available'); ?>
                </div>
                <div>
                    <strong>Student ID:</strong> <?php echo htmlspecialchars($student_id ?: 'Not available'); ?>
                </div>
                <div>
                    <strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['email']); ?>
                </div>
                <div>
                    <strong>Role:</strong> Student
                </div>
            </div>
        </div>

        <!-- Overall Statistics -->
        <?php if($overall_stats && $overall_stats['total_subjects'] > 0): ?>
        <div class="stats">
            <div class="stat-card">
                <h3>Overall Attendance</h3>
                <p style="font-size: 24px; margin: 0; color: #2196F3;">
                    <?php echo round($overall_stats['avg_attendance'], 1); ?>%
                </p>
            </div>
            <div class="stat-card">
                <h3>Highest Subject</h3>
                <p style="font-size: 24px; margin: 0; color: #4CAF50;">
                    <?php echo round($overall_stats['max_attendance'], 1); ?>%
                </p>
            </div>
            <div class="stat-card">
                <h3>Lowest Subject</h3>
                <p style="font-size: 24px; margin: 0; color: #f44336;">
                    <?php echo round($overall_stats['min_attendance'], 1); ?>%
                </p>
            </div>
            <div class="stat-card">
                <h3>Total Subjects</h3>
                <p style="font-size: 24px; margin: 0; color: #ff9800;">
                    <?php echo $overall_stats['total_subjects']; ?>
                </p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Subject-wise Attendance Cards -->
        <div class="card">
            <h2>📚 My Subject-wise Attendance</h2>
            
            <?php if($attendance_result && $attendance_result->num_rows > 0): ?>
                <div class="subject-container">
                    <?php while($row = $attendance_result->fetch_assoc()): 
                        $percentage = $row['percentage'];
                        $status_class = '';
                        $status_text = '';
                        $progress_color = '';
                        
                        if($percentage >= 75) {
                            $status_class = 'percentage-high';
                            $status_text = '✅ Good';
                            $progress_color = '#4CAF50';
                        } elseif($percentage >= 50) {
                            $status_class = 'percentage-medium';
                            $status_text = '⚠️ Average';
                            $progress_color = '#ff9800';
                        } else {
                            $status_class = 'percentage-low';
                            $status_text = '❌ Low';
                            $progress_color = '#f44336';
                        }
                    ?>
                    <div class="subject-box">
                        <h4>📚 <?php echo $subjects[$row['subject']] ?? $row['subject']; ?></h4>
                        
                        <div style="margin: 15px 0;">
                            <strong>Classes Attended:</strong> 
                            <?php echo $row['classes_attended']; ?> / <?php echo $row['total_classes']; ?>
                        </div>
                        
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo min($percentage, 100); ?>%; background: <?php echo $progress_color; ?>;"></div>
                        </div>
                        
                        <div style="text-align: center;">
                            <span class="<?php echo $status_class; ?>" style="font-size: 18px;">
                                <?php echo round($percentage, 1); ?>%
                            </span>
                            <div style="margin-top: 5px;">
                                <?php echo $status_text; ?>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <h3>📊 No Attendance Records Found</h3>
                    <p>Your attendance records are not available yet.</p>
                    <p>Please contact your administrator if you believe this is an error.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Detailed Table View -->
        <?php if($attendance_result && $attendance_result->num_rows > 0): ?>
        <div class="card">
            <h2>📋 Detailed Attendance Summary</h2>
            <table>
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Total Classes</th>
                        <th>Classes Attended</th>
                        <th>Attendance %</th>
                        <th>Status</th>
                        <th>Required for 75%</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Reset pointer and loop again
                    $attendance_result->data_seek(0);
                    while($row = $attendance_result->fetch_assoc()): 
                        $percentage = $row['percentage'];
                        $status_class = '';
                        $status_text = '';
                        
                        if($percentage >= 75) {
                            $status_class = 'percentage-high';
                            $status_text = '✅ Good';
                        } elseif($percentage >= 50) {
                            $status_class = 'percentage-medium';
                            $status_text = '⚠️ Average';
                        } else {
                            $status_class = 'percentage-low';
                            $status_text = '❌ Low';
                        }
                        
                        // Calculate required classes for 75% attendance
                        $required_for_75 = ceil($row['total_classes'] * 0.75);
                        $additional_needed = max(0, $required_for_75 - $row['classes_attended']);
                    ?>
                    <tr>
                        <td><strong><?php echo $subjects[$row['subject']] ?? $row['subject']; ?></strong></td>
                        <td><?php echo $row['total_classes']; ?></td>
                        <td><?php echo $row['classes_attended']; ?></td>
                        <td class="<?php echo $status_class; ?>">
                            <?php echo round($percentage, 1); ?>%
                        </td>
                        <td class="<?php echo $status_class; ?>">
                            <?php echo $status_text; ?>
                        </td>
                        <td>
                            <?php if($percentage < 75): ?>
                                Need <?php echo $additional_needed; ?> more classes
                            <?php else: ?>
                                ✅ Target achieved
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Attendance Warnings -->
        <div class="card">
            <h2>⚠️ Attendance Alerts</h2>
            <?php 
            $attendance_result->data_seek(0);
            $low_attendance_count = 0;
            
            while($row = $attendance_result->fetch_assoc()): 
                if($row['percentage'] < 75): 
                    $low_attendance_count++;
            ?>
            <div class="warning-box">
                <strong>⚠️ Low Attendance in <?php echo $subjects[$row['subject']] ?? $row['subject']; ?></strong>
                <p>Your attendance is <?php echo round($row['percentage'], 1); ?>%. 
                You need to attend <?php echo ceil($row['total_classes'] * 0.75) - $row['classes_attended']; ?> more classes to reach 75%.</p>
            </div>
            <?php 
                endif;
            endwhile; 
            
            if($low_attendance_count === 0): 
            ?>
            <div style="text-align: center; padding: 20px; background: #d4edda; border-radius: 5px;">
                <strong>✅ All Good!</strong> Your attendance is above 75% in all subjects.
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Simple animation for progress bars
        document.addEventListener('DOMContentLoaded', function() {
            const progressBars = document.querySelectorAll('.progress-fill');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0';
                setTimeout(() => {
                    bar.style.width = width;
                }, 100);
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>