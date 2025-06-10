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

$user = $_SESSION['user'];
$lastName = ucwords(strtolower($user['last_name']));
$firstName = ucwords(strtolower($user['first_name']));
$middleInitial = ucfirst(strtolower(substr($user['middle_name'], 0, 1))) . ".";

$AccountName = $lastName . ", " . $firstName . " " . $middleInitial;

$stud_id = $_GET['id'];

$Studentquery = "SELECT college_id FROM student_profiles WHERE stud_id = '$stud_id'";
$ResultStudentquery = $db->query($Studentquery);
$CheckStudentProfiles = $ResultStudentquery->fetch_assoc();

if($CheckStudentProfiles['college_id'] != $user['college_id']){
  header("Location: StudentProfiles.php");
}

$sql = "SELECT 
            ai.name,
            ai.date,
            ai.time_in as aitimein,
            ai.time_out as aitimeout,
            ai.time_in_cut_off as aitimeinc,
            ai.time_out_cut_off as aitimeoutc,
            ar.time_in,
            ar.time_out
        FROM attendance_record ar
        JOIN attendance_info ai ON ar.id_attendance_info = ai.id_attendance_info
        JOIN student_profiles s ON ar.stud_id = s.stud_id
        WHERE ar.stud_id = ? AND deleted_at IS NULL
        ORDER BY ai.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->bind_param("s", $stud_id);
$stmt->execute();
$result = $stmt->get_result();

// Count attendance status
$statusCountQuery = "SELECT 
    SUM(CASE 
        WHEN ar.time_in IS NOT NULL 
             AND ar.time_out IS NOT NULL 
             AND ar.time_in <= ai.time_in_cut_off THEN 1 
        ELSE 0 
    END) as present_count,
    
    SUM(CASE 
        WHEN ar.time_in IS NOT NULL 
             AND ar.time_out IS NOT NULL 
             AND ar.time_in > ai.time_in_cut_off THEN 1 
        ELSE 0 
    END) as late_count,
    
    SUM(CASE 
        WHEN (ar.time_in IS NULL AND ar.time_out IS NULL) OR 
             (ar.time_in IS NULL AND ar.time_out IS NOT NULL) OR 
             (ar.time_in IS NOT NULL AND ar.time_out IS NULL) OR 
             (ar.time_in IS NOT NULL AND ar.time_out IS NOT NULL AND ar.time_in > ai.time_out_cut_off) THEN 1 
        ELSE 0 
    END) as absent_count,
    
    COUNT(*) as total_records
FROM attendance_record ar
JOIN attendance_info ai ON ar.id_attendance_info = ai.id_attendance_info
WHERE ar.stud_id = ? AND ai.deleted_at IS NULL";

$stmtCount = $db->prepare($statusCountQuery);
$stmtCount->bind_param("s", $stud_id);
$stmtCount->execute();
$countResult = $stmtCount->get_result();
$attendanceStats = $countResult->fetch_assoc();

$presentCount = $attendanceStats['present_count'];
$lateCount = $attendanceStats['late_count'];
$absentCount = $attendanceStats['absent_count'];
$totalRecords = $attendanceStats['total_records'];
$attendedCount = $presentCount + $lateCount;
$attendancePercentage = $totalRecords > 0 ? round(($attendedCount / $totalRecords) * 100) : 0;

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
            <li class="mb-2">
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
            <li class="NavCurrentPage mb-2">
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
            <div class="row">
                <div class="col-12 col-md-6">
                    <div class="p-2 pb-0">
                      <?php
                      
                      $GetStudentQuery = "SELECT * FROM student_profiles WHERE stud_id = '$stud_id'";
                      $ResultStudentQuery = $db->query($GetStudentQuery);
                      $FetchResultStudentQuery = $ResultStudentQuery->fetch_assoc();
                      $NAME = $FetchResultStudentQuery['lname'] . ', ' . $FetchResultStudentQuery['fname'] . ' ' . $FetchResultStudentQuery['mname'];
                      ?>
                        <p style="font-size: 20px;font-weight: bold;" class="m-0 p-0"><?php echo $NAME ?></p>
                        <p style="font-size: 20px;" class="m-0 p-0"><?php echo $FetchResultStudentQuery['stud_id'] . " | " . $FetchResultStudentQuery['section']. " | " . $FetchResultStudentQuery['sex']; ?></p>
                        <div class="w-100 d-none"><button class="btn btn-warning btn-sm mt-1" style="border-radius: 0;">
                            <svg xmlns="http://www.w3.org/2000/svg" style="margin-top:-21px;padding-top:17px;" width="25" height="40" fill="currentColor" class="bi bi-upc" viewBox="0 0 16 16">
                              <path d="M3 4.5a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0zm2 0a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0zm2 0a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0zm2 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm3 0a.5.5 0 0 1 1 0v7a.5.5 0 0 1-1 0z"/>
                            </svg>
                                Download Barcode</button>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="p-2 pb-0">
