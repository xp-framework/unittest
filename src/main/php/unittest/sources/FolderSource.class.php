<?php namespace xp\unittest\sources;

use io\Folder;
use io\collections\FileCollection;
use io\collections\iterate\FilteredIOCollectionIterator;
use io\collections\iterate\ExtensionEqualsFilter;
use lang\IllegalArgumentException;
use lang\XPClass;
use lang\reflect\Modifiers;

/**
 * Source that loads tests from test case classes inside a folder and
 * its subfolders.
 */
class FolderSource extends AbstractSource {
  protected $folder= null;
  
  /**
   * Constructor
   *
   * @param   io.Folder folder
   * @throws  lang.IllegalArgumentException if the given folder does not exist
   */
  public function __construct(Folder $folder) {
    if (!$folder->exists()) {
      throw new IllegalArgumentException('Folder "'.$folder->getURI().'" does not exist!');
    }
    $this->folder= $folder;
  }

  /**
   * Find first classloader responsible for a given path
   *
   * @param   string path
   * @return  lang.IClassLoader
   */
  protected function findLoaderFor($path) {
    foreach (\lang\ClassLoader::getLoaders() as $cl) {
      if (
        $cl instanceof \lang\FileSystemClassLoader &&
        0 === strncmp($cl->path, $path, strlen($cl->path))
      ) return $cl;
    }
    return null;      
  }

  /**
   * Get all test cases
   *
   * @param   var[] arguments
   * @return  unittest.TestCase[]
   */
  public function testCasesWith($arguments) {
    if (null === ($cl= $this->findLoaderFor($this->folder->getURI()))) {
      throw new IllegalArgumentException($this->folder->toString().' is not in class path');
    }
    $l= strlen($cl->path);
    $e= -strlen(\xp::CLASS_FILE_EXT);

    $it= new FilteredIOCollectionIterator(
      new FileCollection($this->folder),
      new ExtensionEqualsFilter(\xp::CLASS_FILE_EXT),
      true  // recursive
    );
    $cases= [];
    foreach ($it as $element) {
      $name= strtr(substr($element->getUri(), $l, $e), DIRECTORY_SEPARATOR, '.');
      $class= XPClass::forName($name);
      if (
        !$class->isSubclassOf('unittest.TestCase') ||
        Modifiers::isAbstract($class->getModifiers())
      ) continue;

      $cases= array_merge($cases, $this->testCasesInClass($class, $arguments));
    }

    if (empty($cases)) {
      throw new IllegalArgumentException('Cannot find any test cases in '.$this->folder->toString());
    }
    return $cases;
  }

  /**
   * Creates a string representation of this source
   *
   * @return  string
   */
  public function toString() {
    return nameof($this).'['.$this->folder->toString().']';
  }
}
