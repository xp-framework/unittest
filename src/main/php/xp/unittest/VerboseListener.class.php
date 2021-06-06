<?php namespace xp\unittest;

use io\File;
use io\streams\{LinesIn, OutputStreamWriter};
use unittest\{Listener, ColorizingListener, TestStart, Warnings};

/**
 * Verbose listener
 * ----------------
 * Shows details for all tests (succeeded, failed and skipped/ignored).
 * This listener has no options.
 */
class VerboseListener implements Listener, ColorizingListener {
  use Colors;

  const CONTEXT = 4;

  public $out= null;
  private $container= null;
  private $results= [];
  private $success= true;
  
  /**
   * Constructor
   *
   * @param   io.streams.OutputStreamWriter out
   */
  public function __construct(OutputStreamWriter $out) {
    $this->out= $out;
  }

  /** Writes progress indicator */
  private function progress() {
    static $chars= ['⣾', '⣽', '⣻', '⢿', '⡿', '⣟', '⣯', '⣷'];
    static $pos= 0;

    $c= $chars[++$pos] ?? $chars[$pos= 0];
    $this->out->write(str_repeat("\010", strlen($c)), $c);
  }

  /** Writes summary of the current container */
  private function summarize() {
    if ($this->success) {
      $format= $this->colored ? "\r> \033[42;1;37m PASS \033[0m \033[37m%s\033[0m" : "\r> [ PASS ] %s";
    } else {
      $format= $this->colored ? "\r> \033[41;1;37m FAIL \033[0m \033[37m%s\033[0m" : "\r> [ FAIL ] %s";
    }

    $this->out->writeLinef($format, $this->container);
    foreach ($this->results as $result) {
      if ($result instanceof \unittest\TestSuccess) {
        $format= $this->colored ? "  \033[32m✓\033[0m %s" : '  ✓ %s';
      } else if ($result instanceof \unittest\TestFailure) {
        $format= $this->colored ? "  \033[31m⨯\033[0m %s" : '  ⨯ %s';
      } else {
        $format= $this->colored ? "  \033[36m⦾\033[0m %s" : '  ⦾ %s';
      }
      $this->out->writeLinef($format, $result->test()->name());
    }

    $this->out->writeLine('  ');
  }

  /** Minimalistic PHP syntax highlighting */
  private function highlight($code) {
    if (!$this->colored) return $code;

    return preg_replace(
      [
        '/[(){}\[\]+*-\/=<>?:-]+/',
        '/\$[a-z0-9_]+/i',
        '/\b(public|private|protected|static|function|fn|match|if|else|switch|case|class|new|throw|return)\b/'
      ],
      [
        "\033[34;1m\$0\033[0;37m",
        "\033[35;1m\$0\033[0;37m",
        "\033[34;3m\$0\033[0;37m"
      ],
      $code
    );
  }

