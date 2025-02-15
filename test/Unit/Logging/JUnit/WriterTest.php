<?php

declare(strict_types=1);

namespace ParaTest\Tests\Unit\Logging\JUnit;

use ParaTest\Logging\JUnit\Reader;
use ParaTest\Logging\JUnit\Writer;
use ParaTest\Logging\LogInterpreter;
use ParaTest\Tests\TestBase;

use function file_exists;
use function file_get_contents;
use function unlink;

/**
 * @internal
 *
 * @covers \ParaTest\Logging\JUnit\Writer
 */
final class WriterTest extends TestBase
{
    /** @var Writer  */
    protected $writer;
    /** @var LogInterpreter */
    protected $interpreter;
    /** @var string  */
    protected $passing;

    public function setUpTest(): void
    {
        $this->interpreter = new LogInterpreter();
        $this->writer      = new Writer($this->interpreter, 'test/fixtures/tests/');
        $this->passing     = FIXTURES . DS . 'results' . DS . 'single-passing.xml';
    }

    public function testConstructor(): void
    {
        static::assertInstanceOf(
            LogInterpreter::class,
            $this->getObjectValue($this->writer, 'interpreter')
        );
        static::assertEquals('test/fixtures/tests/', $this->writer->getName());
    }

    public function testSingleFileLog(): void
    {
        $this->addPassingReader();
        $xml = $this->writer->getXml();
        static::assertXmlStringEqualsXmlString((string) file_get_contents($this->passing), $xml);
    }

    public function testMixedFileLog(): void
    {
        $mixed  = FIXTURES . DS . 'results' . DS . 'mixed-results.xml';
        $reader = new Reader($mixed);
        $this->interpreter->addReader($reader);
        $writer = new Writer($this->interpreter, 'test/fixtures/tests/');
        $xml    = $writer->getXml();
        static::assertXmlStringEqualsXmlString((string) file_get_contents($mixed), $xml);
    }

    public function testDataProviderWithSpecialCharacters(): void
    {
        $mixed  = FIXTURES . DS . 'results' . DS . 'data-provider-with-special-chars.xml';
        $reader = new Reader($mixed);
        $this->interpreter->addReader($reader);
        $writer = new Writer($this->interpreter, 'test/fixtures/tests/');
        $xml    = $writer->getXml();
        static::assertXmlStringEqualsXmlString((string) file_get_contents($mixed), $xml);
    }

    public function testWriteToFile(): void
    {
        $output = TMP_DIR . DS . 'passing.xml';
        $this->addPassingReader();
        $this->writer->write($output);
        static::assertXmlFileEqualsXmlFile($this->passing, $output);
        if (! file_exists($output)) {
            return;
        }

        unlink($output);
    }

    public function testWriteToFileInNonExistentDir(): void
    {
        $output = TMP_DIR . DS . 'logs' . DS . 'new' . DS . 'dir' . DS . 'passing.xml';
        $this->addPassingReader();
        $this->writer->write($output);
        static::assertXmlFileEqualsXmlFile($this->passing, $output);
        if (! file_exists($output)) {
            return;
        }

        unlink($output);
    }

    private function addPassingReader(): void
    {
        $reader = new Reader($this->passing);
        $this->interpreter->addReader($reader);
    }

    /**
     * Empty line attributes, e.g. line="" breaks Jenkins parsing since it needs to be an integer.
     * To repair, ensure that empty line attributes are actually written as 0 instead of empty string.
     */
    public function testThatEmptyLineAttributesConvertToZero(): void
    {
        $mixed  = FIXTURES . DS . 'results' . DS . 'junit-example-result.xml';
        $reader = new Reader($mixed);
        $this->interpreter->addReader($reader);
        $writer = new Writer($this->interpreter, 'test/fixtures/tests/');
        $xml    = $writer->getXml();

        static::assertStringNotContainsString(
            'line=""',
            $xml,
            'Expected no empty line attributes (line=""), but found one.'
        );
    }
}
