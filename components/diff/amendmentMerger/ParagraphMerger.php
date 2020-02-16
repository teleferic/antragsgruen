<?php

namespace app\components\diff\amendmentMerger;

use app\components\diff\{Diff, DiffRenderer};
use app\components\UrlHelper;
use app\models\db\Amendment;
use yii\helpers\Html;

class ParagraphMerger
{
    /** @var ParagraphOriginalData */
    private $paraData;

    /** @var ParagraphDiff[] */
    private $diffs;

    private $merged = false;

    public function __construct(string $paragraphStr)
    {
        $origTokenized = Diff::tokenizeLine($paragraphStr);
        $words         = [];
        foreach ($origTokenized as $x) {
            $words[] = [
                'orig'         => $x,
                'modification' => null,
                'modifiedBy'   => null,
            ];
        }
        $this->paraData = new ParagraphOriginalData($paragraphStr, $origTokenized, $words);
        $this->diffs    = [];
    }

    public function addAmendmentParagraph(int $amendmentId, array $wordArr): void
    {
        $hasChanges = false;
        $firstDiff  = null;
        for ($i = 0; $i < count($wordArr); $i++) {
            if (isset($wordArr[$i]['amendmentId'])) {
                $hasChanges = true;
                if ($firstDiff === null) {
                    $firstDiff = $i;
                }
            }
        }

        if ($hasChanges) {
            $this->diffs[] = new ParagraphDiff($amendmentId, $firstDiff, $wordArr);
        }
    }

    /*
     * Sort the amendment paragraphs by the last affected line/word.
     * This is an attempt to minimize the number of collisions when merging the paragraphs later on,
     * as amendments changing a lot and therefore colliding more frequently tend to start at earlier lines.
     */
    private function sortDiffParagraphs(): void
    {
        usort($this->diffs, function (ParagraphDiff $val1, ParagraphDiff $val2) {
            if ($val1->firstDiff < $val2->firstDiff) {
                return 1;
            }
            if ($val2->firstDiff < $val1->firstDiff) {
                return -1;
            }
            return 0;
        });
    }

    private function moveInsertIntoOwnWord(int $amendingNo, int $wordNo, string $insert): void
    {
        $insertArr = function ($arr, $pos, $insertedEl) {
            return array_merge(array_slice($arr, 0, $pos + 1), [$insertedEl], array_slice($arr, $pos + 1));
        };

        // Figures out if the blank element is to be inserted in the middle of a deletion block.
        // If so, the "amendmentId"-Attribute needs to be set to trigger a collision
        $pendingDeleteAmendment = function ($locAmendNo, $wordNo) {
            if ($wordNo == 0) {
                return null;
            }

            while ($wordNo >= 0) {
                $str = explode("###DEL_", $this->diffs[$locAmendNo]->diff[$wordNo]['diff']);
                if (count($str) > 1 && strpos($str[count($str) - 1], 'START') === 0) {
                    return $this->diffs[$locAmendNo]->diff[$wordNo]['amendmentId'];
                }
                if (count($str) > 1 && strpos($str[count($str) - 1], 'END') === 0) {
                    return null;
                }
                $wordNo--;
            }

            return null;
        };

        $this->paraData->origTokenized = $insertArr($this->paraData->origTokenized, $wordNo, '');
        $this->paraData->words         = $insertArr($this->paraData->words, $wordNo, [
            'orig'         => '',
            'modification' => null,
            'modifiedBy'   => null,
        ]);

        foreach ($this->diffs as $locAmendNo => $changeSet) {
            if ($locAmendNo == $amendingNo) {
                $amendmentId                      = $changeSet->diff[$wordNo]['amendmentId'];
                $changeSet->diff[$wordNo]['diff'] = $changeSet->diff[$wordNo]['word'];
                unset($changeSet->diff[$wordNo]['amendmentId']);
                $changeSet->diff = $insertArr($changeSet->diff, $wordNo, [
                    'word'        => '',
                    'diff'        => $insert,
                    'amendmentId' => $amendmentId,
                ]);
            } else {
                $insertArrEl = ['word' => '', 'diff' => ''];
                $preAm       = $pendingDeleteAmendment($locAmendNo, $wordNo);
                if ($preAm !== null) {
                    $insertArrEl['amendmentId'] = $preAm;
                }
                $changeSet->diff = $insertArr($changeSet->diff, $wordNo, $insertArrEl);
            }
            $this->diffs[$locAmendNo] = $changeSet;
        }
    }

