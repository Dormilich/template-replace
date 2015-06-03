# template-replace
Wrapper class around str_replace() to ease handling of string replacements in small templates.

## basic usage

```PHP
$template = new TemplateReplace('I love {{ someone }}.', '{{ ', ' }}');
$template->set('someone', 'you');
echo $template->render();
```
```PHP
$template = new TemplateReplace('I love {{ someone }}.', '{{ ', ' }}');
$template['someone'] = 'you';
echo $template->render();
```
```PHP
$template = new TemplateReplace('I love {{ someone }}.', '{{ ', ' }}');
echo $template->render(['someone' => 'you']);
```

## goodies

If you use the same placeholder delimiters before and after the name, it suffices to pass only one.
```PHP
$template = new TemplateReplace('I love %someone%.', '%');
echo $template->render(['someone' => 'you']);
```

The placeholders are treated case-insensitively.
```PHP
$template = new TemplateReplace('I love {{ SomeOne }}.', '{{ ', ' }}');
echo $template->render(['someone' => 'you']);
```

Which also works with non-ASCII characters.
```PHP
$template = new TemplateReplace('I aim {{ HÖHER }}.', '{{ ', ' }}');
echo $template->render(['höher' => 'for the stars']);
```

You can also set a dafault value for every placeholder you haven’t assigned yourself.
```PHP
$template = new TemplateReplace('I love {{ someone }}.', '{{ ', ' }}');
$template->defaultValue = 'me';
echo $template->render();
```

## error handling

The array access method throws a `RuntimeException` if the key is not found in the template.
```PHP
$template = new TemplateReplace('I love {{ someone }}.', '{{ ', ' }}');
$template['foo'] = 'you';
// this part is skipped by the exception
echo $template->render();
```

The set() and the render() methods catch the exception for you.
```PHP
$template = new TemplateReplace('I love {{ someone }}.', '{{ ', ' }}');

// either
echo $template
    ->set('foo', 'me')
    ->set('someone', 'you')
    ->render()
;

// or
echo $template->render([
    'foo'     => 'me',
    'someone' => 'you',
]);

// get you a nice message
echo $template->getLastError(); 
```
