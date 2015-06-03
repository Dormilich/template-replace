<?php

namespace Dormilich\Utilities;

/**
 * Assigning placeholder values:
 * 
 * $obj['key'] = 'value';
 *    - 'key' must be a case-insensitive match of a placeholder key found in the 
 *      template, otherwise an exception is thrown.
 * 
 * $obj->set('key', 'value');
 *    - chainable
 *    - catches errors
 * 
 * $obj->render(['key' => 'value']);
 *    - catches errors
 */
class TemplateReplace implements \ArrayAccess
{
    /**
     * @var string $defaultValue A string that any found and unassigned 
     *          placeholder should use when rendering the templates.
     */
    public $defaultValue = false;

    /**
     * @var string|array $tpl Template(s) to process.
     */
    protected $tpl;

    /**
     * @var array $keys The placeholder identifyers.
     */
    protected $keys = [];

    /**
     * @var array $data The values for each placeholder.
     */
    protected $data = [];

    /**
     * @var string $open Opening placeholder delimiter.
     */
    protected $open;

    /**
     * @var string $close Closing placeholder delimiter.
     */
    protected $close;

    /**
     * @var array $errors List of error messages.
     */
    protected $errors = [];

    /**
     * Create instance.
     * 
     * If no closing delimiter is passed, the same string as for the opening 
     * delimiter is used.
     * 
     * @param string|array $template The template(s) to populate.
     * @param string $open Placeholder opening delimiter.
     * @param string $close Placeholder closing delimiter.
     * @return self
     * @throws InvalidArgumentException Invalid template data type.
     * @throws RuntimeException Ambiguous placeholders found.
     */
    public function __construct($template, $open, $close = null)
    {
        $this->setDelimiters($open, $close);
        $this->setTemplate($template);
        $this->setPlaceholderKeys();
    }

    /**
     * Set the template(s).
     * 
     * @param string|array $template The template(s) to populate.
     * @return void
     * @throws InvalidArgumentException Invalid template data type.
     */
    protected function setTemplate($template)
    {
        if (!is_string($template) and !is_array($template)) {
            throw new \InvalidArgumentException('Invalid template format.');
        }
        $this->tpl = $template;
    }

    /**
     * Set the opening and closing delimiters of the placeholders.
     * 
     * @param string $open Opening placeholder delimiter.
     * @param string $close Closing placeholder delimiter.
     * @return void
     */
    protected function setDelimiters($open, $close)
    {
        $this->open = (string) $open;

        if (null === $close) {
            $this->close = $this->open;
        } else {
            $this->close = (string) $close;
        }
    }

    /**
     * Find the case-insensitively matching key in the placeholder key list.
     * If a key does not exist in the key list, it is returned unchanged.
     * 
     * This method allows the placeholder names to be entered case-insensitively, 
     * e.g. even if the placeholder name is 'FOO', using $obj['foo'] = 'value' 
     * correctly assigns the placeholder value.
     * 
     * @param string $offset Key candidate.
     * @return string Key.
     * @throws RuntimeException Unknown placeholder key.
     */
    protected function findKey($offset)
    {
        if (in_array($offset, $this->keys, true)) {
            return $offset;
        }
        // UTF-8
        foreach ($this->keys as $key) {
            if (mb_strtolower($key, 'UTF-8') === mb_strtolower($offset, 'UTF-8')) {
                return $key;
            }
        }
        throw new \RuntimeException(sprintf('Placeholder name "%s" does not exist in the template.', $offset));
    }

    /**
     * Check if a placeholder key (of a key candidate) is set in the 
     * placeholder value array.
     * 
     * @param string $offset Placeholder candidate key.
     * @return boolean Whether a value exists.
     */
    public function offsetExists($offset)
    {
        try {
            return array_key_exists($this->findKey($offset), $this->data);
        }
        catch (\Exception $exc) {
            return false;
        }
    }

    /**
     * Get the value of a placeholder (candidate) key.
     * 
     * @param string $offset Placeholder candidate key.
     * @return string|null Placeholder value or NULL if either not set or the 
     *          key is not in the placholder keys array.
     */
    public function offsetGet($offset)
    {
        try {
            $key = $this->findKey($offset);

            if ($this->offsetExists($key)) {
                return $this->data[$key];
            }
            return null;
        }
        catch (\Exception $exc) {
            return null;
        }
    }

