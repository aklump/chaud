<?php

namespace AKlump\ChangeAudio\Tests\Unit;

use AKlump\ChangeAudio\App;
use AKlump\ChangeAudio\Tests\Unit\TestingTraits\TestWithFilesTrait;
use PHPUnit\Framework\TestCase;

/**
 * Runs the real executable as a subprocess to guard the bootstrap.
 *
 * @coversNothing
 */
class BootstrapTest extends TestCase {

  use TestWithFilesTrait;

  private string $executable;

  protected function setUp(): void {
    $this->executable = realpath(__DIR__ . '/../' . App::BIN);
  }

  protected function tearDown(): void {
    $this->deleteAllTestFiles();
  }

  /**
   * @return array{0: int, 1: string, 2: string} Exit code, stdout, stderr.
   */
  private function runScript(string $script, array $args, array $env = []): array {
    $env += [
      'PATH' => getenv('PATH'),
      'HOME' => $this->getTestFileFilepath('home/', TRUE),
      'CHAUDIO_CACHE_PATH' => $this->getTestFileFilepath('cache/', TRUE),
      'CHAUDIO_PHP' => PHP_BINARY,
    ];
    $env = array_filter($env, fn($value) => $value !== NULL);
    $command = array_merge([$script], $args);
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, sys_get_temp_dir(), $env);
    $this->assertIsResource($process);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return [proc_close($process), $stdout, $stderr];
  }

  public function testVersion() {
    [$code, $stdout] = $this->runScript($this->executable, ['-V']);
    $this->assertSame(0, $code);
    $this->assertSame(sprintf("%s %s\n", App::NAME, App::VERSION), $stdout);
  }

  public function testHelpFlagListsCommands() {
    foreach (['-h', '--help'] as $flag) {
      [$code, $stdout] = $this->runScript($this->executable, [$flag]);
      $this->assertSame(0, $code, $flag);
      $this->assertStringContainsString('Available commands', $stdout);
      $this->assertStringContainsString('config', $stdout);
    }
  }

  public function testNoArgumentsListsCommands() {
    [$code, $stdout] = $this->runScript($this->executable, []);
    $this->assertSame(0, $code);
    $this->assertStringContainsString('Available commands', $stdout);
  }

  public function testUnknownOptionFailsOnStderr() {
    [$code, $stdout, $stderr] = $this->runScript($this->executable, ['--bogus']);
    $this->assertSame(1, $code);
    $this->assertSame('', $stdout);
    $this->assertStringContainsString('"--bogus" option does not exist', $stderr);
  }

  public function testWorksThroughASymlinkInAnotherDirectory() {
    $link = $this->getTestFileFilepath('bin/', TRUE) . '/' . App::BIN . '-link';
    $this->assertTrue(symlink($this->executable, $link));
    [$code, $stdout] = $this->runScript($link, ['-V']);
    $this->assertSame(0, $code);
    $this->assertStringContainsString(App::NAME, $stdout);
    [$code, $stdout] = $this->runScript($link, ['-h']);
    $this->assertSame(0, $code);
    $this->assertStringContainsString('Available commands', $stdout);
  }

  /**
   * Copies the launcher beside a stub that prints the PHP binary running it.
   *
   * @return string The path to the copied launcher.
   */
  private function createLauncherWithStub(?string $php_version_file = NULL): string {
    $this->getTestFileFilepath('app/bin/', TRUE);
    $dir = rtrim($this->getTestFileFilepath('app/'), '/');
    file_put_contents("$dir/bin/chaudio.php", '<?php echo PHP_BINARY;');
    copy($this->executable, "$dir/" . App::BIN);
    chmod("$dir/" . App::BIN, 0755);
    if ($php_version_file !== NULL) {
      file_put_contents("$dir/.php-version", $php_version_file);
    }

    return "$dir/" . App::BIN;
  }

  /**
   * @return string A phpenv root whose version $version links to PHP_BINARY.
   */
  private function createPhpenvRoot(string $version): string {
    $this->getTestFileFilepath("phpenv/versions/$version/bin/", TRUE);
    $root = rtrim($this->getTestFileFilepath('phpenv/'), '/');
    symlink(PHP_BINARY, "$root/versions/$version/bin/php");

    return $root;
  }

  public function testLauncherUsesBinaryPathInPhpVersionFile() {
    $launcher = $this->createLauncherWithStub(PHP_BINARY . PHP_EOL);
    [$code, $stdout] = $this->runScript($launcher, [], ['CHAUDIO_PHP' => NULL, 'PATH' => '/usr/bin:/bin']);
    $this->assertSame(0, $code);
    $this->assertSame(PHP_BINARY, $stdout);
  }

  public function testLauncherPrefersEnvironmentOverPhpVersionFile() {
    $launcher = $this->createLauncherWithStub('/does/not/exist/php');
    [$code, $stdout] = $this->runScript($launcher, []);
    $this->assertSame(0, $code);
    $this->assertSame(PHP_BINARY, $stdout);
  }

  public function testLauncherFailsOnStderrWhenPhpIsMissing() {
    $launcher = $this->createLauncherWithStub();
    [$code, $stdout, $stderr] = $this->runScript($launcher, [], ['CHAUDIO_PHP' => '/does/not/exist/php']);
    $this->assertSame(1, $code);
    $this->assertSame('', $stdout);
    $this->assertStringContainsString('/does/not/exist/php', $stderr);
    $this->assertStringContainsString('CHAUDIO_PHP', $stderr);
  }

  public function testLauncherUsesPhpenvVersionName() {
    $launcher = $this->createLauncherWithStub("8.3\n");
    $root = $this->createPhpenvRoot('8.3');
    [$code, $stdout] = $this->runScript($launcher, [], ['CHAUDIO_PHP' => NULL, 'PHPENV_ROOT' => $root, 'PATH' => '/usr/bin:/bin']);
    $this->assertSame(0, $code);
    $this->assertSame(PHP_BINARY, $stdout);
  }

  public function testLauncherDefaultsPhpenvRootToHome() {
    $launcher = $this->createLauncherWithStub("8.3\n");
    $home = rtrim($this->getTestFileFilepath('home/', TRUE), '/');
    $this->getTestFileFilepath('home/.phpenv/versions/8.3/bin/', TRUE);
    symlink(PHP_BINARY, "$home/.phpenv/versions/8.3/bin/php");
    [$code, $stdout] = $this->runScript($launcher, [], ['CHAUDIO_PHP' => NULL, 'HOME' => $home, 'PATH' => '/usr/bin:/bin']);
    $this->assertSame(0, $code);
    $this->assertSame(PHP_BINARY, $stdout);
  }

  public function testLauncherFallsBackToPathForPhpenvSystemVersion() {
    $launcher = $this->createLauncherWithStub("system\n");
    [$code, $stdout] = $this->runScript($launcher, [], ['CHAUDIO_PHP' => NULL, 'PATH' => dirname(PHP_BINARY) . ':/usr/bin:/bin']);
    $this->assertSame(0, $code);
    $this->assertSame(PHP_BINARY, $stdout);
  }

  public function testLauncherFailsWhenPhpenvVersionIsNotInstalled() {
    $launcher = $this->createLauncherWithStub("7.0\n");
    $root = $this->createPhpenvRoot('8.3');
    [$code, $stdout, $stderr] = $this->runScript($launcher, [], ['CHAUDIO_PHP' => NULL, 'PHPENV_ROOT' => $root]);
    $this->assertSame(1, $code);
    $this->assertSame('', $stdout);
    $this->assertStringContainsString("$root/versions/7.0/bin/php", $stderr);
    $this->assertStringContainsString('.php-version', $stderr);
  }

}
