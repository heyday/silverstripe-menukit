# SilverStripe MenuKit

MenuKit is a set of classes for SilverStripe that allow traversal of DataObject/Page trees with two instead of n+1 queries.

## Installation

```
$ composer require silverstripe-menukit
```

## Usage

### Example: exposing the site tree as JSON in templates

If you wanted to build a traversable menu in JavaScript, you might want to expose your site tree as JSON in templates. That could look something like this:

```php
use \Heyday\SilverStripe\MenuKit\NestedMenuBuilder;

class JsonMenuProvider implements TemplateGlobalProvider
{
    public static function get_template_global_variables()
    {
        return array(
            'SiteTreeJSON' => 'getSiteTreeJSON'
        );
    }

    public static function getSiteTreeJSON()
    {
    	// Pages that are allowed to show in the menu
    	// If you wanted to exclude news articles from the menu, you'd filter that here
        $candidatePages = SiteTree::get();

        // Pages that should show up at the root level of the menu
        $rootPages = SiteTree::get()
        ->filter([
            'ParentID' => 0,
            'ShowInMenu' => 1
        ]);

        $menu = new NestedMenuBuilder($rootPages, $candidatePages);

        return json_encode($menu->toNestedArray());
    }
}
```
