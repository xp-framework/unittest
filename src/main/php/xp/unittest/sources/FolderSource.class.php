<?php namespace xp\unittest\sources;

use io\Folder;
use io\collections\FileCollection;
use io\collections\iterate\FilteredIOCollectionIterator;
use io\collections\iterate\ExtensionEqualsFilter;
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

  /**
   * Provide tests to test suite
   *
   * @param  unittest.TestSuite $suite
   * @param  var[] $arguments
   * @return void
   * @throws lang.IllegalArgumentException if no tests are found
   */
  public function provideTo($suite, $arguments) {
    $it= new FilteredIOCollectionIterator(
      new FileCollection($this->loader->path),
      new ExtensionEqualsFilter(\xp::CLASS_FILE_EXT),
      true  // recursive
    );

    $l= strlen($this->loader->path);
    $e= -strlen(\xp::CLASS_FILE_EXT);
    $empty= true;
    foreach ($it as $element) {
      $class= $this->loader->loadClass(strtr(substr($element->getUri(), $l, $e), DIRECTORY_SEPARATOR, '.'));
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
