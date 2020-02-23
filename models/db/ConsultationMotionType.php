<?php

namespace app\models\db;

use app\components\{DateTools, Tools};
use app\models\settings\{AntragsgruenApp, InitiatorForm, Layout, MotionType};
use app\models\policies\IPolicy;
use app\models\supportTypes\SupportBase;
use app\views\pdfLayouts\IPDFLayout;
use yii\db\ActiveRecord;

/**
 * @package app\models\db
 *
 * @property int $id
 * @property int $consultationId
 * @property string $titleSingular
 * @property string $titlePlural
 * @property string $createTitle
 * @property string $motionPrefix
 * @property int $position
 * @property int $pdfLayout
 * @property int $texTemplateId
 * @property string $deadlines
 * @property int $policyMotions
 * @property int $policyAmendments
 * @property int $policyComments
 * @property int $policySupportMotions
 * @property int $policySupportAmendments
 * @property int $initiatorsCanMergeAmendments
 * @property int $motionLikesDislikes
 * @property int $amendmentLikesDislikes
 * @property int $supportType @TODO Obsolete, remove database fields with next major version
 * @property string $supportTypeSettings @TODO Obsolete, remove database fields with next major version
 * @property string $supportTypeMotions
 * @property string $supportTypeAmendments
 * @property int $amendmentMultipleParagraphs
 * @property int $status
 * @property string $settings
 * @property int $sidebarCreateButton
 * @property int $pdfPageNumbers
 *
 * @property ConsultationSettingsMotionSection[] $motionSections
 * @property Motion[] $motions
 * @property ConsultationAgendaItem[] $agendaItems
 * @property TexTemplate $texTemplate
 */
class ConsultationMotionType extends ActiveRecord
{
    const STATUS_VISIBLE = 0;
    const STATUS_DELETED = -1;

    const INITIATORS_MERGE_NEVER          = 0;
    const INITIATORS_MERGE_NO_COLLISION   = 1;
    const INITIATORS_MERGE_WITH_COLLISION = 2;

    const DEADLINE_MOTIONS    = 'motions';
    const DEADLINE_AMENDMENTS = 'amendments';
    const DEADLINE_COMMENTS   = 'comments';
    const DEADLINE_MERGING    = 'merging';
    public static $DEADLINE_TYPES = ['motions', 'amendments', 'comments', 'merging'];

    protected $deadlinesObject = null;

    /**
     * @return string
     */
    public static function tableName()
    {
        /** @var AntragsgruenApp $app */
        $app = \Yii::$app->params;
        return $app->tablePrefix . 'consultationMotionType';
    }

    public function setAttributes($values, $safeOnly = true)
    {
        parent::setAttributes($values, $safeOnly);
        if (strlen($this->motionPrefix) > 0) {
            $this->motionPrefix = substr($this->motionPrefix, 0, 10);
        }
    }

