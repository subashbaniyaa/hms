<?php
ob_start();
session_start();

require '../DB.php';
require '../Util.php';
require '../models/StatusEnum.php';
require '../dao/BookingDetailDAO.php';
require '../handlers/BookingDetailHandler.php';

if (isset($_COOKIE['is_admin']) && $_COOKIE['is_admin'] == 'true') {
    if (isset($_SESSION["isAdmin"]) && $_SESSION["isAdmin"][1] == "true") {

        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["confirm"])) {
            if (!isset($_POST["item"]) || !is_array($_POST["item"]) || empty($_POST["item"])) {
                echo Util::displayAlertV1("No reservations selected.", "warning");
                exit;
            }
            try {
                $bdh = new BookingDetailHandler();
                $bdh->confirmSelection($_POST["item"]);
                $feedback = $bdh->getExecutionFeedback();
                if (strpos($feedback, 'Already confirmed') !== false) {
                    echo Util::displayAlertV1($feedback, "warning");
                } else {
                    echo Util::displayAlertV1($feedback, "info");
                }
            } catch (\Throwable $e) {
                echo Util::displayAlertV1("Server error while confirming. Please try again.", "danger");
            }
        }

        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["cancel"])) {
            if (!isset($_POST["item"]) || !is_array($_POST["item"]) || empty($_POST["item"])) {
                echo Util::displayAlertV1("No reservations selected.", "warning");
                exit;
            }
            try {
                $bdh = new BookingDetailHandler();
                $bdh->cancelSelection($_POST["item"]);
                $feedback = $bdh->getExecutionFeedback();
                if (strpos($feedback, 'Already cancelled') !== false) {
                    echo Util::displayAlertV1($feedback, "warning");
                } else {
                    echo Util::displayAlertV1($feedback, "info");
                }
            } catch (\Throwable $e) {
                echo Util::displayAlertV1("Server error while cancelling. Please try again.", "danger");
            }
        }

        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete"])) {
            if (!isset($_POST["item"]) || !is_array($_POST["item"]) || empty($_POST["item"])) {
                echo Util::displayAlertV1("No reservations selected.", "warning");
                exit;
            }
            try {
                $bdh = new BookingDetailHandler();
                $bdh->deleteSelection($_POST["item"]);
                echo Util::displayAlertV1($bdh->getExecutionFeedback(), "danger");
            } catch (\Throwable $e) {
                echo Util::displayAlertV1("Server error while deleting. Please try again.", "danger");
            }
        }

    } else {
        echo Util::displayAlertV1("Unauthorized.", "danger");
    }
} else {
    echo 'not allowed';
}
