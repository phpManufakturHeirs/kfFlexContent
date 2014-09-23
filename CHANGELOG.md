## flexContent for kitFramework ##

(c) 2014 phpManufaktur by Ralf Hertsch

MIT License (MIT) - <http://www.opensource.org/licenses/MIT>

kitFramework - <https://kit2.phpmanufaktur.de>

**0.45** - 2014-09-23

* removed no longer needed `*.twig` files (missed handling in a previous release)
* missing handling for `order_by` and `order_direction`, add handling for `content_exclude`
* add handling for `content_exclude[]` - you can now explicit exclude content ID's from a category or list view
* new: parameter `hide_if_empty[]` hide content if nothing is available and don't prompt an alert
* admin: category type list show used content ID's and content title as tooltip, using category as primary for a content is indicated by bold ID
* fix: title, page title and description are now using sanitize (was missing)
* admin: content list can now filtered by category or category type - selection is saved as session variable
* Introduce Glossary function for flexContent
* Add import of previous [dbGlossary](https://blog.phpmanufaktur.de/de/article/dbglossary-literatur-und-fussnoten-verwalten.php) `CSV` data into flexContent 

**0.44** - 2014-09-18

* fixed: #9 - can not delete the last hashtag which is assigned to a content
* enable setting of `$subdirectory` and `$CMS_PATH` for usage with the BASIC migration tool

**0.43** - 2014-09-14

* added UNIQUE KEY for `category_name` and `tag_name`
* small fix for `|humanize` filter
* updated `@link` references
* updated info URL
* added URL for the changelog in CMS Tool

**0.42** - 2014-09-08

* fixed: wrong release date
* fixed: automatic iFrame height for LEPTON CMS
* install CKEditor plugins for flexContent Article and flexContent #hashtags at BlackCat
* changed toolbar handling in backend
* Split the `title` field into `headline` and `page_title` to improve the SEO handling

**0.41** - 2014-09-05

* fixed NOT NULL at insert new record (Category Type)
* fixed translation for flexContent status
* improved #hashtag check in content
* load proxy settings only for external URL
* improved translation

**0.40** - 2014-09-03

* exclude records marked as 'DELETED' from permanent link check
* improve permanent link comparison
* not SET DEFAULT for TEXT fields! (table import_control)

**0.39** - 2014-08-11

* improved translation handling, added support for i18nEditor
* generate extended debug information if the form submission is not valid

**0.38** - 2014-07-28

* changed `promptAlert()` handling
* fixed some smaller issues, reformatted parts of code

**0.37** - 2014-07-27

* handling for libraries is now defined in `config.flexcontent.json`
* in content embedded kitCommands can now load additional libraries

**0.36** - 2014-07-26

* introduce #hashtag wordcloud

**0.35** - 2014-07-25

* fixed a namespace typo in the permalink class
* introduce #hashtag enumeration for embedding in frontpage (experimental)

**0.34** - 2014-07-08

* fixed two small but strong typo in `RemoteServer.php` and `bootstrap.include.php`
* changed handling for small content images
* removed robots directive for `action[list]`
* improved SQL table definition for the category type
* avoid setting `paging[]` at negative value in `action[list]`
* add check if function `set_time_limit()` is enabled
* add `config.allowedContent = true` to enable extra tags and scripts in the main content

**0.33** - 2014-06-11

* changed library dependencies to `/latest`
* changed handling of `container` and `container-fluid`
* fixed route to `/mediabrowser`
* introduce using of flexContent as Remote Server and Client
* changed BASIC OutputFilter enable to remove dynamically loading of CSS and JS files
* changed routes for `/command/flexcontent/getheader` and `/command/flexcontent/canonical/` to POST method
* massive changes for remote access, and access for the new OutputFilter, changed method `promptAlert()`
* finished first steps for the remote service

**0.32** - 2014-06-04

* changed roles and authentication check for the `/media` directory

**0.31** - 2014-06-02

* Again the handling of GET parameters for the permanent links: check is now more precise and strong again!
* Selecting the URL by content_id does not return the primary target for the category, fixed!
* A bit unclear: seems that $app['contact'] is not initialized in some situations, so we init `Contact` directly
* extended CKEditor layout for the category editing
* added canonical link to each permanent link (SEO)
* load schema.org for each event also in categories and lists (SEO)

**0.30** - 2014-05-23

* completed settings for robots (SEO configuration for category, content, buzzword and faq)

**0.29** - 2014-05-15

* changed for SEO reasons the permalink subdirectoty for Hashtags from `/tag/` to `/buzzword/`

**0.28** - 2014-05-15

* fixed a strong typo in `Configuration.php`
* fixed a problem if keywords contains special chars or German Umlauts
* fixed a problem if special chars are used in keywords and individual SEO settings are used in configuration

**0.27** - 2014-05-07

* the GET command check for permanent links was too strong and does not recognize i.e. TAG Redirects

**0.26** - 2014-05-02

* added action[archive]
* fixed a problem of paging logic in list and archive mode
* generally changed class `container` to `container-fluid`
* fixed a problem in jQuery for checking if CSS is already loaded
* all language files are now loaded by the BASIC extension
* Introduce configurable nav tab order and default nav tab
* added missing CKE Toolbuttons JustifyLeft, JustifyCenter, JustifyRight and JustifyBlock
* Avoid hashtag filter to replace hexadecimal color codes

**0.25** - 2014-03-24

* add the paging also to the default LIST command
* The templates now also prompt their names
* added handling for protected CMS pages (private, registered)

**0.24** - 2014-03-13

* fixed invalid check for the target URL
* added missing import of TOPICS `description` and `keywords` fields
* removed handling for former planned kitCommand iframe
* introduce parameter `check_jquery` to enable skipping the jQuery check
* improved check if needed kitCommands exists in the target URL
* show also archived articles in the administrative overview
* auto update the status from BREAKING to PUBLISHED and from PUBLISHED, BREAKING and HIDDEN to ARCHIVED
* changed parameter handling to enable multiple kitCommands at one target page without conflicts
* enable a paging mode for flexContent lists

**0.23** - 2014-02-24

* added missing button to remove images from content teaser, tag or category description
* added a full-text search to the admin content list
* improved CSS and JS check to avoid duplicate loading of the files
* change the default status for the import function to FALSE and remove the tab from the admin access
* Import Control check now for existing ~~ flexcontent ~~ commands and set WYSIWG, NEWS and TOPICS pages which contain the command to IGNORE
* Added check if the kitCommand for the category is placed at the target URL
* updated all templates to jQuery 1.11.0 
* added tooltips to copy needed kitCommands for the actions
* hide the language column in admin lists if not needed
* in frontend always use #flexcontent as primary container before Bootstrap .container
* Introduce a category type to enable EVENTS as content and to improve the handling of FAQs and other extensions
* `ActionCategory` redirect to `ActionFAQ` if the category type is FAQ
* introduce `prepareContent()` - add also special contents to the records i.e. for EVENT
* removed action value `list_simple` and replace it with the new paramter `type[]`
* View can know redirect to FAQ view if an article belongs to a FAQ
* show event information and inbound schema.org information for events
* fixed some smaller bugs and typos

**0.22** - 2014-02-12

* introduce parameter `content_exposed` to tell the template how many content items should be shown exposed
* grant a proper build of the keywords
* add array with ignore parameters to permanent link handling
* added support for kitCommands in FAQ lists
* added missing function `SelectContentLinkList()`

**0.21** - 2014-02-06

* added support for setting canonical links

**0.20** - 2014-02-04

* fixed sending content type for the RSS feeds
* Improved import handling for third party contents

**0.19** - 2014-02-03

* introduce RSS Feeds
* RSS Statistics for the RSS Channels and to track flexContent Contents called by RSS Feeds
* many, many smaller changes ...

**0.18** - 2014-01-30

* cleanup the configuration and handling of parameters
* create a helpfile for the flexContent kitCommand
* introduce action[faq] for a FAQ mode

**0.17** - 2014-01-28

* added kitCommand parameter `action[list]`
* added redirect_target
* added import support for dbGlossary (remove tags only)
* changed `category.item.twig` to `content.item.twig`
* changed `category.exposed.twig` to `content.exposed.twig`
* replace #hashtags in categories with a link
* replace #hashtags in lists with a link
* replace #hashtags in lists with a link

**0.16** - 2014-01-24

* start introducing editor roles - must change all /admin routes
* added security access rules and security entry points
* flexContent install also a PAGE add-on in the CMS to enable access to flexContent also over the pages and not only via Admin-Tools and a /kit2 login
* implement `Rating` and `Comments` for flexContent articles
* added support for kitCommands within the content

**0.15** - 2014-01-16

* changed handling of the search function
* added styles to the main editor (Sample, Variable, Code, Command KBD, empty SPAN, deleted Text)

**0.14** - 2014-01-13

* first public beta release

**0.10** - 2013-11-30

* initial release