<?php
include "../db_connection.php";

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit();
}

// Handle logout
if (isset($_POST['LogOutButton'])) {
    session_destroy();
    header("Location: ../login.php");
    exit();
}

// Validate event ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../index.php");
    exit();
}

$eventID = (int)$_GET['id'];
$user = $_SESSION['user'];

// Fetch event details securely with prepared statement
$stmt = $db->prepare("SELECT created_by, college_id, other_accounts_allowed_to_record 
                     FROM attendance_info 
                     WHERE id_attendance_info = ?");
$stmt->bind_param("i", $eventID);
$stmt->execute();
$result = $stmt->get_result();

// Check if event exists
if ($result->num_rows === 0) {
    header("Location: ../index.php");
    exit();
}

$event = $result->fetch_assoc();

// Check access conditions
$isCreator = ($user['account_id'] == $event['created_by']);
$othersAllowed = ($event['other_accounts_allowed_to_record'] == 1);
$sameCollege = ($user['college_id'] == $event['college_id']);

// Redirect if:
// 1. Not same college OR
// 2. Not creator AND others not allowed
if (!$sameCollege || (!$isCreator && !$othersAllowed)) {
    header("Location: ../index.php");
    exit();
}

if(isset($_POST['RecordAttendanceButton'])){
    // Verify user still has permission (in case session changed)
    if (!$sameCollege || (!$isCreator && !$othersAllowed)) {
        header("Location: ../index.php");
        exit();
    }

    $CurrentTime = date("H:i:s");
    $CurrentDate = date("Y-m-d"); 
    $StudentID = $db->real_escape_string($_POST['StudentID']);

    // Get attendance info securely
    $stmt = $db->prepare("SELECT time_in, time_out, time_out_cut_off 
                         FROM attendance_info 
                         WHERE id_attendance_info = ?");
    $stmt->bind_param("i", $eventID);
    $stmt->execute();
    $attendanceInfo = $stmt->get_result()->fetch_assoc();

    // Check if student exists in this attendance record
    $stmt = $db->prepare("SELECT stud_id FROM attendance_record 
                         WHERE id_attendance_info = ? AND stud_id = ?");
    $stmt->bind_param("is", $eventID, $StudentID);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        // Time In recording (during official time in period)
        if($CurrentTime >= $attendanceInfo['time_in'] && $CurrentTime < $attendanceInfo['time_out']){
            $stmt = $db->prepare("UPDATE attendance_record 
                                SET time_in = ?, 
                                    update_time = NOW() 
                                WHERE stud_id = ? 
                                AND id_attendance_info = ?");
            $stmt->bind_param("ssi", $CurrentTime, $StudentID, $eventID);
            $stmt->execute();
            
            echo "<div class='alert alert-success'>Time In recorded successfully</div>";
        }
        // Time Out recording (during official time out period)
        elseif($CurrentTime >= $attendanceInfo['time_out'] && $CurrentTime <= $attendanceInfo['time_out_cut_off']){
            $stmt = $db->prepare("UPDATE attendance_record 
                                SET time_out = ?, 
                                    update_time = NOW() 
                                WHERE stud_id = ? 
                                AND id_attendance_info = ?");
            $stmt->bind_param("ssi", $CurrentTime, $StudentID, $eventID);
            $stmt->execute();
            
            echo "<div style='margin-left:50px;' class='alert alert-success'>Time Out recorded successfully</div>";
        }
        else {
            echo "<div style='margin-left:50px;' class='alert alert-warning'>Cannot record at this time</div>";
        }
    }
    else {
        echo "<div style='margin-left:50px;' class='alert alert-danger'>Student not registered for this session</div>";
    }
}

// Format user name
$lastName = ucwords(strtolower($user['last_name']));
$firstName = ucwords(strtolower($user['first_name']));
$middleInitial = !empty($user['middle_name']) 
    ? ucfirst(strtolower(substr($user['middle_name'], 0, 1))) . "."
    : "";

$AccountName = $lastName . ", " . $firstName . ($middleInitial ? " " . $middleInitial : "");

$isDeletedQuery = "SELECT deleted_at FROM attendance_info WHERE id_attendance_info = $eventID";
$resultisDeletedQuery = $db->query($isDeletedQuery);
$checkisDeletedQuery = $resultisDeletedQuery->fetch_assoc();

if(!is_null($checkisDeletedQuery['deleted_at'])){
  header("Location: ../index.php");
}

$allowedToRecord = "SELECT other_accounts_allowed_to_record, created_by FROM attendance_info WHERE id_attendance_info = $eventID";
$resultallowedToRecord = $db->query($allowedToRecord);
$checkallowedToRecord = $resultallowedToRecord->fetch_assoc();


?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AMSBS </title>
    <link rel="stylesheet" href="../styles/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
  </head>
  <body>
    <header class="WebHeader">
      <div class="d-flex justify-content-center align-items-center h-100">
        <div class="w-100"></div>
        <div class="w-100 h-100 text-center"><h1 class="m-0 p-0">AMSBS • 
        <?php
          $collegeid = $user['college_id'];
          $query = "SELECT college_acronym FROM college_info WHERE college_id = $collegeid";
          $GetCollegeId = $db->query("$query");
          $ResultGetCollegeId = $GetCollegeId->fetch_assoc();

          echo $ResultGetCollegeId['college_acronym'];
        ?> 
        </h1></div>
        <div class="w-100">
          <div class="d-flex justify-content-end">
          <div><a href="Account.php"><?php echo $AccountName ?></a></div>
          <div class="mx-3">
              <button style="background: none; border: none; padding: 0; margin: 0;cursor: pointer; color: inherit;" data-bs-toggle="modal" data-bs-target="#logoutModal">
                <svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" fill="currentColor" style="margin-top: -4px;"
                  class="bi bi-box-arrow-right" viewBox="0 0 16 16">
                  <path fill-rule="evenodd"
                    d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0z" />
                  <path fill-rule="evenodd"
                    d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z" />
                </svg>
              </button>           
          </div>
          </div>
        </div>
      </div>
    </header>
    <div class="container-fluid">
      <div class="d-flex">
      <div class="sidebar">
      <div>
        <nav class="">
          <ul>
            <!-- Dashboard -->
            <li class="mb-2">
              <a href="../index.php" style="color: white;text-decoration: none;">
              <div class="d-flex align-items-center px-2 py-2">
                <div class="me-2" style="margin-left: 2px;">
                  <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="white"
                    class="bi bi-house-door-fill" viewBox="0 0 16 16">
                    <path d="M6.5 14.5v-3.505c0-.245.25-.495.5-.495h2c.25 0 .5.25.5.5v3.5a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.146-.354L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5v7a.5.5 0 0 0 .5.5" />
                  </svg>
                </div>
                <div class="label">Dashboard</div>
              </div>
              </a>
            </li>
        
            <!-- Attendance Records -->
            <li class="NavCurrentPage mb-2">
              <a href="ManageAttendanceRecords.php" style="color: white;text-decoration: none;">
              <div class="d-flex align-items-center px-2 py-2">
                <div class="me-2" style="margin-left: 2px;">
                  <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="white"
                    class="bi bi-clipboard2-data-fill" viewBox="0 0 16 16">
                    <path
                      d="M10 .5a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5.5.5 0 0 1-.5.5.5.5 0 0 0-.5.5V2a.5.5 0 0 0 .5.5h5A.5.5 0 0 0 11 2v-.5a.5.5 0 0 0-.5-.5.5.5 0 0 1-.5-.5" />
                    <path
                      d="M4.085 1H3.5A1.5 1.5 0 0 0 2 2.5v12A1.5 1.5 0 0 0 3.5 16h9a1.5 1.5 0 0 0 1.5-1.5v-12A1.5 1.5 0 0 0 12.5 1h-.585q.084.236.085.5V2a1.5 1.5 0 0 1-1.5 1.5h-5A1.5 1.5 0 0 1 4 2v-.5q.001-.264.085-.5M10 7a1 1 0 1 1 2 0v5a1 1 0 1 1-2 0zm-6 4a1 1 0 1 1 2 0v1a1 1 0 1 1-2 0zm4-3a1 1 0 0 1 1 1v3a1 1 0 1 1-2 0V9a1 1 0 0 1 1-1" />
                  </svg>
                </div>
                <div class="label">Attendance Records</div>
              </div>
              </a>
            </li>

            <!-- Student Profiles -->
            <li class="mb-2">
                <a href="StudentProfiles.php" style="color: white; text-decoration: none;">
                  <div class="d-flex align-items-center px-2 py-2">
                    <div class="me-2" style="margin-left: 2px;">
                      <!-- Student Profiles Icon -->
                      <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="white" class="bi bi-people-fill" viewBox="0 0 16 16">
                        <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5"/>
                      </svg>
                    </div>
                    <div class="label">Student Profiles</div>
                  </div>
                </a>
              </li>
        
            <!-- Account -->
            <li class="mb-2">
              <a href="Account.php" style="color: white;text-decoration: none;">
              <div class="d-flex align-items-center px-2 py-2">
                <div class="me-2" style="margin-left: 2px;">
                  <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="white"
                    class="bi bi-person-circle" viewBox="0 0 16 16">
                    <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0" />
                    <path fill-rule="evenodd"
                      d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1" />
                  </svg>
                </div>
                <div class="label">Account</div>
              </div>
              </a>
            </li>
          </ul>
        </nav>
        
      </div>
    </div>
    <div style="width:50px;"></div>
      <div class="row w-100">
        <div class="col-3 pt-2 ps-2 pe-1">
            <div class="w-100">
<form method="POST" class="text-center BoxContainers">
    <p class="BoxContainersHeader fw-bold p-2">RECORD ATTENDANCE</p>
    <p>Scan Barcode or<br>Enter ID Number</p>
    <input class="text-center w-75" type="text" name="StudentID" placeholder="00-00000" required><br><br>
    <button type="submit" name="RecordAttendanceButton" class="btn btn-dark" style="border-radius: 0;">Record</button>
    <br><br>
</form>
            </div>
        </div>
        <div class="col-9 pt-2 ps-1 pe-2">
            <div class="w-100">
                <div class="row">
                    <div class="col-12 col-md-6">
                      <?php
// Fetch attendance details
$stmt = $db->prepare("SELECT 
                      name, 
                      TIME_FORMAT(time_in, '%h:%i %p') as formatted_time_in,
                      TIME_FORMAT(time_in_cut_off, '%h:%i %p') as formatted_time_in_cutoff,
                      TIME_FORMAT(time_out, '%h:%i %p') as formatted_time_out,
                      TIME_FORMAT(time_out_cut_off, '%h:%i %p') as formatted_time_out_cutoff,
                      section
                      FROM attendance_info 
                      WHERE id_attendance_info = ?");
$stmt->bind_param("i", $eventID);
$stmt->execute();
$attendanceInfo = $stmt->get_result()->fetch_assoc();

// Count total attendees (time_in not null)
$stmt = $db->prepare("SELECT COUNT(*) as present_count 
                     FROM attendance_record 
                     WHERE id_attendance_info = ? AND time_in IS NOT NULL");
$stmt->bind_param("i", $eventID);
$stmt->execute();
$presentCount = $stmt->get_result()->fetch_assoc()['present_count'];

// Count total registered students
$stmt = $db->prepare("SELECT COUNT(*) as total_count 
                     FROM attendance_record 
                     WHERE id_attendance_info = ?");
$stmt->bind_param("i", $eventID);
$stmt->execute();
$totalCount = $stmt->get_result()->fetch_assoc()['total_count'];
?>

                        <p class="mb-2">
                            <span class="fw-bold"><?= htmlspecialchars($attendanceInfo['name']) ?></span>
                            <br>
                            Time In: <?= $attendanceInfo['formatted_time_in'] ?> - <?= $attendanceInfo['formatted_time_in_cutoff'] ?>
                            <br>
                            Time Out: <?= $attendanceInfo['formatted_time_out'] ?> - <?= $attendanceInfo['formatted_time_out_cutoff'] ?>
                        </p>
                    </div>
                    <div class="col-12 col-md-6">
                        <p class="p-0 m-0"><?= htmlspecialchars(str_replace(',', ', ', $attendanceInfo['section'])) ?></p>
                        <p class="p-0 m-0">Attendees: <?= $presentCount ?> / <?= $totalCount ?></p></p>
                    </div>
                </div>
                <table class=" mb-2 table table-bordered align-middle table-striped">
                    <thead>
                        <tr>
                            <th class="bg-dark text-white p-1 fw-normal text-center">#</th>
                            <th class="bg-dark text-white p-1 fw-normal">YR. & SEC.</th>
                            <th class="bg-dark text-white p-1 fw-normal">NAME</th>
                            <th class="bg-dark text-white p-1 fw-normal">SEX</th>
                            <th class="bg-dark text-white p-1 fw-normal">TIME IN</th>
                            <th class="bg-dark text-white p-1 fw-normal">TIME OUT</th>
                            <th class="bg-dark text-white p-1 fw-normal">REMARKS</th>
                        </tr>
                    </thead>
                    <tbody>
<?php
// Fetch student attendance records with security check
$GetStudentAttendanceRecordQuery = "
SELECT ar.stud_id, sp.fname, sp.mname, sp.lname, ar.time_in, ar.time_out, sp.sex, sp.section
FROM attendance_record ar
JOIN student_profiles sp ON ar.stud_id = sp.stud_id
WHERE (ar.time_in IS NOT NULL OR ar.time_out IS NOT NULL)
AND ar.id_attendance_info = ?
ORDER BY ar.update_time DESC
";

$stmt = $db->prepare($GetStudentAttendanceRecordQuery);
$stmt->bind_param("i", $eventID);
$stmt->execute();
$resultGetStudentAttendanceRecordQuery = $stmt->get_result();

// First, ensure we have the required attendance info
$attendanceInfoQuery = "SELECT time_in, time_in_cut_off, time_out, time_out_cut_off FROM attendance_info WHERE id_attendance_info = ?";
$stmt = $db->prepare($attendanceInfoQuery);
$stmt->bind_param("i", $eventID);
$stmt->execute();
$attendanceInfo = $stmt->get_result()->fetch_assoc();

// Set default values if fields are missing
$timeInCutoff = isset($attendanceInfo['time_in_cut_off']) ? strtotime($attendanceInfo['time_in_cut_off']) : strtotime('+30 minutes'); // Default 30 min cutoff
$timeOut = isset($attendanceInfo['time_out']) ? strtotime($attendanceInfo['time_out']) : strtotime('+1 hour'); // Default 1 hour session

$counter = 1;
while($row = $resultGetStudentAttendanceRecordQuery->fetch_assoc()) {
    // Format name safely
    $middleInitial = !empty($row['mname']) ? substr($row['mname'], 0, 1) . '.' : '';
    $fullName = htmlspecialchars($row['lname'] . ', ' . $row['fname'] . ($middleInitial ? ' ' . $middleInitial : ''));
    
    // Format times safely
    $timeIn = '';
    if (!empty($row['time_in'])) {
        try {
            $timeIn = date("g:i A", strtotime($row['time_in']));
        } catch (Exception $e) {
            error_log("Time format error for time_in: " . $e->getMessage());
        }
    }
    
    $timeOutDisplay = '';
    if (!empty($row['time_out'])) {
        try {
            $timeOutDisplay = date("g:i A", strtotime($row['time_out']));
        } catch (Exception $e) {
            error_log("Time format error for time_out: " . $e->getMessage());
        }
    }
    
// Determine remarks
$remarks = "PENDING";

if (!empty($row['time_in'])) {
    $studentTimeIn = strtotime($row['time_in']);
    
    if ($studentTimeIn <= $timeInCutoff) {
        $remarks = "PRESENT";
    } elseif ($studentTimeIn <= $timeOut) {
        $remarks = "LATE";
    }
} elseif (!empty($row['time_out'])) {
    $remarks = "NO TIME IN";
}
    ?>
    <tr>
        <td class="p-1 text-center"><?= $counter++ ?></td>
        <td class="p-1"><?= htmlspecialchars($row['section']) ?></td>
        <td class="p-1"><?= $fullName ?></td>
        <td class="p-1"><?= htmlspecialchars($row['sex']) ?></td>
        <td class="p-1"><?= $timeIn ?></td>
        <td class="p-1"><?= $timeOutDisplay ?></td>
        <td class="p-1"><?= $remarks ?></td>
    </tr>
    <?php
}
?>
                    </tbody>
                </table>

            </div>            
        </div>
      </div>
    </div>
    </div>

<!-- Logout Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content bg-dark text-white">
      <div class="modal-header">
        <h5 class="modal-title" id="logoutModalLabel">Logout Confirmation</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to log out?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
        <!-- Logout Form -->
        <form method="POST">
          <button type="submit" class="btn btn-danger" name="LogOutButton">Logout</button>
        </form>
      </div>
    </div>
  </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
  </body>
</html>