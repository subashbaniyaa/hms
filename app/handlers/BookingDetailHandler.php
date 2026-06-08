<?php


class BookingDetailHandler extends BookingDetailDAO
{
    public function __construct()
    {
    }


    private $executionFeedback;


    public function getExecutionFeedback()
    {
        return $this->executionFeedback;
    }


    public function setExecutionFeedback($executionFeedback)
    {
        $this->executionFeedback = $executionFeedback;
    }


    public function getAllBookings()
    {
        $bookings = $this->fetchBooking();
        // Always return array (empty if no data), never return string error
        return is_array($bookings) ? $bookings : [];
    }


    public function getCustomerBookings(Customer $c)
    {
        $bookings = $this->fetchBookingByCid($c->getId());
        
        if (is_array($bookings) && !empty($bookings)) {
            $this->setExecutionFeedback(1);
            return $bookings;
        }
        
        $this->setExecutionFeedback(0);
        return []; // Return empty array instead of 0
    }


    public function getPending()
    {
        $count = 0;
        $pending = \models\StatusEnum::PENDING_STR;
        $bookings = $this->getAllBookings();
        
        if (is_array($bookings)) {
            foreach ($bookings as $v) {
                if (($v["status"] == $pending) || (strtoupper($v["status"]) == $pending)) {
                    $count++;
                }
            }
        }
        
        return $count;
    }


    public function getConfirmed()
    {
        $count = 0;
        $confirmed = \models\StatusEnum::CONFIRMED_STR;
        $bookings = $this->getAllBookings();
        
        if (is_array($bookings)) {
            foreach ($bookings as $v) {
                if (($v["status"] == $confirmed) || (strtoupper($v["status"]) == $confirmed)) {
                    $count++;
                }
            }
        }
        
        return $count;
    }


    public function getCancelled()
    {
        $count = 0;
        $cancelled = \models\StatusEnum::CANCELLED_STR;
        $bookings = $this->getAllBookings();
        
        if (is_array($bookings)) {
            foreach ($bookings as $v) {
                if (($v["status"] == $cancelled) || (strtoupper($v["status"]) == $cancelled)) {
                    $count++;
                }
            }
        }
        
        return $count;
    }


    public function confirmSelection($item)
    {
        $alreadyConfirmed = 0;
        $successCount = 0;
        $errorCount = 0;

        for ($i = 0; $i < count($item); $i++) {
            if (is_numeric($item[$i])) {
                $currentStatus = $this->fetchStatusById($item[$i]);
                if ($currentStatus === 'CONFIRMED') {
                    $alreadyConfirmed++;
                } else {
                    if ($this->updateConfirmed($item[$i])) {
                        $successCount++;
                    } else {
                        $errorCount++;
                    }
                }
            } else {
                $this->setExecutionFeedback("Something is not right!");
                return;
            }
        }

        if ($alreadyConfirmed > 0 && $successCount === 0 && $errorCount === 0) {
            $this->setExecutionFeedback("Already confirmed. No changes were made.");
        } elseif ($successCount > 0) {
            $out = "These reservations have been successfully <b>confirmed</b>.";
            $out .= " This page will reload to reflect changes.";
            if ($alreadyConfirmed > 0) {
                $out .= " ($alreadyConfirmed reservation(s) were already confirmed and skipped.)";
            }
            $this->setExecutionFeedback($out);
        } else {
            $this->setExecutionFeedback("There must be an error processing your request. Please try again later.");
        }
    }


    public function cancelSelection($item)
    {
        $alreadyCancelled = 0;
        $successCount = 0;
        $errorCount = 0;

        for ($i = 0; $i < count($item); $i++) {
            if (is_numeric($item[$i])) {
                $currentStatus = $this->fetchStatusById($item[$i]);
                if ($currentStatus === 'CANCELLED') {
                    $alreadyCancelled++;
                } else {
                    if ($this->updateCancelled($item[$i])) {
                        $successCount++;
                    } else {
                        $errorCount++;
                    }
                }
            } else {
                $this->setExecutionFeedback("Something is not right!");
                return;
            }
        }

        if ($alreadyCancelled > 0 && $successCount === 0 && $errorCount === 0) {
            $this->setExecutionFeedback("Already cancelled. No changes were made.");
        } elseif ($successCount > 0) {
            $out = "These reservations have been successfully <b>cancelled</b>.";
            $out .= " This page will reload to reflect changes.";
            if ($alreadyCancelled > 0) {
                $out .= " ($alreadyCancelled reservation(s) were already cancelled and skipped.)";
            }
            $this->setExecutionFeedback($out);
        } else {
            $this->setExecutionFeedback("There must be an error processing your request. Please try again later.");
        }
    }


    public function deleteSelection($item)
    {
        $successCount = 0;
        $errorCount = 0;

        for ($i = 0; $i < count($item); $i++) {
            if (is_numeric($item[$i])) {
                if ($this->deleteBooking($item[$i])) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
            } else {
                $this->setExecutionFeedback("Something is not right!");
                return;
            }
        }

        if ($successCount > 0) {
            $out = "$successCount reservation(s) have been successfully <b>deleted</b>.";
            $out .= " This page will reload to reflect changes.";
            $this->setExecutionFeedback($out);
        } else {
            $this->setExecutionFeedback("There must be an error processing your request. Please try again later.");
        }
    }


    public function saveNotes($id, $notes)
    {
        return $this->updateNotes($id, $notes);
    }
}


// todo: protect booking functionalities (only admin can perform)
