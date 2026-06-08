<?php

class BookingDetailDAO
{

    public function __construct()
    {
    }

    // use this in admin.php
    protected function fetchBooking()
    {
        $sql = 'SELECT
          t1.id,
          t1.cid,
          t1.status,
          t1.notes,
          t2.start,
          t2.end,
          t2.type,
          t2.requirement,
          t2.adults,
          t2.children,
          t2.requests,
          t2.timestamp
        FROM booking AS t1 LEFT JOIN reservation AS t2 ON t1.id = t2.id;';
        $stmt = DB::getInstance()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // use in index.php
    protected function fetchBookingByCid($cid)
    {
        $sql = 'SELECT
          t1.id,
          t1.status,
          t1.notes,
          t2.start,
          t2.end,
          t2.type,
          t2.requirement,
          t2.adults,
          t2.children,
          t2.requests,
          t2.timestamp
        FROM booking AS t1 LEFT JOIN reservation AS t2 ON t1.id = t2.id
        WHERE t1.cid = ?;';
        $stmt = DB::getInstance()->prepare($sql);
        $stmt->execute([$cid]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // $i is a booking id from booking table
    protected function updateConfirmed($i)
    {
        $sql = 'UPDATE `booking` SET `status` = :status WHERE id = :id;';
        $stmt = DB::getInstance()->prepare($sql);
        $exec = $stmt->execute(["id" => $i, "status" => "CONFIRMED"]);
        return $exec;
    }

    protected function updateCancelled($i)
    {
        $sql = 'UPDATE `booking` SET `status` = :status WHERE id = :id;';
        $stmt = DB::getInstance()->prepare($sql);
        $exec = $stmt->execute(["id" => $i, "status" => "CANCELLED"]);
        return $exec;
    }

    protected function updateNotes($id, $notes)
    {
        $sql = 'UPDATE `booking` SET `notes` = :notes WHERE id = :id;';
        $stmt = DB::getInstance()->prepare($sql);
        $exec = $stmt->execute(["id" => $id, "notes" => $notes]);
        return $exec;
    }

    // Returns the current status string for a booking id, or null if not found
    protected function fetchStatusById($id)
    {
        $sql = 'SELECT `status` FROM `booking` WHERE id = ?;';
        $stmt = DB::getInstance()->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? strtoupper($row['status']) : null;
    }

    // Deletes a booking (CASCADE removes the linked reservation row too)
    protected function deleteBooking($id)
    {
        $sql = 'DELETE FROM `booking` WHERE id = ?;';
        $stmt = DB::getInstance()->prepare($sql);
        return $stmt->execute([$id]);
    }

    // proposed data manipulation function for booking status
    // example usage: updateBooking(1, true, false)
    protected function updateBooking($id, $isForConfirmation, $isForCancellation)
    {
        $sql = 'UPDATE `booking` SET `status` = :status WHERE id = :id;';
        $stmt = DB::getInstance()->prepare($sql);
        $updateStatus = [\models\StatusEnum::PENDING_STR, \models\StatusEnum::CONFIRMED_STR, \models\StatusEnum::CANCELLED_STR];
        if ($isForConfirmation) {
            $exec = $stmt->execute(["id" => $id, "status" => $updateStatus[1]]);
        } else if ($isForCancellation) {
            $exec = $stmt->execute(["id" => $id, "status" => $updateStatus[2]]);
        } else {
            $exec = $stmt->execute(["id" => $id, "status" => $updateStatus[0]]);
        }
        return $exec;
    }
}


