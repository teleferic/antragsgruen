<?php

namespace app\components\mail;

use app\models\db\{Consultation, EMailLog};
use app\models\exceptions\{MailNotSent, ServerConfiguration};

class Tools
{
    public static function getDefaultMailFromName(?Consultation $consultation = null): string
    {
        /** @var \app\models\settings\AntragsgruenApp $params */
        $params = \Yii::$app->params;
        $name   = $params->mailFromName;
        if ($consultation && $consultation->getSettings()->emailFromName) {
            $name = $consultation->getSettings()->emailFromName;
        }
        return $name;
    }

    public static function getDefaultReplyTo(?Consultation $consultation = null): ?string
    {
        /** @var \app\models\settings\AntragsgruenApp $params */
        $params = \Yii::$app->params;

        $replyTo = null;
        if ($consultation) {
            if ($consultation->getSettings()->emailReplyTo) {
                $replyTo = $consultation->getSettings()->emailReplyTo;
            } elseif ($params->multisiteMode && $consultation->adminEmail) {
                $adminEmails = $consultation->getAdminEmails();
                if (count($adminEmails) > 0) {
                    $replyTo = $adminEmails[0];
                }
            }
        }
        return $replyTo;
    }

    /**
     * @param int $mailType
     * @param Consultation|null $fromConsultation
     * @param string $toEmail
     * @param null|int $toPersonId
     * @param string $subject
     * @param string $textPlain
     * @param string $textHtml
     * @param null|array $noLogReplaces
     * @param string|null $fromName
     * @param string|null $replyTo
     * @throws MailNotSent
     * @throws ServerConfiguration
     */
    public static function sendWithLog(
        $mailType,
        $fromConsultation,
        $toEmail,
        $toPersonId,
        $subject,
        $textPlain,
        $textHtml = '',
        $noLogReplaces = null,
        $fromName = null,
        $replyTo = null
    ) {
        /** @var \app\models\settings\AntragsgruenApp $params */
        $params = \Yii::$app->params;
        $mailer = Base::createMailer($params->mailService);
        if (!$mailer) {
            throw new MailNotSent('E-Mail not configured');
        }

        $sendTextPlain = ($noLogReplaces ? str_replace(
            array_keys($noLogReplaces),
            array_values($noLogReplaces),
            $textPlain
        ) : $textPlain);
        $sendTextHtml  = ($noLogReplaces ? str_replace(
            array_keys($noLogReplaces),
            array_values($noLogReplaces),
            $textHtml
        ) : $textHtml);

        $fromEmail = $params->mailFromEmail;
        if (!$fromName) {
            $fromName = static::getDefaultMailFromName($fromConsultation);
        }
        if (!$replyTo) {
            $replyTo = static::getDefaultReplyTo($fromConsultation);
        }

        $messageId = explode('@', $fromEmail);
        if (count($messageId) === 2) {
            $messageId = uniqid() . '@' . $messageId[1];
        } else {
            $messageId = uniqid() . '@antragsgruen.de';
        }

        $exception = null;
        try {
            $message = $mailer->createMessage(
                $mailType,
                $subject,
                $sendTextPlain,
                $sendTextHtml,
                $fromName,
                $fromEmail,
                $replyTo,
                $messageId,
                $fromConsultation
            );
            $status  = $mailer->send($message, $toEmail);
        } catch (\Exception $e) {
            $status    = EMailLog::STATUS_DELIVERY_ERROR;
            $exception = $e;
        }

        $obj = new EMailLog();
        if ($toPersonId) {
            $obj->toUserId = $toPersonId;
        }
        if ($fromConsultation) {
            $obj->fromSiteId = $fromConsultation->siteId;
        }
        $obj->toEmail   = $toEmail;
        $obj->type      = $mailType;
        $obj->fromEmail = mb_encode_mimeheader($fromName) . ' <' . $fromEmail . '>';
        $obj->subject   = mb_substr($subject, 0, 190);
        $obj->text      = $textPlain;
        $obj->dateSent  = date('Y-m-d H:i:s');
        $obj->status    = $status;
        $obj->messageId = $messageId;
        $obj->save();

        if ($exception) {
            \Yii::error($exception->getMessage());
            /** @var \Exception $exception */
            throw new MailNotSent($exception->getMessage());
        }

        if (YII_ENV === 'test') {
            $pre = \yii::$app->session->getFlash('email', '');
            \yii::$app->session->setFlash('email', $pre . 'E-Mail sent to: ' . $toEmail . "\n");
        }
    }
}