    /**
     * @return Consultation
     */
    public function getConsultation()
    {
        $current = Consultation::getCurrent();
        if ($current && $current->id === $this->consultationId) {
            return $current;
        } else {
            return Consultation::findOne($this->consultationId);
        }
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotions()
    {
        return $this->hasMany(Motion::class, ['motionTypeId' => 'id'])
            ->andWhere(Motion::tableName() . '.status != ' . Motion::STATUS_DELETED);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTexTemplate()
    {
        return $this->hasOne(TexTemplate::class, ['id' => 'texTemplateId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotionSections()
    {
        return $this->hasMany(ConsultationSettingsMotionSection::class, ['motionTypeId' => 'id'])
            ->where('status = ' . ConsultationSettingsMotionSection::STATUS_VISIBLE)
            ->orderBy('position');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAgendaItems()
    {
        return $this->hasMany(ConsultationAgendaItem::class, ['motionTypeId' => 'id']);
    }


    public function getMotionPolicy(): IPolicy
    {
        return IPolicy::getInstanceByID($this->policyMotions, $this);
    }

    public function getAmendmentPolicy(): IPolicy
    {
        return IPolicy::getInstanceByID($this->policyAmendments, $this);
    }

    public function getCommentPolicy(): IPolicy
    {
        return IPolicy::getInstanceByID($this->policyComments, $this);
    }

    public function getMotionSupportPolicy(): IPolicy
    {
        return IPolicy::getInstanceByID($this->policySupportMotions, $this);
    }

    public function getAmendmentSupporterSettings(): InitiatorForm
    {
        if ($this->supportTypeAmendments) {
            return new InitiatorForm($this->supportTypeAmendments);
        } else {
            return $this->getMotionSupporterSettings();
        }
    }

    public function getMotionSupporterSettings(): InitiatorForm
    {
        return new InitiatorForm($this->supportTypeMotions);
    }

    public function getAmendmentSupportPolicy(): IPolicy
    {
        return IPolicy::getInstanceByID($this->policySupportAmendments, $this);
    }

    public function getMotionSupportTypeClass(): SupportBase
    {
        $settings = $this->getMotionSupporterSettings();
        return SupportBase::getImplementation($settings, $this);
    }

    public function getAmendmentSupportTypeClass(): SupportBase
    {
        $settings = $this->getAmendmentSupporterSettings();
        return SupportBase::getImplementation($settings, $this);
    }

    public function getMyConsultation(): Consultation
    {
        $current = Consultation::getCurrent();
        if ($current && $current->id === $this->consultationId) {
            return $current;
        } else {
            return Consultation::findOne($this->consultationId);
        }
    }

    public function getPDFLayoutClass(): ?IPDFLayout
    {
        $class = IPDFLayout::getClassById($this->pdfLayout);
        if ($class === null) {
            return null;
        }
        return new $class($this);
    }

    public function getOdtTemplateFile(): string
    {
        $layout    = $this->getConsultation()->site->getSettings()->siteLayout;
        $layoutDef = Layout::getLayoutPluginDef($layout);
        if ($layoutDef && $layoutDef['odtTemplate']) {
            return $layoutDef['odtTemplate'];
        } else {
            $dir = \yii::$app->basePath . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR;
            return $dir . 'OpenOffice-Template-Std.odt';
        }
    }

    /**
     * @return string[]
     */
    public static function getAvailablePDFTemplates()
    {
        /** @var AntragsgruenApp $params */
        $params = \Yii::$app->params;
        $return = [];
        foreach (IPDFLayout::getClasses($params) as $id => $data) {
            $return['php' . $id] = $data;
        }
        if ($params->xelatexPath || $params->lualatexPath) {
            /** @var TexTemplate[] $texLayouts */
            $texLayouts = TexTemplate::find()->all();
            foreach ($texLayouts as $layout) {
                if ($layout->id === 1) {
                    $preview = $params->resourceBase . 'img/pdf_preview_latex_bdk.png';
                } else {
                    $preview = null;
                }
                $return[$layout->id] = [
                    'title'   => $layout->title,
                    'preview' => $preview,
                ];
            }
        }
        return $return;
    }

    public function getDeadlinesByType(string $type): array
    {
        if ($this->deadlinesObject === null) {
            $this->deadlinesObject = json_decode($this->deadlines, true);
        }
        return (isset($this->deadlinesObject[$type]) ? $this->deadlinesObject[$type] : []);
    }

    public function setAllDeadlines(array $deadlines): void
    {
        $this->deadlines       = json_encode($deadlines);
        $this->deadlinesObject = null;
    }

    public function setSimpleDeadlines(?string $deadlineMotions, ?string $deadlineAmendments): void
    {
        $this->setAllDeadlines([
            static::DEADLINE_MOTIONS    => [['start' => null, 'end' => $deadlineMotions, 'title' => null]],
            static::DEADLINE_AMENDMENTS => [['start' => null, 'end' => $deadlineAmendments, 'title' => null]],
        ]);
    }

    public static function isInDeadlineRange(array $deadline, ?int $timestamp = null): bool
    {
        if ($timestamp === null) {
            $timestamp = DateTools::getCurrentTimestamp();
        }
        if ($deadline['start']) {
            $startTs = Tools::dateSql2timestamp($deadline['start']);
            if ($startTs > $timestamp) {
                return false;
            }
        }
        if ($deadline['end']) {
            $endTs = Tools::dateSql2timestamp($deadline['end']);
            if ($endTs < $timestamp) {
                return false;
            }
        }
        return true;
    }

    public function getUpcomingDeadline(string $type): ?\DateTime
    {
        $deadlines = $this->getDeadlinesByType($type);
        foreach ($deadlines as $deadline) {
            if (static::isInDeadlineRange($deadline) && $deadline['end']) {
                return $deadline['end'];
            }
        }
        return null;
    }

    public function isInDeadline(string $type): bool
    {
        $deadlines = $this->getDeadlinesByType($type);
        if (count($deadlines) === 0) {
            return true;
        }
        foreach ($deadlines as $deadline) {
            if (static::isInDeadlineRange($deadline)) {
                return true;
            }
        }
        return false;
    }

    public function getAllCurrentDeadlines(bool $onlyNamed = false): array
    {
        $found = [];
        foreach (static::$DEADLINE_TYPES as $type) {
            foreach ($this->getDeadlinesByType($type) as $deadline) {
                if ($onlyNamed && !$deadline['title']) {
                    continue;
                }
                if (static::isInDeadlineRange($deadline)) {
                    $deadline['type'] = $type;
                    $found[]          = $deadline;
                }
            }
        }
        return $found;
    }

    public function isDeletable(): bool
    {
        foreach ($this->motions as $motion) {
            if ($motion->status !== Motion::STATUS_DELETED) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['consultationId', 'titleSingular', 'titlePlural', 'createTitle', 'sidebarCreateButton'], 'required'],
            [['policyMotions', 'policyAmendments', 'policyComments', 'policySupportMotions'], 'required'],
            [['policySupportAmendments', 'initiatorsCanMergeAmendments', 'status'], 'required'],
            [['amendmentMultipleParagraphs', 'position'], 'required'],

            [['id', 'consultationId', 'position'], 'number'],
            [['status', 'amendmentMultipleParagraphs', 'amendmentLikesDislikes', 'motionLikesDislikes'], 'number'],
            [['policyMotions', 'policyAmendments', 'policyComments', 'policySupportMotions'], 'number'],
            [['initiatorsCanMergeAmendments', 'pdfLayout', 'sidebarCreateButton'], 'number'],

            [['titleSingular', 'titlePlural', 'createTitle', 'motionLikesDislikes', 'amendmentLikesDislikes'], 'safe'],
            [['motionPrefix', 'position', 'supportTypeMotions', 'supportTypeAmendments'], 'safe'],
            [['pdfLayout', 'policyMotions', 'policyAmendments', 'policyComments', 'policySupportMotions'], 'safe'],
            [['policySupportAmendments', 'initiatorsCanMergeAmendments'], 'safe'],
            [['sidebarCreateButton'], 'safe']
        ];
    }

    /** @var null|MotionType */
    private $settingsObject = null;

    public function getSettingsObj(): MotionType
    {
        if (!is_object($this->settingsObject)) {
            $this->settingsObject = new MotionType($this->settings);
        }
        return $this->settingsObject;
    }

    public function setSettingsObj(MotionType $settings)
    {
        $this->settingsObject = $settings;
        $this->settings       = json_encode($settings, JSON_PRETTY_PRINT);
    }

    /**
     * @param bool $withdrawnAreVisible
     *
     * @return Motion[]
     */
    public function getVisibleMotions($withdrawnAreVisible = true)
    {
        $return = [];
        foreach ($this->motions as $motion) {
            if (!in_array($motion->status, $this->getConsultation()->getInvisibleMotionStatuses($withdrawnAreVisible))) {
                $return[] = $motion;
            }
        }
        return $return;
    }

    public function isCompatibleTo(ConsultationMotionType $cmpMotionType): bool
    {
        $mySections  = $this->motionSections;
        $cmpSections = $cmpMotionType->motionSections;

        if (count($mySections) !== count($cmpSections)) {
            return false;
        }
        for ($i = 0; $i < count($mySections); $i++) {
            if ($mySections[$i]->type !== $cmpSections[$i]->type) {
                return false;
            }
        }
        return true;
    }

    public function getSectionCompatibilityMapping(ConsultationMotionType $cmpMotionType): array
    {
        $mapping = [];
        for ($i = 0; $i < count($this->motionSections); $i++) {
            $mapping[$this->motionSections[$i]->id] = $cmpMotionType->motionSections[$i]->id;
        }
        return $mapping;
    }

    /**
     * @return ConsultationMotionType[]
     */
    public function getCompatibleMotionTypes()
    {
        $compatible = [];
        foreach ($this->getMyConsultation()->motionTypes as $motionType) {
            if ($motionType->isCompatibleTo($this)) {
                $compatible[] = $motionType;
            }
        }
        return $compatible;
    }
}
