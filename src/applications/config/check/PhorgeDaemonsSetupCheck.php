<?php

final class PhorgeDaemonsSetupCheck extends PhorgeSetupCheck {

  public function getDefaultGroup() {
    return self::GROUP_IMPORTANT;
  }

  protected function executeChecks() {

    try {
      $task_daemons = id(new PhorgeDaemonLogQuery())
        ->setViewer(PhorgeUser::getOmnipotentUser())
        ->withStatus(PhorgeDaemonLogQuery::STATUS_ALIVE)
        ->withDaemonClasses(array('PhorgeTaskmasterDaemon'))
        ->setLimit(1)
        ->execute();

      $no_daemons = !$task_daemons;
    } catch (Exception $ex) {
      // Just skip this warning if the query fails for some reason.
      $no_daemons = false;
    }

    if ($no_daemons) {
      $doc_href = PhorgeEnv::getDoclink('Managing Daemons with phd');

      $summary = pht(
        'You must start the daemons to send email, rebuild search indexes, '.
        'and do other background processing.');

      $message = pht(
        'The daemons are not running, background processing (including '.
        'sending email, rebuilding search indexes, importing commits, '.
        'cleaning up old data, and running builds) can not be performed.'.
        "\n\n".
        'Use %s to start daemons. See %s for more information.',
        phutil_tag('tt', array(), 'bin/phd start'),
        phutil_tag(
          'a',
          array(
            'href' => $doc_href,
            'target' => '_blank',
          ),
          pht('Managing Daemons with phd')));

      $this->newIssue('daemons.not-running')
        ->setShortName(pht('Daemons Not Running'))
        ->setName(pht('Daemons Are Not Running'))
        ->setSummary($summary)
        ->setMessage($message)
        ->addCommand('$ ./bin/phd start');
    }

    $expect_user = PhorgeEnv::getEnvConfig('phd.user');
    if (strlen($expect_user)) {

      try {
        $all_daemons = id(new PhorgeDaemonLogQuery())
          ->setViewer(PhorgeUser::getOmnipotentUser())
          ->withStatus(PhorgeDaemonLogQuery::STATUS_ALIVE)
          ->execute();
      } catch (Exception $ex) {
        // If this query fails for some reason, just skip this check.
        $all_daemons = array();
      }

      foreach ($all_daemons as $daemon) {
        $actual_user = $daemon->getRunningAsUser();
        if ($actual_user == $expect_user) {
          continue;
        }

        $summary = pht(
          'At least one daemon is currently running as the wrong user.');

        $message = pht(
          'A daemon is running as user %s, but daemons should be '.
          'running as %s.'.
          "\n\n".
          'Either adjust the configuration setting %s or restart the '.
          'daemons. Daemons should attempt to run as the proper user when '.
          'restarted.',
          phutil_tag('tt', array(), $actual_user),
          phutil_tag('tt', array(), $expect_user),
          phutil_tag('tt', array(), 'phd.user'));

        $this->newIssue('daemons.run-as-different-user')
          ->setName(pht('Daemon Running as Wrong User'))
          ->setSummary($summary)
          ->setMessage($message)
          ->addPhorgeConfig('phd.user')
          ->addCommand('$ ./bin/phd restart');

        break;
      }
    }
  }

}
