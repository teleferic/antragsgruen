<?php

namespace app\controllers;

use app\components\MessageSource;
use app\components\RSSExporter;
use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\AmendmentComment;
use app\models\db\ConsultationAgendaItem;
use app\models\db\IComment;
use app\models\db\IRSSItem;
use app\models\db\Motion;
use app\models\db\Consultation;
use app\models\db\MotionComment;
use app\models\db\User;
use app\models\db\UserNotification;
use app\models\exceptions\Access;
use app\models\exceptions\FormError;
use app\models\settings\AntragsgruenApp;

class ConsultationController extends Base
{
    /**
     *
     */
    public function actionSearch()
    {
        if (!isset($_REQUEST['query']) || trim($_REQUEST['query']) == '') {
            \yii::$app->session->setFlash('error', \Yii::t('con', 'search_no_query'));
            return $this->redirect(UrlHelper::createUrl('consultation/index'));
        }

        $results = $this->consultation->fulltextSearch($_REQUEST['query'], [
            'backTitle' => 'Suche',
            'backUrl'   => UrlHelper::createUrl(['consultation/search', 'query' => $_REQUEST['query']]),
        ]);

        return $this->render(
            'search_results',
            [
                'query'   => $_REQUEST['query'],
                'results' => $results
            ]
        );
    }


