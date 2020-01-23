<?php


class User {

    private $conn = null;

    function __contruct ($conn)
    {
        $this->conn = $conn;
    }

    
}