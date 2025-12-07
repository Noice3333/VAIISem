<?php

namespace App\Models;

use Framework\Core\Model;
use Framework\Core\IIdentity;

/**
 * Simple User value object representing an authenticated user.
 */
class User extends Model implements IIdentity
{
    protected ?int $id = null;
    protected ?string $username = null;
    protected ?string $password = null;
    protected ?string $name = null;


    public function __construct(
        $id = null,
        public string $login = '',
        $name = ''
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): void
    {
        $this->username = $username;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }


}
