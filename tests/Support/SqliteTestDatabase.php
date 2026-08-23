<?php

namespace App\Tests\Support;

use PDO;

final class SqliteTestDatabase
{
    public static function env(): void
    {
        $_ENV['MATI_LAT_MIN'] = '6.89';
        $_ENV['MATI_LAT_MAX'] = '7.01';
        $_ENV['MATI_LNG_MIN'] = '126.13';
        $_ENV['MATI_LNG_MAX'] = '126.27';
        $_ENV['DEDUP_RADIUS_METERS'] = '50';
        $_ENV['DEDUP_TIME_WINDOW_HOURS'] = '24';
    }

    public static function create(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->sqliteCreateFunction('NOW', static fn (): string => date('Y-m-d H:i:s'), 0);

        foreach (self::schema() as $ddl) {
            $pdo->exec($ddl);
        }

        return $pdo;
    }

    private static function schema(): array
    {
        return [
            "CREATE TABLE users (
                id TEXT PRIMARY KEY,
                full_name TEXT NOT NULL DEFAULT '',
                email TEXT UNIQUE,
                password_hash TEXT,
                auth_provider TEXT NOT NULL DEFAULT 'native',
                google_id TEXT,
                phone_number TEXT,
                address TEXT,
                role TEXT NOT NULL,
                account_status TEXT NOT NULL DEFAULT 'active',
                profile_photo_url TEXT,
                created_at TEXT NOT NULL DEFAULT (datetime('now')),
                updated_at TEXT NOT NULL DEFAULT (datetime('now'))
            )",
            "CREATE TABLE reports (
                id TEXT PRIMARY KEY,
                resident_id TEXT NOT NULL,
                animal_description TEXT NOT NULL,
                photo_urls TEXT,
                latitude TEXT NOT NULL,
                longitude TEXT NOT NULL,
                address_text TEXT,
                content_hash TEXT NOT NULL,
                duplicate_of_report_id TEXT,
                validation_status TEXT NOT NULL DEFAULT 'pending',
                status TEXT NOT NULL DEFAULT 'pending_verification',
                dismiss_reason TEXT,
                verified_by TEXT,
                verified_at TEXT,
                created_at TEXT NOT NULL DEFAULT (datetime('now'))
            )",
            "CREATE TABLE cases (
                id TEXT PRIMARY KEY,
                report_id TEXT NOT NULL,
                assigned_rescuer_id TEXT,
                assigned_by TEXT,
                status TEXT NOT NULL DEFAULT 'assigned',
                resolution_notes TEXT,
                resolution_photos TEXT,
                created_at TEXT NOT NULL DEFAULT (datetime('now')),
                updated_at TEXT NOT NULL DEFAULT (datetime('now'))
            )",
            "CREATE TABLE case_activity_log (
                id TEXT PRIMARY KEY,
                case_id TEXT NOT NULL,
                actor_id TEXT NOT NULL,
                actor_role TEXT NOT NULL,
                action TEXT NOT NULL,
                notes TEXT,
                created_at TEXT NOT NULL DEFAULT (datetime('now'))
            )",
            "CREATE TABLE rescuer_duty_status (
                id TEXT PRIMARY KEY,
                user_id TEXT NOT NULL UNIQUE,
                status TEXT NOT NULL DEFAULT 'off_duty',
                updated_at TEXT NOT NULL DEFAULT (datetime('now'))
            )",
            "CREATE TABLE notifications (
                id TEXT PRIMARY KEY,
                user_id TEXT NOT NULL,
                type TEXT NOT NULL,
                message TEXT NOT NULL,
                related_type TEXT,
                related_id TEXT,
                is_read INTEGER NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL DEFAULT (datetime('now'))
            )",
            "CREATE TABLE animals (
                id TEXT PRIMARY KEY,
                name TEXT,
                species TEXT NOT NULL,
                breed_type TEXT NOT NULL,
                sex TEXT NOT NULL,
                age_estimate TEXT,
                birth_date TEXT,
                color_markings TEXT,
                barangay TEXT,
                description TEXT,
                photo_urls TEXT,
                model_3d_url TEXT,
                photo_360_set TEXT,
                adoption_status TEXT NOT NULL DEFAULT 'not_listed',
                source TEXT NOT NULL DEFAULT 'rescued_case',
                created_by TEXT,
                deleted_at TEXT,
                created_at TEXT NOT NULL DEFAULT (datetime('now')),
                updated_at TEXT NOT NULL DEFAULT (datetime('now'))
            )",
            "CREATE TABLE adoptions (
                id TEXT PRIMARY KEY,
                animal_id TEXT NOT NULL,
                applicant_id TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'pending',
                rejection_reason TEXT,
                reviewed_by TEXT,
                reviewed_at TEXT,
                completed_at TEXT,
                created_at TEXT NOT NULL DEFAULT (datetime('now'))
            )",
        ];
    }
}
