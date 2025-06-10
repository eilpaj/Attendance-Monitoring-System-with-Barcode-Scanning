<?php
include "../db_connection.php";

if(!isset($_SESSION['user'])) {
    header("Location: ../login.php");
    exit();
}

if(isset($_POST['LogOutButton'])){
    session_destroy();
    header("Location: ../login.php");
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../index.php");
    exit();
}

$user = $_SESSION['user'];
$lastName = ucwords(strtolower($user['last_name']));
$firstName = ucwords(strtolower($user['first_name']));
$middleInitial = ucfirst(strtolower(substr($user['middle_name'], 0, 1))) . ".";

$AccountName = $lastName . ", " . $firstName . " " . $middleInitial;

$id_attendance_info = $_GET['id'];
$usercollegeid = $user['college_id'];

$CheckCollegeQuery = "SELECT college_id FROM attendance_info WHERE id_attendance_info = $id_attendance_info";
$CheckCollegeQueryResult = $db->query($CheckCollegeQuery);
$collegeData = $CheckCollegeQueryResult->fetch_assoc();

if ((int)$usercollegeid !== (int)$collegeData['college_id']) {
    header("Location: ../index.php");
    exit();
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

if (isset($_POST['deletebtn'])) {
    $deleteAttendanceId = $id_attendance_info;

    $stmt = $db->prepare("UPDATE attendance_info SET deleted_at = NOW() WHERE id_attendance_info = ?");
    $stmt->bind_param("i", $deleteAttendanceId);
    $stmt->execute();
}

$isDeletedQuery = "SELECT deleted_at FROM attendance_info WHERE id_attendance_info = $id_attendance_info";
$resultisDeletedQuery = $db->query($isDeletedQuery);
$checkisDeletedQuery = $resultisDeletedQuery->fetch_assoc();

if(!is_null($checkisDeletedQuery['deleted_at'])){
  header("Location: ManageAttendanceRecords.php");
}

?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AMSBS Attendance Record</title>
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
        <div class="col-12 p-2">
<?php
$AttedanceInfo = $db->query("SELECT * FROM attendance_info WHERE id_attendance_info = $id_attendance_info");
$attendanceData = $AttedanceInfo->fetch_assoc();
$sections = explode(',', $attendanceData['section']);

$date = new DateTime($attendanceData['date']);
$timeIn = new DateTime($attendanceData['time_in']);
$timeInCutOff = new DateTime($attendanceData['time_in_cut_off']);
$timeOut = new DateTime($attendanceData['time_out']);
$timeOutCutOff = new DateTime($attendanceData['time_out_cut_off']);
$createdAt = new DateTime($attendanceData['created_at']);

$formattedDate = $date->format('F j, Y'); 
$formattedTimeIn = $timeIn->format('g:i A'); 
$formattedTimeInCutOff = $timeInCutOff->format('g:i A');
$formattedTimeOut = $timeOut->format('g:i A');
$formattedTimeOutCutOff = $timeOutCutOff->format('g:i A');
$formattedCreatedAt = $createdAt->format('F j, Y g:i A'); 

$creatorID = $attendanceData['created_by'];
$fetchCreatorName = $db->query("SELECT first_name, middle_name, last_name FROM user_accounts WHERE account_id = $creatorID");
$creator = $fetchCreatorName->fetch_assoc();
$creatorname = 
    ucwords(strtolower($creator['last_name'])) . ', ' . 
    ucwords(strtolower($creator['first_name'])) . ' ' . 
    strtoupper(substr($creator['middle_name'], 0, 1)) . '.';


?>

            <div class="BoxContainers pb-2">
                <div class="BoxContainersHeader d-flex justify-content-between align-items-center ps-2">
                  <div class="fw-bold">
                    <?php echo $attendanceData['name']; ?>
                  </div>
                  <div class="d-inline-flex flex-nowrap">
                    <span <?php if($attendanceData['created_by'] != $user['account_id']){ echo 'title="Only the user who created this attendance record can edit."';}?>>
<button class="btn btn-sm btn-light me-1 edit-btn" style="border-radius: 0;" type="button"
    data-bs-toggle="modal" 
    data-bs-target="#editAttendanceModal"
    data-id="<?php echo $attendanceData['id_attendance_info']; ?>"
    data-name="<?php echo htmlspecialchars($attendanceData['name']); ?>"
    data-date="<?php echo $attendanceData['date']; ?>"
    data-timein="<?php echo $attendanceData['time_in']; ?>"
    data-timein-cutoff="<?php echo $attendanceData['time_in_cut_off']; ?>"
    data-timeout="<?php echo $attendanceData['time_out']; ?>"
    data-timeout-cutoff="<?php echo $attendanceData['time_out_cut_off']; ?>"
    data-sections="<?php echo htmlspecialchars($attendanceData['section']); ?>"
    data-other-accounts="<?php echo $attendanceData['other_accounts_allowed_to_record']; ?>"
    data-web-recording="<?php echo $attendanceData['allow_attendance_web_recording']; ?>"
    data-csc-access="<?php echo $attendanceData['allow_access_to_CSC']; ?>"
    <?php if($attendanceData['created_by'] != $user['account_id']){ echo 'disabled';} ?>
    >Edit</button>
    </span>
                    <span <?php if($attendanceData['created_by'] != $user['account_id']){ echo 'title="Only the user who created this attendance record can delete."';}?>>
                    <button class="btn btn-sm btn-danger" style="border-radius: 0;" type="button" data-bs-toggle="modal" data-bs-target="#deleteAttendanceModal" <?php if($attendanceData['created_by'] != $user['account_id']){ echo 'disabled';} ?>>Delete</button>
                  </span>
                  </div>
                </div>
                <div class="d-flex m-2">
                  <div class="w-100 d-flex">
                    <div>
                      <div class="me-3">DATE:</div>
                      <div class="me-3">TIME IN:</div>
                      <div class="me-3">TIME OUT:</div>
                    </div>
                    <div>
                      <div> <?php echo $formattedDate; ?></div>
                      <div> <?php echo $formattedTimeIn . ' - ' . $formattedTimeInCutOff; ?></div>
                      <div> <?php echo $formattedTimeOut . ' - ' . $formattedTimeOutCutOff; ?></div>
                    </div>
                  </div>
                  <div class="w-100">
                  <div class="w-100 d-flex">
                    <div>
                      <div class="me-3">CREATED BY:</div>
                      <div class="me-3">CREATED AT:</div>
                    </div>
                    <div>
                      <div><?php echo $creatorname; ?></div>
                      <div><?php echo $formattedCreatedAt; ?></div>
                    </div>
                  </div>
                  </div>
                  <div class="w-100">
                  <?php if ($attendanceData['other_accounts_allowed_to_record'] == 1): ?>
                  <div class="">Other users are allowed to record.</div>
                  <?php endif; ?>
                  <?php if ($attendanceData['allow_attendance_web_recording'] == 1): ?>
                  <div class="">Recording attendance via Web is allowed.</div>
                  <?php endif; ?>
                  <?php if ($attendanceData['allow_attendance_web_recording'] == 0): ?>
                  <div class="">Can only record attendance via AMSBS app.</div>
                  <?php endif; ?>
                  <?php if ($attendanceData['allow_access_to_CSC'] == 1): ?>
                  <div class="">CSC has access to this record.</div>
                  <?php endif; ?>
                  </div>
                </div>
                <div>
                    <form class="ms-2 mt-2">
                        <div class="d-flex">
                            <div>
                                <div class="me-3">PARTICIPANTS:</div>
                                <div class="me-3">SEX:</div>
                                <div class="me-3">REMARKS:</div>
                            </div>
                            <div>
                                <div class="d-block justify-content-start align-items-center">
                                      <div class="d-flex align-items-center">
<?php foreach ($sections as $index => $section): 
    $section = trim($section);
    $inputId = 'YearAndSectionSelection_' . $index;
?>

        <input type="checkbox" id="<?= $inputId ?>" name="sections[]" value="<?= htmlspecialchars($section) ?>">
        <label for="<?= $inputId ?>" class="ms-1 me-3"><?= htmlspecialchars($section) ?></label>
   
<?php endforeach; ?>
</div>
                            <div class="d-flex">
                                
                                <input type="checkbox" id="MaleSexSelection">
                                <label for="MaleSexSelection" class="ms-1 me-3">Male</label>
                                <input type="checkbox" id="FemaleSexSelection">
                                <label for="FemaleSexSelection" class="ms-1">Female</label>
                            </div>
                            <div class="d-flex">
                                
                                <input type="checkbox" id="PresentRemarksSelection">
                                <label for="PresentRemarksSelection" class="ms-1 me-3">Present</label>       
                                <input type="checkbox" id="LateRemarksSelection">
                                <label for="LateRemarksSelection" class="ms-1 me-3">Late</label>   
                                <input type="checkbox" id="AbsentRemarksSelection">
                                <label for="AbsentRemarksSelection" class="ms-1">Absent</label>                           
                            </div>
                            <div>
                            <button type="submit" class="btn btn-sm btn-dark mt-1" style="border-radius: 0;">SELECT</button>
                            <button type="submit" class="btn btn-sm btn-warning mt-1" style="border-radius: 0;">PRINT</button>
                            </div>
                        </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
<table class="mt-2 mb-2 table table-bordered align-middle table-striped">
  <thead>
    <tr>
      <th class="p-1 text-center bg-dark fw-normal text-white">#</th>
      <th class="bg-dark text-white p-1 fw-normal">YR. & SEC.</th>
      <th class="bg-dark text-white p-1 fw-normal">NAME</th>
      <th class="bg-dark text-white p-1 fw-normal">SEX</th>
      <th class="bg-dark text-white p-1 fw-normal">TIME IN</th>
      <th class="bg-dark text-white p-1 fw-normal">TIME OUT</th>
      <th class="bg-dark text-white p-1 fw-normal">LOCATION</th>
      <th class="bg-dark text-white p-1 fw-normal">REMARKS</th>
      <th class="bg-dark text-white p-1 fw-normal"></th>
    </tr>
  </thead>
  <?php
    $query = "
        SELECT 
            ar.id_attendance_record,
            ar.id_attendance_info,
            ar.time_in,
            ar.time_out,
            ar.remarks,
            ar.location,
            ar.update_time,
            ar.recorded_by,
            sp.stud_id,
            sp.lname,
            sp.fname,
            sp.mname,
            sp.name_ext,
            sp.section,
            sp.sex
        FROM attendance_record ar
        JOIN student_profiles sp ON ar.stud_id = sp.stud_id
        WHERE ar.id_attendance_info = ?
        ORDER BY sp.lname, sp.fname
    ";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $id_attendance_info);
    $stmt->execute();
    $result = $stmt->get_result();
    $students = $result->fetch_all(MYSQLI_ASSOC);

?>

<tbody>
<?php if (empty($students)): ?>
    <tr>
        <td colspan="8" class="text-center text-muted">No records found for this attendance session.</td>
    </tr>
<?php else: ?>
    <?php foreach ($students as $index => $student): 
        $fullName = strtoupper("{$student['lname']}, {$student['fname']} " . 
            ($student['mname'] ? substr($student['mname'], 0, 1) . '.' : '') . 
            ($student['name_ext'] ? ' ' . $student['name_ext'] : ''));

        $yrSec = "{$student['section']}";
    ?>
    <tr>
        <td class="p-1 text-center"><?= $index + 1 ?></td>
        <td class="p-1"><?= htmlspecialchars($yrSec) ?></td>
        <td class="p-1"><?= htmlspecialchars($fullName) ?></td>
        <td class="p-1"><?= htmlspecialchars(strtoupper(substr($student['sex'], 0, 1))) ?></td>
        <td class="p-1"><?= $student['time_in'] ? date("g:i A", strtotime($student['time_in'])) : '—' ?></td>
        <td class="p-1"><?= $student['time_out'] ? date("g:i A", strtotime($student['time_out'])) : '—' ?></td>
        <td class="p-1"><?php echo $student['location']; ?></td>
        <td class="p-1">
<?php

$attendanceid = $student['id_attendance_info'];
$query = "SELECT * FROM attendance_info WHERE id_attendance_info = $attendanceid";
$result = $db->query($query);
$attendanceInfo = $result->fetch_assoc();

$remarks = isset($student['remarks']) ? (int)$student['remarks'] : null;

$currentDateTime = new DateTime();
$attendanceDateTimeCutoff = new DateTime($attendanceInfo['time_out_cut_off']);
$attendanceDate = new DateTime($attendanceInfo['date']);

// Check if current time/date is past cutoff
$isPastCutoff = $currentDateTime > $attendanceDateTimeCutoff || $currentDateTime->format('Y-m-d') > $attendanceDate->format('Y-m-d');

if ($remarks === 1) {
    echo "<p class='m-0 p-0' style='color:green;'>EXCUSED</p>";
} elseif ($remarks === 2) {
    echo "<p class='m-0 p-0' style='color:red;'>CANCELLED</p>";
} else {
    if (is_null($student['time_in']) && !is_null($student['time_out'])) {
        echo "<p class='m-0 p-0' style='color:red;'>NO TIME IN</p>";
    } elseif (!is_null($student['time_in']) && is_null($student['time_out']) && $isPastCutoff) {
        echo "<p class='m-0 p-0' style='color:red;'>NO TIME OUT</p>";
    } elseif (!is_null($student['time_in']) && $student['time_in'] <= $attendanceInfo['time_in_cut_off']) {
        echo "<p class='m-0 p-0' style='color:green;'>PRESENT</p>";
    } elseif (!is_null($student['time_in']) && $student['time_in'] > $attendanceInfo['time_in_cut_off']) {
        echo "<p class='m-0 p-0' style='color:orange;'>LATE</p>";
    } elseif ($isPastCutoff) {
        echo "<p class='m-0 p-0' style='color:red;'>ABSENT</p>";
    } else {
        echo "<p class='m-0 p-0 text-muted'>Pending</p>"; 
    }
}
?>

        </td>
        <td style="width:10px;" class="text-end p-1">
            <div class="dropdown">
                <a role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <svg style="margin-top: -3px;" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="black" class="bi bi-three-dots-vertical" viewBox="0 0 16 16">
                        <path d="M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0"/>
                    </svg>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#">Mark as Present</a></li>
                    <li><a class="dropdown-item" href="#">Mark as Late</a></li>
                    <li><a class="dropdown-item" href="#">Mark as Absent</a></li>
                    <li><a class="dropdown-item" href="#">Mark as Excused</a></li>
                </ul>
            </div>
        </td>
    </tr>
    <?php endforeach; ?>
<?php endif; ?>
</tbody>

</table>


        </div>
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

// Get all available sections
$GetSections = "SELECT DISTINCT section FROM student_profiles WHERE college_id = $CollegeID ORDER BY section ASC";
$resultGetSections = $db->query($GetSections);

// Get already selected sections for this attendance record
$selectedSections = array();
if (isset($id_attendance_info)) {
    $GetSelectedSections = "SELECT section FROM attendance_info WHERE id_attendance_info = $id_attendance_info ORDER BY section ASC";
    $resultSelectedSections = $db->query($GetSelectedSections);
    if ($resultSelectedSections && $resultSelectedSections->num_rows > 0) {
        $row = $resultSelectedSections->fetch_assoc();
        // Split the comma-separated string into an array
        $selectedSections = array_map('trim', explode(',', $row['section']));
    }
}
while($row = $resultGetSections->fetch_assoc()) {
    $section = $row['section'];
    $isSelected = in_array($section, $selectedSections);
    
    echo "
    <div class='form-check form-check-inline me-2'>";
    
    if ($isSelected) {
        echo "
        <input class='form-check-input section-checkbox' type='checkbox' name='section[]' value='{$section}' id='edit-section-".htmlspecialchars($section, ENT_QUOTES)."' checked disabled>
        <input type='hidden' name='section[]' value='{$section}'>";
    } else {
        echo "
        <input class='form-check-input section-checkbox' type='checkbox' name='section[]' value='{$section}' id='edit-section-".htmlspecialchars($section, ENT_QUOTES)."'>";
    }
    
    echo "
        <label class='form-check-label' for='edit-section-".htmlspecialchars($section, ENT_QUOTES)."'>{$section}</label>
    </div>";
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

<!-- Delete Attendance Record Modal -->
<div class="modal fade" id="deleteAttendanceModal" tabindex="-1" aria-labelledby="deleteAttendanceModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content bg-dark text-white">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteAttendanceModalLabel">Delete Attendance Record</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to delete this attendance record?<br>Deleted Attendance Records will be recoverable within 30 days upon deletion.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
        <form method="POST">
          <button type="submit" class="btn btn-danger" name="deletebtn">Delete</button>
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
            // Get data from the button's data attributes
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const date = this.getAttribute('data-date');
            const timeIn = this.getAttribute('data-timein');
            const timeInCutoff = this.getAttribute('data-timein-cutoff');
            const timeOut = this.getAttribute('data-timeout');
            const timeOutCutoff = this.getAttribute('data-timeout-cutoff');
            const sections = this.getAttribute('data-sections').split(',');
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
            
            // Uncheck all section checkboxes first
            document.querySelectorAll('.section-checkbox').forEach(checkbox => {
                checkbox.checked = false;
            });
            
            // Check the sections that were selected
            sections.forEach(section => {
                const trimmedSection = section.trim();
                // Find checkbox by value instead of ID to avoid issues with special characters
                document.querySelectorAll('.section-checkbox').forEach(checkbox => {
                    if (checkbox.value === trimmedSection) {
                        checkbox.checked = true;
                    }
                });
            });
            
            // Set the checkbox values
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