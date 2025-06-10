<?php
include "db_connection.php";

if(!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

if(isset($_POST['LogOutButton'])){
    session_destroy();
    header("Location: login.php");
}

$user = $_SESSION['user'];
$lastName = ucwords(strtolower($user['last_name']));
$firstName = ucwords(strtolower($user['first_name']));
$middleInitial = ucfirst(strtolower(substr($user['middle_name'], 0, 1))) . ".";

$AccountName = $lastName . ", " . $firstName . " " . $middleInitial;

if (isset($_POST['CreateAttendanceRecordButton'])) {
    $Name = $_POST['Name'];
    $Date = $_POST['Date'];
    $TimeIn = $_POST['TimeIn'];
    $TimeInCutOff = $_POST['TimeInCutOff'];
    $TimeOut = $_POST['TimeOut'];
    $TimeOutCutOff = $_POST['TimeOutCutOff'];
    $other_accounts_allowed_to_record = isset($_POST['other_accounts_allowed_to_record']) ? 1 : 0;
    $allow_attendance_web_recording = isset($_POST['allow_attendance_web_recording']) ? 1 : 0;
    $allow_access_to_CSC = isset($_POST['allow_access_to_CSC']) ? 1 : 0;
    $CollegeID = $_POST['CollegeID'];
    $CreatedBy = $user['account_id'];

    // Validate time inputs
    if ($TimeInCutOff < $TimeIn) {
        echo "<script>alert('Time In Cut Off must not be earlier than Time In.');</script>";
    } elseif ($TimeOut < $TimeIn) {
        echo "<script>alert('Time Out must not be earlier than Time In.');</script>";
    } elseif ($TimeOutCutOff < $TimeOut) {
        echo "<script>alert('Time Out Cut Off must not be earlier than Time Out.');</script>";
    } elseif (!isset($_POST['section']) || empty($_POST['section'])) {
        echo "<script>alert('Please select at least one section.');</script>";
    } else {
        $SelectedSections = implode(",", $_POST['section']);

        $CreateAttendanceRecordQuery = "INSERT INTO attendance_info 
            (name, section, date, time_in, time_in_cut_off, time_out, time_out_cut_off, 
            other_accounts_allowed_to_record, allow_attendance_web_recording, allow_access_to_CSC, college_id, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($CreateAttendanceRecordQuery);
        $stmt->bind_param("sssssssiiisi", 
            $Name, $SelectedSections, $Date, $TimeIn, $TimeInCutOff, $TimeOut, 
            $TimeOutCutOff, $other_accounts_allowed_to_record, $allow_attendance_web_recording, 
            $allow_access_to_CSC, $CollegeID, $CreatedBy);
        $stmt->execute();
        $attendanceInfoID = $stmt->insert_id;
        $stmt->close();

        // Prepare section list for student query
        $sectionArray = $_POST['section'];
        $placeholders = implode(',', array_fill(0, count($sectionArray), '?'));
        $types = str_repeat('s', count($sectionArray));
        
        $FetchStudentIDsQuery = "SELECT stud_id FROM student_profiles WHERE college_id = ? AND section IN ($placeholders)";
        $stmt = $db->prepare($FetchStudentIDsQuery);
        
        // Bind parameters
        $params = array_merge([$CollegeID], $sectionArray);
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $InsertAttendanceRecord = "INSERT INTO attendance_record (id_attendance_info, stud_id) VALUES (?, ?)";
        $insertStmt = $db->prepare($InsertAttendanceRecord);
        
        while ($row = $result->fetch_assoc()) {
            $studID = $row['stud_id'];
            $insertStmt->bind_param("is", $attendanceInfoID, $studID);
            $insertStmt->execute();
        }
        
        $insertStmt->close();
        $stmt->close();

        echo "<script>alert('Attendance record created successfully!');</script>";
        echo "<script>window.location.href = window.location.href;</script>";
    }
}

if(isset($_POST['UpdateAttendanceRecordButton'])) {
    $AttendanceID = $_POST['AttendanceID'];
    $Name = $_POST['Name'];
    $Date = $_POST['Date'];
    $TimeIn = $_POST['TimeIn'];
    $TimeInCutOff = $_POST['TimeInCutOff'];
    $TimeOut = $_POST['TimeOut'];
    $TimeOutCutOff = $_POST['TimeOutCutOff'];
    $other_accounts_allowed_to_record = isset($_POST['other_accounts_allowed_to_record']) ? 1 : 0;
    $allow_attendance_web_recording = isset($_POST['allow_attendance_web_recording']) ? 1 : 0;
    $allow_access_to_CSC = isset($_POST['allow_access_to_CSC']) ? 1 : 0;
    $CollegeID = $_POST['CollegeID'];
    $CurrentUser = $user['account_id'];
    
    // Fetch the real creator of the attendance record
    $creatorQuery = "SELECT created_by FROM attendance_info WHERE id_attendance_info = ?";
    $stmtCreator = $db->prepare($creatorQuery);
    $stmtCreator->bind_param("i", $AttendanceID);
    $stmtCreator->execute();
    $resultCreator = $stmtCreator->get_result();
    
    if ($resultCreator->num_rows === 0) {
        echo "<script>alert('Attendance record not found.');</script>";
        echo "<script>window.location.href = window.location.href;</script>";
    }
    
    $rowCreator = $resultCreator->fetch_assoc();
    $CreatedByID = $rowCreator['created_by'];
    $CurrentUser = $user['account_id'];
    
    if ($CreatedByID != $CurrentUser) {
        echo "<script>alert('You are not authorized to update this attendance record.');</script>";
        echo "<script>window.location.href = window.location.href;</script>";
    }
    $stmtCreator->close();
    
    if ($TimeInCutOff < $TimeIn) {
        echo "<script>alert('Time In Cut Off must not be earlier than Time In.');</script>";
    } elseif ($TimeOut < $TimeIn) {
        echo "<script>alert('Time Out must not be earlier than Time In.');</script>";
    } elseif ($TimeOutCutOff < $TimeOut) {
        echo "<script>alert('Time Out Cut Off must not be earlier than Time Out.');</script>";
    } elseif (!isset($_POST['section']) || empty($_POST['section'])) {
        echo "<script>alert('Please select at least one section.');</script>";
    } else {
        $SelectedSections = implode(",", $_POST['section']);

        $UpdateAttendanceRecordQuery = "UPDATE attendance_info SET 
            name = ?, 
            section = ?, 
            date = ?, 
            time_in = ?, 
            time_in_cut_off = ?, 
            time_out = ?, 
            time_out_cut_off = ?, 
            other_accounts_allowed_to_record = ?, 
            allow_attendance_web_recording = ?, 
            allow_access_to_CSC = ?
            WHERE id_attendance_info = ?";
        
        $stmt = $db->prepare($UpdateAttendanceRecordQuery);
        $stmt->bind_param("sssssssiiii", 
            $Name, $SelectedSections, $Date, $TimeIn, $TimeInCutOff, $TimeOut, 
            $TimeOutCutOff, $other_accounts_allowed_to_record, $allow_attendance_web_recording, 
            $allow_access_to_CSC, $AttendanceID);

        if ($stmt->execute()) {
            // STEP 1: Get existing student-section pairs for this attendance
            $existingStudentSections = [];
            $getExistingQuery = "
                SELECT ar.stud_id, sp.section 
                FROM attendance_record ar
                JOIN student_profiles sp ON ar.stud_id = sp.stud_id
                WHERE ar.id_attendance_info = ?";
            $stmtExist = $db->prepare($getExistingQuery);
            $stmtExist->bind_param("i", $AttendanceID);
            $stmtExist->execute();
            $resultExist = $stmtExist->get_result();
            while ($row = $resultExist->fetch_assoc()) {
                $existingStudentSections[$row['stud_id']] = $row['section'];
            }
            $stmtExist->close();

            // STEP 2: Build list of current section students
            $sectionArray = $_POST['section'];
            $placeholders = implode(',', array_fill(0, count($sectionArray), '?'));
            $fetchStudentsQuery = "SELECT stud_id, section FROM student_profiles WHERE college_id = ? AND section IN ($placeholders)";
            $stmtFetch = $db->prepare($fetchStudentsQuery);
            $params = array_merge([$CollegeID], $sectionArray);
            $types = str_repeat('s', count($params));
            $stmtFetch->bind_param($types, ...$params);
            $stmtFetch->execute();
            $result = $stmtFetch->get_result();

            $newStudentSections = [];
            while ($row = $result->fetch_assoc()) {
                $newStudentSections[$row['stud_id']] = $row['section'];
            }
            $stmtFetch->close();

            // STEP 3: Insert new students not already in the attendance_record
            $insertStmt = $db->prepare("INSERT INTO attendance_record (id_attendance_info, stud_id) VALUES (?, ?)");
            foreach ($newStudentSections as $studID => $section) {
                if (!array_key_exists($studID, $existingStudentSections)) {
                    $insertStmt->bind_param("is", $AttendanceID, $studID);
                    $insertStmt->execute();
                }
            }
            $insertStmt->close();

            // STEP 4: Delete students from attendance_record if their section is no longer selected
            $deleteStmt = $db->prepare("DELETE FROM attendance_record WHERE id_attendance_info = ? AND stud_id = ?");
            foreach ($existingStudentSections as $studID => $section) {
                if (!in_array($section, $sectionArray)) {
                    $deleteStmt->bind_param("is", $AttendanceID, $studID);
                    $deleteStmt->execute();
                }
            }
            $deleteStmt->close();

            echo "<script>alert('Attendance record updated successfully!');</script>";
            echo "<script>window.location.href = window.location.href;</script>";
        } else {
            echo "<script>alert('Error updating attendance record.');</script>";
        }

        $stmt->close();
    }
}

?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AMSBS</title>
    <link rel="stylesheet" href="styles/styles.css">
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
          <div><a href="pages/Account.php"><?php echo $AccountName ?></a></div>
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
            <li class="NavCurrentPage mb-2">
              <a href="index.php" style="color: white;text-decoration: none;">
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
            <li class="mb-2">
              <a href="pages/ManageAttendanceRecords.php" style="color: white;text-decoration: none;">
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
              <a href="pages/StudentProfiles.php" style="color: white; text-decoration: none;">
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
            <li>
              <a href="pages/Account.php" style="color: white;text-decoration: none;">
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
        <div class="col-6 p-0">
          <div class="border m-2 me-1">
            <div class="d-block BoxContainers">
              <div class="BoxContainersHeader">
                <div class="d-flex justify-content-between align-items-center">
                  <div><p class="m-0 ms-1">Attendance Records Today</p></div>
                  <div>
                    <button class="btn btn-light btn-sm" style="border-radius:0; height:30px;" type="button" data-bs-toggle="modal" data-bs-target="#attendanceModal">
                      <div class="d-flex align-items-center">
                      <div>
                      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clipboard2-plus" viewBox="0 0 16 16">
                        <path d="M9.5 0a.5.5 0 0 1 .5.5.5.5 0 0 0 .5.5.5.5 0 0 1 .5.5V2a.5.5 0 0 1-.5.5h-5A.5.5 0 0 1 5 2v-.5a.5.5 0 0 1 .5-.5.5.5 0 0 0 .5-.5.5.5 0 0 1 .5-.5z"/>
                        <path d="M3 2.5a.5.5 0 0 1 .5-.5H4a.5.5 0 0 0 0-1h-.5A1.5 1.5 0 0 0 2 2.5v12A1.5 1.5 0 0 0 3.5 16h9a1.5 1.5 0 0 0 1.5-1.5v-12A1.5 1.5 0 0 0 12.5 1H12a.5.5 0 0 0 0 1h.5a.5.5 0 0 1 .5.5v12a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5z"/>
                        <path d="M8.5 6.5a.5.5 0 0 0-1 0V8H6a.5.5 0 0 0 0 1h1.5v1.5a.5.5 0 0 0 1 0V9H10a.5.5 0 0 0 0-1H8.5z"/>
                      </svg>
                      </div>
                      <div>
                      Create
                    </div>
                    </div>
                    </button>
                  </div>
                </div>
              </div>
<div class="ms-2 pt-2">
    <?php
    // Get today's date
    $today = date('Y-m-d');
    $collegeID = $_SESSION['user']['college_id'];
    
    // Query to get today's attendance records for the user's college
    $attendanceQuery = "SELECT * FROM attendance_info 
                       WHERE college_id = ? AND date = ? AND deleted_at IS NULL
                       ORDER BY created_at DESC";
    
    $stmt = $db->prepare($attendanceQuery);
    $stmt->bind_param("is", $collegeID, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Display each attendance record
        while ($row = $result->fetch_assoc()) {
            $timeIn = date("g:i A", strtotime($row['time_in']));
            $timeOut = date("g:i A", strtotime($row['time_out']));
            $formattedSections = str_replace(',', ', ', $row['section']);
            ?>
            <div class="bg-white d-flex align-items-center justify-content-between me-2 mb-2" style="border:1px solid grey;">
                <div>
                    <p class="m-0 ms-2">
                        <strong><?php echo htmlspecialchars($row['name']); ?></strong><br>
                        <?php echo $timeIn; ?> - <?php echo $timeOut; ?><br>
                        <?php echo htmlspecialchars($formattedSections); ?>
                    </p>
                </div>
                <div class="me-2" style="min-width:160px;text-align:right;">

<a href="pages/ActiveAttendanceRecord.php?id=<?php echo $row['id_attendance_info']; ?>" 
   class="btn btn-success btn-sm <?php echo ($_SESSION['user']['account_id'] != $row['created_by'] && $row['other_accounts_allowed_to_record'] != 1) ? 'd-none' : ''; ?> <?php echo ($row['allow_attendance_web_recording'] == 0) ? 'disabled' : ''; ?>" 
   style="border-radius: 0;"
   <?php echo ($row['allow_attendance_web_recording'] == 0) ? 'onclick="return false;"' : ''; ?>>
   Record
</a>
<a href="pages/AttendanceRecord.php?id=<?php echo $row['id_attendance_info']; ?>" class="btn btn-primary btn-sm" style="border-radius: 0;">View</a>

<button class="btn btn-warning btn-sm edit-btn <?php echo ($_SESSION['user']['account_id'] != $row['created_by']) ? 'd-none' : ''; ?>" 
        style="border-radius: 0;" 
        data-bs-toggle="modal" 
        data-bs-target="#editAttendanceModal"
        data-id="<?php echo $row['id_attendance_info']; ?>"
        data-name="<?php echo htmlspecialchars($row['name']); ?>"
        data-date="<?php echo $row['date']; ?>"
        data-timein="<?php echo $row['time_in']; ?>"
        data-timein-cutoff="<?php echo $row['time_in_cut_off']; ?>"
        data-timeout="<?php echo $row['time_out']; ?>"
        data-timeout-cutoff="<?php echo $row['time_out_cut_off']; ?>"
        data-sections="<?php echo htmlspecialchars($row['section']); ?>"
        data-other-accounts="<?php echo $row['other_accounts_allowed_to_record']; ?>"
        data-web-recording="<?php echo $row['allow_attendance_web_recording']; ?>"
        data-csc-access="<?php echo $row['allow_access_to_CSC']; ?>">
    Edit
</button>

                </div>
            </div>
            <?php
        }
    } else {
        // Display message if no records found
        echo "<p class='mb-4 pb-1'><br>No Attendance Records Today<br></p>";
    }
    $stmt->close();
    ?>
</div>
            </div>
          </div>
        </div>
        <div class="col-6 p-0">
          <div class="m-2 ms-1">
            <div class="d-block BoxContainers">
            <div class="BoxContainersHeader d-flex align-items-center justify-content-start">
              <div class="ms-1">
                <div>Attendance Records</div>
              </div>
            </div> 
              <div class="ms-2 me-2">

<?php
$collegeID = $_SESSION['user']['college_id'];

$query = "SELECT * FROM attendance_info WHERE college_id = ?  AND deleted_at IS NULL ORDER BY created_at DESC LIMIT 10";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $collegeID);
$stmt->execute();
$result = $stmt->get_result();


echo '
<table class="mt-2 mb-2 table table-bordered text-center align-middle table-striped">
    <thead>
        <tr>
            <th class="p-1 ps-2 text-start text-white bg-dark fw-normal">ATTENDANCE</th>
            <th class="p-1 text-white bg-dark fw-normal">P</th>
            <th class="p-1 text-white bg-dark fw-normal">L</th>
            <th class="p-1 text-white bg-dark fw-normal">A</th>
            <th class="p-1 text-white bg-dark fw-normal">TURNOUT</th>
        </tr>
    </thead>
    <tbody>
';

while ($attendance = $result->fetch_assoc()) {
    $attendanceID = $attendance['id_attendance_info'];
    $attendanceName = htmlspecialchars($attendance['name']);
    $formattedDate = date("F j, Y", strtotime($attendance['date']));
    $formattedTimeIn = date("g:i A", strtotime($attendance['time_in']));
    $formattedTimeOut = date("g:i A", strtotime($attendance['time_out']));
    $attendanceTimeInCutoff = $attendance['time_in_cut_off'];
    $sections = htmlspecialchars(str_replace(',', ', ', $attendance['section']));

    $recordQuery = "SELECT * FROM attendance_record WHERE id_attendance_info = ?";
    $stmtRecord = $db->prepare($recordQuery);
    $stmtRecord->bind_param("i", $attendanceID);
    $stmtRecord->execute();
    $records = $stmtRecord->get_result();

    $present = 0;
    $late = 0;
    $absent = 0;
    $total = 0;

    while ($record = $records->fetch_assoc()) {
        $total++;
        $timeIn = $record['time_in'];
        $timeOut = $record['time_out'];

        if (is_null($timeIn) || is_null($timeOut)) {
            $absent++;
        } elseif ($timeIn <= $attendanceTimeInCutoff) {
            $present++;
        } elseif ($timeIn > $attendanceTimeInCutoff && $timeIn <= $attendance['time_out']) {
            $late++;
        } else {
            $absent++;
        }
    }

    $turnoutTotal = $present + $late;
    $turnoutPercentage = $total > 0 ? round(($turnoutTotal / $total) * 100) : 0;

echo '
<tr class="Selection" onclick="window.location.href=\'pages/AttendanceRecord.php?id=' . $attendanceID . '\'" style="cursor: pointer;">
    <td class="text-start bg-white text-dark p-1 pt-0">
        ' . $attendanceName . '<br>' .
        $formattedDate . ' | ' . $formattedTimeIn . ' - ' . $formattedTimeOut . '<br>'  . $sections . '
    </td>
    <td class="bg-white text-dark p-1 pt-0">' . $present . '</td>
    <td class="bg-white text-dark p-1 pt-0">' . $late . '</td>
    <td class="bg-white text-dark p-1 pt-0">' . $absent . '</td>
    <td class="bg-white text-dark p-1 pt-0">' . $turnoutTotal . '/' . $total . ' (' . $turnoutPercentage . '%)</td>
</tr>
';

    $stmtRecord->close();
}

// Close the table
echo '
    </tbody>
</table>
';

$stmt->close();
?>

                <div class="w-100 d-flex justify-content-center mb-2"><a class="btn btn-sm btn-dark" href="pages/ManageAttendanceRecords.php" style="border-radius: 0;">View all Attendance Records</a></div>
                
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    </div>

<!-- Create Attendance Record Modal -->
<div class="modal fade" id="attendanceModal" tabindex="-1" aria-labelledby="attendanceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content bg-dark text-white">
      <div class="modal-header">
        <h5 class="modal-title" id="attendanceModalLabel">New Attendance Record</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="">
        <div class="modal-body">
          <!-- Hidden College ID field -->
          <input type="hidden" name="CollegeID" value="<?php echo $_SESSION['user']['college_id']; ?>">
          
          <!-- Row 1: Participants / Sections -->
          <div class="mb-3">
            <label class="form-label">Participants (Sections)</label><br>
            <div class="sections-container">
              <?php
              $CollegeID = $_SESSION['user']['college_id'];
              $GetSections = "SELECT DISTINCT section FROM student_profiles WHERE college_id = $CollegeID ORDER BY section ASC";
              $resultGetSections = $db->query($GetSections);
              while($row = $resultGetSections->fetch_assoc()) {
                echo "
                <div class='form-check form-check-inline'>
                  <input class='form-check-input' type='checkbox' name='section[]' value='{$row['section']}' id='section-".htmlspecialchars($row['section'], ENT_QUOTES)."'>
                  <label class='form-check-label' for='section-".htmlspecialchars($row['section'], ENT_QUOTES)."'>{$row['section']}</label>
                </div>
                ";
              }
              ?>
            </div>
          </div>

          <!-- Row 2: Attendance Name -->
          <div class="mb-3">
            <label for="Name" class="form-label">Attendance Name</label>
            <input type="text" class="form-control" name="Name" id="Name" required>
          </div>

          <!-- Row 3: Date -->
          <div class="mb-3">
            <label for="Date" class="form-label">Date</label>
            <input type="date" class="form-control" name="Date" id="Date" required>
          </div>

          <!-- Row 4: Time In / Time In Cut-Off -->
          <div class="mb-3 row">
            <div class="col">
              <label for="TimeIn" class="form-label">Time In</label>
              <input type="time" class="form-control" name="TimeIn" id="TimeIn" required>
            </div>
            <div class="col">
              <label for="TimeInCutOff" class="form-label">Time In Cut-Off</label>
              <input type="time" class="form-control" name="TimeInCutOff" id="TimeInCutOff" required>
            </div>
          </div>

          <!-- Row 5: Time Out / Time Out Cut-Off -->
          <div class="mb-3 row">
            <div class="col">
              <label for="TimeOut" class="form-label">Time Out</label>
              <input type="time" class="form-control" name="TimeOut" id="TimeOut" required>
            </div>
            <div class="col">
              <label for="TimeOutCutOff" class="form-label">Time Out Cut-Off</label>
              <input type="time" class="form-control" name="TimeOutCutOff" id="TimeOutCutOff" required>
            </div>
          </div>

          <!-- Row 6: Allow other accounts to record -->
          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="other_accounts_allowed_to_record" id="other_accounts_allowed_to_record" value="1" checked>
            <label class="form-check-label" for="other_accounts_allowed_to_record">
              Allow other accounts to record attendance
            </label>
          </div>

          <!-- Row 7: Allow Web Attendance Recording  -->
          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="allow_attendance_web_recording" id="allow_attendance_web_recording" value="1" checked>
            <label class="form-check-label" for="allow_attendance_web_recording">
              Allow Web Attendance Recording<br>(If disabled, you can only record attendance through the AMSBS Mobile App)
            </label>
          </div>
          
          <!-- Row 8: Give Access to College Student Council -->
          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="allow_access_to_CSC" id="allow_access_to_CSC" value="1">
            <label class="form-check-label" for="allow_access_to_CSC">
              Give Access to College Student Council
            </label>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success" name="CreateAttendanceRecordButton">Create</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Edit Attendance Record Modal -->
<div class="modal fade" id="editAttendanceModal" tabindex="-1" aria-labelledby="editAttendanceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content bg-dark text-white">
      <div class="modal-header">
        <h5 class="modal-title" id="editAttendanceModalLabel">Edit Attendance Record</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="">
        <input type="hidden" name="AttendanceID" id="editAttendanceID">
        
        <div class="modal-body">
          <!-- Hidden College ID field -->
          <input type="hidden" name="CollegeID" value="<?php echo $_SESSION['user']['college_id']; ?>">
          
<!-- Row 1: Participants / Sections -->
<div class="mb-3">
  <label class="form-label">Participants (Sections)</label><br>
  <div class="sections-container">
 <?php
              $CollegeID = $_SESSION['user']['college_id'];
              $GetSections = "SELECT DISTINCT section FROM student_profiles WHERE college_id = $CollegeID ORDER BY section ASC";
              $resultGetSections = $db->query($GetSections);
              
              while($row = $resultGetSections->fetch_assoc()) {
                  $section = $row['section'];
                  echo '
                  <div class="form-check form-check-inline me-2">
                    <input class="form-check-input section-checkbox" type="checkbox" name="section[]" 
                           value="'.htmlspecialchars($section).'" id="edit-section-'.htmlspecialchars($section).'">
                    <label class="form-check-label" for="edit-section-'.htmlspecialchars($section).'">
                      '.htmlspecialchars($section).'
                    </label>
                  </div>
                  ';
              }
              ?>
  </div>
</div>

          <!-- Row 2: Attendance Name -->
          <div class="mb-3">
            <label for="editName" class="form-label">Attendance Name</label>
            <input type="text" class="form-control" name="Name" id="editName" required>
          </div>

          <!-- Row 3: Date -->
          <div class="mb-3">
            <label for="editDate" class="form-label">Date</label>
            <input type="date" class="form-control" name="Date" id="editDate" required>
          </div>

<!-- Row 4: Time In / Time In Cut-Off -->
<div class="mb-3 row">
    <div class="col">
        <label for="editTimeIn" class="form-label">Time In</label>
        <input type="time" class="form-control" name="TimeIn" id="editTimeIn" step="60" required>
    </div>
    <div class="col">
        <label for="editTimeInCutOff" class="form-label">Time In Cut-Off</label>
        <input type="time" class="form-control" name="TimeInCutOff" id="editTimeInCutOff" step="60" required>
    </div>
</div>

<!-- Row 5: Time Out / Time Out Cut-Off -->
<div class="mb-3 row">
    <div class="col">
        <label for="editTimeOut" class="form-label">Time Out</label>
        <input type="time" class="form-control" name="TimeOut" id="editTimeOut" step="60" required>
    </div>
    <div class="col">
        <label for="editTimeOutCutOff" class="form-label">Time Out Cut-Off</label>
        <input type="time" class="form-control" name="TimeOutCutOff" id="editTimeOutCutOff" step="60" required>
    </div>
</div>

          <!-- Row 6: Allow other accounts to record -->
          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="other_accounts_allowed_to_record" id="editOtherAccountsAllowed" value="1">
            <label class="form-check-label" for="editOtherAccountsAllowed">
              Allow other accounts to record attendance
            </label>
          </div>

          <!-- Row 7: Allow Web Attendance Recording  -->
          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="allow_attendance_web_recording" id="editWebRecording" value="1">
            <label class="form-check-label" for="editWebRecording">
              Allow Web Attendance Recording<br>(If disabled, you can only record attendance through the AMSBS Mobile App)
            </label>
          </div>
          
          <!-- Row 8: Give Access to College Student Council -->
          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="allow_access_to_CSC" id="editCSCAccess" value="1">
            <label class="form-check-label" for="editCSCAccess">
              Give Access to College Student Council
            </label>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success" name="UpdateAttendanceRecordButton">Update</button>
        </div>
      </form>
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle edit button clicks
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function() {
            // Clear previous hidden inputs
            document.querySelectorAll('input[type="hidden"][name="section[]"]').forEach(input => {
                if(input.previousElementSibling?.matches('.section-checkbox[disabled]')) {
                    input.remove();
                }
            });

            // Get data from the button's data attributes
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const date = this.getAttribute('data-date');
            const timeIn = this.getAttribute('data-timein');
            const timeInCutoff = this.getAttribute('data-timein-cutoff');
            const timeOut = this.getAttribute('data-timeout');
            const timeOutCutoff = this.getAttribute('data-timeout-cutoff');
            const sections = this.getAttribute('data-sections').split(',').map(s => s.trim());
            const otherAccounts = this.getAttribute('data-other-accounts') === '1';
            const webRecording = this.getAttribute('data-web-recording') === '1';
            const cscAccess = this.getAttribute('data-csc-access') === '1';

            // Set the values in the edit modal
            document.getElementById('editAttendanceID').value = id;
            document.getElementById('editName').value = name;
            document.getElementById('editDate').value = date;
            
            // Set time values without seconds
            document.getElementById('editTimeIn').value = timeIn.substring(0, 5);
            document.getElementById('editTimeInCutOff').value = timeInCutoff.substring(0, 5);
            document.getElementById('editTimeOut').value = timeOut.substring(0, 5);
            document.getElementById('editTimeOutCutOff').value = timeOutCutoff.substring(0, 5);
            
            // Handle section checkboxes
            document.querySelectorAll('.section-checkbox').forEach(checkbox => {
                // Reset all checkboxes
                checkbox.checked = false;
                checkbox.disabled = false;
                
                // Check if this section is in the existing selection
                if(sections.includes(checkbox.value.trim())) {
                    checkbox.checked = true;
                    checkbox.disabled = true;
                    
                    // Add hidden input to preserve the value
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'section[]';
                    hiddenInput.value = checkbox.value;
                    checkbox.parentNode.insertBefore(hiddenInput, checkbox.nextSibling);
                }
            });
            
            // Set other checkboxes
            document.getElementById('editOtherAccountsAllowed').checked = otherAccounts;
            document.getElementById('editWebRecording').checked = webRecording;
            document.getElementById('editCSCAccess').checked = cscAccess;
        });
    });
});
</script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
  </body>
</html>