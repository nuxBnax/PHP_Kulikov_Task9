<?php

namespace Geekbrains\Application1\Domain\Controllers;

use Geekbrains\Application1\Application\Application;
use Geekbrains\Application1\Domain\Models\User;

class AbstractController extends Controller {

    protected array $actionsPermissions = [];
    
    public function getUserRoles(): array{
        $roles = [];
        $roles[] = 'user';
      
        $roles = User::getUserRoleById($roles);
        return $roles;
    }

    public function getActionsPermissions(string $methodName): array {
        return $this->actionsPermissions[$methodName] ?? [];
    }
}