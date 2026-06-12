<?php
/**
 * Settings Model
 * Handles application settings stored in database
 */
class Settings {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Fetch settings row (single-row table). Returns associative array or empty array.
     */
    public function getSettings(): array {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM settings LIMIT 1");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: [];
        } catch (PDOException $e) {
            // Graceful fallback if table is missing; caller can still render defaults
            error_log('[Settings] getSettings error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Upsert settings row. Accepts sanitized values array.
     */
    public function saveSettings(array $data): bool {
        try {
            // Ensure a single row exists; use INSERT ... ON DUPLICATE KEY UPDATE with primary key 1
            $sql = "
                INSERT INTO settings (
                    id, site_name, site_logo_path, contact_email, maintenance_mode,
                    recaptcha_site_key, recaptcha_secret_key, session_timeout_minutes,
                    email_notifications_enabled, email_sender_name, email_sender_email,
                    email_template_welcome, email_template_donation, updated_by
                ) VALUES (
                    1, :site_name, :site_logo_path, :contact_email, :maintenance_mode,
                    :recaptcha_site_key, :recaptcha_secret_key, :session_timeout_minutes,
                    :email_notifications_enabled, :email_sender_name, :email_sender_email,
                    :email_template_welcome, :email_template_donation, :updated_by
                )
                ON DUPLICATE KEY UPDATE
                    site_name = VALUES(site_name),
                    site_logo_path = VALUES(site_logo_path),
                    contact_email = VALUES(contact_email),
                    maintenance_mode = VALUES(maintenance_mode),
                    recaptcha_site_key = VALUES(recaptcha_site_key),
                    recaptcha_secret_key = VALUES(recaptcha_secret_key),
                    session_timeout_minutes = VALUES(session_timeout_minutes),
                    email_notifications_enabled = VALUES(email_notifications_enabled),
                    email_sender_name = VALUES(email_sender_name),
                    email_sender_email = VALUES(email_sender_email),
                    email_template_welcome = VALUES(email_template_welcome),
                    email_template_donation = VALUES(email_template_donation),
                    updated_by = VALUES(updated_by),
                    updated_at = NOW()
            ";

            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':site_name' => $data['site_name'] ?? '',
                ':site_logo_path' => $data['site_logo_path'] ?? null,
                ':contact_email' => $data['contact_email'] ?? null,
                ':maintenance_mode' => $data['maintenance_mode'] ?? 0,
                ':recaptcha_site_key' => $data['recaptcha_site_key'] ?? null,
                ':recaptcha_secret_key' => $data['recaptcha_secret_key'] ?? null,
                ':session_timeout_minutes' => $data['session_timeout_minutes'] ?? 30,
                ':email_notifications_enabled' => $data['email_notifications_enabled'] ?? 0,
                ':email_sender_name' => $data['email_sender_name'] ?? null,
                ':email_sender_email' => $data['email_sender_email'] ?? null,
                ':email_template_welcome' => $data['email_template_welcome'] ?? null,
                ':email_template_donation' => $data['email_template_donation'] ?? null,
                ':updated_by' => $data['updated_by'] ?? null,
            ]);
        } catch (PDOException $e) {
            error_log('[Settings] saveSettings error: ' . $e->getMessage());
            return false;
        }
    }
}

















