<?php

// this script acts like a controller
// to process a login a JS request from form-submission.js is sent to this script
// view -> js -> process_login -> server
// view <- js <- process_login <- server
// other process scripts follow the same cycle

ob_start();
session_start();

// include this for every Customer model existence
require '../lib/phpPasswordHashing/passwordLib.php';

require 'DB.php';
require 'Util.php';
require 'dao/CustomerDAO.php';
require 'dao/AdminDAO.php';
require 'models/Customer.php';
require 'models/Admin.php';
require 'handlers/CustomerHandler.php';
require 'handlers/AdminHandler.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submitBtn"])) {
    $errors_ = null;

    $emailInput = isset($_POST["email"]) ? trim($_POST["email"]) : '';
    $passwordInput = isset($_POST["password"]) ? $_POST["password"] : '';

    if (empty($emailInput)) {
        $errors_ .= Util::displayAlertV1("Email address is required.", "warning");
    } elseif (!filter_var($emailInput, FILTER_VALIDATE_EMAIL)) {
        $errors_ .= Util::displayAlertV1("Please enter a valid email address.", "warning");
    }

    if (empty($passwordInput)) {
        $errors_ .= Util::displayAlertV1("Password is required.", "warning");
    }

    if (!empty($errors_)) {
        echo $errors_;
    } else {
        try {
            $handler = new CustomerHandler();
            $customer = new Customer();
            $customer->setEmail($emailInput);

            $isAdmin = $handler->handleIsAdmin($emailInput);

            if (!$handler->isPasswordMatchWithEmail($passwordInput, $customer)) {
                echo Util::displayAlertV1("Invalid email or password. Please try again.", "danger");
            } else {
                if ($isAdmin) {
                    $_SESSION["username"] = $emailInput;
                    $_SESSION["accountEmail"] = $emailInput;
                    $_SESSION["isAdmin"] = [1, "true"];
                    echo json_encode($_SESSION["isAdmin"]);
                } else {
                    $_SESSION["username"] = $handler->getUsername($emailInput);
                    $_SESSION["accountEmail"] = $customer->getEmail();
                    $_SESSION["authenticated"] = [1, "false"];
                    // Do NOT store plain-text password in session

                    // set the session phone number too
                    $customerObj = $handler->getCustomerObj($emailInput);
                    if ($customerObj->getPhone()) {
                        $_SESSION["phoneNumber"] = $customerObj->getPhone();
                    }
                    echo json_encode($_SESSION["authenticated"]);
                }
            }
        } catch (Exception $e) {
            echo Util::displayAlertV1("A server error occurred. Please try again later.", "danger");
        }
    }
}

/**
 * [x] validate the fields first
 * [x] if no errors check if email is registered
 *     if not registered, display not registered message
 *     otherwise, create a customer object
 * [x] check if password entered match with db password
 *     if not match display incorrect message
 *     otherwise, create a session variables
 */
