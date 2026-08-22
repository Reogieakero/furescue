<?php

namespace App\Entity;

class RescueCase
{
    private ?string $id;
    private ?string $reportId;
    private ?string $assignedRescuerId;
    private ?string $assignedBy;
    private ?string $status;
    private ?string $resolutionNotes;
    private ?string $createdAt;
    private ?string $updatedAt;
    private ?string $resolutionPhotos;

    private function __construct()
    {
    }

    public static function fromRow(array $row): self
    {
        $case = new self();
        $case->id = $row['id'] ?? null;
        $case->reportId = $row['report_id'] ?? null;
        $case->assignedRescuerId = $row['assigned_rescuer_id'] ?? null;
        $case->assignedBy = $row['assigned_by'] ?? null;
        $case->status = $row['status'] ?? null;
        $case->resolutionNotes = $row['resolution_notes'] ?? null;
        $case->createdAt = $row['created_at'] ?? null;
        $case->updatedAt = $row['updated_at'] ?? null;
        $case->resolutionPhotos = $row['resolution_photos'] ?? null;
        return $case;
    }

    public function id(): ?string
    {
        return $this->id;
    }

    public function reportId(): ?string
    {
        return $this->reportId;
    }

    public function assignedRescuerId(): ?string
    {
        return $this->assignedRescuerId;
    }

    public function assignedBy(): ?string
    {
        return $this->assignedBy;
    }

    public function status(): ?string
    {
        return $this->status;
    }

    public function resolutionNotes(): ?string
    {
        return $this->resolutionNotes;
    }

    public function createdAt(): ?string
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function resolutionPhotos(): ?string
    {
        return $this->resolutionPhotos;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'report_id' => $this->reportId,
            'assigned_rescuer_id' => $this->assignedRescuerId,
            'assigned_by' => $this->assignedBy,
            'status' => $this->status,
            'resolution_notes' => $this->resolutionNotes,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'resolution_photos' => $this->resolutionPhotos,
        ];
    }
}
