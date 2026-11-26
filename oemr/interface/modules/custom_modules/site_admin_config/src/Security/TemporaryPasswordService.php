<?php

namespace OpenEMR\Modules\SiteAdmin\Security;

use OpenEMR\Common\Session\SessionWrapperFactory;
use OpenEMR\Core\OEGlobalsBag;

class TemporaryPasswordService
{
    public const CHANGE_PATH = '/interface/modules/custom_modules/site_admin_config/public/change_temporary_password.php';

    public static function ensureSchema(): void
    {
        if (!function_exists('sqlStatement')) {
            return;
        }

        sqlStatement(
            "CREATE TABLE IF NOT EXISTS `mod_site_admin_temp_passwords` (
                `user_id` INT NOT NULL PRIMARY KEY,
                `username` VARCHAR(255) NOT NULL,
                `is_temporary` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `cleared_at` DATETIME NULL,
                `created_by` VARCHAR(255) NULL,
                KEY `idx_temp_password_username` (`username`),
                KEY `idx_temp_password_active` (`is_temporary`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }

    public static function markTemporary(int $userId, ?string $username = null, ?string $createdBy = null): void
    {
        if ($userId <= 0 || !function_exists('sqlStatement')) {
            return;
        }

        self::ensureSchema();
        $username ??= self::resolveUsername($userId);

        sqlStatement(
            "INSERT INTO `mod_site_admin_temp_passwords`
                (`user_id`, `username`, `is_temporary`, `created_at`, `cleared_at`, `created_by`)
             VALUES (?, ?, 1, NOW(), NULL, ?)
             ON DUPLICATE KEY UPDATE
                `username` = VALUES(`username`),
                `is_temporary` = 1,
                `created_at` = NOW(),
                `cleared_at` = NULL,
                `created_by` = VALUES(`created_by`)",
            [$userId, (string) $username, $createdBy]
        );
    }

    public static function clearTemporary(int $userId): void
    {
        if ($userId <= 0 || !function_exists('sqlStatement')) {
            return;
        }

        self::ensureSchema();
        sqlStatement(
            "UPDATE `mod_site_admin_temp_passwords`
             SET `is_temporary` = 0, `cleared_at` = NOW()
             WHERE `user_id` = ?",
            [$userId]
        );
    }

    public static function notifyTemporaryPassword(int $userId, string $temporaryPassword): bool
    {
        if ($userId <= 0 || $temporaryPassword === '' || !function_exists('sqlQuery')) {
            return false;
        }

        $user = sqlQuery(
            "SELECT `fname`, `lname`, `username`, `email`
             FROM `users`
             WHERE `id` = ?
             LIMIT 1",
            [$userId]
        );

        $recipient = trim((string) ($user['email'] ?? ''));
        if ($recipient === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $sender = trim((string) (
            OEGlobalsBag::getInstance()->getString('patient_reminder_sender_email')
            ?: OEGlobalsBag::getInstance()->getString('practice_return_email_path')
        ));
        if ($sender === '' || !filter_var($sender, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if (!class_exists(\MyMailer::class)) {
            $mailerPath = rtrim(OEGlobalsBag::getInstance()->getString('fileroot'), '/\\') . '/library/classes/postmaster.php';
            if (is_file($mailerPath)) {
                require_once $mailerPath;
            }
        }

        if (!class_exists(\MyMailer::class)) {
            return false;
        }

        $name = trim(($user['fname'] ?? '') . ' ' . ($user['lname'] ?? '')) ?: (string) ($user['username'] ?? '');
        $loginUrl = OEGlobalsBag::getInstance()->get('login_screen');
        $body = "Hello {$name},\n\n"
            . "Your CarelioEMR password was reset by an administrator.\n\n"
            . "Username: " . (string) ($user['username'] ?? '') . "\n"
            . "Temporary Password: {$temporaryPassword}\n\n"
            . "Sign in here: {$loginUrl}\n\n"
            . "You must change this temporary password before you can use CarelioEMR.";

        try {
            $mail = new \MyMailer();
            $mail->AddReplyTo($sender, $sender);
            $mail->SetFrom($sender, $sender);
            $mail->AddAddress($recipient, $name);
            $mail->Subject = xl('CarelioEMR Temporary Password');
            $mail->Body = $body;
            $mail->AltBody = $body;
            $mail->IsHTML(false);

            return (bool) $mail->Send();
        } catch (\Throwable) {
            return false;
        }
    }

    public static function requiresChange(int $userId): bool
    {
        if ($userId <= 0 || !function_exists('sqlQuery')) {
            return false;
        }

        self::ensureSchema();
        $row = sqlQuery(
            "SELECT `is_temporary`
             FROM `mod_site_admin_temp_passwords`
             WHERE `user_id` = ?
             LIMIT 1",
            [$userId]
        );

        return !empty($row) && (int) ($row['is_temporary'] ?? 0) === 1;
    }

    public static function enforceCurrentRequest(): void
    {
        if (PHP_SAPI === 'cli') {
            return;
        }

        $session = SessionWrapperFactory::getInstance()->getActiveSession();
        $userId = (int) ($session->get('authUserID') ?? 0);
        if ($userId <= 0 || !self::requiresChange($userId) || self::isExemptRequest()) {
            return;
        }

        $target = OEGlobalsBag::getInstance()->getWebRoot() . self::CHANGE_PATH;

        if (!headers_sent()) {
            header('Location: ' . $target);
            exit;
        }

        echo '<script>top.location.href=' . json_encode($target) . ';</script>';
        exit;
    }

    private static function isExemptRequest(): bool
    {
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $changePath = self::CHANGE_PATH;

        if (str_ends_with($scriptName, $changePath)) {
            return true;
        }

        if (
            str_ends_with($scriptName, '/interface/main/main_screen.php')
            && ($_GET['auth'] ?? '') === 'login'
            && isset($_POST['new_login_session_management'])
        ) {
            return true;
        }

        return str_contains($scriptName, '/interface/login/login.php')
            || str_contains($scriptName, '/portal/');
    }

    private static function resolveUsername(int $userId): string
    {
        if (!function_exists('sqlQuery')) {
            return '';
        }

        $row = sqlQuery("SELECT `username` FROM `users` WHERE `id` = ? LIMIT 1", [$userId]);
        return (string) ($row['username'] ?? '');
    }
}
