<?php

ob_start();
session_start();

require '../DB.php';
require '../Util.php';
require '../models/Customer.php';
require '../dao/CustomerDAO.php';
require '../handlers/CustomerHandler.php';

if (isset($_COOKIE['is_admin']) && $_COOKIE['is_admin'] == 'true') {
    if (isset($_SESSION["isAdmin"]) && $_SESSION["isAdmin"][1] == "true") {

        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"])) {
            $action = $_POST["action"];

            // ---- DELETE CUSTOMER ----
            if ($action === "delete") {
                $cid = isset($_POST["cid"]) ? intval($_POST["cid"]) : 0;
                if ($cid <= 0) {
                    echo Util::displayAlertV1("Invalid customer ID.", "danger");
                    exit;
                }
                $sql = 'DELETE FROM `customer` WHERE `cid` = ?';
                $stmt = DB::getInstance()->prepare($sql);
                if ($stmt->execute([$cid])) {
                    echo Util::displayAlertV1("Customer has been <b>deleted</b> successfully. This page will reload to reflect changes.", "success");
                } else {
                    echo Util::displayAlertV1("Failed to delete customer. Please try again.", "danger");
                }
                exit;
            }

            // ---- UPDATE CUSTOMER ----
            if ($action === "update") {
                $cid      = isset($_POST["cid"])      ? intval($_POST["cid"])            : 0;
                $fullname = isset($_POST["fullname"]) ? trim($_POST["fullname"])          : '';
                $phone    = isset($_POST["phone"])    ? trim($_POST["phone"])             : '';

                if ($cid <= 0 || $fullname === '') {
                    echo Util::displayAlertV1("Invalid data provided.", "danger");
                    exit;
                }

                // Sanitise
                $fullname = htmlspecialchars(strip_tags($fullname));
                $phone    = htmlspecialchars(strip_tags($phone));

                $sql = 'UPDATE `customer` SET fullname = :fullname, phone = :phone WHERE cid = :cid';
                $stmt = DB::getInstance()->prepare($sql);
                if ($stmt->execute(['fullname' => $fullname, 'phone' => $phone, 'cid' => $cid])) {
                    echo Util::displayAlertV1("Customer details <b>updated</b> successfully. This page will reload to reflect changes.", "success");
                } else {
                    echo Util::displayAlertV1("Failed to update customer. Please try again.", "danger");
                }
                exit;
            }

            echo Util::displayAlertV1("Unknown action.", "danger");
        }

    } else {
        echo Util::displayAlertV1("Unauthorized.", "danger");
    }
} else {
    echo Util::displayAlertV1("Not allowed.", "danger");
}
