<?php /** @noinspection PhpUnusedParameterInspection */

namespace app\models\layoutHooks;

use app\models\db\{Amendment, Consultation, ConsultationMotionType, ISupporter, Motion, Site};

class Hooks
{
    /** @var \app\models\settings\Layout */
    protected $layout;

    /** @var Consultation|null */
    protected $consultation;

    public function __construct(\app\models\settings\Layout $layout, ?Consultation $consultation)
    {
        $this->layout = $layout;
        $this->consultation = $consultation;
    }

    public function beforePage(string $before): string
    {
        return $before;
    }

    public function beginPage(string $before): string
    {
        return $before;
    }

    public function logoRow(string $before): string
    {
        return $before;
    }

    public function favicons(string $before): string
    {
        return $before;
    }

    public function endOfHead(string $before): string
    {
        return $before;
    }

    public function beforeContent(string $before): string
    {
        return $before;
    }

    public function beginContent(string $before): string
    {
        return $before;
    }

    public function afterContent(string $before): string
    {
        return $before;
    }

    public function endPage(string $before): string
    {
        return $before;
    }

    public function renderSidebar(string $before): string
    {
        return $before;
    }

    public function getSearchForm(string $before): string
    {
        return $before;
    }

    public function getAntragsgruenAd(string $before): string
    {
        return $before;
    }

    /**
     * @param string $before
     * @param ConsultationMotionType[] $motionTypes
     * @return string
     */
    public function setSidebarCreateMotionButton($before, $motionTypes)
    {
        return $before;
    }

    public function getStdNavbarHeader(string $before): string
    {
        return $before;
    }

    public function breadcrumbs(string $before): string
    {
        return $before;
    }

    public function footerLine(string $before): string
    {
        return $before;
    }

    public function beforeMotionView(string $before, Motion $motion): string
    {
        return $before;
    }

    public function afterMotionView(string $before, Motion $motion): string
    {
        return $before;
    }

    public function getMotionViewData(array $motionData, Motion $motion): array
    {
        return $motionData;
    }

    public function getFormattedMotionStatus(string $before, Motion $motion): string
    {
        return $before;
    }

    public function getFormattedAmendmentStatus(string $before, Amendment $amendment): string
    {
        return $before;
    }

    public function getConsultationMotionLineContent(string $before, Motion $motion): string
    {
        return $before;
    }

    public function getConsultationAmendmentLineContent(string $before, Amendment $amendment): string
    {
        return $before;
    }

    public function getMotionDetailsInitiatorName(string $before, ISupporter $supporter): string
    {
        return $before;
    }

    public function getAdminIndexHint(string $before, Consultation $consultation): string
    {
        return $before;
    }

    /**
     * @param string[] $before
     * @param Site $site
     * @return string[]
     */
    public function getSitewidePublicWarnings($before, Site $site)
    {
        return $before;
    }
}
