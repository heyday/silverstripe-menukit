# (Faster) mobile menu

Hooray! A module for that mobile menu that's copied everywhere. This was originally written for SilverStripe 2.4, so is in dire need of a rethink and may be completely misguided anyway.

The original intent of this code was to build a full menu tree with just two database queries, instead of running n+1 queries by nesting `SiteTree::Children()` calls in templates.

    // Todo: love this code and make it use features available in SilverStripe 3

## Template for now...

Here's the template that's been thrown around with this code. It really needs to be refactored to be reusable, or removed entirely in favour of a JSON solution.

This package provides some basic components for efficiently pulling large portions of the SiteTree out of the database. This can be used with something like `heyday/silverstripe-menumanager` or just pulling top level pages in a site to expose a tree for rendering in a template:

```php
class Page extends Page implements TemplateGlobalProvider
{
    // ...

    public static function get_template_global_variables()
    {
        return array(
            'MobileMenuTree'
        );
    }

    public static function getMobileMenuTree()
    {
        static $tree = null;

        if (!$tree) {
            $resolver = new DataObjectTreeResolver();

            // Use multiple menus for the source of root items
            $rootIds = MenuItem::get()
                ->filter(array(
                    'MenuSet.Name' => array(
                        'MainMenu',
                        'TopMenu'
                    )
                ))
                ->column('PageID');

            // Select all pages that are allowed to show in the menu
            $pages = Page::get()
                ->exlcude('ClassName', array(
                    'NewsArticle'
                ));

            $tree = $resolver->getTree($rootIds, $pages);
        }

        return $tree;
    }
}
```

```html
<div class="mnv t-light" data-menu data-menu-default="1">
	<div class="mnv-page" data-menu-id="1">
		<ul class="nav">
			<% loop $MenuSet('MobileNav').MenuItems %>
				<li class="nav-item">
					<% if $Children %>
						<a href="$Link" class="mnv-down" data-menu-show="$PageID">$MenuTitle</a>
					<% else %>
						<a class="mnv-text" href="$Link">$MenuTitle</a>
					<% end_if %>
				</li>
			<% end_loop %>
		</ul>
	</div>

	<% loop $NavigationTreeOptimised %>
		<div class="mnv-page" data-menu-id="$ID">
			<ul class="nav">
				<% if $MenuParent %>
					<li class="nav-item"><a href="$MenuParent.Link" class="mnv-back" data-menu-back="$ParentID">$MenuParent.MenuTitle</a></li>
				<% else %>
					<li class="nav-item"><a href="/" class="mnv-back" data-menu-back="1">Home</a></li>
				<% end_if %>
					<li class="nav-item"><a href="$Link" class="mnv-text">$MenuTitle</a></li>
				<% loop $MenuChildren %>
					<li class="nav-item">
						<% if $HasChildren %>
							<a href="$Link" class="mnv-down mnv-child" data-menu-show="$ID">$MenuTitle</a>
						<% else %>
							<a href="$Link" class="mnv-text mnv-child">$MenuTitle</a>
						<% end_if %>
					</li>
				<% end_loop %>
			</ul>
		</div>
	<% end_loop %>
</div>
```
