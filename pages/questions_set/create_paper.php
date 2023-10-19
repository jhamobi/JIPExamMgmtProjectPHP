<?php
session_start();
if (!isset($_SESSION['userId']) || !isset($_SESSION['userType'])) {
    header("Location: ../../login.html");
} else {
    include "../../config.php";
    require("../../../fpdf/fpdf.php");

    if (isset($_POST['fillDetail'])) {
        $paperId = $_POST['fillDetail'];
        $instiName = $_POST['instituteName'];
        $examDate = $_POST['examDate'];
        $examDuration = $_POST['duration'];
        $programName = $_POST['gradeName'];

        $result = mysqli_query($conn, "INSERT INTO `paper_details` (`quesSetId`, `instituteName`, `examDate`, `duration`, `gradeName`) VALUES ('$paperId', ' $instiName', '$examDate', '$examDuration', '$programName')");
        if ($result) {
            if (isset($_POST['fillDetail'])) {

                $paperId = $_POST['fillDetail'];

                $result1 = mysqli_query($conn, "select * from questionSet WHERE `quesSetId`='$paperId'");
                while ($row = mysqli_fetch_array($result1)) {
                    $quesCount = $row['quesCount'];
                    $quesIdList = $row['quesIdList'];
                }

                $result2 = mysqli_query($conn, "select * from paper_details WHERE `quesSetId`='$paperId'");
                while ($row = mysqli_fetch_array($result2)) {
                    $instituteName = trim($row['instituteName']);
                    $date = $row['examDate'];
                    $duration = $row['duration'];
                }

                $quesIdArray = explode(',', $quesIdList);

                $pdf = new FPDF();
                $pdf->AddPage('P', 'Letter');
                $pdf->SetMargins(10, 10, 10);

                $pdf->SetFont("Arial", "", 12);

                $totalMarks = 0;
                $gradeName = "";
                $subjectName = "";
                $firstQuestion = true;

                foreach ($quesIdArray as $quesId) {
                    $query = "SELECT marks, gradeName, subjectName FROM question WHERE `questionId`='$quesId'";
                    $queryrun = mysqli_query($conn, $query);

                    if ($queryrun && mysqli_num_rows($queryrun) > 0) {
                        $row = mysqli_fetch_assoc($queryrun);
                        $marks = $row['marks'];
                        $totalMarks += $marks;
                    }
                }
                $originalY = $pdf->GetY();
                $pdf->SetY($originalY + 33);
                $pdf->Cell(0, 10, "Total Marks: $totalMarks", 0, 0, 'L');
                $pdf->SetY($originalY);

                foreach ($quesIdArray as $quesId) {
                    $query = "SELECT marks, gradeName, subjectName FROM question WHERE `questionId`='$quesId'";
                    $queryrun = mysqli_query($conn, $query);

                    if ($queryrun && mysqli_num_rows($queryrun) > 0) {
                        $row = mysqli_fetch_assoc($queryrun);
                        $marks = $row['marks'];
                        $totalMarks += $marks;
                    }

                    $result2 = mysqli_query($conn, "SELECT * FROM question WHERE `questionId`='$quesId'");
                    $optionLabels = ['A', 'B', 'C', 'D'];

                    while ($row = mysqli_fetch_array($result2)) {
                        $marks = $row['marks'];

                        if ($firstQuestion) {
                            $gradeName = $row['gradeName'];
                            $subjectName = $row['subjectName'];

                            $pdf->SetFont("Arial", "B", 14);
                            $pdf->Cell(0, 10, "Institute: $instituteName", 0, 1, 'C');
                            $pdf->Cell(0, 10, "Grade: $gradeName", 0, 1, 'C');
                            $pdf->Cell(0, 10, "Subject: $subjectName", 0, 1, 'C');
                            $pdf->SetFont("Arial", "", 10);
                            $pdf->Cell(0, 10, "Date: $date", 0, 1, 'R');

                            $pdf->Cell(0, 10, "Hour: $duration", 0, 1, 'R');

                            $firstQuestion = false;
                        }

                        $chapterName = trim($row['chapterName']);
                        $questionText = trim($row['questionText']);

                        // Print Chapter
                        $pdf->SetFont("Arial", "B", 12);
                        $pdf->Cell(0, 10, "Chapter: $chapterName", 0, 1, 'L');
                        $pdf->SetFont("Arial", "", 12);

                        // Print Question
                        $pdf->MultiCell(0, 10, "Q.  $questionText", 0, 'L');

                        $answerOptionsResult = mysqli_query($conn, "SELECT answerOpt1Text, answerOpt2Text, answerOpt3Text, answerOpt4Text FROM answer_options WHERE `questionId`='$quesId'");

                        if ($answerRow = mysqli_fetch_array($answerOptionsResult)) {
                            $answerOptions = [
                                trim($answerRow['answerOpt1Text']),
                                trim($answerRow['answerOpt2Text']),
                                trim($answerRow['answerOpt3Text']),
                                trim($answerRow['answerOpt4Text'])
                            ];

                            // Print answer options
                            for ($i = 0; $i < count($answerOptions); $i++) {
                                $pdf->SetFont("Arial", "", 10);
                                $pdf->Cell(10);
                                $pdf->Cell(0, 10, "{$optionLabels[$i]}. {$answerOptions[$i]}", 0, 1, 'L');
                            }
                        }
                        $pdf->Cell(0, 10, "Marks: $marks", 0, 1, 'R');

                        $pdf->Ln();
                    }
                }

                $pdf->Output();
            }
        } else {
            echo "<script type='text/javascript'>
            window.location.href='http://localhost/assessment/pages/questions_set/add_question_set.php';
            alert('Something went wrong!')
            </script>";
        }
    }
}
?>