    /*
     * Inserting new words / paragraphs is stored like "</p>###INS_START###...###INS_END###,
     * being assigned to the "</p>" token. This makes multiple insertions after </p> colliding with each other.
     * This workaround splits this up by inserting empty tokens in the original word array
     * and moving the insertion to this newly created index.
     * To maintain consistency, we need to insert the new token both in the original word array as well as in _all_
     * amendments affecting this paragraph.
     *
     * This isn't exactly very elegant, as the data structure mutates as we're iterating over it,
     * therefore we need to cancel out the side-effects.
     *
     * AmendmentRewriter::moveInsertsIntoTheirOwnWords does about the same and should behave similarily
     */
    private function moveInsertsIntoTheirOwnWords(): void
    {
        foreach ($this->diffs as $changeSetNo => $changeSet) {
            $changeSet = $this->diffs[$changeSetNo];
            $words     = count($changeSet->diff);
            for ($wordNo = 0; $wordNo < $words; $wordNo++) {
                $word  = $changeSet->diff[$wordNo];
                $split = explode('###INS_START###', $word['diff']);
                if (count($split) === 2 && $split[0] == $word['word']) {
                    $this->moveInsertIntoOwnWord($changeSetNo, $wordNo, '###INS_START###' . $split[1]);
                    $changeSet = $this->diffs[$changeSetNo];
                    $wordNo++;
                    $words++;
                }
            }
        }
    }


    /*
     * Identify adjacent tokens that are about to be changed and check if any of the changes leads to a collision.
     */
    private function groupChangeSet(ParagraphDiff $changeSet): array
    {
        $foundGroups = [];

        $currTokens        = null;
        $currGroupCollides = null;

        foreach ($changeSet->diff as $i => $token) {
            if (isset($token['amendmentId'])) {
                if ($currTokens === null) {
                    $currGroupCollides = false;
                    $currTokens        = [];
                }
                $currTokens[$i] = $token;
                if ($this->paraData->words[$i]['modifiedBy'] > 0) {
                    $currGroupCollides = true;
                }
            } else {
                if ($currTokens !== null) {
                    $foundGroups[]     = [
                        'tokens'   => $currTokens,
                        'collides' => $currGroupCollides
                    ];
                    $currTokens        = null;
                    $currGroupCollides = null;
                }
            }
        }
        if ($currTokens !== null) {
            $foundGroups[] = [
                'tokens'   => $currTokens,
                'collides' => $currGroupCollides
            ];
        }

        return $foundGroups;
    }

    public function mergeParagraph(ParagraphDiff $changeSet): void
    {
        $words = $this->paraData->words;

        $paragraphHadCollisions = false;
        $groups                 = $this->groupChangeSet($changeSet);
        foreach ($groups as $group) {
            // Transfer the diff from the non-colliding groups to the merged diff and remove the from the changeset.
            // The changeset that remains will contain the un-mergable collisions

            if ($group['collides']) {
                $paragraphHadCollisions = true;
                continue;
            }

            foreach ($group['tokens'] as $i => $token) {
                // Apply the changes to the paragraph
                $words[$i]['modification'] = $token['diff'];
                $words[$i]['modifiedBy']   = $token['amendmentId'];

                // Only the colliding changes are left in the changeset
                unset($changeSet->diff[$i]['amendmentId']);
                $changeSet->diff[$i]['diff'] = $changeSet->diff[$i]['word'];
            }
        }

        $this->paraData->words = $words;
        if ($paragraphHadCollisions) {
            $this->paraData->collidingParagraphs[] = $changeSet;
        }
    }

    private function merge(): void
    {
        if ($this->merged) {
            return;
        }

        $this->sortDiffParagraphs();
        $this->moveInsertsIntoTheirOwnWords();

        foreach ($this->diffs as $changeSet) {
            $this->mergeParagraph($changeSet);
        }

        $this->merged = true;
    }

