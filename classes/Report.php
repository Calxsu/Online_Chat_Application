<?php
declare(strict_types=1);

require_once 'Database.php';

class Report extends Database {
    public function __construct() {
        parent::__construct();  // Explicitly call parent's constructor to fix argument error
    }

    public function create(string $type, int $entityId, int $reportedBy, string $reason): array {
        if (empty($reason)) {
            return ['error' => 'Reason required'];
        }
        $this->query(
            'INSERT INTO reports (report_type, entity_id, reported_by, reason) VALUES (?, ?, ?, ?)',
            [$type, $entityId, $reportedBy, $reason]
        );
        return ['success' => true];
    }

    public function getAll(): array {
        return $this->query('SELECT * FROM reports ORDER BY created_at DESC')->fetchAll();
    }
}
