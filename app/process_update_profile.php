<?php

ob_start();
session_start();

require '../lib/phpPasswordHashing/passwordLib.php';
require 'DB.php';
require 'Util.php';
require 'dao/CustomerDAO.php';
require 'models/Customer.php';
require 'handlers/CustomerHandler.php';

if (isset($_SESSION["authenticated"]) && $_SESSION["authenticated"][1] == "false") {

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submitBtn"])) {
        $errors_ = null;

        $fullName = isset($_POST["fullName"]) ? trim($_POST["fullName"]) : '';
        $phone    = isset($_POST["phone"])    ? trim($_POST["phone"])    : '';
        $email    = isset($_POST["email"])    ? trim($_POST["email"])    : '';
        $cid      = isset($_POST["cid"])      ? intval($_POST["cid"])   : 0;
        $newPwd   = isset($_POST["newPassword"]) ? $_POST["newPassword"] : '';

        if (empty($fullName)) {
            $errors_ .= Util::displayAlertV1("Full name cannot be empty.", "warning");
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors_ .= Util::displayAlertV1("A valid email address is required.", "warning");
        }

        if ($cid <= 0) {
            $errors_ .= Util::displayAlertV1("Invalid session. Please log in again.", "danger");
        }

        $pwd = null;
        if (!empty($newPwd)) {
            if (strlen($newPwd) < 4) {
                $errors_ .= Util::displayAlertV1("New password must be at least 4 characters.", "warning");
            } else {
                // Hash the new password with bcrypt
                $pwd = password_hash($newPwd, PASSWORD_BCRYPT);
            }
        }
        // If newPassword is empty, $pwd stays null — the DAO skips updating the password column

        if (!empty($errors_)) {
            echo json_encode(["success" => "false", "response" => $errors_]);
        } else {
            try {
                if (isset($_SESSION["authenticated"]) && $_SESSION["authenticated"][1] == "false" &&
                    isset($_COOKIE['is_admin']) && $_COOKIE['is_admin'] == "false") {

                    $c = new Customer();
                    $c->setId(Util::sanitize_xss((string)$cid));
                    $c->setFullName(Util::sanitize_xss($fullName));
                    $c->setPhone(Util::sanitize_xss($phone));
                    $c->setEmail(Util::sanitize_xss($email));
                    $c->setPassword($pwd);

                    $cHandler = new CustomerHandler();
                    $cHandler->updateCustomer($c);
                    $feedback = $cHandler->getExecutionFeedback();

                    // Update session values to reflect changes
                    if (isset($_SESSION["username"])) {
                        $_SESSION["username"] = $cHandler->getUsername($email);
                    }
                    if (isset($_SESSION["phoneNumber"])) {
                        $_SESSION["phoneNumber"] = $phone;
                    }

                    echo json_encode(["success" => "true", "response" => Util::displayAlertV1($feedback, "success")]);
                } else {
                    echo json_encode(["success" => "false", "response" => Util::displayAlertV1("Unauthorized request.", "danger")]);
                }
            } catch (Exception $e) {
                echo json_encode(["success" => "false", "response" => Util::displayAlertV1("A server error occurred. Please try again later.", "danger")]);
            }
        }
    }

} else {
    echo json_encode(["success" => "false", "response" => Util::displayAlertV1("Session expired. Please log in again.", "danger")]);
}
