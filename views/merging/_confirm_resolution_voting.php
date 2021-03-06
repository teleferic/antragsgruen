<?php

use app\components\Tools;
use app\models\db\Motion;
use yii\helpers\Html;

/** @var Motion $motion */

$locale = Tools::getCurrentDateLocale();
$date   = Tools::dateSql2bootstrapdate(date('Y-m-d'));

$voting       = $motion->getVotingData();
$votingOpened = $voting->hasAnyData();
?>
<h2 class="green"><?= Yii::t('amend', 'merge_new_status') ?></h2>
<div class="content row contentMotionStatus">
    <div class="col-md-6 newMotionStatus">
        <label>
            <input type="radio" name="newStatus" value="motion" checked>
            <?= Yii::t('amend', 'merge_new_status_screened') ?>
        </label>
        <label>
            <input type="radio" name="newStatus" value="resolution_final">
            <?= Yii::t('amend', 'merge_new_status_res_f') ?>
        </label>
        <label>
            <input type="radio" name="newStatus" value="resolution_preliminary">
            <?= Yii::t('amend', 'merge_new_status_res_p') ?>
        </label>
    </div>
    <div class="col-md-6 newMotionInitiator">
        <label for="newInitiator"><?= Yii::t('amend', 'merge_new_orga') ?></label>
        <input class="form-control" name="newInitiator" type="text" id="newInitiator">
        <label for="dateResolution"><?= Yii::t('amend', 'merge_new_resolution_date') ?></label>
        <div class="input-group date" id="dateResolutionHolder">
            <input type="text" class="form-control" name="dateResolution" id="dateResolution"
                   value="<?= Html::encode($date) ?>" data-locale="<?= Html::encode($locale) ?>">
            <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
        </div>
    </div>
</div>
<div class="content contentVotingResultCaller">
    <button class="btn btn-link votingResultOpener <?= ($votingOpened ? 'hidden' : '') ?>" type="button">
        <span class="glyphicon glyphicon-chevron-down"></span>
        <?= Yii::t('amend', 'merge_new_votes_enter') ?>
    </button>
    <button class="btn btn-link votingResultCloser <?= ($votingOpened ? '' : 'hidden') ?>" type="button">
        <span class="glyphicon glyphicon-chevron-up"></span>
        <?= Yii::t('amend', 'merge_new_votes_enter') ?>:
    </button>
</div>
<div class="content contentVotingResult row <?= ($votingOpened ? '' : 'hidden') ?>">
    <div class="col-md-3">
        <label for="votesYes"><?= Yii::t('amend', 'merge_new_votes_yes') ?></label>
        <input class="form-control" name="votes[yes]" type="number" id="votesYes"
               value="<?= Html::encode($voting->votesYes ? $voting->votesYes : '') ?>">
    </div>
    <div class="col-md-3">
        <label for="votesNo"><?= Yii::t('amend', 'merge_new_votes_no') ?></label>
        <input class="form-control" name="votes[no]" type="number" id="votesNo"
               value="<?= Html::encode($voting->votesNo ? $voting->votesNo : '') ?>">
    </div>
    <div class="col-md-3">
        <label for="votesAbstention"><?= Yii::t('amend', 'merge_new_votes_abstention') ?></label>
        <input class="form-control" name="votes[abstention]" type="number" id="votesAbstention"
               value="<?= Html::encode($voting->votesAbstention ? $voting->votesAbstention : '') ?>">
    </div>
    <div class="col-md-3">
        <label for="votesInvalid"><?= Yii::t('amend', 'merge_new_votes_invalid') ?></label>
        <input class="form-control" name="votes[invalid]" type="number" id="votesInvalid"
               value="<?= Html::encode($voting->votesInvalid ? $voting->votesInvalid : '') ?>">
    </div>
</div>
<div class="content contentVotingResultComment row <?= ($votingOpened ? '' : 'hidden') ?>">
    <div class="col-md-12">
        <label for="votesComment"><?= Yii::t('amend', 'merge_new_votes_comment') ?></label>
        <input class="form-control" name="votes[comment]" type="text" id="votesComment"
               value="<?= Html::encode($voting->comment ? $voting->comment : '') ?>">
    </div>
</div>
