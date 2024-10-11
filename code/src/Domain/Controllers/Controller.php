<?php

namespace Geekbrains\Application1\Domain\Controllers;

// use Geekbrains\Application1\Application\Application;

class Controller 
{
	 public function __construct()
	{
		if (!isset($_SESSION['counter'])) {
			$_SESSION['counter'] = "";
		} 
		$_SESSION['counter']++;
	}
};