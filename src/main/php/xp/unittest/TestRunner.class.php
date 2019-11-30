<?php namespace xp\unittest;

use io\File;
use io\Folder;
use io\streams\FileOutputStream;
use io\streams\InputStream;
use io\streams\OutputStream;
use io\streams\Streams;
use io\streams\StringReader;
use io\streams\StringWriter;
use lang\ClassLoader;
use lang\IllegalArgumentException;
use lang\MethodNotImplementedException;
use lang\Throwable;
use lang\XPClass;
use lang\reflect\Package;
use lang\reflect\TargetInvocationException;
use unittest\ColorizingListener;
use unittest\TestSuite;
use util\NoSuchElementException;
use util\Properties;
use util\cmd\Console;
use xp\unittest\sources\ClassFileSource;
use xp\unittest\sources\ClassSource;
use xp\unittest\sources\EvaluationSource;
use xp\unittest\sources\FolderSource;
use xp\unittest\sources\PackageSource;
use xp\unittest\sources\PropertySource;

/**
 * Runs unittests and displays a summary
 * ========================================================================
 *
 * - Run all tests inside the given directory
 *   ```sh
 *   $ xp test src/test/php
 *   ```
 * - Run test classes inside a given package
 *   ```sh
 *   $ xp test com.example.unittest.**
 *   ```
 * - Run a single test class
 *   ```sh
 *   $ xp test com.example.unittest.VerifyItWorks
 *   ```
 * - Run a single test file
 *   ```sh
 *   $ xp test Test.class.php
 *   ```
 * - Evaluates test code directly from the command line
 *   ```sh
 *   $ xp test -e '$this->assertEquals(3, sizeof([1, 2, 3]);'
 *   ```
 * - Run indefinitely, watching the current directory for changes:
 *   ```sh
 *   $ xp -watch . test src/test/php
 *   ```
 *
 * The `-q` option suppresses all output, `-v` is more verbose. By default,
 * all test methods are run. To interrupt this, use `-s` *fail|ignore|skip*.
 * Arguments to tests can be passed by supplying on ore more `-a` *{value}*.
 *
 * The exit code is **0** when all tests succeed, nonzero otherwise.
 */
class TestRunner {
  protected $in, $out, $err;

  private static $cmap= [
    ''      => null,
    '=on'   => true,
    '=off'  => false,
    '=auto' => null
  ];

  private static $stop= [
    'fail'   => StopListener::FAIL,
    'skip'   => StopListener::SKIP,
    'ignore' => StopListener::IGNORE
  ];

  /**
   * Constructor. Initializes in, out and err members to console
   */
  public function __construct() {
    $this->in= Console::$in;
    $this->out= Console::$out;
    $this->err= Console::$err;
  }

  /**
   * Reassigns standard input stream
   *
   * @param   io.streams.InputStream out
   * @return  io.streams.InputStream the given output stream
   */
  public function setIn(InputStream $in) {
    $this->in= new StringReader($in);
    return $in;
  }

  /**
   * Reassigns standard output stream
   *
   * @param   io.streams.OutputStream out
   * @return  io.streams.OutputStream the given output stream
   */
  public function setOut(OutputStream $out) {
    $this->out= new StringWriter($out);
    return $out;
  }

  /**
   * Reassigns standard error stream
   *
   * @param   io.streams.OutputStream error
   * @return  io.streams.OutputStream the given output stream
   */
  public function setErr(OutputStream $err) {
    $this->err= new StringWriter($err);
    return $err;
  }

  /**
   * Converts api-doc "markup" to plain text w/ ASCII "art"
   *
   * @param   string markup
   * @return  string text
   */
  protected function textOf($markup) {
    $line= str_repeat('=', 72);
    return strip_tags(preg_replace(
      ['#```([a-z]*)#', '#```#', '#^\- #'],
      [$line, $line, '* '],
      trim($markup)
    ));
  }

  /**
   * Displays usage
   *
   * @return  int exitcode
   */
  protected function usage() {
    $this->err->writeLine('Runs unittests: `xp test [tests]`. xp help test has the details!');
    return 2;
  }

