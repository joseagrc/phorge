<?php

final class PhorgeSSHLog extends Phobject {

  private static $log;

  public static function getLog() {
    if (!self::$log) {
      $path = PhorgeEnv::getEnvConfig('log.ssh.path');
      $format = PhorgeEnv::getEnvConfig('log.ssh.format');
      $format = nonempty(
        $format,
        "[%D]\t%p\t%h\t%r\t%s\t%S\t%u\t%C\t%U\t%c\t%T\t%i\t%o");

      // NOTE: Path may be null. We still create the log, it just won't write
      // anywhere.

      $data = array(
        'D' => date('r'),
        'h' => php_uname('n'),
        'p' => getmypid(),
        'e' => time(),
        'I' => PhorgeEnv::getEnvConfig('cluster.instance'),
      );

      $sudo_user = PhorgeEnv::getEnvConfig('phd.user');
      if (phutil_nonempty_string($sudo_user)) {
        $data['S'] = $sudo_user;
      }

      if (function_exists('posix_geteuid')) {
        $system_uid = posix_geteuid();
        $system_info = posix_getpwuid($system_uid);
        $data['s'] = idx($system_info, 'name');
      }

      $client = getenv('SSH_CLIENT');
      if (strlen($client)) {
        $remote_address = head(explode(' ', $client));
        $data['r'] = $remote_address;
      }

      $log = id(new PhutilDeferredLog($path, $format))
        ->setFailQuietly(true)
        ->setData($data);

      self::$log = $log;
    }

    return self::$log;
  }

}
