<?php

/**
 * @file
 * Contains \Scitalk\composer\ScriptHandler.
 */

namespace Scitalk\composer;

use Composer\Script\Event;
use DrupalFinder\DrupalFinder;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

class ScriptHandler {

  public static function moveProfile(Event $event) {
    $fs = new Filesystem();
    $drupalFinder = new DrupalFinder();
    $drupalFinder->locateRoot(getcwd());
    $drupalRoot = $drupalFinder->getDrupalRoot();
    $event->getIO()->write("Mirror the scitalk profile to web/profiles");
    $fs->mirror('profiles/scitalk', $drupalRoot . '/profiles/scitalk');
    $event->getIO()->write("Completed scitalk mirror.");
  }

}
