<?php
session_start();
if(!isset($_SESSION['email']) || $_SESSION['role'] != 'admin'){
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

// Handle form operations
if(isset($_POST['add_attendance'])){
    $student_name = $_POST['student_name'];
    $student_id = $_POST['student_id'];
    
    // Process each subject
    foreach($subjects as $key => $value) {
        $total_classes = $_POST['total_classes_' . $key];
        $classes_attended = $_POST['classes_attended_' . $key];
        
        // Only insert if both values are provided and greater than 0
        if($total_classes > 0 && $classes_attended >= 0) {
            $attendance_percentage = ($classes_attended / $total_classes) * 100;
            
            // Check if record already exists
            $check_sql = "SELECT id FROM attendance WHERE student_id = ? AND subject = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("ss", $student_id, $key);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if($check_result->num_rows > 0) {
                // Update existing record
                $row = $check_result->fetch_assoc();
                $update_sql = "UPDATE attendance SET student_name = ?, total_classes = ?, classes_attended = ?, percentage = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("siiid", $student_name, $total_classes, $classes_attended, $attendance_percentage, $row['id']);
                $update_stmt->execute();
                $update_stmt->close();
            } else {
                // Insert new record
                $sql = "INSERT INTO attendance (student_name, student_id, subject, total_classes, classes_attended, percentage) 
                        VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssiid", $student_name, $student_id, $key, $total_classes, $classes_attended, $attendance_percentage);
                $stmt->execute();
                $stmt->close();
            }
            $check_stmt->close();
        }
    }
}

if(isset($_POST['update_attendance'])){
    $id = $_POST['attendance_id'];
    $total_classes = $_POST['total_classes'];
    $classes_attended = $_POST['classes_attended'];
    $attendance_percentage = ($classes_attended / $total_classes) * 100;

    $sql = "UPDATE attendance SET total_classes=?, classes_attended=?, percentage=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iidi", $total_classes, $classes_attended, $attendance_percentage, $id);
    $stmt->execute();
}