  /**
   * Displays listener usage
   *
   * @return  int exitcode
   */
  protected function listenerUsage($listener) {
    $this->err->writeLine($this->textOf($listener->getComment()));
    $positional= $options= [];
    foreach ($listener->getMethods() as $method) {
      if ($method->hasAnnotation('arg')) {
        $arg= $method->getAnnotation('arg');
        $name= strtolower(preg_replace('/^set/', '', $method->getName()));
        if (isset($arg['position'])) {
          $positional[$arg['position']]= $name;
          $options['<'.$name.'>']= $method;
        } else {
          $name= $arg['name'] ?? $name;
          $short= $arg['short'] ?? $name[0];
          $param= ($method->numParameters() > 0 ? ' <'.$method->getParameter(0)->getName().'>' : '');
          $options[$name.'|'.$short.$param]= $method;
        }
      }
    }
    $this->err->writeLine();
    $this->err->write('Usage: -l ', $listener->getName(), ' ');
    ksort($positional);
    foreach ($positional as $name) {
      $this->err->write('-o <', $name, '> ');
    }
    if ($options) {
      $this->err->writeLine('[options]');
      $this->err->writeLine();
      foreach ($options as $name => $method) {
        $this->err->writeLine('  * -o ', $name, ': ', self::textOf($method->getComment()));
      }
    } else {
      $this->err->writeLine();
    }
    return 2;
  }

  /**
   * Gets an argument
   *
   * @param   string[] args
   * @param   int offset
   * @param   string option
   * @return  string
   * @throws  lang.IllegalArgumentException if no argument exists by this offset
   */
  protected function arg($args, $offset, $option) {
    if (!isset($args[$offset])) {
      throw new IllegalArgumentException('Option -'.$option.' requires an argument');
    }
    return $args[$offset];
  }
  
  /**
   * Returns an output stream writer for a given file name.
   *
   * @param   string in
   * @return  io.streams.OutputStreamWriter
   */
  protected function streamWriter($in) {
    if ('-' == $in) {
      return Console::$out;
    } else {
      return new StringWriter(new FileOutputStream($in));
    }
  }
  
