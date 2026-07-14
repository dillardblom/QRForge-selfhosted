<?php
require_once __DIR__.'/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class Mailer
{
    /**
     * Sends a temporary/initial password to a newly created or provisioned account.
     * Returns true on success, false on failure (never throws - callers decide how to
     * surface a mail failure without blocking the account creation itself).
     */
    public function sendInitialPassword($toEmail, $tempPassword) {
        $subject = 'Your ' . MAIL_FROM_NAME . ' account';
        $body = "An account was created for you.\n\n"
            . "Email: {$toEmail}\n"
            . "Temporary password: {$tempPassword}\n\n"
            . "You'll be asked to set a new password the first time you log in.\n\n"
            . rtrim(BASE_URL, '/') . "/login.php";

        return $this->send($toEmail, $subject, $body);
    }

    private function send($toEmail, $subject, $body) {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = MAIL_HOST;
            $mail->Port = MAIL_PORT;
            $mail->SMTPAuth = MAIL_SMTP_AUTH;

            if (MAIL_SMTP_AUTH) {
                $mail->Username = MAIL_USERNAME;
                $mail->Password = MAIL_PASSWORD;
            }

            if (MAIL_ENCRYPTION !== '') {
                $mail->SMTPSecure = MAIL_ENCRYPTION;
            }

            $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
            $mail->addAddress($toEmail);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->isHTML(false);

            $mail->send();
            return true;
        } catch (PHPMailerException $e) {
            error_log('Mailer: failed to send to ' . $toEmail . ': ' . $mail->ErrorInfo);
            return false;
        }
    }
}
