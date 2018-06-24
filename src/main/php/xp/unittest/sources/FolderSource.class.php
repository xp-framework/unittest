<?php namespace xp\unittest\sources;

use io\Folder;
use lang\ClassLoader;
use lang\FileSystemClassLoader;
use lang\IllegalArgumentException;

/**
 * Source that loads tests from test case classes inside a folder and
 * its subfolders.
 *
 * @test  xp://unittest.tests.FolderSourceTest
 */
class FolderSource extends ClassesSource {
  private $folder;
  
  /**
   * Constructor
   *
   * @param  io.Folder $folder
   * @throws lang.IllegalArgumentException if the given folder does not exist or isn't in class path
   */
  public function __construct(Folder $folder) {
    if (!$folder->exists()) {
      throw new IllegalArgumentException($folder->toString().' does not exist');
    }

    $path= $folder->getURI();
    foreach (ClassLoader::getLoaders() as $cl) {
      $l= strlen($cl->path);
      if ($cl instanceof FileSystemClassLoader && 0 === strncmp($cl->path, $path, $l)) {
        $this->folder= $folder;
        return;
      }
    }

    throw new IllegalArgumentException($folder->toString().' is not in class path');
  }

  /** @return iterable */
  private function classesIn($folder) {
    $cl= ClassLoader::getDefault();
    foreach ($folder->entries() as $entry) {
      $name= $entry->name();

      // Must begin with an uppercase letter, and end with ".php"
      if (1 === strspn($name, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ') && 0 === substr_compare($name, '.php', -4)) {
        $uri= $entry->asURI();
        if ($loader= $cl->findUri($uri)) {
          yield $loader->loadUri($uri);
        }
      } else if ($entry->isFolder()) {
        foreach ($this->classesIn($entry->asFolder()) as $class) {
          yield $class;
        }
      }
    }
  }

  /** @return iterable */
  protected function classes() {
    return $this->classesIn($this->folder);
  }

  /**
   * Creates a string representation of this source
   *
   * @return string
   */
  public function toString() {
    return nameof($this).'['.$this->folder->toString().']';
  }
}
