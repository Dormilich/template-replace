<?php

use Dormilich\Utilities\TemplateReplace;

class TemplateReplaceTest extends \PHPUnit_Framework_TestCase
{
    public function testClassAcceptsStringAsTemplate()
    {
        $engine = new TemplateReplace('I love {{ foo }}.', '{{ ', ' }}');
        $engine->set('foo', 'you');
        $result = $engine->render();

        $this->assertEquals('I love you.', $result);
    }

    public function testClassAcceptsArrayAsTemplate()
    {
        $templates = [
            'I love {{ foo }}.',
            'I hate {{ bar }}.',
        ];
        $engine = new TemplateReplace($templates, '{{ ', ' }}');
        $engine->set('foo', 'you');
        $engine->set('bar', 'tomatoes');
        $result = $engine->render();

        $expected = [
            'I love you.',
            'I hate tomatoes.',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testClassDoesNotAcceptIntegerAsTemplate()
    {
        $engine = new TemplateReplace(12345, '{{ ', ' }}');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testClassDoesNotAcceptObjectAsTemplate()
    {
        $engine = new TemplateReplace(new \stdClass, '{{ ', ' }}');
    }

    public function testClassUsesOpenDelimiterIfCloseDelimiterNotGiven()
    {
        $engine = new TemplateReplace('I love %%foo%%.', '%%');
        $engine->set('foo', 'you');
        $result = $engine->render();

        $this->assertEquals('I love you.', $result);
    }

    public function testPlaceholderDelimiterCanBeRegexCharacters()
    {
        $engine = new TemplateReplace('I love /foo?.', '/', '?');
        $engine->set('foo', 'you');
        $result = $engine->render();

        $this->assertEquals('I love you.', $result);
    }

    public function testReplacesAllInstancesOfSamePlaceholder()
    {
        $engine = new TemplateReplace('I love %%foo%% and %%foo%%.', '%%');
        $engine->set('foo', 'you');
        $result = $engine->render();

        $this->assertEquals('I love you and you.', $result);
    }

    public function testSetValueThroughArrayAccess()
    {
        $engine = new TemplateReplace('I love {{ foo }}.', '{{ ', ' }}');
        $engine['foo'] = 'you';
        $result = $engine->render();

        $this->assertEquals('I love you.', $result);
    }

    public function testSetValuesThroughChainedSetters()
    {
        $engine = new TemplateReplace('I love {{ foo }} and {{ bar }}.', '{{ ', ' }}');
        $engine
            ->set('foo', 'you')
            ->set('bar', 'me')
        ;
        $result = $engine->render();

        $this->assertEquals('I love you and me.', $result);
        $this->assertFalse($engine->getLastError());
    }

    public function testSetValueInRenderMethod()
    {
        $engine = new TemplateReplace('I love {{ foo }}.', '{{ ', ' }}');
        $result = $engine->render(['foo' => 'you']);

        $this->assertEquals('I love you.', $result);
    }

    public function testSetValueThroughCaseInsensitiveAccess()
    {
        $engine = new TemplateReplace('I love {{ FOO }}.', '{{ ', ' }}');
        $engine['Foo'] = 'you';
        $result = $engine->render();

        $this->assertEquals('I love you.', $result);
    }

    public function testSetValueThroughCaseInsensitiveAccessWithNonAsciiKey()
    {
        $engine = new TemplateReplace('I love {{ MÖÖ }}.', '{{ ', ' }}');
        $engine['möö'] = 'you';
        $result = $engine->render();

        $this->assertEquals('I love you.', $result);
    }

    public function invalidPlaceholderProvider()
    {
        return [
            ['a b'], ['x'.\PHP_EOL], ["\tfoo"]
        ];
    }

    /**
     * @dataProvider invalidPlaceholderProvider
     * @expectedException RuntimeException
     */
    public function testPlaceholderMustNotContainSpace($name)
    {
        $engine = new TemplateReplace('I love {{ foo }}.', '{{ ', ' }}');
        $engine[$name] = 'you';
    }

    public function testUsingDefaultValue()
    {
        $engine = new TemplateReplace('I love {{ foo }} and {{ bar }}.', '{{ ', ' }}');
        $engine->defaultValue = 'me';
        $result = $engine->render(['foo' => 'you']);

        $this->assertEquals('I love you and me.', $result);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testWarnForAmbiguousPlaceholders()
    {
        $engine = new TemplateReplace('I love {{ foo }} and {{ FOO }}.', '{{ ', ' }}');
    }

    /**
     * @expectedException RuntimeException
     */
    public function testWarnForNonAsciiAmbiguousPlaceholders()
    {
        $engine = new TemplateReplace('I love {{ mü }} and {{ MÜ }}.', '{{ ', ' }}');
    }

    /**
     * @expectedException RuntimeException
     */
    public function testWarnForUnknownPlaceholders()
    {
        $engine = new TemplateReplace('I love {{ foo }}.', '{{ ', ' }}');
        $engine['bar'] = 'you';
    }

    public function testSetterMethodCatchesErrors()
    {
        $engine = new TemplateReplace('I love {{ foo }}.', '{{ ', ' }}');
        $engine->set('bar', 'you');

        $this->assertNotFalse($engine->getLastError());
    }

    public function testRenderMethodCatchesErrors()
    {
        $engine = new TemplateReplace('I love {{ foo }}.', '{{ ', ' }}');
        $result = $engine->render([
            'foo' => 'you',
            'bar' => 'me',
        ]);

        $this->assertEquals('I love you.', $result);
        $this->assertNotFalse($engine->getLastError());
    }

    public function testClearErrors()
    {
        $engine = new TemplateReplace('I love {{ foo }}.', '{{ ', ' }}');
        $result = $engine->render([
            'foo' => 'you',
            'bar' => 'me',
        ]);

        $this->assertNotFalse($engine->getLastError());
        $this->assertInternalType('array', $engine->getErrors());        
        $this->assertFalse($engine->getLastError());
    }
}