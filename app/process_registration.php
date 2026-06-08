<?php

require '../lib/phpPasswordHashing/passwordLib.php';

require 'DB.php';
require 'Util.php';
require 'dao/CustomerDAO.php';
require 'models/Customer.php';
require 'handlers/CustomerHandler.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submitBtn"])) {
    $errors_ = null;

    $fullName    = isset($_POST["fullName"])     ? trim($_POST["fullName"])     : '';
    $email       = isset($_POST["email"])        ? trim($_POST["email"])        : '';
    $phone       = isset($_POST["phoneNumber"])  ? trim($_POST["phoneNumber"])  : '';
    $password    = isset($_POST["password"])     ? $_POST["password"]           : '';
    $password2   = isset($_POST["password2"])    ? $_POST["password2"]          : '';

    if (empty($fullName)) {
        $errors_ .= Util::displayAlertV1("Full name is required.", "warning");
    }

    if (empty($email)) {
        $errors_ .= Util::displayAlertV1("Email address is required.", "warning");
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors_ .= Util::displayAlertV1("Please enter a valid email address.", "warning");
    }

    if (empty($password)) {
        $errors_ .= Util::displayAlertV1("Password is required.", "warning");
    } elseif (strlen($password) < 4) {
        $errors_ .= Util::displayAlertV1("Password must be at least 4 characters.", "warning");
    }

    if (empty($password2)) {
        $errors_ .= Util::displayAlertV1("Please confirm your password.", "warning");
    } elseif (!empty($password) && $password !== $password2) {
        $errors_ .= Util::displayAlertV1("Passwords do not match.", "warning");
    }

    if (!empty($errors_)) {
        echo $errors_;
    } else {
        try {
            $customer = new Customer();
            $customer->setFullName(Util::sanitize_xss($fullName));
            $customer->setEmail(Util::sanitize_xss($email));
            $customer->setPhone(Util::sanitize_xss($phone));
            // Hash the password with bcrypt before storing
            $customer->setPassword(password_hash($password, PASSWORD_BCRYPT));

            $handler = new CustomerHandler();
            $handler->insertCustomer($customer);
            echo Util::displayAlertV1($handler->getExecutionFeedback(), "info");
        } catch (Exception $e) {
            echo Util::displayAlertV1("A server error occurred. Please try again later.", "danger");
        }
    }
}

/**
 * [x] validate all fields first (name, email, password, confirm password)
 * [x] if no error create a Customer object
 * [x] check if email already exists
 *     if not exists insert the customer object (with hashed password)
 *     otherwise, display email exists message
 */