  /** Shortens path according to the current platform */
  private function path($dir) {
    $cwd= getcwd();
    $replace= [$cwd => '.', dirname($cwd) => '..'];
    $windows= 0 === strncasecmp('Win', PHP_OS, 3);

    if (!$windows) {
      $separator= '/';
      $replace+= [getenv('HOME') => '~'];
    } else if ($home= getenv('HOME')) {
      $separator= '/';
      $replace+= [getenv('HOME') => '~', getenv('APPDATA') => '$APPDATA', getenv('USERPROFILE') => '$USERPROFILE'];
    } else {
      $separator= '\\';
      $replace+= [getenv('APPDATA') => '%APPDATA%', getenv('USERPROFILE') => '%USERPROFILE%'];
    }

    // Short-circuit paths without directory
    if (strcspn($dir, '/\\') === strlen($dir)) return '.'.$separator.$dir;

    // Compare expanded paths against replace using case-insensitivity on Windows
    $prefix= $windows ? 'stripos' : 'strpos';
    $expand= function($path) {
      return realpath($path) ?: rtrim(strtr($path, '/\\', DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR);
    };

    $path= $expand($dir);
    foreach ($replace as $base => $with) {
      if (0 === $prefix($path, $expand($base))) {
        $path= $with.substr($path, strlen($base));
        break;
      }
    }
    return strtr($path, DIRECTORY_SEPARATOR, $separator);
  }

  /** Writes traced origin for failed test */
  private function trace($file, $line) {
    $this->out->writeLinef(
      $this->colored ? "  @\033[32m%s\033[0m:%d" : '  @%s:%d',
      $this->path($file),
      $line
    );

    // Show code
    $n= 0;
    foreach (new LinesIn(new File(fopen($file, 'rb'))) as $l) {
      $n++;
      if ($n < $line - self::CONTEXT) continue;
      if ($n > $line + self::CONTEXT) break;
      if ($n === $line) {
        $this->out->writeLinef(
          $this->colored ? "  \033[31m➜\033[0m \033[37m%4d\033[0m▕ \033[37m%s\033[0m" : '  ➜ %4d▕ %s',
          $n,
          $this->highlight($l)
        );
      } else {
        $this->out->writeLinef(
          $this->colored ? "    %4d▕ \033[37m%s\033[0m" : '    %4d▕ %s',
          $n,
          $this->highlight($l)
        );
      }
    }
    $this->out->writeLine();
  }

  /**
   * Called when a test case starts.
   *
   * @param  unittest.TestStart $start
   */
  public function testStarted(TestStart $start) {
    $container= $start->test()->container();
    if (null === $this->container) {
      $this->container= $container;
    } else if ($this->container !== $container) {
      $this->summarize();
      $this->container= $container;
      $this->results= [];
      $this->success= true;
    }
  }

  /**
   * Called when a test fails.
   *
   * @param   unittest.TestFailure failure
   */
  public function testFailed(\unittest\TestFailure $failure) {
    $this->progress();
    $this->results[]= $failure;
    $this->success= false;
  }

  /**
   * Called when a test errors.
   *
   * @param   unittest.TestError error
   */
  public function testError(\unittest\TestError $error) {
    $this->progress();
    $this->results[]= $error;
    $this->success= false;
  }

  /**
   * Called when a test raises warnings.
   *
   * @param   unittest.TestWarning warning
   */
  public function testWarning(\unittest\TestWarning $warning) {
    $this->progress();
    $this->results[]= $warning;
    $this->success= false;
  }
  
  /**
   * Called when a test finished successfully.
   *
   * @param   unittest.TestSuccess success
   */
  public function testSucceeded(\unittest\TestSuccess $success) {
    $this->progress();
    $this->results[]= $success;
  }
  
  /**
   * Called when a test is not run because it is skipped due to a 
   * failed prerequisite.
   *
   * @param   unittest.TestSkipped skipped
   */
  public function testSkipped(\unittest\TestSkipped $skipped) {
    $this->progress();
    $this->results[]= $skipped;
  }

  /**
   * Called when a test is not run because it has been ignored by using
   * the @ignore annotation.
   *
   * @param   unittest.TestSkipped ignore
   */
  public function testNotRun(\unittest\TestSkipped $ignore) {
    $this->progress();
    $this->results[]= $ignore;
  }

  /**
   * Called when a test run starts.
   *
   * @param   unittest.TestSuite suite
   */
  public function testRunStarted(\unittest\TestSuite $suite) {
    $this->out->writeLine('Running ', $suite->numTests(), ' test(s)...');
    $this->out->writeLine();
  }
  
  /**
   * Called when a test run finishes.
   *
   * @param   unittest.TestSuite suite
   * @param   unittest.TestResult result
   * @param  unittest.StopTests $stop
   */
  public function testRunFinished(\unittest\TestSuite $suite, \unittest\TestResult $result, \unittest\StopTests $stopped= null) {
    $this->summarize();

    // Show details for failed tests
    if ($result->failureCount() > 0) {
      foreach ($result->failed as $outcome) {
        $this->out->writeLinef($this->colored ? "\033[31m⨯ %s\033[0m" : '⨯ %s', $outcome->test()->getName(true));
        $this->out->writeLinef($this->colored ? "\033[37m  %s\033[0m" : '  %s', $outcome->reason->compoundMessage());

        // If any warnings have occurred, add them to the output, they may
        // help identify the cause.
        foreach (Warnings::raised() as $raised) {
          $this->out->writeLine('  ', $raised[Warnings::MESSAGE]);
        }

        // Trace this error back to its origin and show the source code
        // location plus a couple of lines of context.
        $this->out->writeLine();
        $this->trace(...$outcome->source());
      }
    }

    // Test counts and metrics
    $counts= '';
    if ($result->failureCount() > 0) {
      $counts.= sprintf($this->colored ? ", \033[31m%d failed\033[0m" : ', %d failed', $result->failureCount());
    }
    if ($result->successCount() > 0) {
      $counts.= sprintf($this->colored ? ", \033[32m%d passed\033[0m" : ', %d passed', $result->successCount());
    }
    if ($result->skipCount() > 0) {
      $counts.= sprintf($this->colored ? ", \033[36m%d skipped\033[0m" : ', %d skipped', $result->skipCount());
    }
    if ($stopped) {
      $counts.= sprintf($this->colored ? ", \033[33mstopped\033[0m" : ', stopped');
    }
    $this->out->writeLinef(
      $this->colored ? "\033[37mTests:\033[0m%s%s" : '  Tests:%s%s',
      str_repeat(' ', 12 - strlen('Tests')),
      substr($counts, 2)
    );

    foreach ($result->metrics() as $name => $metric) {
      $this->out->writeLinef(
        $this->colored ? "\033[37m%s:\033[0m%s%s" : '  %s:%s%s',
        $name,
        str_repeat(' ', 12 - strlen($name)),
        $metric->formatted()
      );
    }
  }
}