<?php

final class PhorgeApplicationUninstallTransaction
  extends PhorgeApplicationTransactionType {

  const TRANSACTIONTYPE = 'application.uninstall';

  public function generateOldValue($object) {
    $key = 'phorge.uninstalled-applications';
    $config_entry = PhorgeConfigEntry::loadConfigEntry($key);
    $list = $config_entry->getValue();
    $uninstalled = PhorgeEnv::getEnvConfig($key);

    if (isset($uninstalled[get_class($object)])) {
      return 'uninstalled';
    } else {
      return 'installed';
    }
  }

  public function generateNewValue($object, $value) {
    if ($value === 'uninstall') {
      return 'uninstalled';
    } else {
      return 'installed';
    }
  }

  public function applyExternalEffects($object, $value) {
    $application = $object;
    $user = $this->getActor();

    $key = 'phorge.uninstalled-applications';
    $config_entry = PhorgeConfigEntry::loadConfigEntry($key);
    $list = $config_entry->getValue();
    $uninstalled = PhorgeEnv::getEnvConfig($key);

    if (isset($uninstalled[get_class($application)])) {
      unset($list[get_class($application)]);
    } else {
      $list[get_class($application)] = true;
    }

    $editor = $this->getEditor();
    $content_source = $editor->getContentSource();

    // Today, changing config requires "Administrator", but "Can Edit" on
    // applications to let you uninstall them may be granted to any user.
    PhorgeConfigEditor::storeNewValue(
      PhorgeUser::getOmnipotentUser(),
      $config_entry,
      $list,
      $content_source,
      $user->getPHID());
  }

  public function getTitle() {
    if ($this->getNewValue() === 'uninstalled') {
      return pht(
        '%s uninstalled this application.',
        $this->renderAuthor());
    } else {
      return pht(
        '%s installed this application.',
        $this->renderAuthor());
    }
  }

  public function getTitleForFeed() {
    if ($this->getNewValue() === 'uninstalled') {
      return pht(
        '%s uninstalled %s.',
        $this->renderAuthor(),
        $this->renderObject());
    } else {
      return pht(
        '%s installed %s.',
        $this->renderAuthor(),
        $this->renderObject());
    }
  }

}