    public static function stripDistantUnchangedWords(array $words, int $maxDistance): array
    {
        $distance = null;
        $numWords = count($words);
        foreach ($words as $i => $word) {
            $words[$i]['distance'] = null;
        }
        for ($i = 0; $i < $numWords; $i++) {
            if ($words[$i]['modification']) {
                $distance = 0;
            } else {
                if ($distance === null) {
                    continue;
                }
                if (trim(strip_tags($words[$i]['orig'])) != '') {
                    $distance++;
                }
                $words[$i]['distance'] = $distance;
            }
        }
        for ($i = $numWords - 1; $i >= 0; $i--) {
            if ($words[$i]['modification']) {
                $distance = 0;
            } else {
                if ($distance === null) {
                    continue;
                }
                if (trim(strip_tags($words[$i]['orig'])) != '') {
                    $distance++;
                }
                if ($words[$i]['distance'] === null || $words[$i]['distance'] > $distance) {
                    $words[$i]['distance'] = $distance;
                }
            }
        }

        foreach ($words as $i => $word) {
            if (strpos($word['orig'], '<') === false && trim($word['orig']) != '') {
                if ($words[$i]['distance'] == ($maxDistance + 1)) {
                    $words[$i]['orig'] = ' … ';
                } elseif ($words[$i]['distance'] > ($maxDistance + 1)) {
                    $words[$i]['orig'] = '';
                }
            }
            unset($words[$i]['distance']);
        }

        return $words;
    }

    private static function groupParagraphData(array $words): array
    {
        $groupedParaData  = [];
        $pending          = '';
        $pendingCurrAmend = 0;
        $addToGrouped     = function ($pendingCurrAmend, $text) use (&$groupedParaData) {
            $groupedParaData[] = [
                'amendment' => $pendingCurrAmend,
                //'text'      => static::cleanupParagraphData($text),
                'text'      => $text,
            ];
        };
        foreach ($words as $word) {
            if ($word['modifiedBy'] !== null) {
                if ($pendingCurrAmend == 0 && $word['orig'] != '') {
                    if (mb_strpos($word['modification'], $word['orig']) === 0) {
                        $shortened            = mb_substr($word['modification'], mb_strlen($word['orig']));
                        $pending              .= $word['orig'];
                        $word['modification'] = $shortened;
                    }
                }
                if ($word['modifiedBy'] != $pendingCurrAmend) {
                    $addToGrouped($pendingCurrAmend, $pending);
                    $pending          = '';
                    $pendingCurrAmend = $word['modifiedBy'];
                }
                $pending .= $word['modification'];
            } else {
                if (0 != $pendingCurrAmend) {
                    $addToGrouped($pendingCurrAmend, $pending);
                    $pending          = '';
                    $pendingCurrAmend = 0;
                }
                $pending .= $word['orig'];
            }
        }
        $addToGrouped($pendingCurrAmend, $pending);
        return $groupedParaData;
    }

    public function getGroupedParagraphData(?int $stripDistantUnchangedWords = null): array
    {
        $this->merge();

        $words = $this->paraData->words;
        if ($stripDistantUnchangedWords) {
            $words = $this->stripDistantUnchangedWords($words, $stripDistantUnchangedWords);
        }

        return static::groupParagraphData($words);
    }

    /**
     * @param Amendment[] $amendmentsById
     * @param null|integer $stripDistantUnchangedWords
     * @return string
     */
    public function getFormattedDiffText(array $amendmentsById, ?int $stripDistantUnchangedWords = null): string
    {
        $CHANGESET_COUNTER = 0;
        $changeset         = [];

        $groupedParaData = $this->getGroupedParagraphData($stripDistantUnchangedWords);
        $paragraphText   = '';
        foreach ($groupedParaData as $part) {
            $text = $part['text'];

            if ($part['amendment'] > 0) {
                $amendmentId = $part['amendment'];
                $cid         = $CHANGESET_COUNTER++;
                if (!isset($changeset[$amendmentId])) {
                    $changeset[$amendmentId] = [];
                }
                $changeset[$amendmentId][] = $cid;

                $mid  = $cid . '-' . $amendmentId;
                $text = str_replace('###INS_START###', '###INS_START' . $mid . '###', $text);
                $text = str_replace('###DEL_START###', '###DEL_START' . $mid . '###', $text);
            }

            $paragraphText .= $text;
        }

        return DiffRenderer::renderForInlineDiff($paragraphText, $amendmentsById);
    }


