<?php

namespace app\plugins\member_petitions;

use app\models\settings\Consultation;

class ConsultationSettings extends Consultation
{
    public $organizationId = '';
    public $replyDeadline = 14;
    public $minDiscussionTime = 21;
    public $maxOverallTime = 0;
    public $petitionPage = true;

    public function getStartLayoutView(): string
    {
        if ($this->petitionPage) {
            return '@app/plugins/member_petitions/views/consultation';
        } else {
            return '@app/views/consultation/index_layout_std';
        }
    }

    public function getConsultationSidebar(): ?string
    {
        if ($this->petitionPage) {
            return '@app/plugins/member_petitions/views/consultation-sidebar';
        } else {
            return '@app/views/consultation/sidebar';
        }
    }

    /**
     * @return null|string|LayoutSettings
     */
    public function getSpecializedLayoutClass(): ?string
    {
        return LayoutSettings::class;
    }
}
