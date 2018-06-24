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
  private $loader, $package;
  
  /**
   * Constructor
   *
   * @param  io.Folder $folder
   * @throws lang.IllegalArgumentException if the given folder does not exist or isn't in class path
   */
  public function __construct(Folder $folder) {
    $path= $folder->getURI();
    foreach (ClassLoader::getLoaders() as $cl) {
      $l= strlen($cl->path);
      if ($cl instanceof FileSystemClassLoader && 0 === strncmp($cl->path, $path, $l)) {
        $this->loader= $cl;
        $this->package= rtrim(strtr(substr($path, $l), [DIRECTORY_SEPARATOR => '.']), '.');
        return;
      }
    }

    throw new IllegalArgumentException($folder->toString().($folder->exists()
      ? ' is not in class path'
      : ' does not exist'
    ));
  }

  /** @return iterable */
  private function classesIn($package) {
    $e= -strlen(\xp::CLASS_FILE_EXT);
    $p= $package ? $package.'.' : '';
    foreach ($this->loader->packageContents($package) as $file) {
      if (0 === substr_compare($file, \xp::CLASS_FILE_EXT, $e)) {
        yield $this->loader->loadClass($p.substr($file, 0, $e));
      } else if ('/' === $file{strlen($file) - 1}) {
        foreach ($this->classesIn($p.substr($file, 0, -1)) as $class) {
          yield $class;
        }
      }
    }
  }

  /** @return iterable */
  public function classes() {
    return $this->classesIn($this->package);
  }

  /**
   * Creates a string representation of this source
   *
   * @return string
   */
  public function toString() {
    return nameof($this).'['.$this->loader->toString().($this->package ? ', package '.$this->package : '').']';
  }
}