<div class="col-12 col-md-6">
    <div class="p-2 pb-0">
        <p class="m-0 p-0">
            Present: <?php echo $presentCount; ?> | Late: <?php echo $lateCount; ?> | Absent: <?php echo $absentCount; ?> 
            <br>
            Total Attended: <?php echo $attendedCount; ?>/<?php echo $totalRecords; ?> (<?php echo $attendancePercentage; ?>%)
        </p>
    </div>
</div>
                    </div>
                    
                </div>
            </div>
            <table class="mt-2 mb-2 table table-bordered align-middle table-striped">
                <thead>
                    <tr>
                        <th class="bg-dark text-white p-1 fw-normal text-center">#</th>
                        <th class="bg-dark text-white p-1 fw-normal">ATTENDANCE NAME</th>
                        <th class="bg-dark text-white p-1 fw-normal">DATE</th>
                        <th class="bg-dark text-white p-1 fw-normal">TIME IN</th>
                        <th class="bg-dark text-white p-1 fw-normal">TIME OUT</th>
                        <th class="bg-dark text-white p-1 fw-normal">REMARKS</th>
                    </tr>
                </thead>
                <tbody>
<?php
$counter = 1;
while ($row = $result->fetch_assoc()):
?>
<tr>
    <td class="p-1 text-center"><?php echo $counter++; ?></td>
    <td class="p-1"><?php echo htmlspecialchars($row['name']); ?></td>
    <td class="p-1"><?php echo date("F j, Y", strtotime($row['date'])); ?></td>
    <td class="p-1">
        <?php 
            echo !empty($row['time_in']) ? date("g:i A", strtotime($row['time_in'])) : "—"; 
        ?>
    </td>
    <td class="p-1">
        <?php 
            echo !empty($row['time_out']) ? date("g:i A", strtotime($row['time_out'])) : "—"; 
        ?>
    </td>
    <td class="p-1">
        <?php

        $remarks = $row['remarks'] ?? null;

        $currentTime = date("H:i:s");
        $cutoffDateTime = strtotime($row['date'] . ' ' . $row['aitimeoutc']);
        $currentDateTime = strtotime(date('Y-m-d H:i:s'));
        $isPastCutoff = $currentDateTime > $cutoffDateTime;

        $timeIn = $row['time_in'];
        $timeOut = $row['time_out'];
        $timeInCutoff = $row['aitimeinc'];

        if ($remarks === 1) {
            echo "<p class='m-0 p-0' style='color:green;'>EXCUSED</p>";
        } elseif ($remarks === 2) {
            echo "<p class='m-0 p-0' style='color:red;'>CANCELLED</p>";
        } else {
            if (is_null($timeIn) && !is_null($timeOut)) {
                echo "<p class='m-0 p-0' style='color:red;'>NO TIME IN</p>";
            } elseif (!is_null($timeIn) && is_null($timeOut) && $isPastCutoff) {
                echo "<p class='m-0 p-0' style='color:red;'>NO TIME OUT</p>";
            } elseif (!is_null($timeIn) && $timeIn <= $timeInCutoff) {
                echo "<p class='m-0 p-0' style='color:green;'>PRESENT</p>";
            } elseif (!is_null($timeIn) && $timeIn > $timeInCutoff) {
                echo "<p class='m-0 p-0' style='color:orange;'>LATE</p>";
            } elseif ($isPastCutoff) {
                echo "<p class='m-0 p-0' style='color:red;'>ABSENT</p>";
            } else {
                echo "<p class='m-0 p-0 text-muted'>Pending</p>";
            }
        }
        ?>
    </td>
</tr>


<?php endwhile; ?>

                </tbody>
            </table>
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