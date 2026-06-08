<?php

class Customer
{
    private $cid;
    private $fullname;
    private $email;
    private $password;
    private $phone;

    public function __construct()
    {
    }

    public function getId()
    {
        return $this->cid;
    }

    public function setId($id)
    {
        $this->cid = $id;
    }

    public function getFullName()
    {
        return $this->fullname;
    }

    public function setFullName($fullName)
    {
        $this->fullname = $fullName;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Stores the password value as-is.
     * Hashing is the responsibility of the caller (process scripts).
     * Do NOT hash here — getCustomerObj() passes in an already-hashed DB value.
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function getPhone()
    {
        return $this->phone;
    }

    public function setPhone($phone)
    {
        $this->phone = $phone;
    }
}
