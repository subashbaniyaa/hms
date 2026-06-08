<?php
ob_start();
session_start();

require '../DB.php';
require '../Util.php';
require '../dao/BookingDetailDAO.php';
require '../handlers/BookingDetailHandler.php';

if (isset($_COOKIE['is_admin']) && $_COOKIE['is_admin'] == 'true') {
    if (isset($_SESSION["isAdmin"]) && $_SESSION["isAdmin"][1] == "true") {
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["booking_id"]) && isset($_POST["notes"])) {
            // Supports single ID or array of IDs (bulk "Add Note" from "With selected")
            $ids   = (array) $_POST["booking_id"];
            $notes = trim($_POST["notes"]);
            $successCount = 0;
            $errorCount   = 0;
            $bdh = new BookingDetailHandler();
            foreach ($ids as $rawId) {
                $bookingId = intval($rawId);
                if ($bookingId > 0) {
                    if ($bdh->saveNotes($bookingId, $notes)) {
                        $successCount++;
                    } else {
                        $errorCount++;
                    }
                } else {
                    $errorCount++;
                }
            }
            if ($successCount > 0) {
                echo Util::displayAlertV1("Note saved successfully for $successCount reservation(s).", "success");
            } else {
                echo Util::displayAlertV1("Failed to save note. Please try again.", "danger");
            }
        }
    } else {
        echo Util::displayAlertV1("Unauthorized.", "danger");
    }
} else {
    echo Util::displayAlertV1("Not allowed.", "danger");
}
