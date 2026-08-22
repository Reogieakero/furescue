<?php

namespace App\Entity;

class User
{
    private ?string $id;
    private ?string $fullName;
    private ?string $email;
    private ?string $passwordHash;
    private ?string $authProvider;
    private ?string $googleId;
    private ?string $phoneNumber;
    private ?string $address;
    private ?string $role;
    private ?string $accountStatus;
    private ?string $profilePhotoUrl;
    private ?string $createdAt;
    private ?string $updatedAt;

    private function __construct()
    {
    }

    public static function fromRow(array $row): self
    {
        $user = new self();
        $user->id = $row['id'] ?? null;
        $user->fullName = $row['full_name'] ?? null;
        $user->email = $row['email'] ?? null;
        $user->passwordHash = $row['password_hash'] ?? null;
        $user->authProvider = $row['auth_provider'] ?? null;
        $user->googleId = $row['google_id'] ?? null;
        $user->phoneNumber = $row['phone_number'] ?? null;
        $user->address = $row['address'] ?? null;
        $user->role = $row['role'] ?? null;
        $user->accountStatus = $row['account_status'] ?? null;
        $user->profilePhotoUrl = $row['profile_photo_url'] ?? null;
        $user->createdAt = $row['created_at'] ?? null;
        $user->updatedAt = $row['updated_at'] ?? null;
        return $user;
    }

    public function id(): ?string
    {
        return $this->id;
    }

    public function fullName(): ?string
    {
        return $this->fullName;
    }

    public function email(): ?string
    {
        return $this->email;
    }

    public function passwordHash(): ?string
    {
        return $this->passwordHash;
    }

    public function authProvider(): ?string
    {
        return $this->authProvider;
    }

    public function googleId(): ?string
    {
        return $this->googleId;
    }

    public function phoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function address(): ?string
    {
        return $this->address;
    }

    public function role(): ?string
    {
        return $this->role;
    }

    public function accountStatus(): ?string
    {
        return $this->accountStatus;
    }

    public function profilePhotoUrl(): ?string
    {
        return $this->profilePhotoUrl;
    }

    public function createdAt(): ?string
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->fullName,
            'email' => $this->email,
            'auth_provider' => $this->authProvider,
            'google_id' => $this->googleId,
            'phone_number' => $this->phoneNumber,
            'address' => $this->address,
            'role' => $this->role,
            'account_status' => $this->accountStatus,
            'profile_photo_url' => $this->profilePhotoUrl,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
