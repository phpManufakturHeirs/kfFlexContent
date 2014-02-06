## flexContent for kitFramework ##

(c) 2014 phpManufaktur by Ralf Hertsch

MIT License (MIT) - <http://www.opensource.org/licenses/MIT>

kitFramework - <https://kit2.phpmanufaktur.de>

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