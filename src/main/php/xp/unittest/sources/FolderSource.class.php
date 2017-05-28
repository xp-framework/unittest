<?php namespace xp\unittest\sources;

use io\Folder;
use lang\IllegalArgumentException;
use lang\ClassLoader;
use lang\FileSystemClassLoader;
use lang\reflect\Modifiers;
use unittest\TestCase;

/**
 * Source that loads tests from test case classes inside a folder and
 * its subfolders.
 */
class FolderSource extends AbstractSource {
  private $loader;
  
  /**
   * Constructor
   *
   * @param  io.Folder $folder
   * @throws lang.IllegalArgumentException if the given folder does not exist or isn't in class path
   */
  public function __construct(Folder $folder) {
    $path= $folder->getURI();
    foreach (ClassLoader::getLoaders() as $cl) {
      if ($cl instanceof FileSystemClassLoader && 0 === strncmp($cl->path, $path, strlen($cl->path))) {
        $this->loader= $cl;
        return;
      }
    }

    throw new IllegalArgumentException($folder->toString().($folder->exists()
      ? ' is not in class path'
      : ' does not exist'
    ));
  }

  /** @return iterable */
  private function classFilesIn(Folder $folder) {
    $e= -strlen(\xp::CLASS_FILE_EXT);
    foreach ($folder->entries() as $entry) {
      if ($entry->isFolder()) {
        foreach ($this->classFilesIn($entry->asFolder()) as $entry) {
          yield $entry;
        }
      } else if (0 === substr_compare($entry->name(), \xp::CLASS_FILE_EXT, $e)) {
        yield $entry;
      }
    }
  }

  /**
   * Provide tests to test suite
   *
   * @param  unittest.TestSuite $suite
   * @param  var[] $arguments
   * @return void
   * @throws lang.IllegalArgumentException if no tests are found
   */
  public function provideTo($suite, $arguments) {
    $empty= true;

    $l= strlen($this->loader->path);
    $e= -strlen(\xp::CLASS_FILE_EXT);
    foreach ($this->classFilesIn(new Folder($this->loader->path)) as $classFile) {
      $class= $this->loader->loadClass(strtr(substr($classFile, $l, $e), DIRECTORY_SEPARATOR, '.'));
      if ($class->isSubclassOf(TestCase::class) && !Modifiers::isAbstract($class->getModifiers())) {
        $suite->addTestClass($class, $arguments);
        $empty= false;
      }
    }

    if ($empty) {
      throw new IllegalArgumentException('Cannot find any test cases in '.$this->loader->toString());
    }
  }

  /**
   * Creates a string representation of this source
   *
   * @return string
   */
  public function toString() {
    return nameof($this).'['.$this->loader->toString().']';
  }
}