  /**
   * Runs suite
   *
   * @param   string[] args
   * @return  int exitcode
   */
  public function run(array $args) {
    if (!$args) return $this->usage();

    // Setup suite
    $suite= new TestSuite();

    // Parse arguments
    $sources= [];
    $listener= TestListeners::$DEFAULT;
    $arguments= [];
    $colors= null;
    $stop= 0;

    try {
      for ($i= 0, $s= sizeof($args); $i < $s; $i++) {
        if ('-v' == $args[$i]) {
          $listener= TestListeners::$VERBOSE;
        } else if ('-q' == $args[$i]) {
          $listener= TestListeners::$QUIET;
        } else if ('-e' == $args[$i]) {
          $arg= ++$i < $s ? $args[$i] : '-';
          if ('-' === $arg) {
            $sources[]= new EvaluationSource(Streams::readAll($this->in->getStream()));
          } else {
            $sources[]= new EvaluationSource($this->arg($args, $i, 'e'));
          }
        } else if ('-l' == $args[$i]) {
          $arg= $this->arg($args, ++$i, 'l');
          $class= XPClass::forName(strstr($arg, '.') ? $arg : 'xp.unittest.'.ucfirst($arg).'Listener');
          $arg= $this->arg($args, ++$i, 'l');
          if ('-?' == $arg || '--help' == $arg) {
            return $this->listenerUsage($class);
          }
          $output= $this->streamWriter($arg);
          $instance= $suite->addListener($class->newInstance($output));

          // Get all @arg-annotated methods
          $options= [];
          foreach ($class->getMethods() as $method) {
            if ($method->hasAnnotation('arg')) {
              $arg= $method->getAnnotation('arg');
              if (isset($arg['position'])) {
                $options[$arg['position']]= $method;
              } else {
                $name= $arg['name'] ?? strtolower(preg_replace('/^set/', '', $method->getName()));
                $short= $arg['short'] ?? $name{0};
                $options[$name]= $options[$short]= $method;
              }
            }
          }
          $option= 0;
        } else if ('-o' == $args[$i]) {
          if (isset($options[$option])) {
            $name= '#'.($option+ 1);
            $method= $options[$option];
          } else {
            $name= $this->arg($args, ++$i, 'o');
            if (!isset($options[$name])) {
              $this->err->writeLine('*** Unknown listener argument '.$name.' to '.nameof($instance));
              return 2;
            }
            $method= $options[$name];
          }
          $option++;
          if (0 == $method->numParameters()) {
            $pass= [];
          } else {
            $pass= $this->arg($args, ++$i, 'o '.$name);
          }
          try {
            $method->invoke($instance, $pass);
          } catch (TargetInvocationException $e) {
            $this->err->writeLine('*** Error for argument '.$name.' to '.nameof($instance).': '.$e->getCause()->toString());
            return 2;
          }
        } else if ('-?' == $args[$i] || '--help' == $args[$i]) {
          return $this->usage();
        } else if ('-a' == $args[$i]) {
          $arguments[]= $this->arg($args, ++$i, 'a');
        } else if ('-w' == $args[$i]) {
          $this->arg($args, ++$i, 'w');
        } else if ('-s' == $args[$i]) {
          $argument= $this->arg($args, ++$i, 's');
          if (isset(self::$stop[$argument])) {
            $stop |= self::$stop[$argument];
          } else {
            $this->err->writeLine('*** Unknown value for -s (must be one of '.implode(', ', array_keys(self::$stop)).')');
            return 2;
          }
        } else if ('--color' == substr($args[$i], 0, 7)) {
          $remainder= (string)substr($args[$i], 7);
          if (!array_key_exists($remainder, self::$cmap)) {
            throw new IllegalArgumentException('Unsupported argument for --color (must be <empty>, "on", "off", "auto" (default))');
          }
          $colors= self::$cmap[$remainder];
        } else if (strstr($args[$i], '.ini')) {
          $sources[]= new PropertySource(new Properties($args[$i]));
        } else if (strstr($args[$i], '.**')) {
          $sources[]= new PackageSource(Package::forName(substr($args[$i], 0, -3)), true);
        } else if (strstr($args[$i], '.*')) {
          $sources[]= new PackageSource(Package::forName(substr($args[$i], 0, -2)));
        } else if (false !== ($p= strpos($args[$i], '::'))) {
          $sources[]= new ClassSource(XPClass::forName(substr($args[$i], 0, $p)), substr($args[$i], $p+ 2));
        } else if (is_file($args[$i])) {
          $sources[]= new ClassFileSource(new File($args[$i]));
        } else if (is_dir($args[$i])) {
          $sources[]= new FolderSource(new Folder($args[$i]));
        } else {
          $sources[]= new ClassSource(XPClass::forName($args[$i]));
        }
      }
    } catch (Throwable $e) {
      $this->err->writeLine('*** ', $e->getMessage());
      \xp::gc();
      return 2;
    }
    
    if (empty($sources)) {
      $this->err->writeLine('*** No tests specified');
      return 2;
    }
    
    // Set up suite
    $l= $suite->addListener($listener->newInstance($this->out));
    if ($l instanceof ColorizingListener) {
      $l->setColor($colors);
    }

    if ($stop) {
      $suite->addListener(new StopListener($stop));
    }

    foreach ($sources as $source) {
      try {
        $source->provideTo($suite, $arguments);
      } catch (NoSuchElementException $e) {
        $this->err->writeLine('*** Warning: ', $e->getMessage());
        continue;
      } catch (IllegalArgumentException $e) {
        $this->err->writeLine('*** Error: ', $e->getMessage());
        return 2;
      } catch (MethodNotImplementedException $e) {
        $this->err->writeLine('*** Error: ', $e->getMessage(), ': ', $e->method, '()');
        return 2;
      }
    }
    
    // Run it!
    if (0 == $suite->numTests()) {
      return 3;
    } else {
      return $suite->run()->failureCount() > 0 ? 1 : 0;
    }
  }

  /**
   * Main runner method
   *
   * @param   string[] args
   */
  public static function main(array $args) {
    return (new self())->run($args);
  }    
}
