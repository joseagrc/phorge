<?php

final class PhorgeEmailNotificationsSetting
  extends PhorgeSelectSetting {

  const SETTINGKEY = 'no-mail';

  const VALUE_SEND_MAIL = '0';
  const VALUE_NO_MAIL = '1';

  public function getSettingName() {
    return pht('Email Notifications');
  }

  public function getSettingPanelKey() {
    return PhorgeEmailDeliverySettingsPanel::PANELKEY;
  }

  protected function getSettingOrder() {
    return 100;
  }

  protected function getControlInstructions() {
    return pht(
      'If you disable **Email Notifications**, this server will never '.
      'send email to notify you about events. This preference overrides '.
      'all your other settings.'.
      "\n\n".
      "//You will still receive some administrative email, like password ".
      "reset email.//");
  }

  public function getSettingDefaultValue() {
    return self::VALUE_SEND_MAIL;
  }

  protected function getSelectOptions() {
    return array(
      self::VALUE_SEND_MAIL => pht('Enable Email Notifications'),
      self::VALUE_NO_MAIL => pht('Disable Email Notifications'),
    );
  }

}
