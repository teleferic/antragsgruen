<?php

namespace app\models\layoutHooks;

use app\models\db\{Amendment, Consultation, ConsultationMotionType, ISupporter, Motion, Site};

class Layout
{
    /** @var Hooks[] */
    private static $hooks = [];

    public static function addHook(Hooks $hook): void
    {
        if (!in_array($hook, static::$hooks)) {
            static::$hooks[] = $hook;
        }
    }

    /**
     * @param string $name
     * @param mixed[] $args
     * @param mixed $initValue
     * @return mixed
     */
    private static function callHook($name, $args = [], $initValue = '')
    {
        $out = $initValue;
        foreach (static::$hooks as $hook) {
            $callArgs = array_merge([$out], $args);
            $out      = call_user_func_array([$hook, $name], $callArgs);
        }
        return $out;
    }

    public static function beforePage(): string
    {
        return static::callHook('beforePage');
    }

    public static function beginPage(): string
    {
        return static::callHook('beginPage');
    }

    public static function favicons(): string
    {
        return static::callHook('favicons');
    }

    public static function endOfHead(?Consultation $consultation): string
    {
        return static::callHook('endOfHead', [$consultation]);
    }

    public static function logoRow(): string
    {
        return static::callHook('logoRow');
    }

    public static function beforeContent(): string
    {
        return static::callHook('beforeContent');
    }

    public static function afterContent(): string
    {
        return static::callHook('afterContent');
    }

    public static function beginContent(): string
    {
        return static::callHook('beginContent');
    }

    public static function endPage(): string
    {
        return static::callHook('endPage');
    }

    public static function renderSidebar(): string
    {
        return static::callHook('renderSidebar');
    }

    public static function getSearchForm(): string
    {
        return static::callHook('getSearchForm');
    }

    public static function getAntragsgruenAd(): string
    {
        return static::callHook('getAntragsgruenAd');
    }

    /**
     * @param ConsultationMotionType[] $motionTypes
     * @return string
     */
    public static function setSidebarCreateMotionButton($motionTypes): string
    {
        return static::callHook('setSidebarCreateMotionButton', [$motionTypes]);
    }

    public static function getStdNavbarHeader(): string
    {
        return static::callHook('getStdNavbarHeader');
    }

    public static function footerLine(): string
    {
        return static::callHook('footerLine');
    }

    public static function breadcrumbs(): string
    {
        return static::callHook('breadcrumbs');
    }

    public static function beforeMotionView(Motion $motion): string
    {
        return static::callHook('beforeMotionView', [$motion]);
    }

    public static function afterMotionView(Motion $motion): string
    {
        return static::callHook('afterMotionView', [$motion]);
    }

    public static function getMotionViewData(array $motionData, Motion $motion): array
    {
        return static::callHook('getMotionViewData', [$motion], $motionData);
    }

    public static function getFormattedMotionStatus(string $origStatus, Motion $motion): string
    {
        return static::callHook('getFormattedMotionStatus', [$motion], $origStatus);
    }

    public static function getFormattedAmendmentStatus(string $origStatus, Amendment $amendment): string
    {
        return static::callHook('getFormattedAmendmentStatus', [$amendment], $origStatus);
    }

    public static function getConsultationMotionLineContent(string $origLine, Motion $motion): string
    {
        return static::callHook('getConsultationMotionLineContent', [$motion], $origLine);
    }

    public static function getConsultationAmendmentLineContent(string $origLine, Amendment $amendment): string
    {
        return static::callHook('getConsultationAmendmentLineContent', [$amendment], $origLine);
    }

    public static function getMotionDetailsInitiatorName(string $origLine, ISupporter $supporter): string
    {
        return static::callHook('getMotionDetailsInitiatorName', [$supporter], $origLine);
    }

    public static function getAdminIndexHint(Consultation $consultation): string
    {
        return static::callHook('getAdminIndexHint', [$consultation]);
    }

    /**
     * @param Site $site
     * @return string[]
     */
    public static function getSitewidePublicWarnings(Site $site)
    {
        return static::callHook('getSitewidePublicWarnings', [$site], []);
    }
}