if(isset($_GET['delete'])){
    $id = $_GET['delete'];
    $sql = "DELETE FROM attendance WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

// Handle search functionality
$search_query = "";
$search_condition = "";

if(isset($_GET['search']) && !empty($_GET['search'])) {
    $search_query = $_GET['search'];
    $search_condition = " WHERE student_id LIKE '%" . $conn->real_escape_string($search_query) . "%'";
}

// Fetch all students (unique)
$students_result = $conn->query("SELECT DISTINCT student_name, student_id FROM attendance ORDER BY student_name");

// Fetch all attendance records with optional search
$result = $conn->query("SELECT * FROM attendance" . $search_condition . " ORDER BY student_name, subject");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Attendance</title>
    <link rel="stylesheet" href="ad_style.css">
</head>
<body>
    <div class="container">
        <h1 style="color:white;">📊 Admin Dashboard - Students Attendance</h1>
        <p style="color:white;">Welcome, <?php echo $_SESSION['email']; ?> </p>
        <!-- Statistics -->
        <div class="stats">
            <div class="stat-card">
                <h3>Total Students</h3>
                <p style="font-size: 24px; margin: 0;">
                    <?php 
                    $total_students = $conn->query("SELECT COUNT(DISTINCT student_id) as total FROM attendance");
                    echo $total_students->fetch_assoc()['total'] ?: '0';
                    ?>
                </p>
            </div>
            <div class="stat-card">
                <h3>Average Attendance</h3>
                <p style="font-size: 24px; margin: 0;">
                    <?php 
                    $avg = $conn->query("SELECT AVG(percentage) as avg_percentage FROM attendance");
                    echo $avg->fetch_assoc()['avg_percentage'] ? round($avg->fetch_assoc()['avg_percentage'], 1) . '%' : '0%';
                    ?>
                </p>
            </div>
            <div class="stat-card">
                <h3>Total Subjects</h3>
                <p style="font-size: 24px; margin: 0;">5</p>
            </div>
        </div>

        <!-- Add Attendance Form -->
        <div class="card">
            <h2>➕ Add/Update Student Attendance</h2>
            <form method="POST" action="" id="attendanceForm">
                
                <!-- Student Information Box -->
                <div class="student-info-box">
                    <h3>🎓 Student Information</h3>
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Student Name:</label>
                            <input type="text" name="student_name" id="student_name" required placeholder="Enter student name">
                        </div>
                        
                        <div class="form-group">
                            <label>Student ID:</label>
                            <input type="text" name="student_id" id="student_id" required placeholder="Enter student ID" onblur="checkExistingRecords()">
                        </div>
                    </div>
                    <div id="existingRecords" style="display: none;">
                        <div class="alert alert-warning">
                            <strong>⚠️ Existing records found for this student!</strong>
                            <div id="existingRecordsList"></div>
                            <small>Submitting will update existing records automatically.</small>
                        </div>
                    </div>
                </div>

                <!-- Subjects Container -->
                <div class="subject-container">
                    <?php foreach($subjects as $key => $value): ?>
                    <div class="subject-box">
                        <h4>📚 <?php echo $value; ?></h4>
                        <div class="input-row">
                            <div class="input-group">
                                <label>Total Classes:</label>
                                <input type="number" name="total_classes_<?php echo $key; ?>" 
                                       id="total_<?php echo $key; ?>" 
                                       min="0" value="84" 
                                       placeholder="Total" 
                                       oninput="calculatePercentage('<?php echo $key; ?>')">
                            </div>
                            
                            <div class="input-group">
                                <label>Attended:</label>
                                <input type="number" name="classes_attended_<?php echo $key; ?>" 
                                       id="attended_<?php echo $key; ?>" 
                                       min="0" value="0" 
                                       placeholder="Attended"
                                       oninput="calculatePercentage('<?php echo $key; ?>')">
                            </div>
                        </div>
                        <div class="input-group">
                            <label>Attendance Percentage:</label>
                            <div id="percentage_<?php echo $key; ?>" style="padding: 8px; background: #f8f9fa; border-radius: 4px; text-align: center; font-weight: bold;">
                                0%
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" name="add_attendance" class="btn btn-primary" style="padding: 12px 30px; font-size: 16px;">
                        💾 Save All Subjects Attendance
                    </button>
                </div>
            </form>
        </div>

        <!-- Subject-wise Attendance -->
        <div class="card">
            <h2>📚 Subject-wise Attendance Overview</h2>
            <div class="subject-tabs">
                <?php foreach($subjects as $key => $value): ?>
                <div class="subject-tab" onclick="filterSubject('<?php echo $key; ?>')">
                    <?php echo $value; ?>
                </div>
                <?php endforeach; ?>
                <div class="subject-tab active" onclick="filterSubject('all')">All Subjects</div>
            </div>

            <!-- Search Functionality -->
            <div class="search-container">
                <form method="GET" action="" style="display: flex; width: 100%; gap: 10px;">
                    <input type="text" name="search" placeholder="🔍 Search by Student ID..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <?php if(!empty($search_query)): ?>
                        <a href="?" class="btn btn-warning">Clear Search</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Attendance Records Table -->
            <h2>📋 Student Attendance Records</h2>
            <table id="attendanceTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student Name</th>
                        <th>Student ID</th>
                        <th>Subject</th>
                        <th>Total Classes</th>
                        <th>Classes Attended</th>
                        <th>Attendance %</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()): 
                            $percentage = $row['percentage'];
                            $status_class = '';
                            if($percentage >= 75) $status_class = 'percentage-high';
                            elseif($percentage >= 50) $status_class = 'percentage-medium';
                            else $status_class = 'percentage-low';
                    ?>
                    <tr data-subject="<?php echo $row['subject']; ?>">
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo $row['student_name']; ?></td>
                        <td><?php echo $row['student_id']; ?></td>
                        <td><?php echo $subjects[$row['subject']] ?? $row['subject']; ?></td>
                        <td><?php echo $row['total_classes']; ?></td>
                        <td><?php echo $row['classes_attended']; ?></td>
                        <td class="<?php echo $status_class; ?>">
                            <?php echo round($percentage, 1); ?>%
                        </td>
                        <td class="<?php echo $status_class; ?>">
                            <?php 
                            if($percentage >= 75) echo '✅ Good';
                            elseif($percentage >= 50) echo '⚠️ Average';
                            else echo '❌ Low';
                            ?>
                        </td>
                        <td>
                            <!-- Edit Form -->
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="attendance_id" value="<?php echo $row['id']; ?>">
                                <input type="number" name="total_classes" value="<?php echo $row['total_classes']; ?>" style="width: 80px; display: inline;">
                                <input type="number" name="classes_attended" value="<?php echo $row['classes_attended']; ?>" style="width: 80px; display: inline;">
                                <button type="submit" name="update_attendance" class="btn btn-warning">✏️ Update</button>
                            </form>
                            
                            <a href="?delete=<?php echo $row['id']; ?><?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" class="btn btn-danger" 
                               onclick="return confirm('Are you sure you want to delete this record?')">🗑️ Delete</a>
                        </td>
                    </tr>
                    <?php 
                        endwhile;
                    } else {
                        if(!empty($search_query)) {
                            echo '<tr><td colspan="9" class="no-results">No attendance records found for student ID: "' . htmlspecialchars($search_query) . '"</td></tr>';
                        } else {
                            echo '<tr><td colspan="9" class="no-results">No attendance records found.</td></tr>';
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>

       
    </div>

    <script>
        function calculatePercentage(subject) {
            const totalInput = document.getElementById('total_' + subject);
            const attendedInput = document.getElementById('attended_' + subject);
            const percentageDiv = document.getElementById('percentage_' + subject);
            
            const total = parseFloat(totalInput.value) || 0;
            const attended = parseFloat(attendedInput.value) || 0;
            
            if (total > 0 && attended >= 0) {
                const percentage = (attended / total * 100).toFixed(1);
                percentageDiv.textContent = percentage + '%';
                
                // Color coding
                if (percentage >= 75) {
                    percentageDiv.style.color = '#4CAF50';
                } else if (percentage >= 50) {
                    percentageDiv.style.color = '#ff9800';
                } else {
                    percentageDiv.style.color = '#f44336';
                }
            } else {
                percentageDiv.textContent = '0%';
                percentageDiv.style.color = '#666';
            }
        }

        function checkExistingRecords() {
            const studentId = document.getElementById('student_id').value;
            const studentName = document.getElementById('student_name').value;
            
            if(studentId.length > 0) {
                // Simulate AJAX call to check existing records
                setTimeout(() => {
                    // This would be replaced with actual AJAX call
                    const existingDiv = document.getElementById('existingRecords');
                    const existingList = document.getElementById('existingRecordsList');
                    
                    // For demo purposes, show message if student ID contains numbers
                    if(/\d/.test(studentId)) {
                        existingList.innerHTML = 'Records found for this student ID. Existing data will be updated.';
                        existingDiv.style.display = 'block';
                    } else {
                        existingDiv.style.display = 'none';
                    }
                }, 500);
            }
        }

        function filterSubject(subject) {
            const rows = document.querySelectorAll('#attendanceTable tbody tr');
            const tabs = document.querySelectorAll('.subject-tab');
            
            tabs.forEach(tab => tab.classList.remove('active'));
            event.target.classList.add('active');
            
            rows.forEach(row => {
                if (subject === 'all' || row.getAttribute('data-subject') === subject) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Initialize percentage calculations on page load
        document.addEventListener('DOMContentLoaded', function() {
            <?php foreach($subjects as $key => $value): ?>
                calculatePercentage('<?php echo $key; ?>');
            <?php endforeach; ?>
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>