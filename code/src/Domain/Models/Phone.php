<?php

namespace Geekbrains\Application1\Domain\Models;

class Phone
{
    private string $phone;
    public function __construct()
    {
//    $this->phone = '-7154816344';
    }

    public function getPhone(): string{
        // var_dump($_GET); // вытаскивает данные из url
        $this->phone = $_GET['phone'] ?? ''; // ?? тоже самое что isset()
        return $this->phone;
    }
}