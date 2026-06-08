<?php

class BookingReservationDAO
{
    protected function insert(Reservation $r, Pricing $p)
    {
        $db = DB::getInstance();

        $db->beginTransaction();
        try {
            // 1. Insert into booking
            $stmt = $db->prepare('INSERT INTO `booking` (`cid`, `status`, `notes`) VALUES (?, ?, ?)');
            $stmt->execute([$r->getCid(), $r->getStatus(), $r->getNotes()]);
            $lastInsertedBookId = $db->lastInsertId();

            // 2. Insert into reservation (linked to booking via shared id)
            $stmt2 = $db->prepare(
                'INSERT INTO `reservation`
                    (`id`, `start`, `end`, `type`, `requirement`, `adults`, `children`, `requests`, `hash`)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $resOk = $stmt2->execute([
                $lastInsertedBookId,
                $r->getStart(),
                $r->getEnd(),
                $r->getType(),
                $r->getRequirement(),
                $r->getAdults(),
                $r->getChildren(),
                $r->getRequests(),
                $r->getHash()
            ]);

            // 3. Insert into pricing
            $stmt3 = $db->prepare(
                'INSERT INTO `pricing` (`booking_id`, `nights`, `total_price`, `booked_date`)
                 VALUES (?, ?, ?, ?)'
            );
            $priceOk = $stmt3->execute([
                $lastInsertedBookId,
                $p->getNights(),
                $p->getTotalPrice(),
                $p->getBookedDate()
            ]);

            if ($resOk && $priceOk) {
                $db->commit();
                return true;
            }

            $db->rollBack();
            return false;

        } catch (\Throwable $e) {
            $db->rollBack();
            return false;
        }
    }

    protected function update(Reservation $r)
    {
        $sql = 'UPDATE `reservation`
                SET `start`       = ?,
                    `end`         = ?,
                    `type`        = ?,
                    `requirement` = ?,
                    `adults`      = ?,
                    `children`    = ?,
                    `requests`    = ?,
                    `hash`        = ?
                WHERE `id` = ?';
        $stmt = DB::getInstance()->prepare($sql);
        return $stmt->execute([
            $r->getStart(),
            $r->getEnd(),
            $r->getType(),
            $r->getRequirement(),
            $r->getAdults(),
            $r->getChildren(),
            $r->getRequests(),
            $r->getHash(),
            $r->getId()
        ]);
    }

    protected function delete(Reservation $r)
    {
        $sql = 'DELETE FROM `booking` WHERE `booking`.`id` = ? AND `booking`.`cid` = ?';
        $stmt = DB::getInstance()->prepare($sql);
        $stmt->execute([$r->getId(), $r->getCid()]);
        return $stmt->rowCount();
    }
}
