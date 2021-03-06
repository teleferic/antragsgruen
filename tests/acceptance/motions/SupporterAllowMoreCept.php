<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);

$I->populateDBData1();

$I->wantTo('check that allowing more supporters is enabled');
$I->gotoConsultationHome(true, 'bdk', 'bdk');
$I->loginAsStdAdmin();
$I->click('.createMotion');
$I->see(mb_strtoupper('Unterstützer*innen'), '.supporterDataHead');
$I->seeElement('.supporterData .adderRow');

$I->click('#adminLink');
$I->click('.motionType7');
$I->seeCheckboxIsChecked('#typeAllowMoreSupporters input[type=checkbox]');


$I->wantTo('disable allowing more supporters');
$I->uncheckFueluxCheckbox('#typeAllowMoreSupporters .checkbox-custom');
$I->submitForm('.adminTypeForm', [], 'save');
$I->cantSeeCheckboxIsChecked('#typeAllowMoreSupporters input[type=checkbox]');

$I->gotoConsultationHome(true, 'bdk', 'bdk');
$I->click('.createMotion');
$I->see(mb_strtoupper('Unterstützer*innen'), '.supporterDataHead');
$I->dontSeeElement('.supporterData .adderRow');
