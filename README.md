# (Faster) mobile menu

Hooray! A module for that mobile menu that's copied everywhere. This was originally written for SilverStripe 2.4, so is in dire need of a rethink and may be completely misguided anyway.

The intent of this code originally was to build a full menu tree with only two database queries, instead of running n+1 queries by nesting `SiteTree::Children()` calls in templates.

  // Todo: love this code and make it use features available in SilverStripe 3

## Template for now...

The template that's been thrown around with this code an needs to be refactored to be reusable or removed in favour of a JSON and/or AJAX solution:

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