    /**
     *
     */
    public function actionFeedmotions()
    {
        $newest = Motion::getNewestByConsultation($this->consultation, 20);

        $feed = new RSSExporter();
        if ($this->consultation->getSettings()->logoUrl != '') {
            $feed->setImage($this->consultation->getSettings()->logoUrl);
        } else {
            $feed->setImage('/img/logo.png');
        }
        $feed->setTitle($this->consultation->title . ': ' . 'Anträge');
        $feed->setLanguage(\yii::$app->language);
        $feed->setBaseLink(UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/index')));
        $feed->setFeedLink(UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/feedmotions')));
        foreach ($newest as $motion) {
            $motion->addToFeed($feed);
        }

        \yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/xml');
        return $feed->getFeed();
    }

    /**
     *
     */
    public function actionFeedamendments()
    {
        $newest = Amendment::getNewestByConsultation($this->consultation, 20);

        $feed = new RSSExporter();
        if ($this->consultation->getSettings()->logoUrl != '') {
            $feed->setImage($this->consultation->getSettings()->logoUrl);
        } else {
            $feed->setImage('/img/logo.png');
        }
        $feed->setTitle($this->consultation->title . ': ' . 'Änderungsanträge');
        $feed->setLanguage(\yii::$app->language);
        $feed->setBaseLink(UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/index')));
        $feed->setFeedLink(UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/feedamendments')));
        foreach ($newest as $amend) {
            $amend->addToFeed($feed);
        }

        \yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/xml');
        return $feed->getFeed();
    }

    /**
     *
     */
    public function actionFeedcomments()
    {
        $newest = IComment::getNewestByConsultation($this->consultation, 20);

        $feed = new RSSExporter();
        if ($this->consultation->getSettings()->logoUrl != '') {
            $feed->setImage($this->consultation->getSettings()->logoUrl);
        } else {
            $feed->setImage('/img/logo.png');
        }
        $feed->setTitle($this->consultation->title . ': ' . 'Kommentare');
        $feed->setLanguage(\yii::$app->language);
        $feed->setBaseLink(UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/index')));
        $feed->setFeedLink(UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/feedcomments')));
        foreach ($newest as $comm) {
            $comm->addToFeed($feed);
        }

        \yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/xml');
        return $feed->getFeed();
    }

    /**
     *
     */
    public function actionFeedall()
    {
        /** @var IRSSItem[] $comments */
        $items = array_merge(
            Motion::getNewestByConsultation($this->consultation, 20),
            Amendment::getNewestByConsultation($this->consultation, 20),
            MotionComment::getNewestByConsultation($this->consultation, 20),
            AmendmentComment::getNewestByConsultation($this->consultation, 20)
        );
        usort($items, function ($item1, $item2) {
            /** @var IRSSItem $item1 */
            /** @var IRSSItem $item2 */
            $ts1 = Tools::dateSql2timestamp($item1->getDate());
            $ts2 = Tools::dateSql2timestamp($item2->getDate());
            if ($ts1 < $ts2) {
                return 1;
            }
            if ($ts1 > $ts2) {
                return -1;
            }
            return 0;
        });
        $items = array_slice($items, 0, 20);

        $feed = new RSSExporter();
        if ($this->consultation->getSettings()->logoUrl != '') {
            $feed->setImage($this->consultation->getSettings()->logoUrl);
        } else {
            $feed->setImage('/img/logo.png');
        }
        $feed->setTitle($this->consultation->title . ': ' . 'Neues');
        $feed->setLanguage(\yii::$app->language);
        $feed->setBaseLink(UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/index')));
        $feed->setFeedLink(UrlHelper::absolutizeLink(UrlHelper::createUrl('consultation/feedall')));

        foreach ($items as $item) {
            /** @var IRSSItem $item */
            $item->addToFeed($feed);
        }

        \yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/xml');
        return $feed->getFeed();
    }

    /**
     *
     */
    public function actionNotifications()
    {
        $this->forceLogin();

        $user = User::getCurrentUser();
        $con  = $this->consultation;

        if (isset($_POST['save'])) {
            $newNotis = (isset($_POST['notifications']) ? $_POST['notifications'] : []);
            if (in_array('motion', $newNotis)) {
                UserNotification::addNotification($user, $con, UserNotification::NOTIFICATION_NEW_MOTION);
            } else {
                UserNotification::removeNotification($user, $con, UserNotification::NOTIFICATION_NEW_MOTION);
            }
            if (in_array('amendment', $newNotis)) {
                UserNotification::addNotification($user, $con, UserNotification::NOTIFICATION_NEW_AMENDMENT);
            } else {
                UserNotification::removeNotification($user, $con, UserNotification::NOTIFICATION_NEW_AMENDMENT);
            }
            if (in_array('comment', $newNotis)) {
                UserNotification::addNotification($user, $con, UserNotification::NOTIFICATION_NEW_COMMENT);
            } else {
                UserNotification::removeNotification($user, $con, UserNotification::NOTIFICATION_NEW_COMMENT);
            }
            \Yii::$app->session->setFlash('success', 'Gespeichert.');
        }

        $notifications = UserNotification::getUserConsultationNotis($user, $this->consultation);

        return $this->render('user_notifications', ['user' => $user, 'notifications' => $notifications]);
    }

    /**
     * @param string $pageKey
     * @return string
     * @throws Access
     */
    public function actionSavetextajax($pageKey)
    {
        if (!User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_CONTENT_EDIT)) {
            throw new Access('No permissions to edit this page');
        }
        if (MessageSource::savePageData($this->consultation, $pageKey, $_POST['data'])) {
            return '1';
        } else {
            return '0';
        }
    }

    /**
     * @return string
     */
    public function actionMaintainance()
    {
        return $this->renderContentPage('maintainance');
    }

    /**
     * @return string
     */
    public function actionLegal()
    {
        /** @var AntragsgruenApp $params */
        $params = \Yii::$app->params;
        if ($params->multisiteMode) {
            $admin      = User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_CONTENT_EDIT);
            $saveUrl    = UrlHelper::createUrl(['consultation/savetextajax', 'pageKey' => 'legal']);
            $viewParams = ['pageKey' => 'legal', 'admin' => $admin, 'saveUrl' => $saveUrl];
            return $this->render('imprint_multisite', $viewParams);
        } else {
            return $this->renderContentPage('legal');
        }
    }

    /**
     * @return string
     */
    public function actionPrivacy()
    {
        return $this->renderContentPage('privacy');
    }

    /**
     * @return string
     */
    public function actionHelp()
    {
        return $this->renderContentPage('help');
    }

    /**
     * @param Consultation $consultation
     */
    private function consultationSidebar(Consultation $consultation)
    {
        $newestAmendments = Amendment::getNewestByConsultation($consultation, 5);
        $newestMotions    = Motion::getNewestByConsultation($consultation, 3);
        $newestComments   = IComment::getNewestByConsultation($consultation, 3);

        $this->renderPartial(
            'sidebar',
            [
                'newestMotions'    => $newestMotions,
                'newestAmendments' => $newestAmendments,
                'newestComments'   => $newestComments,
            ]
        );
    }


    /**
     * @param array $arr
     * @param int|null $parentId
     * @return \int[]
     * @throws FormError
     */
    private function saveAgendaArr($arr, $parentId)
    {
        $items = [];
        foreach ($arr as $i => $jsitem) {
            if ($jsitem['id'] > 0) {
                $consultationId = IntVal($this->consultation->id);
                $condition      = ['id' => IntVal($jsitem['id']), 'consultationId' => $consultationId];
                /** @var ConsultationAgendaItem $item */
                $item = ConsultationAgendaItem::findOne($condition);
                if (!$item) {
                    throw new FormError('Inconsistency - did not find given agenda item: ' . $condition);
                }
            } else {
                $item                 = new ConsultationAgendaItem();
                $item->consultationId = $this->consultation->id;
            }

            $item->code         = $jsitem['code'];
            $item->title        = $jsitem['title'];
            $item->motionTypeId = ($jsitem['motionTypeId'] > 0 ? $jsitem['motionTypeId'] : null);
            $item->parentItemId = $parentId;
            $item->position     = $i;

            $item->save();
            $items[] = $item->id;

            $items = array_merge($items, $this->saveAgendaArr($jsitem['children'], $item->id));
        }
        return $items;
    }

    /**
     * @throws FormError
     */
    private function saveAgenda()
    {
        if (!User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_CONTENT_EDIT)) {
            \Yii::$app->session->setFlash('error', 'No permissions to edit this page');
            return;
        }

        $data = json_decode($_POST['data'], true);
        if (!is_array($data)) {
            \Yii::$app->session->setFlash('error', 'Could not parse input');
            return;
        }

        try {
            $usedItems = $this->saveAgendaArr($data, null);
        } catch (\Exception $e) {
            \Yii::$app->session->setFlash('error', $e->getMessage());
            return;
        }

        foreach ($this->consultation->agendaItems as $item) {
            if (!in_array($item->id, $usedItems)) {
                $item->delete();
            }
        }

        $this->consultation->refresh();

        \Yii::$app->session->setFlash('success', 'Saved');
    }

    /**
     * @param string $url
     * @return string
     */
    public function actionShariffbackend($url)
    {
        \yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/json');
        try {
            $shariff = new \Heise\Shariff\Backend([
                'domain'   => $_SERVER['HTTP_HOST'],
                'services' => ['Facebook'],
                'cache'    => [
                    'ttl'      => 60,
                    'cacheDir' => $this->getParams()->tmpDir,
                ]
            ]);
            return json_encode($shariff->get($url));
        } catch (\Exception $e) {
            return json_encode([]);
        }
    }


    /**
     * @return string
     */
    public function actionIndex()
    {
        $this->layout = 'column2';
        $this->consultationSidebar($this->consultation);

        if (isset($_POST['saveAgenda'])) {
            $this->saveAgenda();
        }


        $myself = User::getCurrentUser();
        if ($myself) {
            $myMotions    = $myself->getMySupportedMotionsByConsultation($this->consultation);
            $myAmendments = $myself->getMySupportedAmendmentsByConsultation($this->consultation);
        } else {
            $myMotions    = null;
            $myAmendments = null;
        }

        $saveUrl = UrlHelper::createUrl(['consultation/savetextajax', 'pageKey' => 'welcome']);

        return $this->render(
            'index',
            [
                'consultation' => $this->consultation,
                'myself'       => $myself,
                'myMotions'    => $myMotions,
                'myAmendments' => $myAmendments,
                'admin'        => User::currentUserHasPrivilege($this->consultation, User::PRIVILEGE_CONTENT_EDIT),
                'saveUrl'      => $saveUrl,
            ]
        );
    }
}