    /**
     * Set a value for a placeholder (candidate) key.
     * 
     * @param string $offset Placeholder candidate key.
     * @param string $value Placeholder value.
     * @return void
     * @throws RuntimeException Unknown placeholder key.
     */
    public function offsetSet($offset, $value)
    {
        $this->data[$this->findKey($offset)] = (string) $value;
    }

    /**
     * Delete a placeholder value.
     * 
     * @param string $offset Placeholder candidate key.
     * @return void
     */
    public function offsetUnset($offset)
    {
        try {
            unset($this->data[$this->findKey($offset)]);
        }
        catch (\Exception $exc) {
            // do nothing
        }
    }

    /**
     * Set a value for a placeholder.
     * 
     * When using this method any exceptions caused by unknown keys are put 
     * into the error array and can be retrieved from there.
     * 
     * @param string $key Placeholder name.
     * @param string $value Placeholder value.
     * @return self
     */
    public function set($key, $value)
    {
        try {
            $this->offsetSet($key, $value);
        }
        catch (\Exception $exc) {
            $this->errors[] = $exc->getMessage();
        }

        return $this;
    }

    /**
     * Populate the template with data and reset the placeholder value array.
     * 
     * @param array $values (optional) Template values.
     * @return string|array The populated template(s).
     */
    public function render(array $values = array())
    {
        // add last chance values
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }
        // set default values (if any)
        if (false !== $this->defaultValue) {
            $defaults   = $this->getDefaultPlaceholders($this->defaultValue);
            $this->data = array_replace($defaults, $this->data);
        }
        // prepare arguments
        $placeholders = array_map([$this, 'createPlaceholder'], array_keys($this->data));
        $values       = array_values($this->data);
        // delete used data
        $this->data   = [];

        return str_replace($placeholders, $values, $this->tpl);
    }

    /**
     * Transform the internal placeholder name into the placeholders as 
     * expected in the template(s).
     * 
     * @param string $key Placeholder name.
     * @return string Template placeholder.
     */
    protected function createPlaceholder($key)
    {
        return $this->open . $key . $this->close;
    }

    /**
     * Find all candidate placeholder names consisting of non-whitespace 
     * characters and set them into the placeholder array.
     * 
     * @return void
     * @throws RuntimeException Ambiguous placeholders found.
     */
    protected function setPlaceholderKeys()
    {
        $template = implode(\PHP_EOL, (array) $this->tpl);
        $pattern  = sprintf('/%s(\S+?)%s/', preg_quote($this->open, '/'), preg_quote($this->close, '/'));
        $count    = preg_match_all($pattern, $template, $matches, \PREG_SET_ORDER);

        if (!$count) {
            return;
        }

        $keys = array_map(function ($value) {
            return $value[1];
        }, $matches);

        $this->keys = array_unique($keys);

        // check keys for ambiguity
        $lower_keys = array_map(function ($value) {
            return mb_strtolower($value, 'UTF-8');
        }, $this->keys);

        if (count($lower_keys) > count(array_unique($lower_keys))) {
            throw new \RuntimeException('There are ambiguous placeholder names.');
        }
    }

    /**
     * Create an array from all found keys and set a default value for each of them.
     * 
     * @param string $default Default value.
     * @return array Default data array.
     */
    protected function getDefaultPlaceholders($default = '')
    {
        return array_combine($this->keys, array_fill(0, count($this->keys), (string) $default));
    }

    /**
     * Get all encountered errors and clear the internal error array.
     * 
     * @return array List of error messages.
     */
    public function getErrors()
    {
        $errors       = $this->errors;
        $this->errors = [];

        return $errors;
    }

    /**
     * Get the last error message. If no errors occurred FALSE is returned, 
     * which can be used to check if an error occurred at all.
     * 
     * @return string The last error message or FALSE.
     */
    public function getLastError()
    {
        return end($this->errors);
    }
}
