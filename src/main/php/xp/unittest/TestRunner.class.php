<?php namespace xp\unittest;

use io\streams\{FileOutputStream, InputStream, OutputStream, StringReader, StringWriter};
use io\{File, Folder};
use lang\reflect\{Package, TargetInvocationException};
use lang\{Environment, IllegalArgumentException, MethodNotImplementedException, Throwable, XPClass};
use unittest\{ColorizingListener, TestSuite};
use util\cmd\Console;
use util\{NoSuchElementException, Properties};
use xp\unittest\sources\{ClassFileSource, ClassSource, EvaluationSource, FolderSource, PackageSource, PropertySource};

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
 * - Run a single test method, here `verify()`.
 *   ```sh
 *   $ xp test com.example.unittest.VerifyItWorks::verify
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
 * The `-q` option suppresses all output, `-o` *compact|quiet|verbose|bar*
 * selects output. By default, all test methods are run. To interrupt this,
 * use `-s` *fail|ignore|skip*. Arguments to tests can be passed by supplying
 * one or more `-a` *{value}*.
 *
 * Supports user preferences in **test.ini** in the environment's config dir,
 * (~/.xp or $XDG_CONFIG_DIR/xp on Un*x, %APPDATA%\Xp on Windows), which
 * may contain the following configuration options and values:
 * `
 *   output=compact|verbose|quiet|bar
 *   colors=on|off|auto
 *   stop=never|fail[,skip[,ignore]]
 * `
 * Preferences are overidden by their respective command line options.
 *
 * The exit code is **0** when all tests succeed, nonzero otherwise.
 */
class TestRunner {
  protected $in, $out, $err;

  private static $colors= [
    ''     => null,
    'on'   => true,
    'off'  => false,
    'auto' => null
  ];

  private static $stop= [
    'never'  => 0,
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
   * Displays usage and preferences
   *
   * @return  int exitcode
   */
  protected function usage() {
    $this->err->writeLine('Runs unittests: `xp test [tests]`. xp help test has the details!');

    $ini= new File(Environment::configDir('xp'), 'test.ini');
    if ($ini->exists()) {
      $this->err->writeLine();
      $this->err->writeLine('Preferences via ', $ini);
      foreach ($this->preferences() as $key => $value) {
        $this->err->writeLine('* ', $key, '=', $value);
      }
    } else {
      $this->err->writeLine('No user preferences (searched for ', $ini->getURI(), ')');
    }
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
    if ('-' === $in) {
      return Console::$out;
    } else {
      return new StringWriter(new FileOutputStream($in));
    }
  }

  /**
   * Returns user preferences stored in `~/.xp/test.ini`
   *
   * @return [:var]
   */
  protected function preferences() {
    $ini= new File(Environment::configDir('xp'), 'test.ini');
    if (!$ini->exists()) return [];

    $p= new Properties();
    $p->load($ini);
    return $p->readSection(null);
  }

  /**
   * Runs suite
   *
   * @param  string[] $args
   * @return int exitcode
   */
  public function run(array $args) {
    if (empty($args)) return $this->usage();

    $preferences= $this->preferences();
    $output= TestListeners::named($preferences['output'] ?? 'default');
    $colors= self::$colors[$preferences['colors'] ?? 'auto'];
    $stop= $preferences['stop'] ?? null;

    // Parse arguments
    $suite= new TestSuite();
    $sources= [];
    $arguments= [];
    try {
      for ($i= 0, $s= sizeof($args); $i < $s; $i++) {
        if ('-?' === $args[$i] || '--help' === $args[$i]) {
          return $this->usage();
        } else if ('-q' === $args[$i]) {
          $output= TestListeners::$QUIET;
        } else if ('-o' === $args[$i]) {
          $output= TestListeners::named($this->arg($args, ++$i, 'o'));
        } else if ('-e' === $args[$i]) {
          $arg= ++$i < $s ? $args[$i] : '-';
          if ('-' === $arg) {
            $sources[]= new EvaluationSource($this->in);
          } else {
            $sources[]= new EvaluationSource($this->arg($args, $i, 'e'));
          }
        } else if ('-l' === $args[$i]) {
          $arg= $this->arg($args, ++$i, 'l');
          $class= XPClass::forName(strstr($arg, '.') ? $arg : 'xp.unittest.'.ucfirst($arg).'Listener');
          $arg= $this->arg($args, ++$i, 'l');
          if ('-?' === $arg || '--help' === $arg) return $this->listenerUsage($class);

          $instance= $suite->addListener($class->newInstance($this->streamWriter($arg)));

          // Get all @arg-annotated methods
          $options= [];
          foreach ($class->getMethods() as $method) {
            if ($method->hasAnnotation('arg')) {
              $arg= $method->getAnnotation('arg');
              if (isset($arg['position'])) {
                $options[$arg['position']]= $method;
              } else {
                $name= $arg['name'] ?? strtolower(preg_replace('/^set/', '', $method->getName()));
                $short= $arg['short'] ?? $name[0];
                $options[$name]= $options[$short]= $method;
              }
            }
          }

          // ...and pass arguments to them
          $option= 0;
          while ('-o' === ($args[++$i] ?? null)) {
            if (isset($options[$option])) {
              $method= $options[$option++];
            } else {
              $name= $this->arg($args, ++$i, 'o');
              if (!isset($options[$name])) {
                $this->err->writeLine('*** Unknown listener argument '.$name.' to '.nameof($instance));
                return 2;
              }
              $method= $options[$name];
            }
            $pass= $method->numParameters() ? $this->arg($args, ++$i, 'o') : [];
            try {
              $method->invoke($instance, $pass);
            } catch (TargetInvocationException $e) {
              $this->err->writeLine('*** Error for argument '.$name.' to '.nameof($instance).': '.$e->getCause()->toString());
              return 2;
            }
          }
          $i--;
        } else if ('-a' === $args[$i]) {
          $arguments[]= $this->arg($args, ++$i, 'a');
        } else if ('-s' === $args[$i]) {
          $stop= $this->arg($args, ++$i, 's');
        } else if ('-v' === $args[$i]) {
          $output= TestListeners::$VERBOSE;
        } else if ('--color' === substr($args[$i], 0, 7)) {
          $remainder= (string)substr($args[$i], 8);
          if (!array_key_exists($remainder, self::$colors)) {
            throw new IllegalArgumentException('Unsupported argument for --color (must be <empty>, "on", "off", "auto" (default))');
          }
          $colors= self::$colors[$remainder];
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

    // Setup output
    $l= $suite->addListener($output->newInstance($this->out));
    if ($l instanceof ColorizingListener) {
      $l->setColor($colors);
    }

    // Check on which events to stop
    if ($stop) {
      $events= 0;
      foreach (explode(',', $stop) as $event) {
        $event= trim($event);
        if (isset(self::$stop[$event])) {
          $events |= self::$stop[$event];
        } else {
          $this->err->writeLine('*** Unknown value for -s (must be one of '.implode(', ', array_keys(self::$stop)).')');
          return 2;
        }
      }
      $events && $suite->addListener(new StopListener($events));
    }

    // Gather tests from the provided sources
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
    if (0 === $suite->numTests()) return 3;

    // Run it!
    return $suite->run()->failureCount() > 0 ? 1 : 0;
  }

  /**
   * Main runner method
   *
   * @param  string[] args
   * @return int
   */
  public static function main(array $args) {
    return (new self())->run($args);
  }    
}