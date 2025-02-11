<?php

function phorge_date($epoch, PhorgeUser $user) {
  return phorge_format_local_time(
    $epoch,
    $user,
    phutil_date_format($epoch));
}

function phorge_relative_date($epoch, $user, $on = false) {
  static $today;
  static $yesterday;

  if (!$today || !$yesterday) {
    $now = time();
    $today = phorge_date($now, $user);
    $yesterday = phorge_date($now - 86400, $user);
  }

  $date = phorge_date($epoch, $user);

  if ($date === $today) {
    return 'today';
  }

  if ($date === $yesterday) {
    return 'yesterday';
  }

  return (($on ? 'on ' : '').$date);
}

function phorge_time($epoch, $user) {
  $time_key = PhorgeTimeFormatSetting::SETTINGKEY;
  return phorge_format_local_time(
    $epoch,
    $user,
    $user->getUserSetting($time_key));
}

function phorge_dual_datetime($epoch, $user) {
  $screen_view = phorge_datetime($epoch, $user);
  $print_view = phorge_absolute_datetime($epoch, $user);

  $screen_tag = javelin_tag(
    'span',
    array(
      'print' => false,
    ),
    $screen_view);

  $print_tag = javelin_tag(
    'span',
    array(
      'print' => true,
    ),
    $print_view);

  return array(
    $screen_tag,
    $print_tag,
  );
}

function phorge_absolute_datetime($epoch, $user) {
  $format = 'Y-m-d H:i:s (\\U\\T\\CP)';

  $datetime = phorge_format_local_time($epoch, $user, $format);
  $datetime = preg_replace('/(UTC[+-])0?([^:]+)(:00)?/', '\\1\\2', $datetime);

  return $datetime;
}

function phorge_datetime($epoch, $user) {
  $time_key = PhorgeTimeFormatSetting::SETTINGKEY;
  return phorge_format_local_time(
    $epoch,
    $user,
    pht('%s, %s',
      phutil_date_format($epoch),
      $user->getUserSetting($time_key)));
}

function phorge_datetimezone($epoch, $user) {
  $datetime = phorge_datetime($epoch, $user);
  $timezone = phorge_format_local_time($epoch, $user, 'T');

  // Some obscure timezones just render as "+03" or "-09". Make these render
  // as "UTC+3" instead.
  if (preg_match('/^[+-]/', $timezone)) {
    $timezone = (int)trim($timezone, '+');
    if ($timezone < 0) {
      $timezone = pht('UTC-%s', $timezone);
    } else {
      $timezone = pht('UTC+%s', $timezone);
    }
  }

  return pht('%s (%s)', $datetime, $timezone);
}

/**
 * This function does not usually need to be called directly. Instead, call
 * @{function:phorge_date}, @{function:phorge_time}, or
 * @{function:phorge_datetime}.
 *
 * @param int Unix epoch timestamp.
 * @param PhorgeUser User viewing the timestamp.
 * @param string Date format, as per DateTime class.
 * @return string Formatted, local date/time.
 */
function phorge_format_local_time($epoch, $user, $format) {
  if (!$epoch) {
    // If we're missing date information for something, the DateTime class will
    // throw an exception when we try to construct an object. Since this is a
    // display function, just return an empty string.
    return '';
  }

  $user_zone = $user->getTimezoneIdentifier();

  static $zones = array();
  if (empty($zones[$user_zone])) {
    $zones[$user_zone] = new DateTimeZone($user_zone);
  }
  $zone = $zones[$user_zone];

  // NOTE: Although DateTime takes a second DateTimeZone parameter to its
  // constructor, it ignores it if the date string includes timezone
  // information. Further, it treats epoch timestamps ("@946684800") as having
  // a UTC timezone. Set the timezone explicitly after constructing the object.
  try {
    $date = new DateTime('@'.$epoch);
  } catch (Exception $ex) {
    // NOTE: DateTime throws an empty exception if the format is invalid,
    // just replace it with a useful one.
    throw new Exception(
      pht("Construction of a DateTime() with epoch '%s' ".
      "raised an exception.", $epoch));
  }

  $date->setTimezone($zone);

  return PhutilTranslator::getInstance()->translateDate($format, $date);
}
