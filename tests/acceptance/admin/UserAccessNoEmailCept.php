<?php

/** @var \Codeception\Scenario $scenario */
$I = new AcceptanceTester($scenario);
$I->populateDBData1();

$I->wantTo('check the basic configuration');
$I->loginAndGotoStdAdminPage()->gotoSiteAccessPage();
$I->checkOption('input[name=managedUserAccounts]');
$I->submitForm('#siteSettingsForm', [], 'saveLogin');

$I->click('.addUsersOpener.email');
$I->see('Benachrichtigungs-E-Mail', '.alert-info');
$I->dontSee('Datenschutzgründen', '.alert-info');
$I->dontSeeElement('#passwords');


$I->wantTo('disable e-mails');

$I->setAntragsgruenConfiguration(['mailService' => ['transport' => 'none']]);

$I->gotoConsultationHome();
$I->click('#adminLink');
$I->click('.siteAccessLink');

$I->wantTo('create an user');

$I->dontSeeElement('#emailAddresses');
$I->click('.addUsersOpener.email');
$I->dontSee('Benachrichtigungs-E-Mail', '.alert-info');
$I->see('Datenschutzgründen', '.alert-info');

$I->seeElement('#emailAddresses');
$I->seeElement('#passwords');

$I->fillField('#emailAddresses', 'blibla@example.org');
$I->fillField('#passwords', 'bliblablubb');
$I->fillField('#names', 'Kasper');
$I->submitForm('#accountsCreateForm', [], 'addUsers');

$I->see('Kasper', '.accountListTable');
$I->see('blibla@example.org', '.accountListTable');



$I->wantTo('log in with the new user');
$I->gotoConsultationHome();
$I->logout();
$I->click('#loginLink');
$I->fillField('#username', 'blibla@example.org');
$I->fillField('#passwordInput', 'bliblablubb');
$I->submitForm('#usernamePasswordForm', [], 'loginusernamepassword');
$I->see('Willkommen!', '.alert-success');
