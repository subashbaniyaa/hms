<?php

ob_start();
session_start();

require 'DB.php';
require 'Util.php';
require 'dao/BookingReservationDAO.php';
require 'models/Booking.php';
require 'models/Reservation.php';
require 'models/Pricing.php';
require 'models/StatusEnum.php';
require 'handlers/BookingReservationHandler.php';

if (isset($_SESSION["authenticated"]) && $_SESSION["authenticated"][1] == "false") {
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["readySubmit"])) {
        $errors_ = null;

        $startRaw = isset($_POST["start"]) ? trim($_POST["start"]) : '';
        $endRaw   = isset($_POST["end"])   ? trim($_POST["end"])   : '';

        if (empty($startRaw)) {
            $errors_ .= Util::displayAlertV1("Please select a check-in date.", "warning");
        } elseif (!DateTime::createFromFormat('Y-m-d', $startRaw)) {
            $errors_ .= Util::displayAlertV1("Invalid check-in date format.", "warning");
        }

        if (empty($endRaw)) {
            $errors_ .= Util::displayAlertV1("Please select a check-out date.", "warning");
        } elseif (!DateTime::createFromFormat('Y-m-d', $endRaw)) {
            $errors_ .= Util::displayAlertV1("Invalid check-out date format.", "warning");
        }

        if (empty($_POST["type"])) {
            $errors_ .= Util::displayAlertV1("Please select a room type.", "warning");
        }
        if (empty($_POST["adults"])) {
            $errors_ .= Util::displayAlertV1("Please enter a number of adults.", "warning");
        }

        // Date logic check — only if both dates passed individual validation
        if (empty($errors_) || (empty($startRaw) === false && empty($endRaw) === false)) {
            if (!empty($startRaw) && !empty($endRaw)) {
                try {
                    $startDate = new DateTime($startRaw);
                    $endDate   = new DateTime($endRaw);
                    if ($endDate <= $startDate) {
                        $errors_ .= Util::displayAlertV1("Check-out date must be after check-in date.", "warning");
                    }
                } catch (Exception $e) {
                    $errors_ .= Util::displayAlertV1("Invalid date value.", "warning");
                }
            }
        }

        if (!empty($errors_)) {
            echo $errors_;
        } else {
            try {
                $r = new Reservation();
                $r->setCid(Util::sanitize_xss($_POST["cid"]));
                $r->setStatus(\models\StatusEnum::PENDING_STR);
                $r->setNotes(null);
                $r->setStart(Util::sanitize_xss($startRaw));
                $r->setEnd(Util::sanitize_xss($endRaw));
                $r->setType(Util::sanitize_xss($_POST["type"]));
                $r->setRequirement(Util::sanitize_xss($_POST["requirement"]));
                $r->setAdults(Util::sanitize_xss($_POST["adults"]));
                $r->setChildren(Util::sanitize_xss($_POST["children"]));
                $r->setRequests(Util::sanitize_xss($_POST["requests"]));
                $unique = uniqid();
                $r->setHash($unique);

                $p = new Pricing();
                $p->setBookedDate(Util::sanitize_xss($_POST['bookedDate']));
                $p->setNights(Util::sanitize_xss($_POST['numNights']));
                $p->setTotalPrice(Util::sanitize_xss($_POST['totalPrice']));

                $brh = new BookingReservationHandler($r, $p);
                $temp = $brh->create();
                $out = array(
                    "success" => "true",
                    "response" => Util::displayAlertV2($brh->getExecutionFeedback(), $temp)
                );
                echo json_encode($out, JSON_PRETTY_PRINT);
            } catch (Exception $e) {
                echo json_encode([
                    "success" => "false",
                    "response" => Util::displayAlertV1("A server error occurred. Please try again later.", "danger")
                ]);
            }
        }
    }
} else {
    echo json_encode([
        "success" => "false",
        "response" => Util::displayAlertV1("You must be logged in to make a reservation.", "danger")
    ]);
}
/*
 * validation:
 * if end date is less than start date -> invalid
 * if start date, end date, room type, adults are empty -> invalid
 */