    /*
     * Somewhat special case: if two amendments are inserting a bullet point at the same place,
     * they are colliding. We cannot change this fact right now, so at least
     * let's try not to print the previous line that wasn't actually changed twice.
     */
    private static function stripUnchangedLiFromColliding(string $str): string
    {
        if (mb_substr($str, 0, 8) !== '<ul><li>' && mb_substr($str, 0, 8) !== '<ol><li>') {
            return $str;
        }
        if (mb_substr_count($str, '<li>') !== 1 || mb_substr_count($str, '</li>') !== 1) {
            return $str;
        }
        return preg_replace('/<li>.*<\/li>/siu', '', $str);
    }

    /**
     * @return ParagraphDiff[]
     */
    public function getCollidingParagraphs(): array
    {
        $this->merge();
        return $this->paraData->collidingParagraphs;
    }

    public function getCollidingParagraphGroups(?int $stripDistantUnchangedWords = null): array
    {
        $this->merge();

        $grouped = [];

        foreach ($this->paraData->collidingParagraphs as $changeSet) {
            $words = [];
            foreach ($this->paraData->origTokenized as $token) {
                $words[] = [
                    'orig'         => $token,
                    'modification' => null,
                    'modifiedBy'   => null,
                ];
            }
            foreach ($changeSet->diff as $i => $token) {
                if (isset($token['amendmentId'])) {
                    $words[$i]['modification'] = $token['diff'];
                    $words[$i]['modifiedBy']   = $token['amendmentId'];
                }
            }
            if ($stripDistantUnchangedWords) {
                $words = $this->stripDistantUnchangedWords($words, $stripDistantUnchangedWords);
            }

            $data = static::groupParagraphData($words);
            foreach ($data as $i => $dat) {
                if ($dat['amendment'] == 0) {
                    $data[$i]['text'] = static::stripUnchangedLiFromColliding($dat['text']);
                }
            }
            $grouped[$changeSet->amendment] = $data;
        }

        return $grouped;
    }

    public static function getFormattedCollision(array $paraData, Amendment $amendment, array $amendmentsById): string
    {
        $amendmentUrl      = UrlHelper::createAmendmentUrl($amendment);
        $paragraphText     = '';
        $CHANGESET_COUNTER = 0;

        foreach ($paraData as $part) {
            $text = $part['text'];

            if ($part['amendment'] > 0) {
                $amendment = $amendmentsById[$part['amendment']];
                $cid       = $CHANGESET_COUNTER++;

                $mid  = $cid . '-' . $amendment->id;
                $text = str_replace('###INS_START###', '###INS_START' . $mid . '###', $text);
                $text = str_replace('###DEL_START###', '###DEL_START' . $mid . '###', $text);
            }

            $paragraphText .= $text;
        }

        $out = '<div class="collidingParagraph collidingParagraph' . $amendment->id . '"
                     data-link="' . Html::encode($amendmentUrl) . '"
                     data-amendment-id="' . $amendment->id . '"
                     data-username="' . Html::encode($amendment->getInitiatorsStr()) . '">
                     <button class="btn btn-link pull-right btn-xs hideCollision" type="button">' .
               \Yii::t('amend', 'merge_colliding_hide') . ' <span class="glyphicon glyphicon-minus-sign"></span>' .
               '</button>
                     <p class="collidingParagraphHead"><strong>' .
            \Yii::t('amend', 'merge_colliding') . ': ' .
            Html::a(Html::encode($amendment->titlePrefix), $amendmentUrl) .
            '</strong></p>';

        $out .= '<div class="alert alert-danger"><p>' . \Yii::t('amend', 'merge_colliding_hint') . '</p></div>';
        $out .= DiffRenderer::renderForInlineDiff($paragraphText, $amendmentsById);
        $out .= '</div>';

        return $out;
    }

    /**
     * @return int[]
     */
    public function getAffectingAmendmentIds(): array
    {
        return array_map(function (ParagraphDiff $diff) {
            return $diff->amendment;
        }, $this->diffs);
    }
}
