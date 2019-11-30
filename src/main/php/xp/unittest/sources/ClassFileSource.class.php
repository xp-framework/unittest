<?php namespace xp\unittest\sources;

use io\File;
use lang\{ClassLoader, IllegalArgumentException};

/**
 * Source that load tests from a class filename
 */
class ClassFileSource extends AbstractSource {
  private $loader, $uri;
  
  /**
   * Constructor
   *
   * @param   io.File file
   * @throws  lang.IllegalArgumentException if the given file does not exist
   */
  public function __construct(File $file) {
    $uri= $file->getURI();
    $cl= ClassLoader::getDefault()->findUri($uri);
    if ($cl === null) {
      throw new IllegalArgumentException('File "'.$uri.($file->exists()
        ? '" is not in class path'
        : '" not found'
      ));
    }

    $this->loader= $cl;
    $this->uri= $uri;
  }

  /**
   * Provide tests to test suite
   *
   * @param  unittest.TestSuite $suite
   * @param  var[] $arguments
   * @return void
   */
  public function provideTo($suite, $arguments) {
    $suite->addTestClass($this->loader->loadUri($this->uri), $arguments);
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