<?php

use app\components\HTMLTools;
use yii\helpers\Html;

/**
 * @var \app\models\db\Motion $motion
 * @var int $sectionId
 * @var int $paragraphNo
 */

$comment = $motion->getPrivateComment($sectionId, $paragraphNo);
?>
<section class="privateParagraphNoteHolder">
    <?php
    if (!$comment) {
        ?>
        <div class="privateParagraphNoteOpener hidden">
            <button class="btn btn-link btn-xs">
                <span class="glyphicon glyphicon-pushpin"></span>
                <?= Yii::t('motion', 'private_notes') ?>
            </button>
        </div>
        <?php
    }
    if ($comment) {
        ?>
        <blockquote class="privateParagraph<?= $comment ? '' : ' hidden' ?>" id="comm<?= $comment->id ?>">
            <button class="btn btn-link btn-xs btnEdit"><span class="glyphicon glyphicon-edit"></span></button>
            <?= HTMLTools::textToHtmlWithLink($comment ? $comment->text : '') ?>
        </blockquote>
        <?php
    }
    ?>
    <?= Html::beginForm('', 'post', ['class' => 'form-inline hidden']) ?>
    <label>
        <?= Yii::t('motion', 'private_notes') ?>
        <textarea class="form-control" name="noteText"
        ><?= Html::encode($comment ? $comment->text : '') ?></textarea>
    </label>
    <input type="hidden" name="paragraphNo" value="<?= $paragraphNo ?>">
    <input type="hidden" name="sectionId" value="<?= $sectionId ?>">
    <button type="submit" name="savePrivateNote" class="btn btn-success">
        <?= Yii::t('base', 'save') ?>
    </button>
    <?= Html::endForm() ?>
</section>
